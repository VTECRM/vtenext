/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@192033 */

window.VTE = window.VTE || {};

VTE.PDFMakerActions = VTE.PDFMakerActions || {

	busy: false,
	
	showBusy: function() {
		var me = VTE.PDFMakerActions;
		me.busy = true;
		jQuery("#vtbusy_info").show();
	},
	
	hideBusy: function() {
		var me = VTE.PDFMakerActions;
		me.busy = false;
		
		jQuery("#vtbusy_info").hide();
		jQuery('.dropdown.open').removeClass('open');
		releaseOverAll('detailViewActionsContainer');
	},
	
	getSelectedTemplates: function() {
		var selectedColumnsObj = getObj("use_common_template");
		var selectedColStr = "";
		for (i = 0; i < selectedColumnsObj.options.length; i++) {
			if (selectedColumnsObj.options[i].selected) {
				selectedColStr += selectedColumnsObj.options[i].value + ";";
			}
		}
		return selectedColStr;
	},

	getPDFDocDivContent: function(rootElm, module, id) {
		var me = VTE.PDFMakerActions;
		
		if (me.busy) return false;
		
		me.showBusy();
		
		jQuery.ajax({
			url: "index.php?module=PDFMaker&return_module=" + module + "&action=PDFMakerAjax&file=docSelect&return_id=" + id,
			method: 'POST',
			dataType: 'json',
			success: function(res) {
				// crmv@163191
				me.hideBusy();
				if (res && res.success) {
					var title = res.title;
					var contents = res.html;
					VTE.showModal('PDFDocDiv', title, contents);
				}
				// crmv@163191e
			}
		});
	},

	getPDFBreaklineDiv: function(rootElm, id) {
		var me = VTE.PDFMakerActions;
		
		if (me.busy) return false;
		
		me.showBusy();
		
		jQuery.ajax({
			url:'index.php',
			method: 'POST',
			data:  "module=PDFMaker&action=PDFMakerAjax&file=breaklineSelect&return_id=" + id,
			success: function(result) {
				getObj('PDFBreaklineDiv').innerHTML = result;
				showFloatingDiv('PDFBreaklineDiv');
				me.hideBusy();
			}
		});
	},

	getPDFImagesDiv: function(rootElm, id) {
		var me = VTE.PDFMakerActions;
		
		if (me.busy) return false;
		
		me.showBusy();
		
		jQuery.ajax({
			url: 'index.php', 
			method: 'POST',
			data: "module=PDFMaker&action=PDFMakerAjax&file=imagesSelect&return_id=" + id,
			success: function(result) {
				getObj('PDFImagesDiv').innerHTML = result;
				showFloatingDiv('PDFImagesDiv');
				me.hideBusy();
			}
		});
	},

	sendPDFmail: function(module, idstrings) {
		var me = VTE.PDFMakerActions;
		
		if (me.busy) return false;
		
		me.showBusy();
		
		var smodule = document.DetailView.module.value;
		var record = document.DetailView.record.value;

		//crmv@48915
		var result = '';
		jQuery.ajax({
			'url': 'index.php',
			'type': 'POST',
			'data': "module=PDFMaker&return_module=" + module + "&action=PDFMakerAjax&file=mailSelect&idlist=" + idstrings,
			'async': false,
			success: function(data) {
				result = data;
			}
		});
		
		if (result == "Mail Ids not permitted" || result == "No Mail Ids") {
			var emailhref = 'module=PDFMaker&action=PDFMakerAjax&file=SendPDFMail&language=' + document.getElementById('template_language').value + '&record=' + record + '&relmodule=' + module + '&commontemplateid=' + VTE.PDFMakerActions.getSelectedTemplates();
			jQuery.ajax({
				'url': 'index.php',
				'type': 'POST',
				'data': emailhref,
				'async': false,
				success: function(data2) {
					// crmv@106363
					var url = 'index.php?module=Emails&action=EmailsAjax&file=EditView&pmodule=' + module +
						'&pid=' + idstrings +
						'&language=' + jQuery('#template_language').val() +
						'&sendmail=true' +
						'&attachment=' + encodeURIComponent(data2 + '.pdf');
					window.open(url, '_blank');
					
					me.hideBusy();
					// crmv@106363e
				},
			});
		} else {
			jQuery('#sendpdfmail_cont').show();
			getObj('sendpdfmail_cont').innerHTML = result;
			
			showFloatingDiv('sendpdfmail_cont');
			me.hideBusy();
		}
		//crmv@48915e
	},

	validate_sendPDFmail: function(idlist, module) {
		var me = VTE.PDFMakerActions;
		
		if (me.busy) return false;
		
		var smodule = document.DetailView.module.value;
		var record = document.DetailView.record.value;
		var j = 0;
		var chk_emails = document.SendPDFMail.elements.length;
		var oFsendmail = document.SendPDFMail.elements
		email_type = new Array();
		for (var i = 0; i < chk_emails; i++) {
			if (oFsendmail[i].type != 'button') {
				if (oFsendmail[i].checked != false) {
					email_type[j++] = oFsendmail[i].value;
				}
			}
		}
		if (email_type != '') {
			me.showBusy();
			
			var field_lists = email_type.join(':');
			//crmv@48915
			var emailhref = 'module=PDFMaker&action=PDFMakerAjax&file=SendPDFMail&language=' + document.getElementById('template_language').value + '&record=' + record + '&relmodule=' + smodule + '&commontemplateid=' + VTE.PDFMakerActions.getSelectedTemplates();
			jQuery.ajax({
				'url': 'index.php',
				'type': 'POST',
				'data': emailhref,
				'async': false,
				success: function(data) {
					window.open('index.php?module=Emails&action=EmailsAjax&file=EditView&pmodule=' + module + '&idlist=' + idlist + '&field_lists=' + field_lists + '&language=' + document.getElementById('template_language').value + '&sendmail=true&attachment=' + encodeURIComponent(data) + '.pdf' + '&pid=' + record, '_blank'); //crmv@58554 crmv@107249
					me.hideBusy();
				},
			});
			//crmv@48915e
			hideFloatingDiv('sendpdfmail_cont');
			return true;
		} else {
			alert(alert_arr.SELECT_MAILID);
		}
	},

	validatePDFDocForm: function() {
		if (document.PDFDocForm.notes_title.value == '') {
			alert_label = getObj('alert_doc_title').innerHTML;
			alert(alert_label);
			return false;
		} else {
			document.PDFDocForm.template_ids.value = VTE.PDFMakerActions.getSelectedTemplates();
			document.PDFDocForm.language.value = document.getElementById('template_language').value;
			return true;
		}
	},

	savePDFBreakline: function() {
		var me = VTE.PDFMakerActions;
		
		if (me.busy) return false;
		
		me.showBusy();
		
		var record = document.DetailView.record.value;
		var frm = document.PDFBreaklineForm;
		var url = 'module=PDFMaker&action=PDFMakerAjax&file=SavePDFBreakline&pid=' + record + '&breaklines=';
		var url_suf = '';
		var url_suf2 = '';
		if (frm != 'undefined') {
			for (i = 0; i < frm.elements.length; i++) {
				if (frm.elements[i].type == 'checkbox') {
					if (frm.elements[i].name == 'show_header' || frm.elements[i].name == 'show_subtotal') {
						if (frm.elements[i].checked)
							url_suf2 += '&' + frm.elements[i].name + '=true';
						else
							url_suf2 += '&' + frm.elements[i].name + '=false';
					} else {
						if (frm.elements[i].checked)
							url_suf += frm.elements[i].name + '|';
					}
				}
			}
			url += url_suf + url_suf2;
			jQuery.ajax({
				url: 'index.php', 
				type: 'POST',
				data: url,
				success: function(result) {
					hideFloatingDiv('PDFBreaklineDiv');
					me.hideBusy();
				}
			});
		}
	},

	savePDFImages: function() {
		var me = VTE.PDFMakerActions;
		
		if (me.busy) return false;
		
		me.showBusy();
		
		var record = document.DetailView.record.value;
		var frm = document.PDFImagesForm;
		var url = 'module=PDFMaker&action=PDFMakerAjax&file=SavePDFImages&pid=' + record;
		var url_suf = '';
		if (frm != 'undefined') {
			for (i = 0; i < frm.elements.length; i++) {
				if (frm.elements[i].type == 'radio') {
					if (frm.elements[i].checked) {
						url_suf += '&' + frm.elements[i].name + '=' + frm.elements[i].value;
					}
				} else if (frm.elements[i].type == 'text') {
					url_suf += '&' + frm.elements[i].name + '=' + frm.elements[i].value;
				}
			}

			url += url_suf;
			jQuery.ajax({
				url: 'index.php', 
				type: 'POST',
				data: url,
				success:  function(result) {
					hideFloatingDiv('PDFImagesDiv');
					me.hideBusy();
				}
			});
		}
	},

	checkIfAny: function() {
		var frm = document.PDFBreaklineForm;
		if (frm != 'undefined') {
			var j = 0;
			for (i = 0; i < frm.elements.length; i++) {
				if (frm.elements[i].type == 'checkbox' && frm.elements[i].name != 'show_header' && frm.elements[i].name != 'show_subtotal') {
					if (frm.elements[i].checked) {
						j++;
					}
				}
			}
			if (j == 0) {
				frm.show_header.checked = false;
				frm.show_subtotal.checked = false;
				frm.show_header.disabled = true;
				frm.show_subtotal.disabled = true;
			} else {
				frm.show_header.disabled = false;
				frm.show_subtotal.disabled = false;
			}
		}
	},

	getPDFListViewPopup2: function(srcButt, module) {
		if (document.getElementById("PDFListViewDiv") == undefined) {
			var newdiv = document.createElement('div');
			newdiv.setAttribute('id', 'PDFListViewDiv');
			document.body.appendChild(newdiv);
		}
		
		// crmv@17889
		var select_options = get_real_selected_ids(module);
		if (select_options.substr('0', '1') == ";") {
			select_options = select_options.substr('1');
		}
		// crmv@17889e
		
		var x = select_options.split(";");
		var count = x.length;
		// crmv@27096
		count = count - 1;
		if (count < 1) {
			alert(alert_arr.SELECT);
			return false;
		}
		// crmv@27096e
		
		jQuery('#status').show();
		
		jQuery.ajax({
			url: 'index.php', 
			type: 'POST',
			data: "module=PDFMaker&return_module=" + module + "&action=PDFMakerAjax&file=listviewSelect",
			success:  function(result) {
				getObj('PDFListViewDiv').innerHTML = result;
				showFloatingDiv('PDFListViewDivCont');
				releaseOverAll('detailViewActionsContainer');

				jQuery('#status').hide();
			}
		});
	},
	
	onGeneratePDF: function(rootElm, module, recordid) {
		if (VTE.PDFMakerActions.getSelectedTemplates() == '') {
			alert(alert_arr['select_template']);
		} else {
			var params = {
				'module': 'PDFMaker',
				'relmodule': module,
				'action': 'CreatePDFFromTemplate',
				'record': recordid,
				'commontemplateid': VTE.PDFMakerActions.getSelectedTemplates(),
				'language': document.getElementById('template_language').value,
			};
			
			document.location.href = 'index.php?' + jQuery.param(params);
		}
	},
	
	onSendPDFmail: function(rootElm, module, recordid) {
		VTE.PDFMakerActions.sendPDFmail(module, recordid);
	},
	
	onEditAndGeneratePDF: function(rootElm, module, recordid) {
		if (VTE.PDFMakerActions.getSelectedTemplates() == '') {
			alert(alert_arr['select_template']);
		} else {
			var params = {
				'module': 'PDFMaker',
				'relmodule': module,
				'action': 'PDFMakerAjax',
				'file': 'CreatePDFFromTemplate',
				'mode': 'content',
				'record': recordid,
				'commontemplateid': VTE.PDFMakerActions.getSelectedTemplates(),
				'language': document.getElementById('template_language').value,
			};
			
			openPopUp('PDF', this, 'index.php?' + jQuery.param(params), '', '900', '800', 'menubar=no,toolbar=no,location=no,status=no,resizable=yes,scrollbars=yes');
		}
	},
	
	onSaveAsDocument: function(rootElm, module, recordid) {
		if (VTE.PDFMakerActions.getSelectedTemplates() == '') {
			alert(alert_arr['select_template']);
		} else {
			VTE.PDFMakerActions.getPDFDocDivContent(rootElm, module, recordid);
		}
	},
	
	onGetPDFBreaklineDiv: function(rootElm, module, recordid) {
		VTE.PDFMakerActions.getPDFBreaklineDiv(rootElm, recordid);
	},
	
	onGetPDFImagesDiv: function(rootElm, module, recordid) {
		VTE.PDFMakerActions.getPDFImagesDiv(rootElm, recordid);
	},
	
	onGenerateRTF: function(rootElm, module, recordid) {
		if (VTE.PDFMakerActions.getSelectedTemplates() == '') {
			alert(alert_arr['select_template']); 
		} else {
			var params = {
				'module': 'PDFMaker',
				'relmodule': module,
				'action': 'CreatePDFFromTemplate',
				'type': 'rtf',
				'record': recordid,
				'commontemplateid': VTE.PDFMakerActions.getSelectedTemplates(),
				'language': document.getElementById('template_language').value,
			};
				
			document.location.href = 'index.php?' + jQuery.param(params);
		}
	},
	
};

/**
 * @deprecated
 * This function has been moved to VTE.PDFMakerActions class.
 */

function getSelectedTemplates() {
	return VTE.callDeprecated('getSelectedTemplates', VTE.PDFMakerActions.getSelectedTemplates, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.PDFMakerActions class.
 */

function getPDFDocDivContent(rootElm, module, id) {
	return VTE.callDeprecated('getPDFDocDivContent', VTE.PDFMakerActions.getPDFDocDivContent, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.PDFMakerActions class.
 */

function getPDFBreaklineDiv(rootElm, id) {
	return VTE.callDeprecated('getPDFBreaklineDiv', VTE.PDFMakerActions.getPDFBreaklineDiv, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.PDFMakerActions class.
 */

function getPDFImagesDiv(rootElm, id) {
	return VTE.callDeprecated('getPDFImagesDiv', VTE.PDFMakerActions.getPDFImagesDiv, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.PDFMakerActions class.
 */

function sendPDFmail(module, idstrings) {
	return VTE.callDeprecated('sendPDFmail', VTE.PDFMakerActions.sendPDFmail, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.PDFMakerActions class.
 */

function validate_sendPDFmail(idlist, module) {
	return VTE.callDeprecated('validate_sendPDFmail', VTE.PDFMakerActions.validate_sendPDFmail, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.PDFMakerActions class.
 */

function validatePDFDocForm() {
	return VTE.callDeprecated('validatePDFDocForm', VTE.PDFMakerActions.validatePDFDocForm, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.PDFMakerActions class.
 */

function savePDFBreakline() {
	return VTE.callDeprecated('savePDFBreakline', VTE.PDFMakerActions.savePDFBreakline, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.PDFMakerActions class.
 */

function savePDFImages() {
	return VTE.callDeprecated('savePDFImages', VTE.PDFMakerActions.savePDFImages, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.PDFMakerActions class.
 */

function checkIfAny() {
	return VTE.callDeprecated('checkIfAny', VTE.PDFMakerActions.checkIfAny, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.PDFMakerActions class.
 */

function getPDFListViewPopup2(srcButt, module) {
	return VTE.callDeprecated('getPDFListViewPopup2', VTE.PDFMakerActions.getPDFListViewPopup2, arguments);
}