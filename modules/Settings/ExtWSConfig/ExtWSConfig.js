/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@146670 crmv@146671 */

var ExtWSConfig = {
	
	busy: false,
		
	showBusy: function() {
		var me = this;
		me.busy = true;
		jQuery('#extws_busy').show();
	},
	
	hideBusy: function() {
		var me = this;
		me.busy = false;
		jQuery('#extws_busy').hide();
	},
	
	createNew: function() {
		if (this.busy) return;
		this.showBusy();
		location.href = 'index.php?module=Settings&action=ExtWSConfig&parentTab=Settings&mode=create';
	},
	
	editWS: function(wsid) {
		if (this.busy) return;
		this.showBusy();
		location.href = 'index.php?module=Settings&action=ExtWSConfig&parentTab=Settings&mode=edit&extwsid='+wsid;
	},
	
	testWS: function(wsid) {
		var me = this;
		if (me.busy) return;
		me.prepareFieldForSave();
		if (!me.validate(false)) return false; // crmv@147433
		var formval = jQuery('#extws_form').serializeArray(),
			params = {};
		jQuery.each(formval, function(idx, el) {
			params[el.name] = el.value;
		});
		params[extwsid] = wsid;
		me.ajaxCall('test_ws', params, function(data) {
			// populate the popup
			var result = data.result;
			if (result) {
				me.populateTestResult(result);
				me.gotoTestTab('result');
				showFloatingDiv('TestWSWindow');
			}
		});
	},
	
	populateTestResult: function(result) {
		var me = this;
		
		jQuery('#wstest_status').removeClass('text-success text-danger').addClass(result.success ? 'text-success' : 'text-danger')
			.text(result.success ? alert_arr.SUCCESS : alert_arr.ERROR);
		jQuery('#wstest_code').text(result.code +' '+result.message);
		
		if (result.headers) {
			var htext = "";
			for (var key in result.headers) {
				var val = result.headers[key];
				htext += key+': '+val+"\r\n";
			}
			if (!me.headers_code) {
				me.headers_code = CodeMirror.fromTextArea(jQuery('#wstest_headers')[0], {
					mode: 'http',
					readOnly: true,
					theme: 'eclipse',
					lineWrapping: false,
					indentUnit: 4,
					tabSize: 4,
					indentWithTabs: true,
				});
			}
			me.headers_code.setValue(htext);
		}
		
		me.body_response = null;
		if (result.body) {
			if (!me.body_code) {
				me.body_code = CodeMirror.fromTextArea(jQuery('#wstest_body')[0], {
					mode: null,
					readOnly: true,
					theme: 'eclipse',
					lineWrapping: true,
					maxHighlightLength: 50000,
				});
			}
			me.body_code.setValue(result.body);
			
			// now detect the format of the response
			var beginBody = result.body.substring(0, 5),
				format = 'raw';
			
			if (beginBody == '<?xml') {
				format = 'xml';
			} else if (beginBody[0] == '[' || beginBody[0] == '{') {
				format = 'json';
			} else if (beginBody[0] == '<') {
				format = 'html';
			}
			jQuery('#wstest_formatas').val(format);
			me.formatResponse();
		}
	},
	
	formatResponse: function() {
		var me = this,
			format = jQuery('#wstest_formatas').val();
		
		var totalLines = me.body_code.lineCount(); 
		var range = { from: {line:0, ch:0}, to: {line:totalLines} };
		
		if (format != 'raw' && !me.body_response) {
			// save the original response
			me.body_response = me.body_code.getValue();
		}
		switch (format) {
			case 'json':
				me.body_code.setOption('mode', {name: 'javascript', json:true});
				me.body_code.autoFormatRange(range.from, range.to);
				break;
			case 'xml':
				me.body_code.setOption('mode', 'xml');
				me.body_code.autoFormatRange(range.from, range.to);
				break;
			case 'html':
				me.body_code.setOption('mode', 'htmlmixed');
				me.body_code.autoFormatRange(range.from, range.to);
				break;
			default:
			case 'raw':
				me.body_code.setOption('mode', null);
				if (me.body_response) {
					// restore the original response
					me.body_code.setValue(me.body_response);
				}
				break;
		}
		
		me.body_code.scrollTo(0,0);
		me.body_code.setCursor(0,0);
	},
	
	deleteWS: function(wsid) {
		var me = this;
		if (me.busy) return;
		if (confirm(alert_arr.SURE_TO_DELETE)) {
			me.ajaxCall('delete_ws', {extwsid: wsid}, function(data) {
				me.gotoList();
			});
		}
	},
	
	ajaxCall: function(action, params, callback, options) {
		var me = this;
		
		// return if busy
		if (me.busy) return;
		
		options = options || {
			includeForm: false,
			jsonData: true,
			callbackOnError: false,
		};
		params = params || {};
		var url = "index.php?module=Settings&action=SettingsAjax&file=ExtWSConfig&ajax=1&subaction="+action;
		
		if (options.includeForm) {
			var form = jQuery('#extws_form').serialize();
			params = jQuery.param(params) + '&' + form;
		}
		
		me.showBusy();
		jQuery.ajax({
			url: url,
			type: 'POST',
			async: true,
			data: params,
			success: function(data) {
				me.hideBusy();
				if (options.jsonData) {
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
				me.hideBusy();
				if (options.callbackOnError) {
					if (typeof callback == 'function') callback();
				}
			}
		});
		
	},

	gotoList: function() {
		var me = this;
		if (me.busy) return;
		me.showBusy();
		location.href = 'index.php?module=Settings&action=ExtWSConfig&parentTab=Settings';
	},
	
	showAuth: function() {
		jQuery('#auth_table').show();
		jQuery('#auth_button').hide();
	},
	
	hideAuth: function() {
		jQuery('#auth_table').hide();
		jQuery('#auth_button').show();
		jQuery('#extws_auth_username').val('');
		jQuery('#extws_auth_password').val('');
	},
	
	addHeader: function() {
		var table = jQuery('#headers_table');
		var tpl = jQuery('#header_row_tpl');
		
		var newrow = tpl.clone().show();
		table.append(newrow);
		table.show();
	},
	
	delHeader: function(self) {
		var table = jQuery('#headers_table');
		
		// remove row
		jQuery(self).closest('tr').remove();
		
		// if only 2 rows are left (header and tpl), hide it
		if (table.find('tr').length <= 2) table.hide();
	},
	
	addParam: function() {
		var table = jQuery('#params_table');
		var tpl = jQuery('#param_row_tpl');
		
		var newrow = tpl.clone().show();
		table.append(newrow);
		table.show();
	},
	
	delParam: function(self) {
		var table = jQuery('#params_table');
		
		// remove row
		jQuery(self).closest('tr').remove();
		
		// if only 2 rows are left (header and tpl), hide it
		if (table.find('tr').length <= 2) table.hide();
	},
	
	addResult: function(name, value) {
		var table = jQuery('#results_table');
		var tpl = jQuery('#result_row_tpl');
		
		var newrow = tpl.clone().show();
		
		if (name && value) {
			newrow.find('input.paramname').val(name);
			newrow.find('input.paramvalue').val(value);
		}
		table.append(newrow);
		table.show();
	},
	
	delResult: function(self) {
		var table = jQuery('#results_table');
		
		// remove row
		jQuery(self).closest('tr').remove();
		
		// if only 2 rows are left (header and tpl), hide it
		if (table.find('tr').length <= 2) table.hide();
	},
	
	delAllResults: function() {
		var table = jQuery('#results_table');
		table.find('tr').slice(2).remove();
		table.hide();
	},
	
	validateAndSave: function() {
		var me = this;
		me.prepareFieldForSave();
		if (!me.validate()) return false;
		return true;
	},
	
	prepareFieldForSave: function() {
		var authinfo = '',
			headers = [],
			params = [],
			results = [];
			
		var authname = jQuery('#extws_auth_username').val();
		if (authname !== '') {
			authinfo = {
				username: authname,
				password: jQuery('#extws_auth_password').val(),
			}
			authinfo = JSON.stringify(authinfo);
		}
		
		var htable = jQuery('#headers_table');
		htable.find('input.headername').each(function(idx, hval) {
			var hname = jQuery(hval).val();
			if (hname !== '') {
				var hval = jQuery(hval).closest('td').next().find('input.headervalue').val();
				headers.push({
					name: hname,
					value: hval,
				});
			}
		});
		
		var ptable = jQuery('#params_table');
		ptable.find('input.paramname').each(function(idx, hval) {
			var hname = jQuery(hval).val();
			if (hname !== '') {
				var hval = jQuery(hval).closest('td').next().find('input.paramvalue').val();
				params.push({
					name: hname,
					value: hval,
				});
			}
		});
		
		ptable = jQuery('#results_table');
		ptable.find('input.paramname').each(function(idx, hval) {
			var hname = jQuery(hval).val();
			if (hname !== '') {
				var hval = jQuery(hval).closest('td').next().find('input.paramvalue').val();
				if (hval != '') {
					results.push({
						name: hname,
						value: hval,
					});
				}
			}
		});
			
		jQuery('#extws_auth').val(authinfo);
		jQuery('#extws_headers').val(JSON.stringify(headers));
		jQuery('#extws_params').val(JSON.stringify(params));
		jQuery('#extws_results').val(JSON.stringify(results));
	},
	
	automapFields: function() {
		var me = this,
			data = me.body_response || me.body_code.getValue();
		
		me.ajaxCall('automap_fields', {data: data}, function(result) {
			if (result.success && result.fields) {
				var fields = result.fields;
				
				me.prepareFieldForSave();
				
				// retrieve fields in json
				var oldFields = JSON.parse(jQuery('#extws_results').val()) || [],
					oldFields2 = {};
				jQuery.each(oldFields, function(idx, el) {
					oldFields2[el.name] = el.value;
				});

				// now merge
				fields = jQuery.extend({}, oldFields2, fields);
				
				// remove old lines
				me.delAllResults();
				
				// and restore from fields
				for (var name in fields) {
					me.addResult(name, fields[name]);
				}
				// align json values
				me.prepareFieldForSave();
				
				hideFloatingDiv('TestWSWindow');
			}
		});
	},
	
	displayError: function(message) {
		alert(message);
		return false;
	},
	
	// crmv@147433
	validate: function(checkResults) {
		var me = this;
		
		if (checkResults === undefined || checkResults === null) {
			checkResults = true;
		}
		
		var wsname = jQuery('#extws_name').val();
		var wsurl = jQuery('#extws_url').val();
		var wsurl_label = jQuery('#extws_url').closest('tr').find('td > span').text();

		// check emptyness
		if (!wsname) return me.displayError(alert_arr.CANNOT_BE_EMPTY.replace('%s', alert_arr.LBL_NAME));
		if (!wsurl) return me.displayError(alert_arr.CANNOT_BE_EMPTY.replace('%s', wsurl_label));

		// check results
		if (checkResults) {
			var table = jQuery('#results_table');
			var fields = JSON.parse(jQuery('#extws_results').val()) || [];
			if (table.find('tr').length <= 2 || fields.length == 0) {
				return me.displayError(alert_arr.LBL_EXTWS_NO_RETURN_FIELDS);
			}
			// check for resultswith same name
			var names = jQuery.map(fields, function(o) { return o["name"]; });
			var dup = false;
			for (var i=0; i<names.length; ++i) {
				for (var j=i+1; j<names.length; ++j) {
					if (names[i] == names[j]) {
						dup = true;
						break;
					}
				}
				if (dup) break;
			}
			if (dup) return me.displayError(alert_arr.LBL_EXTWS_DUP_RETURN_FIELDS);
		}

		return true;
	},
	// crmv@147433e
	
	gotoTestTab: function(tab) {
		var me = this;
		
		if (tab == 'result') {
			jQuery('#wstab_result').removeClass('dvtUnSelectedCell').addClass('dvtSelectedCell');
			jQuery('#wstab_headers').removeClass('dvtSelectedCell').addClass('dvtUnSelectedCell');
			jQuery('#wstab_response').removeClass('dvtSelectedCell').addClass('dvtUnSelectedCell');
			jQuery('#wstab_result_div').show();
			jQuery('#wstab_headers_div').hide();
			jQuery('#wstab_response_div').hide();
		} else if (tab == 'headers') {
			jQuery('#wstab_result').removeClass('dvtSelectedCell').addClass('dvtUnSelectedCell');
			jQuery('#wstab_headers').removeClass('dvtUnSelectedCell').addClass('dvtSelectedCell');
			jQuery('#wstab_response').removeClass('dvtSelectedCell').addClass('dvtUnSelectedCell');
			jQuery('#wstab_result_div').hide();
			jQuery('#wstab_headers_div').show();
			jQuery('#wstab_response_div').hide();
			if (me.headers_code) me.headers_code.refresh();
		} else if (tab == 'response') {
			jQuery('#wstab_result').removeClass('dvtSelectedCell').addClass('dvtUnSelectedCell');
			jQuery('#wstab_headers').removeClass('dvtSelectedCell').addClass('dvtUnSelectedCell');
			jQuery('#wstab_response').removeClass('dvtUnSelectedCell').addClass('dvtSelectedCell');
			jQuery('#wstab_result_div').hide();
			jQuery('#wstab_headers_div').hide();
			jQuery('#wstab_response_div').show();
			if (me.body_code) me.body_code.refresh();
		}
	}
}