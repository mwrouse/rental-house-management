<?php
require_once('database.php');
require_once('utils.php');
require_once('objectstore.php');

$cookieName = 'login_Id';


class SessionManager {
    private $db;

    public $User = null;
    public $UserName = 'Anonymous';

    public $Id;
    public $TenantID;

    public $CurrentUser;

    private $Sessions; // All sessions
    private $Session = null; // Current session


    public function __construct() {
        global $cookieName;

        if (isset($_COOKIE[$cookieName]))
            $this->Id = $_COOKIE[$cookieName];
        else
            $this->Id = null;

        if (!is_null($this->Id)) {
            $this->_getCurrentSession();
            $this->_getCurrentUser();
        }
    }

    // Check if the session exists
    public function DoesSessionExist() {
        $isNull = is_null($this->Session);

        if (!$isNull) {
            if (time() > $this->Session->Expiration)
            {
                $this->DestroySession();
                return false;
            }
        }

        return !$isNull;
    }



    // For making the session manager singleton
    private static $instance;
    public static function GetSession() {
        self::_cleanDatabase();

        if (!isset(self::$instance))
            self::$instance = new SessionManager();

        return self::$instance;
    }

    // Creates a new session
    public static function CreateSession($username, $longRunning = False) {
        global $cookieName;

        $sessions = self::_getAllSessions();

        // Make a new session
        $sessionId = Guid::NewGuid();
        $expiration = time() + (86400 * ($longRunning ? 15 : 5));
        $session = [
            'Id' => $sessionId,
            'Expiration' => $expiration,
            'Username' => $username
        ];

        array_push($sessions, $session);
        ObjectStore::Save('app', 'sessions', $sessions);

        $_COOKIE[$cookieName] = $sessionId;
        setcookie($cookieName, $sessionId, $expiration, "/", $_SERVER['SERVER_NAME'], True, True);

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
        foreach ($this->Sessions as $i => $session) {
            if ($session->Id == $this->Session->Id)
            {
                unset($this->Sessions[$i]);
                break;
            }
        }
        ObjectStore::Save('app', 'sessions', $this->Sessions);

        $this->Session = null;
        $this->CurrentUser = null;
    }


    // Gets and saves the current session
    private function _getCurrentSession() {
        $this->Sessions = ObjectStore::Get('app', 'sessions');

        foreach ($this->Sessions as $session) {
            if ($session->Id == $this->Id) {
                $this->Session = $session;
                return;
            }
        }
    }

    private function _getCurrentUser() {
        if (is_null($this->Session))
            return;

        $user = ObjectStore::Get('identities', $this->Session->Username);
        if (is_null($user))
            return;

        $this->CurrentUser = ObjectStore::Get('tenants', $user->TenantId);
        if (is_null($this->CurrentUser))
            return;

        $this->CurrentUser->Permissions = $user->Permissions;
    }

    private static function _getAllSessions() {
        $sessions = ObjectStore::Get('app', 'sessions');
        if (is_null($sessions))
            return [];
        return $sessions;
    }

    // Removes old sessions
    private static function _cleanDatabase() {
        $now = time();

        $sessions = ObjectStore::Get('app', 'sessions');
        foreach ($sessions as $i => $session) {
            if ($session->Expiration <= $now)
                unset($sessions[$i]);
        }

        ObjectStore::Save('app', 'sessions', $sessions);
    }

}