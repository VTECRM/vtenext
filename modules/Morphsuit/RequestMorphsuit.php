<?php 
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@130421 */


// crmv@198545
if (!isset($root_directory)) {
	require_once('../../config.inc.php');
	chdir($root_directory);
}
require_once('include/utils/utils.php'); 
// crmv@198545e

//crmv@35153 crmv@69514 crmv@69892
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
//crmv@35153e

($installation_mode) ? $path = '../../' : $path = '';

require_once('data/CRMEntity.php');
require_once('modules/Morphsuit/Morphsuit.php');

include('modules/Morphsuit/HeaderMorphsuit.php');
$focus = new Morphsuit();

$focusUsers = CRMEntity::getInstance('Users');
$lbl_not_safety_password = sprintf(getTranslatedString('LBL_NOT_SAFETY_PASSWORD','Users'),$focusUsers->password_length_min);

$sectionTitle = 'Administrator user activation';
?>

<body>
<div id="main-container" class="container">
	<div class="row">
		<div class="col-xs-offset-2 col-xs-8">
			
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
						</div>
						
						<div id="config" class="col-xs-12">
						
							<div class="col-xs-12">
								<div class="col-xs-12">
								
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
													<label for="username_std"><?php echo getTranslatedString('User Name','Users'); ?></label>
													<div class="dvtCellInfo">
														<input id="username_std" name="username_std" class="small detailedViewTextBox" value="admin" type="text" disabled="true" />
													</div>
												</td>
											</tr>
											<tr>
												<td align="left">
													<label for="username_std"><?php echo getTranslatedString('Email'); ?></label>
													<div class="dvtCellInfoM">
														<input id="email_std" name="email_std" class="small detailedViewTextBox" value="" />
													</div>
												</td>
											</tr>
											<tr>
												<td align="left">
													<label for="username_std"><?php echo getTranslatedString('First Name','Users'); ?></label>
													<div class="dvtCellInfo">
														<input id="first_name" name="first_name" class="small detailedViewTextBox" value="" />
													</div>
												</td>
											</tr>
											<tr>
												<td align="left">
													<label for="username_std"><?php echo getTranslatedString('Last Name','Users'); ?></label>
													<div class="dvtCellInfo">
														<input id="last_name" name="last_name" class="small detailedViewTextBox" value="" />
													</div>
												</td>
											</tr>
											<tr>
												<td align="left">
													<label for="username_std"><?php echo getTranslatedString('Company','Leads'); ?></label>
													<div class="dvtCellInfo">
														<input id="company" name="company" class="small detailedViewTextBox" value="" />
													</div>
												</td>
											</tr>
											<tr>
												<td align="left">
													<label for="username_std"><?php echo getTranslatedString('LBL_MORPH_NEWSLETTER_LANG','Morphsuit'); ?></label>
													<div class="dvtCellInfo">
														<select id="newsletter_lang" name="newsletter_lang" class="small detailedViewTextBox">
															<option value="ENG" selected="">English</option>
															<option value="ITA">Italian</option>
															<option value="DEU">German</option>
															<option value="DUT">Dutch</option>
														</select>
													</div>
												</td>
											</tr>
											<tr>
												<td align="left">
													<label for="username_std">Password:</label>
													<div class="dvtCellInfoM">
														<input id="password_std" name="password_std" class="small detailedViewTextBox" value="" type="password" />
													</div>
												</td>
											</tr>
											<tr>
												<td align="left">
													<label for="username_std"><?php echo getTranslatedString('Confirm Password','Users'); ?></label>
													<div class="dvtCellInfoM">
														<input id="confirm_password_std" name="confirm_password_std" value="" type="password" class="detailedViewTextBox" />
													</div>
												</td>
											</tr>
											<tr height="20px"><td align="center" colspan="2"></td></tr>
											<tr>
												<td colspan="2" align="center">
													<div class="dvtCellInfo">
														<textarea class="small detailedViewTextBox" style="resize:vertical; height:90px" readonly><?php echo getTranslatedString('LBL_PRIVACY_DESC','Settings'); ?></textarea>
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
									<!--
									<button type="button" onClick="if (validate()) submitStd();" class="crmbutton small edit btn-arrow-right"><?php echo $mod_strings['LBL_MORPHSUIT_NEXT']; ?></button>
									-->
									<input type="button" onClick="if (validateUser()) submitCreateUser();" class="crmbutton small save" value="<?php echo getTranslatedString('LBL_MORPHSUIT_REGISTER','Morphsuit'); ?>" />
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
// crmv@69514

var createUser = true;

function validateUser() {
	//if (!emptyCheck('last_name','Last Name',getObj('last_name').type)) return false;
	if (!emptyCheck('email_std','Email',getObj('email_std').type)) return false;
	if (!patternValidate('email_std','Email','email')) return false;
	if (!emptyCheck('password_std','Password',getObj('password_std').type)) return false;
	if (!emptyCheck('confirm_password_std','Password',getObj('confirm_password_std').type)) return false;
	if (getObj('password_std').value != getObj('confirm_password_std').value) {
		alert("<?php echo getTranslatedString('ERR_REENTER_PASSWORDS','Morphsuit'); ?>");
		return false;
	}
	var checkPasswValues = {'user_name':'admin','first_name':getObj('first_name').value,'last_name':getObj('last_name').value};
	<?php if ($installation_mode) { ?>
		var res = getFile('<?php echo $path; ?>modules/Users/CheckPasswordCriteria.php?record=&password='+getObj('password_std').value+'&row='+encodeURIComponent(JSON.stringify(checkPasswValues)));
	<?php } else { ?>
		var res = getFile('index.php?module=Users&action=UsersAjax&file=CheckPasswordCriteria&record=&password='+getObj('password_std').value+'&row='+encodeURIComponent(JSON.stringify(checkPasswValues)));
	<?php } ?>
	if (res == "no") {
		alert('<?php echo $lbl_not_safety_password; ?>');
		return false;
	}
	if (!emptyCheck('privacy_flag_std','<?php echo getTranslatedString('LBL_PRIVACY_FLAG','Settings'); ?> ',getObj('privacy_flag_std').type)) return false;
	return true;
}

function success(response) {
	jQuery('#checkDataMorphsuit').html(response);
	placeAtCenter(getObj('checkDataMorphsuit'));
	getObj('checkDataMorphsuit').style.top = '0px';
}

function submitCreateUser() {
	pleaseWait('enable');
	
	var params = 'tipo_installazione=Free&email='+getObj('email_std').value;
	<?php if ($installation_mode) { ?>
		var key = getFile('<?php echo $path; ?>modules/Morphsuit/SendMorphsuit.php?'+params);
	<?php } else { ?>
		var key = getFile('index.php?module=Morphsuit&action=MorphsuitAjax&file=SendMorphsuit&'+params);
	<?php } ?>
	var url = '<?php echo $focus->vteFreeServer; ?>';
	var params = {
		'method' : 'generateMorphsuitCommunityUser',
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
		'first_name' : getObj('first_name').value,
		'last_name' : getObj('last_name').value,
		'company' : getObj('company').value,
		'newsletter_lang' : jQuery('#newsletter_lang').val(),
		'key' : key
	};
	jQuery.ajax({
		url:'<?php echo $path; ?>modules/Morphsuit/MorphParam.php',type:'POST',data:{'value':getObj('email_std').value},async:false,
		complete  : function(res, status) { params['email'] = res.responseText; }
	});
	var result = '';
	jQuery.ajax({
		url : url,
		type: 'POST',
		data: params,
		complete  : function(res, status) {
			checkMorphsuit();
		}
	});
}

function checkMorphsuit(new_key, user_info) {
	var user_info = {
		'username' : getObj('username_std').value,
		'password' : getObj('password_std').value,
		'email' : getObj('email_std').value,
		'last_name' : getObj('last_name').value,
		'first_name' : getObj('first_name').value
	};
	<?php if ($installation_mode) { ?>
		var url = '<?php echo $path; ?>modules/Morphsuit/CheckMorphsuit.php';
	<?php } else { ?>
		var url = 'index.php?module=Morphsuit&action=MorphsuitAjax&file=CheckMorphsuit';
	<?php } ?>
	var params = {
		'user_info' : JSON.stringify(user_info)
	};
	jQuery.ajax({
		url : url,
		type: 'POST',
		data: params,
		complete  : function(res1, status1) {
			var check = res1.responseText;
			if (check != 'yes') {
				//alert('VTECRM Network service fails to create your license (err 5)');
				pleaseWait('disable');
				window.location.href = "<?php echo $path; ?>index.php";
			} else if (check == 'yes') {
				var res1 = getFile('index.php?module=Morphsuit&action=MorphsuitAjax&file=CheckSMTP');
				if (res1 != 'ok') {
					if(confirm("<?php echo $mod_strings['LBL_ERROR_SMTP']; ?>")) {
						window.location.href = "<?php echo $path; ?>index.php?module=Settings&action=EmailConfig&parenttab=Settings";
						return true;
					} else {
						window.location.href = "<?php echo $path; ?>index.php";
					}
				} else {
					window.location.href = "<?php echo $path; ?>index.php";
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
