var ko = require('knockout');
var system = require('system');

function numberWithCommas(x) {
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

class BillsViewModel {
    public IsLoading: KnockoutObservable<boolean> = ko.observable(true);
    public Bills: KnockoutObservableArray<IBill> = ko.observableArray([]);
    public System: ISystem = system;


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

    public TotalRemaining = ko.computed(() => {
        let bills: IBill[] = this.Bills();
        let cost = 0;

        for (let i = 0; i < bills.length; i++) {
            let bill: IBill = bills[i];
            cost += bill.Remaining;
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

    public GetBillRemaining = (bill: IBill): string => {
        return '$' + numberWithCommas(bill.Remaining);
    };

    public GetBillSplitAmount = (bill: IBill): number => {
        if (bill.AppliesTo.length == 0)
            return bill.Amount;
        return bill.Amount / bill.AppliesTo.length;
    };
    public GetBillSplitAmountFormatted = (bill: IBill): string => {
        return '$' + numberWithCommas(this.GetBillSplitAmount(bill))
    };


    public GetBillRowClass = (bill: IBill): KnockoutComputed<string> => {
        return ko.computed(() => {
            let days = this.GetNumberOfDaysUntilBillIsDue(bill);

            if (days < 0)
                return "table-danger";
            else if (days < 5)
                return "table-warning";

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


    public GetBillCollapseId = (bill: IBill, hash: boolean = false) => {
        return (hash?'#':'') + 'collapse-bill-' + bill.Id;
    };

    public GetPaymentString = (payment: IPayment) => {
        return payment.PaidBy.AbbreviatedName + ' payed $' + numberWithCommas(payment.Amount) + ' on ' + payment.Date + '.';
    }

    public ActiveBill: KnockoutObservable<IBill> = ko.observable(null);
    public PayBill = (bill: IBill): void => {
        this.ActiveBill(bill);
        system.ChangeHash('bills/pay');
    };


    // Gets the active bill that is used for subpages of the bill page
    public GetActiveBillForPage = ko.computed(() => {
        let bill = this.ActiveBill();
        if (bill == null)
        {
            if (system.Hash() == 'bills/pay') {
                console.warn('No active bill');
                system.ChangeHash('bills');
            }
            return null;
        }
        return bill;
    });

    public DoesActiveBillApplyToUser = ko.computed(() =>{
        let bill = this.ActiveBill();
        if (bill == null)
            return;

        for (let tenantToPay of bill.AppliesTo)
        {
            if (tenantToPay == system.CurrentUser.Id)
                return true;
        }
        return false;
    });


    public HasUserPaidActiveBill = ko.computed(() => {
        let bill = this.ActiveBill();
        if (bill == null)
            return;

        for (let payment of bill.Payments) {
            if (payment.PaidBy.Id == system.CurrentUser.Id)
                return true;
        }
        return false;
    });

    public GetAmountPaidByUser = (bill: IBill): number => {
        let total = 0;
        for (let payment of bill.Payments) {
            if (payment.PaidBy.Id == system.CurrentUser.Id)
                total += payment.Amount;
        }
        return total;
    };

    public GetUserAlreadyPaidNotice = (bill: IBill): string => {
        let split = this.GetBillSplitAmount(bill);
        let amountAlreadyPaid = this.GetAmountPaidByUser(bill);
        let remaining = split - amountAlreadyPaid;

        return 'You have already paid $' + numberWithCommas(amountAlreadyPaid) + ' to this bill. You have $' + numberWithCommas(remaining) + ' remaining.';
    };

    public DefaultPaymentAmount = ko.computed(() => {
        let bill = this.ActiveBill();
        if (bill == null)
            return 0.00;

        return 5.00;
    });


    public MakeBillPayment = (method: IPaymentMethod) => {
        let bill = this.ActiveBill();

        let amountEl = document.getElementById('paymentAmount') as HTMLInputElement;
        let amount = amountEl.value;

        this.IsLoading(true);

        let dfd = $.Deferred<any>();

        $.post('/api/v1/bills/' + bill.Id + '/payments/new', {
            Amount: parseFloat(amount)
        })
        .done(() => {
            dfd.resolve();
            this.ActiveBill(null);
            system.ChangeHash('bills');
            this.IsLoading(false);

            system.PaymentHandler.HandlePayment(amount, method, bill);
        });
        amountEl.value = null;

        return dfd.promise();
    };


    public GoToNewBillPage = (): void => {
        system.ChangeHash('bills/new');
    };


    public SubmitNewBill = (): JQueryPromise<any> => {
        let dfd = $.Deferred<any>();
        this.IsLoading(true);

        let nameEl = document.getElementById('billName') as HTMLInputElement;
        let dueEl = document.getElementById('billDueDate') as HTMLInputElement;
        let amountEl = document.getElementById('billAmount') as HTMLInputElement;
        let payToEl = document.getElementById('payTo') as HTMLSelectElement;
        let appliesToEls = document.getElementsByName('appliesTo') as NodeListOf<HTMLInputElement>;
        let appliesTo = [];
        appliesToEls.forEach(element => {
            if (element.checked)
                appliesTo.push(element.value);
        });

        let data = {
            Title: nameEl.value,
            Amount: amountEl.value,
            DueDate: dueEl.value,
            AppliesTo: appliesTo,
            PayTo: payToEl.value,
        };

        $.post('/api/v1/bills/new', data)
            .done(() => {
                this.IsLoading(false);
                system.ChangeHash('bills');
                nameEl.value = null;
                dueEl.value = null;
                amountEl.value = null;
                payToEl.value = null;
                appliesToEls.forEach((element) => {
                    element.checked = false;
                });
            });

        return dfd.promise();
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