<?php
require_once('connection.php');

$cookieName = 'login_token';

if (!function_exists('uuid')) {
    function uuid() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}

class SessionManager {
    private $db;

    public $User = null;
    public $UserName = 'Anonymous';

    public $Token;
    public $TenantID;

    private $Expiration;

    public function __construct() {
        global $cookieName;
        $this->db = database();

        if (isset($_COOKIE[$cookieName]))
            $this->Token = $_COOKIE[$cookieName];
        else
            $this->Token = null;

        if (!is_null($this->Token))
            $this->_getTenant();
    }

    // Check if the session exists
    public function DoesSessionExist() {
        $isNull = is_null($this->Token);

        if (!$isNull) {
            if (time() > $this->Expiration)
            {
                $this->DestroySession();
                return false;
            }
        }
        return !$isNull;
    }


    private function _getTenant() {
        $result = $this->db->query("SELECT TenantID, Expiration from sessions WHERE Token=?", [
            $this->Token
        ]);
        $result = $result[0];

        $this->Expiration = $result['Expiration'];

        $this->TenantID = $result['TenantID'];

        $tenantQry = $this->db->Query("Select Id, FirstName, LastName, StartDate, EndDate, username, Permissions
            FROM tenants WHERE Id=?", [ $this->TenantID ]);
        $tenant = $tenantQry[0];
        $this->Tenant = $tenant;
        $this->Username = $tenant['username'];
    }

    // For making the session manager singleton
    private static $instance;
    public static function GetSession() {
        if (!isset(self::$instance))
            self::$instance = new SessionManager();

        self::_cleanDatabase();

        return self::$instance;
    }

    // Creates a new session
    public static function CreateSession($tenantID, $longRunning = False) {
        global $cookieName;
        $db = database();

        $sessionID = uuid();
        $expiration = time() + (86400 * ($longRunning ? 15 : 5));

        $db->query("INSERT INTO sessions (Token, TenantID, Expiration) VALUES (?, ?, ?)", [
            $sessionID,
            $tenantID,
            $expiration
        ]);

        $_COOKIE[$cookieName] = $sessionID;
        setcookie($cookieName, $sessionID, $expiration, "/", $_SERVER['SERVER_NAME'], True, True);

        self::$instance = null;
        return self::GetSession();
    }

    // Destroys the current session
    public function DestroySession() {
        global $cookieName;

        // Delete cookie
        unset($_COOKIE[$cookieName]);
        setcookie($cookieName, null, -1, "/", $_SERVER['SERVER_NAME'], True, True);

        // Delete from database
        $this->db->query("DELETE FROM sessions WHERE Token=?", [$this->Token]);

        $this->Token = null;
        $this->User = null;
        $this->TenantID = null;
        $this->User = 'Anonymous';
    }

    // Removes old sessions
    private static function _cleanDatabase() {
        $now = time();
        $db = database();
        $db->query("DELETE FROM sessions WHERE Expiration <= ?", [ $now ]);
    }
}