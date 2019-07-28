<?php
require_once('connection.php');

$cookieName = "rental_login_token";

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
    global $cookieName;

    return isset($_COOKIE[$cookieName]);
});


/**
 * Endpoint to check if logged in
 */
$Router->Get(AuthEndpoint('ping'), function() {
    return True;
})->Authenticate();


/**
 * Logins in a user
 */
$Router->Get(AuthEndpoint('login'), function() {
    global $cookieName;

    $db = database();

    $result = $db->query("SELECT password FROM tenants WHERE username=?", [
        $this->Data['username']
      ]);

    if (is_null($result))
        return False;

    $hashed_pwd = $result[0]['password'];
    $access = password_verify($this->Data['password'], $hashed_pwd);

    if ($access)
        $this->SetCookie($cookieName, $this->Data['username']);
    else
        $this->RemoveCookie($cookieName);

    return $access;
})->RequiredData(['username', 'password']);


/**
 * Logs a user out
 */
$Router->Get(AuthEndpoint('logout'), function() {
    global $cookieName;

    $this->RemoveCookie($cookieName);
    return True;
})->Authenticate();