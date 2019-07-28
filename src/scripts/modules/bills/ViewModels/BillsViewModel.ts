import * as ko from "knockout";

class BillsViewModel {
    public IsLoading: KnockoutObservable<boolean> = ko.observable(true);
}

export = BillsViewModel;