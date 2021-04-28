/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@192033 */

var Webforms ={

	confirmAction:function(msg){
		return confirm(msg);
	},
	deleteForm:function(formname,id){
		if (typeof webforms_alert_arr != 'undefined') {
			var status=Webforms.confirmAction(getTranslatedString('LBL_DELETE_MSG', webforms_alert_arr));
		} else {
			var status=Webforms.confirmAction(getTranslatedString('LBL_DELETE_MSG'));
		}
		if(!status){
			return false;
		}
		Webforms.submitForm(formname, 'index.php?module=Webforms&action=Delete&id='+id);
		return true;
	},
	editForm:function(id){
		Webforms.submitForm('action_form', 'index.php?module=Webforms&action=WebformsEditView&id='+id+'&parenttab=Settings&operation=edit');
	},
	submitForm:function(formName,action){
		document.forms[formName].action=action;
		document.forms[formName].submit();
	},
	showHideElement:function(){
		var len=arguments.length;
		for(var i=0;i<len;i++){
			// crmv@201752
			var elem = document.getElementById(arguments[i]);
			jQuery(elem).toggle();
			// crmv@201752e
		}
	},

	validateForm: function(form,action) {
		var name = jQuery('#name').val();
		var ownerid = jQuery('#ownerid').val();
		var module = jQuery('#targetmodule').val();
		
		if((name=="")||(name==null)||(ownerid=="")||(ownerid==null)||(module=="")||(module==null)){
			if (typeof webforms_alert_arr != 'undefined') {
				alert(getTranslatedString('LBL_MANDATORY_FIELDS_WF', webforms_alert_arr));
			} else {
				alert(getTranslatedString('LBL_MANDATORY_FIELDS_WF'));
			}
			return false;
		}
		elem=document.getElementById(form).elements;
		elemNo=document.getElementById(form).elements.length;
		for(i=0;i<elemNo;i++){
			if((elem[i].value!='' && elem[i].value!=null) && (elem[i].getAttribute('fieldtype')!=null && elem[i].getAttribute('fieldtype')!='') && elem[i].style.display!='none' ){
				switch(elem[i].getAttribute('fieldtype')){
					case 'date' :if(!dateValidate(elem[i].name,elem[i].getAttribute('fieldlabel'),elem[i].getAttribute('fieldtype')))
										return false;
						break;
					case 'time' :if(!timeValidate(elem[i].name,elem[i].getAttribute('fieldlabel'),elem[i].getAttribute('fieldtype')))
										return false;
						break;
					case 'currency':
					case 'number':
					case 'double' :if(!numValidate(elem[i].name,elem[i].getAttribute('fieldlabel'),elem[i].getAttribute('fieldtype')))
										return false;
						break;
					case 'email' :if(!patternValidate(elem[i].name,elem[i].getAttribute('fieldlabel'),elem[i].getAttribute('fieldtype')))
										return false;
						break;
					default :break;


				}
			}
		}
		if(mode=="save")
			Webforms.checkName(name,form,action);
		else
			Webforms.submitForm(form, action);
		return false;
	},

	getHTMLSource:function(id){
		var url = "module=Webforms&action=WebformsAjax&file=WebformsHTMLView&ajax=true&id=" + encodeURIComponent(id);

		VteJS_DialogBox.block();
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data:url,
			success: function(result) {
				VteJS_DialogBox.unblock();
				document.getElementById('webform_source').innerText = result;
				document.getElementById('webform_source').value=result;
				showFloatingDiv('orgLay1');
			}
		});
	},

	fetchFieldsView: function(module) {
		if((module=="")||(module==null)) return;
		var url = "module=Webforms&action=WebformsAjax&file=WebformsFieldsView&ajax=true&targetmodule=" + encodeURIComponent(module);

		VteJS_DialogBox.block();
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data:url,
			success: function(result) {
				VteJS_DialogBox.unblock();
				jQuery('#Webforms_FieldsView').html(result);
			}
		});
	},
	checkName: function(name,form,action) {
		if((name=="")||(name==null)) return;
		var url = "module=Webforms&action=WebformsAjax&file=Save&ajax=true&name=" + encodeURIComponent(name);

		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data:url,
			success: function(result) {
				var JSONres = JSON.parse(result);
				if(JSONres.result==false){
					alert(getTranslatedString('LBL_DUPLICATE_NAME', webforms_alert_arr));
				}
				else{
					Webforms.submitForm(form, action);
				}
			}
		});
	},
	//crmv@162158
	insertMetaVar: function(fieldid,value) {
		var fieldObj = document.getElementById(fieldid);
		jQuery(fieldObj).val(jQuery(fieldObj).val() + '$'+value);
		jQuery(fieldObj).focus();
	},
	//crmv@162158e
	//crmv@32257
	checkHidden: function(name,checked) {
		var el = document.getElementById('hidden['+name+']');
		if (checked) {
			el.checked = false;
			el.disabled = true;
		} else {
			el.checked = false;
			el.disabled = false;
		}
	}
	//crmv@32257e
}

function getTranslatedString(key, alertArray){
	if(alertArray != undefined) {
		if(alertArray[key] != undefined) {
			return alertArray[key];
		}
	}
    if(alert_arr[key] != undefined) {
        return alert_arr[key];
    }
    else {
        return key;
	}
}
