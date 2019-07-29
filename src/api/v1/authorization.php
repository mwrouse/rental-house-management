<?php
require_once('authentication.php');
require_once('connection.php');
require_once('session.php');

$Router->Get(AuthEndpoint('me'), function() {
    global $cookieName;

    $session = SessionManager::GetSession();

    return $session->Tenant;
    /*global $Router;
    $tenantID = $_COOKIE[$cookieName];

    $url = sprintf('/tenants/%s', $tenantID);
    $tenant = $Router->RunLocal('GET', $url);

    return $tenant->Data;*/
})->Authenticate();

