<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

require_once('modules/Utilities/Currencies.php');

session_start();

if (isset($_REQUEST['db_hostname'])) $_SESSION['config_file_info']['db_hostname'] = $db_hostname = $_REQUEST['db_hostname'];
if (isset($_REQUEST['db_hostport'])) $_SESSION['config_file_info']['db_hostport'] = $db_hostport = $_REQUEST['db_hostport'];
if (isset($_REQUEST['db_username'])) $_SESSION['config_file_info']['db_username'] = $db_username = $_REQUEST['db_username'];
if (isset($_REQUEST['db_password'])) $_SESSION['config_file_info']['db_password'] = $db_password = $_REQUEST['db_password'];
if (isset($_REQUEST['db_name'])) $_SESSION['config_file_info']['db_name'] = $db_name = $_REQUEST['db_name'];
if (isset($_REQUEST['db_type'])) $_SESSION['config_file_info']['db_type'] = $db_type = $_REQUEST['db_type'];

if (isset($_REQUEST['site_URL'])) $_SESSION['config_file_info']['site_URL']= $site_URL = $_REQUEST['site_URL'];
if (isset($_REQUEST['root_directory'])) $_SESSION['config_file_info']['root_directory'] = $root_directory = $_REQUEST['root_directory'];

if (isset($_REQUEST['currency_name'])) $_SESSION['config_file_info']['currency_name'] = $currency_name = $_REQUEST['currency_name'];
if (isset($_REQUEST['admin_email'])) $_SESSION['config_file_info']['admin_email']= $admin_email = $_REQUEST['admin_email'];

if (isset($_REQUEST['currency_name'])) $_SESSION['installation_info']['currency_name'] = $currency_name = $_REQUEST['currency_name'];
if (isset($_REQUEST['check_createdb'])) $_SESSION['installation_info']['check_createdb'] = $check_createdb = $_REQUEST['check_createdb'];
if (isset($_REQUEST['root_user'])) $_SESSION['installation_info']['root_user'] = $root_user = $_REQUEST['root_user'];
if (isset($_REQUEST['root_password'])) $_SESSION['installation_info']['root_password'] = $root_password = $_REQUEST['root_password'];
if (isset($_REQUEST['admin_email'])) $_SESSION['installation_info']['admin_email']= $admin_email = $_REQUEST['admin_email'];
if (isset($_REQUEST['admin_password'])) $_SESSION['installation_info']['admin_password'] = $admin_password = $_REQUEST['admin_password'];
if (isset($_REQUEST['confirm_admin_password'])) $_SESSION['installation_info']['confirm_admin_password'] = $confirm_admin_password = $_REQUEST['confirm_admin_password'];	//crmv@28327

if (isset($_REQUEST['create_utf8_db'])) 
	$_SESSION['installation_info']['create_utf8_db'] = $create_utf8_db = 'true';
else 
	$_SESSION['installation_info']['create_utf8_db'] = $create_utf8_db = 'false';

if (isset($_REQUEST['db_populate'])) 
	$_SESSION['installation_info']['db_populate'] = $db_populate = 'true';
else
	$_SESSION['installation_info']['db_populate'] = $db_populate = 'false';

if(isset($currency_name)){
	$_SESSION['installation_info']['currency_code'] = $currencies[$currency_name][0];
	$_SESSION['installation_info']['currency_symbol'] = $currencies[$currency_name][1];
}

$create_db = false;
if(isset($_REQUEST['check_createdb']) && $_REQUEST['check_createdb'] == 'on') $create_db = true;

$dbCheckResult = Installation_Utils::checkDbConnection($db_type, $db_hostname,$db_hostport,$db_username, $db_password, $db_name, $create_db, $create_utf8_db, $root_user, $root_password);
$next = $dbCheckResult['flag'];
$error_msg = $dbCheckResult['error_msg'];
$error_msg_info = $dbCheckResult['error_msg_info'];
$db_utf8_support = $dbCheckResult['db_utf8_support'];
$vt_charset = ($db_utf8_support)? "UTF-8" : "ISO-8859-1";
$_SESSION['config_file_info']['vt_charset']= $vt_charset;
$dbarr = Installation_Utils::getDbOptions();
$dbtype_label = $dbarr[$db_type];
if($next == true) {
	$_SESSION['authentication_key'] = md5(microtime());
}
//crmv@28327
$installation_focus = new Installation_Utils();
if (!$installation_focus->checkPasswordCriteria($admin_password,array('user_name'=>'admin','last_name'=>'Administrator'))) {
	$next = false;
	$error_msg_password = $installationStrings['LBL_SAFETY_PASSWORD_ERROR'];
	$error_msg_info_password = sprintf($installationStrings['LBL_NOT_SAFETY_PASSWORD'],$installation_focus->password_length_min);
}
//crmv@28327e

$title = $enterprise_mode. ' - ' . $installationStrings['LBL_CONFIG_WIZARD']. ' - ' . $installationStrings['LBL_CONFIRM_SETTINGS'];
$sectionTitle = $installationStrings['LBL_CONFIRM_CONFIG_SETTINGS'];
$bigTitle = true;

include_once "install/templates/overall/header.php";

?>

<div class="col-xs-12">
	<table class="table borderless">
		<?php if($error_msg) : ?>
		<tr>
			<td>
				<div style="background-color:#ff0000;;padding:5px">
					<b style="color:#ffffff"><?php echo $error_msg ?></b>
				</div>
				<?php if($error_msg_info) : ?>
					<p><?php echo $error_msg_info ?><p>
				<?php endif; ?>
			</td>
		</tr>
		<?php endif; ?>
		<!-- crmv@28327 -->
		<?php if($error_msg_password) : ?>
		<tr>
			<td>
				<div style="background-color:#ff0000;;padding:5px">
					<b style="color:#ffffff"><?php echo $error_msg_password ?></b>
				</div>
				<?php if($error_msg_info_password) : ?>
					<p><?php echo $error_msg_info_password ?><p>
				<?php endif; ?>
			</td>
		</tr>
		<?php endif; ?>
		<!-- crmv@28327e -->
	</table>
</div>
						
<div id="config" class="col-xs-12">
	<div id="config-inner" class="col-xs-12">
		<div class="col-xs-12 nopadding">
			<div class="col-xs-12 nopadding">
			
				<div class="col-xs-12 col-md-6" style="padding-left:0px">
					<div class="col-xs-12 nopadding">
						<h3><?php echo $installationStrings['LBL_DATABASE_CONFIGURATION']; ?></h3>
						<div class="spacer-20"></div>
					
						<table class="table">
							<tr>
								<td noWrap width="40%"><?php echo $installationStrings['LBL_DATABASE_TYPE']; ?></td>
								<td align="left" nowrap> <font class="dataInput"><i><?php if (isset($db_type)) echo "$dbtype_label"; ?></i></font></td>
							</tr>
							<tr>
								<td noWrap width="40%"><?php echo $installationStrings['LBL_DATABASE_NAME']; ?></td>
								<td align="left" nowrap> <font class="dataInput"><i><?php if (isset($db_name)) echo "$db_name"; ?></i></font></td>
							</tr>
							<tr>
								<td noWrap width="40%"><?php echo $installationStrings['LBL_DATABASE_PORT']; ?></td>
								<td align="left" nowrap> <font class="dataInput"><i><?php if (isset($db_hostport)) echo "$db_hostport"; ?></i></font></td>
							</tr>
							<tr>
								<td noWrap width="40%"><?php echo $installationStrings['LBL_DATABASE'].' '.$installationStrings['LBL_UTF8_SUPPORT']; ?></td>
								<td align="left" nowrap> <font class="dataInput"><?php echo ($db_utf8_support)? $installationStrings['LBL_ENABLED'] : "<strong style='color:#df0000';>{$installationStrings['LBL_NOT_ENABLED']}</strong>" ?></font>
								</td>
							</tr>
						</table>
					</div>
				</div>
				
				<div class="col-xs-12 col-md-6" style="padding-right:0px">
					<div class="col-xs-12 nopadding">
						<h3><?php echo $installationStrings['LBL_SITE_CONFIGURATION']; ?></h3>
						<div class="spacer-20"></div>
						
						<table class="table">
							<tr>
								<td width="40%"><?php echo $installationStrings['LBL_URL']; ?></td>
								<td align="left"> <i><?php if (isset($site_URL)) echo $site_URL; ?></i></td>
							</tr>
							<tr>
								<td width="40%"><?php echo $installationStrings['LBL_DEFAULT_CHARSET']; ?></td>
								<td align="left"> <i><?php if (isset($vt_charset)) echo $vt_charset; ?></i></td>
							</tr>
							<tr>
								<td width="40%"><?php echo $installationStrings['LBL_CURRENCY_NAME']; ?></td>
								<td align="left"> <i><?php if (isset($currency_name)) echo $currency_name."(".$currencies[$currency_name][1].")"; ?></i></td>
							</tr>
						</table>
					</div>
				</div>
			</div>
			
		</div>
	</div>
</div>
						
<div id="nav-bar" class="col-xs-12 nopadding">
	<div id="nav-bar-inner" class="col-xs-12">	
		<div class="col-xs-6 text-left">
			<form action="install.php" method="post" name="form" id="form">
				<input type="hidden" name="file" value="SetInstallationConfig.php">
				<button class="crmbutton small edit btn-arrow-left"><?php echo $installationStrings['LBL_CHANGE']; ?></button>
			</form>
		</div>
		
		<div class="col-xs-6 text-right">
			<?php if ($next) : ?>
				<form action="install.php" method="post" name="form" id="form">
					<input type="hidden" name="mode" value="installation">
					<input type="hidden" name="file" value="SelectOptionalModules.php">
					<button class="crmbutton small edit btn-arrow-right"><?php echo $installationStrings['LBL_NEXT']; ?></button>
				</form>
			<?php endif ?>
		</div>
	</div>
</div>
	
<?php include_once "install/templates/overall/footer.php"; ?>
