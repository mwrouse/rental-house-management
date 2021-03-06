<?php
require_once('database.php');

class ObjectStore {
    public static function Get($scope, $key=null) {
        try {
            $db = database();

            $qry = "SELECT value FROM object_store WHERE scope=?";
            if (!is_null($key))
                $qry .= " AND _key=?";

            if (!is_null($key)) {
                $results = $db->query("SELECT value FROM object_store WHERE scope=? AND _key=?", [
                    $scope,
                    $key
                ]);
            }
            else {
                $results = $db->query("SELECT value FROM object_store WHERE scope=?", [ $scope ]);
            }

            if (is_null($results)) {
                //error_log($scope . ' ' . $key . ' => ' . gettype($results));
                return null;
            }

            $final = [];
            foreach ($results as $result) {
                array_push($final, json_decode($result['value']));
            }

            if (count($final) == 0) {
                #error_log($scope . ' ' . $key . ' => null');
                return null;
            }

            if (count($final) == 1 && !is_null($key)) {
                #error_log($scope . ' ' . $key . ' => ' . json_encode($final[0]));
                return $final[0];
            }

            #error_log($scope . ' ' . $key . ' => ' . json_encode($final));
            return $final;
        }
        catch (Exception $e) {
            error_log("Exception in ObjectStore::Get(" . $scope . ", " . $key . "): " . $e);
            error_log($e);
            return null;
        }
    }

    public static function GetKeysForScope($scope) {
        try {
            $db = database();

            $res = $db->query("SELECT _key FROM object_store WHERE scope=?", [$scope]);

            $keys = [];
            foreach ($res as $row) {
                array_push($keys, $row['_key']);
            }

            return $keys;
        }
        catch (Exception $e) {
            error_log($e);
            return [];
        }
    }

    public static function Save($scope, $key, $value) {
        try {
            $db = database();

            if (self::DoesKeyExist($scope, $key))
            {
                $db->query("UPDATE object_store SET value=? WHERE scope=? AND _key=?", [
                    json_encode($value, JSON_NUMERIC_CHECK),
                    $scope,
                    $key
                ]);
            }
            else {
                $db->query("INSERT INTO object_store (scope, _key, value) VALUES (?, ?, ?)", [
                    $scope,
                    $key,
                    json_encode($value, JSON_NUMERIC_CHECK)
                ]);
            }
            return True;
        }
        catch (Exception $e) {
            error_log($e);
            return False;
        }
    }

    public static function Delete($scope, $key) {
        try {
            if (self::DoesKeyExist($scope, $key))
            {
                $db = database();
                $db->query("DELETE FROM object_store WHERE scope=? AND _key=?", [
                    $scope,
                    $key
                ]);
            }

            return True;
        }
        catch (Exception $e) {
            error_log($e);
            return False;
        }
    }

    public static function DoesKeyExist($scope, $key) {
        try {
            $db = database();

            $result = $db->query("SELECT value FROM object_store WHERE scope=? AND _key=?", [
                $scope,
                $key
            ]);

            if (is_null($result[0]) || is_null($result[0]['value']))
                return False;

            return True;
        }
        catch (Exception $e) {
            error_log($e);
            return False;
        }
    }
}