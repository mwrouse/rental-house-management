<?php

/**
 * Represents a request
 */
class Request {
    public $Method;
    public $Uri;
    public $Data;

    private $_statusCode;
    private $_base;

    public function __construct($method, $uri, $baseUri, $data) {
      $this->Method = strtoupper($method);
      $this->Uri = $uri;
      $this->_base = $baseUri;
      $this->Data = $data;
      $this->_statusCode = 200;
    }

    /**
     * Will abort the request if data is not specified
     */
    public function RequiredData($requiredData) {
        if (!is_array($requiredData)) {
            $requiredData = [$requiredData];
        }

        // Check for the data
        foreach ($requiredData as $key) {
            if (!isset($this->Data[$key]) || empty($this->Data[$key])) {
                $this->Abort('400', 'Invalid Request');
            }
        }
    }

    /**
     * Aborts a response with a code
     */
    public function Abort($code=200, $reason='', $exception=True) {
      try {
        http_response_code($code);
        $this->_statusCode = $code;
      }
      catch (Exception $e) {
        // TODO
      }
      if ($exception)
        throw new Exception($reason);
    }

    /**
     * Redirects to another page
     * @param string $location url to redirect to
     */
    public function Redirect($location) {
      header("Location: " . $location);
      return $this;
    }

    /**
     * Sets a response header
     * @param string $name Header name
     * @param string $value Header value
     */
    public function SetHeader($name, $value) {
      header($name . ": " . $value);
      return $this;
    }

    /**
     * Sets a response cookie
     */
    public function SetCookie($name, $value, $expiration = null) {
      if ($expiration == null)
        $expiration = time() + (86400 * 30);

      setcookie($name, $value, $expiration, "/", $_SERVER['SERVER_NAME'], True, True);
      $_COOKIE[$name] = $value;
      return $this;
    }

    /**
     * Removes a cookie
     */
    public function RemoveCookie($name) {
      unset($_COOKIE[$name]);
      setcookie($name, null, -1, "/", $_SERVER['SERVER_NAME'], True, True);
      return $this;
    }

    /**
     * Returns the object without POST/GET Data
     */
    public function Serialize() {
      return (object) [ 'Method' => $this->Method, 'StatusCode' => $this->_statusCode, 'Uri' => $this->Uri ];
    }

}


/**
 * Class that represents a route
 */
class Route {
  public $RequiresAuthentication = False;

  private $_path;
  private $_callback;

  private $_arguments = [];

  private $_methods = []; // List of methods that this endpoint handles

  private $_requiredData = [];
  private $_originalPath = '';

  private $_requiredPermissions = [];

  public function __construct($methods, $path, $fn, $argDefinitions = []) {
    $this->_methods = $methods;
    $this->_originalPath = $path;
    $this->_path = $this->_parseArgs($path, $argDefinitions);
    $this->_callback = $fn;
  }

  /**
   * Sets the required data that an endpoint must have to apply
   */
  public function RequiredData($data) {
    if (!is_array($data)) {
      array_push($this->_requiredData, $data);
    }
    else {
      $this->_requiredData = array_merge($this->_requiredData, $data);
    }

    return $this;
  }

   /**
   * Sets the required permissions needed to access an endpoint
   */
  public function RequiredPermissions($data) {
    if (!is_array($data)) {
      array_push($this->_requiredPermissions, $data);
    }
    else {
      $this->_requiredPermissions = array_merge($this->_requiredPermissions, $data);
    }

    return $this;
  }

  /**
   * Marks a request as requiring authentication
   */
  public function Authenticate() {
    $this->RequiresAuthentication = True;
    return $this;
  }

  /**
   * Tries to run the endpoint when given a request
   *
   * @param Request $request The request that will become the callback $this
   * @return Value returned from callback (if endpoint matched)
   */
  public function TryRun($request, $authMethod, $permissions = '') {
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
        $this->_verifyData($request);

        // Verify that user is logged in if needed
        if ($this->RequiresAuthentication) {
          if ($authMethod == null) {
            $request->Abort(500, 'No Authentication Method');
            return null;
          }

          $isAuthed = call_user_func(Closure::bind($authMethod, $request));

          if ($isAuthed == False) {
            $request->Abort(401, 'Not Logged In');
            return null;
          }

          // Check permissions
          if (!is_null($permissions)) {
            $permissions = explode(",", $permissions);
            if (count($this->_requiredPermissions) > 0) {
              $matches = 0;
              foreach ($this->_requiredPermissions as $permission) {
                if (in_array($permission, $permissions))
                  $matches = $matches + 1;
              }
              if ($matches != count($this->_requiredPermissions))
              {
                $request->Abort(401, 'Invalid Permissions');
              }
            }
          }
        }

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
   * Verify that all post/get data exists
   */
  private function _verifyData($request) {
    foreach ($this->_requiredData as $data) {
      if (!isset($request->Data[$data]) || empty($request->Data[$data])) {
        $request->Abort(400, 'Invalid Request');
      }
    }
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

    return '/^' . $final . '(\/?)$/i';
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

  private $_authenticationMethod;

  public function __construct($base = '', $authMethod = null) {
    $this->_base = $this->_normalizeEndpoint($base);
    $this->_authenticationMethod = $authMethod;
    $this->_endpointFound = false;
    $this->_request = $this->_generateRequestObject();
  }

  /**
   * Produces a new Child Router, which extends this one
   */
  public function NewChildRouter($base) {
    $newBase = $this->_normalizeEndpoint($this->_base + $base);
    return new Router($newBase, null);
  }


  /**
   * Returns the Base URI
   */
  public function GetBase() {
    return $this->_base;
  }

  /**
   * Sets pre-defined custom regexes for variable names in URLs
   *
   * @param string[] $definitions Custom regex for variables in the $path
   */
  public function SetParameters($definitions) {
    $this->_definitions = $definitions;
    return $this;
  }

  /**
   * Sets the authentication method for a router
   */
  public function SetAuthenticationMethod($func) {
    $this->_authenticationMethod = $func;
    return $this;
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
      $ret = $route->TryRun($request, $this->_authenticationMethod, $_COOKIE['user_permissions']);

      if (!is_null($ret)) {
        $found = true;
        break;
      }
    }

    // Return error info if no matching endpoint was found
    if (!$found) {
      try {
        $request->Abort(404, 'No Matching Endpoint');
      }
      catch (Exception $e) {
        $ret = (object) ['Data' => null, 'Error' => $e->getMessage(), 'Request' => $request->Serialize()];
      }
    }

    echo json_encode($ret);
  }

  /**
   * Get content from a local URL
   */
  public function RunLocal($method, $url, $postData=null) {
    $ret = null;
    $found = false;

    $request = new Request(strtoupper($method), $this->_base . $this->_normalizeEndpoint($url), $postData);

    foreach ($this->_routes as $route) {
      $ret = $route->TryRun($request, $this->_authenticationMethod, $_COOKIE['user_permissions']);

      if (!is_null($ret)) {
        $found = true;
        break;
      }
    }

    return $ret;
  }


  /**
   * Generates the Request object that will become $this for the callback
   * for the endpoint
   */
  private function _generateRequestObject() {
    $method = $_SERVER['REQUEST_METHOD'];
    $url = explode('?', $_SERVER['REQUEST_URI'])[0];

    return new Request($method, $url, $this->_base, array_merge($_POST, $_GET));
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

    return $route;
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

