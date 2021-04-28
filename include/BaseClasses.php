<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/*
 * crmv@42024 - an easy system to have automagically SDK-able classes, works only for PHP >= 5.3.0
 */


interface ExtendableClass {
	public static function getInstance();
	public static function getInstanceByName($className);
}

interface ExtendableUniqueClass {
	public static function getUniqueInstance();
}

// crmv@115378
class VTEClass {

	public static function getInstance() {

		// note: get_called_class is available only in PHP >= 5.3.0
		$className = get_called_class();
		if (!class_exists($className)) throw new Exception("Unable to find the class $className");

		return self::constructClass($className, func_get_args());
	}

	// instantiate an object with the given name and parameters
	protected static function constructClass($name, $args) {
		// this works for PHP >= 5.1.3
		if (class_exists('ReflectionClass')) {
			$reflection = new ReflectionClass($name);
			try {
				$const = $reflection->getConstructor();
				if ($const) {
					$classInst = $reflection->newInstanceArgs($args);
				} else {
					$classInst = $reflection->newInstance();
				}
			} catch (ReflectionException $e) {
				// try the normal way
				$classInst = new $name($args[0], $args[1], $args[2], $args[3]);
			}
		} else {
			// use only 4 arguments
			$classInst = new $name($args[0], $args[1], $args[2], $args[3]);
		}
		return $classInst;
	}
	
}

/**
 * used to create singletons, non sdk-extendable
 */
class VTEUniqueClass extends VTEClass implements ExtendableUniqueClass {

	protected static $instances = array();

	// redefine getInstance
	public static function getInstance() {
		return call_user_func_array(array('self', 'getUniqueInstance'), func_get_args()); // crmv@167234
	}

	// gets a new instance of the class, if not already exists, otherwise reuse the existing object
	public static function getUniqueInstance() {
		$classname = get_called_class();
		if (!array_key_exists($classname, self::$instances)) {
			// forward the call to getInstance with the parameters
			self::$instances[$classname] = call_user_func_array(array('parent', 'getInstance'), func_get_args()); //crmv@167234
		}
		return self::$instances[$classname];
	}

}
// crmv@115378e


// implements the getInstance method
class SDKExtendableClass extends VTEClass implements ExtendableClass { // crmv@115378

	/* - made public
	protected function __construct() {
		// to avoid direct call
	}
	*/

	// gets a new instance of the class, checking for overrides
	public static function getInstance() {
		if (!class_exists('SDK')) throw new Exception('SDK class not found');

		// note: get_called_class is available only in PHP >= 5.3.0
		$className = get_called_class();

		// get SDK Class name
		$sdkClass = SDK::getClass($className);
		if (!empty($sdkClass)) {
			if (!class_exists($sdkClass['module'])) {
				checkFileAccess($sdkClass['src']);
				require_once($sdkClass['src']);
			}
			$className = $sdkClass['module'];
		} elseif (!class_exists($className)) {
			// suppose it's a standard module, try to include it if not found
			$modulePath = $className;
			checkFileAccess("modules/$modulePath/$className.php");
			require_once("modules/$modulePath/$className.php");
		}

		if (!class_exists($className)) throw new Exception("Unable to find the class $className");

		return self::constructClass($className, func_get_args());
	}

	// the same, but the first parameter is the name of the class
	public static function getInstanceByName($className) {
		if (!class_exists('SDK')) throw new Exception('SDK class not found');

		// note: get_called_class is available only in PHP >= 5.3.0
		if (empty($className)) return self::getInstance();

		// get SDK Class name
		$sdkClass = SDK::getClass($className);
		if (!empty($sdkClass)) {
			if (!class_exists($sdkClass['module'])) {
				checkFileAccess($sdkClass['src']);
				require_once($sdkClass['src']);
			}
			$className = $sdkClass['module'];
		} elseif (!class_exists($className)) {
			// suppose it's a standard module, try to include it if not found
			$modulePath = $className;
			checkFileAccess("modules/$modulePath/$className.php");
			require_once("modules/$modulePath/$className.php");
		}

		if (!class_exists($className)) throw new Exception("Unable to find the class $className");

		$argList = func_get_args();
		array_shift($argList);	// remove first argument

		return self::constructClass($className, $argList);
	}
	
	// crmv@115378 - removed code

}

// this class can be used to create classes with a single instance
class SDKExtendableUniqueClass extends SDKExtendableClass implements ExtendableUniqueClass {

	protected static $instances = array();

	// redefine getInstance
	public static function getInstance() {
		return call_user_func_array(array('self', 'getUniqueInstance'), func_get_args()); // crmv@167234
	}

	// gets a new instance of the class, if not already exists, otherwise reuse the existing object
	public static function getUniqueInstance() {
		$classname = get_called_class();
		if (!array_key_exists($classname, self::$instances)) {
			// forward the call to getInstance with the parameters
			self::$instances[$classname] = call_user_func_array(array('parent', 'getInstance'), func_get_args()); // crmv@167234
		}
		return self::$instances[$classname];
	}

}


// crmv@65455
/**
 * A simple base class to create other classes that supports options
 * In the future, this might be translated to a PHP Trait
 */
class OptionableClass {

	protected $options = array();

	public function __construct($options = array()) {
		if (is_array($options)) {
			$this->options = self::array_merge_recursive_simple($this->options, $options);
		}
	}

	public function setOption($option, $value) {
		$this->options[$option] = $value;
	}

	public function getOption($option) {
		return $this->options[$option];
	}
	
	// better than the array_merge_recursice, since that one create new arrays
	protected static function array_merge_recursive_simple() {
		if (func_num_args() < 2) {
			trigger_error(__METHOD__ .' needs two or more array arguments', E_USER_WARNING);
			return;
		}
		$arrays = func_get_args();
		$merged = array();
		while ($arrays) {
			$array = array_shift($arrays);
			if (!is_array($array)) {
				trigger_error(__METHOD__ .' encountered a non array argument', E_USER_WARNING);
				return;
			}
			if (!$array) continue;
			foreach ($array as $key => $value) {
				if (is_string($key)) {
					if (is_array($value) && array_key_exists($key, $merged) && is_array($merged[$key]))
						$merged[$key] = call_user_func(__METHOD__, $merged[$key], $value);
					else
						$merged[$key] = $value;
				} else {
					$merged[] = $value;
				}
			}
		}
		return $merged;
	}

}
// crmv@65455e