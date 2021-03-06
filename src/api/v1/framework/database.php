<?php

/**
 * This class is a wrapper for PDO connection to the database
 */
class DatabaseConnection {
  private $_conn = null; // PDO connection

  /**
   * Constructor
   *
   * @param String  $connectionString  Connection String
   * @param String  $username          Username for database connection
   * @param String  $password          Password for database connection
   * @param Array   $options           Array of optional driver options
   */
  public function __construct($connectionString, $username, $password, $options=[]) {
    try {
      $this->_conn = new PDO($connectionString, $username, $password, $options);
    }
    catch (PDOException $e) {
      throw new Exception('Connection Failed: ' . $e->getMessage());
    }
  }


  /**
   * Performs a query
   *
   * @param String  $query    The query to perform
   * @param Array   $params   Array of parameters for the query
   * @return Array            Array of rows from the query result
   */
  public function query($qry, $params=[]) {
    try {
      $statement = $this->_conn->prepare($qry);
      $statement->execute($params);

      if ($statement->errorCode() == 0) {
        // Get the rows returned from the query
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        // Return null if no rows were returned
        if (count($rows) == 0) {
          return null;
        }

        return $rows;
      }
      else {
        $errors = $statement->errorInfo();
        throw new Exception($errors[2]); // Throw the error message
      }
    }
    catch (Exception $e) {
      error_log($e);
      throw $e;
    }
  }

}


/**
 * Used when connecting to a database
 */
function database() {
  static $connection;

  if (!isset($connection)) {
    // Load config
    $cfg = parse_ini_file('../dbconfig.ini');

    // Error if unable to read from dbconfig.ini file
    if ($cfg == false) {
      throw new Exception('Could not read dbconfig.ini file');
    }

    // Verify that all keys exist
    $requiredKeys = ['connection', 'username', 'password'];
    foreach ($requiredKeys as $key) {
      if (!array_key_exists($key, $cfg)) {
        throw new Exception("dbconfig.ini file is missing an etnry for '". $key . "'.");
      }
    }

    $connection = new DatabaseConnection($cfg['connection'], $cfg['username'], $cfg['password']);  #mysqli_connect($cfg['server'], $cfg['username'], $cfg['password'], $cfg['database']);
  }

  if ($connection == false) {
    // TODO
    echo "WHOOPS";
  }

  return $connection;
}