let modules = [
    "bills",
    "tenants"
];
//import BillsManifest = require("./bills/manifest");
//import TenantsManifest = require("./tenants/manifest");


class ModuleLoader implements IModuleLoader {
    private manifests: IManifest[] = [];

    constructor(){
        //this.manifests.push(BillsManifest);
        //this.manifests.push(TenantsManifest);
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