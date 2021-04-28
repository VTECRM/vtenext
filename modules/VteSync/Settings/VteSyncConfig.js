/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@176547 */

var VteSyncConfig = {
	
	modulesAddedQueue: [],
	
	ajaxCall: function(action, params, options, callback) {
		var me = this;
		
		/*options = options || {
			includeForm: false,
			jsonData: true,
			callbackOnError: false,
		};*/
		
		params = params || {};
		var url = "index.php?module=Settings&action=SettingsAjax&file=VteSync&ajax=1&subaction="+action;
		
		/*if (options.includeForm) {
			var form = jQuery('#extws_form').serialize();
			params = jQuery.param(params) + '&' + form;
		}*/
		
		jQuery('#status').show();
		jQuery.ajax({
			url: url,
			type: 'POST',
			async: true,
			data: params,
			success: function(data) {
				jQuery('#status').hide();
				if (true) {
					// data should be json with a success property
					try {
						data = JSON.parse(data);
					} catch (e) {
						data = null;
					}
					if (data && data.success) {
						if (typeof callback == 'function') callback(data);
					} else if (data && data.error) {
						alert(data.error);
					} else {
						console.log('Unknown error');
						console.log(data);
					}
				} else {
					if (typeof callback == 'function') callback(data);
				}
			},
			error: function() {
				jQuery('#status').hide();
				/*if (options.callbackOnError) {
					if (typeof callback == 'function') callback();
				}*/
			}
		});
	},
	
	toggleSyncStatus: function(syncid, status) {
		var me = this;
		me.ajaxCall('toggle_status', {syncid: syncid, active: status ? 1 : 0}, null, function(data) {
			window.location.reload();
		});
	},
	
	addSync: function() {
		location.href = "index.php?module=Settings&action=VteSync&parenttab=Settings&mode=create";
	},
	
	editSync: function(syncid) {
		location.href = "index.php?module=Settings&action=VteSync&parenttab=Settings&mode=edit&syncid="+syncid;
	},
	
	deleteSync: function(syncid) {
		var me = this;
		vteconfirm(alert_arr.ARE_YOU_SURE_YOU_WANT_TO_DELETE, function(yes) {
			if (yes) {
				me.ajaxCall('delete_sync', {syncid: syncid}, null, function(data) {
					window.location.reload();
				});
			}
		});
	},
	
	onTypeChange: function(self) {
		var me = this,
		typeid = jQuery(self).val();
		
		if (typeid > 0) {
			// get available modules/auth...
			me.ajaxCall('get_service_info', {typeid: typeid}, null, function(data) {
				// crmv@190016
				// system url
				if (data.result.has_system_url) {
					jQuery('#system_url').attr("placeholder", data.result.system_url_example || '');
					jQuery('tr.system_url').show();
				} else {
					jQuery('tr.system_url').hide();
				}
				
				// crmv@190016e
				// populate and show
				if (data.result.modules) {
					me.populateModules(data.result.modules);
					jQuery('tr.sync_modules').show();
				}
				if (data.result.auths) {
					me.populateAuths(data.result.auths);
					jQuery('tr.auth_types').show();
				}
				// crmv@196666
				if (data.result.oauth2_flows) {
					me.populateOAuth2Flow(data.result.oauth2_flows);
				}
				// crmv@196666e
				// crmv@190016
				jQuery('tr.auth_type_oauth2').hide();
				jQuery('tr.auth_type_http').hide();
				jQuery('tr.auth_oauth2flow').hide(); // crmv@196666
				// crmv@190016e
				me.populateData(null, 2);
				me.populateData(null, 3);
			});
		} else {
			// hide everything
			jQuery('tr.system_url').hide(); // crmv@190016
			jQuery('tr.sync_modules').hide();
			jQuery('tr.auth_types').hide();
			jQuery('tr.auth_type_oauth2').hide();
			jQuery('tr.auth_type_http').hide(); // crmv@190016
		}
	},
	
	// crmv@196666
	populateOAuth2Flow: function(list) {
		var target = jQuery('#oauthtypeflow'),
			opts = '',
			cnt = 0;
	
		for (auth in list) {
			var label = list[auth];
			opts += '<option value="'+auth+'">'+label+'</option>';
			++cnt;
		}
		
		target.html(opts);
		
		// select the first if only one
		if (cnt == 1) target.val(auth);
	},
	// crmv@196666e
	
	populateModules: function(list) {
		var targetLeft = jQuery('#availModules'),
			targetRight = jQuery('select[name=authtype]'),
			mods = '';
		
		for (module in list) {
			var label = list[module];
			mods += '<tr onclick="jQuery(this).toggleClass(\'selected\')"><td data-module="'+module+'">'+label+'</td></tr>';
		}
		targetLeft.html(mods);
	},
	
	populateAuths: function(list) {
		var target = jQuery('select[name=authtype]'),
			opts = '';
		
		opts = '<option value="">'+alert_arr.LBL_PLEASE_SELECT+'</option>';
		target.html('');
		for (auth in list) {
			var label = list[auth];
			opts += '<option value="'+auth+'">'+label+'</option>';
		}
		target.html(opts);
		
	},
	
	onAuthTypeChange: function(self) {
		var me = this,
			val = jQuery(self).val();
		
		// crmv@190016
		if (val == 'oauth2') {
			jQuery('.auth_type_oauth2').show();
			jQuery('.auth_type_http').hide();
			jQuery('.auth_oauth2flow').show(); // crmv@196666 
			me.onOauthFlowChange(document.getElementById('oauthtypeflow'));
		} else if (val == 'http') {
			jQuery('.auth_type_oauth2').hide();
			jQuery('.auth_type_http').show();	
			jQuery('.auth_oauth2flow').hide(); // crmv@196666 
		} else {
			jQuery('.auth_type_oauth2').hide();
			jQuery('.auth_type_http').hide();
			jQuery('.auth_oauth2flow').hide(); // crmv@196666 
		}
		// crmv@190016e
		
		me.populateData(null, 4);
	},
	
	// crmv@196666
	onOauthFlowChange: function(self) {
		var me = this,
			val = jQuery(self).val();

		if (val == "oauth2_flow_client_cred") {
			jQuery('#oauth2_auth_ok').hide();
			jQuery('#oauth2_auth_ko').hide();
			jQuery('#revokeButton').hide();
			jQuery('#authorizeButton').hide();
		} else {
			jQuery('#authorizeButton').show();
			jQuery('#revokeButton').show();
		}
		
		me.populateData(null, 4);
	},
	// crmv@196666e
	
	oauthAuthorize: function() {
		var me = this;
					
		var params = {
			typeid: jQuery('#synctype').val(),
			client_id: jQuery('#client_id').val(),
			client_secret: jQuery('input[name=client_secret]').val(),
		};
		
		if (params.client_id == '' || params.client_secret == '') {
			vtealert(alert_arr.LBL_VTESYNC_FILL_OAUTH2);
			return false;
		}
		
		me.ajaxCall('save_oauth_data', params, null, function(data) {
			var url = 'index.php?module=Settings&action=SettingsAjax&file=VteSync&ajax=1&subaction=authorize';
			url += '&saveid='+encodeURIComponent(data.result.saveid);
			window.open(url, "_blank", "width=512,height=720,dialog,scrollbars=no,resizable=no,status=no,menubar=no,location=no");
		});
	},
	
	setAuthorizeStatus: function(status, saveid) {
		if (status) {
			jQuery('#oauth2_saveid').val(saveid);
			jQuery('#client_id').closest('div').removeClass('dvtCellInfo').addClass('dvtCellInfoOff');
			jQuery('#client_secret').closest('div').removeClass('dvtCellInfo').addClass('dvtCellInfoOff');
			jQuery('#oauth2_auth_ok').show();
			jQuery('#oauth2_auth_ko').hide();
			jQuery('#authorizeButton').hide();
			jQuery('#revokeButton').show();
		} else {
			jQuery('#oauth2_saveid').val('');
			jQuery('#client_id').closest('div').removeClass('dvtCellInfoOff').addClass('dvtCellInfo');
			jQuery('#client_secret').closest('div').removeClass('dvtCellInfoOff').addClass('dvtCellInfo');
			jQuery('#oauth2_auth_ko').show();
			jQuery('#oauth2_auth_ok').hide();
			jQuery('#authorizeButton').show();
			jQuery('#revokeButton').hide();
		}
	},
	
	oauthRevoke: function() {
		jQuery('#client_id').val('');
		jQuery('#client_secret').val('');
		this.setAuthorizeStatus(false);
	},
	
	addModules: function() {
		var me = this,
			target = jQuery('#selModules'),
			added = [],
			html = '';
		
		jQuery('#availModules tr.selected').each(function() {
			var td = jQuery(this).find('td');
			var mod = td.data('module'),
				label = me.getModuleLabel(mod);
				
			// check if existing
			if (target.find('td[data-module='+mod+']').length == 0) {
				var cfg = {
					module: mod
				}
				added.push(mod);
				html += me.getModuleHtml(mod, label, cfg);
			}
		});
		target.append(html);
		
		// show settings popup (only 1st if multiple)
		if (added.length > 0) {
			me.modulesAddedQueue = added;
			// show the config
			var nextmod = me.modulesAddedQueue.shift();
			var el = jQuery('#modconfig_'+nextmod).get(0);
			me.configModule(el);
		}
		
	},
	
	getModuleHtml: function(module, label, cfg) {
		var me = this,
			target = jQuery('#selModules');
		
		var cfgJson = JSON.stringify(cfg).replace(/"/g, '&quot;');
		var newrow = '<tr onclick="jQuery(this).toggleClass(\'selected\')">\
			<td data-module="'+module+'">'+label+'</td>\
			<td width="40">\
				<a href="javascript:void(0)" onclick="event.stopPropagation(); VteSyncConfig.configModule(this)"><i class="vteicon md-sm">settings</i></a>\
				<input type="hidden" id="modconfig_'+module+'" name="modconfig[]" value="'+cfgJson+'">\
			</td>\
		</tr>';
		
		return newrow;
	},
	
	removeModules: function() {
		var me = this;
		jQuery('#selModules tr.selected').each(function() {
			jQuery(this).remove();
		});
	},
	
	configModule: function(self) {
		var me = this,
			tdmod = jQuery(self).closest('tr').find('td[data-module]'),
			module = tdmod.data('module'),
			label = me.getModuleLabel(module),
			cfg = JSON.parse(jQuery('#modconfig_'+module).val() || "{}");
			
		// title
		jQuery('#ConfigModuleDiv_Handle_Title').find('span[name=modulename]').text(label);
		
		//console.log(module, cfg);
		var syncDir = jQuery('select[name=sync_direction]');
		var syncDel = jQuery('select[name=deletions]');
		var syncPlist = jQuery('select[name=sync_picklist]');
			
		if (module == 'Users') {
			// disable sync directions
			syncDir.find('option[value=both]').attr('disabled', true);
			syncDir.find('option[value=from_vte]').attr('disabled', true);
			syncDir.val('to_vte');
			// disable deletion options
			syncDel.find('option[value=both]').attr('disabled', true);
			syncDel.find('option[value=in_external]').attr('disabled', true);
			syncDel.val(cfg.deletions || 'both'); // crmv@190016
			// disable picklist
			syncPlist.val('none', false).attr('disabled', true).attr('readonly', true);
		} else {
			syncDir.find('option[value=both]').attr('disabled', false);
			syncDir.find('option[value=from_vte]').attr('disabled', false);
			syncDir.val(cfg.sync_direction || 'both');
			syncDel.find('option[value=both]').attr('disabled', false);
			syncDel.find('option[value=in_external]').attr('disabled', false);
			syncDel.val(cfg.deletions || 'both'); // crmv@190016
			syncPlist.attr('disabled', false).attr('readonly', false);
			syncPlist.val(cfg.sync_picklist || 'none');
		}
		jQuery('#modconfigName').val(module);
		showFloatingDiv('ConfigModuleDiv');
	},
	
	saveModuleConfig: function() {
		var me = this,
			module = jQuery('#modconfigName').val(),
			cfg = JSON.parse(jQuery('#modconfig_'+module).val() || "{}");
		
		var syncDir = jQuery('select[name=sync_direction]').val();
		var deletions = jQuery('select[name=deletions]').val();
		var sync_plist = jQuery('select[name=sync_picklist]').val();
		
		cfg.sync_direction = syncDir;
		cfg.deletions = deletions;
		cfg.sync_picklist = sync_plist;
		
		var cfgJson = JSON.stringify(cfg);
		jQuery('#modconfig_'+module).val(cfgJson);
		
		me.hideModuleConfig(false);
	},
	
	hideModuleConfig: function(cancel) {
		var me = this;
		
		hideFloatingDiv('ConfigModuleDiv');
		
		// check if I need to open another one
		if (me.modulesAddedQueue.length > 0) {
			var nextmod = me.modulesAddedQueue.shift();
			// find the right element
			var el = jQuery('#modconfig_'+nextmod).get(0);
			me.configModule(el);
		}
	},
	
	showOAuthHelp: function() {
		var stype = jQuery('#synctype').val();
		var div = jQuery('#OAuthHelpDiv');
		
		// hide all
		div.find('.oauth_help_link').hide();
		div.find('.oauth_help_scopes').hide();
		
		// show only selected service
		div.find('#oauth_link_'+stype).show();
		div.find('#oauth_scopes_'+stype).show();
		
		showFloatingDiv('OAuthHelpDiv');
	},
	
	save: function() {
		var me = this;
		
		// client validation
		
		// selected type
		var stype = jQuery('#synctype').val();
		if (stype == '') {
			vtealert(alert_arr.LBL_VTESYNC_SELECT_TYPE);
			return false;
		}
		
		// selected modules?
		if (jQuery('input[name="modconfig[]"]').length == 0) {
			vtealert(alert_arr.LBL_VTESYNC_SELECT_MODS);
			return false;
		}
		
		// selecet auth?
		var authType = jQuery('select[name=authtype]').val();
		if (authType == '') {
			vtealert(alert_arr.LBL_VTESYNC_SELECT_AUTH);
			return false;
		}
		
		if (authType == 'oauth2') {
			// oauth2 populated ?
			if (jQuery('#client_id').val() == '' || jQuery('#client_secret').val() == '') {
				vtealert(alert_arr.LBL_VTESYNC_FILL_OAUTH2);
				return false;
			}
			
			// oauth2 authorized ?
			if (jQuery('#oauthtypeflow').val() == 'oauth2_flow_authorization' && jQuery('#oauth2_saveid').val() == '') {
				vtealert(alert_arr.LBL_VTESYNC_OAUTH2_AUTH);
				return false;
			}
		}
		
		// server validation
		var form = me.serializeForm('VteSyncEditForm');
		me.ajaxCall('validate_save', form, null, function(data) {
			if (data && data.success) {
				jQuery('#VteSyncEditForm').submit();
			}
		});
		
	},
	
	serializeForm: function(formid) {
		var form = jQuery('#'+formid).serializeArray(),
			obj = {};
		
		for (var i=0; i<form.length; ++i) {
			var field = form[i];
			if (field.name.match(/\[\]$/)) {
				// array
				var nname = field.name.replace(/\[\]$/, '');
				if (!obj[nname]) obj[nname] = [];
				obj[nname].push(field.value);
			} else {
				obj[field.name] = field.value;
			}
		}
		
		return obj;
	},
	
	getModuleLabel: function(module) {
		var label = jQuery('#availModules').find('td[data-module='+module+']').text();
		return label;
	},
	
	populateData: function(syncdata, step) {
		var me = this;
		
		if (!step) {
			// first call
			jQuery('#synctype').val(syncdata.typeid).change();
			me.populating = true;
			me.initialData = syncdata;
		} else if (me.populating) {
			syncdata = me.initialData;

			if (step == 2) {
				// system url
				jQuery('#system_url').val(syncdata.system_url) // crmv@190016
				// modules
				var html = '';
				for (var i=0; i<syncdata.modconfig.length; ++i) {
					var cfg = syncdata.modconfig[i];
					var label = me.getModuleLabel(cfg.module);
					html += me.getModuleHtml(cfg.module, label, cfg);
				}
				jQuery('#selModules').append(html);
			} else if (step == 3) {
				// auth type
				jQuery('select[name=authtype]').val(syncdata.authtype).change();
			} else if (step == 4) {
				
				// auth fields
				if (syncdata.authtype == 'oauth2') {
					jQuery('#client_id').val(syncdata.authdata.client_id);
					jQuery('#client_secret').val(syncdata.authdata.client_secret);
					
					// crmv@196666
					if(syncdata.typeid == 4) { // client credentials, only suitecrm has it
						jQuery('#oauthtypeflow').val('oauth2_flow_client_cred');
					} else {
						jQuery('#oauthtypeflow').val('oauth2_flow_authorization');
						me.setAuthorizeStatus(true, -1); // don't check again'
					}
					// crmv@196666e
					
				// crmv@190016
				} else if (syncdata.authtype == 'http') {
					jQuery('#http_username').val(syncdata.authdata.username);
					jQuery('#http_password').val(syncdata.authdata.password);
				}
				// crmv@190016e
				
				me.populating = false;
				
			}

		}
	},
	
	
}