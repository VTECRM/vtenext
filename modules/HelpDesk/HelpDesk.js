/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

loadFileJs('include/js/Merge.js');

// crmv@160733

window.VTE = window.VTE || {};

VTE.HelpDesk = VTE.HelpDesk || {
	// add here specific functions for tickets
};

VTE.HelpDesk.ConfidentialInfo = VTE.HelpDesk.ConfidentialInfo || {
	
	ajaxRequest: function(module, crmid, subaction, params, callback) {
		var ajaxaction = (subaction == 'see' || subaction == 'getrequestcomment' ? 'CONFIDENTIALINFO' : 'DETAILVIEW');
		var url = 'index.php?module='+module+'&action='+module+'Ajax&recordid='+crmid+'&file=DetailViewAjax&ajxaction='+ajaxaction+'&ciaction='+subaction;
		jQuery.ajax({
			url: url,
			method: 'POST',
			data: params || {},
			success: function(result) {
				if (typeof callback == 'function') callback(result);
			}
		});
	},
	
	onChangeCheckbox: function(module, crmid, self) {
		var checked = jQuery(self).is(':checked');
		
		if (checked) {
			this.askPassword(module, crmid, 'comments');
		} else {
			// remove the saved pwd
			jQuery('#confinfo_save_pwd').val('');
			jQuery('#confinfo_edit_icon').hide();
		}
	},
	
	onEditPassword: function(module, crmid) {
		this.editPassword(module, crmid, 'comments');
	},
	
	editPassword: function(module, crmid, fieldname) {
		var pwd = jQuery('#confinfo_save_pwd').val();
		var more = jQuery('#confinfo_save_more').val();
		var comment = jQuery('textarea[name=comments]').val();
		
		// populate fields
		jQuery('#confinfo_pwd1').val(pwd);
		jQuery('#confinfo_pwd2').val(pwd);
		jQuery('#confinfo_more').val(more);
		jQuery('#confinfo_comment').val(comment);
		
		showFloatingDiv('reqConfInfo');
	},
	
	askPassword: function(module, crmid, fieldname) {
		var comment = jQuery('textarea[name='+fieldname+']').val();
		
		// reset fields
		jQuery('#confinfo_pwd1').val('');
		jQuery('#confinfo_pwd2').val('');
		jQuery('#confinfo_more').val('');
		jQuery('#confinfo_comment').val(comment);
		
		showFloatingDiv('reqConfInfo');
		jQuery('#confinfo_pwd1').focus();
	},
	
	cancelAskPassword: function() {
		var pwd = jQuery('#confinfo_save_pwd').val();
		if (!pwd) {
			// remove only if in create mode
			jQuery('#confinfo_save_pwd').val('');
			jQuery('#confinfo_save_more').val('');
			jQuery('#confinfo_check').attr('checked', false);
		}
		hideFloatingDiv('reqConfInfo');
	},
	
	validatePassword: function() {
		var pwd1 = jQuery('#confinfo_pwd1').val();
		var pwd2 = jQuery('#confinfo_pwd2').val();
		
		if (pwd1 === '') {
			vtealert(alert_arr.LBL_UT208_PASSWORDEMPTY);
			return false;
		} else if (pwd1 !== pwd2) {
			vtealert(alert_arr.LBL_UT208_DIFFPWD);
			return false;
		}
		return true;
	},
	
	savePassword: function() {
		var pwd1 = jQuery('#confinfo_pwd1').val();
		var more = jQuery('#confinfo_more').val();
		var comment = jQuery('#confinfo_comment').val();
		
		if (!this.validatePassword()) {
			return false;
		}
		
		jQuery('#confinfo_save_pwd').val(pwd1);
		jQuery('#confinfo_save_more').val(more);
		hideFloatingDiv('reqConfInfo');
		
		jQuery('#confinfo_edit_icon').show();
		jQuery('textarea[name=comments]').val(comment);
	},
	
	requestInfo: function(module, crmid, fieldlabel) {
		var me = this;
		
		if (!this.validatePassword()) {
			return false;
		}
		
		var params = {
			fldName: 'comments',
			fieldValue: jQuery('#confinfo_comment').val(),
			pwd: jQuery('#confinfo_pwd1').val(),
			data: jQuery('#confinfo_more').val()
		}
		jQuery('#status').show();
		me.ajaxRequest(module, crmid, 'request', params, function(result) {
			jQuery('#status').hide();
			if (result.indexOf(":#:SUCCESS") > -1) {
				// clean the ajax form
				var dtlView = "dtlview_"+ fieldlabel;
				getObj(dtlView).innerHTML = "";
				getObj("comments").value = "";
				getObj("comments_div").innerHTML = result.replace(":#:SUCCESS","");
				// hide the ajax form
				hndCancel('dtlview_'+fieldlabel, 'editarea_'+fieldlabel, fieldlabel, false);
				// remove the check
				jQuery('#confinfo_check').attr('checked', false);
				hideFloatingDiv('reqConfInfo');
			}
		});
	},
	
	askData: function(module, crmid, commentid, status) {
		var me = this;
		
		if (status != 1) {
			vtealert(alert_arr.LBL_CONFIDENTIAL_INFO_ALREADY_PROVIDED);
			return false;
		}
		
		if (document.EditView) {
			vtealert(alert_arr.LBL_OPERATION_NOT_SUPPORTED_EDITVIEW);
			return;
		}
		
		jQuery('#status').show();
		// ask for the additional comment
		me.ajaxRequest(module, crmid, 'getrequestcomment', {
			commentid: commentid
		}, function(result) {
			jQuery('#status').hide();
			if (result.indexOf(":#:FAILURE") > -1) {
				vtealert(alert_arr.ERROR_WHILE_EDITING);
			} else if (result.indexOf(":#:SUCCESS") > -1) {
				var more = result.replace(":#:SUCCESS","");
				var moretr = jQuery('#confinfo_data_more').closest('tr');
				if (more) {
					moretr.show();
					moretr.prev().show();
				} else {
					moretr.hide();
					moretr.prev().hide();
				}
				jQuery('#confinfo_data_more').val(more);
				jQuery('#confinfo_data').val('');
				jQuery('#confinfo_data_comment').val('');
				jQuery('#confinfo_commentid').val(commentid);
				showFloatingDiv('provideConfInfo');
			}
		});
			
	},
	
	provideInfo: function(module, crmid, fieldname, fieldlabel) {
		var me = this;
		var commentid = jQuery('#confinfo_commentid').val();
		var data = jQuery('#confinfo_data').val().trim();
		var comment = jQuery('#confinfo_data_comment').val().trim() || '@DELETEME@';
		
		if (data === '') {
			vtealert(alert_arr.LBL_INVALID_VALUE);
			return false;
		}
		
		var params = {
			fldName: fieldname,
			fieldValue: comment,
			data: data,
			request_commentid: commentid
		}
		me.ajaxRequest(module, crmid, 'provide', params, function(result) {
			if (result.indexOf(":#:FAILURE") > -1) {
				vtealert(alert_arr.ERROR_WHILE_EDITING);
			} else if (result.indexOf(":#:SUCCESS") > -1) {
				var dtlView = "dtlview_"+ fieldlabel;
				getObj(dtlView).innerHTML = "";
				getObj("comments").value = "";
				getObj("comments_div").innerHTML = result.replace(":#:SUCCESS","");
			}
			hideFloatingDiv('provideConfInfo');
		});
	},
	
	seeData: function(module, crmid, commentid, status) {
		if (status != 3) {
			return false;
		}
		jQuery('#confinfo_pwd').val('');
		jQuery("#confinfo_see_data").val('');
		jQuery('#confinfo_see_commentid').val(commentid);
		showFloatingDiv('showConfInfo');
		jQuery('#confinfo_pwd').focus();
	},
	
	loadData: function(module, crmid) {
		var me = this;
		var pwd = jQuery('#confinfo_pwd').val();
		var commentid = jQuery('#confinfo_see_commentid').val();
		
		if (pwd === '') {
			vtealert(alert_arr.LBL_UT208_PASSWORDEMPTY);
			return false;
		}
		
		var params = {
			pwd: pwd,
			commentid: commentid,
		}
		jQuery('#status').show();
		me.ajaxRequest(module, crmid, 'see', params, function(result) {
			jQuery('#status').hide();
			if (result.indexOf(":#:FAILURE") > -1) {
				vtealert(alert_arr.LBL_UT208_WRONGPWD);
			} else if (result.indexOf(":#:SUCCESS") > -1) {
				jQuery("#confinfo_see_data").val(result.replace(":#:SUCCESS",""));
				// and hide the pwd again
				jQuery('#confinfo_pwd').blur().val('');
			}
		});
	},
	
	onPwdKeyup: function(event, module, crmid) {
		if (event.keyCode == 13) this.loadData(module, crmid);
	},
	
}
// crmv@160733e

function verify_data(form) {
	if(! form.createpotential.checked == true)
	{
		if (form.potential_name.value == "")
		{
			alert(alert_arr.OPPORTUNITYNAME_CANNOT_BE_EMPTY);
			return false;	
		}
		if (form.closedate.value == "")
		{
			alert(alert_arr.CLOSEDATE_CANNOT_BE_EMPTY);
			return false;	
		}
		return dateValidate('closedate','Potential Close Date','GECD');
	}
	return true;
}

function togglePotFields(form)
{
	if (form.createpotential.checked == true)
	{
		form.potential_name.disabled = true;
		form.closedate.disabled = true;
		
	}
	else
	{
		form.potential_name.disabled = false;
		form.closedate.disabled = false;
	}	

}

function toggleAssignType(currType)
{
	if (currType=="U")
	{
		getObj("assign_user").style.display="block"
		getObj("assign_team").style.display="none"
	}
	else
	{
		getObj("assign_user").style.display="none"
		getObj("assign_team").style.display="block"
	}
}

function set_return(product_id, product_name) {
	//crmv@29190
	var formName = getReturnFormName();
	var form = getReturnForm(formName);
	//crmv@29190e
	form.parent_name.value = product_name;
	form.parent_id.value = product_id;
	disableReferenceField(form.parent_name,form.parent_id,form.parent_id_mass_edit_check);	//crmv@29190
}

//crmv@56233
function doNotImportAnymore(module,record,view) {
	VteJS_DialogBox.progress();
	var mode = 'spam';
	if (view == 'MassListView') {
		mode = 'mass_spam';
		get_real_selected_ids(module);
	}
	jQuery.ajax({
		url: 'index.php?module=HelpDesk&action=HelpDeskAjax&file=DoNotImportAnymore&mode='+mode+'&record='+record,
		type: 'POST',
		success: function(data) {
			if (data.indexOf("ERROR::") > -1) {
				var str = data.split("ERROR::");
				alert(str[1]);
			} else {
				if (view == 'ListView' || view == 'MassListView') window.location.reload();
				else if (view == 'DetailView') window.location.href='index.php?module=HelpDesk&action=ListView';
			}
			VteJS_DialogBox.hideprogress();
		}
	});
}
//crmv@56233e