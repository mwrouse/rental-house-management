import BillsManifest = require("./bills/manifest");


class ModuleLoader implements IModuleLoader {
    private manifests: IManifest[] = [];

    constructor(){
        this.manifests.push(BillsManifest);
    }

    public GetModules(): IManifest[] {
        return this.manifests;
    }
}


let instance = new ModuleLoader();
export = instance;