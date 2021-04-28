<?php
require_once('modules/VteSync/VteSync.php');
$vteSync = VteSync::getInstance();
$salesForceTypeId = $vteSync->getSyncTypeId('SalesForce');
$vteSync->addTypeModules($salesForceTypeId, array('Potentials','Campaigns','HelpDesk','Products','Assets'));