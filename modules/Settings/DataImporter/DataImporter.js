/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@65455 */

var DataImporter = {
	
	busy: false,
	
	dbports: {
		'mysql' : 3306,
		'mssql' : 1433,
		'oci8po': 1521,
	},
	
	showBusy: function() {
		var me = this;
		me.busy = true;
		jQuery('#dimporter_busy').show();
	},
	
	hideBusy: function() {
		var me = this;
		me.busy = false;
		jQuery('#dimporter_busy').hide();
	},
	
	gotoList: function() {
		var me = this;
		if (me.busy) return;
		me.showBusy();
		location.href = 'index.php?module=Settings&action=DataImporter&parentTab=Settings';
	},
	
	createNew: function() {
		if (this.busy) return;
		this.showBusy();
		location.href = 'index.php?module=Settings&action=DataImporter&parentTab=Settings&mode=create&data_importer_step=1';
	},
	
	editImport: function(importid) {
		if (this.busy) return;
		this.showBusy();
		location.href = 'index.php?module=Settings&action=DataImporter&parentTab=Settings&mode=edit&data_importer_step=1&importid='+importid;
	},
	
	deleteImport: function(importid) {
		var me = this;
		if (me.busy) return;
		if (confirm(alert_arr.SURE_TO_DELETE)) {
			me.ajaxCall('delete_import', {importid:importid}, function(data) {
				me.gotoList();
			});
		}
	},
	
	enableImport: function(importid) {
		var me = this;
		if (me.busy) return;
		
		me.ajaxCall('enable_import', {importid:importid}, function(data) {
			me.gotoList();
		});
	},
	
	disableImport: function(importid) {
		var me = this;
		if (me.busy) return;
		
		me.ajaxCall('disable_import', {importid:importid}, function(data) {
			me.gotoList();
		});
	},
	
	gotoStep: function(step) {
		// set the inputs
		jQuery('#data_importer_step').val(step);
		
		// get the form
		var form = document.getElementById('data_importer_form');
		
		// crmv@111926
		if (form) {
			// count the number of vars and populate the counter field
			var varcount = jQuery(form).find(':input').length;
			jQuery('#form_var_count').val(varcount);
			
			// submit the form
			form.submit();
		}
		// crmv@111926e
	},
	
	getCurrentStep: function() {
		var step = parseInt(jQuery('#data_importer_prev_step').val());
		return step;
	},
	
	gotoNextStep: function() {
		var me = this,
		step = me.getCurrentStep(),
		nstep = step+1;
		
		if (!me.validateStep(step)) return false;
		
		me.hideError();
		me.gotoStep(nstep);
	},
	
	gotoPrevStep: function() {
		var me = this,
		step = me.getCurrentStep(),
		pstep = step-1;
		
		//if (!me.validateStep(step)) return false;
		
		me.hideError();
		me.gotoStep(pstep);
	},
	
	hideNavigationButtons: function() {
		jQuery('#dimport_div_navigation').hide();
	},
	
	showNavigationButtons: function() {
		jQuery('#dimport_div_navigation').show();
	},
	
	ajaxCall: function(action, params, callback, options) {
		var me = this;
		
		// return if busy
		if (me.busy) return;
		
		options = options || {
			includeForm: false,
			jsonData: true,
			callbackOnError: false,
			//hidePopupMessage: true,
		};
		params = params || {};
		var url = "index.php?module=Settings&action=SettingsAjax&file=DataImporter&ajax=1&subaction="+action;
		
		if (options.includeForm) {
			var form = jQuery('#data_importer_form').serialize();
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
				//if (options.hidePopupMessage) me.hidePopupMessage();
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
				//if (options.hidePopupMessage) me.hidePopupMessage();
				if (options.callbackOnError) {
					if (typeof callback == 'function') callback();
				}
			}
		});
		
	},
	
	initEditor: function(value, mode) {
		var me = this;
		
		if (!mode) mode = 'sql';
		
		var ta = document.getElementById('dimport_dbquery');
		if (ta) {
			// then transform
			me.codeMirror = CodeMirror.fromTextArea(ta, {
				theme: 'eclipse',
				lineWrapping: false,
				indentUnit: 4,
				tabSize: 4,
				indentWithTabs: true,
			});
			me.setEditorText(value || '', mode);
		} else {
			console.log('Textarea not found');
		}
	},
	
	getEditorText: function() {
		var me = this,
		text;
		
		if (me.codeMirror) {
			text = me.codeMirror.getDoc().getValue();
		} else {
			// fallback on standard textarea
			text = jQuery('#dimport_dbquery').val();
		}
		
		return text;
	},
	
	setEditorText: function(text, mode) {
		var me = this;
		
		if (!mode) mode = 'sql';
		
		if (me.codeMirror) {
			me.codeMirror.getDoc().setValue('');
			me.codeMirror.setOption('mode', mode);
			me.codeMirror.getDoc().setValue(text);
		} else {
			// fallback on standard textarea
			jQuery('#dimport_dbquery').val(text);
		}
	},
	
	openFileChooser: function() {
		var me = this;
		me.showFloatingDiv('dimport_div_filechooser');
		jQuery('#fileChooserTree').fileTree({
			//root: 'dataimport/',
			script: 'include/js/jquery_plugins/fileTree/connectors/jqueryFileTree.php'
		}, function(file) {
			if (file && file.indexOf('/') == 0) file = file.substr(1);
			jQuery('#dimport_csvpath').val(file);
			me.hideFloatingDiv('dimport_div_filechooser')
		});
	},
	
	validateStep: function(step) {
		var me = this,
		fname = 'validateStep'+step;
		
		if (typeof me[fname] == 'function') {
			return me[fname]();
		}
		return true;
	},
	
	saveImport: function() {
		var me = this;
		
		jQuery('#data_importer_savedata').val('1');
		jQuery('#skip_vte_header').val('true');
		me.gotoNextStep();
	},
	
	displayError: function(text) {
		var me = this;
		
		var div = jQuery('#dimport_error_box');
		var oldbg = div.css('background-color') || '#ffffff';
		
		// set the text and show it
		div.text(text).show();
		
		// don't animate if already doing it
		if (me.displayErrorAnimation) return false;
		
		// animate the background
		me.displayErrorAnimation = true;
		div.css({
			'background-color': '#ff7070',
		});
		jQuery('#mmaker_error_box').animate({
			'background-color': oldbg,
		}, {
			duration: 600,
			complete: function() {
				me.displayErrorAnimation = false;
			}
		});
		return false;
	},
	
	hideError: function() {
		jQuery('#dimport_error_box').hide().text('');
	},
	
	openLogsPopup: function(importid) {
		var me = this;
		jQuery('#dimport_logs_moduleid').val(importid);
		
		me.showFloatingDiv('dimport_div_logs');	
		me.ajaxCall('getlog', {importid: importid}, function(data) {
			if (data && data.log) {
				jQuery('#dimport_log_text').val(data.log);
			} else {
				jQuery('#dimport_log_text').val('');
			}
		});
		
	},
	
	runNow: function(importid) {
		var me = this;
		me.ajaxCall('run', {importid: importid}, function(data) {
			alert(alert_arr.LBL_DATA_IMPORT_SCHEDULED_NOW);
			me.gotoList();
		});
	},
	
	abortImport: function(importid) {
		var me = this;
		me.ajaxCall('abort', {importid: importid}, function(data) {
			alert(alert_arr.LBL_DATA_IMPORT_ABORTED);
			me.gotoList();
		});
	},
	
	validateStep1: function() {
		var me = this;
		var module = jQuery('#dimport_module').val();
		if (!module) {
			alert(alert_arr.LBL_PLEASE_SELECT_MODULE);
			return false;
		}
		
		if (module == 'ProductRows') {
			var invmodule = jQuery('#dimport_invmodule').val();
			if (!invmodule) {
				alert(alert_arr.LBL_PLEASE_SELECT_MODULE);
				return false;
			}
		}
		return true;
	},
	
	step1_onModuleSelect: function() {
		var me = this;
		var mod = jQuery('#dimport_module').val();
		var invmodRow = jQuery('#dimport_cell_invmodule');
		
		if (mod == 'ProductRows') {
			invmodRow.show();
		} else {
			invmodRow.hide();
		}
	},
	
	validateStep2: function() {
		var me = this;
		var type = 
			jQuery('#dimport_sourcetype_db:checked').val() ||
			jQuery('#dimport_sourcetype_csv:checked').val();

		if (!type) {
			alert(alert_arr.LBL_PLEASE_SELECT_VALUE);
			return false;
		}
		
		return true;
	},
	
	validateStep3: function() {
		var me = this;
		
		var srcType = jQuery('#sourcetype').val();
		
		if (srcType == 'database') {
			return me.validateStep3_database();
		} else if (srcType == 'csv') {
			return me.validateStep3_csv();
		}
		return true;
	},
	
	validateStep3_database: function() {
		var me = this;
		
		var dbtype = jQuery('#dimport_dbtype').val();
		var dbhost = jQuery('#dimport_dbhost').val().trim();
		var dbport = jQuery('#dimport_dbport').val().trim();
		var dbuser = jQuery('#dimport_dbuser').val();
		var dbpass = jQuery('#dimport_dbpass').val();
		var dbname = jQuery('#dimport_dbname').val();
		
		if (!dbtype || !dbhost || !dbuser || dbport == '' || !dbname) {
			alert(alert_arr.LBL_FILL_ALL_FIELDS);
			return false;
		}
		
		var validHostnameRegex = /^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-\\]*[A-Za-z0-9])$/; // crmv@77830
		var validIpAddressRegex = /^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/;
		
		// remove ssl from host name
		dbhost = dbhost.replace(/^ssl:\/\//, ''); // crmv@193619
		
		if (!dbhost.match(validHostnameRegex) && !dbhost.match(validIpAddressRegex)) {
			var label = jQuery('#dimport_dbhost').closest('tr').find('td span').text();
			alert(alert_arr.LBL_FIELD_IS_INVALID.replace('%s', label));
			return false;
		}
		
		if (dbport.match(/[^0-9]/)) {
			var label = jQuery('#dimport_dbport').closest('tr').find('td span').text();
			alert(alert_arr.LBL_FIELD_IS_NUMERIC.replace('%s', label));
			return false;
		}
		
		return true;
	},
	
	validateStep3_csv: function() {
		var me = this;
		
		var path = jQuery('#dimport_csvpath').val().trim();
		
		if (!path) {
			alert(alert_arr.LBL_FILL_ALL_FIELDS);
			return false;
		}
		
		if (path[0] == '/' || path.match(/[a-z]:\\/i)) {
			alert(alert_arr.LBL_CSVPATH_MUST_NOT_BE_ABSOLUTE);
			return false;
		}
				
		return true;
	},
	
	step3_changedbType: function() {
		var me = this;
		var dbtype = jQuery('#dimport_dbtype').val();
		var port = parseInt(jQuery('#dimport_dbport').val()) || 0;
		
		if (dbtype) {
			jQuery('#dimport_dbport').val(me.dbports[dbtype]);
		}
		
	},
	
	validateStep4: function() {
		var me = this;
		var tab = jQuery('#dimport_dbtable').val();
		var q = me.getEditorText();

		if (q) q = q.trim();
		
		if (!tab && !q) {
			alert(alert_arr.LBL_FILL_ALL_FIELDS);
			return false;
		}
		
		if (q && tab) {
			alert(alert_arr.LBL_SELECT_TABLE_OR_QUERY);
			return false;
		}
		
		return true;
	},
	
	validateStep5: function() {
		var me = this;
		var module = DataImporterVars['module'];
		
		// get mandatory list
		var mandatoryFields = [];
		for (fname in DataImporterVars['fields']) {
			if (DataImporterVars['fields'][fname]['mandatory']) mandatoryFields.push(fname);
		}
		
		// check if not all mandatory fields are mapped
		var mappedFields = [];
		var importFields = [];
		var defFields = [];
		
		var exit = false;
		var table = jQuery('#dimport_table_mapping');
		table.find('select').each(function(index, el) {
			if (el && el.id.match(/^dimport_map_(.*)_field$/)) {
				var val = jQuery(el).val();
				if (val) {
					if (importFields.indexOf(val) != -1) {
						// same field twice!
						alert(alert_arr.LBL_FILTER_FIELD_MORE_THAN_ONCE);
						exit = true;
						return false;
					}
					mappedFields.push(val);
					importFields.push(val);
				}
			}
		});
		if (exit) return false;
		
		// add also the defaults in creation
		exit = false;
		var deftable = jQuery('#dimport_table_dfield_c');
		deftable.find('select').each(function(index, el) {
			if (el && el.id.match(/^dimport_dfield_c_(.*)_field$/)) {
				var val = jQuery(el).val();
				if (val) {
					if (defFields.indexOf(val) != -1) {
						// same field twice!
						alert(alert_arr.LBL_FILTER_FIELD_MORE_THAN_ONCE);
						exit = true;
						return false;
					}
					// a mapped field can't appear in default fields
					if (importFields.indexOf(val) != -1) {
						// same field twice!
						alert(alert_arr.LBL_CANT_USE_DEFAULT_MAPPED_FIELD);
						exit = true;
						return false;
					}
					mappedFields.push(val);
					defFields.push(val);
				}
			}
		});
		if (exit) return false;
		
		// some check for the defaults
		exit = false;
		var deftable = jQuery('#dimport_table_dfield_u');
		deftable.find('select').each(function(index, el) {
			if (el && el.id.match(/^dimport_dfield_u_(.*)_field$/)) {
				var val = jQuery(el).val();
				if (val) {
					if (defFields.indexOf(val) != -1) {
						// same field twice!
						alert(alert_arr.LBL_FILTER_FIELD_MORE_THAN_ONCE);
						exit = true;
						return false;
					}
					if (importFields.indexOf(val) != -1) {
						// same field twice!
						alert(alert_arr.LBL_CANT_USE_DEFAULT_MAPPED_FIELD);
						exit = true;
						return false;
					}
					// this is commented on purpouse
					//mappedFields.push(val);
					defFields.push(val);
				}
			}
		});
		if (exit) return false;
		
		// now check
		for (var i=0; i<mandatoryFields.length; ++i) {
			var fname = mandatoryFields[i];
			if (mappedFields.indexOf(fname) < 0) {
				alert(alert_arr.MISSING_REQUIRED_FIELDS.replace(':', ''));
				return false;
			}
		}
		
		// conferma se nessun campo chiave è selezionato
		var keyfields = jQuery('.dimport_map_keycol:checked');
		if (keyfields.length == 0 && module != 'ProductRows') {
			return confirm(alert_arr.LBL_CONTINUE_WITHOUT_KEY_FIELD);
		}
		
		return true;
	},
	
	validateStep6: function() {
		return DataImporterSched.validate();
	},
	
	centerFloatingDiv: function(div) {
		var me = this;
		var divobj = me.getJQueryObject(div);
		
		divobj.css("top", Math.max(0, ((jQuery(window).height() - divobj.outerHeight()) / 2) + jQuery(window).scrollTop()) + "px");
		divobj.css("left", Math.max(0, ((jQuery(window).width() - divobj.outerWidth()) / 2) + jQuery(window).scrollLeft()) + "px");
	},
	
	getJQueryObject: function(ref) {
		if (typeof ref == 'string') {
			ref = '#'+ref;
		}
		return jQuery(ref);
	},
	
	showFloatingDiv: function(div) {
		var me = this;
		
		if (me.activeFloatingDiv) return;
		
		var divobj = me.getJQueryObject(div);		
		
		divobj.show();
        placeAtCenter(divobj.get(0)); // crmv@189795
	},
	
	hideFloatingDiv: function(div) {
		var me = this;
		var divobj = me.getJQueryObject(div);		
		
		divobj.hide();
		me.activeFloatingDiv = null;
	},
	
}

var DataImporterFields = {
	
	onFieldChange: function(col) {
		var me = this;
		
		me.alignSelfReference(col); // crmv@90287
		me.alignReference(col);
		me.alignDefault(col);
		me.alignFormat(col);
		me.alignFormula(col);
		me.alignKey(col);
	},
	
	onDefFieldChange: function(col, type) {
		var me = this;
		
		me.alignDefDefault(col, type);
	},
	
	alignDefault: function(col, defvalue) {
		var me = this;
		
		// prepare the default field
		var fieldName = jQuery('#dimport_map_'+col+'_field').val();
		var cont = jQuery('#dimport_cell_'+col+'_default');
		var copyOfDefaultValueWidget = jQuery('#'+fieldName+'_defaultvalue_container').clone();
		// set the default
		copyOfDefaultValueWidget.find('input').attr('name', 'dimport_map_'+col+'_default').val(defvalue);
		copyOfDefaultValueWidget.find('input[type=checkbox]').attr('name', 'dimport_map_'+col+'_default').prop('checked',!!defvalue).val(1); //crmv@93741
		copyOfDefaultValueWidget.find('select').attr('name', 'dimport_map_'+col+'_default').val(defvalue);
		// insert it in the DOM
		cont.empty().append(copyOfDefaultValueWidget);
	},
	
	alignDefDefault: function(col, type, defvalue) {
		var me = this;
		var strtype = (type == 'create' ? 'c' : 'u');
		
		// prepare the default field
		var fieldName = jQuery('#dimport_dfield_'+strtype+'_'+col+'_field').val();
		var cont = jQuery('#dimport_cell_'+strtype+'_'+col+'_default');
		var copyOfDefaultValueWidget = jQuery('#'+fieldName+'_defaultvalue_container').clone();
		// set the default
		copyOfDefaultValueWidget.find('input').attr('name', 'dimport_dfield_'+strtype+'_'+col+'_default').val(defvalue);
		copyOfDefaultValueWidget.find('input[type=checkbox]').attr('name', 'dimport_dfield_'+strtype+'_'+col+'_default').prop('checked',!!defvalue).val(1); //crmv@93741
		copyOfDefaultValueWidget.find('select').attr('name', 'dimport_dfield_'+strtype+'_'+col+'_default').val(defvalue);
		// insert it in the DOM
		cont.empty().append(copyOfDefaultValueWidget);
	},
	
	arrayFilter: function(arr) {
		if (arr && typeof arr.filter == 'function') {
			// ES6
			arr = arr.filter(function(n){ return !!n; }); 
		} else if (arr && arr.length > 0) {
			// old js
			var tmp = [], 
				len = arr.length;
			for (var i = 0; i < len; ++i) arr[i] && tmp.push(arr[i]);
			arr = tmp;
		}
		return arr || [];
	},
	
	//crmv@90287
	alignSelfReference: function(col) {
		var me = this;
		
		var module = DataImporterVars['module'];
		var fieldName = jQuery('#dimport_map_'+col+'_field').val();
		var finfo = DataImporterVars['fields'][fieldName];
		var select = jQuery('#dimport_map_'+col+'_reference');
		var keylabel = jQuery('#dimport_map_'+col+'_reference_keylabel');
		
		if (fieldName && finfo && (finfo.uitype == 10 || finfo.type == 'reference')) {
			var action = false;
			var showlabel = false;
			
			// if it's related to itself (eg. member of field), add the key field
			if (finfo.references && finfo.references.length > 0 && finfo.references.indexOf(module) >= 0) {
				// check if we already have a key field
				var keyfield = me.getKeyField();
				if (keyfield) {
					action = 'add';
				} else {
					action = 'remove';
					showlabel = true;
				}
			} else {
				action = 'remove';
			}
			
			// first remove all
			select.find('option[value^='+module+']').remove();
			
			// then add
			if (action == 'add') {
				var flabel = me.getFieldLabel(keyfield),
					mlabel = DataImporterVars['modulelabel'],
					okey = module+':'+keyfield;
					
				if (select.find('option[value='+okey+']').length == 0) {
					select.append(jQuery('<option>', {
						value: okey,
						text: mlabel+': '+flabel,
					}));
				}
			} else if (action == 'remove') {
				// nothing
			}
			
			keylabel[showlabel ? 'show' : 'hide']();
			
		}
	},
	
	alignAllSelfReference: function() {
		var me = this;
		
		// find all self-reference fields
		var fields = jQuery('select[id$=_field]').each(function(idx, fld) {
			var val = jQuery(fld).val();
			if (val) {
				var col = jQuery(fld).attr('id').replace('dimport_map_', '').replace('_field', '');
				me.alignSelfReference(col);
			}
		});
	},
	
	//crmv@90287e
	
	alignReference: function(col, defvalue) {
		var me = this;
		
		var fieldName = jQuery('#dimport_map_'+col+'_field').val();
		var finfo = DataImporterVars['fields'][fieldName];
		var select = jQuery('#dimport_map_'+col+'_reference');
		var label = jQuery('#dimport_map_'+col+'_reference_label');
		
		if (fieldName && finfo && (finfo.uitype == 10 || finfo.type == 'reference')) {
			// crmv@90287
			// disable the ones with wrong modules
			if (finfo.references && finfo.references.length > 0) {
				select.find('option').each(function(idx, opt) {
					var val = jQuery(opt).attr('value'),
						mod = val.split(':')[0];
					if (val && mod) {
						if (finfo.references.indexOf(mod) >= 0) {
							jQuery(opt).removeAttr('disabled');
						} else {
							jQuery(opt).attr('disabled', 'disabled');
						}
					}
				});
			} else {
				// enable all
				select.find('option').removeAttr('disabled');
			}
			// crmv@90287e
			select.val(defvalue || '');
			select.show();
			label.show();
		} else {
			// remove all
			label.hide();
			select.hide();
			select.val('');
			//jQuery('option', select).hide();
			//jQuery('#dimport_map_'+col+'_srcformatval').hide();
		}
	},
	
	// crmv@117880
	alignFormat: function(col, setformat, value, listvalue) {
		var me = this;

		var fieldName = jQuery('#dimport_map_'+col+'_field').val();
		var finfo = DataImporterVars['fields'][fieldName];
		var select = jQuery('#dimport_map_'+col+'_srcformat');

		if (fieldName && finfo) {
			var enabledOpts = [];
			// select the valid options
			for (format in DataImporterVars['formats']) {
				var forTypes = DataImporterVars['formats'][format].fortypes;
				var forUitypes = DataImporterVars['formats'][format].foruitypes;
				var hasValue = DataImporterVars['formats'][format].hasvalue;
				var hasValues = DataImporterVars['formats'][format].hasvalues;
				
				if (typeof forTypes == 'string') forTypes = [forTypes];
				if (typeof forUitypes == 'string') forUitypes = [forUitypes];
				forTypes = me.arrayFilter(forTypes);
				forUitypes = me.arrayFilter(forUitypes);

				if (forTypes.length > 0 || forUitypes.length > 0) {
					// filter by [ui]type
					if (forTypes.indexOf(finfo.type) >= 0 || forUitypes.indexOf(finfo.uitype) >= 0) {
						// show it
						jQuery('option[value='+format+']', select).show();
						jQuery('#dimport_map_'+col+'_srcformatval')[hasValue ? 'show' : 'hide']();
						jQuery('#dimport_map_'+col+'_srcformatlist')[hasValues ? 'show' : 'hide']();
						enabledOpts.push(format);
					} else {
						// hide it
						jQuery('option[value='+format+']', select).hide();
						jQuery('#dimport_map_'+col+'_srcformatval').hide();
						jQuery('#dimport_map_'+col+'_srcformatlist').hide();
					}
						
				} else {
					// always visible
					jQuery('option[value='+format+']', select).show();
					jQuery('#dimport_map_'+col+'_srcformatval')[hasValue ? 'show' : 'hide']();
					jQuery('#dimport_map_'+col+'_srcformatlist')[hasValues ? 'show' : 'hide']();
					enabledOpts.push(format);
				}
				// set the options
				if (hasValues && DataImporterVars['formats'][format].values) {
					var opts = '';
					jQuery.each(DataImporterVars['formats'][format].values, function(v, lab) {
						opts += "<option value="+v+">"+lab+"</option>";
					});
					jQuery('#dimport_map_'+col+'_srcformatlist').html(opts);
				}
			}
			// set the specified format and value
			setformat = setformat || "";
			value = value || "";
			listvalue = listvalue || "";
			if (setformat && enabledOpts.indexOf(setformat) >= 0) {
				// something selected
				var format = DataImporterVars['formats'][setformat];
				jQuery('#dimport_map_'+col+'_srcformatval')[format.hasvalue ? 'show' : 'hide']();
				jQuery('#dimport_map_'+col+'_srcformatlist')[format.hasvalues ? 'show' : 'hide']();
			} else {
				// nothing selected
				setformat = "";
				value = "";
				listvalue = "";
				jQuery('#dimport_map_'+col+'_srcformatval').hide();
				jQuery('#dimport_map_'+col+'_srcformatlist').hide();
			}
			select.val(setformat);
			
			select[(setformat || enabledOpts.length > 0) ? 'show' : 'hide']();
			jQuery('#dimport_map_'+col+'_srcformatval').val(value);
			jQuery('#dimport_map_'+col+'_srcformatlist').val(listvalue);

		} else {
			// remove all
			select.hide();
			select.val('');
			jQuery('option', select).hide();
			jQuery('#dimport_map_'+col+'_srcformatval').hide();
			jQuery('#dimport_map_'+col+'_srcformatlist').hide();
		}
	},
	// crmv@117880e
	
	alignFormula: function(col, setformula, value) {
		var me = this;
		
		var fieldName = jQuery('#dimport_map_'+col+'_field').val();
		var finfo = DataImporterVars['fields'][fieldName];
		var format = jQuery('#dimport_map_'+col+'_srcformat').val();
		var select = jQuery('#dimport_map_'+col+'_formula');

		if (fieldName && finfo) {
			var enabledOpts = [];
			// select the valid options
			for (formula in DataImporterVars['formulas']) {
				var forTypes = DataImporterVars['formulas'][formula].fortypes;
				var forUitypes = DataImporterVars['formulas'][formula].foruitypes;
				var forFormats = DataImporterVars['formulas'][formula].forformats || [];
				var hasValue = DataImporterVars['formulas'][formula].hasvalue;
				
				if (typeof forTypes == 'string') forTypes = [forTypes];
				if (typeof forUitypes == 'string') forUitypes = [forUitypes];
				if (typeof forFormats == 'string') forFormats = [forFormats];
				forTypes = me.arrayFilter(forTypes);
				forUitypes = me.arrayFilter(forUitypes);
				forFormats = me.arrayFilter(forFormats);
				if (forTypes.length > 0 || forUitypes.length > 0 || forFormats.length > 0) {
					// filter by [ui]type
					if (forTypes.indexOf(finfo.type) >= 0 || forUitypes.indexOf(finfo.uitype) >= 0 || forFormats.indexOf(format) >= 0) {
						// show it
						jQuery('option[value='+formula+']', select).show();
						jQuery('#dimport_map_'+col+'_formulaval')[hasValue ? 'show' : 'hide']();
						enabledOpts.push(formula);
					} else {
						// hide it
						jQuery('option[value='+formula+']', select).hide();
						jQuery('#dimport_map_'+col+'_formulaval').hide();
					}
						
				} else {
					// always visible
					jQuery('option[value='+formula+']', select).show();
					jQuery('#dimport_map_'+col+'_formulaval')[hasValue ? 'show' : 'hide']();
					enabledOpts.push(formula);
				}
			}
			// set the specified formula and value
			setformula = setformula || "";
			value = value || "";
			if (setformula && enabledOpts.indexOf(setformula) >= 0) {
				// something selected
				var format = DataImporterVars['formulas'][setformula];
				jQuery('#dimport_map_'+col+'_formulaval')[format.hasvalue ? 'show' : 'hide']();
			} else {
				// nothing selected
				setformula = "";
				value = "";
				jQuery('#dimport_map_'+col+'_formulaval').hide();
			}
			select.val(setformula);
			
			select[(setformula || enabledOpts.length > 0) ? 'show' : 'hide']();
			jQuery('#dimport_map_'+col+'_formulaval').val(value);
			
			//crmv@93655
			if(finfo.uitype == 117 && finfo.type == 'integer'){
				// remove all
				select.hide();
				select.val('');
				jQuery('option', select).hide();
				jQuery('#dimport_map_'+col+'_formulaval').hide();
			}
			//crmv@93655e

		} else {
			// remove all
			select.hide();
			select.val('');
			jQuery('option', select).hide();
			jQuery('#dimport_map_'+col+'_formulaval').hide();
		}
	},
	
	onSelectFormat: function(col) {
		var me = this;
		
		var fieldName = jQuery('#dimport_map_'+col+'_field').val();
		var finfo = DataImporterVars['fields'][fieldName];
		var select = jQuery('#dimport_map_'+col+'_srcformat');
		var format = select.val(),
			foinfo = DataImporterVars['formats'][format];
		
		jQuery('#dimport_map_'+col+'_srcformatval').val('');
		if (format && foinfo) {
			if (foinfo.hasvalue) {
				jQuery('#dimport_map_'+col+'_srcformatval').show();
				if (foinfo.value) {
					jQuery('#dimport_map_'+col+'_srcformatval').val(foinfo.value);
				}
			}
			// crmv@117880
			if (foinfo.hasvalues) {
				jQuery('#dimport_map_'+col+'_srcformatlist').show();
				if (foinfo.values) {
					var opts = '';
					jQuery.each(foinfo.values, function(value, label) {
						opts += "<option value="+value+">"+label+"</option>";
					});
					jQuery('#dimport_map_'+col+'_srcformatlist').html(opts);
				}
			}
			// crmv@117880e
		} else {
			jQuery('#dimport_map_'+col+'_srcformatval').hide();
			jQuery('#dimport_map_'+col+'_srcformatlist').hide(); // crmv@117880e
		}
		// and align the formula as well
		me.alignFormula(col);
	},
	
	onSelectFormula: function(col) {
		var me = this;
		
		var fieldName = jQuery('#dimport_map_'+col+'_field').val();
		var finfo = DataImporterVars['fields'][fieldName];
		var select = jQuery('#dimport_map_'+col+'_formula');
		var formula = select.val(),
			foinfo = DataImporterVars['formulas'][formula];
		
		jQuery('#dimport_map_'+col+'_formulaval').val('');
		if (formula && foinfo) {
			if (foinfo.hasvalue) {
				jQuery('#dimport_map_'+col+'_formulaval').show();
				if (foinfo.value) {
					jQuery('#dimport_map_'+col+'_formulaval').val(foinfo.value);
				}
			}
		} else {
			jQuery('#dimport_map_'+col+'_formulaval').hide();
		}
	},
	
	alignKey: function(col, checked) {
		var me = this;
		
		var checkbox = jQuery('#dimport_map_'+col+'_keycol');
		var fieldName = jQuery('#dimport_map_'+col+'_field').val();
		
		if (fieldName) {
			// crmv@203591
			var wstype = DataImporterVars.fields[fieldName].type;

			if (wstype == 'table' || fieldName.match(/^prodattr_/)) {
				checkbox.prop('checked', false);
				checkbox.attr('disabled', true);
			} else {
				// ok, enable it and set the value
				checkbox.attr('disabled', false);
				checkbox.prop('checked', !!checked);
			}
			// crmv@203591e
			
			me.alignAllSelfReference(); // crmv@90287
			
		} else {
			// disable it, remove the value
			checkbox.prop('checked', false);
			checkbox.attr('disabled', true);
		}
	},
	
	onKeyFieldCheck: function(col) {
		var me = this;
		
		var checkbox = jQuery('#dimport_map_'+col+'_keycol');
		var checked = checkbox.is(':checked');
		
		// remove checked from other checkboxes
		if (checked) {
			jQuery('.dimport_map_keycol').each(function(index, el) {
				if (el.id != 'dimport_map_'+col+'_keycol') {
					el.checked = false;
				}
			});
			jQuery('#dimport_mapping_keycol').val(col);
		} else {
			jQuery('#dimport_mapping_keycol').val('');
		}
		
		me.alignAllSelfReference(); // crmv@90287
		
		return true;
	},
	
	// crmv@90287
	getKeyField: function() {
		var me = this,
			field = null;
		
		var checkbox = jQuery('input[type=checkbox][id$=_keycol]:checked');
		if (checkbox.length > 0) {
			var id = checkbox.attr('id');
			var col = id.replace('dimport_map_', '').replace('_keycol', '');
			field = jQuery('#dimport_map_'+col+'_field').val();
		}
		
		return field;
	},
	
	getFieldLabel: function(fieldname) {
		var me = this,
			label = fieldname;
		
		if (DataImporterVars && DataImporterVars['fields']) {
			for (fname in DataImporterVars['fields']) {
				if (!DataImporterVars['fields'].hasOwnProperty(fname)) continue;
				if (fname == fieldname) {
					label = DataImporterVars['fields'][fname].label;
					break;
				}
			}
		}
		
		return label;
	},
	// crmv@90287e
	
	addDefaultField: function(type) {
		var me = this;
		
		DataImporter.ajaxCall('add_default_field', {type:type}, function(data) {
			jQuery('#dimport_div_deffields').html(data);
		}, {
			includeForm: true,
			jsonData: false,
			callbackOnError: false,
		});
		
	},
	
	removeDefaultField: function(type, fieldno) {
		var me = this;
		
		DataImporter.ajaxCall('del_default_field', {type:type, fieldno:fieldno}, function(data) {
			jQuery('#dimport_div_deffields').html(data);
		}, {
			includeForm: true,
			jsonData: false,
			callbackOnError: false,
		});
		
	},
}

var DataImporterSched = {
	
	onEveryKey: function() {
		var me = this;
		var val = jQuery('#dimport_sched_every').val();
		
		if (val == '1') {
			// singular
			jQuery('#dimport_sched_every_day').text(DataImporterVars['sched']['labels']['day']);
			jQuery('#dimport_sched_every_hour').text(DataImporterVars['sched']['labels']['hour']);
			jQuery('#dimport_sched_every_minute').text(DataImporterVars['sched']['labels']['minute']);
		} else {
			// plural
			jQuery('#dimport_sched_every_day').text(DataImporterVars['sched']['labels']['days']);
			jQuery('#dimport_sched_every_hour').text(DataImporterVars['sched']['labels']['hours']);
			jQuery('#dimport_sched_every_minute').text(DataImporterVars['sched']['labels']['minutes']);
		}
	},
	
	onIntervalChange: function() {
		var me = this;
		var val = jQuery('#dimport_sched_everywhat').val();
		
		if (val == 'minute') {
			// hide next box
			jQuery('#dimport_cell_atlabel').hide();
			jQuery('#dimport_cell_at').hide();
			jQuery('#dimport_sched_at').val('');
		} else {
			// show box
			var txt = DataImporterVars['sched']['labels'][val == 'day' ? 'at_hour' : 'at_minute'];
			jQuery('#dimport_sched_atlabel').text(txt);
			jQuery('#dimport_sched_at').val(DataImporterVars['sched'][val == 'day' ? 'default_hour' : 'default_minute']);
			jQuery('#dimport_cell_atlabel').show();
			jQuery('#dimport_cell_at').show();
		}
	},
	
	validateHour: function(str) {
		if (!str.match(/^[0-9][0-9]:[0-9][0-9]$/)) return false;
		var split = str.split(':');
		var h = parseInt(split[0]);
		var m = parseInt(split[1]);
		
		if (h < 0 || h > 23) return false;
		if (m < 0 || m > 59) return false;
		return true;
	},
	
	validate: function() {
		var me = this,
			every = parseInt(jQuery('#dimport_sched_every').val()) || 0,
			everywhat = jQuery('#dimport_sched_everywhat').val(),
			everyat = jQuery('#dimport_sched_at').val();
		
		// check emptyness
		if (!every || !everywhat) {
			alert(alert_arr.LBL_FILL_ALL_FIELDS);
			return false;
		}
		if (everywhat != 'minute' && !everyat) {
			alert(alert_arr.LBL_FILL_ALL_FIELDS);
			return false;
		}
		
		// check ranges
		if (everywhat == 'day') {
			if (every < 1) { alert(alert_arr.LBL_VALUE_TOO_SMALL+': '+every); return false;}
			if (every > 30) { alert(alert_arr.LBL_VALUE_TOO_BIG+': '+every); return false;}
			if (!me.validateHour(everyat)) { alert(alert_arr.LBL_INVALID_VALUE+': '+everyat); return false;}
		} else if (everywhat == 'hour') {
			everyat = parseInt(everyat);
			if (every < 1) { alert(alert_arr.LBL_VALUE_TOO_SMALL+': '+every); return false;}
			if (every > 48) { alert(alert_arr.LBL_VALUE_TOO_BIG+': '+every); return false;}
			if (everyat < 0 || everyat > 59) { alert(alert_arr.LBL_INVALID_VALUE+': '+everyat); return false;}
		} else if (everywhat == 'minute') {
			if (every < 1) { alert(alert_arr.LBL_VALUE_TOO_SMALL+': '+every); return false;}
			if (every > 120) { alert(alert_arr.LBL_VALUE_TOO_BIG+': '+every); return false;}
		}
		
		return true;
	}
}