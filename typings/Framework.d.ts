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


interface IBill {
    Id: string;
    Title: string;
    Amount: number;
    DueDate: string;
    Creator: ITenant;
    FullyPaid: boolean;
}

interface ITenant {
    Id: string;
    FirstName: string;
    LastName: string;
    Username: string;
    StartDate: string;
    EndDate: string;
}