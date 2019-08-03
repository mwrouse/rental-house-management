<?php
require_once('authentication.php');
require_once('framework/include.php');

$Router->Get(AuthEndpoint('me'), function() {
    global $cookieName;

    $session = SessionManager::GetSession();

    return $session->CurrentUser;
    /*global $Router;
    $tenantID = $_COOKIE[$cookieName];

    $url = sprintf('/tenants/%s', $tenantID);
    $tenant = $Router->RunLocal('GET', $url);

    return $tenant->Data;*/
})->Authenticate();

