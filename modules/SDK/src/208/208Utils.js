/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@37679 */

function uitype208Keyup(event, crmid, fieldid) {
	var kc = event.keyCode;
	if (kc == 13) uitype208ShowField(crmid, fieldid);
}

// crmv@81167
function uitype208Escape(string) {
	string = jQuery('<div/>').text(string).html();
	return string.replace(/\n/g, "<br>");
}
// crmv@81167e

function uitype208ShowField(crmid, fieldid) {
	var pwd = jQuery('#uitype208_pwd_'+fieldid).val(),
		pwdcont = jQuery('#uitype208_pwdcont_'+fieldid),
		loader = jQuery('#uitype208_loader_'+fieldid);

	if (!pwd) {
		alert(alert_arr.LBL_UT208_PASSWORDEMPTY);
		return false;
	}

	pwdcont.hide();
	loader.show();
	jQuery.ajax({
		url: 'index.php?module=SDK&action=SDKAjax&file=src/208/208Ajax',
		type: 'POST',
		data: 'subaction=decrypt&crmid='+crmid+'&fieldid='+fieldid+'&password='+encodeURIComponent(pwd),
		success: function(data) {
			if (!data) {
				alert(alert_arr.LBL_UT208_INVALIDSRV);
				pwdcont.show();
				return;
			}
			try {
				var response = eval('('+data+')');
			} catch (err) {
				alert(alert_arr.LBL_UT208_INVALIDSRV+': '+data);
				pwdcont.show();
				return;
			}
			if (!response.success) {
				alert(alert_arr.LBL_UT208_WRONGPWD);
				pwdcont.show();
				return;
			}
			// good data
			var cont = jQuery('#uitype208_td_'+fieldid+' div[name^=uitype208_cont_]'),
				contedit = jQuery(cont).find('.detailedViewTextBox[id^=txtbox]');
			jQuery(cont).find('span').html(uitype208Escape(response.value)); // crmv@81167
			jQuery(cont).find('#uitype208_input_'+fieldid).val(response.value);
			if (contedit.length > 0) contedit.val(response.value);
			pwdcont.hide();
			cont.show();
		},
		complete: function() {
			loader.hide();
		}
	});

	return true;
}

function uitype208EditField(crmid, fieldid) {
	return uitype208ShowField(crmid, fieldid);
}