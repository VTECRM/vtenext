<?php 

// crmv@203484

$singlepane = true;
if (file_exists('user_privileges/default_module_view.php')) {
	include('user_privileges/default_module_view.php');
	$singlepane = ($singlepane_view == 'true');
	@unlink('user_privileges/default_module_view.php');
}

$VTEP = VTEProperties::getInstance();
$VTEP->setProperty('layout.singlepane_view', $singlepane);
