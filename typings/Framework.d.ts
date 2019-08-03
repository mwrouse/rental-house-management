interface IManifest {
    Title: string;
    NavIcon: string;
    Key: string;
    AssociatedPermissions: string[]; // Permissions for showing in the UI
    DemandPermission?: string; // Permission needed to see the module
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
    CurrentUser: ITenant;

    DoesUserHaveAccess(feature: string): boolean;
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
    Name: string;
    AbbreviatedName: string;
    Username: string;
    StartDate: string;
    EndDate: string;
    Permissions: string[];
}