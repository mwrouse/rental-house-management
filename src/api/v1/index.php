<?php
/**
 * PHP file for declaring routes
 */
require_once('framework/include.php');
require_once('permissions.php');

header('Content-type: application/json');

$Router = new Router('/api/v1'); // Create router

$Router->SetParameters([
  'id' => '[0-9A-Za-z]{8}-[0-9A-Za-z]{4}-[0-9A-Za-z]{4}-[0-9A-Za-z]{4}-[0-9A-Za-z]{12}|[0-9]+'
]);




/*****************************
 *           BILLS           *
 *****************************/

/**
 * Retrieves all unpaid bills
 */
$Router->Get('/bills', function() {
  $bills = Bill::GetAll();

  $final = [];
  foreach ($bills as $bill) {
    $found = false;
    foreach ($bill->AppliesTo as $appliesTo)
    {
      if ($appliesTo->Id == $this->Session->CurrentUser->Id)
      {
        array_push($final, $bill);
        $found = true;
        break;
      }
    }

    if (!$found) {
      if ($bill->CreatedBy->Id == $this->Session->CurrentUser->Id)
        array_push($final, $bill);
    }
  }

  return $final;
})->Authenticate()->RequiredPermissions(Permissions::$ViewBills);

/**
 * Make a new bill
 */
$Router->Post('/bills/new', function() {

  $rawBill = [
    'Id' => Guid::NewGuid(),
    'Title' => $this->Data['Title'],
    'Amount' => $this->Data['Amount'],
    'DueDate' => $this->Data['DueDate'],
    'AppliesTo' => $this->Data['AppliesTo'],
    'CreatedBy' => $this->Session->CurrentUser->Id,
    'CreationDate' => date('Y-m-d H:i:s'),
    'PayTo' => $this->Data['PayTo'],
    'FullyPaid' => False,
  ];

  $bill = Bill::Parse($rawBill);
  // Error if null

  $bill->Save();

  $addedBill = Bill::Get($rawBill['Id']);
  Notifier::NewBill($addedBill, $this->Session->CurrentUser);
  return $addedBill;
})->RequiredData(['Title', 'Amount', 'DueDate', 'AppliesTo', 'PayTo'])->Authenticate()->RequiredPermissions(Permissions::$AddBills);

/**
 * Get a bill specific bill
 */
$Router->Get('/bills/{id}', function($id) {
  $bill = Bill::Get($id);
  return $bill;
})->Authenticate()->RequiredPermissions(Permissions::$ViewBills);

/**
 * Get list of payments for a bill
 */
$Router->Get('/bills/{id}/payments', function($id) {
  global $Router;

  $payments = Payment::Get($id);
  return $payments;
})->Authenticate()->RequiredPermissions(Permissions::$ViewBills);


/**
 * Pay a bill
 */
$Router->Post('/bills/{id}/payments/new', function($id) {
  global $Router;

  $bill = Bill::Get($id);

  $payment = [
    'BillId' => $id,
    'Amount' => $this->Data['Amount'],
    'PaidBy' => $this->Session->CurrentUser->Id,
    'Date' => date('Y-m-d')
  ];

  array_push($bill->Payments, Payment::Parse($payment));
  $bill->Save();

  $bill = Bill::Get($id); // Update

  Notifier::PaidBill($bill, $payment['Amount'], $this->Session->CurrentUser);

  return Payment::Get($id);
})->RequiredData(['Amount'])->Authenticate()->RequiredPermissions(Permissions::$ViewBills);

/**
 * Modify a bill
 */
$Router->Post('/bills/{id}/edit', function($id) {

})->RequiredData(['Title', 'DueDate', 'Amount'])->Authenticate();

/**
 * Removes a bill from the system
 */
$Router->Post('/bills/{id}/delete', function($id) {
  ObjectStore::Delete('bills', $id);
  ObjectStore::Delete('payments', $id);
})->Authenticate()->RequiredPermissions(Permissions::$DeleteBills);


/*****************************
 *         TENANTS           *
 *****************************/

/**
 * Retrieves all active tenants
 */
$Router->Get('/tenants', function() {
  $tenants = Tenant::GetAll();
  return $tenants;
})->Authenticate();

/**
 * Create a tenant
 */
$Router->Post('/tenants/new', function() {
  /*global $Router;

  // TODO: Verfy StartDate is an actual date

  $id = uuid(); // Id for the new tenant

  // Create the tenant
  $db = database();
  $res = $db->query("INSERT INTO tenants (Id, FirstName, LastName, StartDate) VALUES (?, ?, ?, ?)", [
    $id,
    $this->Data['FirstName'],
    $this->Data['LastName'],
    $this->Data['StartDate']
  ]);


  // Verify the tenant was actually created
  $url = sprintf('/tenants/%s', $id);
  $tenant = $Router->RunLocal('GET', $url);

  if (!is_null($tenant) && isset($tenant->Data)) {
    return $tenant->Data; // Tenant Created
  }

  // Failed to create tenant
  $this->Abort('204', 'Could not create tenant');*/
})->RequiredData(['FirstName', 'LastName', 'StartDate'])->Authenticate();

/**
 * Get a specific tenant
 */
$Router->Get('/tenants/{id}', function($id) {
  $tenant = Tenant::Get($id);
  return $tenant;
})->Authenticate();

/**
 * Modify a tenant
 */
$Router->Post('/tenants/{id}/edit', function($id) {
  /*global $Router;

  // Verify that the tenant exists
  $tenant = $Router->RunLocal('GET', '/tenants/'.$id);
  if (is_null($tenant) || !isset($tenant->Data)) {
    $this->Abort('404', 'Invalid Tenant');
  }
  $tenant = $tenant->Data;

  // Update in the database
  $db = database();
  $res = $db->query("UPDATE tenants SET FirstName=?, LastName=?, StartDate=?, EndDate=? WHERE Id=?", [
    $this->Data['FirstName'],
    $this->Data['LastName'],
    $this->Data['StartDate'],
    (array_key_exists('EndDate', $this->Data)) ? $this->Data['EndDate'] : null,
    $tenant['Id']
  ]);

  // Get the tenant
  $tenant = $Router->RunLocal('GET', '/tenants/'.$id);
  if (!is_null($tenant) && isset($tenant->Data)) {
    return $tenant->Data;
  }

  $this->Abort('204', 'Could not update tenant');*/
})->RequiredData(['FirstName', 'LastName', 'StartDate'])->Authenticate();



/****************************
 *      Payment Recipients     *
 ****************************/

 // TODO: Allow tenants to be targets

/**
 * Retrieve all targets
 */
$Router->Get('/recipients', function() {
  $targets = Recipient::GetAll();
  return $targets;
})->Authenticate();

/**
 * Create a target
 */
$Router->Post('/recipients/new', function() {
  /*global $Router;

  $id = uuid(); // Id for the new target

  $db = database();
  $res = $db->query("INSERT INTO payment_targets (Id, Name, Url) VALUES (?, ?, ?)", [
    $id,
    $this->Data['Name'],
    $this->Data['Url']
  ]);

  // Check if it was created and return it if it was
  $target = $Router->RunLocal('GET', '/targets/'.$id);
  if (!is_null($target) && isset($target->Data)) {
    return $target->Data;
  }

  // Target not created
  $this->Abort('204', 'Could not create target');*/
})->RequiredData(['Name', 'Url'])->Authenticate();

/**
 * Get a specific target
 */
$Router->Get('/recipients/{id}', function($id) {
  $recipient = Recipient::Get($id);
  return $recipient;
})->Authenticate();

/**
 * Update a target
 */
$Router->Post('/recipients/{id}/edit', function($id) {
  /*global $Router;

  // Confirm target exists and is not a tenant
  $target = $Router->RunLocal('GET', '/targets/'.$id);
  if (is_null($target) || !isset($target->Data) || $target->Data['IsTenant'] == true) {
    $this->Abort('404', 'Invalid Target');
  }

  // Update
  $db = database();
  $res = $db->query("UPDATE payment_targets SET Name=?, Url=?, Archived=? WHERE Id=?", [
    $this->Data['Name'],
    $this->Data['Url'],
    $this->Data['Archived'],
    $id
  ]);

  // Get the newly updated values
  $target = $Router->RunLocal('GET', '/targets/'.$id);
  if (!is_null($target) && isset($target->Data)) {
    return $target->Data;
  }

  // Could not update
  $this->Abort('204', 'Could not update Payment Target');*/
})->RequiredData(['Name', 'Url', 'Archived'])->Authenticate();



/*****************************
 *          LISTS            *
 *****************************/

/**
 * Get all the lists
 */
$Router->Get('/lists', function() {
  /*global $Router;

  $db = database();
  $rows = $db->query("SELECT Id FROM lists");

  $lists = [];
  foreach ($rows as $row) {
    $list = $Router->RunLocal('GET', '/lists/'.$row['Id']);
    array_push($lists, $list->Data);
  }

  return $lists;*/
})->Authenticate();

/**
 * Create a new list
 */
$Router->Post('/lists/new', function() {
  /*global $Router;

  $id = uuid(); // Id for the new row

  $db = database();
  $res = $db->query("INSERT INTO lists (Id, ListData) VALUES (?, ?)", [
    $id,
    $this->Data['List']
  ]);

  // Verify the list was actually created
  $url = sprintf('/lists/%s', $id);
  $list = $Router->RunLocal('GET', $url);

  if (!is_null($list) && isset($list->Data)) {
    return $list->Data; // List Created
  }

  // Failed to create list
  $this->Abort('204', 'Could not create list');*/
})->RequiredData(['List'])->Authenticate();

/**
 * Get a specific list
 */
$Router->Get('/lists/{id}', function($id) {
  /*$db = database();
  $res = $db->query("SELECT ListData FROM lists WHERE Id=?", [
    $id
  ]);

  if (is_null($res))
    $this->Abort(404, 'Could not find list');

  // Should only ever be one result
  $res = $res[0];

  // Convert from JSON to an object
  $list = json_decode($res['ListData']);

  return $list;*/
})->Authenticate();

/**
 * Update the metadata about a list
 */
$Router->Post('/lists/{id}/edit', function($id) {
  /*global $Router;

  // Verify that the list exists
  $list = $Router->RunLocal('GET', '/lists/'.$id);
  if (is_null($list) || !isset($list->Data)) {
    $this->Abort('404', 'Invalid List');
  }
  $list = $list->Data;

  // Update in the database
  $db = database();
  $res = $db->query("UPDATE list SET ListData=? WHERE Id=?", [
    $this->Data['List'],
    $id
  ]);

  // Get the updated list
  $list = $Router->RunLocal('GET', '/lists/'.$id);
  if (!is_null($list) && isset($list->Data)) {
    return $list->Data;
  }

  $this->Abort('204', 'Could not update list');*/
})->RequiredData(['List'])->Authenticate();




/*****************************
 *       Configuration       *
 *****************************/

/**
 * Retrieves the configuration
 */
$Router->Get('/configuration', function() {
  $config = ObjectStore::Get('app', 'config');
  return $config;
})->Authenticate();

/**
 * Updates the configuration
 */
$Router->Post('/configuration/update', function() {

})->Authenticate();




// Include Routes defined in other files
require_once('authentication.php');
require_once('authorization.php');





// Run the current route
$Router->Run();