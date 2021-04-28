<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

global $php_max_execution_time;
set_time_limit($php_max_execution_time);

session_start();

$auth_key = $_REQUEST['auth_key'];
if($_SESSION['authentication_key'] != $auth_key) {
	die($installationStrings['ERR_NOT_AUTHORIZED_TO_PERFORM_THE_OPERATION']);
}
global $selected_optional_modules;
if(isset($_REQUEST['selected_modules'])) {
	$_SESSION['installation_info']['selected_optional_modules'] = $_REQUEST['selected_modules'] ;
}

// crmv@151405
global $selected_beta_modules;
if(isset($_REQUEST['selected_beta_modules'])) {
	$_SESSION['installation_info']['selected_beta_modules'] = $_REQUEST['selected_beta_modules'] ;
}
// crmv@151405e

if (isset($_SESSION['installation_info']['admin_email'])) $admin_email = $_SESSION['installation_info']['admin_email'];
if (isset($_SESSION['installation_info']['admin_password'])) $admin_password = $_SESSION['installation_info']['admin_password'];
if (isset($_SESSION['installation_info']['currency_name'])) $currency_name = $_SESSION['installation_info']['currency_name'];
if (isset($_SESSION['installation_info']['currency_code'])) $currency_code = $_SESSION['installation_info']['currency_code'];
if (isset($_SESSION['installation_info']['currency_symbol'])) $currency_symbol = $_SESSION['installation_info']['currency_symbol'];
if (isset($_SESSION['installation_info']['selected_optional_modules'])) $selected_optional_modules = $_SESSION['installation_info']['selected_optional_modules'];
if (isset($_SESSION['installation_info']['selected_beta_modules'])) $selected_beta_modules = $_SESSION['installation_info']['selected_beta_modules']; // crmv@151405

if (isset($_SESSION['installation_info']['db_populate']))
	$db_populate = $_SESSION['installation_info']['db_populate'];

require_once('install/CreateTables.inc.php');

require_once('vtlib/Vtecrm/Package.php');

global $metaLogs;
if ($metaLogs) $metaLogs->disable();

// Install mandatory modules (already pre-installed)
// The order is important, to keep the compatibility
$mandatoryModules = array(
	'SLA', 'ModNotifications', 'Mobile', 'Ddt', 'FieldFormulas',
	'Touch', 'Sms', 'Services', 'Morphsuit', 'Timecards',
	'Assets', 'Charts', 'WSAPP', 'PDFMaker', 'Myfiles',
	'ProjectMilestone', 'ProjectTask', 'JobOrder', 'ProjectPlan', // crmv@194733
	'Conditionals', 'M', 'ModComments', 'Webforms',
	'MyNotes', 'PBXManager', 'Visitreport',
	'ServiceContracts', 'Targets', 'Newsletter',
	'Transitions', 'Fax', 'Geolocalization',
	'ChangeLog',
);
foreach($mandatoryModules as $m) {
	$package = new Vtecrm_Package();
	$package->importByManifest($m);
}

// Install Vtlib Compliant Modules
Common_Install_Wizard_Utils::installMandatoryModules();
Installation_Utils::installOptionalModules($selected_optional_modules);
Installation_Utils::installBetaModules($selected_beta_modules); // crmv@151405

// crmv@97862 - hide the emails module
$emailsInst = Vtecrm_Module::getInstance('Emails');
if ($emailsInst) $emailsInst->hide(array('hide_report' => 1));
// crmv@97862e

//crmv@29079
$modCommentsFocus = CRMEntity::getInstance('ModComments');
$modCommentsFocus->addWidgetToAll();
//crmv@29079e

//crmv@29463
$leadsFocus = CRMEntity::getInstance('Leads');
$leadsFocus->updateConvertLead();
//crmv@29463e

//crmv@3083m
$myNotesFocus = CRMentity::getInstance('MyNotes');
$myNotesFocus->addWidgetToAll();
//crmv@3083me

//crmv@2963m
// install modules by folder and manifest (put xml file in modules/MODULENAME/manifest.xml)
$othermodules_to_install = array('Messages', 'ProductLines', 'Processes', 'Employees', 'VteSync', 'ConfProducts'); // crmv@44323 crmv@83576 crmv@161021 crmv@176547 crmv@198024
foreach($othermodules_to_install as $m) {
	$package = new Vtecrm_Package();
	$package->importByManifest($m);
}
//crmv@2963me

// install not entity modules
$notEntityModules = array('Popup','Area');
foreach ($notEntityModules as $module) {
	$Mod = Vtecrm_Module::getInstance($module);
	if (empty($Mod)) {
		$Mod = new Vtecrm_Module();
		$Mod->name = $module;
		$Mod->isentitytype = false;
		$Mod->save();
		$Mod->hide(array('hide_module_manager'=>1, 'hide_profile'=>1));
		$adb->pquery("UPDATE {$table_prefix}_tab SET customized=0 WHERE name=?", array($module));

		require_once("modules/$module/$module.php");
		$instance = new $module();
		if ($instance) {
			$instance->vtlib_handler($module, Vtecrm_Module::EVENT_MODULE_POSTINSTALL);
		}
	}
}

//crmv@3085m
require_once('include/utils/DetailViewWidgets.php');
$focusDetailViewWidgets = new DetailViewWidgets();
$widgets = array('AccountsHierarchy');
foreach($widgets as $widget) {
	$widgetObj = $focusDetailViewWidgets->getWidget($widget);
	$widgetObj->install();
}
$focusDetailViewWidgets->reorder();
//crmv@3085me

include('modules/SDK/src/CalendarTracking/install.php');	// crmv@62394 - install CalendarTracking

//crmv@94084
require_once('include/utils/VTEProperties.php');
$VTEProperties = VTEProperties::getInstance();
$VTEProperties->initDefaultProperties(false); // crmv@148789
$VTEProperties->rebuildCache();
//crmv@94084e

//crmv@102334
require_once('include/utils/ModuleHomeView.php');
$MHW = ModuleHomeView::install();
//crmv@102334e

// crmv@104782 - install MailScanner
require('modules/Settings/MailScanner/Install.php');
// crmv@104782e

//crmv@150751
require_once('include/utils/UserInfoUtil.php');
$UIUtils = UserInfoUtils::getInstance();
$UIUtils->initSystemVersions();
//crmv@150751e

// crmv@168297
require_once('soap/SOAPWebservices.php');
SOAPWebservices::installWS();
// crmv@168297e

//crmv@161554
require_once('include/utils/PrivacyPolicyUtils.php');
require_once('include/utils/GDPRWS/GDPRWS.php');

$PPU = PrivacyPolicyUtils::getInstance();
$PPU->install();

$GDPRWS = GDPRWS::getInstance();
$GDPRWS->install();
//crmv@161554e

// crmv@144893
// create the resources cache, inserting at least one resource
// so the other ones will be appended when requested
require_once('include/utils/ResourceVersion.php');
// force-enable the cache, to create the file
$cache = Cache::getInstance('cacheResources');
if ($cache) $cache->enable();
// now create the resource cache
$RV = ResourceVersion::getInstance();
$RV->enableCacheWrite();
$RV->createResource('include/js/general.js');
$RV->updateResources();
// crmv@144893e

// Unset all of the session variables.
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (isset($_COOKIE[session_name()])) {
   setcookie(session_name(), '', time()-42000, '/');
}

// Finally, destroy the session.
session_destroy();

$title = $enterprise_mode. ' - ' . $installationStrings['LBL_CONFIG_WIZARD']. ' - ' . $installationStrings['LBL_FINISH'];
$sectionTitle = $installationStrings['LBL_CONFIG_COMPLETED'];

include_once "install/templates/overall/header.php";

?>

<div id="config" class="col-xs-12">
	<div id="config-inner" class="col-xs-12">
		<div class="col-xs-12 nopadding">
		
			<form action="install.php" method="post" name="finishform" id="finishform">
				<input type="hidden" name="file" value="InstallationComplete.php" />
			</form>
			
			<div class="spacer-20"></div>
			<b><?php echo $enterprise_mode.' '.$enterprise_current_version. ' (build ' .$enterprise_current_build. ')  - ' . $installationStrings['LBL_SUCCESSFULLY_INSTALLED']; ?></b>
			<div class="spacer-50"></div>
			
			<?php if ($db_populate == 'true') { 
				echo "<b>".$installationStrings['LBL_DEMO_DATA_IN_PROGRESS'] . '...</b>'; 
			} ?>
			<div class="spacer-50"></div>

			<script type="text/javascript">
			<?php if ($db_populate == 'true') { ?>
				VteJS_DialogBox.progress();
				// crmv@192033
				jQuery.ajax({
					url: 'install.php',
					method: 'POST',
					data: "file=PopulateSeedData.php",
					success: function(result) {
						window.document.finishform.submit();
					}
				});
				// crmv@192033e
			<?php } else { ?>
				window.document.finishform.submit();
			<?php } ?>
			</script>
			
		</div>
	</div>
</div>
							
<?php include_once "install/templates/overall/footer.php"; ?>
