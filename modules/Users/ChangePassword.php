<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
// This file is used for all popups on this module
// The popup_picker.html file is used for generating a list from which to find and choose one instance.
// Portions created by CRMVILLAGE.BIZ are Copyright (C) CRMVILLAGE.BIZ.

global $theme;
require_once('modules/VteCore/layout_utils.php');	//crmv@30447

global $app_strings;
global $mod_strings;

$mod_strings['ERR_ENTER_OLD_PASSWORD'];

insert_popup_header($theme);

$focus = CRMEntity::getInstance('Users');	//crmv@28327

eval(Users::m_de_cryption());
?>
<script language="JavaScript" type="text/javascript" src="include/js/jquery.js"></script><!-- crmv@22566 -->
<script type='text/javascript' src="include/js/general.js"></script>
<script type='text/javascript' src="modules/Morphsuit/MorphsuitCommon.js"></script>
<script type='text/javascript' src="modules/Users/Users.js"></script>
<script type='text/javascript' language='JavaScript'>

function set_password(form) {
	if (form.is_admin.value == 1 && trim(form.old_password.value) == "") {
		alert("<?php echo $mod_strings['ERR_ENTER_OLD_PASSWORD']; ?>");
		return false;
	}
	if (trim(form.new_password.value) == "") {
		alert("<?php echo $mod_strings['ERR_ENTER_NEW_PASSWORD']; ?>");
		return false;
	}
	if (trim(form.confirm_new_password.value) == "") {
		alert("<?php echo $mod_strings['ERR_ENTER_CONFIRMATION_PASSWORD']; ?>");
		return false;
	}
	//crmv@28327
	var res = getFile('index.php?module=Users&action=UsersAjax&file=CheckPasswordCriteria&record='+parent.document.DetailView.record.value+'&password='+encodeURIComponent(form.new_password.value));	//crmv@38918
	if (res == "no") {
		alert('<?php echo sprintf(getTranslatedString('LBL_NOT_SAFETY_PASSWORD','Users'),$focus->password_length_min); ?>');
		return false;
	}
	//crmv@28327e
	if (trim(form.new_password.value) == trim(form.confirm_new_password.value)) {
		<?php eval($hash_version[15]); ?>
		if (form.is_admin.value == 1) parent.document.DetailView.old_password.value = form.old_password.value;
		parent.document.DetailView.new_password.value = form.new_password.value;
		parent.document.DetailView.return_module.value = 'Users';
		parent.document.DetailView.return_action.value = 'DetailView';
		parent.document.DetailView.changepassword.value = 'true';
		parent.document.DetailView.return_id.value = parent.document.DetailView.record.value;
		parent.document.DetailView.action.value = 'Save';
		parent.document.DetailView.submit();
		return true;
	}
	else {
		alert("<?php echo $mod_strings['ERR_REENTER_PASSWORDS']; ?>");
		return false;
	}
}
</script>

<form name="ChangePassword" onsubmit="VteJS_DialogBox.block();">
<?php echo get_form_header($mod_strings['LBL_CHANGE_PASSWORD'], "", false); ?>

<table border="0" cellpadding="5" cellspacing="0" width="100%">
	<tr height="34">
		<td style="padding:5px" class="level3Bg">
			<table cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td width="80%" class="small"><b><?php echo $mod_strings['LBL_CHANGE_PASSWORD']; ?></b></td>
					<td width="20%" align="right">
						<input value='<?php echo $app_strings['LBL_SAVE_BUTTON_LABEL']; ?>' class='crmbutton small save' LANGUAGE=javascript onclick='if (set_password(this.form)) closePopup(); else return false;' type='submit' name='button'>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>

<table width='100%' cellspacing='0' cellpadding='5' border='0' class="small">
<?php if (!is_admin($current_user)) { ?>
	<tr>
		<td width='40%' class='dvtCellLabel' align='right'><?php echo $mod_strings['LBL_OLD_PASSWORD']; ?></td>
		<td width='60%'>
			<div class='dvtCellInfo'>
				<input name='old_password' type='password' tabindex='1' size='15'>
			</div>
		</td>
		<input name='is_admin' type='hidden' value='1'>
	</tr>
<?php }
else
	echo "<input name='old_password' type='hidden'><input name='is_admin' type='hidden' value='0'>";
?>
	<tr>
		<td width='40%' class='dvtCellLabel' nowrap align="right"><?php echo $mod_strings['LBL_NEW_PASSWORD']; ?></td>
		<td width='60%'>
			<div class='dvtCellInfo'>
				<input class="detailedViewTextBox" name='new_password' type='password' tabindex='1' size='15'>
			</div>
		</td>
	</tr>
	<tr>
		<td width='40%' class='dvtCellLabel' nowrap align="right"><?php echo $mod_strings['LBL_CONFIRM_PASSWORD']; ?></td>
		<td width='60%'>
			<div class='dvtCellInfo'>
				<input class="detailedViewTextBox" name='confirm_new_password' type='password' tabindex='1' size='15'>
			</div>
		</td>
	</tr>
</table>
<script language="JavaScript">
<?php if (!is_admin($current_user)) { ?>
	document.ChangePassword.old_password.focus();
<?php } else { ?>
	document.ChangePassword.new_password.focus();
<?php } ?>
//crmv@22566
jQuery(document).ready(function() {
	loadedPopup();
});
//crmv@22566e
</script>
</form>