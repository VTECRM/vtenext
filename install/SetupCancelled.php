<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

$title = $enterprise_mode. ' - ' . $installationStrings['LBL_CONFIG_WIZARD']. ' - ' . "Setup Cancelled";
$sectionTitle = "Setup Cancelled";

include_once "install/templates/overall/header.php";

?>

<div id="config" class="col-xs-12">
	<div id="config-inner" class="col-xs-12 content-padding">
		<p id="config-content">The setup has been cancelled, you can safely close this browser window.</p>
		<div class="spacer-20"></div>
	</div>
</div>
			
<?php include_once "install/templates/overall/footer.php"; ?>