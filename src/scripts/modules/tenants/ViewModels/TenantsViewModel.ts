var ko = require('knockout');
var system = require('system');

function numberWithCommas(x) {
    return x.toFixed(2).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

class TenantsViewModel {
    public IsLoading: KnockoutObservable<boolean> = ko.observable(true);
    public Tenants: KnockoutObservableArray<ITenant> = ko.observableArray([]);
    public System: ISystem = system;


    constructor() {
        this._loadTenants();
    }


    public GoToNewTenantPage = (): void => {
        system.ChangeHash('tenants/new');
    };

    public GetTenantTotalRemaining = (tenant: ITenant): KnockoutComputed<string> => {
        return ko.computed(() => {
            return '$' + numberWithCommas((tenant as any).TotalRemaining);
        });
    }


    public DeleteTenant = (tenant: ITenant): void => {
        this.IsLoading(true);

        $.post('/api/v1/tenants/' + tenant.Id + '/delete')
        .done(() => {
            window.location.reload();
        });
    };



    public SubmitNewTenant = (): JQueryPromise<any> => {
        let dfd = $.Deferred<any>();
        this.IsLoading(true);

        let nameEl = document.getElementById('tenantName') as HTMLInputElement;
        let phoneEl = document.getElementById('tenantNumber') as HTMLInputElement;
        let amountEl = document.getElementById('billAmount') as HTMLInputElement;
        let payToEl = document.getElementById('payTo') as HTMLSelectElement;
        let permissionEls = document.getElementsByName('permissions') as NodeListOf<HTMLInputElement>;
        let permissions = [];
        permissionEls.forEach(element => {
            if (element.checked)
                permissions.push(element.value);
        });

        let usernameEl = document.getElementById('username') as HTMLInputElement;
        let password1El = document.getElementById('password') as HTMLInputElement;
        let password2El = document.getElementById('password2') as HTMLInputElement;

        let invalid = false;
        let invalidate = (el) => {
            el.classList.add('is-invalid');
            invalid = true;
        };
        let clear = (el) => {
            el.classList.remove('is-invalid');
        }

        clear(password1El);
        clear(password2El);
        if (password1El.value != password2El.value)
        {
            invalidate(password1El);
            invalidate(password2El);
        }

        clear(nameEl);
        if (nameEl.value == "" || nameEl.value.split(' ').length != 2)
            invalidate(nameEl);

        clear(phoneEl);
        if (phoneEl.value == "")
            invalidate(phoneEl);

        clear(usernameEl);
        if (usernameEl.value == "")
            invalidate(usernameEl);

        if (invalid) {
            dfd.reject();
            this.IsLoading(false);
            return;
        }

        let firstName = nameEl.value.split(' ')[0];
        let lastName = nameEl.value.split(' ')[1];

        let data = {
            FirstName: firstName,
            LastName: lastName,
            Phone: phoneEl.value,
            Permissions: permissions,
            Username: usernameEl.value,
            Password: password1El.value,
        };

        $.post('/api/v1/tenants/new', data)
            .done(() => {
                system.ChangeHash('tenants');
                window.location.reload();
            });

        return dfd.promise();
    };

    private _loadTenants() {
        let dfd = $.Deferred<any>();
        this.IsLoading(true);

        $.get('/api/v1/tenants', (data: any) => {
            let tenants: ITenant[] = data.Data;
            this.Tenants(tenants);
            this.IsLoading(false);
            dfd.resolve();
        });
        return dfd.promise();
    }
}

export = TenantsViewModel;