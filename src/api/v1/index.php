<?php
/**
 * PHP file for declaring routes
 */
require_once('router.php');
require_once('connection.php');

header('Content-type: application/json');

$Router = new Router('/api/v1'); // Create router



/*****************************
 *           BILLS           *
 *****************************/

/**
 * Retrieves all unpaid bills
 */
$Router->Get('/bills', function() {
    $db = database();

    $qry = $db->query("SELECT Id, Title, DueDate, CreationDate, Createdby, Amount, EveryonePaid FROM bills WHERE EveryonePaid=false");

    $bills = [];
    while ($row = $qry->fetch_assoc()) {
        array_push($bills, $row);
    }

    return $bills;
});

/**
 * Make a new bill
 */
$Router->Post('/bills/new', function() {
    // TODO
    print_r($this->Data);
});

/**
 * Get a bill specific bill
 */
$Router->Get('/bills/{id}', function($id) {
    $db = database();

    $qry = $db->query("SELECT Id, Title, DueDate, CreationDate, CreatedBy, Amount, EveryonePaid FROM bills WHERE Id='" . $id . "' OR AutoID='" . $id . "'");

    $res = $qry->fetch_assoc();

    if ($res == null)
       $this->Abort(404, 'Invalid Id');

    return $res;
});

/**
 * Pay a bill
 */
$Router->Post('/bills/{id}/payment', function($id) {
    // TODO:
});

/**
 * Modify a bill
 */
$Router->Post('/bills/{id}/edit', function($id) {
    // TODO
});




/*****************************
 *         TENANTS           *
 *****************************/

/**
 * Retrieves all active tenants
 */
$Router->Get('/tenants', function() {
    $db = database();

    // Query for all active tenants
    $qry = $db->query("SELECT Id, FirstName, LastName, StartDate, EndDate FROM tenants WHERE EndDate IS NULL or EndDate > CURDATE()");

    $tenants = [];
    while ($row = $qry->fetch_assoc()) {
        array_push($tenants, $row);
    }

    return $tenants;
});

/**
 * Create a tenant
 */
$Router->Post('/tenants/new', function() {
    // TODO
});

/**
 * Get a specific tenant
 */
$Router->Get('/tenants/{id}', function($id) {
    $db = database();
    $qry = $db->query("SELECT Id, FirstName, LastName, StartDate, EndDate FROM tenants WHERE Id='" . $id . "' OR AutoID='" . $id . "'");

    return $qry->fetch_assoc();
});

/**
 * Modify a tenant
 */
$Router->Post('/tenants/{id}/edit', function($id) {
    // TODO
});




/*****************************
 *          LISTS            *
 *****************************/

/**
 * Get all the lists
 */
$Router->Get('/lists', function() {
    // TODO
});

/**
 * Create a new list
 */
$Router->Post('/lists/new', function() {
    // TODO
});

/**
 * Get a specific list
 */
$Router->Get('/lists/{id}', function($id) {
    // TODO
});

/**
 * Update a list
 */
$Router->Post('/lists/{id}/edit', function($id) {
    // TODO
});











// Run the current route
$Router->Run();