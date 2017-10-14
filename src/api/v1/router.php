<?php

/**
 * Represents a request
 */
class Request {
    public $Method;
    public $Uri;
    public $Data;

    public function __construct($method, $uri, $data) {
      $this->Method = strtoupper($method);
      $this->Uri = $uri;
      $this->Data = $data;
    }


    /**
     * Aborts a response with a code
     */
    public function Abort($code=200, $reason='') {
      try {
        http_response_code($code);
      }
      catch (Exception $e) {
        // TODO
      }
      throw new Exception($reason);
    }

    /**
     * Redirects to another page
     * @param string $location url to redirect to
     */
    public function Redirect($location) {
      header("Location: " . $location);
    }

    /**
     * Returns the object without POST/GET Data
     */
    public function Serialize() {
      return (object) [ 'Method' => $this->Method, 'Uri' => $this->Uri ];
    }

}


/**
 * Class that represents a route
 */
class Route {
  private $_path;
  private $_callback;

  private $_arguments = [];

  private $_methods = []; // List of methods that this endpoint handles

  public function __construct($methods, $path, $fn, $argDefinitions = []) {
    $this->_methods = $methods;
    $this->_path = $this->_parseArgs($path, $argDefinitions);
    $this->_callback = $fn;
  }


  /**
   * Tries to run the endpoint when given a request
   *
   * @param Request $request The request that will become the callback $this
   * @return Value returned from callback (if endpoint matched)
   */
  public function TryRun($request) {
    if (!in_array($request->Method, $this->_methods)) {
      return null; // Method not supported
    }

    $args = [];

    $result = preg_match($this->_path, $request->Uri, $matches);
    if ($result == 1) {
      // Build argument list to send to the function
      if (count($matches) > 1) {
        for ($i = 1; $i < count($matches); $i++) {
            array_push($args, $matches[$i]);
        }
      }

      try {
        // Perform the endpoint callback
        $callbackReturn = call_user_func_array(Closure::bind($this->_callback, $request), $args);

        if ($callbackReturn == null)
            throw new Exception('No Result');

        return (object) ['Data' => $callbackReturn, 'Request' => $request->Serialize()];
      }
      catch (Exception $e) {
        return (object) ['Data' => null, 'Error' => $e->getMessage(), 'Request' => $request->Serialize()];
      }
    }

    return null;
  }

  /**
   * Parses arguments and turns them into regex
   *
   * @param string $path Endpoint url specified by the user
   * @param string[] $definitions Associative array that the user specified to define
   * custom regexs
   * @return string Final endpoint url with regexs
   */
  private function _parseArgs($path, $definitions) {
    preg_match_all('/\{(.*?)\}/i', $path, $matches);

    $final = str_replace('/', '\/', $path);

    if (count($matches) > 1) {
      for ($i = 0; $i < count($matches[1]); $i++) {
        $name = $matches[1][$i]; // Name of the argument {name}

        $replacement = '([^\/]*){1}'; // Generic regex to replace the argument

        if (isset($definitions[$name])) {
          // Custom regex
          $replacement = '(' . $definitions[$name] . ')';
        }

        $final = str_replace('{' . $name . '}', $replacement, $final);
      }
    }

    return '/^' . $final . '$/i';
  }

}


/**
 * Custom PHP routing class
 */
class Router {
  private $_routes = []; // Array of routes and the function they map to
  private $_definitions = []; // Array of custom regexes
  private $_base = '';
  private $_request; // Request object

  private $_endpointFound;

  public function __construct($base = '') {
    if ($base != '') {
      $this->SetBase($base);
    }
    $this->_endpointFound = false;
    $this->_request = $this->_generateRequestObject();
  }


  /**
   * Sets the base of each route url
   *
   * @param string $base The url base of all routes
   * @return void
   */
  public function SetBase($base) {
    $this->_base = $this->_normalizeEndpoint($base);
  }

  /**
   * Sets pre-defined custom regexes for variable names in URLs
   *
   * @param string[] $definitions Custom regex for variables in the $path
   */
  public function SetParameters($definitions) {
    $this->_definitions = $definitions;
  }

  /**
   * Defines an endpoint for a GET Request
   *
   * @param string $path The endpoint url
   * @param function $func The callback
   * @param string[] $definitions Custom regex for variables in the $path
   */
  public function Get($path, $func, $defintions = []) {
    return $this->_addEndpoint(['GET'], $path, $func, $defintions);
  }

  /**
   * Defines an endpoint for a POST Request
   *
   * @param string $path The endpoint url
   * @param function $func The callback
   * @param string[] $definitions Custom regex for variables in the $path
   */
  public function Post($path, $func, $definitions = []) {
    return $this->_addEndpoint(['POST'], $path, $func, $definitions);
  }

  /**
   * Defines a route that can be used with more than one method
   *
   * @param string[] $methods Array of HTTP Methods
   * @param string $path The endpoint url
   * @param function $func The callback
   * @param string[] $definitions Custom regex for variables in the $path
   */
  public function AddRoute($methods, $path, $func, $definitions = []) {
    return $this->_addEndpoint($methods, $path, $func, $definitions);
  }

  /**
   * Runs the route according to this current page url
   */
  public function Run() {
    $request = $this->_generateRequestObject();

    $ret = null;
    $found = false;

    foreach ($this->_routes as $route) {
      $ret = $route->TryRun($request);

      if ($ret != null) {
        $found = true;
        break;
      }
    }

    echo json_encode($ret);
  }

  /**
   * Generates the Request object that will become $this for the callback
   * for the endpoint
   */
  private function _generateRequestObject() {
    $method = $_SERVER['REQUEST_METHOD'];
    $url = explode('?', $_SERVER['REQUEST_URI'])[0];

    return new Request($method, $url, array_merge($_POST, $_GET));
  }

  /**
   * Adds an endpoint
   *
   * @param string[] $methods HTTP Method typ e
   * @param string $path The endpoint URL
   * @param function $func Callback function
   * @param string[] $definitions Custom regex for variables in the $path
   */
  private function _addEndpoint($methods, $path, $func, $definitions) {
    $methods = array_map('strtoupper', $methods);
    $path = $this->_normalizeEndpoint($path);

    // Merge definitions but make ones defined at the method take priority
    foreach ($this->_definitions as $name => $regex) {
      if (!isset($definitions[$name])) {
        $definitions[$name] = $regex;
      }
    }

    // Create new route
    $route = new Route($methods, $this->_base .$path, $func, $definitions);
    array_push($this->_routes, $route);
  }

  /**
   * Adds a leading forward slash and removes trailing slashes to a string
   *
   * @param string $path The string to add/remove slashes
   * @return string The modified string
   */
  private function _normalizeEndpoint($path) {
    // Add leading forward slash
    if ($path[0] != '/') {
      $path = '/' . $path;
    }

    // Remove trailing slash
    if (substr($path, -1) == '/') {
      $path = substr_replace($path, '', -1);
    }

    return $path;
  }

}

