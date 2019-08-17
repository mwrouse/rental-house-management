<?php

class Tenant {
    public $Id;

    public $FirstName;
    public $LastName;
    public $Name; // Full Name
    public $AbbreviatedName; // First Name + Last Initial

    public $Phone;

    public $StartDate;
    public $EndDate;
    public $CreationDate;


    public function Save() {
        $serializableTenant = [
            'Id' => $this->Id,
            'FirstName' => $this->FirstName,
            'LastName' => $this->LastName,
            'Phone' => $this->Phone,
            'StartDate' => $this->StartDate,
            'EndDate' => $this->EndDate,
            'CreationDate' => $this->CreationDate
        ];

        ObjectStore::Save('tenants', $this->Id, $serializableTenant);

        return $this;
    }

    /**
     * Builds a tenant class from an object
     */
    public static function Parse($raw) {
        if (is_array($raw))
            $raw = json_decode(json_encode($raw)); // Convert to object

        $tenant = new Tenant();

        $tenant->Id = $raw->Id;
        $tenant->FirstName = $raw->FirstName;
        $tenant->LastName = $raw->LastName;
        $tenant->Name = $tenant->FirstName . ' ' . $tenant->LastName;
        $tenant->AbbreviatedName = $tenant->FirstName . ' ' . substr($tenant->LastName, 0, 1) . '.';

        $tenant->Phone = $raw->Phone;

        $tenant->StartDate = $raw->StartDate;
        $tenant->EndDate = $raw->EndDate;
        $tenant->CreationDate = $raw->CreationDate;

        return $tenant;
    }

    /**
     * Retrieves a tenant with an Id
     */
    public static function Get($id) {
        $raw = ObjectStore::Get('tenants', $id);
        return Tenant::Parse($raw);
    }

    /**
     * Retrieves all tenants
     */
    public static function GetAll() {
        $raw = ObjectStore::Get('tenants');

        $tenants = [];

        foreach ($raw as $rawTenant) {
            array_push($tenants, Tenant::Parse($rawTenant));
        }

        return $tenants;
    }
}