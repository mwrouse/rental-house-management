/**
 * Promise Polyfill
 */
(function (root) {

    // Store setTimeout reference so promise-polyfill will be unaffected by
    // other code modifying setTimeout (like sinon.useFakeTimers())
    var setTimeoutFunc = setTimeout;
  
    function noop() {}
  
    // Polyfill for Function.prototype.bind
    function bind(fn, thisArg) {
      return function () {
        fn.apply(thisArg, arguments);
      };
    }
  
    function Promise(fn) {
      if (typeof this !== 'object') throw new TypeError('Promises must be constructed via new');
      if (typeof fn !== 'function') throw new TypeError('not a function');
      this._state = 0;
      this._handled = false;
      this._value = undefined;
      this._deferreds = [];
  
      doResolve(fn, this);
    }
  
    function handle(self, deferred) {
      while (self._state === 3) {
        self = self._value;
      }
      if (self._state === 0) {
        self._deferreds.push(deferred);
        return;
      }
      self._handled = true;
      Promise._immediateFn(function () {
        var cb = self._state === 1 ? deferred.onFulfilled : deferred.onRejected;
        if (cb === null) {
          (self._state === 1 ? resolve : reject)(deferred.promise, self._value);
          return;
        }
        var ret;
        try {
          ret = cb(self._value);
        } catch (e) {
          reject(deferred.promise, e);
          return;
        }
        resolve(deferred.promise, ret);
      });
    }
  
    function resolve(self, newValue) {
      try {
        // Promise Resolution Procedure: https://github.com/promises-aplus/promises-spec#the-promise-resolution-procedure
        if (newValue === self) throw new TypeError('A promise cannot be resolved with itself.');
        if (newValue && (typeof newValue === 'object' || typeof newValue === 'function')) {
          var then = newValue.then;
          if (newValue instanceof Promise) {
            self._state = 3;
            self._value = newValue;
            finale(self);
            return;
          } else if (typeof then === 'function') {
            doResolve(bind(then, newValue), self);
            return;
          }
        }
        self._state = 1;
        self._value = newValue;
        finale(self);
      } catch (e) {
        reject(self, e);
      }
    }
  
    function reject(self, newValue) {
      self._state = 2;
      self._value = newValue;
      finale(self);
    }
  
    function finale(self) {
      if (self._state === 2 && self._deferreds.length === 0) {
        Promise._immediateFn(function() {
          if (!self._handled) {
            Promise._unhandledRejectionFn(self._value);
          }
        });
      }
  
      for (var i = 0, len = self._deferreds.length; i < len; i++) {
        handle(self, self._deferreds[i]);
      }
      self._deferreds = null;
    }
  
    function Handler(onFulfilled, onRejected, promise) {
      this.onFulfilled = typeof onFulfilled === 'function' ? onFulfilled : null;
      this.onRejected = typeof onRejected === 'function' ? onRejected : null;
      this.promise = promise;
    }
  
    /**
     * Take a potentially misbehaving resolver function and make sure
     * onFulfilled and onRejected are only called once.
     *
     * Makes no guarantees about asynchrony.
     */
    function doResolve(fn, self) {
      var done = false;
      try {
        fn(function (value) {
          if (done) return;
          done = true;
          resolve(self, value);
        }, function (reason) {
          if (done) return;
          done = true;
          reject(self, reason);
        });
      } catch (ex) {
        if (done) return;
        done = true;
        reject(self, ex);
      }
    }
  
    Promise.prototype['catch'] = function (onRejected) {
      return this.then(null, onRejected);
    };
  
    Promise.prototype.then = function (onFulfilled, onRejected) {
      var prom = new (this.constructor)(noop);
  
      handle(this, new Handler(onFulfilled, onRejected, prom));
      return prom;
    };
  
    Promise.all = function (arr) {
      var args = Array.prototype.slice.call(arr);
  
      return new Promise(function (resolve, reject) {
        if (args.length === 0) return resolve([]);
        var remaining = args.length;
  
        function res(i, val) {
          try {
            if (val && (typeof val === 'object' || typeof val === 'function')) {
              var then = val.then;
              if (typeof then === 'function') {
                then.call(val, function (val) {
                  res(i, val);
                }, reject);
                return;
              }
            }
            args[i] = val;
            if (--remaining === 0) {
              resolve(args);
            }
          } catch (ex) {
            reject(ex);
          }
        }
  
        for (var i = 0; i < args.length; i++) {
          res(i, args[i]);
        }
      });
    };
  
    Promise.resolve = function (value) {
      if (value && typeof value === 'object' && value.constructor === Promise) {
        return value;
      }
  
      return new Promise(function (resolve) {
        resolve(value);
      });
    };
  
    Promise.reject = function (value) {
      return new Promise(function (resolve, reject) {
        reject(value);
      });
    };
  
    Promise.race = function (values) {
      return new Promise(function (resolve, reject) {
        for (var i = 0, len = values.length; i < len; i++) {
          values[i].then(resolve, reject);
        }
      });
    };
  
    // Use polyfill for setImmediate for performance gains
    Promise._immediateFn = (typeof setImmediate === 'function' && function (fn) { setImmediate(fn); }) ||
      function (fn) {
        setTimeoutFunc(fn, 0);
      };
  
    Promise._unhandledRejectionFn = function _unhandledRejectionFn(err) {
      if (typeof console !== 'undefined' && console) {
        console.warn('Possible Unhandled Promise Rejection:', err); // eslint-disable-line no-console
      }
    };
  
    /**
     * Set the immediate function to execute callbacks
     * @param fn {function} Function to execute
     * @deprecated
     */
    Promise._setImmediateFn = function _setImmediateFn(fn) {
      Promise._immediateFn = fn;
    };
  
    /**
     * Change the function to execute on unhandled rejection
     * @param {function} fn Function to execute on unhandled rejection
     * @deprecated
     */
    Promise._setUnhandledRejectionFn = function _setUnhandledRejectionFn(fn) {
      Promise._unhandledRejectionFn = fn;
    };
  
    if (typeof module !== 'undefined' && module.exports) {
      module.exports = Promise;
    } else {
      root.Promise = Promise;
    }
  
  })(this);
  
  
  // A deferred object, for async operations, sometimes looks better than using a promise (cleaner code).
  // When using this, create the deferred object at the beginning, handle it however, call resolve or reject,
  // then at the end of your function return myDeferred.promise
  var Deferred = function() {
    var dfd = {};
  
    dfd.promise = new Promise(function(resolve, reject) {
      dfd.resolve = resolve;
      dfd.reject = reject;
    });
  
    return dfd;
  };
  
  
  /**
   * Ajax Request
   * Michael Rouse
   * December 2016
   */
  function ajaxRequest(url) {
      return new Promise(function(resolve, reject) {
          var requestTimeout;
          var request = new XMLHttpRequest();
  
          request.open('GET', url, true);
  
          request.timeout = 90000; // Timeout after 1.5 minutes
  
          // Event for if the request times out (won't actually get triggered with the onreadystatechange stuff)
          // But... just in case :)
          request.ontimeout = function(e) {
              clearTimeout(requestTimeout);
              reject("Request Timed Out");
          };
  
          // Handle an error in the request (also won't happen)
          request.onerror = function(e) {
              clearTimeout(requestTimeout);
              reject("Unknown Error Occured");
          };
  
          // Event for when the state of the request changes (completed, failure, etc...)
          // This gets called multiple times as the request is handled, but we are only doing stuff
          // when the request state is 4 (DONE)
          request.onreadystatechange = function() {
              // This try-catch block is needed becuase any exceptions thrown from here will go straight to the window
              // and be classified as a fatal error, which they are not.
              // Well... they could be, but let's say they are recoverable...
              try {
                  if (request.readyState == 4) {
                      if (request.status >= 200 && request.status < 400) {
                          // Success!
                          clearTimeout(requestTimeout);
                          resolve(request.responseText);
                      }
                      else if (request.status == 0) {
                          // :(
                          clearTimeout(requestTimeout);
                          reject("No Internet Connection");
                      }
                      else {
                          // :'(
                          clearTimeout(requestTimeout);
                          reject("Failed to Load Page");
                      }
                  }
              } catch (e) {
                clearTimeout(requestTimeout);
                reject(e);
              }
          };
  
          request.send(); // Bye bye!
  
          // When the request takes a really, really long amount of time, and for some reason doesn't timeout, do this
          requestTimeout = setTimeout(function() {
              request.abort();
              reject("Request Timed Out"); // This won't happen because calling abort() changes the state to 0 (UNSENT),
                                            // which then calls the onreadystatechange listener, which it will reject with
                                            // "No Internet Connection", but it doesn't hurt to be here.
          }, request.timeout * 5);
  
      });
  }
  
  