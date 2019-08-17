<?php
require_once('database.php');



class Events {
    /*****************************
     *           BILLS           *
     *****************************/
    public static function AddedBill($bill, $actor) {
        $db = database();
    }

    public static function PaidBill($bill, $amount, $actor) {
        $db = database();
    }

    public static function DeletedBill($bill, $actor) {
        $db = database();
    }
}