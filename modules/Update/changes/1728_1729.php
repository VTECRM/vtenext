<?php
require_once('include/utils/VTEProperties.php');
$VTEP = VTEProperties::getInstance();
$VTEP->setProperty('performance.webservice_log',0);