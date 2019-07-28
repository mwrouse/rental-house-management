<?php
require_once('authentication.php');
require_once('connection.php');

$Router->Get(AuthEndpoint('me'), function() {
    global $cookieName;
    global $Router;
    $tenantID = $_COOKIE[$cookieName];

    $url = sprintf('/tenants/%s', $tenantID);
    $tenant = $Router->RunLocal('GET', $url);

    return $tenant->Data;
})->Authenticate();

