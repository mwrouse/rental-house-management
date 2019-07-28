let modules = [
    "bills",
    "tenants"
];

class ModuleLoader implements IModuleLoader {
    private manifests: IManifest[] = [];

    constructor(){
    }

    public GetModules(): JQueryPromise<IManifest[]> {
        let dfd = $.Deferred<IManifest[]>();

        if (this.manifests.length > 0)
        {
            dfd.resolve(this.manifests);
            return dfd.promise();
        }

        // Load modules
        modules = modules.map((module) => "./" + module + "/manifest");

        require(modules, (...manifests) => {
            this.manifests = manifests;
            dfd.resolve(this.manifests);
        });

        return dfd.promise();
    }
}


let instance = new ModuleLoader();
export = instance;