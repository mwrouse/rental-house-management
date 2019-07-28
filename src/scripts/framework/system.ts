import * as ModuleLoader from "scripts/../modules/ModuleLoader";


class System {
    public Configuration: ISystemConfiguration = null;
    public Modules: IManifest[] = [];

    public WhenReady: JQueryPromise<any>;
    private _readyDfd: JQueryDeferred<any>;

    constructor() {
        this._readyDfd = $.Deferred<any>();
        this.WhenReady = this._readyDfd.promise();

        this._beginLoad();
    }

    private _beginLoad = () => {
        this._loadModules()
            .then(this._loadConfiguration)
            .then(() => {
                this._readyDfd.resolve();
            });
    };

    private _loadModules = () => {
        return ModuleLoader.GetModules()
            .then((modules) => {
                this.Modules = modules;
            });
    };

    private _loadConfiguration = () => {
        let dfd = $.Deferred();
        $.get("/api/v1/configuration", (raw: any) => {
            if (raw.Data === null)
              return; // :(

            this.Configuration = raw.Data as ISystemConfiguration;
            dfd.resolve();
          });
        return dfd.promise();
    };
}


let instance = new System();
export = instance;