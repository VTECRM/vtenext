<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/*
 * Class for a generic Touch WS
 */

// TODO: move the global $touchInst inside the class: $this->touch

class TouchWSClass {

	public $preLogin = false;		// if true, can be called before the login is done
	public $longOperation = false;	// if true the webservice is allowed to use a lot of time
									// (configurable in Touch.php)
	public $requestedVersion;		// version requested

	public $validateModule = false;
	public $moduleParam = 'module';

	// TODO: add a singleModule param
	// TODO: add a list of parameters and their type and description for automatic validation

	public function __construct($wsversion = null) {
		$this->requestedVersion = $wsversion;
	}
	
	public function clearCache() {
		// Do nothing, override this method in the subclass
	}

	/**
	 * Validates data passed to the webservice.
	 */
	public function validate(&$request, &$message = null) {
		global $touchInst;

		$message = '';

		if ($this->validateModule && !empty($this->moduleParam)) {
			$module = $request[$this->moduleParam];
			if (!empty($module) && in_array($module, $touchInst->excluded_modules)) {
				$message = "Module not permitted";
				return false;
			}
		}
		return true;
	}

	/**
	 * Executes the webservice and output the result
	 */
	public function execute(&$request, &$result = null) { // crmv@106521
		global $touchInst;

		// validation
		$valid = $this->validate($request, $message);
		if (!$valid) return $touchInst->outputFailure($message);

		// execution
		$result = $this->process($request);

		// output
		return $this->output($result);
	}

	/**
	 * Calls the webservice and return the result
	 */
	public function call(&$request) {
		global $touchInst;

		$valid = $this->validate($request, $message);
		if (!$valid) return $touchInst->createOutput(array(), $message, false);

		// execution
		$result = $this->process($request);
		return $touchInst->createOutput($result);
	}

	/**
	 * Calls another webservice using the same version
	 */
	protected function subcall($wsname, &$request) {
		global $touchInst;
		return $touchInst->callWS($wsname, $this->requestedVersion, $request);
	}

	/**
	 * The real core of the webservice
	 */
	public function process(&$request) {
		global $touchInst;
		// Override this method to provide functionality
		//
		return array();
	}

	protected function success($data = array()) {
		global $touchInst;
		return $touchInst->createOutput($data, '', true);
	}

	protected function error($message) {
		global $touchInst;
		return $touchInst->createOutput(null, $message, false);
	}

	protected function output($result) {
		global $touchInst;
		return $touchInst->outputRaw($result);
	}

}
