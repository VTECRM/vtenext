/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@150592 crmv@192033 */

var ProfileUtils = ProfileUtils || {

	rolevalidate: function(profile_err_msg) {
	    var me = this,
	    	profilename = trim(document.getElementById('pobox').value);
	    
	    if(profilename != '') {
	    	me.dup_validation(profilename, '', function(result){
	    		if(result.indexOf('SUCCESS') > -1)
					document.profileform.submit();
				else
					alert(result);
	    	});
		} else {
	        alert(profile_err_msg);
	        document.getElementById('pobox').focus();
	        return false;
	    }
	    return false;
	},
	
	dup_validation: function(profilename, profileid, callback) {
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'module=Users&action=UsersAjax&file=CreateProfile&ajax=true&dup_check=true&profile_name='+profilename+'&profileid='+profileid,
			success: function(result) {
				callback(result);
			}
		});
	},
	
	UpdateProfile: function(profileid, lbl_profilename_empty, lbl_profile_details_updated) {
		var me = this;
		
		if(default_charset.toLowerCase() == 'utf-8')
		{
			//crmv@157296
			var prof_name = encodeURIComponent(document.getElementById('profile_name').value);
			var prof_desc = encodeURIComponent(document.getElementById('description').value);
			//crmv@157296e
		}
		else
		{
			var prof_name = escapeAll(document.getElementById('profile_name').value);
			var prof_desc = escapeAll(document.getElementById('description').value);
		}
		if(prof_name == '')
		{
			jQuery('#profile_name').focus();
			alert(lbl_profilename_empty);
		}
		else
		{
			me.dup_validation(prof_name, profileid, function(result){
				if(result.indexOf('SUCCESS') > -1) {
					var urlstring ="module=Users&action=UsersAjax&file=RenameProfile&profileid="+profileid+"&profilename="+prof_name+"&description="+prof_desc;
					jQuery.ajax({
						url: 'index.php',
						method: 'POST',
						data:urlstring,
						success: function(result) {
							jQuery('#renameProfile').hide();
							window.location.reload();
							alert(lbl_profile_details_updated);
						}
					});
				} else
					alert(result);
			});
		}
	},
	
	fnToggleVIew: function(obj){
		jQuery('#'+obj).toggleClass('hideTable');
	},
	
	DeleteProfile: function(obj,profileid) {
        jQuery('#status').show();
        jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data:'module=Users&action=UsersAjax&file=ProfileDeleteStep1&profileid='+profileid,
			success: function(result) {
				jQuery('#status').hide();
				jQuery("#tempdiv").html(result);
				showFloatingDiv('DeleteLay', obj);
			}
		});
	},
	
	ajaxCall: function(service, params, options, callback) {
		var me = this;
			
		params = jQuery.extend({}, {
			displayVersion: false,
			versionContainer: 'profileListVersion',
		}, params || {});
		
		options = jQuery.extend({}, options || {});
		
		var url = 'index.php?module=Settings&action=SettingsAjax&file=ListProfilesAjax&ajax=true&parenttab=Settings&sub_mode='+service;
		
		jQuery('#status').show();
		jQuery.ajax({
			url: url,
			method: 'POST',
			data: params,
			success: function(response) {
				if (params.displayVersion && params.versionContainer) {
					jQuery("#"+params.versionContainer).html(response);
				}
				jQuery('#status').hide();
				if (typeof callback == 'function') callback(response);
			}
		});
	},
	
	closeVersion: function(callback) {
		var me = this;
		me.ajaxCall('closeVersion', {displayVersion: true}, {}, function(response){
			if (typeof callback == 'function') callback();
		});
	},
	
	exportVersion: function() {
		var me = this,
			module = jQuery('input[name=fld_module]').val(),
			url = 'index.php?module=Settings&action=SettingsAjax&file=ListProfilesAjax&sub_mode=exportVersion';
		
		me.ajaxCall('checkExportVersion', {}, {}, function(response){
			if (response != '') alert(response);
			else location.href = url;
		});
	}
}