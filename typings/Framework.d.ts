interface IManifest {
    Title: string;
    NavIcon: string;
    Key: string;
    DefaultArea: string;
}

interface IModuleLoader {
    GetModules(): IManifest[];
}

interface IViewModel {

}