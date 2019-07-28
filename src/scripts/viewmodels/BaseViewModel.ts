import * as ko from "knockout";
var system: ISystem = require('system');

/**
 * This is the base view model, it will load configuration from
 * the database as well as contain access to the actual view model
 * (for the bills page, history page, etc...)
 */
class BaseViewModel {
    public System: ISystem = system;

    public ActiveModule: KnockoutObservable<IManifest>;

    public IsLoading: KnockoutObservable<boolean> = ko.observable(true);

    constructor() {
        this.ActiveModule = ko.observable(this.System.Modules[0]);
        this.IsLoading(false);
    }


    public ChangeView = (manifest: IManifest) => {
        this.ActiveModule(manifest);
    };

    public _changeViewModel() {
        /*let hash = window.location.hash;
        if (hash == "") {
            this.ViewModel = HomeViewModel_1.default;
        }*/
    }
}

export = BaseViewModel