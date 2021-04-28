<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('include/utils/utils.php');
global $adb,$table_prefix;

$fetch = vtlib_purify($_REQUEST['fetch']);
$ids = vtlib_purify($_REQUEST['ids']);

$csrfToken = RequestHandler::getCSRFToken(); // crmv@200843

if ($fetch=='true') {
	$ids = array_values(array_filter(explode(';',$ids)));
	
	$geo = Geolocalization::getInstance();
	$addresses = $geo->retrieveAddresses($ids);

	echo Zend_Json::encode($addresses);
	exit;
}
?>
<html>
<head>
<title>VTE Map</title>
</head>
<body>
<div id="map_canvas" style="width: 100%; height: 100%"></div>
<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=<?php echo Geolocalization::getApiKey(); ?>"></script> <!-- crmv@124745 crmv@174250 -->
<script type="text/javascript" src="modules/Geolocalization/src/markerclusterer.min.js"></script>
<script type="text/javascript" src="modules/Geolocalization/Geolocalization.js"></script>
<script language="JavaScript" type="text/javascript" src="include/js/jquery.js"></script>
<script language="JavaScript" type="text/javascript" src="include/js/json2.js"></script>
<script language="JavaScript" type="text/javascript" src="include/js/general.js"></script>
<script language="JavaScript" type="text/javascript" src="include/js/csrf.js"></script> <!-- crmv@200843 -->
<script language="JavaScript" type="text/javascript">
VTE.CSRF.initialize('__csrf_token', '<?php echo $csrfToken; ?>'); // crmv@200843
jQuery(document).ready(function() {
	VTEGeolocalization.initializeMap('<?php echo $ids; ?>');
});
</script>
</body>
</html>