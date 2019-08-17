<?php
require_once('objectstore.php');
require_once('lib/Twilio/autoload.php');

use Twilio\Rest\Client;


function getTwilioClient() {
    $cfg = parse_ini_file("../twilio.ini");

    $keys = ['accountSID', 'authToken', 'number'];
    foreach ($keys as $key) {
        if (!array_key_exists($key, $cfg))
            throw new Exception("twilio.ini file is missing an entry for '" . $key ."'");
    }

    $twilio = new stdClass;
    $twilio->service = new Client($cfg['accountSID'], $cfg['authToken']);
    $twilio->config = $cfg;

    return $twilio;
}


class Notifier {
    /*****************************
     *           BILLS           *
     *****************************/
    public static function NewBill($bill, $actor) {
        $twilio = getTwilioClient();

        $msg = $actor->AbbreviatedName . " created '" . $bill->Title . "'\n";
        $msg .= 'Due: ' . $bill->DueDate . "\n";
        $msg .= 'Amount: $' . $bill->Amount . "\n";
        $msg .= 'Split: $' . $bill->Split;

        $sendTo = array_map(function ($i) { return $i->Phone; }, $bill->AppliesTo);

        Notifier::_SendSMS($msg, $sendTo);
    }

    /**
     * Send alert that a bill was paid
     */
    public static function PaidBill($bill, $amount, $actor) {
        $twilio = getTwilioClient();

        $msg = $actor->AbbreviatedName . " paid $" . $amount . " to '" . $bill->Title . "' \n";
        $msg .= "Remaining: $" . $bill->Remaining . " \n";
        $msg .= "\nPaid So Far:\n";

        // Mark down who has paid what
        foreach ($bill->AppliesTo as $appliesTo) {
            $msg .= $appliesTo->AbbreviatedName . ": $";
            $amt = 0;

            foreach ($bill->Payments as $payment) {
                if ($payment->PaidBy->Id == $appliesTo->Id)
                    $amnt += $payment->Amount;
            }

            $msg .= strval($amnt) . "\n";
        }

        // Get phone numbers to send this to
        $sendTo = $bill->PayTo->Phone;
        if ($sendTo == "")
            $sendTo = array_map(function ($i) { return $i->Phone; }, $bill->AppliesTo);
        else
            $msg = $bill->PayTo->Name . ",\n" . $msg;

        Notifier::_SendSMS($msg, $sendTo); // Send the SMS
    }

    public static function DeletedBill($bill, $actor) {
        $twilio = getTwilioClient();
    }



    private static function _SendSMS($msg, $to) {
        if (!is_array($to))
            $to = [$to];

        $twilio = getTwilioClient();

        foreach ($to as $number)
        {
            try {
                $msg = $twilio->service->messages
                                        ->create($number,
                                                [
                                                    "body" => $msg,
                                                    "from" => $twilio->config['number']
                                                ]);
            }
            catch (Exception $e) {
                error_log("Exception trying to send SMS");
                error_log($e);
            }
        }
    }

}