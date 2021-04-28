<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

@include_once('config.db.php');
global $dbconfig, $vte_legacy_version;
$hostname = $_SERVER['SERVER_NAME'];
$web_root = ($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"]:$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'];
$web_root .= $_SERVER["REQUEST_URI"];
$web_root = str_replace("/install.php", "", $web_root);
$web_root = "http://".$web_root;

$current_dir = pathinfo(dirname(__FILE__));
$current_dir = $current_dir['dirname']."/";
$cache_dir = "cache/";

$newdbname = 'vte'.str_replace(array('.',' '),array(''),strtolower($enterprise_current_version)).'_'.$enterprise_current_build;

require_once('modules/Utilities/Currencies.php');

session_start();

//crmv@24713m
chmod('smartoptimizer/cache', 0777);
@rename('htaccess.txt','.htaccess');
//crmv@24713me

//crmv@35153
$_SESSION['installation_info']['admin_email'] = 'admin@vte123abc987.com';
$password = date('l').time();
$_SESSION['installation_info']['admin_password'] = $password;
$_SESSION['installation_info']['confirm_admin_password'] = $password;
//crmv@35153e

!isset($_REQUEST['host_name']) ? $host_name= $hostname : $host_name = $_REQUEST['host_name'];

!isset($_SESSION['config_file_info']['db_hostname']) ? $db_hostname = $hostname: $db_hostname = $_SESSION['config_file_info']['db_hostname'];
!isset($_SESSION['config_file_info']['db_hostport']) ? $db_hostport = "" : $db_hostport = $_SESSION['config_file_info']['db_hostport'];
!isset($_SESSION['config_file_info']['db_type']) ? $db_type = "" : $db_type = $_SESSION['config_file_info']['db_type'];
!isset($_SESSION['config_file_info']['db_username']) ? $db_username = "" : $db_username = $_SESSION['config_file_info']['db_username'];
!isset($_SESSION['config_file_info']['db_password']) ? $db_password = "" : $db_password = $_SESSION['config_file_info']['db_password'];
!isset($_SESSION['config_file_info']['db_name']) ? $db_name = $newdbname : $db_name = $_SESSION['config_file_info']['db_name'];
!isset($_SESSION['config_file_info']['site_URL']) ? $site_URL = $web_root : $site_URL = $_SESSION['config_file_info']['site_URL'];
!isset($_SESSION['config_file_info']['root_directory']) ? $root_directory = $current_dir : $root_directory = $_SESSION['config_file_info']['root_directory'];
!isset($_SESSION['config_file_info']['admin_email']) ? $admin_email = "" : $admin_email = $_SESSION['config_file_info']['admin_email'];
!isset($_SESSION['config_file_info']['currency_name']) ? $currency_name = 'Euro' : $currency_name = $_SESSION['config_file_info']['currency_name'];

!isset($_SESSION['installation_info']['check_createdb']) ? $check_createdb = "" : $check_createdb = $_SESSION['installation_info']['check_createdb'];
!isset($_SESSION['installation_info']['root_user']) ? $root_user = "" : $root_user = $_SESSION['installation_info']['root_user'];
!isset($_SESSION['installation_info']['root_password']) ? $root_password = "" : $root_password = $_SESSION['installation_info']['root_password'];
!isset($_SESSION['installation_info']['create_utf8_db']) ? $create_utf8_db = "true" : $create_utf8_db = $_SESSION['installation_info']['create_utf8_db'];
!isset($_SESSION['installation_info']['db_populate']) ? $db_populate = "true" : $db_populate = $_SESSION['installation_info']['db_populate'];
!isset($_SESSION['installation_info']['admin_email']) ? $admin_email = "" : $admin_email = $_SESSION['installation_info']['admin_email'];
!isset($_SESSION['installation_info']['admin_password']) ? $admin_password = "" : $admin_password = $_SESSION['installation_info']['admin_password'];	//crmv@28327
!isset($_SESSION['installation_info']['confirm_admin_password']) ? $confirm_admin_password = "" : $confirm_admin_password = $_SESSION['installation_info']['confirm_admin_password'];	//crmv@28327

$db_options = Installation_Utils::getDbOptions();
$installation_focus = new Installation_Utils();	//crmv@28327

$title = $installationStrings['LBL_VTE_CRM']. ' - ' . $installationStrings['LBL_CONFIG_WIZARD']. ' - ' . $installationStrings['LBL_SYSTEM_CONFIGURATION'];
$sectionTitle = $installationStrings['LBL_SYSTEM_CONFIGURATION'];

include_once "install/templates/overall/header.php";

?>

<style>
	.hide_tab{display:none;}
	.show_div{}
</style>

<script type="text/javascript">
	function fnShow_Hide(){
		var sourceTag = document.getElementById('check_createdb').checked;
		if(sourceTag){
			document.getElementById('root_user').className = 'show_div';
			document.getElementById('root_pass').className = 'show_div';
			document.getElementById('create_db_config').className = 'show_div';
			document.getElementById('root_user_txtbox').focus();
		}
		else{
			document.getElementById('root_user').className = 'hide_tab';
			document.getElementById('root_pass').className = 'hide_tab';
			document.getElementById('create_db_config').className = 'hide_tab';
		}
	}
	
	function trim(s) {
	    while (s.substring(0,1) == " ") {
	        s = s.substring(1, s.length);
	    }
	    while (s.substring(s.length-1, s.length) == ' ') {
	        s = s.substring(0,s.length-1);
	    }
	    return s;
	}
	
	function verify_data(form) {
		var isError = false;
		var errorMessage = "";
		if (trim(form.db_hostname.value) =='') {
			isError = true;
			errorMessage += "\n <?php echo $installationStrings['LBL_DATABASE'].' '.$installationStrings['LBL_HOST_NAME']; ?>";
			form.db_hostname.focus();
		}
		if (trim(form.db_username.value) =='') {
			isError = true;
			errorMessage += "\n <?php echo $installationStrings['LBL_DATABASE'].' '.$installationStrings['LBL_USER_NAME']; ?>";
			form.db_username.focus();
		}
		if (trim(form.db_name.value) =='') {
			isError = true;
			errorMessage += "\n <?php echo $installationStrings['LBL_DATABASE_NAME']; ?>";
			form.db_name.focus();
		}
		if (trim(form.site_URL.value) =='') {
			isError = true;
			errorMessage += "\n <?php echo $installationStrings['LBL_SITE_URL']; ?>";
			form.site_URL.focus();
		}
		if (trim(form.root_directory.value) =='') {
			isError = true;
			errorMessage += "\n <?php echo $installationStrings['LBL_PATH']; ?>";
			form.root_directory.focus();
		}
		if (trim(form.admin_password.value) =='') {
			isError = true;
			errorMessage += "\n admin <?php echo $installationStrings['LBL_PASSWORD']; ?>";
			form.admin_password.focus();
		}
		//crmv@28327
		if (trim(form.confirm_admin_password.value) =='') {
			isError = true;
			errorMessage += "\n admin <?php echo $installationStrings['LBL_CONFIRM_PASSWORD']; ?>";
			form.confirm_admin_password.focus();
		}
		//crmv@28327e
		if (trim(form.admin_email.value) =='') {
			isError = true;
			errorMessage += "\n admin <?php echo $installationStrings['LBL_EMAIL']; ?>";
			form.admin_email.focus();
		}
		if (trim(form.currency_name.value) =='') {
	        isError = true;
	        errorMessage += "\n <?php echo $installationStrings['LBL_CURRENCY_NAME']; ?>";
	        form.currency_name.focus();
	    }
	
		if(document.getElementById('check_createdb').checked == true) {
			if (trim(form.root_user.value) =='') {
				isError = true;
				errorMessage += "\n <?php echo $installationStrings['LBL_ROOT']. ' ' .$installationStrings['LBL_USER_NAME']; ?>";
				form.root_user.focus();
			}
		}
	
		if (isError == true) {
			alert("<?php echo $installationStrings['LBL_MISSING_REQUIRED_FIELDS']; ?>:" + errorMessage);
			return false;
		}
		
		//crmv@28327
		if (trim(form.admin_password.value) != trim(form.confirm_admin_password.value)) {
			alert("<?php echo $installationStrings['ERR_REENTER_PASSWORDS']; ?>");
			return false;
		}
		//crmv@28327e
		
		if (trim(form.admin_email.value) != "" && !/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,4})+$/.test(form.admin_email.value)) {
			alert("<?php echo $installationStrings['ERR_ADMIN_EMAIL_INVALID']; ?> - \'"+form.admin_email.value+"\'");
			form.admin_email.focus();
			return false;
		}
	
		var SiteUrl = form.site_URL.value;
	    if(SiteUrl.indexOf("localhost") > -1 && SiteUrl.indexOf("localhost") < 10) {
	        if(confirm("<?php echo $installationStrings['WARNING_LOCALHOST_IN_SITE_URL']; ?>")) {
				form.submit();
	        } else {
	            form.site_URL.select();
	            return false;
	        }
	    } else {
			form.submit();
	    }	
	}
</script>

<div id="config" class="col-xs-12">
	<div id="config-inner" class="col-xs-12 content-padding">
		<div class="col-xs-12 nopadding">
			<div class="col-xs-12 nopadding">
			
				<form action="install.php" method="post" name="installform" id="form">
					<input type="hidden" name="file" value="ConfirmConfig.php" />			
				
					<div class="col-xs-12 col-md-6" style="padding-left:0px">
						<div class="col-xs-12 nopadding">
							<h3><?php echo $installationStrings['LBL_DATABASE_INFORMATION']; ?></h3>
								<div class="spacer-20"></div>
	    	
               	<div class="form-group">
               		<label for="db_type"><?php echo $installationStrings['LBL_DATABASE_TYPE']; ?> <sup><font color=red>*</font></sup></label>
               	
								<?php if(!$db_options) : ?>
								<?php echo $installationStrings['LBL_NO_DATABASE_SUPPORT']; ?>
								<?php elseif(count($db_options) == 1) : ?>
								<?php list($db_type, $label) = each($db_options); ?>
								<input type="hidden" name="db_type" value="<?php echo $db_type ?>"><?php echo $label ?>
								<?php else : ?>
								<div class="dvtCellInfo">
									<select class="detailedViewTextBox" id="db_type" name="db_type">
									<?php foreach($db_options as $db_option_type => $label) : ?>
									<option value="<?php echo $db_option_type ?>" <?php if(isset($db_type) && $db_type == $db_option_type) { echo "SELECTED"; } ?>><?php echo $label ?></option>
									<?php endforeach; ?>
									</select>
								</div>
								<?php endif; ?>
               	</div>
		               
								<div class="form-group">
									<label for="db_hostname"><?php echo $installationStrings['LBL_HOST_NAME']; ?> <sup><font color=red>*</font></sup></label>
								<div class="dvtCellInfo">
									<input type="text" class="detailedViewTextBox" id="db_hostname" name="db_hostname" value="<?php if (isset($db_hostname)) echo "$db_hostname"; ?>" />
								</div>
							</div>
							
							<div class="form-group">
								<label for="db_hostport"><?php echo $installationStrings['LBL_DATABASE_PORT']; ?></label>
								<div class="dvtCellInfo">
									<input type="text" class="detailedViewTextBox" id="db_hostport" name="db_hostport" value="<?php if (isset($db_hostport)) echo "$db_hostport"; ?>" />
								</div>
							</div>
							
							<div class="form-group">
								<label for="db_username"><?php echo $installationStrings['LBL_USER_NAME']; ?> <sup><font color=red>*</font></sup></label>
								<div class="dvtCellInfo">
									<input type="text" class="detailedViewTextBox" id="db_username" name="db_username" value="<?php if (isset($db_username)) echo "$db_username"; ?>" />
								</div>
							</div>
							
							<div class="form-group">
								<label for="db_password"><?php echo $installationStrings['LBL_PASSWORD']; ?></label>
								<div class="dvtCellInfo">
									<input type="password" class="detailedViewTextBox" id="db_password" name="db_password" value="<?php if (isset($db_password)) echo "$db_password"; ?>" />
								</div>
							</div>
							
							<div class="form-group">
								<label for="db_name"><?php echo $installationStrings['LBL_DATABASE_NAME']; ?> <sup><font color=red>*</font></sup></label>
								<div class="dvtCellInfo">
									<input type="text" class="detailedViewTextBox" id="db_name" name="db_name" value="<?php if (isset($db_name)) echo "$db_name"; ?>" />
								</div>
							</div>
						
							<div class="form-group">
								<div class="checkbox">
									<label for="check_createdb">
									<?php if($check_createdb == 'on') { ?>
					       		<input class="small" id="check_createdb" name="check_createdb" type="checkbox" id="check_createdb" checked onClick="fnShow_Hide()"/> 
					       	<?php }else{?>
					       		<input class="small" id="check_createdb" name="check_createdb" type="checkbox" id="check_createdb" onClick="fnShow_Hide()"/> 
					       	<?php } ?>&nbsp;
									<b><?php echo $installationStrings['LBL_CREATE_DATABASE'] . " (". $installationStrings['LBL_DROP_IF_EXISTS'] .")"; ?></b>
					       	</label>
				       	</div>
			       	</div>
					       	
			      	<div id="root_user" class="hide_tab">
				      	<div class="form-group">
				      		<label for="root_user_txtbox"><?php echo $installationStrings['LBL_ROOT']. ' ' .$installationStrings['LBL_USER_NAME']; ?> <sup><font color="red">*</font></sup></label>
				      		<div class="dvtCellInfo">
				      			<input class="detailedViewTextBox" name="root_user" id="root_user_txtbox" value="<?php echo $root_user;?>" type="text">
				      		</div>
				      	</div>
		 	      	</div>
		 	      	
			      	<div id="root_pass" class="hide_tab">
			      		<div class="form-group">
					   			<label for="root_password"><?php echo $installationStrings['LBL_ROOT']. ' ' .$installationStrings['LBL_PASSWORD']; ?></label>
										<div class="dvtCellInfo">
											<input class="detailedViewTextBox" id="root_password" name="root_password" value="<?php echo $root_password;?>" type="password">
										</div>
		          	</div>
	          	</div>
	          	
	          	<div id="create_db_config" class="hide_tab">
								<div class="form-group">
									<div class="checkbox">
										<label for="create_utf8_db">
											<input class="small" type="checkbox" id="create_utf8_db" name="create_utf8_db" <?php if($create_utf8_db == 'true') echo "checked"; ?> /> <!-- DEFAULT CHARACTER SET utf8, DEFAULT COLLATE utf8_general_ci -->
											<b><?php echo $installationStrings['LBL_UTF8_SUPPORT']; ?></b>
										</label>
									</div>
								</div>
							</div>
						
							<div class="form-group">
								<div class="checkbox">
									<label for="db_populate">
									<input type="checkbox" class="dataInput" id="db_populate" name="db_populate" <?php if($db_populate == 'true') echo "checked"; ?> />&nbsp;
									<b><?php echo $installationStrings['LBL_POPULATE_DEMO_DATA']; ?></b>
					       	</label>
				       	</div>
			       	</div>
					
						</div>
					</div>
					
					<div class="col-xs-12 col-md-6" style="padding-right:0px">
						<div class="col-xs-12 nopadding">
							<!-- Web site configuration -->
							<h3><?php echo $installationStrings['LBL_CRM_CONFIGURATION']; ?></h3>
							<div class="spacer-20"></div>
							
							<div class="form-group">		
								<label for="site_URL"><?php echo $installationStrings['LBL_URL']; ?><sup><font color=red>*</font></sup></label>
								<div class="dvtCellInfo">
									<input class="detailedViewTextBox" type="text" id="site_URL"  name="site_URL" value="<?php if (isset($site_URL)) echo $site_URL; ?>" />
								</div>
							</div>
								
							<div class="form-group">		
								<label for="currency_name"><?php echo $installationStrings['LBL_CURRENCY_NAME']; ?><sup><font color=red>*</font></sup></label>
								<div class="dvtCellInfo">
									<select class="detailedViewTextBox" id="currency_name" name="currency_name">
										<?php
											foreach ($currencies as $index => $value) {
											if ($index == $currency_name) {
												echo "<option value='$index' selected>$index(" . $value[1] . ")</option>";
											} else {
												echo "<option value='$index'>$index(" . $value[1] . ")</option>";
											}
										}
										?>
									</select>
								</div>
								<input type="hidden" name="root_directory" value="<?php if (isset($root_directory)) echo "$root_directory"; ?>" />
								<input type="hidden" name="cache_dir" value="<?php if (isset($cache_dir)) echo $cache_dir; ?>" />
								<!-- Admin Configuration -->
								<!-- crmv@35153 -->
								<input type="hidden" name="admin_password" value="<?php if (isset($admin_password)) echo "$admin_password";?>">
								<input type="hidden" name="confirm_admin_password" value="<?php if (isset($confirm_admin_password)) echo "$confirm_admin_password";?>">
								<input type="hidden" name="admin_email" value="<?php if (isset($admin_email)) echo "$admin_email"; ?>">
								<!-- crmv@35153e -->
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<div id="nav-bar" class="col-xs-12 nopadding">
	<div id="nav-bar-inner" class="col-xs-12">	
		<div class="col-xs-6 text-left">
			<button type="button" class="crmbutton small edit btn-arrow-left" onClick="window.history.back();"><?php echo $installationStrings['LBL_BACK']; ?></button>
		</div>
		
		<div class="col-xs-6 text-right">
			<button type="button" class="crmbutton small edit btn-arrow-right" onClick="return verify_data(window.document.installform);"><?php echo $installationStrings['LBL_NEXT']; ?></button>
		</div>
	</div>
</div>
							
<script type="text/javascript">
	fnShow_Hide();
</script>

<?php include_once "install/templates/overall/footer.php"; ?>