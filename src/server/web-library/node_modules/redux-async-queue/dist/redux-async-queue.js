(function webpackUniversalModuleDefinition(root, factory) {
	if(typeof exports === 'object' && typeof module === 'object')
		module.exports = factory();
	else if(typeof define === 'function' && define.amd)
		define([], factory);
	else if(typeof exports === 'object')
		exports["ReduxAsyncQueue"] = factory();
	else
		root["ReduxAsyncQueue"] = factory();
})(this, function() {
return /******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};

/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {

/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId])
/******/ 			return installedModules[moduleId].exports;

/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			exports: {},
/******/ 			id: moduleId,
/******/ 			loaded: false
/******/ 		};

/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);

/******/ 		// Flag the module as loaded
/******/ 		module.loaded = true;

/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}


/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;

/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;

/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";

/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(0);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/***/ function(module, exports, __webpack_require__) {

	module.exports = __webpack_require__(1);


/***/ },
/* 1 */
/***/ function(module, exports) {

	'use strict';

	exports.__esModule = true;
	exports['default'] = queueMiddleware;
	function assertFunc(fn, message) {
	    if (typeof fn !== 'function') {
	        throw message;
	    }
	}

	function queueMiddleware(_ref) {
	    var dispatch = _ref.dispatch;
	    var getState = _ref.getState;

	    var queues = {};

	    /**
	     * This function executes the first function in
	     * the queue at <key>. It provides a <next()> param
	     * to the queued function to notify when to dequeue
	     * the next queued function.
	     *
	     * @param {string} key - the key for the particular queue
	     *
	     * Callback params
	     * @param {Function} next - notify when async action is finished
	     * @param {Function} dispatch - allow dispatching of more actions
	     * @param {Function} getState - allow state use within callback
	     */
	    function dequeue(key) {
	        var callback = queues[key][0];
	        callback(function next() {
	            queues[key].shift();
	            if (queues[key].length > 0) {
	                dequeue(key);
	            }
	        }, dispatch, getState);
	    }

	    return function (next) {
	        return function (action) {
	            var _ref2 = action || {};

	            var key = _ref2.queue;
	            var callback = _ref2.callback;

	            if (key) {
	                assertFunc(callback, 'Queued actions must have a <callback> property');
	                // Verify array at <key>
	                queues[key] = queues[key] || [];
	                // Add new queued callback
	                queues[key].push(callback);
	                // If it's the only one, sync call it.
	                if (queues[key].length === 1) {
	                    dequeue(key);
	                }
	            } else {
	                return next(action);
	            }
	        };
	    };
	}

/***/ }
/******/ ])
});
;