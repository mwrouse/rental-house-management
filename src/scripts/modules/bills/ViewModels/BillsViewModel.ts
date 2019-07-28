import * as ko from "knockout";

class BillsViewModel {
    public IsLoading: KnockoutObservable<boolean> = ko.observable(true);

    public Bills: KnockoutObservableArray<IBill> = ko.observableArray([]);


    constructor() {
        this._loadBills();
    }


    public TotalBillCost = ko.computed(() => {
        let cost = 0;
        let bills: IBill[] = this.Bills();

        for (let i = 0; i < bills.length; i++) {
            let bill: IBill = bills[i];
            cost += bill.Amount;
        }

        return cost;
    });

    private _loadBills() {
        let dfd = $.Deferred<any>();

        $.get('/api/v1/bills', (data: any) => {
            let bills: IBill[] = data.Data;
            this.Bills(bills);
            this.IsLoading(false);
            dfd.resolve();
        });
        return dfd.promise();
    }
}

export = BillsViewModel;