<?php
// crmv@200816
SDK::setLanguageEntries('Settings', 'LBL_RECORD_TO_LOAD', array('it_it'=>'Redirect a','en_us'=>'Redirect to'));

// crmv@200912
$mlUtils = ModLightUtils::getInstance();
$modules = $mlUtils->getModuleList();
if (!empty($modules)) {
	foreach($modules as $module) {
		include_once('vtlib/Vtecrm/Module.php');
		$moduleInstance = Vtecrm_Module::getInstance($module);
		$moduleInstance->setDefaultSharing('Public_ReadWriteDelete', 2);
	}
}