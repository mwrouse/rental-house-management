import * as ModuleLoader from "scripts/../modules/ModuleLoader";
import * as PaymentHandler from "scripts/../framework/paymentHandler";

var ko: KnockoutStatic = require('knockout');

class System {
    public Configuration: ISystemConfiguration = null;
    public Modules: IManifest[] = [];
    public PaymentHandler: IPaymentHandler = PaymentHandler;

    public Tenants: KnockoutObservableArray<ITenant> = ko.observableArray([]);
    public Recipients: KnockoutObservableArray<IRecipient> = ko.observableArray([]);
    public Bills: KnockoutObservableArray<IBill> = ko.observableArray([]);

    public CurrentUser: ITenant = null;


    public WhenReady: JQueryPromise<any>;
    private _readyDfd: JQueryDeferred<any>;
    public Hash: KnockoutObservable<string> = ko.observable('');
    public Search: KnockoutObservable<string> = ko.observable('');

    constructor() {
        this._readyDfd = $.Deferred<any>();
        this.WhenReady = this._readyDfd.promise();

        this._beginLoad();

        this._ping();

        // Check if logged in every five minutes
        setInterval(() => {
            this._ping();
        }, 60000 * 5);

        this.Hash(window.location.hash.replace('#!/', '').split('?')[0]);
        window.addEventListener('hashchange', () => {
            let hash = window.location.hash.replace('#!/', '').split('?');
            this.Hash(hash[0]);

            if (hash.length > 1)
                this.Search(hash[1]);
            else
                this.Search('');
        });
    }


    // Check if user has a certain permission
    public DoesUserHaveAccess(feature: string): boolean {
        for (let i = 0; i < this.CurrentUser.Permissions.length; i++) {
            if (this.CurrentUser.Permissions[i] == feature)
                return true;
        }
        return false;
    };

    // Used for checking hashes
    public IsOnPage = (hash: any): KnockoutComputed<boolean> => {
        return ko.computed<boolean>(() => {
            let pageHash = this.Hash();

            if (typeof(hash) == 'string')
                return pageHash === hash;

            return hash.match(pageHash) != null;
        });
    }

    public ChangeHash = (hash: any): void => {
        window.location.hash = '#!/' + hash;
    };

    public GetURLParameter = (paramName: string, url: string = window.location.href): string => {
        let param: string = '';

        var regex = new RegExp("[?&]" + paramName + "(=([^&]*)|&|#|$)"),
            results = regex.exec(url);

        if (results && results[2])
            param = decodeURIComponent(results[2].replace(/\+/g, " "));

        return param;
    };

    /**
     * Loading
     */
    private _beginLoad = () => {
        this._loadCurrentUser()
            .then(this._loadConfiguration)
            .then(this._loadModules)
            .then(this._loadTenants)
            .then(this._loadRecipients)
            .then(() => {
                this._readyDfd.resolve();
            });
    };

    private _loadModules = () => {
        return ModuleLoader.GetModules()
            .then((modules) => {
                for (let i = 0; i < modules.length; i++) {
                    let module = modules[i];
                    if (module.DemandPermission)
                    {
                        if (!this.DoesUserHaveAccess(module.DemandPermission))
                            continue;
                    }
                    this.Modules.push(module);
                }
            });
    };

    private _loadConfiguration = () => {
        let dfd = $.Deferred();
        $.get("/api/v1/configuration", (raw: any) => {
            if (raw.Data === null) {
                dfd.reject();
                return; // :(
            }

            this.Configuration = raw.Data as ISystemConfiguration;
            dfd.resolve();
          });
        return dfd.promise();
    };

    private _loadCurrentUser = () => {
        let dfd = $.Deferred();
        $.get('/api/v1/auth/me', (raw: any) => {
            if (raw.Data === null) {
                dfd.reject();
                return;
            }

            this.CurrentUser = raw.Data as ITenant;
            dfd.resolve();
        });
        return dfd.promise();
    };

    private _loadTenants = (): JQueryPromise<any> => {
        let dfd = $.Deferred<any>();

        $.get('/api/v1/tenants', (data: any) => {
            let tenants: ITenant[] = data.Data;
            this.Tenants(tenants);
            dfd.resolve();
        });
        return dfd.promise();
    };

    private _loadRecipients = (): JQueryPromise<any> => {
        let dfd = $.Deferred<any>();
        $.get('/api/v1/recipients', (data: any) => {
            let recipients = data.Data;
            this.Recipients(recipients);
            dfd.resolve();
        });
        dfd.resolve();
        return dfd.promise();
    };

    private _ping = () => {
        $.get('/api/v1/auth/ping', (data: any) => {
            if (!data.Data) {
                window.location.href = '/login.html?path=' + encodeURIComponent(window.location.hash);
            }
        });
    };
}


let instance = new System();
export = instance;