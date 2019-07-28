interface IManifest {
    Title: string;
    NavIcon: string;
    Key: string;
}

interface IPartialView {

}

interface IModuleLoader {
    GetModules(): JQueryPromise<IManifest[]>;
}

interface IViewModel {
    OnLoad(): void;
    OnHide(): void;
}