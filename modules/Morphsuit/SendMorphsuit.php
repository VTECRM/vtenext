<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@35153 crmv@103922 */

// crmv@171581
if (!isset($root_directory)) {
	require_once('../../config.inc.php');
	chdir($root_directory);
}
require_once('include/utils/utils.php'); 
// crmv@171581e

$installation_mode = false;
if (empty($_SESSION)) {
	// disable session limiter for the activation
	VteSession::$maxRequests = 100; // crmv@146653
	VteSession::start();
}
if (VteSession::get('morph_mode') == 'installation') {
	$installation_mode = true;
}
include('vteversion.php');	//crmv@130421 crmv@181168

global $adb;
if (!empty($_REQUEST['vte_user_info'])) {
	$vte_user_info = Zend_Json::decode($_REQUEST['vte_user_info']);
	$email = $vte_user_info['email'];
} elseif (!empty($_REQUEST['email'])) {
	$email = $_REQUEST['email'];
}
$update_mode = false;
if (file_exists('modules/Update/free_changes') && getUserName(1) == 'admin') {
	$update_mode = true;
}
$tipo_installazione = strval($_REQUEST['tipo_installazione']);
$durata_installazione = strval($_REQUEST['durata_installazione']);
$numero_utenti = strval($_REQUEST['numero_utenti']);
$params = array(
	'email'=>$email,
	'tipo_installazione'=>$tipo_installazione,
	'durata_installazione'=>$durata_installazione,
	'numero_utenti'=>$numero_utenti,
	'subversion'=>$enterprise_subversion,	//crmv@130421
);
$chiave = getChiave($params);

function getChiave($params) {
	
	global $application_unique_key,$root_directory,$adb,$table_prefix;
	$params['application_unique_key'] = $application_unique_key;
	$params['root_directory'] = $root_directory;
	$params['data_installazione'] = date('Y-m-d');
	
	$mac_address = OSUtils::getMACAddress(); // crmv@167416
	$params['mac_address'] = $mac_address;
	
	if ($params['tipo_installazione'] == 'Free') {
		$params['numero_utenti'] = 0;
		$params['roles'] = 3;	//Organisation + 2
		$params['profiles'] = 2;
		$params['pdf'] = 1;
		$params['adv_sharing_rules'] = 1;
		$params['sharing_rules_user'] = 1;
		
		$result = $adb->query("select * from {$table_prefix}_role");
		if ($result && $adb->num_rows($result) > 0) {
			if ($adb->num_rows($result) > $params['roles']) {
				$params['roles'] = $adb->num_rows($result);
			}
		}
		
		$result = $adb->query("select * from {$table_prefix}_profile");
		if ($result && $adb->num_rows($result) > 0) {
			if ($adb->num_rows($result) > $params['profiles']) {
				$params['profiles'] = $adb->num_rows($result);
			}
		}
		
		$result = $adb->query("SELECT COUNT(*) as count FROM {$table_prefix}_pdfmaker GROUP BY module");
		if ($result && $adb->num_rows($result) > 0) {
			$count = array();
			while($row=$adb->fetchByAssoc($result)) {
				$count[] = $row['count'];
			}
			if (!empty($count) && max($count) > $params['pdf']) {
				$params['pdf'] = max($count);
			}
		}
		
		$othermodules = getSharingModuleList();
		if(!empty($othermodules)) {
			$count = array();
			foreach($othermodules as $moduleresname) {
				$tmp = getAdvSharingRuleList($moduleresname) ?: array(); // crmv@192073
				$count[] = count($tmp);
			}
			if (!empty($count) && max($count) > $params['adv_sharing_rules']) {
				$params['adv_sharing_rules'] = max($count);
			}
		}
		
		$othermodules = getSharingModuleList(Array('Contacts'));
		if(!empty($othermodules)) {
			$result = $adb->query("SELECT id FROM {$table_prefix}_users WHERE status = 'Active' AND user_name <> 'admin'");
			if ($result) {
				$count = array();
				while($row=$adb->fetchByAssoc($result)) {
					foreach($othermodules as $moduleresname) {
						$tmp = getSharingRuleListUser($moduleresname,$row['id']) ?: array(); // crmv@192073
						$count[] = count($tmp);
					}
				}
				if (!empty($count) && max($count) > $params['sharing_rules_user']) {
					$params['sharing_rules_user'] = max($count);
				}
			}
		}
	}
	
	$key = generate_key_pair_morphsuit();
	$chiave = encrypt_morphsuit($key['public_key'],Zend_Json::encode($params));
	
	return urlencode($key['private_key'].'-----'.$chiave);
}

if ($_REQUEST['type'] == 'time_expired') {
	$saved_morphsuit = getSavedMorphsuit();
	$saved_morphsuit = urldecode(trim($saved_morphsuit));
	$private_key = substr($saved_morphsuit,0,strpos($saved_morphsuit,'-----'));
	$enc_text = substr($saved_morphsuit,strpos($saved_morphsuit,'-----')+5);
	$saved_morphsuit = @decrypt_morphsuit($private_key,$enc_text);
	$saved_morphsuit = Zend_Json::decode($saved_morphsuit);
	$saved_morphsuit_id = $saved_morphsuit['id'];
}

if ($tipo_installazione == 'Free') {
	$saved_morphsuit = getSavedMorphsuit();
	if (empty($saved_morphsuit)) {	// first activation
		include_once("modules/Users/Users.php");
		$user = CRMEntity::getInstance('Users');
		$user->retrieve_entity_info(1,'Users');
	}
	echo $chiave;
	exit;
}

include('modules/Morphsuit/HeaderMorphsuit.php');

require_once('modules/Morphsuit/Morphsuit.php');
$focusMorphsuit = new Morphsuit();
$vteActivationMail = $focusMorphsuit->vteActivationMail;

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
							<?php if ($_REQUEST['type'] == 'time_expired') { ?>
							<div class="col-xs-12 text-center content-padding">
								<h4><?php echo $mod_strings['LICENSE_ID'].': <b>123456'.$saved_morphsuit_id.'</b>'; ?></h4>
							</div>
							<?php } ?>
						</div>
						
						<div id="config" class="col-xs-12">
						
							<div class="col-xs-12">
								<div class="col-xs-12">
								
								<?php if ($chiave != '') { ?>
								<form action="index.php" method="post" id="MorphsuitForm">
								<input type="hidden" name="module" value="Morphsuit">
								<input type="hidden" name="action" value="MorphsuitAjax">
								<input type="hidden" name="file" value="">
								<input type="hidden" name="type" value="">
								<input type="hidden" name="vte_user_info" value='<?php echo $_REQUEST['vte_user_info']; ?>'>
								<?php if ($installation_mode) { ?>
								<input type="hidden" name="mode" value="installation">
								<?php } ?>
									
									<div class="col-xs-6">
										<h4><?php echo sprintf($mod_strings['LBL_MORPHSUIT_DESCRIPTION'],$vteActivationMail); ?></h4>
										<table class="table borderless">
											<tr><td>
												<label for="chiave"><?php echo $mod_strings['LBL_MORPHSUIT_KEY']; ?></label>
												<div class="dvtCellInfo">
												<textarea class="small detailedViewTextBox" style="resize:vertical" id="chiave" name="chiave" readonly><?php echo $chiave; ?></textarea>
												</div>
											</td></tr>
											<tr><td align="right">
												<input type="submit" onclick="getObj('file').value='MailMorphsuit';getObj('type').value='SendMorphsuit';" class="crmbutton small edit" value="<?php echo $mod_strings['LBL_MORPHSUIT_SEND_REQUEST']; ?>" />
											</td></tr>
											<tr><td>
												<label for="valida_chiave"><?php echo $mod_strings['LBL_MORPHSUIT_CODE']; ?></label>
												<div class="dvtCellInfo">
												<textarea class="small detailedViewTextBox" style="resize:vertical" id="valida_chiave" name="valida_chiave"></textarea>
												</div>
											</td></tr>
										</table>
									</div>
			
									<div class="col-xs-6">
										<h4><?php echo $mod_strings['LBL_MORPHSUIT_ADMIN_CONFIG']; ?></h4>
										<table class="table borderless">
										<?php if ($installation_mode && !$update_mode) { ?>
											<tr>
												<td align="left">
													<label for="user_name"><?php echo getTranslatedString('User Name','Users'); ?></label>
													<div class="dvtCellInfo">
														<input id="user_name" name="user_name" class="small detailedViewTextBox" value="" type="text" />
													</div>
												</td>
											</tr>
											<tr>
												<td align="left">
													<label for="user_password"><?php echo getTranslatedString('Password','Users'); ?></label>
													<div class="dvtCellInfo">
														<input id="user_password" name="user_password" class="small detailedViewTextBox" value="" type="password" />
													</div>
												</td>
											</tr>
											<tr>
												<td align="left">
													<label for="confirm_password"><?php echo getTranslatedString('Confirm Password','Users'); ?></label>
													<div class="dvtCellInfo">
														<input id="confirm_password" name="confirm_password" class="small detailedViewTextBox" value="" type="password" />
													</div>
												</td>
											</tr>
											<tr>
												<td align="left">
													<label for="first_name"><?php echo getTranslatedString('First Name','Users'); ?></label>
													<div class="dvtCellInfo">
														<input id="first_name" name="first_name" class="small detailedViewTextBox" value="" type="text" />
													</div>
												</td>
											</tr>
											<tr>
												<td align="left">
													<label for="last_name"><?php echo getTranslatedString('Last Name','Users'); ?></label>
													<div class="dvtCellInfo">
														<input id="last_name" name="last_name" class="small detailedViewTextBox" value="" type="text" />
													</div>
												</td>
											</tr>
											<tr>
												<td align="left">
													<label for="email1"><?php echo getTranslatedString('Email','Morphsuit'); ?></label>
													<div class="dvtCellInfo">
														<input id="email1" name="email1" class="small detailedViewTextBox" value="" type="text" />
													</div>
												</td>
											</tr>
										<?php } ?>
										</table>
									</div>
								
								</form>
								<?php } ?>
							
								</div>
							</div>
														
						</div>

						<div id="nav-bar" class="col-xs-12 nopadding">
							<div id="nav-bar-inner" class="col-xs-12">	
								<div class="col-xs-6 text-left">
									<button type="button" onclick="<?php if ($installation_mode || $_REQUEST['type'] == 'time_expired') { ?>history.back();<?php } else { ?>getObj('file').value='RequestMorphsuit';submitStd();<?php } ?>" class="crmbutton small btn-arrow-left"><?php echo $mod_strings['LBL_MORPHSUIT_PREVIOUS']; ?></button>
								</div>
								<div class="col-xs-6 text-right">
									<button type="button" onclick="getObj('file').value='CheckMorphsuit';submitStd();" class="crmbutton small save"><?php echo $mod_strings['LBL_MORPHSUIT_ACTIVATE']; ?></button>
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
function validateChiave() {
	if (jQuery('#chiave').val() == '') {
		alert('<?php echo $mod_strings['LBL_MORPHSUIT_KEY_EMPTY']; ?>');
		return false;
	}
	<?php if ($installation_mode && !$update_mode) { ?>
		if (getObj('file').value != 'MailMorphsuit') {
			if (!emptyCheck('user_name','<?php echo getTranslatedString('User Name','Users'); ?>',getObj('user_name').type))
				return false;
			if (!emptyCheck('user_password','<?php echo getTranslatedString('Password','Users'); ?>',getObj('user_password').type))
				return false;
			if (!emptyCheck('confirm_password','<?php echo getTranslatedString('Confirm Password','Users'); ?>',getObj('confirm_password').type))
				return false;
			if (!emptyCheck('last_name','<?php echo getTranslatedString('Last Name','Users'); ?>',getObj('last_name').type))
				return false;
			if (!emptyCheck('email1','<?php echo getTranslatedString('Email','Morphsuit'); ?>',getObj('email1').type))
				return false;
			if (trim(getObj('user_password').value) == trim(getObj('confirm_password').value)) {
				var values = {};
				values['user_name'] = trim(getObj('user_name').value);
				values['first_name'] = trim(getObj('first_name').value);
				values['last_name'] = trim(getObj('last_name').value);
				res = getFile('<?php echo $path; ?>modules/Users/CheckPasswordCriteria.php?record=&password='+getObj('user_password').value+'&row='+encodeURIComponent(JSON.stringify(values)));
				if (res == "no") {
					alert('<?php $focus = CRMEntity::getInstance('Users'); echo sprintf(getTranslatedString('LBL_NOT_SAFETY_PASSWORD','Users'),$focus->password_length_min); ?>');
					return false;
				}
			} else {
				alert("<?php echo getTranslatedString('ERR_REENTER_PASSWORDS','Users'); ?>");
				return false;
			}
		}
	<?php } ?>
	if (getObj('file').value != 'MailMorphsuit') {
		if (jQuery('#valida_chiave').val() == '') {
			alert('<?php echo $mod_strings['LBL_MORPHSUIT_CODE_EMPTY']; ?>');
			return false;
		}
	}
	pleaseWait('enable');
	return true;
}

function submitStd() {
	jQuery('#MorphsuitForm').submit();
}

function checkMorphsuit(res) {
	if (getObj('file').value == 'MailMorphsuit') {
		if (res != '') {
			alert(res+'\n\n<?php echo $mod_strings['LBL_MORPHSUIT_ACTIVATION_MAIL_ERROR']; ?>');
			pleaseWait('disable');
			return false;
		}
		else {
			alert('<?php echo $mod_strings['LBL_MORPHSUIT_ACTIVATION_MAIL_OK']; ?>');
			pleaseWait('disable');
			return true;
		}
	} else {
		if (res == 'yes') {
			// crmv@99315
			vtealert('<?php echo $mod_strings['LBL_MORPHSUIT_CODE_RIGHT']; ?>', function() {
				var res1 = getFile('<?php echo $path; ?>index.php?module=Morphsuit&action=MorphsuitAjax&file=CheckSMTP');
				pleaseWait('disable');
				if (res1 != 'ok') {
					vteconfirm("<?php echo $mod_strings['LBL_ERROR_SMTP']; ?>", function(yes) {
						if (yes) {
							window.location.href = "<?php echo $path; ?>index.php?module=Settings&action=EmailConfig&parenttab=Settings";
						} else {
							window.location.href = "<?php echo $path; ?>index.php";
						}
					});
					return;
				}
				window.location.href = "<?php echo $path; ?>index.php";
			}, {showOkButton: true});
			// crmv@99315e
		}
		else {
			alert('<?php echo $mod_strings['LBL_MORPHSUIT_CODE_WRONG']; ?>');
			pleaseWait('disable');
		}
	}
}

function pleaseWait(status) {
	if (status == 'enable') {
		VteJS_DialogBox.progress();
	} else {
		VteJS_DialogBox.hideprogress();
	}
}

jQuery('#tipo_installazione').val('<?php echo $tipo_installazione; ?>');
jQuery('#durata_installazione').val('<?php echo $durata_installazione; ?>');

var options = {
	beforeSubmit:	validateChiave,		// pre-submit callback 
	success: checkMorphsuit		// post-submit callback 
};

jQuery('#MorphsuitForm').ajaxForm(options);
</script>
</body>
</html>
<?php die; ?>