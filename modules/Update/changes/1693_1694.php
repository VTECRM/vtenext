<?php
$moduleInstance = Vtiger_Module::getInstance('Newsletter');
Vtiger_Link::addLink($moduleInstance->id,'HEADERSCRIPT','StatisticsScript','modules/Campaigns/Statistics.js');