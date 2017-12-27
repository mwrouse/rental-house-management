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




/*****************************
 *           BILLS           *
 *****************************/

/**
 * Retrieves all unpaid bills
 */
$Router->Get('/bills', function() {
  global $Router;

  $db = database();

  $qry = $db->query("SELECT Id FROM bills WHERE FullyPaid=false");

  $bills = [];
  while ($row = $qry->fetch_assoc()) {
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

  // Tenant exists, create the bill
  $db = database();

  $id = uuid();
  $qryStr = sprintf("INSERT INTO bills (Id, Title, DueDate, CreatedBy, Amount, PayTO) VALUES ('%s', '%s', '%s', %s, %s, '%s')", $id, $this->Data['Title'], $this->Data['DueDate'], $tenant->Data['AutoId'], $this->Data['Amount'], $this->Data['PayTo']);
  $qrySuccess = $db->query($qryStr);

  // Verify that the bill was created
  if ($qrySuccess) {
    $url = sprintf('bills/%s', $id);
    $check = $Router->RunLocal('GET', $url);

    if (!is_null($check) && isset($check->Data)) {
      return $check->Data;
    }
  }

  $this->Abort('204', 'Could not create bill');
})->RequiredData(['Title', 'Amount', 'DueDate', 'CreatorId', 'PayTo']);

/**
 * Get a bill specific bill
 */
$Router->Get('/bills/{id}', function($id) {
  global $Router;

  $db = database();

  $qryStr = sprintf("SELECT AutoId, Id, Title, DueDate, Amount, CreatedBy, FullyPaid, PayTo FROM bills WHERE Id='%s' OR AutoId='%s'", $id, $id);
  $qry = $db->query($qryStr);

  $res = $qry->fetch_assoc();
  if ($res == null)
    $this->Abort(404, 'Could not find bill');

  // Retrieve the tenant
  $tenant = $Router->RunLocal('GET', '/tenants/' . $res['CreatedBy']);
  $res['CreatedBy'] = $tenant->Data;

  // Retrieve the payment target
  $target = $Router->RunLocal('GET', '/targets/'.$res['PayTo']);
  $res['PayTo'] = $target->Data;

  // Data Type Conversions
  $res['AutoId'] = intval($res['AutoId']);
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

  $qryStr = sprintf("SELECT AutoId FROM payments WHERE BillId='%s' ORDER BY AutoId ASC", $bill['AutoId']);

  $qry = $db->query($qryStr);

  $payments = [];
  while ($row = $qry->fetch_assoc()) {
    $payment = $Router->RunLocal('GET', '/bills/'.$bill['AutoId'].'/payments/'.$row['AutoId']);
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

  $qryStr = sprintf("SELECT PaidBy, Amount, PaymentDate, PaidInFull FROM payments WHERE BillId='%s' AND (AutoId='%s' OR Id='%s')", $bill['AutoId'], $paymentId, $paymentId);
  $qry = $db->query($qryStr);

  $res = $qry->fetch_assoc();
  if ($res == null) {
    $this->Abort('404', 'Could not find payment');
  }

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

  // Add to the database
  $db = database();

  $id = uuid();
  $qryStr = sprintf("INSERT INTO payments (Id, BillId, PaidBy, Amount, PaidInFull) VALUES ('%s',%s, %s, %s, %s)", $id, $bill['AutoId'], $tenant['AutoId'], $this->Data['Amount'], $this->Data['PaidInFull']);
  $qrySuccess = $db->query($qryStr);

  if ($qrySuccess) {
    // Confirm that the payment was created
    $payment = $Router->RunLocal('GET', '/bills/'.$id.'/payments/'.$id);

    if (!is_null($payment) && isset($payment->Data)) {
      // TODO: Handle the bill being paid in full
      return $payment->Data;
    }
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

  $qryStr = sprintf("UPDATE bills SET Title='%s', DueDate='%s', Amount='%s' WHERE AutoId='%s'", $this->Data['Title'], $this->Data['DueDate'], $this->Data['Amount'], $bill['AutoId']);
  $qrySuccess = $db->query($qryStr);

  if ($qrySuccess) {
    $bill = $Router->RunLocal('GET', '/bills/'.$id);
    if (!is_null($bill) && isset($bill->Data)) {
      return $bill->Data;
    }
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
  $qry = $db->query("SELECT Id FROM tenants WHERE EndDate IS NULL or EndDate > CURDATE()");

  $tenants = [];
  while ($row = $qry->fetch_assoc()) {
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

  // Create the tenant
  $db = database();

  $id = uuid();
  $qryStr = sprintf("INSERT INTO tenants (Id, FirstName, LastName, StartDate) VALUES ('%s', '%s', '%s', '%s')", $id, $this->Data['FirstName'], $this->Data['LastName'], $this->Data['StartDate']);
  $qrySuccess = $db->query($qryStr);

  // Verify the tenant was actually created
  if ($qrySuccess) {
    $url = sprintf('/tenants/%s', $id);
    $tenant = $Router->RunLocal('GET', $url);

    if (!is_null($tenant) && isset($tenant->Data)) {
      return $tenant->Data; // Tenant Created
    }
  }

  // Failed to create tenant
  $this->Abort('204', 'Could not create tenant');
})->RequiredData(['FirstName', 'LastName', 'StartDate']);

/**
 * Get a specific tenant
 */
$Router->Get('/tenants/{id}', function($id) {
  $db = database();

  $qryStr = sprintf("SELECT AutoId, Id, FirstName, LastName, StartDate, EndDate FROM tenants WHERE Id='%s' OR AutoID='%s'", $id, $id);
  $qry = $db->query($qryStr);

  $res = $qry->fetch_assoc();
  if ($res == null) {
    $this->Abort(404, 'Could not find tenant');
  }

  // Add computed values
  $res['Name'] = $res['FirstName'] . ' ' . $res['LastName'];
  $res['AbbreviatedName'] = $res['FirstName'] . ' ' . substr($res['LastName'], 0, 1) . '.';

  // Data Type Conversion
  $res['AutoId'] = intval($res['AutoId']);

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

  $qryStr = sprintf("UPDATE tenants SET FirstName='%s', LastName='%s', StartDate='%s' WHERE AutoId='%s'", $this->Data['FirstName'], $this->Data['LastName'], $this->Data['StartDate'], $tenant['AutoId']);

  // Update EndDate if it was specified
  if (array_key_exists('EndDate', $this->Data)) {
    $qryStr = sprintf("UPDATE tenants SET FirstName='%s', LastName='%s', StartDate='%s', EndDate='%s' WHERE AutoId='%s'", $this->Data['FirstName'], $this->Data['LastName'], $this->Data['StartDate'], $this->Data['EndDate'], $tenant['AutoId']);
  }

  $qrySuccess = $db->query($qryStr);

  if ($qrySuccess) {
    // Get the tenant
    $tenant = $Router->RunLocal('GET', '/tenants/'.$id);
    if (!is_null($tenant) && isset($tenant->Data)) {
      return $tenant->Data;
    }
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

  $qry = $db->Query("SELECT Id FROM payment_targets WHERE Archived=false");

  $targets = [];
  while ($row = $qry->fetch_assoc()) {
    $target = $Router->RunLocal('/targets/'.$row['Id']);
    array_push($targets, $target->Data);
  }

  return $targets;
});

/**
 * Create a target
 */
$Router->Post('/targets/new', function() {
  global $Router;

  $db = database();

  $id = uuid();
  $qryStr = sprintf("INSERT INTO payment_targets (Id, Name, Url) VALUES ('%s', '%s', '%s')", $id, $this->Data['Name'], $this->Data['Url']);
  $qrySuccess = $db->query($qryStr);

  if ($qrySuccess) {
    $target = $Router->RunLocal('GET', '/targets/'.$id);
    if (!is_null($target) && isset($target->Data)) {
      return $target->Data;
    }
  }

  $this->Abort('204', 'Could not create target');
})->RequiredData(['Name', 'Url']);

/**
 * Get a specific target
 */
$Router->Get('/targets/{id}', function($id) {
  global $Router;

  $db = database();

  $qryStr = sprintf("SELECT Id, Name, Url, Archived FROM payment_targets WHERE Id='%s'", $id);
  $qry = $db->query($qryStr);

  $res = $qry->fetch_assoc();
  if ($res == null) {
    // Check if id belongs to a tenant
    $tenant = $Router->RunLocal('GET', '/tenants/'.$id);
    if (!is_null($tenant) && isset($tenant->Data)) {
      $tenant->Data['Url'] = null;
      $tenant->Data['IsTenant'] = true;
      $tenant->Data['Archived'] = ($tenant->Data['EndDate'] == null) ? false : (strtotime($tenant->Data['EndDate']) <= strtotime("now")) ? true : false;

      return $tenant->Data;
    }

    $this->Abort('404', 'Could not find Payment Target');
  }

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

  $qryStr = sprintf("UPDATE payment_targets SET Name='%s', Url='%s', Archived=%s WHERE Id='%s'", $this->Data['Name'], $this->Data['Url'], $this->Data['Archived'], $id);
  $qrySuccess = $db->query($qryStr);

  if ($qrySuccess) {
    $target = $Router->RunLocal('GET', '/targets/'.$id);
    if (!is_null($target) && isset($target->Data)) {
      return $target->Data;
    }
  }

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

  $qry = $db->Query("SELECT Id FROM lists");

  $lists = [];
  while ($row = $qry->fetch_assoc()) {
    $list = $Router->RunLocal('/lists/'.$row['Id']);
    array_push($lists, $list->Data);
  }

  return $targets;
});

/**
 * Create a new list
 */
$Router->Post('/lists/new', function() {
  global $Router;

  $db = database();

  $id = uuid();
  $qryStr = sprintf("INSERT INTO lists (Id, ListData) VALUES ('%s', '%s')", $id, $this->Data['List']);
  $qrySuccess = $db->query($qryStr);

  // Verify the list was actually created
  if ($qrySuccess) {
    $url = sprintf('/lists/%s', $id);
    $list = $Router->RunLocal('GET', $url);

    if (!is_null($list) && isset($list->Data)) {
      return $list->Data; // List Created
    }
  }

  // Failed to create list
  $this->Abort('204', 'Could not create list');
});

/**
 * Get a specific list
 */
$Router->Get('/lists/{id}', function($id) {
  $db = database();

  $qryStr = sprintf("SELECT ListData FROM lists WHERE Id='%s' OR AutoID='%s'", $id, $id);
  $qry = $db->query($qryStr);

  $res = $qry->fetch_assoc();
  if ($res == null) {
    $this->Abort(404, 'Could not find list');
  }

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

  $list = json_decode($this->Data['List']);

  $qryStr = sprintf("UPDATE list SET ListData='%s' WHERE AutoId='%s' OR Id='%s'", json_encode($list), $id, $id);

  $qrySuccess = $db->query($qryStr);

  if ($qrySuccess) {
    // Get the list
    $list = $Router->RunLocal('GET', '/lists/'.$id);
    if (!is_null($list) && isset($list->Data)) {
      return json_decode($list->Data);
    }
  }

  $this->Abort('204', 'Could not update list');
})->RequiredData(['List']);












// Run the current route
$Router->Run();