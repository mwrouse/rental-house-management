import * as ModuleLoader from "scripts/../modules/ModuleLoader";
var ko: KnockoutStatic = require('knockout');

class System {
    public Configuration: ISystemConfiguration = null;
    public Modules: IManifest[] = [];
    public CurrentUser: ITenant = null;


    public WhenReady: JQueryPromise<any>;
    private _readyDfd: JQueryDeferred<any>;
    public Hash: KnockoutObservable<string> = ko.observable('');

    constructor() {
        this._readyDfd = $.Deferred<any>();
        this.WhenReady = this._readyDfd.promise();


        this._beginLoad();

        this._ping();

        // Check if logged in every five minutes
        setInterval(() => {
            this._ping();
        }, 60000 * 5);

        this.Hash(window.location.hash.replace('#!/', ''));
        window.addEventListener('hashchange', () => {
            this.Hash(window.location.hash.replace('#!/', ''));
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

    /**
     * Loading
     */
    private _beginLoad = () => {
        this._loadCurrentUser()
            .then(this._loadConfiguration)
            .then(this._loadModules)
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


    private _ping = () => {
        $.get('/api/v1/auth/ping', (data: any) => {
            if (!data.Data) {
                window.location.href = '/login.html';
            }
        });
    };
}


let instance = new System();
export = instance;