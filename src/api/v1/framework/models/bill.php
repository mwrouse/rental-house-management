<?php


class Payment {
    public $BillId;
    public $Amount;
    public $PaidBy;
    public $Date;

    /**
     * Parses a payment
     */
    public static function Parse($raw) {
        if (is_null($raw))
            return null;

        if (is_array($raw))
            $raw = json_decode(json_encode($raw)); // Convert to object

        $payment = new Payment();
        $payment->BillId = $raw->BillId;
        $payment->PaidBy = Tenant::Get($raw->PaidBy);
        $payment->Date = $raw->Date;
        $payment->Amount = floatval($raw->Amount);

        return $payment;
    }

    /**
     * Gets all payments for a bill
     */
    public static function Get($billId) {
        $raw = ObjectStore::Get('payments', $billId);
        if (is_null($raw))
            return [];

        $final = [];
        foreach ($raw as $payment)
            array_push($final, Payment::Parse($payment));

        return $final;
    }
}

class Bill {
    public $Id;

    public $Title;
    public $DueDate;
    public $Amount;
    public $Remaining;
    public $PayTo;
    public $FullyPaid;
    public $CreationDate;

    public $AppliesTo;
    public $CreatedBy;

    public $Payments;
    public $Split;

    /**
     * Saves to the database
     */
    public function Save() {
        // Save the bill
        $serializableBill = [
            'Id' => $this->Id,
            'Title' => $this->Title,
            'Amount' => $this->Amount,
            'FullyPaid' => $this->FullyPaid,
            'DueDate' => $this->DueDate,
            'CreationDate' => $this->CreationDate,
            'CreatedBy' => $this->CreatedBy->Id,
            'AppliesTo' => array_map(function($p) { return $p->Id; }, $this->AppliesTo),
            'PayTo' => $this->PayTo->Id
        ];

        ObjectStore::Save('bills', $this->Id, $serializableBill);

        // Save the payments
        $payments = array_map(function($p) {
            $p->PaidBy = $p->PaidBy->Id;
            return $p;
        }, $this->Payments);

        ObjectStore::Save('payments', $this->Id, $payments);

        return $this;
    }

    /**
     * Builds a bill class
     */
    public static function Parse($raw) {
        if (is_array($raw))
            $raw = json_decode(json_encode($raw)); // Convert to object

        $bill = new Bill();

        $bill->Id = $raw->Id;
        $bill->Title = $raw->Title;
        $bill->DueDate = $raw->DueDate;
        $bill->Amount = floatval($raw->Amount);
        $bill->FullyPaid = $raw->FullyPaid;
        $bill->CreationDate = $raw->CreationDate;

        $bill->CreatedBy = Tenant::Get($raw->CreatedBy);

        // Populate who the bill applies to
        $bill->AppliesTo = [];
        foreach ($raw->AppliesTo as $appliedTo)
            array_push($bill->AppliesTo, Tenant::Get($appliedTo));

        $bill->PayTo = Recipient::Get($raw->PayTo);

        $bill->Payments = Payment::Get($bill->Id);


        // Calculate the remaining amount
        $payments = [];
        $bill->Remaining = $bill->Amount;
        foreach ($bill->Payments as $payment)
        {
            $bill->Remaining = $bill->Remaining - $payment->Amount;
            $payments[$payment->PaidBy->Id] += $payment->Amount;
        }

        // Calculate split
        if (count($bill->AppliesTo) > 0)
            $bill->Split = floatval($bill->Amount / count($bill->AppliesTo));
        else
            $bill->Split = $bill->Amount;

        foreach ($bill->AppliesTo as $appliesTo) {
            if (!array_key_exists($appliesTo->Id, $payments))
                $appliesTo->Paid = 0;
            else
                $appliesTo->Paid = $payments[$appliesTo->Id];

            $appliesTo->Remaining = $bill->Split - $appliesTo->Paid;
        }

        return $bill;
    }

    /**
     * Gets a single bill
     */
    public static function Get($id) {
        $raw = ObjectStore::Get('bills', $id);
        return Bill::Parse($raw);
    }


    public static function GetAll() {
        $raw = ObjectStore::Get('bills');

        $final = [];
        foreach ($raw as $bill)
            array_push($final, Bill::Parse($bill));

        usort($final, function($b1, $b2) {
            return strcmp($b1->DueDate, $b2->DueDate);
        });

        return $final;
    }
}