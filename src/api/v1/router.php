<?php


/**
 * Class that represents a route
 */
class Route {
  private $_path;
  private $_callback;
  private $_methods;


  public function __construct($path, $fn, $methods) {
    $this->_path = $path;
    $this->_callback = $fn;
    $this->_methods = $methods;
  }


  public function Is($url, $method) {
    // Confirm that the method is correct
    if (!in_array($method, $this->_methods)) {
      return false;
    }

    // Verify URL
    if (preg_match($this->_path, $url)) {
      return true;
    }

    return false;
  }


  public function Execute() {
    return call_user_func($this->_callback);
  }
}


/**
 * Custom PHP routing class
 */
class Router {
  private $_routes = []; // Array of routes and the function they map to



  /**
   * Registers a new route
   * @method Register
   * @param $path_regex (string) - the name of the route
   * @param $func (function) - the function to perform when this route is called
   * @param $methods (string[]) - HTTP methods to use on this route (default: ['GET'])
   */
  public function Register($path_regex, $func, $methods = ['GET']) {
    // Add to the array of routes
    $func();
    array_push($this->_routes, new Route($path_regex, $func, $methods));
  }


  /**
   * Runs the route according to this current page url
   * @method Run
   */
  public function Run() {
    $requestURL = "/api/v1/tenants/1";
    $method = $_SERVER['REQUEST_METHOD'];

    $ret = null;

    foreach ($this->_routes as $route) {
      if ($route->Is($requestURL, $method)) {
        $ret = $route->Execute();
        break; // Stop the for-loop
      }
    }

    echo $ret;
  }


}

