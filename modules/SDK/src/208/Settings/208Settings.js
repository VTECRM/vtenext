/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@37679 */

function addEncryptedField() {
	location.href = "index.php?module=Settings&action=EncryptedFields&parenttab=Settings&subaction=add";
	return;
}

function editEncryptedField(fieldid) {
	location.href = "index.php?module=Settings&action=EncryptedFields&parenttab=Settings&subaction=edit&fieldid="+fieldid;
	return;
}

function hideDeleteForm(fieldid) {
	jQuery('#encField_DelForm_div').hide();
}

function showDeleteForm(clicker,fieldid) {
	fnvshobj(clicker, 'encField_DelForm_div');
	jQuery('#encField_DelForm_fieldid').val(fieldid);
}

function deleteEncryptedField() {
	var pwd = jQuery('#encField_DelForm_pwd').val(),
		fieldid = jQuery('#encField_DelForm_fieldid').val();
	if (!pwd) {
		alert(alert_arr.LBL_UT208_PASSWORDEMPTY);
		return;
	}
	if (!fieldid) return;
	jQuery('#encField_DelForm_loader').show();
	jQuery('#encField_DelForm_btn').hide();
	jQuery.ajax({
		url: "index.php?module=Settings&action=SettingsAjax&file=EncryptedFields&subaction=delete",
		type: 'POST',
		data: 'fieldid='+fieldid+'&password='+encodeURIComponent(pwd),
		success: function(data) {
			if (!data) {
				alert(alert_arr.LBL_UT208_INVALIDSRV);
				return;
			}
			try {
				var ret = eval('('+data+')');
			} catch (e) {
				alert(alert_arr.LBL_UT208_INVALIDSRV+': '+data);
				return;
			}
			if (ret.success == true) {
				location.reload();
			} else {
				alert(alert_arr.ERROR+': '+ret.message);
			}
		},
		complete: function() {
			jQuery('#encField_DelForm_loader').hide();
			jQuery('#encField_DelForm_btn').show();
		}
	});
}

function encFieldAdd_changeModule() {
	var selmod = jQuery('#encFieldAdd_selModule').val();
	if (selmod) {
		jQuery('select[id^=encFieldAdd_sel_]').hide();
		jQuery('#encFieldAdd_sel_'+selmod).show();
	}
}

function encFieldAdd_save() {
	var fieldid = jQuery('select[id^=encFieldAdd_sel_]:visible').val(),
		pwd1 = jQuery('#encFieldAdd_pwd1').val(),
		pwd2 = jQuery('#encFieldAdd_pwd2').val();

	if (!fieldid) return;

	if (!pwd1 || !pwd2) {
		alert(alert_arr.LBL_UT208_PASSWORDEMPTY);
		return;
	}
	if (pwd1 != pwd2) {
		alert(alert_arr.LBL_UT208_DIFFPWD);
		return;
	}
	if (pwd1.length < 6) {
		alert(alert_arr.LBL_UT208_PWDCRITERIA);
		return;
	}
	jQuery.ajax({
		url: "index.php?module=Settings&action=SettingsAjax&file=EncryptedFields&subaction=addfield",
		type: 'POST',
		data: 'fieldid='+fieldid+'&password='+encodeURIComponent(pwd1),
		success: function(data) {
			if (!data) {
				alert(alert_arr.LBL_UT208_INVALIDSRV);
				return;
			}
			try {
				var ret = eval('('+data+')');
			} catch (e) {
				alert(alert_arr.LBL_UT208_INVALIDSRV+': '+data);
				return;
			}
			if (ret.success == true) {
				location.href='index.php?module=Settings&action=EncryptedFields&parenttab=Settings'
			} else {
				alert(alert_arr.ERROR+': '+ret.message);
			}
		},
		complete: function() {
			//jQuery('#encField_DelForm_loader').hide();
			//jQuery('#encField_DelForm_btn').show();
		}
	});
}

function encFieldAdd_changeModule() {
	var selmod = jQuery('#encFieldAdd_selModule').val();
	if (selmod) {
		jQuery('select[id^=encFieldAdd_sel_]').hide();
		jQuery('#encFieldAdd_sel_'+selmod).show();
	}
}

function encFieldEdit_pwdtype() {
	var pwd = jQuery('#encFieldEdit_pwd'),
		pval = jQuery(pwd).val(),
		pwd1 = jQuery('#encFieldEdit_pwd1'),
		pwd2 = jQuery('#encFieldEdit_pwd2');

	if (pval != '') {
		pwd1.removeAttr('disabled');
		pwd2.removeAttr('disabled');
	} else {
		pwd1.val('').attr('disabled', 'disabled');
		pwd2.val('').attr('disabled', 'disabled');
	}
}

function encFieldEdit_save() {
	var fieldid = jQuery('#encFieldEdit_fieldid').val(),
		pwd = jQuery('#encFieldEdit_pwd').val(),
		pwd1 = jQuery('#encFieldEdit_pwd1').val(),
		pwd2 = jQuery('#encFieldEdit_pwd2').val(),
		timeout = jQuery('#encFieldEdit_timeout').val(),
		roles = jQuery('#encFieldEdit_roles').val(),
		ips = jQuery('#encFieldEdit_filterip').val();

	if (pwd != '') {
		// changing password
		if (!pwd1 || !pwd2) {
			alert(alert_arr.LBL_UT208_PASSWORDEMPTY);
			return;
		}
		if (pwd1 != pwd2) {
			alert(alert_arr.LBL_UT208_DIFFPWD);
			return;
		}
		if (pwd1.length < 6) {
			alert(alert_arr.LBL_UT208_PWDCRITERIA);
			return;
		}
	}

	if (!roles) roles = '';
	if (!ips) ips = '';

	jQuery.ajax({
		url: "index.php?module=Settings&action=SettingsAjax&file=EncryptedFields&subaction=editfield",
		type: 'POST',
		data: 'fieldid='+fieldid+'&password='+encodeURIComponent(pwd)+'&newpassword='+encodeURIComponent(pwd1)+
			'&roles='+roles+'&timeout='+timeout+'&ips='+ips,
		success: function(data) {
			if (!data) {
				alert(alert_arr.LBL_UT208_INVALIDSRV);
				return;
			}
			try {
				var ret = eval('('+data+')');
			} catch (e) {
				alert(alert_arr.LBL_UT208_INVALIDSRV+': '+data);
				return;
			}
			if (ret.success == true) {
				location.href='index.php?module=Settings&action=EncryptedFields&parenttab=Settings'
			} else {
				alert(alert_arr.ERROR+': '+ret.message);
			}
		},
		complete: function() {
			//jQuery('#encField_DelForm_loader').hide();
			//jQuery('#encField_DelForm_btn').show();
		}
	});


}