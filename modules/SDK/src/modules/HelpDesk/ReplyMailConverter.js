/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
//crmv@2043m
function ReplyMailConverter(id,user) {
	var record = '';
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: 'module=Emails&action=EmailsAjax&file=GetReplayMailId&record='+id+'&user='+user,
		success: function(result) {
			record = result;
			if (record == 'helpdesk_from_empty') {
				alert(sprintf(alert_arr.CANNOT_BE_EMPTY, alert_arr.HelpDeskFromMail));
				return false;
			}
			window.open('index.php?module=Emails&action=EmailsAjax&file=EditView&record='+record+'&relation='+id+'&reply_mail_converter=true&reply_mail_converter_record='+id+'&reply_mail_user='+user,'_blank');
		}
	});
}
//crmv@2043me