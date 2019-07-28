import * as ko from "knockout";


function numberWithCommas(x) {
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

class BillsViewModel {
    public IsLoading: KnockoutObservable<boolean> = ko.observable(true);

    public Bills: KnockoutObservableArray<IBill> = ko.observableArray([]);


    constructor() {
        this._loadBills();
    }

    // Cost of all the bills
    public TotalBillCost = ko.computed(() => {
        let bills: IBill[] = this.Bills();
        let cost = 0;

        for (let i = 0; i < bills.length; i++) {
            let bill: IBill = bills[i];
            cost += bill.Amount;
        }

        return '$' + numberWithCommas(cost);
    });

    // Number of bills that are due soon
    public BillsDueSoon = ko.computed(() => {
        let bills: IBill[] = this.Bills();
        let dueSoon = 0;

        for (let i = 0; i < bills.length; i++) {
            let dueIn = this.GetNumberOfDaysUntilBillIsDue(bills[i]);
            if (dueIn <= 3 && dueIn >= 0)
                dueSoon++;
        }

        return dueSoon;
    });

    // Get number of overdue bills
    public OverDueBills = ko.computed(() => {
        let bills: IBill[] = this.Bills();
        let overDue = 0;

        for (let i = 0; i < bills.length; i++) {
            let dueIn = this.GetNumberOfDaysUntilBillIsDue(bills[i]);
            if (dueIn < 0)
                overDue++;
        }

        return overDue;
    });


    // Format as currency
    public GetBillAmount = (bill: IBill): string => {
        return '$' + numberWithCommas(bill.Amount);
    };


    public GetBillRowClass = (bill: IBill): KnockoutComputed<string> => {
        return ko.computed(() => {
            let days = this.GetNumberOfDaysUntilBillIsDue(bill);

            if (days < 0)
                return "bill-red";
            else if (days < 5)
                return "bill-yellow";

            return "";
        });
    };

    public GetNumberOfDaysUntilBillIsDue = (bill: IBill): number => {
        let today: any = new Date();
        let billDueDate: any = new Date(bill.DueDate);
        var res = (billDueDate - today) / 1000;
        var days = Math.floor(res / 86400);
        return days
    };


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