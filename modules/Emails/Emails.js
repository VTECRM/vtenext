/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@25562 crmv@2963m crmv@82831 */

function blockComposePage() {
	VteJS_DialogBox.progress();
	// TODO: find out why this is not working!!
	//jQuery.fancybox.showActivity();
	jQuery("#fancybox-loading").css('zIndex', findZMax()+1);
	blockButtons();
}

function blockButtons() {
	jQuery("input[type=button]").attr("disabled", "disabled");
	jQuery("input[type=button]").addClass("disabled");
	jQuery("input[type=submit]").attr("disabled", "disabled");
	jQuery("input[type=submit]").addClass("disabled");
}

function releaseComposePage() {
	VteJS_DialogBox.hideprogress();
	loadedPopup();
	releaseButtons();
}

function releaseButtons() {
	jQuery("input[type=button]").removeAttr("disabled");
	jQuery("input[type=button]").removeClass("disabled");
	jQuery("input[type=submit]").removeAttr("disabled");
	jQuery("input[type=submit]").removeClass("disabled");
}

//function to extract the mailaddress inside < > symbols.......for the bug fix #3752
function findAngleBracket(mailadd) {
	var strlen = mailadd.length;
	var success = 0;
	var gt = 0;
	var lt = 0;
	var ret = '';
	for(i=0;i<strlen;i++) {
		if(mailadd.charAt(i) == '<' && gt == 0) {
			lt = 1;
		}
		if(mailadd.charAt(i) == '>' && lt == 1){
			gt = 1;
		}
		if(mailadd.charAt(i) != '<' && lt == 1 && gt == 0) {
			ret = ret + mailadd.charAt(i);
		}
	}
	var email_regex = /^[a-zA-Z0-9]+([\_\-\.]*[a-zA-Z0-9]+[\_\-]?)*@[a-zA-Z0-9]+([\_\-]?[a-zA-Z0-9]+)*\.+([\_\-]?[a-zA-Z0-9])+(\.?[a-zA-Z0-9]+)*$/;
	if(email_regex.test(ret)) {
		return true;
	} else {
		return false;
	}
}

// crmv@192033
function delAttachments(id) {
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: 'module=Contacts&action=ContactsAjax&file=DelImage&attachmodule=Emails&recordid='+id,
		success: function(result) {
			jQuery('#row_'+id).fadeOut(); // crmv@168103
		}
	});
}
// crmv@192033e

function email_validate(oform,mode) {
	
	//crmv@91082 crmv@167238
	if(!SessionValidator.check()) {
		SessionValidator.showLogin();
		return false;
	}
	//crmv@91082e crmv@167238e
	
	if(trim(mode) == '') {
		return false;
	}
	if (mode == 'save' || mode == 'auto_save') {
		if (saving_draft == true) {
			return false;
		}
		saving_draft = true;
		oform.send_mail.value = '';
	} else {
		if (VTE.ModCommentsCommon.checkComment('composeEmail','ModCommentsDetailViewBlockCommentWidget') != true) {
			return false;
		}
		oform.send_mail.value = 'true';
	}
	
	var empty_rcpt = false;
	var empty_cc = false;
	var empty_bcc = false;
	var empty_subject = false;
	var empty_body = false;

	// controlla destinatario
	var dests = jQuery('#parent_id').val();
	var dests1 = jQuery('#to_mail').val();
	if (dests != undefined && (dests == '' || dests == ',' || dests == '|') && dests1 != undefined && (dests1 == '' || dests1 == ',' || dests1 == '|')) {	// crmv@138829
		if (mode == 'save' || mode == 'auto_save') {
			empty_rcpt = true;
		} else {
			alert(no_rcpts_err_msg);
			return false;
		}
	}
	// altri destinatari
	var ccraw = jQuery('#cc_name').val();
	if (ccraw != undefined && ccraw == '') empty_cc = true;
	ccraw = jQuery('#bcc_name').val();
	if (ccraw != undefined && ccraw == '') empty_bcc = true;
	
	var rawbody = CKEDITOR.instances.description.getData();
	if (rawbody != undefined && rawbody == '') empty_body = true;
	jQuery("#description").val(rawbody);	//crmv@104438 crmv@107809

	// removed the emails validation
	
	if(oform.subject.value.replace(/^\s+/g, '').replace(/\s+$/g, '').length==0)	{
		if (mode == 'save' || mode == 'auto_save') {
			empty_subject = true;
		} else {
			if(email_sub = prompt(no_subject,no_subject_label)) { //crmv@7216
				oform.subject.value = email_sub;
			} else {
				return false;
			}
		}
	}
	if (mode != 'save' && mode != 'auto_save') {
		sdkValidate = SDKValidate();
		if (sdkValidate) {
			sdkValidateResponse = eval('('+sdkValidate.responseText+')');
			if (!sdkValidateResponse['status']) {
				return false;
			}
		}
	}

	var all_empty = (empty_rcpt && empty_cc && empty_bcc && empty_subject && empty_body);

	if(mode == 'send') {
		check_cron_messages_send(); //crmv@62821
		return server_check();
	} else if(mode == 'save' || mode == 'auto_save') {
		//crmv@167238
		if (all_empty) {
			saving_draft = false;
			return false;
		}
		if (mode == 'save' && !draft_folder_check()) {
			saving_draft = false;
			return false;
		}
		//crmv@167238e
		
		//durante il salvataggio automatico blocco i pulsanti Salva Bozza e Invia e mostro un messaggio di salvataggio automatico in corso...
		blockButtons()

		if (mode == 'save') blockComposePage();
		jQuery('#composeEmailDraftUpdate').html(alert_arr.LBL_SAVING_DRAFT);

		var inputs = jQuery(oform).serializeArray();
		var params = '';
		jQuery.each(inputs, function(i, field) {
			if (field.name == 'description')
				params += '&'+field.name+'='+encodeURIComponent(rawbody);
			else if (field.name == 'mode')
				params += '&mode=';
			else
	    		params += '&'+field.name+'='+encodeURIComponent(field.value);
		});
		
		jQuery.ajax({
			url: 'index.php?save_in_draft='+mode,
			type: 'POST',
			data: params,
			//async: (mode == 'auto_save'),
			success: function(data){
				var tmp = data.split('|##|');
				//var message_id = tmp[1];
				var messagesid = tmp[0];
				if (document.EditView.message != undefined)
					document.EditView.message.value = messagesid;
				document.EditView.record.value = '';
				document.EditView.mode.value = '';
				jQuery('#composeEmailDraftUpdate').html(tmp[2]);
				if (mode == 'save') releaseComposePage();
				//ripristino i pulsanti al termine del salvataggio bozza
				releaseButtons();
				saving_draft = false;
			}
		});
	} else {
		return false;
	}
}

//crmv@62821
function check_cron_messages_send() {
	var response = getFile('index.php?module=Emails&action=EmailsAjax&file=Save&ajax=true&cron_messagessend_check=true');
	if (response.indexOf('SUCCESS') > -1) {
		return true;
	} else {
		getObj('add2queue').value='false';
		return false;
	}
}
//crmv@62821 e

// crmv@114260
function server_check(accountid) {
	if (!accountid) {
		// get the accountid from the page
		accountid = jQuery('#from_email').find('option:selected').data('accountid');
	}
	var response = getFile('index.php?module=Emails&action=EmailsAjax&file=Save&ajax=true&server_check=true&accountid='+accountid);
	if (response.indexOf('SUCCESS') > -1) {
		return true;
	} else {
		alert(conf_mail_srvr_err_msg);
		return false;
	}
}
// crmv@114260e

//crmv@167238
function draft_folder_check(accountid) {
	if (!accountid) {
		// get the accountid from the page
		accountid = jQuery('#from_email').find('option:selected').data('accountid');
	}
	var response = getFile('index.php?module=Emails&action=EmailsAjax&file=Save&ajax=true&draft_folder_check=true&accountid='+accountid);
	if (response.indexOf('SUCCESS') > -1) {
		return true;
	} else if (response.indexOf('FAILURE') > -1) {
		var res = response.split('|##|');
		alert(res[1]);
		return false;
	} else {
		alert(alert_arr.ERROR);
		return false;
	}
}
//crmv@167238e

function beforeSendEmail(jqForm, options) {	//crmv@104438
	blockComposePage();
	saving_draft = true;
	var result = email_validate(document.EditView,'send');
	if (result) {
		checkBrowserAlive(15000);	//crmv@52920
		return true;
	} else {
		saving_draft = false;
		releaseComposePage();
		return false;
	}
}

//crmv@52920
function checkBrowserAlive(seconds) {
	setTimeout( function(){
		if (jQuery('#__vtejs_dialogbox_olayer__').length > 0) {
			errorSendEmail();
		}
	}, seconds);
}
//crmv@52920e

function successSendEmail(responseText, statusText, xhr, $form)  {
	responseText = responseText.replace('<head></head><body>','');
	responseText = responseText.replace('</body>','');
	if (responseText.indexOf('SUCCESS') > -1) {
		var res = responseText.split('[#]');
		res = jQuery.parseJSON(res[2]);
		if (res['error'] != '') {
			releaseComposePage();
			alert(alert_arr.SEND_MAIL_ERROR+"\n\n"+res['error']); // crmv@201913
			return false;
		}
		// crmv@187622
		if (res['alert'] != '' && res['alert'] != null) {
			alertSendEmail(res['alert'],5);
		}
		if (typeof(res['reload_folders']) != 'undefined' && res['reload_folders'] == 'yes' && window.opener) {
			window.opener.reloadFolders();
		}
		// crmv@187622e
		//crmv@41930
		if (window.opener && window.opener.specialFolders != undefined && window.opener.current_folder == window.opener.specialFolders['Sent']) {
			window.opener.getListViewEntries_js('Messages','start=1&account='+window.opener.current_account+'&folder='+window.opener.current_folder);
		}
		//crmv@41930e
		
		Blockage.releaseBlock(); // crmv@140887
		
		top.window.close(); // crmv@187622
		/*
		alert('OK');
		releaseComposePage();
		*/
		return true;
	}
	errorSendEmail();
}

function errorSendEmail(res, err, e) {
	// TODO: fix this horrible workaround!
	if (res && res.responseText && res.responseText.match(/SUCCESS/)) return successSendEmail(res.responseText, res.statusText, res);
	alert(alert_arr.SEND_MAIL_ERROR);	//crmv@47673
	releaseComposePage();
	jQuery('#scheduled_date').val(''); // crmv@187622
}

//crmv@159178
function alertSendEmail(msg,autoclose) {
	if (checkJSOverride(arguments)) return callJSOverride(arguments);
	if (checkJSExtension(arguments)) if (!callJSExtension(arguments)) return false;
	
	if (window.opener) {
		if (window.opener.vtealert) window.opener.vtealert(msg,null,{"autoclose":autoclose});
		else if (window.opener.parent.vtealert) window.opener.parent.vtealert(msg,null,{"autoclose":autoclose});
	}		
}
//crmv@159178e

function removeAddress(type,id) {
	if (type == 'to') {
		var parent_id = getObj('to_'+id+'_parent_id').innerHTML;
		var parent_name = getObj('to_'+id+'_parent_name').innerHTML+' <'+getObj('to_'+id+'_hidden_toid').innerHTML+'>';
		var hidden_toid = getObj('to_'+id+'_hidden_toid').innerHTML;

		var tmp1 = getObj('parent_id').value;
		tmp1 = tmp1.replace(parent_id,'');
		getObj('parent_id').value = tmp1;

		var tmp2 = getObj('parent_name').value;
		tmp2 = tmp2.replace(parent_name,'');
		if (getObj('parent_name').value != tmp2) {
			getObj('parent_name').value = tmp2;
		} else {
			var parent_name_1 = getObj('to_'+id+'_parent_name').innerHTML+'<'+getObj('to_'+id+'_hidden_toid').innerHTML+'>';
			tmp2 = getObj('parent_name').value;
			tmp2 = tmp2.replace(parent_name_1,'');
			getObj('parent_name').value = tmp2;
		}

		var tmp3 = getObj('hidden_toid').value;
		tmp3 = tmp3.replace(hidden_toid,'');
		getObj('hidden_toid').value = tmp3;

		var d = document.getElementById('autosuggest_to');
		var olddiv = document.getElementById('to_'+id);
		d.removeChild(olddiv);
	}
}

function incDest(availDest,selDest,buttonValue) {
	var trId = '';
	jQuery('#' + availDest).find('tr').find('input:checked').each(function(){
		trId = jQuery(this).val();
		if (jQuery('#' + selDest).contents().find('#' + trId + '_--_' + selDest).length < 1) {
			jQuery('#' + trId).clone().attr('id',trId + '_--_' + selDest).appendTo('#' + selDest);
		}
	});
	jQuery('#selectedDest').find('input:checked').each(function(){
		jQuery(this).removeAttr('checked');
		jQuery(this).parent().parent().css('background-color','')
	});
}

function rmvDest(selDest) {
	jQuery('#' + selDest).find('input:checked').each(function(){
		trId = jQuery(this).parent().parent().attr('id');
		jQuery('#' + selDest).find('#' + trId).remove();
	});
}

function checkTr(objId) {
	if (jQuery('#' + objId).find('input:checkbox').prop('checked') == true) {
		jQuery('#' + objId).css('background-color','');
	    jQuery('#' + objId).find('input:checkbox').prop('checked', false);
	}
	else {
	    jQuery('#' + objId).css('background-color','#C8DEFB');
	    jQuery('#' + objId).find('input:checkbox').prop('checked', true);
	}
}

function popupDestReady() {
	jQuery.expr[":"].containsNoCase = function(el, i, m) {
		var search = m[3];
		if (!search) return false;
		return eval("/" + search + "/i").test(jQuery(el).text()); // crmv@80716
	};
		  
	jQuery('#imgSearch').click(function() {
	    resetSearch();
	});
	 
	jQuery('#txtSearch').keyup(function() {
		searchFunction();
	});
	
	jQuery("#parent_type").change(function() {
		if (jQuery(this).val() != 'all') {
			jQuery('#availableTable tr').hide();
			var this_text = jQuery("#parent_type option:selected").text();
			jQuery('#availableTable tr td:containsNoCase(\'' + this_text + '\')').parent().show();
		}
		else {
			jQuery('#availableTable tr').show();
		}
		jQuery('#availableTable').find('input:checked').each(function() {
			jQuery(this).removeAttr('checked');
			jQuery(this).parent().parent().css('background-color','')
		});
	});
	
	jQuery.each(['availableDest','selected1','selected2','selected3'], function(index, value) {
		jQuery('#'+value).slimScroll({
			height: jQuery('#'+value).height() + 'px',
			wheelStep: 10
		});
	});
	
	loadedPopup();
}

function searchFunction() {
	if (jQuery('#txtSearch').val().length > 2) {
		jQuery('#txtSearch').addClass('ui-autocomplete-loading');
		autocompleteCall();
    }
    else if (jQuery('#txtSearch').val().length == 0) {
        resetSearch();
    }
 
    if (jQuery('#availableTable tr:visible').length == 0) {
        jQuery('.norecords').remove();
        jQuery('#availableTable').html();
    }
}

function resetSearch() {
	jQuery('#txtSearch').val('');
    jQuery('#availableTable tr').remove();
    jQuery('#txtSearch').focus();
    jQuery("#parent_type").val('all');
}

function autocompleteCall() {
	var term = '&term=' + jQuery('#txtSearch').val();
	var urlAutocomplete = 'index.php?module=Emails&action=EmailsAjax&file=Autocomplete' + term;
	
	jQuery.getJSON(urlAutocomplete, function(data) {
		var items = [];
		var dataStr = '';
		jQuery.each(data, function(key, val) {
			dataStr += '<tr id="' + val.id + '" class="' + val.moduleName + '" onclick="checkTr(this.id)">' +
							'<td align="center" style="display:none;"><input type="checkbox" value="' + val.id + '" onclick=""></td>' +
							'<td align="left" class="parent_name" style="width:35%">' + val.parent_name + '</td>' +
							'<td align="left" class="hidden_toid" style="width:45%">' + val.hidden_toid + '</td>' +
							'<td align="left" style="width:20%">' + val.module + '</td>' +
						'</tr>';
		});
		jQuery('#availableTable').html(dataStr);
		jQuery("#parent_type").change();
		jQuery('#txtSearch').removeClass('ui-autocomplete-loading');
	});
}

function CheckAllMails(tabName,checked) {
	jQuery('#' + tabName).find('input[type="checkbox"]').prop('checked', !checked);
	jQuery('#' + tabName).find('input[type="checkbox"]').each(function(index) {
		checkTr(jQuery(this).val());
	});
}

function addInvitee() {
	checkAndSend('selectedDestTabTo');
	checkAndSend('selectedDestTabCc');
	checkAndSend('selectedDestTabBcc');
	closePopup();
}

function checkAndSend(selectedDestTabXx) {
	var modulesSelectable = new Array('Contacts','Accounts','Vendors','Leads','Users');
	var selected_array = new Array();
	var selected_ids = '';
	var addPrev = '';
	jQuery.each(modulesSelectable, function (key, val) {
		if (selectedDestTabXx == 'selectedDestTabTo') {
			if (jQuery('#' + selectedDestTabXx + ' tr.' + val).length > 0) {
				selected_array = [];
				jQuery('#' + selectedDestTabXx + ' tr.' + val).each(function() {
					item_id = ((jQuery(this).attr('id').split('_--_'))[0]).replace('_','@');
					item_value = jQuery(this).find('.parent_name').html();
					item_parent_id = item_id;
					item_parent_name = jQuery(this).find('.parent_name').html();
					item_hidden_toid = jQuery(this).find('.hidden_toid').html();
					
					selected_array.push(new Array(item_id,item_value,item_parent_id,item_parent_name,item_hidden_toid));
				});
		    	addMailTo(selected_array);
			}
		} else {
			if (jQuery('#' + selectedDestTabXx + ' tr.' + val).length > 0) {
				selected_ids = '';
				addPrev = '';
				jQuery('#' + selectedDestTabXx + ' tr.' + val).each(function() {
					selected_ids += jQuery(this).find('.hidden_toid').html() + ', ';
				});
				if (selectedDestTabXx == 'selectedDestTabCc') {
					var destInputId = 'cc_name';
				}
				else if (selectedDestTabXx == 'selectedDestTabBcc') {
					var destInputId = 'bcc_name';
				}
				if (parent.jQuery('#' + destInputId).val() != '' && parent.jQuery('#' + destInputId).val() != undefined) {
					addPrev = parent.jQuery('#' + destInputId).val() + ', ';
				}
				//crmv@32091
				var val = addPrev + selected_ids;
				var arr = val.split( /,\s*/ );
				arr = cleanArray(arr);
				arr.push( "" );
				parent.jQuery('#' + destInputId).val(arr.join( ", " ));
				//crmv@32091e
			}
		}
	});
}

function addMailTo(selected_array) {
	var i = 0;
	jQuery.each(selected_array, function (key, val) {
		i = 0;
		item_id = val[i++];
		item_value = val[i++];
		item_parent_id = val[i++];
		item_parent_name = val[i++];
		item_hidden_toid = val[i++];
		
		// add the selected item
		var span = '<span id="to_'+item_id+'" class="addrBubble">'+item_value
				+'<div id="to_'+item_id+'_parent_id" style="display:none;">'+item_parent_id+'</div>'
				+'<div id="to_'+item_id+'_parent_name" style="display:none;">'+item_parent_name+'</div>'
				+'<div id="to_'+item_id+'_hidden_toid" style="display:none;">'+item_hidden_toid+'</div>'
				+'<div id="to_'+item_id+'_remove" class="ImgBubbleDelete" onClick="removeAddress(\'to\',\''+item_id+'\');"><i class="vteicon small">clear</i></div>'
				+'</span>';
		parent.jQuery("#autosuggest_to").prepend(span);
		
		parent.document.EditView.parent_id.value = parent.document.EditView.parent_id.value + item_parent_id + '|';
		parent.document.EditView.parent_name.value = parent.document.EditView.parent_name.value + item_parent_name+' <' + item_hidden_toid + '>,';
		parent.document.EditView.hidden_toid.value = item_hidden_toid + ',' + parent.document.EditView.hidden_toid.value;
	});
}

//crmv@44037 crmv@48228 crmv@80155
function changeSignature(id,account) {
	if (typeof(id) == 'undefined') var id = jQuery('#signature_id').val();
	if (typeof(account) == 'undefined') var account = jQuery('#from_email').val();
	
	if (jQuery('#use_signature').val() == 0) {
		jQuery('#signature_box').html('');
		setSignature();
	} else {
		jQuery.ajax({
			url: 'index.php?module=Emails&action=EmailsAjax&file=GetAccountSignature&account='+account,
			success: function(data){
				jQuery('#signature_box').html(data);
				setSignature(id);
			}
		});
	}
}

function setSignature(id) {
	if (typeof(id) == 'undefined') var id = jQuery('#signature_id').val();
	
	var ckeditor = CKEDITOR.instances.description;
	var html = ckeditor.getData();
	var div = document.createElement('div');
	div.innerHTML = html;
	jQuery(div).find('div#signature'+id).html(jQuery('#signature_box').html());
	ckeditor.setData(jQuery(div).html());
}
//crmv@44037e crmv@48228e crmv@80155e

// crmv@187622
var ScheduleSending = {
	showOptions: function(validate,messagesid) {
		var me = this;
		if (typeof(messagesid) == 'undefined') var messagesid = '';
		
		var _showOptions = function(){
			if (validate) var check = email_validate(document.EditView,'send');
			else var check = true;
			if (check) {
				jQuery.ajax({
					url: 'index.php?module=Emails&action=EmailsAjax&file=ScheduleSendingAjax&ajxaction=GETOPTIONS&messagesid='+messagesid,
					success: function(data){
						jQuery('#confirm-dialog .modal-footer').addClass('hidden');
						vteconfirm(data, function(res){
							if (res) {
								if (patternValidate('jscal_field_schedule_date','Date',"DATE") == false) return false;
								if (patternValidate('jscal_field_schedule_hour','Hour',"TIME") == false) return false;
								
								me.schedule(jQuery('[name="schedule_date"]').val()+' '+jQuery('[name="schedule_hour"]').val(), messagesid);
							} else {
								if (messagesid != '') me.enable(messagesid);
							}
						}, {html:true});
						if (messagesid != '') me.showCustomOptions();
					}
				});
			}
		}
		
		if (messagesid != '') {
			me.disable(messagesid, function(){
				_showOptions();				
			});
		} else {
			_showOptions();
		}
	},
	showCustomOptions: function() {
		jQuery('#scheduleSendingCustomOpt').show();
		jQuery('#confirm-dialog .modal-footer').removeClass('hidden');
	},
	enable: function(messagesid, callback) {
		// restore status to 0
		jQuery.ajax({
			url: 'index.php?module=Emails&action=EmailsAjax&file=ScheduleSendingAjax&ajxaction=ENABLE&messagesid='+messagesid,
			success: function(data){
				if (typeof callback == 'function') callback(data);
			}
		});
	},
	disable: function(messagesid, callback) {
		// set status to 3
		jQuery.ajax({
			url: 'index.php?module=Emails&action=EmailsAjax&file=ScheduleSendingAjax&ajxaction=DISABLE&messagesid='+messagesid,
			success: function(data){
				if (typeof callback == 'function') callback(data);
			}
		});
	},
	schedule: function(date, messagesid) {
		if (messagesid != '') {
			// reschedule
			jQuery.ajax({
				url: 'index.php?module=Emails&action=EmailsAjax&file=ScheduleSendingAjax&ajxaction=RESCHEDULE&messagesid='+messagesid,
				data: {'date':date},
				success: function(data){
					fetch();
					selectRecord(messagesid,false,true);
					jQuery('#confirm-dialog').click();
				}
			});
		} else {
			// schedule new mail
			jQuery('#scheduled_date').val(date);
			jQuery('form[name="EditView"]').submit();
		}
	},
}
// crmv@187622e