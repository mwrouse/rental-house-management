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
    OnShow(): void;
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

interface IModalBuilder {

}

interface IBill {
    Id: string;
    Title: string;
    Amount: number;
    Remaining: number;
    DueDate: string;
    CreatedBy: ITenant;
    FullyPaid: boolean;
    AppliesTo: IAppliesTo[];
    Payments: IPayment[];
    PayTo: IRecipient;
    CreationDate: string;
}

interface IPayment {
    Amount: number;
    BillId: string;
    Date: string;
    PaidBy: ITenant;
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


interface IAppliesTo extends ITenant {
    Remaining: number;
    Paid: number;
}


interface IRecipient {
    Id: string;
    Name: string;
    PaymentMethods: IPaymentMethod[];
}

interface IPaymentMethod {
    Key: string;
    Display: string;
    Source: string;
}

interface IPaymentHandler {

}

interface IPermissions {
    Display: string;
    Key: string;
}