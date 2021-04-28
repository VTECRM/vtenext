<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

//get php configuration settings.  requires elaborate parsing of phpinfo() output
ob_start();
phpinfo(INFO_GENERAL);
$string = ob_get_contents();
ob_end_clean();

$pieces = explode("<h2", $string);
$settings = array();

require_once('config.inc.php');
$install_permitted = false;
if(!isset($dbconfig['db_hostname']) || $dbconfig['db_status']=='_DB_STAT_') {
	$install_permitted = true;
}

foreach($pieces as $val)
{
   preg_match("/<a name=\"module_([^<>]*)\">/", $val, $sub_key);
   preg_match_all("/<tr[^>]*>
									   <td[^>]*>(.*)<\/td>
									   <td[^>]*>(.*)<\/td>/Ux", $val, $sub);
   preg_match_all("/<tr[^>]*>
									   <td[^>]*>(.*)<\/td>
									   <td[^>]*>(.*)<\/td>
									   <td[^>]*>(.*)<\/td>/Ux", $val, $sub_ext);
   foreach($sub[0] as $key => $val) {
		if (preg_match("/Configuration File \(php.ini\) Path /", $val)) {
	   		$val = preg_replace("/Configuration File \(php.ini\) Path /", '', $val);
			$phpini = strip_tags($val);
	   	}
   }

}

$title = $enterprise_mode.' '.$enterprise_current_version. ' (build ' .$enterprise_current_build. ') '. ' - ' . $installationStrings['LBL_CONFIG_WIZARD']. ' - ' . $installationStrings['LBL_WELCOME'];
$sectionTitle = $installationStrings['LBL_CONFIGURATION_WIZARD'];

include_once "install/templates/overall/header.php";

?>
				
<div id="config" class="col-xs-12">
	<div id="config-inner" class="col-xs-12 content-padding">
		<p id="config-title"><?php echo $installationStrings['LBL_ABOUT_CONFIG_WIZARD'] . $enterprise_mode.' '.$enterprise_current_version. ' (build ' .$enterprise_current_build. ') '; ?>.</p>
		<p id="config-content"><br><?php echo $installationStrings['LBL_ABOUT_VTE']; ?></p>
	</div>
  		
	<div id="nav-bar" class="col-xs-12 nopadding">
		<div id="nav-bar-inner" class="col-xs-12 text-right">
			<?php if ($install_permitted == true) { ?>
				<form class="vcenter" action="install.php" method="post" name="installform" id="form">
	        <input type="hidden" name="file" value="LicenceAgreement.php" />	
	        <input type="hidden" name="install" value="true" />	
					<button type="button" class="crmbutton small edit btn-arrow-right" onClick="window.document.installform.submit();"><?php echo $installationStrings['LBL_INSTALL']; ?></button>
				</form>
			<?php } ?>
		</div>
	</div>
</div>
							
<?php include_once "install/templates/overall/footer.php"; ?>
