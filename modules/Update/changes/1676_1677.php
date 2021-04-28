<?php
if (!isModuleInstalled('Employees')) {
	require_once('vtlib/Vtecrm/Package.php');
	$package = new Vtiger_Package();
	$package->importByManifest('Employees');
} else {
	Update::warn('Module Employees is already installed. Please remove it and install the new one.');
	Update::warn('');
}