/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
function alertDataMorphsuit() {
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: 'module=Morphsuit&action=MorphsuitAjax&file=RequestMorphsuit&type=time_expired',
		success: function(result) {
				
			jQuery('#checkDataMorphsuit').html(result);
			
			if (getObj('freezeMorphsuit') == null) {
				var oFreezeLayerMorphsuit = document.createElement("DIV");
			    oFreezeLayerMorphsuit.id = "freezeMorphsuit";
			    oFreezeLayerMorphsuit.className = "small veil_new";
			    if (browser_ie) oFreezeLayerMorphsuit.style.height = (document.body.offsetHeight + (document.body.scrollHeight - document.body.offsetHeight)) + "px";
			    else if (browser_nn4 || browser_nn6) oFreezeLayerMorphsuit.style.height = document.body.offsetHeight + "px";
			    oFreezeLayerMorphsuit.style.width = "100%";
			    document.body.appendChild(oFreezeLayerMorphsuit);
			    jQuery('#freezeMorphsuit').css('z-index','10000002');
		    }
			jQuery('#checkDataMorphsuit').show();
			placeAtCenter(getObj('checkDataMorphsuit'));
			getObj('checkDataMorphsuit').style.top = '0px';
		}
	});
}

function checkUsersMorphsuit(userid,mode,user_status) {
	res = getFile('index.php?module=Morphsuit&action=MorphsuitAjax&file=CheckUsersMorphsuit&userid='+userid+'&mode='+mode+'&user_status='+user_status);
	var result = false;
	if (res == 'yes' || res.indexOf("images/denied.gif")>-1) {	//se il modulo è disattivato permetto
		result = true;
	}
	return result;
}
function isFreeVersion() {
	res = getFile('index.php?module=Morphsuit&action=MorphsuitAjax&file=IsFreeVersion');
	var result = false;
	if (res == 'yes') {
		result = true;
	}
	return result;
}
function CheckAvailableVersion(vteUpdateServer,actual_version,day) {
	var params = {
		'check_version' : 'yes',
		'actual_version' : actual_version
	}
	jQuery.ajax({
		url : vteUpdateServer,
		type: 'POST',
		data: params,
		//async: false,
		complete  : function(res, status) {
			if (res.responseText == 'yes'){
				CheckAvailableVersionProcess(day);
	    	} else {
	    		var url = 'index.php?module=Morphsuit&action=MorphsuitAjax&file=SetCheckAvailableVersion';
	    		if (day != undefined) {
		    		url += '&day='+day;
	    		}
	    		getFile(url);
	    	}
		}
	});
}
function CheckAvailableVersionProcess(day) {
	CheckAvailableVersion_callback = document.getElementById("CheckAvailableVersionDiv");
	if(CheckAvailableVersion_callback == null) return;
	CheckAvailableVersion_callback.style.display = 'block';
	var url = '';
	if (day != undefined) {
		url += '&day='+day;
	}
	CheckAvailableVersion_callback.innerHTML = getFile('index.php?module=Morphsuit&action=MorphsuitAjax&file=CheckAvailableVersionDiv'+url);
}
