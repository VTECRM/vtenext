<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@103922 */

include('modules/Morphsuit/HeaderMorphsuit.php');
$saved_morphsuit_id = getMorphsuitNo();

$limit_exceeded_mapping = array(
	'users'=>$mod_strings['LBL_MORPHSUIT_USER_NUMBER_EXCEEDED'],
	'roles'=>$mod_strings['LBL_MORPHSUIT_ROLE_NUMBER_EXCEEDED'],
	'profiles'=>$mod_strings['LBL_MORPHSUIT_PROFILE_NUMBER_EXCEEDED'],
	'pdf'=>$mod_strings['LBL_MORPHSUIT_PDF_NUMBER_EXCEEDED'],
	'adv_sharing_rules'=>$mod_strings['LBL_MORPHSUIT_ADV_SHARING_RULE_NUMBER_EXCEEDED'],
	'sharing_rules_user'=>$mod_strings['LBL_MORPHSUIT_SHARING_RULE_USER_NUMBER_EXCEEDED'],
);
$limit = $_REQUEST['limit_exceeded'];
if (in_array($limit,array_keys($limit_exceeded_mapping))) {
	$update_msg = $limit_exceeded_mapping[$limit];
} else {
	$update_msg = $mod_strings['LBL_MORPHSUIT_UPDATE'];
}

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
								<h4><?php echo $update_msg ?></h4>
							</div>
							<div class="col-xs-12 text-center content-padding">
								<h4><?php echo $mod_strings['LICENSE_ID'].': <b>'.$saved_morphsuit_id.'</b>'; ?></h4>
							</div>
						</div>
						
						<div id="config" class="col-xs-12">
						
							<div class="col-xs-12">
								<div class="col-xs-12">
								
									<form action="index.php" method="post" id="MorphsuitForm">
										<input type="hidden" name="module" value="Morphsuit">
										<input type="hidden" name="action" value="MorphsuitAjax">
										<input type="hidden" name="file" value="CheckMorphsuit">
		
										<table class="table borderless">
											<tr><td align="left">
												<label for="valida_chiave"><?php echo $mod_strings['LBL_MORPHSUIT_CODE']; ?></label>
												<div class="dvtCellInfo">
													<textarea class="small detailedViewTextBox" id="valida_chiave" name="valida_chiave" style="resize:vertical"></textarea>
												</div>
											</td></tr>
										</table>
									</form>
									
								</div>
							</div>
							
						</div>
						
						<div id="nav-bar" class="col-xs-12 nopadding">
							<div id="nav-bar-inner" class="col-xs-12 text-right">	
								<?php if ($limit != '') { ?>
								<input type="button" onclick="zombieMorph();" class="crmbutton small delete" value="<?php echo getTranslatedString('LBL_ZOMBIE_MODE','Morphsuit'); ?>" />
								<?php } else { ?>
								<input type="button" onclick="history.back();" class="crmbutton small delete" value="<?php echo getTranslatedString('LBL_CANCEL_BUTTON_LABEL'); ?>" />
								<?php } ?>
								<button type="button" onclick="submitStd();" class="crmbutton small save"><?php echo $mod_strings['LBL_MORPHSUIT_ACTIVATE']; ?></button>
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
function zombieMorph() {
	window.location.href = "index.php?module=Morphsuit&action=MorphsuitAjax&file=Zombie";
}

function validateChiave() {
	pleaseWait('enable');
	if (jQuery('#valida_chiave').val() == '') {
		alert('<?php echo $mod_strings['LBL_MORPHSUIT_CODE_EMPTY']; ?>');
		pleaseWait('disable');
		return false;
	}
	return true;
}

function checkMorphsuit(res) {
	if (res == 'yes') {
		alert('<?php echo $mod_strings['LBL_MORPHSUIT_CODE_RIGHT']; ?>');
		window.location.href = "index.php";
	} else {
		alert('<?php echo $mod_strings['LBL_MORPHSUIT_CODE_WRONG']; ?>');
		pleaseWait('disable');
	}
}

function pleaseWait(status) {
	if (status == 'enable') {
		VteJS_DialogBox.progress();
	} else {
		VteJS_DialogBox.hideprogress();
	}
}

function submitStd() {
	jQuery('#MorphsuitForm').submit();
}

var options = {
	beforeSubmit:	validateChiave,  	// pre-submit callback 
	success: checkMorphsuit,  	// post-submit callback 
};
jQuery('#MorphsuitForm').ajaxForm(options);
</script>
</body>
</html>
<?php die; ?>