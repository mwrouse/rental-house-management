var ko = require('knockout');
var system = require('system');

class TenantsViewModel {
    public IsLoading: KnockoutObservable<boolean> = ko.observable(true);
    public Tenants: KnockoutObservableArray<ITenant> = ko.observableArray([]);
    public System: ISystem = system;


    constructor() {
        this._loadTenants();
    }

    private _loadTenants() {
        let dfd = $.Deferred<any>();

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