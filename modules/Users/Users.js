/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

loadFileJs('include/js/Mail.js');
loadFileJs('include/js/Fax.js');
loadFileJs('include/js/Sms.js');

// crmv@161368

window.VTE = window.VTE || {};

VTE.Users = VTE.Users || {
	
	confirmRemoteWipe: function(userid) {
		var me = this;
		
		vteconfirm(alert_arr.LBL_CONFIRM_REMOTE_WIPE, function(yes) {
			if (yes) me.remoteWipe(userid);
		});
	},
	
	remoteWipe: function(userid) {
		var me = this;
		
		jQuery('#status').show();
		jQuery.ajax({
			url: 'index.php?module=Users&action=UsersAjax&file=RemoteWipe&userid='+userid,
			method: 'GET',
			success: function(data) {
				jQuery('#status').hide();
				try {
					data = JSON.parse(data);
				} catch (e) {
					data = null;
				}
				if (data && data.success) {
					vtealert(alert_arr.LBL_REMOTE_WIPE_OK);
				} else if (data && data.error) {
					vtealert(data.error);
				} else {
					vtealert(alert_arr.LBL_UT208_INVALIDSRV);
					console.log('Unknown error');
					console.log(data);
				}
			},
			error: function() {
				jQuery('#status').hide();
			}
		});
	}
	
};

// crmv@161368e

function set_return(user_id, user_name) {
	//crmv@21048m	//crmv@30408
	if(top.jQuery('div#addEventInviteUI').css('display') == 'block'){
		var linkedMod = 'Users';
		var entity_id = user_id;
		var strVal = user_name;
		if (top.jQuery('#addEventInviteUI').contents().find('#' + entity_id + '_' + linkedMod + '_dest').length < 1) {
			strHtlm = '<tr id="' + entity_id + '_' + linkedMod + '_dest' + '" onclick="checkTr(this.id)">' +
			'<td align="center" style="display:none;"><input type="checkbox" value="' + entity_id + '_' + linkedMod + '"></td>' +
			'<td nowrap align="left" class="parent_name" style="width:100%">' + strVal + '</td>' +
			'</tr>';
			top.jQuery('#selectedTable').append(strHtlm);
			jQuery('#parent_id_link_contacts').val(jQuery('#parent_id_link_contacts').val() + ';' + entity_id);
		}
	}
	else{
		//crmv@29190
		var formName = getReturnFormName();
		var form = getReturnForm(formName);
		//crmv@29190e
		//crmv@42247
		if(form.elements["reports_to_id_display"]){
			form.elements["reports_to_id_display"].value = user_name; 
			var name_disabled = form.elements["reports_to_id_display"];
		}
		if(form.elements["reports_to_id"]){
			form.elements["reports_to_id"].value = user_id; 
			var id_disabled = form.elements["reports_to_id"];
		}
		if(form.elements["newresp_display"]){
			form.elements["newresp_display"].value = user_name; 
			var name_disabled = form.elements["newresp_display"];
		}
		if(form.elements["newresp"]){
			form.elements["newresp"].value = user_id; 
			var id_disabled = form.elements["newresp"];
		}
		disableReferenceField(name_disabled,id_disabled);
		//crmv@42247e
	}
	//crmv@21048me	//crmv@30408e
}

//ds@28 workflow
function set_return_specific(user_id, user_name) {
	//crmv@29190
	var formName = getReturnFormName();
	var form = getReturnForm(formName);
	//crmv@29190e
	form.user_name.value = user_name;
	form.user_id.value = user_id;
	disableReferenceField(form.user_name,form.user_id);	//crmv@29190
}
//ds@28e

//crmv@35153
function getUserName(id) {
	return getFile('index.php?module=Users&action=UsersAjax&file=GetUserName&record='+id);
}
//crmv@35153e