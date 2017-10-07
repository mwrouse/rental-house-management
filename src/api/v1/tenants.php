<?php
/**
 * This is the tenants endpoint, it is used to retrieving tenants and creating new tenants
 */
require_once('connection.php');

header('Content-type: application/json');


/**
 * @function tenantsGET
 * @description Used when the tenants api endpoint is called with a GET request
 */
 function billsGET() {
  $db = database();

  // Query for all active tenants
  $qry = $db->query("SELECT Id, FirstName, LastName, StartDate, EndDate FROM tenants WHERE EndDate IS NULL or EndDate > CURDATE()");

  $tenants = [];
  while ($row = $qry->fetch_assoc()) {
    array_push($tenants, $row);
  }

  echo json_encode($tenants);
}

/**
 * @function billsPOST
 * @description Used when the bills api endpoint is called with a POST request
 */
 function billsPOST() {
  $db = database();

  // TODO: Verify POST data

  echo json_encode([]);
}




$method = $_SERVER['REQUEST_METHOD'];


/**
 * Call the function that belongs to the request method
 */
switch ($method) {
  case "GET":
    billsGET();
    break;

  case "POST":
    billsPOST();
    break;

  default:

}
?>