<?php 
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@35153 crmv@54179 crmv@103922 */

// crmv@198545
if (!isset($root_directory)) {
	require_once('../../config.inc.php');
	chdir($root_directory);
}
require_once('include/utils/utils.php'); 
// crmv@198545e

$installation_mode = false;
if ($_REQUEST['morph_mode'] == 'installation' || VteSession::get('morph_mode') == 'installation') {
	$installation_mode = true;
	// crmv@198545 - removed code
	if (isMorphsuitActive()) {
		exit;
	}
	global $enterprise_mode; // crmv@192073
	include('vteversion.php'); // crmv@181168
	VteSession::start();
	VteSession::set('morph_mode', 'installation');
	
	if ($enterprise_mode == 'VTENEXTCE') { // crmv@192073
		// recalc application_unique_key
		$application_unique_key = md5(time() . rand(1,9999999) . md5($root_directory));
		$configInc = file_get_contents('config.inc.php');
		$configInc = preg_replace('/^\$application_unique_key.*$/m', "\$application_unique_key = '{$application_unique_key}';", $configInc);
		if (is_writable('config.inc.php')) file_put_contents('config.inc.php', $configInc);
		
		// recalc admin accesskey
		require_once 'include/Webservices/Utils.php';
		$accesskey = vtws_generateRandomAccessKey(16);
		$adb->pquery("update {$table_prefix}_users set accesskey=? where id=?",array($accesskey,1));
		$priv_file = $root_directory.'user_privileges/user_privileges_1.php';
		$userfile = file_get_contents($priv_file);
		$userfile = preg_replace("/'accesskey'\s*=>\s*[^,]+,/", "'accesskey'=>'{$accesskey}',", $userfile);
		if (is_writable($priv_file)) file_put_contents($priv_file, $userfile);
	} // crmv@192073
	
}
$update_mode = false;
if (file_exists('modules/Update/free_changes') && getUserName(1) == 'admin') {
	$update_mode = true;
} elseif (getUserName(1) != 'admin') {
	$username_free = getUserName(1);
}
if (isFreeVersion()) {
	$morph_activation_message = '<b>'.$mod_strings['LBL_MORPHSUIT_BUSINESS_ACTIVATION'].'</b>';
} else {
	$morph_activation_message = $mod_strings['LBL_MORPHSUIT_TIME_EXPIRED'];
}
include('modules/Morphsuit/HeaderMorphsuit.php');
$focus = CRMEntity::getInstance('Morphsuit');

($installation_mode) ? $path = '../../' : $path = '';

$sectionTitle = $mod_strings['LBL_MORPHSUIT_ACTIVATION']." $enterprise_mode $enterprise_current_version";
?>

<body>
<div id="main-container" class="container">
	<div class="row">
		<div class="col-xs-offset-1 col-xs-10">
			
			<div id="content" class="col-xs-12">
				<div id="content-cont" class="col-xs-12">
					<div id="content-inner-cont" class="col-xs-12">
						
						<div class="col-xs-12 content-padding">	
							<div class="col-xs-8 vcenter text-left">
								<h2><?php echo $sectionTitle; ?></h2>
							</div><!--
							--><div class="col-xs-4 nopadding vcenter text-right">
								<a href="<?php echo $enterprise_website[0]; ?>" target="_blank">
									<img src="<?php echo $path; ?>themes/logos/vtenext.png" />
								</a>
							</div>
							<div class="col-xs-12 text-center content-padding">
								<h4><?php echo $morph_activation_message.'<br />'.$mod_strings['LBL_MORPHSUIT_SITE_LOGIN']; ?>&nbsp;your VTECRM LTD Partner Account.</h4>
							</div>
						</div>
						
						<div id="config" class="col-xs-12">
						
							<div class="col-xs-12">
								<div class="col-xs-8 col-xs-offset-2">
								
									<?php if ($installation_mode) { ?>
									<form action="SendMorphsuit.php" method="post" id="MorphsuitForm">
									<?php } else { ?>
									<form action="index.php" method="post" id="MorphsuitForm">
									<?php } ?>
									<input type="hidden" name="module" value="Morphsuit">
									<input type="hidden" name="action" value="MorphsuitAjax">
									<input type="hidden" name="file" value="SendMorphsuit">
									<input type="hidden" name="type" value="<?php echo $_REQUEST['type']; ?>">
									<input type="hidden" name="vte_user_info" value="">
									
									<table class="table borderless" id="Standard">
											<tr>
												<td align="left">
													<label for="username_std">Username:</label>
													<div class="dvtCellInfo">
														<input id="username_std" name="username_std" class="small detailedViewTextBox" value="" type="text" />
													</div>
												</td>
											</tr>
											<tr>
												<td align="left">
													<label for="username_std">Password:</label>
													<div class="dvtCellInfo">
														<input id="password_std" name="password_std" class="small detailedViewTextBox" value="" type="password" />
													</div>
												</td>
											</tr>
											<tr height="20px"><td align="center" colspan="2"></td></tr>
											<tr>
												<td align="left">
													<label for="tipo_installazione"><?php echo $mod_strings['LBL_MORPHSUIT_INSTALLATION_TYPE']; ?>:</label>
													<div class="dvtCellInfo">
														<select id="tipo_installazione" name="tipo_installazione" class="small detailedViewTextBox">
															<option value="produzione"><?php echo $mod_strings['LBL_MORPHSUIT_PROD']; ?></option>
															<option value="test"><?php echo $mod_strings['LBL_MORPHSUIT_TEST']; ?></option>
															<option value="demo"><?php echo $mod_strings['LBL_MORPHSUIT_DEMO']; ?></option>
														</select>
													</div>
												</td>
											</tr>
											<tr>
												<td align="left">
													<label for="durata_installazione"><?php echo $mod_strings['LBL_MORPHSUIT_INSTALLATION_LENGTH']; ?>:</label>
													<div class="dvtCellInfo">
														<select id="durata_installazione" name="durata_installazione" class="small detailedViewTextBox">
															<option value="1 year"><?php echo $mod_strings['LBL_MORPHSUIT_1Y']; ?></option>
															<option value="6 months"><?php echo $mod_strings['LBL_MORPHSUIT_6M']; ?></option>
															<option value="30 days"><?php echo $mod_strings['LBL_MORPHSUIT_30D']; ?></option>
															<option value="15 days"><?php echo $mod_strings['LBL_MORPHSUIT_15D']; ?></option>
															<option value="1 day"><?php echo $mod_strings['LBL_MORPHSUIT_1D']; ?></option>
														</select>
													</div>
												</td>
											</tr>
											<tr>
												<td align="left">
													<label for="numero_utenti"><?php echo $mod_strings['LBL_MORPHSUIT_USER_NUMBER']; ?>:</label>
													<div class="dvtCellInfo">
														<select id="numero_utenti" name="numero_utenti" class="small detailedViewTextBox">
															<option value="9"><?php echo $mod_strings['LBL_MORPHSUIT_USER_NUMBER_10']; ?></option>
															<option value="19"><?php echo $mod_strings['LBL_MORPHSUIT_USER_NUMBER_20']; ?></option>
															<option value="49"><?php echo $mod_strings['LBL_MORPHSUIT_USER_NUMBER_50']; ?></option>
															<option value="99"><?php echo $mod_strings['LBL_MORPHSUIT_USER_NUMBER_100']; ?></option>
															<option value=""><?php echo $mod_strings['LBL_MORPHSUIT_USER_NUMBER_UNLIMITED']; ?></option>
														</select>
													</div>
												</td>
											</tr>
											<tr><td colspan="2">&nbsp;</td></tr>
											<tr>
												<td colspan="2" align="center">
													<div class="dvtCellInfo">
														<textarea class="small detailedViewTextBox" style="resize:vertical" readonly><?php echo getTranslatedString('LBL_PRIVACY_DESC','Settings'); ?></textarea>
													</div>
												</td>
											</tr>
											<tr>
												<td colspan="2" align="left">
													<div class="checkbox">
														<label for="privacy_flag_std"><input type="checkbox" id="privacy_flag_std" name="privacy_flag_std" />&nbsp;&nbsp;<b><?php echo getTranslatedString('LBL_PRIVACY_FLAG','Settings'); ?></b></label>
													</div>
												</td>
											</tr>
										</table>
									</form>
								</div>
							</div>
	
							<div id="nav-bar" class="col-xs-12 nopadding">
								<div id="nav-bar-inner" class="col-xs-12 text-right">	
									<?php if ($_REQUEST['type'] == 'time_expired') { ?>
											<button type="button" onClick="zombieMorph();" class="crmbutton small delete"><?php echo getTranslatedString('LBL_ZOMBIE_MODE','Morphsuit'); ?></button>
									<?php } ?>
									<button type="button" onClick="if (validate()) submitStd();" class="crmbutton small edit btn-arrow-right"><?php echo $mod_strings['LBL_MORPHSUIT_NEXT']; ?></button>
								</div>
							</div>
						</div>

					</div>
				</div>
			</div>
			
			<div id="footer" class="col-xs-12 content-padding">
				<div id="footer-inner" class="col-xs-12 content-padding text-center">
					<div class="spacer-50"></div>
				</div>
			</div>
				
		</div>
	</div>
</div>

<script type="text/javascript">
var currentTab;
currentTab = 'Standard';

function validate() {
	if (currentTab == 'Free') {
		if (!emptyCheck('username_free','Username',getObj('username_free').type))
			return false;
		if (!emptyCheck('password_free','Password',getObj('password_free').type))
			return false;
		if (!emptyCheck('privacy_flag_free','<?php echo getTranslatedString('LBL_PRIVACY_FLAG','Settings'); ?> ',getObj('privacy_flag_free').type))
			return false;
		return true;
	} else {
		if (!emptyCheck('username_std','Username',getObj('username_std').type))
			return false;
		if (!emptyCheck('password_std','Password',getObj('password_std').type))
			return false;
		if (!emptyCheck('privacy_flag_std','<?php echo getTranslatedString('LBL_PRIVACY_FLAG','Settings'); ?> ',getObj('privacy_flag_std').type))
			return false;
		//login
		pleaseWait('enable');
		var url = '<?php echo $focus->vteFreeServer; ?>';
		var params = {
			'method' : 'checkUserCredentials'
		};
		jQuery.ajax({
			url:'<?php echo $path; ?>modules/Morphsuit/MorphParam.php',type:'POST',data:{'value':getObj('username_std').value},async:false,
			complete  : function(res, status) { params['username'] = res.responseText; }
		});
		jQuery.ajax({
			url:'<?php echo $path; ?>modules/Morphsuit/MorphParam.php',type:'POST',data:{'value':getObj('password_std').value},async:false,
			complete  : function(res, status) { params['password'] = res.responseText; }
		});
		var result = '';
		jQuery.ajax({
			url : url,
			type: 'POST',
			data: params,
			complete  : function(res, status) {
				if (status != 'success') {
					alert('Connection with VTECRM Network failed ('+status+')');
					pleaseWait('disable');
					return false;
				} else {
					result = res.responseText;
					if (result == false) {
						alert('Login failed');
						pleaseWait('disable');
						return false;
					} else {
						getObj('vte_user_info').value = result;
						document.forms['MorphsuitForm'].submit();
					}
				}
			}
		});
	}
}

function submitStd() {
	document.forms['MorphsuitForm'].submit();
}

function success(response) {
	jQuery('#checkDataMorphsuit').html(response);
	placeAtCenter(getObj('checkDataMorphsuit'));
	getObj('checkDataMorphsuit').style.top = '0px';
}

function zombieMorph() {
	window.location.href = "index.php?module=Morphsuit&action=MorphsuitAjax&file=Zombie";
}

function submitFree() {
	pleaseWait('enable');
	
	<?php if ($installation_mode) { ?>
		var key = getFile('<?php echo $path; ?>modules/Morphsuit/SendMorphsuit.php?tipo_installazione=Free');
	<?php } else { ?>
		var key = getFile('index.php?module=Morphsuit&action=MorphsuitAjax&file=SendMorphsuit&tipo_installazione=Free');
	<?php } ?>
	var url = '<?php echo $focus->vteFreeServer; ?>';
	var params = {
		'method' : 'generateMorphsuit',
		'revision' : '<?php echo $enterprise_current_build; ?>',
		'subversion' : '<?php echo $enterprise_subversion; ?>',
		<?php
		$saved_morphsuit = getSavedMorphsuit();
		if (!empty($saved_morphsuit)) {
			$saved_morphsuit = urldecode(trim($saved_morphsuit));
			$private_key = substr($saved_morphsuit,0,strpos($saved_morphsuit,'-----'));
			$enc_text = substr($saved_morphsuit,strpos($saved_morphsuit,'-----')+5);
			$saved_morphsuit = @decrypt_morphsuit($private_key,$enc_text);
			$saved_morphsuit = Zend_Json::decode($saved_morphsuit);
			$saved_morphsuit_id = $saved_morphsuit['id'];
			echo "'id' : '{$saved_morphsuit_id}',";
		}
		?>
		'key' : key
	};
	jQuery.ajax({
		url:'<?php echo $path; ?>modules/Morphsuit/MorphParam.php',type:'POST',data:{'value':getObj('username_free').value},async:false,
		complete  : function(res, status) { params['username'] = res.responseText; }
	});
	jQuery.ajax({
		url:'<?php echo $path; ?>modules/Morphsuit/MorphParam.php',type:'POST',data:{'value':getObj('password_free').value},async:false,
		complete  : function(res, status) { params['password'] = res.responseText; }
	});
	var result = '';
	jQuery.ajax({
		url : url,
		type: 'POST',
		data: params,
		complete  : function(res, status) {
			if (status != 'success') {
				alert('Connection with VTECRM Network failed ('+status+')');
				pleaseWait('disable');
			} else {
				result = res.responseText;
				if (result == 'LOGIN_FAILED') {
					alert('Login failed (err 1)');
					pleaseWait('disable');
				} else if (result == 'USER_NOT_IMPORTED') {
					alert('Login failed (err 2)');
					pleaseWait('disable');
				} else if (result == 'VERSION_NOT_ACTIVABLE') {
					alert('<?php echo addslashes($mod_strings['LBL_ERROR_VTE_FREE_NOT_ACTIVABLE']); ?>');
					pleaseWait('disable');
				} else if (result.indexOf('ERROR: The requested URL could not be retrieved')>-1) {
					alert('Connection with VTECRM Network failed (err 3)');
					pleaseWait('disable');
				} else if (result == '') {
					alert('VTECRM Network service fails to create your license (err 4)');
					pleaseWait('disable');
				} else {
					result = eval('('+result+')');
					var new_key = result['new_key'];
					var user_info = {
						'username' : getObj('username_free').value,
						'password' : getObj('password_free').value,
						'email' : result['email'],
						'name' : result['name']
					};
					<?php if ($installation_mode) { ?>
						var url1 = '<?php echo $path; ?>modules/Morphsuit/CheckMorphsuit.php';
					<?php } else { ?>
						var url1 = 'index.php?module=Morphsuit&action=MorphsuitAjax&file=CheckMorphsuit';
					<?php } ?>
					var params1 = {
						'valida_chiave' : new_key,
						'user_info' : JSON.stringify(user_info)
					};
					jQuery.ajax({
						url : url1,
						type: 'POST',
						data: params1,
						complete  : function(res1, status1) {
							var check = res1.responseText;
							if (check != 'yes') {
								alert('VTECRM Network service fails to create your license (err 5)');
							} else if (check == 'yes') {
								var res1 = getFile('<?php echo $path; ?>index.php?module=Morphsuit&action=MorphsuitAjax&file=CheckSMTP');
								if (res1 != 'ok') {
									// crmv@99315
									vteconfirm("<?php echo $mod_strings['LBL_ERROR_SMTP']; ?>", function(yes) {
										if (yes) {
											window.location.href = "<?php echo $path; ?>index.php?module=Settings&action=EmailConfig&parenttab=Settings";
										} else {
											window.location.href = "<?php echo $path; ?>index.php";
										}
									});
									return;
									// crmv@99315e
								}
							}
							window.location.href = "<?php echo $path; ?>index.php";
						}
					});
				}
			}
		}
	});
}

function pleaseWait(status) {
	if (status == 'enable') {
		VteJS_DialogBox.progress();
	} else {
		VteJS_DialogBox.hideprogress();
	}
}
</script>
</body>
</html>
<?php die; ?>