/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
function getSDKUitype(uitype) {
	if (sdk_js_uitypes[uitype] != undefined) {
		return sdk_js_uitypes[uitype];
	} else {
		return '';
	}
}
function SDKValidate(form) {
	if (form == undefined || form == '') {
		form = this.document.EditView;
	}
	if (form == undefined) {
		return false;
	}
	if (top.sdk_js_presave != undefined) {
		var exists_pre_save = false;
		for (i in top.sdk_js_presave) {
			if (top.sdk_js_presave[i]['module'] == form.module.value) {
				exists_pre_save = true;
				break;
			} 
		}
		if (exists_pre_save == false) {
			return false;
		}
	}
    var url = '';
 	var inputs = jQuery(form).serializeArray();
	jQuery.each(inputs, function(i, field) {
    	url += '&sdk_par_'+field.name+'='+encodeURIComponent(field.value);
	});
 	var inputs_checkbox = jQuery(form).find(':checkbox');
	jQuery.each(inputs_checkbox, function(i, field) {
		var value = 0;
		if (field.checked) value = 1;
    	url += '&sdk_par_'+field.name+'='+value;
	});
	//crmv@26919
	var force_false = false;
	var response = jQuery.ajax({
	//crmv@26919e
		url:'index.php?module=SDK&action=SDKAjax&file=Validate&form='+form.name,
		dataType:"json",
		type: "post",
		async: false,
		data: url,
	  	success: function(data,textStatus){
	  		if (textStatus == 'success') {
	  			if (data['changes'] != '' && jQuery(data['changes']).length > 0) {
	  				//crmv@58750
	  				var fancyBoxes = jQuery('#fancybox-content').contents();
					if(fancyBoxes.length >= 1) var fancyFirst = jQuery(fancyBoxes[0]).contents(); 						
	  				//crmv@58750e	  			
  					jQuery.each(data['changes'], function(field,value){
  						//crmv@58750
  						if(!jQuery.isEmptyObject(fancyFirst))
				  			fancyFirst.find('[name="'+form.name+'"] :input[name="'+field+'"]').val(value);
						else
						//crmv@58750e
  							jQuery('[name="'+form.name+'"] :input[name="'+field+'"]').val(value);
  					})
  				}
  				if(data['focus'] != '') {
  					jQuery('[name="'+form.name+'"] :input[name="'+data['focus']+'"]').focus();
  				}
  				//crmv@26919
  				if (data['confirm']){
  					if (!confirm(data['message'])){
  						force_false = true;
  					}
  				}
  				else if (data['message'] != '') {
  				//crmv@26919e
  					alert(data['message']);
  				}
	  		}
  		}
	});
	//crmv@26919
	if (force_false){
		var respose2 = new Object();
		var data = eval("("+response.responseText+")");
		data['status'] = false;
		respose2.responseText = JSON.stringify(data);
		response = respose2;
	}
	return response;
	//crmv@26919e
}
function getSDKHomeIframe(stuffid, callback) {
	jQuery.ajax({
		url: 'index.php',
		method: 'GET',
		data: 'module=SDK&action=SDKAjax&file=GetHomeIframe&stuffid='+stuffid,
		dataType: 'json',
		success: function(sdkdata) {
			if (typeof callback == 'function') callback(sdkdata);
		}
	});
}