import * as ko from "knockout";

class BillsViewModel {
    public IsLoading: KnockoutObservable<boolean> = ko.observable(true);

    public Bills: KnockoutObservableArray<IBill> = ko.observableArray([]);


    constructor() {
        this._loadBills();
    }

    public TotalBillCost = ko.computed(() => {
        let bills: IBill[] = this.Bills();
        let cost = 0;

        for (let i = 0; i < bills.length; i++) {
            let bill: IBill = bills[i];
            cost += bill.Amount;
        }
        function numberWithCommas(x) {
            return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
        return '$' + numberWithCommas(cost);
    });


    public BillsDueSoon = ko.computed(() => {
        let bills: IBill[] = this.Bills();
        let dueSoon = 0;

        let targetDate = new Date();
        targetDate.setDate(targetDate.getDate() + 3 /*days*/);

        for (let i = 0; i < bills.length; i++) {
            let bill: IBill = bills[i];
            let dueDate = new Date(bill.DueDate);
            if (dueDate <= targetDate)
                dueSoon++;
        }

        return dueSoon;
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