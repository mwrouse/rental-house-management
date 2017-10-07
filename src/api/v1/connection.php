<?php
/**
 * Used when connecting to a database
 */
function database() {
  static $connection;

  if (!isset($connection)) {
    // Load config
    $cfg = parse_ini_file('../dbconfig.ini');
    $connection = mysqli_connect($cfg['server'], $cfg['username'], $cfg['password'], $cfg['database']);
  }

  if ($connection == false) {
    // TODO
    echo "WHOOPS";
  }

  return $connection;
}