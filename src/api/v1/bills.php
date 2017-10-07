<?php
/**
 * This is the bills endpoint, it is used to create new bills, and retrieve existing bills
 */
require_once('connection.php');

header('Content-type: application/json');


/**
 * @function billsGET
 * @description Used when the bills api endpoint is called with a GET request
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