interface IManifest {
    Title: string;
    NavIcon: string;
    Key: string;
    DefaultArea: string;
}

interface IModuleLoader {
    GetModules(): JQueryPromise<IManifest[]>;
}

interface IViewModel {

}