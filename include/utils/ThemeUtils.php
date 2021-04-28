<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@140887
// crmv@152713

require_once('include/BaseClasses.php');
require_once('include/utils/Cache/CacheStorage.php');

class ThemeUtils extends SDKExtendableUniqueClass {
	
	const RANDOM_BACKGROUND_IMAGE = 0;
	
	const SEQUENTIAL_BACKGROUND_IMAGE = 1;
	
	protected $backgroundImagesFolder = 'themes/wallpapers';
	protected $backgroundImagesExtensions = array('jpg', 'jpeg', 'png', 'gif');
	protected $backgroundImagesLimit = 100;
	
	protected $cycleLoginBackgroundEnabled = true;
	protected $loginBackgroundStrategy = null;
	protected $currentLoginBackgroundColor = null;
	protected $currentLoginBackgroundImage = null;
	
	protected $rcache;
	
	protected $themeDir = 'themes';
	protected $themeFile = 'theme.php';
	
	protected $default_values = array(
		'handle_contestual_buttons' => false, // Use this flag if the theme uses contextual buttons (theme without header buttons) // crmv@190519
		'lateral_left_menu' => false,
		'lateral_right_menu' => false,
	);
	
	public function __construct($theme) {
		$this->rcache = new CacheStorageVar();

		$this->initDefaultProperties($theme);
		$this->overrideDefaultProperties($theme);
	}
	
	public function install() {
		$vteProp = VTEProperties::getInstance();
		
		$prop = $vteProp->get('theme.cycle_login_background');
		if ($prop === null) {
			$prop = $vteProp->set('theme.cycle_login_background', true);
		}
		
		$prop = $vteProp->get('theme.current_login_background_color');
		if ($prop === null) {
			$prop = $vteProp->set('theme.current_login_background_color', '#4C92DA');
		}
		
		$prop = $vteProp->get('theme.current_login_background_image');
		if ($prop === null) {
			$defaultBackgroundImage = $this->getDefaultBackgroundImage();
			$prop = $vteProp->set('theme.current_login_background_image', $defaultBackgroundImage);
		}
		
		$prop = $vteProp->get('theme.login_background_image_strategy');
		if ($prop === null) {
			$prop = $vteProp->set('theme.login_background_image_strategy', 'sequential');
		}
	}
	
	public function getDefaultBackgroundImage() {
		$backgroundImages = $this->getAllBackgroundImages('*');
		
		$defaultBackgroundImage = array();
		
		if (count($backgroundImages)) {
			$defaultBackgroundImage = array('idx' => 0, 'path' => $backgroundImages[0], 'ts' => 0);
		}
		
		return $defaultBackgroundImage;
	}
	
	public function setCycleLoginBackgroundEnabled($cycleLoginBackgroundEnabled) {
		$this->cycleLoginBackgroundEnabled = $cycleLoginBackgroundEnabled;
		
		// Update props
		$vteProp = VTEProperties::getInstance();
		$vteProp->set('theme.cycle_login_background', $cycleLoginBackgroundEnabled);
	}
	
	public function isCycleLoginBackgroundEnabled() {
		return $this->cycleLoginBackgroundEnabled;
	}
	
	public function setLoginBackgroundColor($backgroundColor) {
		$this->currentLoginBackgroundColor = $backgroundColor;
		
		// Update props
		$vteProp = VTEProperties::getInstance();
		$vteProp->set('theme.current_login_background_color', $backgroundColor);
	}
	
	public function getLoginBackgroundColor() {
		return $this->currentLoginBackgroundColor;
	}
	
	public function setLoginBackgroundImage($backgroundImage) {
		$this->currentLoginBackgroundImage = $backgroundImage;
		
		// Update props
		$vteProp = VTEProperties::getInstance();
		$vteProp->set('theme.current_login_background_image', $backgroundImage);
	}
	
	public function shouldChangeLoginBackground() {
		if (!$this->cycleLoginBackgroundEnabled) return false;
		
		$currentLoginBackgroundImage = $this->getLoginBackgroundImage();
		
		if (is_array($currentLoginBackgroundImage)) {
			$ts = $currentLoginBackgroundImage['ts'];
			if ((time() - $ts) > (60 * 60 * 24)) {
				return true;
			}
		}
		
		return false;
	}
	
	public function getLoginBackgroundImage() {
		return $this->currentLoginBackgroundImage;
	}
	
	public function setLoginBackgroundStrategy($strategy) {
		if (!is_numeric($strategy)) {
			$strategy = $this->translateLoginBackgroundStrategy($strategy);
		}
		$this->loginBackgroundStrategy = $strategy;
		
		// Obtain the strategy name
		$strategy = $this->translateLoginBackgroundStrategy($strategy, true);
		
		// Update props
		$vteProp = VTEProperties::getInstance();
		$vteProp->set('theme.login_background_image_strategy', $strategy);
	}
	
	public function getLoginBackgroundStrategy() {
		return $this->loginBackgroundStrategy;
	}
	
	protected function translateLoginBackgroundStrategy($strategy, $reverse = false) {
		$translatedStrategy = null;
		
		if ($reverse) {
			if ($strategy == self::SEQUENTIAL_BACKGROUND_IMAGE) {
				$translatedStrategy = 'sequential';
			} elseif ($strategy == self::RANDOM_BACKGROUND_IMAGE) {
				$translatedStrategy = 'random';
			} else {
				// Unknown
			}
		} else {
			if ($strategy == 'sequential') {
				$translatedStrategy = self::SEQUENTIAL_BACKGROUND_IMAGE;
			} elseif ($strategy == 'random') {
				$translatedStrategy = self::RANDOM_BACKGROUND_IMAGE;
			} else {
				// Unknown
			}
		}
		
		return $translatedStrategy;
	}
	
	public function getNextLoginBackgroundImage() {
		$currentLoginBackgroundImage = $this->getLoginBackgroundImage();
		
		$nextBackgroundImage = array();
		
		if ($this->loginBackgroundStrategy == self::RANDOM_BACKGROUND_IMAGE) {
			$nextBackgroundImage = $this->getRandomLoginBackgroundImage($currentLoginBackgroundImage);
		} else if ($this->loginBackgroundStrategy == self::SEQUENTIAL_BACKGROUND_IMAGE) {
			$nextBackgroundImage = $this->getSequentialLoginBackgroundImage($currentLoginBackgroundImage);
		} else {
			// Do nothing
		}
		
		return $nextBackgroundImage;
	}
	
	public function getRandomLoginBackgroundImage($currentLoginBackgroundImage) {
		if (!is_array($currentLoginBackgroundImage)) return false;
		
		$backgroundImages = $this->getAllBackgroundImages('*');
		
		$rand = rand(0, count($backgroundImages) - 1);
		
		$nextBackgroundImage = array(
			'idx' => $rand,
			'path' => $backgroundImages[$rand],
			'ts' => time()
		);
		
		$this->currentLoginBackgroundImage = $nextBackgroundImage;
		
		return $nextBackgroundImage;
	}
	
	public function getSequentialLoginBackgroundImage($currentLoginBackgroundImage) {
		if (!is_array($currentLoginBackgroundImage)) return false;
		
		$backgroundImages = $this->getAllBackgroundImages('*');
		
		$index = $currentLoginBackgroundImage['idx'];
		$index = isset($backgroundImages[$index + 1]) ? $index + 1 : 0;
		
		$nextBackgroundImage = array(
			'idx' => $index,
			'path' => $backgroundImages[$index],
			'ts' => time()
		);
		
		$this->currentLoginBackgroundImage = $nextBackgroundImage;
		
		return $nextBackgroundImage;
	}
	
	public function getAllBackgroundImages($name) {
		global $root_directory;
		
		$filesList = array();
		
		if (!is_dir($this->backgroundImagesFolder)) return false;
		if (substr($this->backgroundImagesFolder, -1) != '/') $this->backgroundImagesFolder .= '/';
		
		// check for jolly chars
		if (preg_match('/\*|\?/', $name)) {
			// get the first matching file
			chdir($this->backgroundImagesFolder);
			$list = glob($name);
			chdir($root_directory);
			
			if (!$list) return false;
			
			$counter = 0;
			foreach ($list as $file) {
				$path = $this->backgroundImagesFolder . $file;
				if ($file != '.' && $file != '..' && is_readable($path) && is_file($path)) {
					if ($counter >= $this->backgroundImagesLimit) break;
					if (strlen($file) > 300) continue;
					
					// check the extension
					$epos = strrpos($file, '.');
					if ($epos !== false) {
						$ext = strtolower(substr($file, $epos + 1));
					} else {
						$ext = '';
					}
					
					if (is_array($this->backgroundImagesExtensions) && count($this->backgroundImagesExtensions) > 0) {
						if (!in_array($ext, $this->backgroundImagesExtensions)) continue;
					}
					
					$filesList[] = $path;
					$counter++;
				}
			}
		} else {
			$filesList[] = $this->backgroundImagesExtensions . $name;
		}
		
		return $filesList;
	}
	
	protected function initDefaultProperties($theme) {
		global $current_user;
		
		foreach ($this->default_values as $prop => $value) {
			$oldVal = $this->getProperty($prop);
			if ($oldVal === null) {
				$this->setProperty($prop, $value);
			}
		}
		
		$vteProp = VTEProperties::getInstance();
		
		$this->cycleLoginBackgroundEnabled = $vteProp->get('theme.cycle_login_background');
		$this->currentLoginBackgroundColor = $vteProp->get('theme.current_login_background_color');
		$this->currentLoginBackgroundImage = $vteProp->get('theme.current_login_background_image');
		
		if (empty($this->currentLoginBackgroundImage)) {
			$defaultBackgroundImage = $this->getDefaultBackgroundImage();
			$prop = $vteProp->set('theme.current_login_background_image', $defaultBackgroundImage);
			$this->currentLoginBackgroundImage = $defaultBackgroundImage;
		}
		
		$strategy = $vteProp->get('theme.login_background_image_strategy');
		$this->loginBackgroundStrategy = $this->translateLoginBackgroundStrategy($strategy);
		
		// crmv@187406
		if (!empty($current_user) && $current_user->id !== null) {
			$isDarkModePermitted = $this->isDarkModePermitted($current_user);
		} else {
			$isDarkModePermitted = false;
		}
		$this->setProperty('darkmode', $isDarkModePermitted);
		// crmv@187406e
		
		// crmv@202705
		$primaryColors = $vteProp->get('theme.primary_colors');
		if (empty($primaryColors) || (is_array($primaryColors) && !isset($primaryColors[$theme]))) {
			$primaryColor = $this->getThemePrimaryColor($theme, $isDarkModePermitted);
			$primaryColors = is_array($primaryColors) && !empty($primaryColors) ? $primaryColors : [];
			$primaryColors[$theme] = $primaryColor;
			$vteProp->set('theme.primary_colors', $primaryColors);
		} else {
			$primaryColor = $primaryColors[$theme];
		}
		$this->setProperty('primary_color', $primaryColor);
		// crmv@202705e
		
		$this->setProperty('settings_page', $this->isSettingsPage());
		$this->setProperty('body_light', $this->isBodyLight());
	}
	
	// crmv@187406
	public function isDarkModePermitted($current_user) {
		if (!$current_user instanceof Users) return false;
		
		$isPermitted = !$this->isSettingsPage();
		
		return $isPermitted && boolval($current_user->column_fields['dark_mode']);
	}
	// crmv@187406e
	
	protected function isSettingsPage() {
		$moduleName = vtlib_purify($_REQUEST['module']);
		$parentTab = vtlib_purify($_REQUEST['parenttab']);
		
		if ($moduleName === 'Settings' || $parentTab === 'Settings' || $moduleName === 'com_workflow') {//crmv@207901
			return true;
		}
		
		return false;
	}

	protected function isBodyLight() {
		$moduleName = vtlib_purify($_REQUEST['module']);
		$action =  vtlib_purify($_REQUEST['action']);
		$file = vtlib_purify($_REQUEST['file']);
		
		$isBodyLight = $this->isSettingsPage();

		if ($moduleName === 'SDK' && preg_match('/TrackerManager$/im', $file)) {
			$isBodyLight = $isBodyLight || true;
		}

		if ($moduleName === 'Users') {
			$isBodyLight = $isBodyLight || true;
		}
		
		return $isBodyLight;
	}
	
	// crmv@202705
	protected function getThemePrimaryColor($theme, $darkmode) {
		return $this->getWebManifestProperty($theme, 'theme_color', $darkmode);
	}
	// crmv@202705e
	
	protected function overrideDefaultProperties($theme) {
		$filename = $this->getThemeConfigFile($theme);

		if (!empty($filename)) {
			if (!class_exists('ThemeConfig')) require($filename);
			if (!class_exists('ThemeConfig')) return false;
			
			$themeConfig = new ThemeConfig();
			if (!$themeConfig instanceof OptionableClass) return false;
			
			foreach ($this->default_values as $prop => $value) {
				$newVal = $themeConfig->getOption($prop);
				if (!empty($newVal)) {
					$this->setProperty($prop, $newVal);
				}
			}
		}
		
		return false;
	}
	
	protected function getThemeConfigFile($theme) {
		if (empty($theme)) return false;
		
		if (!is_dir($this->themeDir)) return false;
		if (!is_dir($this->themeDir.'/'.$theme)) return false;

		$filePath = $this->themeDir.'/'.$theme.'/'.$this->themeFile;

		if (!is_readable($filePath)) return false;
		
		return $filePath;
	}
	
	// crmv@202705
	protected function getThemeSassFile($theme, $file) {
		if (empty($theme)) return false;
		
		if (!is_dir($this->themeDir)) return false;
		if (!is_dir($this->themeDir.'/'.$theme)) return false;
		
		$filePath = $this->themeDir.'/'.$theme.'/scss/'.$file;
		
		if (!is_readable($filePath)) return false;
		
		return $filePath;
	}
	
	protected function getThemeSassColor($theme, $varname, $darkmode = false) {
		$varvalue = null;
		
		$files = [
			$this->getThemeSassFile($theme, 'vars/_sdk_overrides' . ($darkmode ? '_dm' : '') . '.scss'),
			$this->getThemeSassFile($theme, 'vars/_colors' . ($darkmode ? '_dm' : '') . '.scss'),
		];

		// TODO: Only hex and rgb are supported for now
		
		foreach ($files as $file) {
			if ($file !== false) {
				$contents = file_get_contents($file) ?: '';
				if ($contents) {
					if (preg_match("#^\\\${$varname}\s*:\s*((?:\#|rgb)[\w]*)#im", $contents, $matches)) {
						$varvalue = $matches[1];
						break;
					}
				}
			}
		}
		
		return $varvalue;
	}

	protected function getWebManifestFile($theme, $darkmode = false) {
		if (empty($theme)) return false;
		
		if (!is_dir($this->themeDir)) return false;
		if (!is_dir($this->themeDir.'/'.$theme)) return false;
		
		$filePath = $this->themeDir.'/'.$theme.'/site.webmanifest';
		
		if (!is_readable($filePath)) return false;
		
		return $filePath;
	}

	protected function getWebManifestProperty($theme, $property, $darkmode = false) {
		$file = $this->getWebManifestFile($theme, $darkmode);

		$propertyValue = null;

		if ($file !== false) {
			$contents = file_get_contents($file) ?: '';
			if ($contents) {
				$jsonContents = Zend_Json::decode($contents);
				if (is_array($jsonContents) && isset($jsonContents[$property])) {
					$propertyValue = $jsonContents[$property];
				}
			}
		}
		
		return $propertyValue;
	}
	// crmv@202705e
	
	/**
	 * Alias for getProperty
	 */
	public function get($property) {
		return $this->getProperty($property);
	}
	
	/**
	 * Get all properties
	 */
	public function getAll() {
		$values = $this->rcache->getAll();
		return $values;
	}
	
	/**
	 * Return a stored value
	 */
	protected function getProperty($property) {
		$value = $this->rcache->get($property);
		if ($value !== null) return $value;
		
		return null;
	}
	
	/**
	 * Alias for setProperty
	 */
	public function set($property, $value) {
		return $this->setProperty($property, $value);
	}
	
	/**
	 * Set property value
	 */
	protected function setProperty($property, $value) {
		$this->rcache->set($property, $value);
	}
	
}