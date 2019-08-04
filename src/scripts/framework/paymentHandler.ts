/**
 * The payment handler class handles payment methods
 * such as opening a link to the specified url and such.
 *
 */

function replaceUrlParameters(url: string, amount: number, bill: IBill) {
    url = url.replace('{amount}', amount.toString());
    url = url.replace('{billId}', bill.Id);

    return url;
}

let paymentMethods = [
    {
        key: 'paypal',
        handle: (amount: number, method: IPaymentMethod, bill: IBill) => {
            let url = replaceUrlParameters(method.Source, amount, bill);
            window.open(url, '_blank');
        }
    }
];



class PaymentHandler implements IPaymentHandler {
    public HandlePayment = (amount: number, methodToUse: IPaymentMethod, bill: IBill): JQueryPromise<any> => {
        let dfd = $.Deferred<any>();

        let key = methodToUse.Key;

        for (let method of paymentMethods) {
            if (method.key == key) {
                method.handle(amount, methodToUse, bill);
                break;
            }
        }

        dfd.resolve();

        return dfd.promise();
    }
}

let instance = new PaymentHandler();
export = instance;