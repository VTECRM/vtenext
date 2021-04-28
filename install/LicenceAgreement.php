<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

$title = $enterprise_mode. ' - ' . $installationStrings['LBL_CONFIG_WIZARD']. ' - ' . $installationStrings['LBL_LICENSE_TERMS'];
$sectionTitle = $installationStrings['LBL_LICENSE_TERMS'];

include_once "install/templates/overall/header.php";

?>

<div id="config" class="col-xs-12">
	<div id="config-inner" class="col-xs-12 content-padding">
		<iframe class="licence" frameborder="0" src="LICENSE.txt" marginwidth="20" scrolling="auto"></iframe>
	</div>
	
	<div id="nav-bar" class="col-xs-12 nopadding">
		<div id="nav-bar-inner" class="col-xs-12">	
			<div class="col-xs-6 text-left nopadding">
				<button type="button" class="crmbutton small edit btn-arrow-left" onClick="window.history.back();"><?php echo $installationStrings['LBL_BACK']; ?></button>
			</div>
			
			<div class="col-xs-6 text-right nopadding">
				<form action="install.php" method="post" name="cancform" id="cancform" class="vcenter">
					<input type="hidden" name="file" value="SetupCancelled.php" />
	        <button type="button" class="crmbutton small edit" onClick="window.document.cancform.submit();"><?php echo $installationStrings['LBL_NOT_AGREE']; ?></button>
				</form>
				
				<form action="install.php" method="post" name="form" id="form" class="vcenter">
					<input type="hidden" name="filename" value="SetInstallationConfig.php" />
					<input type="hidden" name="file" value="CheckSystem.php" />	
	        <button type="button" class="crmbutton small edit btn-arrow-right" onClick="window.document.form.submit();"><?php echo $installationStrings['LBL_AGREE']; ?></button>
				</form>
			</div>
		</div>
	</div>
</div>
			
<?php include_once "install/templates/overall/footer.php"; ?>
