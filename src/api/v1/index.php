<?php
/**
 * PHP file for declaring routes
 */
require_once('router.php');
require_once('connection.php');

header('Content-type: application/json');

function uuid() {
  return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
  );
}

$Router = new Router('/api/v1'); // Create router

$Router->SetParameters([
  'id' => '[0-9A-Za-z]{8}-[0-9A-Za-z]{4}-[0-9A-Za-z]{4}-[0-9A-Za-z]{4}-[0-9A-Za-z]{12}|[0-9]+'
]);



require_once('authentication.php');
require_once('authorization.php');


/*****************************
 *           BILLS           *
 *****************************/

/**
 * Retrieves all unpaid bills
 */
$Router->Get('/bills', function() {
  global $Router;

  $db = database();

  $rows = $db->query("SELECT Id FROM bills WHERE FullyPaid=false");

  $bills = [];
  foreach ($rows as $row) {
    $bill = $Router->RunLocal('GET', '/bills/' . $row['Id']);
    array_push($bills, $bill->Data);
  }

  return $bills;
});

/**
 * Make a new bill
 */
$Router->Post('/bills/new', function() {
  global $Router;

  // TODO: Verify DueDate is an actual date

  // Verify that the tenant exists
  $url = sprintf('/tenants/%s', $this->Data['CreatorId']);
  $tenant = $Router->RunLocal('GET', $url);
  if (is_null($tenant) || !isset($tenant->Data) || !is_null($tenant->Data['EndDate'])) {
    $this->Abort('400', 'Invalid Tenant');
  }

  $id = uuid(); // ID for a new bill

  // Tenant exists, create the bill
  $db = database();
  $result = $db->query("INSERT INTO bills (Id, Title, DueDate, CreatedBy, Amount, PayTo) VALUES (?, ?, ?, ?, ?, ?)", [
    $id,
    $this->Data['Title'],
    $this->Data['DueDate'],
    $tenant->Data['Id'],
    $this->Data['Amount'],
    $this->Data['PayTo']
  ]);

  // Verify that the bill was created
  $url = sprintf('bills/%s', $id);
  $check = $Router->RunLocal('GET', $url);

  if (!is_null($check) && isset($check->Data)) {
    return $check->Data;
  }

  $this->Abort('204', 'Could not create bill');
})->RequiredData(['Title', 'Amount', 'DueDate', 'CreatorId', 'PayTo']);

/**
 * Get a bill specific bill
 */
$Router->Get('/bills/{id}', function($id) {
  global $Router;

  $db = database();
  $res = $db->query("SELECT Id, Title, DueDate, Amount, CreatedBy, FullyPaid, PayTo FROM bills WHERE Id=?", [$id]);

  if (is_null($res))
    $this->Abort(404, 'Could not find bill');

  // Only use the first item in the result (should only ever be one)
  $res = $res[0];

  // Retrieve the tenant
  $tenant = $Router->RunLocal('GET', '/tenants/' . $res['CreatedBy']);
  $res['CreatedBy'] = $tenant->Data;

  // Retrieve the payment target
  $target = $Router->RunLocal('GET', '/targets/'.$res['PayTo']);
  $res['PayTo'] = $target->Data;

  // Data Type Conversions
  $res['Amount'] = floatval($res['Amount']);
  $res['FullyPaid'] = ($res['FullyPaid'] == 0) ? false : true;

  return $res;
});

/**
 * Get list of payments for a bill
 */
$Router->Get('/bills/{id}/payments', function($id) {
  global $Router;

  // Verify that the bill exists
  $bill = $Router->RunLocal('GET', '/bills/'.$id);
  if (is_null($bill) || !isset($bill->Data)) {
    $this->Abort('404', 'Invalid Bill');
  }
  $bill = $bill->Data;

  // Get list of payments
  $db = database();
  $rows = $db->query("SELECT Id FROM payments WHERE BillId=? ORDER BY PaymentDate ASC", [
    $bill['Id']
  ]);

  $payments = [];
  foreach ($rows as $row) {
    $payment = $Router->RunLocal('GET', '/bills/'.$bill['Id'].'/payments/'.$row['Id']);
    array_push($payments, $payment->Data);
  }

  return $payments;
});

/**
 * Get a specific payment
 */
$Router->Get('/bills/{id}/payments/{id}', function($billId, $paymentId) {
  global $Router;

  // Verify that the bill exists
  $bill = $Router->RunLocal('GET', '/bills/'.$billId);
  if (is_null($bill) || !isset($bill->Data)) {
    $this->Abort('404', 'Invalid Bill');
  }
  $bill = $bill->Data;

  // Fetch the payment from the database
  $db = database();
  $res = $db->query("SELECT PaidBy, Amount, PaymentDate, PaidInFull FROM payments WHERE BillId=? AND Id=?", [
    $bill['Id'],
    $paymentId
  ]);

  if (is_null($res))
    $this->Abort('404', 'Could not find payment');

  // Should only ever be one result
  $res = $res[0];

  // Add computed data
  $tenant = $Router->RunLocal('GET', '/tenants/' . $res['PaidBy']);
  $res['PaidBy'] = $tenant->Data;
  $res['Bill'] = $billId;

  // Data Type Conversion
  $res['Amount'] = floatval($res['Amount']);
  $res['PaidInFull'] = ($res['PaidInFull'] == 0) ? false : true;

  return $res;
});

/**
 * Pay a bill
 */
$Router->Post('/bills/{id}/payments/new', function($id) {
  global $Router;

  // Verify the bill exists
  $bill = $Router->RunLocal('GET', '/bills/'.$id);
  if (is_null($bill) || !isset($bill->Data)) {
    $this->Abort('404', 'Invalid Bill');
  }
  $bill = $bill->Data;

  // Verify the tenant exists
  $tenant = $Router->RunLocal('GET', '/tenants/' . $this->Data['TenantId']);
  if (is_null($tenant) || !isset($tenant->Data)) {
    $this->Abort('400', 'Invalid Tenant');
  }
  $tenant = $tenant->Data;

  $newId = uuid(); // Id for new payment

  // Add to the database
  $db = database();
  $qry = $db->query("INSERT INTO payments (Id, BillId, PaidBy, Amount, PaidInFull) VALUES (?, ?, ?, ?, ?)", [
    $newId,
    $bill['Id'],
    $tenant['Id'],
    $this->Data['Amount'],
    $this->Data['PaidInFull']
  ]);

  // Confirm that the payment was created
  $payment = $Router->RunLocal('GET', '/bills/'.$id.'/payments/'.$newId);

  if (!is_null($payment) && isset($payment->Data)) {
    // TODO: Handle the bill being paid in full
    return $payment->Data;
  }

  $this->Abort('204', 'Could not add payment');
})->RequiredData(['TenantId', 'Amount', 'PaidInFull']);

/**
 * Modify a bill
 */
$Router->Post('/bills/{id}/edit', function($id) {
  global $Router;

  // Verify that the bill exists
  $bill = $Router->RunLocal('GET', '/bills/'.$id);
  if (is_null($bill) || !isset($bill->Data)) {
    $this->Abort('404', 'Invalid Bill');
  }
  $bill = $bill->Data;

  // Update the bill
  $db = database();
  $res = $db->query("UPDATE bills SET Title=?, DueDate=?, Amount=? WHERE Id=?", [
    $this->Data['Title'],
    $this->Data['DueDate'],
    $this->Data['Amount'],
    $bill['Id']
  ]);

  // Get the new bill data
  $bill = $Router->RunLocal('GET', '/bills/'.$id);
  if (!is_null($bill) && isset($bill->Data)) {
    return $bill->Data;
  }

  $this->Abort('204', 'Could not update bill');
})->RequiredData(['Title', 'DueDate', 'Amount']);




/*****************************
 *         TENANTS           *
 *****************************/

/**
 * Retrieves all active tenants
 */
$Router->Get('/tenants', function() {
  global $Router;
  $db = database();

  // Query for all active tenants
  $rows = $db->query("SELECT Id FROM tenants WHERE EndDate IS NULL or EndDate > CURDATE()");

  $tenants = [];
  foreach ($rows as $row) {
    $tenant = $Router->RunLocal('GET', '/tenants/' . $row['Id']);
    array_push($tenants, $tenant->Data);
  }

  return $tenants;
});

/**
 * Create a tenant
 */
$Router->Post('/tenants/new', function() {
  global $Router;

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
  $this->Abort('204', 'Could not create tenant');
})->RequiredData(['FirstName', 'LastName', 'StartDate']);

/**
 * Get a specific tenant
 */
$Router->Get('/tenants/{id}', function($id) {
  $db = database();
  $res = $db->query("SELECT Id, FirstName, LastName, StartDate, EndDate, Permissions FROM tenants WHERE Id=?", [
    $id
  ]);

  if (is_null($res))
    $this->Abort(404, 'Could not find tenant');

  // Should only ever be one result
  $res = $res[0];

  // Add computed values
  $res['Name'] = $res['FirstName'] . ' ' . $res['LastName'];
  $res['AbbreviatedName'] = $res['FirstName'] . ' ' . substr($res['LastName'], 0, 1) . '.';

  $res['Permissions'] = explode(",", $res['Permissions']);

  return $res;
});

/**
 * Modify a tenant
 */
$Router->Post('/tenants/{id}/edit', function($id) {
  global $Router;

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

  $this->Abort('204', 'Could not update tenant');
})->RequiredData(['FirstName', 'LastName', 'StartDate']);



/****************************
 *      Payment Targets     *
 ****************************/

 // TODO: Allow tennats to be targets

/**
 * Retrieve all targets
 */
$Router->Get('/targets', function() {
  global $Router;

  $db = database();
  $rows = $db->Query("SELECT Id FROM payment_targets WHERE Archived=false");

  $targets = [];
  foreach ($rows as $row) {
    $target = $Router->RunLocal('GET', '/targets/'.$row['Id']);
    array_push($targets, $target->Data);
  }

  return $targets;
});

/**
 * Create a target
 */
$Router->Post('/targets/new', function() {
  global $Router;

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
  $this->Abort('204', 'Could not create target');
})->RequiredData(['Name', 'Url']);

/**
 * Get a specific target
 */
$Router->Get('/targets/{id}', function($id) {
  global $Router;

  $db = database();
  $res = $db->query("SELECT Id, Name, Url, Archived FROM payment_targets WHERE Id=?", [
    $id
  ]);

  if (is_null($res)) {
    // Check if id belongs to a tenant
    $tenant = $Router->RunLocal('GET', '/tenants/'.$id);
    if (!is_null($tenant) && isset($tenant->Data)) {
      // Convert to a target style object
      $tenant->Data['Url'] = null;
      $tenant->Data['IsTenant'] = true;
      $tenant->Data['Archived'] = (is_null($tenant->Data['EndDate'])) ? false : (strtotime($tenant->Data['EndDate']) <= strtotime("now")) ? true : false;

      return $tenant->Data;
    }

    // Does not belong to a tenant
    $this->Abort('404', 'Could not find Payment Target');
  }

  $res = $res[0]; // Should only ever be on e

  $res['IsTenant'] = false;

  // Data Type conversion
  $res['Archived'] = ($res['Archived'] == 0) ? false : true;

  return $res;
});

/**
 * Update a target
 */
$Router->Post('/targets/{id}/edit', function($id) {
  global $Router;

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
  $this->Abort('204', 'Could not update Payment Target');
})->RequiredData(['Name', 'Url', 'Archived']);



/*****************************
 *          LISTS            *
 *****************************/

/**
 * Get all the lists
 */
$Router->Get('/lists', function() {
  global $Router;

  $db = database();
  $rows = $db->query("SELECT Id FROM lists");

  $lists = [];
  foreach ($rows as $row) {
    $list = $Router->RunLocal('GET', '/lists/'.$row['Id']);
    array_push($lists, $list->Data);
  }

  return $lists;
});

/**
 * Create a new list
 */
$Router->Post('/lists/new', function() {
  global $Router;

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
  $this->Abort('204', 'Could not create list');
})->RequiredData(['List']);

/**
 * Get a specific list
 */
$Router->Get('/lists/{id}', function($id) {
  $db = database();
  $res = $db->query("SELECT ListData FROM lists WHERE Id=?", [
    $id
  ]);

  if (is_null($res))
    $this->Abort(404, 'Could not find list');

  // Should only ever be one result
  $res = $res[0];

  // Convert from JSON to an object
  $list = json_decode($res['ListData']);

  return $list;
});

/**
 * Update the metadata about a list
 */
$Router->Post('/lists/{id}/edit', function($id) {
  global $Router;

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

  $this->Abort('204', 'Could not update list');
})->RequiredData(['List']);




/*****************************
 *       Configuration       *
 *****************************/

/**
 * Retrieves the configuration
 */
$Router->Get('/configuration', function() {
  // Retrieve from the database
  $db = database();
  $res = $db->query("SELECT Value from object_store WHERE Name='config'");

  if ($res == null)
    $this->Abort('404', 'Configuration not found');

  $res = $res[0]['Value'];

  return json_decode($res);
});

/**
 * Updates the configuration
 */
$Router->Post('/configuration/update', function() {

});










// Run the current route
$Router->Run();