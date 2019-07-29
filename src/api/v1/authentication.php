<?php
require_once('framework/include.php');

/**
 * Authentication Endpoints
 */
function AuthEndpoint($uri) {
    if ($uri[0] == '/') {
        return '/auth' . $uri;
    }
    return '/auth' . '/' . $uri;
}


/**
 * Verifies that the user exists
 */
$Router->SetAuthenticationMethod(function() {
    return $this->Session->DoesSessionExist();
});


/**
 * Endpoint to check if logged in
 */
$Router->Get(AuthEndpoint('ping'), function() {
    return $this->Session->DoesSessionExist();
});


/**
 * Logins in a user
 */
$Router->Post(AuthEndpoint('login'), function() {
    $db = database();

    $result = $db->query("SELECT Id, password FROM tenants WHERE username=?", [
        strtolower($this->Data['Username'])
      ]);

    if (is_null($result))
        return false;

    $hashed_pwd = trim($result[0]['password']);

    $access = password_verify($this->Data['Password'], $hashed_pwd);

    if ($access) {
        SessionManager::CreateSession($result[0]['Id']);
    }

    return $access;
})->RequiredData(['Username', 'Password', 'RememberMe']);


/**
 * Logs a user out
 */
$Router->Get(AuthEndpoint('logout'), function() {
    $this->Session->DestroySession();
    return True;
})->Authenticate();