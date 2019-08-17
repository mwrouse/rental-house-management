<?php

class PaymentMethod {
    public $Key;
    public $Source;
    public $Display;

    /**
     * Builds a Recipient class
     */
    public static function Parse($raw) {
        if (is_array($raw))
            $raw = json_decode(json_encode($raw)); // Convert to object

        $method = new PaymentMethod();
        $method->Key = $raw->Key;
        $method->Source = $raw->Source;
        $method->Display = $raw->Display;

        return $method;
    }
}


class Recipient {
    public $Id;
    public $Name;
    public $PaymentMethods;
    public $Phone;

    /**
     * Builds a Recipient class
     */
    public static function Parse($raw) {
        if (is_array($raw))
            $raw = json_decode(json_encode($raw)); // Convert to object

        $recip = new Recipient();
        $recip->Id = $raw->Id;
        $recip->Name = $raw->Name;
        $recip->Phone = $raw->Phone;

        $recip->PaymentMethods = [];
        foreach ($raw->PaymentMethods as $paymentMethod)
            array_push($recip->PaymentMethods, PaymentMethod::Parse($paymentMethod));

        return $recip;
    }

    /**
     * Gets a specific recipient
     */
    public static function Get($id) {
        $raw = ObjectStore::Get('recipient', $id);
        return Recipient::Parse($raw);
    }

    /**
     * Retrieves all Recipients
     */
    public static function GetAll() {
        $raw = ObjectStore::Get('recipient');

        $recipients = [];

        foreach ($raw as $rawRecipient) {
            array_push($recipients, Recipient::Parse($rawRecipient));
        }

        return $recipients;
    }
}