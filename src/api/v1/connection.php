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
    $this->_conn = new PDO($connectionString, $username, $password, $options); 
  }


  /**
   * Performs a query
   * 
   * @param String  $query    The query to perform 
   * @param Array   $params   Array of parameters for the query
   * @return Array            Array of rows from the query result
   */
  public function query($qry, $params=[]) { 
    $statement = $this->_conn->prepare($qry); 
    $statement->execute($params); 
    
    // Get the rows returned from the query
    $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

    // Return null if no rows were returned
    if (count($rows) == 0) { 
      return null; 
    }

    return $rows;
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
    $connection = new DatabaseConnection($cfg['connection'], $cfg['username'], $cfg['password']);  #mysqli_connect($cfg['server'], $cfg['username'], $cfg['password'], $cfg['database']);
  }

  if ($connection == false) {
    // TODO
    echo "WHOOPS";
  }

  return $connection;
}