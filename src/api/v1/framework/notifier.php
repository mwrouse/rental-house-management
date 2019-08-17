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

        $msg = $actor->FirstName . " created a new bill '" . $bill->Title . "', due on " . $bill->DueDate . " with a total of $" . $bill->Amount;

        foreach ($bill->AppliesTo as $appliesTo) {
            $tenant = ObjectStore::Get('tenants', $appliesTo);
            try {
                $msg = $twilio->service->messages
                                        ->create($tenant->Phone,
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

    public static function PaidBill($bill, $amount, $actor) {
        $twilio = getTwilioClient();
    }

    public static function DeletedBill($bill, $actor) {
        $twilio = getTwilioClient();
    }

}