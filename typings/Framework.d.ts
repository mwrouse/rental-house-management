interface IManifest {
    Title: string;
    NavIcon: string;
    Key: string;
}

interface IModuleLoader {
    GetModules(): JQueryPromise<IManifest[]>;
}

interface IViewModel {
    OnLoad(): void;
    OnHide(): void;
}

interface ISystemConfiguration {
    SiteName: string;
}

interface ISystem {
    WhenReady: JQueryPromise<any>;
    Modules: IManifest[];
    Configuration: ISystemConfiguration;
}