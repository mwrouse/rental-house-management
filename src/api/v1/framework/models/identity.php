<?php

class Identity {
    public $TenantId;
    public $Tenant;

    public $Permissions;


    public function Save($username, $password) {
        $data = [
            'TenantId' => $this->TenantId,
            'Permissions' => $this->Permissions,
            'Password' => password_hash($password, PASSWORD_DEFAULT)
        ];

        ObjectStore::Save('identities', $username, $data);
    }

    /**
     * Builds a Identity class
     */
    public static function Parse($raw) {
        if (is_array($raw))
            $raw = json_decode(json_encode($raw)); // Convert to object

        $ident = new Identity();
        $ident->TenantId = $raw->TenantId;
        $ident->Tenant = Tenant::Get($ident->TenantId);
        $ident->Permissions = $raw->Permissions;

        return $ident;
    }

    public static function Get($username) {
        $raw = ObjectStore::Get('identities', $username);
        if (is_null($raw))
            return null;

        return Identity::Parse($raw);
    }
}