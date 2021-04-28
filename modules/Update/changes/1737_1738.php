<?php 

// crmv@158563
global $adb;
$em = new VTEventsManager($adb);
$em->registerHandler('vtiger.entity.beforesave', 'modules/Geolocalization/GeolocalizationHandler.php', 'GeolocalizationHandler');
