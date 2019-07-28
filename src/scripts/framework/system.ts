import * as ModuleLoader from "scripts/../modules/ModuleLoader";


class System {
    public Configuration: ISystemConfiguration = null;
    public Modules: IManifest[] = [];
    public CurrentUser: ITenant = null;

    public WhenReady: JQueryPromise<any>;
    private _readyDfd: JQueryDeferred<any>;

    constructor() {
        this._readyDfd = $.Deferred<any>();
        this.WhenReady = this._readyDfd.promise();

        this._beginLoad();
    }


    // Check if user has a certain permission
    public DoesUserHaveAccess(feature: string): boolean {
        for (let i = 0; i < this.CurrentUser.Permissions.length; i++) {
            if (this.CurrentUser.Permissions[i] == feature)
                return true;
        }
        return false;
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
}


let instance = new System();
export = instance;