<?php
/**
 * PHP file for declaring routes
 */
require_once('router.php');
require_once('connection.php');

$router = new Router(); // Create router


$router->Register('/\/api\/v1\/tenants\/([0-9]*)/', function() {
  echo "hi";
  return "Howdy";
});




// Run the current route
$router->Run();