/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@92272 crmv@96450 crmv@108078 crmv@115268 crmv@118977 crmv@127048 crmv@153321_5 crmv@160843 */
 
if (typeof(ActionTaskScript) == 'undefined') {
	ActionTaskScript = {
			
		enable_cache: true,
		log_time: false,	// log in console the time of loading pages
		
		// internal variables
		__cache: {},
		__describe_object_cache:{},
		//__describeObjectCount: 0,
		__overwriteCache: false,
	
		init: function(id){
			var context = jQuery('form[shape-id="'+id+'"]');
			var newTaskPopup = NewTaskPopup(jQuery,context);
			jQuery("#new_task",context).click(function(){
				newTaskPopup.show();
			});
		},
		
		// crmv@102879
		changeActionType: function(self) {
			var value = jQuery(self).val();
			
			jQuery('#new_task_cycle').hide();
			jQuery('#new_task_inserttablerow').hide();
			jQuery('#new_task_insertproductrow').hide(); // crmv@195745

			jQuery('#new_task_cycle_related').hide();
			jQuery('#new_task_inserttablerow_related').hide();
			jQuery('#new_task_insertproductrow_related').hide(); // crmv@195745
			if (value == 'Cycle' ) {
				jQuery('#new_task_cycle').show();
				this.changeCycleField(document.getElementById('table_fields')); // crmv@195745
			}
			//crmv@203075
			else if(value == 'CycleRelated')
			{
				jQuery('#new_task_cycle_related').show();
				this.changeCycleField(document.getElementById('table_fields_related')); // crmv@195745
			}
			//crmv@203075e
			else if (value == 'InsertTableRow') {
				jQuery('#new_task_inserttablerow').show();
			// crmv@195745
			} else if (value == 'InsertProductRow') {
				jQuery('#new_task_insertproductrow').show();
			}
			// crmv@195745e
		},
		
		// crmv@195745
		changeCycleField: function(self) {
			var value = jQuery(self).val(),
				valueExp = value.split(':'),
				relmod = valueExp[2],
				target = jQuery('#cycle_action_type');
			
			// delete or hide the Delete*Row action
			if (relmod === 'ProductsBlock') {
				target.find('option[value=DeleteTableRow]').hide();
				target.find('option[value=DeleteProductRow]').show();
			} else {
				target.find('option[value=DeleteTableRow]').show();
				target.find('option[value=DeleteProductRow]').hide();
			}
			
		},
		// crmv@195745e
		
		changeCycleActionType: function(self) {
			var value = jQuery(self).val();
			
			// crmv@195745
			if (value == 'InsertTableRow') {
				jQuery('#cycle_inserttablerow').show();
				jQuery('#cycle_insertpblockrow').hide();
			} else if (value == 'InsertProductRow') {
				jQuery('#cycle_inserttablerow').hide();
				jQuery('#cycle_insertpblockrow').show();
			} else {
				jQuery('#cycle_inserttablerow').hide();
				jQuery('#cycle_insertpblockrow').hide();
			}
			// crmv@195745e
		},
		
		editaction: function(processid,id,action_type,action_id, cycle_field, cycle_action, inserttablerow_field, insertproductrow_inventory_fields){	// popolare i nuovi campi dal tpl crmv@195745
			var me = this;
			
			if (!ProcessMakerScript.sessionCheck()) return false; // crmv@189903
			
			jQuery.fancybox.showLoading();
			ProcessMakerScript.saveMetadata(processid,id,'Action',function(){
				if (action_type.indexOf('SDK:') == 0) {
					var meta_action = {'action_type':'SDK','function':action_type.substring(4)};
					me.saveaction(processid,id,action_type,action_id,'',meta_action,function(){
						jQuery.fancybox.hideLoading();
					});
				} else {
					var url = 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=editaction&id='+processid+'&elementid='+id+'&action_type='+action_type+'&action_id='+action_id;
					if (action_type == 'Cycle') {
						// get cycle field and subaction
						url += '&cycle_field='+encodeURIComponent(cycle_field || jQuery('#table_fields').val());
						url += '&cycle_action='+encodeURIComponent(cycle_action || jQuery('#cycle_action_type').val());
						url += '&inserttablerow_field='+encodeURIComponent(inserttablerow_field || jQuery('#cycle_inserttablerow_table_fields').val());
						url += '&insertproductrow_inventory_fields='+encodeURIComponent(insertproductrow_inventory_fields || jQuery('#cycle_insertpblockrow_table_fields').val()); // crmv@195745
					}
					//crmv@203075
					else if (action_type == 'CycleRelated') {
						// get cycle field and subaction
						url += '&cycle_field='+encodeURIComponent(cycle_field || jQuery('#table_fields_related').val());
						url += '&cycle_action='+encodeURIComponent(cycle_action || jQuery('#cycle_action_type_related').val());
						url += '&inserttablerow_field='+encodeURIComponent(inserttablerow_field || jQuery('#cycle_inserttablerow_table_fields_related').val());
						url += '&insertproductrow_inventory_fields='+encodeURIComponent(insertproductrow_inventory_fields || jQuery('#cycle_insertpblockrow_table_fields_related').val()); // crmv@195745
					}
					//crmv@203075e
					else if (action_type == 'InsertTableRow') {
						url += '&inserttablerow_field='+encodeURIComponent(inserttablerow_field || jQuery('#inserttablerow_table_fields').val());					
					// crmv@195745
					} else if (action_type == 'InsertProductRow') {
						url += '&insertproductrow_inventory_fields='+encodeURIComponent(insertproductrow_inventory_fields || jQuery('#insertproductrow_inventory_fields').val());
					}
					// crmv@195745e
					window.location.href = url;
					jQuery.fancybox.hideLoading();
				}
			});
		},
		// crmv@102879e
		
		// crmv@104180
		saveaction: function(processid,id,action_type,action_id,action_title,meta_action,callback){
			if (!ProcessMakerScript.sessionCheck()) return false; // crmv@189903
			
			var context = top.jQuery('form[shape-id="'+id+'"]');
			if (typeof(meta_action) == 'undefined') var meta_action = {};
			jQuery.each(jQuery('#actionform').serializeArray(), function(){
				//crmv@166678
				if (this.name.indexOf('[]') > -1) {
					if (typeof(meta_action[this.name.replace('[]','')]) == 'undefined') meta_action[this.name.replace('[]','')] = [];
					meta_action[this.name.replace('[]','')].push(this.value);
				} else {
					meta_action[this.name] = this.value;
				}
				//crmv@166678e
			});
			if (typeof(CKEDITOR) != "undefined" && CKEDITOR.instances != undefined) {
				jQuery.each(CKEDITOR.instances,function(fldName,obj){
					var textObj = CKEDITOR.instances[fldName];
					meta_action[obj.element.getAttribute('name')] = textObj.getData();
				});
			}
			if (jQuery('#editForm').length > 0) {
				meta_action['form'] = {};
				jQuery.each(jQuery('#editForm').find('form[name="EditView"]').serializeArray(), function(){
					//crmv@166678
					if (this.name.indexOf('[]') > -1) {
						if (typeof(meta_action['form'][this.name.replace('[]','')]) != 'object') meta_action['form'][this.name.replace('[]','')] = []; // crmv@191206
						meta_action['form'][this.name.replace('[]','')].push(this.value);
					} else {
						meta_action['form'][this.name] = this.value;
					}
					//crmv@166678e
				});
				// crmv@204530
				if (typeof(CKEDITOR) != "undefined" && CKEDITOR.instances != undefined) {
					jQuery.each(CKEDITOR.instances,function(fldName,obj){
						var textObj = CKEDITOR.instances[fldName];
						meta_action['form'][obj.element.getAttribute('name')] = textObj.getData();
					});
				}
				// crmv@204530e
			}
			
			var object = jQuery('#actionform');
			if (jQuery('#save_conditions',object).length > 0) {
				var conditions = GroupConditions.getJson(jQuery, 'save_conditions', jQuery(object));
				meta_action['conditions'] = conditions;
			}
			
			// crmv@147433
			if (action_type == 'CallExtWS') {
				if (!ActionCallWSScript.validate(meta_action)) {
					if (typeof callback == 'function') callback();
					return false;
				}
			}
			// crmv@147433e
			
			var postdata = {
				meta_action: meta_action
			};
			
			jQuery.ajax({
				'url': 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=saveaction&id='+processid+'&elementid='+id+'&action_id='+action_id,
				'type': 'POST',
				'data': postdata,
				success: function(data) {
					ProcessMakerScript.reloadMetadata(processid,id);
					if (typeof callback == 'function') callback();
				}
			});
		},
		// crmv@104180e
		
		deleteaction: function(processid,id,action_id){
			if (!ProcessMakerScript.sessionCheck()) return false; // crmv@189903
			
			vteconfirm(alert_arr.ARE_YOU_SURE, function(yes) {
				if (yes) {
					jQuery.fancybox.showLoading();
					ProcessMakerScript.saveMetadata(processid,id,'Action',function(){
						jQuery.ajax({
							'url': 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=deleteaction&id='+processid+'&elementid='+id+'&action_id='+action_id,
							'type': 'POST',
							success: function(data) {
								ProcessMakerScript.reloadMetadata(processid,id);
								jQuery.fancybox.hideLoading();
							}
						});
					});
				}
			});
		},
		
		//crmv@106856
		openAdvancedFieldAssignment: function(processid,elementid,actionid,fieldname,form_module,open,reload_session){
			var url = 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=open_advanced_field_assignment&processid='+processid+'&elementid='+elementid+'&actionid='+actionid+'&form_module='+form_module+'&fieldname='+fieldname;
			if (open == 'popup') {
				url += '&storage='+jQuery('#advanced_field_assignment_button_'+fieldname).attr('storage');
				openPopup(url);
			} else if (open == 'parent') {
				jQuery.fancybox.showLoading();
				url += '&storage='+parent.parent.jQuery('#advanced_field_assignment_button_'+fieldname).attr('storage');
				parent.window.location.href = url;
			} else if (open == 'current') {
				jQuery.fancybox.showLoading();
				url += '&storage='+parent.jQuery('#advanced_field_assignment_button_'+fieldname).attr('storage');
				window.location.href = url;
			}
		},
		saveAdvancedFieldAssignment: function(processid,elementid,actionid,fieldname){
			var me = this;
			me.saveAdvancedFieldAssignmentValues(fieldname, function(){
				closePopup();
			});
		},
		closeAdvancedFieldAssignment: function(processid,elementid,actionid,fieldname){
			if (typeof(parent.jQuery('#advanced_field_assignment_button_'+fieldname).attr('restore-storage-db')) != 'undefined' && parent.jQuery('#advanced_field_assignment_button_'+fieldname).attr('restore-storage-db') == 'true') parent.jQuery('#advanced_field_assignment_button_'+fieldname).attr('storage','db');
			closePopup();
		},
		editAdvancedFieldAssignment: function(processid,elementid,actionid,fieldname,form_module,ruleid){
			var me = this;
			me.saveAdvancedFieldAssignmentValues(fieldname, function(){
				openPopup('index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=open_advanced_field_assignment_condition&processid='+processid+'&elementid='+elementid+'&actionid='+actionid+'&form_module='+form_module+'&fieldname='+fieldname+'&ruleid='+ruleid);
			});
		},
		deleteAdvancedFieldAssignment: function(processid,elementid,actionid,fieldname,form_module,ruleid){
			var me = this;
			me.saveAdvancedFieldAssignmentValues(fieldname, function(){
				jQuery.ajax({
					'url': 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=delete_advanced_field_assignment&processid='+processid+'&elementid='+elementid+'&actionid='+actionid+'&fieldname='+fieldname+'&ruleid='+ruleid,
					'type': 'POST',
					success: function(data) {
						me.openAdvancedFieldAssignment(processid,elementid,actionid,fieldname,form_module,'current',false);
					}
				});
			});
		},
		saveAdvancedFieldAssignmentValues: function(fieldname,callback){	// update values in session
			var form = {};
			jQuery.each(jQuery('form[name="EditView"]').serializeArray(), function(){
				form[this.name] = this.value;
			});
			var postdata = {
				form: JSON.stringify(form)
			}
			jQuery.ajax({
				'url': 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=save_advanced_field_assignment_values&fieldname='+fieldname,
				'type': 'POST',
				'data': postdata,
				success: function(data) {
					if (parent.jQuery('#advanced_field_assignment_button_'+fieldname).length > 0) {
						var advanced_field_assignment_button = parent.jQuery('#advanced_field_assignment_button_'+fieldname);
					} else if (parent.parent.jQuery('#advanced_field_assignment_button_'+fieldname) > 0) {
						var advanced_field_assignment_button = parent.parent.jQuery('#advanced_field_assignment_button_'+fieldname);
					}
					if (typeof(advanced_field_assignment_button) != 'undefined') {
						(jQuery(advanced_field_assignment_button).attr('storage') == 'db') ? jQuery(advanced_field_assignment_button).attr('restore-storage-db','true') : jQuery(advanced_field_assignment_button).attr('restore-storage-db','false');
						jQuery(advanced_field_assignment_button).attr('storage','session');
					}
					callback();
				}
			});
		},
		openAdvancedFieldAssignmentCondition: function(processid,elementid,actionid,fieldname,form_module){
			var me = this;
			me.saveAdvancedFieldAssignmentValues(fieldname, function(){
				openPopup('index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=open_advanced_field_assignment_condition&processid='+processid+'&elementid='+elementid+'&actionid='+actionid+'&form_module='+form_module+'&fieldname='+fieldname);
			});
		},
		closeAdvancedFieldAssignmentCondition: function(){
			closePopup();
		},
		saveAdvancedFieldAssignmentCondition: function(processid,elementid,actionid,fieldname,form_module,ruleid){
			var me = this;
			var postdata = {
				meta_record: jQuery('[name="moduleName"]').val(),
				conditions: GroupConditions.getJson(jQuery,'save_conditions',jQuery('form[shape-id="'+elementid+'"]'))
			};
			if (postdata.meta_record == '') {
				alert(alert_arr.LBL_PM_SELECT_ENTITY);
				return false;
			}
			if (postdata.conditions == '') {
				alert(alert_arr.LBL_LEAST_ONE_CONDITION);
				return false;
			}
			jQuery.ajax({
				'url': 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=save_advanced_field_assignment_condition&fieldname='+fieldname+'&ruleid='+ruleid,
				'type': 'POST',
				'data': postdata,
				success: function(data) {
					me.openAdvancedFieldAssignment(processid,elementid,actionid,fieldname,form_module,'parent',false);
					me.closeAdvancedFieldAssignmentCondition();
				}
			});
		},
		//crmv@106856e
		
		//crmv@113527 [deprecated]
		showSdkParamsInput: function(field, fieldname){
			if (jQuery('#other_'+fieldname).val() != '' && jQuery('#other_'+fieldname).val().indexOf('$sdk:') > -1 && jQuery('#sdk_params_'+fieldname).val() != '') {
				jQuery('#other_'+fieldname).val(jQuery('#other_'+fieldname).val().replace('()','('+jQuery('#sdk_params_'+fieldname).val()+')'));
				jQuery('#sdk_params_'+fieldname).val('');
			}
		},
		//crmv@113527e
		
		calendarDateOptions: function(value,field) {
			if (value == '' || value == null) {
				jQuery('[name="'+field+'"]').hide();
				jQuery('#jscal_trigger_'+field).hide();
				jQuery('#'+field+'_adv_options').hide();
				jQuery('.editoptions[fieldname="'+field+'_opt_num"]').hide();
			} else if (value == 'custom') {
				jQuery('[name="'+field+'"]').show();
				jQuery('#jscal_trigger_'+field).show();
				jQuery('#'+field+'_adv_options').hide();
				jQuery('.editoptions[fieldname="'+field+'_opt_num"]').hide();
			} else {
				jQuery('[name="'+field+'"]').hide();
				jQuery('#jscal_trigger_'+field).hide();
				jQuery('#'+field+'_adv_options').show();
			}
		},
		calendarTimeOptions: function(value,field) {
			if (value == '' || value == null) {
				jQuery('#'+field+'_custom').hide();
				jQuery('#'+field+'_adv_options').hide();
				jQuery('.editoptions[fieldname="'+field+'_opt_num"]').hide();
			} else if (value == 'custom') {
				jQuery('#'+field+'_custom').show();
				jQuery('#'+field+'_adv_options').hide();
				jQuery('.editoptions[fieldname="'+field+'_opt_num"]').hide();
			} else {
				jQuery('#'+field+'_custom').hide();
				jQuery('#'+field+'_adv_options').show();
			}
		},
		
		//crmv@113775
		loadPotentialRelations: function(record) {
			if (record == '') {
				jQuery('#record2_container').html('');
			} else {
				jQuery.ajax({
					'url': 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=load_potential_relations&record1='+record+'&id='+jQuery('[name="id"]').val()+'&elementid='+jQuery('[name="elementid"]').val(),
					'type': 'POST',
					success: function(data) {
						jQuery('#record2_container').html(data);
					}
				});
			}
		},
		//crmv@113775e
		
		//crmv@185548
		loadEntityRelations: function(record,mode) {
			jQuery('#modules_list_container').html('');
			jQuery('#linkRecordSelect2').html('');
			if (record == '') {
				jQuery('#record2_container').html('');
			} else {
				jQuery.ajax({
					'url': 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=load_entity_relations&record1='+record+'&id='+jQuery('[name="id"]').val()+'&elementid='+jQuery('[name="elementid"]').val(),
					'type': 'POST',
					success: function(data) {
						if(mode == 'create'){
							jQuery('#record2_container').html(data);
						}
						else{
							jQuery('#record_container2').html(data);
						}
					}
				});
			}
		},
		reloadModuleList: function(record,entity_mode) {
			if (record == '') {
				jQuery('#modules_list_container').html('');
			} else {
				jQuery.ajax({
					'url': 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&entity_mode='+entity_mode+'&mode=reload_module_list&record1='+jQuery('#linkRecordSelect1').val()+'&record2='+record+'&id='+jQuery('[name="id"]').val()+'&elementid='+jQuery('[name="elementid"]').val(),
					'type': 'POST',
					success: function(data) {
						console.log("OK:"); console.log(data);
						jQuery('#modules_list_container').html('');
						jQuery('#record3_container').html(data);
					}
				});
			}
		},
		//crmv@185548e
		
		//crmv@122245
		toggleFieldEditOptions: function(fieldname) {
			//jQuery('[name="'+fieldname+'"]').toggle();
			jQuery('.editoptions[fieldname="'+fieldname+'"]').toggle();
			jQuery('#'+fieldname+'_editoptions_more').toggle();
			//jQuery('#'+fieldname+'_editoptions_cancel').toggle();
			/*
			jQuery('[name="'+fieldname+'"]').change(function(){
				var fieldname = jQuery(this).attr('name');
				var tagField = jQuery('.editoptions[fieldname="'+fieldname+'"]');
				restorePopulateFieldGroup(tagField);
				tagField.hide();
			});*/
		},
		//crmv@122245e
		
		// crmv@126184
		loadRelationsNtoN: function(record) {
			if (record == '') {
				jQuery('#record2_container').html('');
			} else {
				jQuery.ajax({
					url: 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=load_relation_nton&record1='+record+'&id='+jQuery('[name="id"]').val()+'&elementid='+jQuery('[name="elementid"]').val(),
					type: 'POST',
					success: function(data) {
						jQuery('#record2_container').html(data);
					}
				});
			}
		},
		
		refreshStaticRelatedRecords: function(record, relmodule) {
			jQuery.ajax({
				url: 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=load_static_related&record1='+record+'&relmodule='+relmodule+'&id='+jQuery('[name="id"]').val()+'&elementid='+jQuery('[name="elementid"]').val(),
				data: {
					sel_static_records: jQuery('#sel_static_records').val(),
				},
				type: 'POST',
				success: function(data) {
					jQuery('#record3_container').html(data);
				}
			});
		},
		
		loadStaticRelatedRecords: function(record, relmodule) {
			var me = this;
			jQuery('#sel_static_records').val('');
			if (record == '' || relmodule == '') {
				jQuery('#record3_container').html('');
			} else {
				me.refreshStaticRelatedRecords(record, relmodule);
			}
		},
		
		removeStaticLinkedRecord: function(self) {
			var crmid = jQuery(self).data('crmid') + '',
				list = jQuery('#sel_static_records').val(),
				row = jQuery(self).closest('tr');
			row.remove();
			
			list = list.split(',');
			var idx = list.indexOf(crmid);
			if (idx > -1) {
				list.splice(idx, 1);
				jQuery('#sel_static_records').val(list.join(','));
			}
		},
		
		quickClosePopup: function(listid) {
			jQuery('.fancybox-close').click();
		},
		
		addStaticRelatedRecord: function(module, crmid, entityname) {
			var me = this;
			
			me.quickClosePopup();
			me.addStaticRecords([crmid]);
		},
		
		addStaticRelatedRecords: function(listid) {
			var me = this,
				crmids = [],
				frame = jQuery('.fancybox-iframe')[0];
				
			if (frame) {
				var win = frame.contentWindow;
				crmids = win.SLV.add_selected(listid);
			}
			me.quickClosePopup();
			me.addStaticRecords(crmids);
		},
		
		addStaticRecords: function(crmids) {
			var me = this,
				list = jQuery('#sel_static_records').val();
				
			list += ','+crmids.join(',');
			jQuery('#sel_static_records').val(list);
			
			var record = jQuery('#linkRecordSelect1').val();
			var relmodule = jQuery('#linkRecordSelect2').val();
			me.refreshStaticRelatedRecords(record, relmodule);
		},
		// crmv@126184e
		
		//crmv@139690
		fieldGroup: {},
		populateSelectBox: function(element,type) {
			var me = this, 
				selectBox = jQuery('#task-'+type);

			switch(type) {
			    case 'fieldnames':
			    case 'smownerfieldnames':
			    case 'referencefieldnames':
			    case 'pickfieldnames':
			    	var populateFieldGroup = jQuery(element),
			    		populateField = jQuery(populateFieldGroup).next();

			    	if (jQuery(populateFieldGroup).find('option').length > 1) return;
			    	
			    	// populate populateFieldGroup
					// disable cache in config ws
			    	if (jQuery(me.fieldGroup[type]).length == 0 || ActionCallWSScript.wsinfo) { // crmv@147433
		    			var str = '';
		    			str += '<option value="">'+alert_arr.LBL_SELECT_OPTION_DOTDOTDOT+'</option>';
						
						// crmv@147433
						// append values for ExtWS
						var fieldname = populateFieldGroup.closest('div').attr('fieldname');
						if (ActionCallWSScript.wsinfo) {
							var defparams = ActionCallWSScript.wsinfo.params;
							var defresults = ActionCallWSScript.wsinfo.results;
							if (fieldname.match(/^param_[0-9]+/) && defparams && defparams.length > 0) {
								str += '<option value="!DEFAULT!">'+alert_arr.Default+'</option>';
								str += '<option value="!DONTUSE!">'+alert_arr.LBL_DONT_USE+'</option>';
							}
							if (fieldname.match(/^result_[0-9]+/) && defresults && defresults.length > 0) {
								str += '<option value="!DEFAULT!">'+alert_arr.Default+'</option>';
								str += '<option value="!DONTUSE!">'+alert_arr.LBL_DONT_USE+'</option>';
							}
						}
						
	    				selectBox.find('optgroup').each(function(){
	    					str += '<option value="'+this.label+'">'+this.label+'</option>';
	    				});
						
						me.fieldGroup[type] = str;
			    	}
			    	
				    // init populateFieldGroup events
		    		jQuery(populateFieldGroup).html(me.fieldGroup[type]).change(function(event){
						// crmv@147433
						var val = jQuery(this).val();
						if (val == '!DEFAULT!' || val == '!DONTUSE!') {
							ActionCallWSScript.handleSpecialValue(this);
							return;
						}
						// crmv@147433e
    					jQuery(populateFieldGroup).hide();
    					populateField.find('optgroup').hide();
    					populateField.find('optgroup[label="'+this.value+'"]').show();
    					jQuery(populateField).show();
    					populateField.val(populateField.find("option:first").val());
    				});
		    		
			    	// populate populateField
			    	jQuery(populateField).html(selectBox.html());
			    	
			        break;
			}
		},
		//crmv@139690e
		
		/*
		 * inizializzo tutte le picklist di compilazione dei tag
		 * in base al tipo (campo testo, picklist, reference, owner) cambia la modalità di compilazione dei tag
		 * es. i campi testo hanno la picklist dei tag in alto a destra, mentre per le picklist i tag vengono aggiunti ai valori della picklist stessa
		 */
		initPopulateFields(element,object,module,params) {
			switch(element) {
			    case 'task-fieldnames':
			    	jQuery('#editForm .editoptions[optionstype="fieldnames"]').each(function(){
						jQuery(this).html('<select class="populateFieldGroup" onfocus="ActionTaskScript.populateSelectBox(this,\'fieldnames\')"><option value="">'+alert_arr.LBL_SELECT_OPTION_DOTDOTDOT+'</option></select><select style="display:none" class="populateField" onchange="'+object.objectName+'.populateField(this)"></select>');	//crmv@112299 crmv@139690
					});
			        break;
			        
			    case 'task-pickfieldnames':
			    	jQuery('#editForm .editoptions[optionstype="pickfieldnames"]').each(function(){
						jQuery(this).html('<select class="populateFieldGroup" onfocus="ActionTaskScript.populateSelectBox(this,\'pickfieldnames\')"><option value="">'+alert_arr.LBL_SELECT_OPTION_DOTDOTDOT+'</option></select><select style="display:none" class="populateField" onchange="'+object.objectName+'.populateField(this)"></select>');	//crmv@112299 crmv@139690
					});
			        break;
			        
			    case 'task-smownerfieldnames':
			    	/*
			    	var form_data = params['form_data'],
			    		reference_users_values = params['reference_users_values'] || {};
			    	*/
		    		jQuery('#editForm .editoptions[optionstype="smownerfieldnames"]').each(function(){
						jQuery(this).html('<select class="populateFieldGroup" onfocus="ActionTaskScript.populateSelectBox(this,\'smownerfieldnames\')"><option value="">'+alert_arr.LBL_SELECT_OPTION_DOTDOTDOT+'</option></select><select style="display:none" class="populateField" onchange="'+object.objectName+'.populateField(this)"></select>');	//crmv@112299 crmv@139690
						var fieldname = jQuery(this).attr('fieldname').replace('other_','');
						if (jQuery('#other_'+fieldname).length > 0) ActionTaskScript.showSdkParamsInput(jQuery('#other_'+fieldname),fieldname);	//crmv@106856 crmv@113527
					});
			    	break;
			    	
			    case 'task-referencefieldnames':
			    	var form_data = params['form_data'],
			    		reference_values = params['reference_values'] || {};
			    		
		    		jQuery('#editForm .editoptions[optionstype="referencefieldnames"]').each(function(){
						jQuery(this).html('<select class="populateFieldGroup" onfocus="ActionTaskScript.populateSelectBox(this,\'referencefieldnames\')"><option value="">'+alert_arr.LBL_SELECT_OPTION_DOTDOTDOT+'</option></select><select style="display:none" class="populateField" onchange="'+object.objectName+'.populateField(this)"></select>');	//crmv@112299 crmv@139690
					});
			    	jQuery.each(reference_values, function(name,value){
			    		if ((module == 'Calendar' || module == 'Events') && name == 'parent_id') var field_type = 'parent_type'; else var field_type = name+'_type'; 
						if (jQuery('#'+field_type).val() == 'Other' && form_data[name] != undefined) {
							jQuery('#other_'+name).val(form_data[name]);
						}
					});
			    	break;
			    	
			    case 'task-booleanfieldnames':
			    	var boolean_values = params['boolean_values'] || {};
			    	jQuery.each(boolean_values, function(name,value){
						jQuery('[name="'+name+'"]').append(jQuery('#task-booleanfieldnames').html());
						if (value != null) jQuery('[name="'+name+'"]').val(value);
					});
			    	break;
			    	
			    case 'task-datefieldnames':
			    	var date_values = params['date_values'] || {};
			    	//crmv@120769
					jQuery.each(date_values, function(name,value){
						jQuery('[name="'+name+'_options"]').append(jQuery('#task-datefieldnames').html());
						if (value != null && value != '') {
							try {
								value = jQuery.parseJSON(value);
								jQuery('[name="'+name+'_options"]').val(value['options']);
								jQuery('[name="'+name+'"]').val(getDisplayDate(value['custom']));	//crmv@131239
								jQuery('[name="'+name+'_opt_operator"]').val(value['operator']);
								jQuery('[name="'+name+'_opt_num"]').val(value['num']);
								jQuery('[name="'+name+'_opt_unit"]').val(value['unit']);
								if (name == 'time_start' || name == 'time_end')
									ActionTaskScript.calendarTimeOptions(value['options'],name);
								else
									ActionTaskScript.calendarDateOptions(value['options'],name);
							} catch(err) {	// old mode
								jQuery('[name="'+name+'_options"]').val(getDisplayDate('custom'));	//crmv@131239
								jQuery('[name="'+name+'"]').val(value);
								ActionTaskScript.calendarDateOptions('custom',name);
							}												
						}
					});
					//crmv@120769e
			    	break;
			}
		},
		
		loadFormEditOptions: function(object,module,params, callback) { // crmv@147433
			var me = this,
				i = 0,
				processid = params['processid'],
				involved_records = params['involved_records'],
				elements_actors = params['elements_actors'],
				extws_options = params['extws_options'] || {},	// crmv@146671
				dynaform_options = params['dynaform_options'],
				processmaker_entity_options;

			if (ActionTaskScript.log_time) var start = Date.now();
			
			ActionTaskScript.getCache('processmaker_entity_options_'+processid, function(processmaker_entity_options){

				if (processmaker_entity_options) {
					jQuery.each(processmaker_entity_options,function(element,optgroups){
						var append = '';
						jQuery.each(optgroups,function(optgrouplabel,options){
							if (!checkSelectBoxDuplicates(jQuery('#'+element),optgrouplabel)) {
								if (optgrouplabel != '') append += '<optgroup label="'+optgrouplabel+'">';
								jQuery.each(options,function(key,value){
									append += '<option value="'+key+'">'+value+'</option>';
								});
								if (optgrouplabel != '') append += '</optgroup>';
							}
						});
						jQuery('#'+element).append(append);
						me.initPopulateFields(element,object,module,params);
					});
					me.afterLastIteration();
					if (typeof callback == 'function') callback(); // crmv@147433
					
					if (ActionTaskScript.log_time) {
						var end = Date.now();
						var total_time = end - start;
						console.log('ends ActionTaskScript > loadFormEditOptions with cache in ',total_time);
					}
					
				} else {
					var vtinst = new VtenextWebservices("webservice.php",undefined,undefined,true);
					vtinst.extendSession(handleError(function(result){
						vtinst.listTypes(handleError(function(accessibleModules) {
							accessibleModulesInfo = accessibleModules;
							if (jQuery.isEmptyObject(involved_records)) { //crmv@179315 check if there are involved records
								me.afterLastIteration();
								return false;
							}
							ActionTaskScript.getCache('processmaker_describe_modules_'+processid, function(){
								jQuery.each(involved_records,function(key,involved_record){
									var moduleName = involved_record.module;
									if (moduleName == '' || moduleName == null) {	// check if there are involved records
										i++;
										if (i == jQuery(involved_records).length) me.afterLastIteration();	// check last
										return;
									}
									getDescribeObjects(vtinst, accessibleModules, moduleName, processid, involved_record, handleError(function(describeObjectResult){
										modules = describeObjectResult[0];
										moduleName = describeObjectResult[1];
										involved_record = describeObjectResult[2];
										i++;

										if (object.objectName == 'ActionCreateScript') {
											fillSelectBox('task-fieldnames', modules, moduleName, involved_record, null);
										} else {
											fillSelectBox('task-fieldnames', modules, moduleName, involved_record);
										}
										fillSelectBox('task-pickfieldnames', modules, moduleName, involved_record, function(e){return (e['type']['name']=='picklist' || e['type']['name']=='multipicklist');});
										fillSelectBox('task-smownerfieldnames', modules, moduleName, involved_record, function(e){return (e['type']['name']=='reference' && e['type']['refersTo'][0]=='Users');});
										fillSelectBox('task-referencefieldnames', modules, moduleName, involved_record, function(e){return (e['type']['name']=='reference' && e['type']['refersTo'][0]!='Users' && e['type']['refersTo'][0]!='Currency');});
										fillSelectBox('task-booleanfieldnames', modules, moduleName, involved_record, function(e){return (e['type']['name']=='boolean');});
										fillSelectBox('task-datefieldnames', modules, moduleName, involved_record, function(e){return (e['type']['name']=='date' || e['type']['name']=='datetime' || e['type']['name']=='time');});	//crmv@128159

										// last
										if (i == jQuery(involved_records).length) {
										//if (ActionTaskScript.__describeObjectCount == 0) {
											
											if (ActionTaskScript.log_time) {
												var getDescribeObjects_time_end = Date.now();
												var getDescribeObjects_time = getDescribeObjects_time_end - start;
												console.log('ends ActionTaskScript > getDescribeObjects in ',getDescribeObjects_time);
											}
											// text
											appendDynaformOptions(jQuery('#task-fieldnames'),dynaform_options,'all');
											appendExtWSOptions(jQuery('#task-fieldnames'),extws_options); // crmv@146671 TODO add ws options to other field types
											me.initPopulateFields('task-fieldnames',object,module,params);
											
											// picklist
											appendDynaformOptions(jQuery('#task-pickfieldnames'),dynaform_options,'picklist');
											me.initPopulateFields('task-pickfieldnames',object,module,params);
											
											// owner
											appendDynaformOptions(jQuery('#task-smownerfieldnames'),dynaform_options,'user');
											//crmv@100591
											if (jQuery(elements_actors).length > 0 && !checkSelectBoxDuplicates(jQuery('#task-smownerfieldnames'),alert_arr.LBL_PM_ELEMENTS_ACTORS)) {
												var append = '<optgroup label="'+alert_arr.LBL_PM_ELEMENTS_ACTORS+'">';
												jQuery.each(elements_actors, function(fieldvalue, fieldlabel){
													append += '<option value="'+fieldvalue+'">'+fieldlabel+'</value>';
												});
												append += '</optgroup>';
												jQuery('#task-smownerfieldnames').append(append);
											}
											//crmv@100591e
											me.initPopulateFields('task-smownerfieldnames',object,module,params);
											
											// reference
											appendDynaformOptions(jQuery('#task-referencefieldnames'),dynaform_options,'reference');
											me.initPopulateFields('task-referencefieldnames',object,module,params);
											
											// boolean
											appendDynaformOptions(jQuery('#task-booleanfieldnames'),dynaform_options,'boolean');
											me.initPopulateFields('task-booleanfieldnames',object,module,params);
											
											// date
											appendDynaformOptions(jQuery('#task-datefieldnames'),dynaform_options,'date');
											//crmv@128159
											appendDynaformOptions(jQuery('#task-datefieldnames'),dynaform_options,'datetime');
											appendDynaformOptions(jQuery('#task-datefieldnames'),dynaform_options,'time');
											//crmv@128159e
											me.initPopulateFields('task-datefieldnames',object,module,params);
											
											//crmv@112299 crmv@139690: removed filterPopulateField()
											
											if (me.enable_cache) {
												me.cacheSelectBoxOptions(processid);
												if (me.__overwriteCache) me.setCache('processmaker_describe_modules_'+processid,me.__cache['processmaker_describe_modules_'+processid]);
											}
											
											me.afterLastIteration();
											
											if (typeof callback == 'function') callback(); // crmv@147433
			
											if (ActionTaskScript.log_time) {
												var end = Date.now();
												var display_time = end - getDescribeObjects_time_end;
												var total_time = end - start;
												console.log('ends ActionTaskScript > loadFormEditOptions : display:',display_time,' total:',total_time);
											}
										}
									}));
								});
							});
						}));
					}));
				}
			});
		},
		cacheSelectBoxOptions: function(processid) {
			var processmaker_entity_options = {};
			var elements = ['task-fieldnames','task-pickfieldnames','task-smownerfieldnames','task-referencefieldnames','task-booleanfieldnames','task-datefieldnames'];
			jQuery(elements).each(function(k,element){
				processmaker_entity_options[element] = {};
				/* skip options without optgroup because are drawn in Create.tpl and Update.tpl
				processmaker_entity_options[element][''] = {};
				jQuery("#"+element+" > option").each(function(){
					processmaker_entity_options[element][''][jQuery(this).val()] = jQuery(this).text();
				});
				*/
				// options in optgroup
				jQuery("#"+element+" > optgroup").each(function(i,optgroup){
					processmaker_entity_options[element][jQuery(optgroup).attr('label')] = {};
					jQuery(optgroup).find('option').each(function(){
						processmaker_entity_options[element][jQuery(optgroup).attr('label')][jQuery(this).val()] = jQuery(this).text();
					});
				});
			});
			ActionTaskScript.setCache('processmaker_entity_options_'+processid,processmaker_entity_options);
		},
		getCache: function(item, callback) {
			var me = this;
			
			if (me.__cache[item]) {
				if (typeof callback == 'function') callback(me.__cache[item]);
			} else {
				jQuery.ajax({
					'url': 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=get_cache&item='+item,
					'type': 'POST',
					success: function(data) {
						if (data != null && data != "null") data = JSON.parse(data); else data = null;
						me.__cache[item] = data;
						if (typeof callback == 'function') callback(data);
					}
				});
			}
		},
		setCache: function(item,value) {
			jQuery.ajax({
				'url': 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=set_cache&item='+item,
				'type': 'POST',
				'data': {'value':JSON.stringify(value)},
				success: function(data) {}
			});
		},
		afterLastIteration: function() {
			jQuery.fancybox.hideLoading();
		}
	}
}

if (typeof(ActionCreateScript) == 'undefined') {
	ActionCreateScript = {
		objectName: 'ActionCreateScript',
		loadForm: function(module,processid,id,action_type,action_id,tablerow_mode,load_editview) { //crmv@182148
			var me = ActionCreateScript;
			if (typeof(tablerow_mode) == 'undefined') var tablerow_mode = false;
			if (typeof(load_editview) == 'undefined') var load_editview = true; //crmv@182148
			if (load_editview && module == '') { //crmv@182148
				jQuery('#editForm').html('');
			} else {
				jQuery.fancybox.showLoading();
				// crmv@102879
				var url = 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker/actions/CreateForm&mod='+module+'&id='+processid+'&elementid='+id+'&action_id='+action_id;
				url += '&cycle_field='+encodeURIComponent(jQuery('input[name=cycle_field]').val() || '');
				url += '&cycle_action='+encodeURIComponent(jQuery('input[name=cycle_action]').val() || '');
				url += '&cycle_fieldname='+encodeURIComponent(jQuery('input[name=cycle_fieldname]').val() || '');//crmv@203075 add fieldname to check in CreateForm and not retrieve prod blocks in cycle related
				if (tablerow_mode) url += '&tablerow_mode=1'; else url += '&tablerow_mode=0';
				// crmv@102879e
				
				jQuery.ajax({
					'url': url,
					'type': 'POST',
					success: function(data) {
						var res = data.split('|&|&|&|');
						if (res[0] != '') var involved_records = JSON.parse(res[0]); else var involved_records = {};
						if (res[1] != '') var form_data = JSON.parse(res[1]); else var form_data = {};
						if (res[2] != '') var picklist_values = JSON.parse(res[2]); else var picklist_values = {};
						if (res[3] != '') var reference_values = JSON.parse(res[3]); else var reference_values = {};
						if (res[4] != '') var reference_users_values = JSON.parse(res[4]); else var reference_users_values = {};
						if (res[5] != '') var boolean_values = JSON.parse(res[5]); else var boolean_values = {};
						if (res[6] != '') var date_values = JSON.parse(res[6]); else var date_values = {};
						if (res[7] != '') var dynaform_options = JSON.parse(res[7]); else var dynaform_options = {};
						if (res[8] != '') var elements_actors = JSON.parse(res[8]); else var elements_actors = {};	//crmv@100591
						if (res[9] != '') var extws_options = JSON.parse(res[9]); else var extws_options = {}; // crmv@146671
						//crmv@182148
						if (load_editview) {
							try {
								jQuery('#editForm').html(res[10]); // crmv@146671
							} catch(err) {
							    console.error(err.message);
							}
						}
						//crmv@182148e
						var params = {
							'processid':processid,
							'involved_records':involved_records,
							'form_data':form_data,
							'picklist_values':picklist_values,
							'reference_values':reference_values,
							'reference_users_values':reference_users_values,
							'boolean_values':boolean_values,
							'date_values':date_values,
							'dynaform_options':dynaform_options,
							'elements_actors':elements_actors,
							'extws_options':extws_options, // crmv@146671
						}
						ActionTaskScript.loadFormEditOptions(me,module,params);
					}
				});
			}
		},
		//crmv@106857
		populateField: function(field, value){ //crmv@OPER10174
			var tagField = jQuery(field);
			var fieldname = jQuery(field).parent().attr('fieldname');
			var field = jQuery('#editForm [name="'+fieldname+'"]');
			if (typeof(value) == 'undefined') value = jQuery(tagField).val(); //crmv@OPER10174
			var tablefields_fieldname = fieldname.replace('other_','');
			//crmv@112299
			if (value == 'back') {
				restorePopulateFieldGroup(tagField);
			//crmv@112299e
			} else if (value.indexOf('::') != -1) {
				// show table fields options
				if (jQuery('#actionform [name="cycle_action"]').val() != '') {
					// check if I am in a cycle
					//crmv@182891
					var cycle_field = jQuery('#actionform [name="cycle_field"]').val().split(':');
					if (value.indexOf('$'+cycle_field[0]+'-'+cycle_field[3]+'::') == 0
						|| (value.indexOf('$'+cycle_field[0]+'-(') == 0 && value.indexOf(' '+cycle_field[3]+'::') > -1)
						|| value.indexOf('$DF'+cycle_field[0]+'-'+cycle_field[1]+'::') == 0) {
					//crmv@182891e
						jQuery("#tablefields_options_"+tablefields_fieldname+" .cycle_opt").show();
					} else {
						jQuery("#tablefields_options_"+tablefields_fieldname+" .cycle_opt").hide();
					}
				}
				jQuery(field).parent().parent().find('.editoptions .populateField').css('max-width','300px');
				jQuery("#tablefields_options_"+tablefields_fieldname+" option:eq(0)").prop('selected', true);
				jQuery("#tablefields_options_"+tablefields_fieldname).show();
			} else {
				// hide table fields options
				jQuery(field).parent().parent().find('.editoptions .populateField').css('max-width','400px');
				jQuery("#tablefields_options_"+tablefields_fieldname).hide();
				jQuery('#tablefields_seq_'+tablefields_fieldname).hide();
				jQuery('#tablefields_seq_btn_'+tablefields_fieldname).hide();
				// end
				if (value != '') insertAtCursor(field.get(0), value);
			}
		},
		changeTableFieldOpt: function(obj, fieldname){
			var me = this,
				value = obj.value;
			if (value == 'seq') {
				jQuery('#tablefields_seq_'+fieldname).show().focus();
				jQuery('#tablefields_seq_btn_'+fieldname).show();
			} else {
				jQuery('#tablefields_seq_'+fieldname).hide();
				jQuery('#tablefields_seq_btn_'+fieldname).hide();
				if (value != '') me.insertTableFieldValue(obj, fieldname, value);
			}
		},
		insertTableFieldValue: function(obj, fieldname, value){
			var tagField = jQuery(obj).parent().parent().find('.editoptions .populateField');
			var parent_value = jQuery(tagField).val();
			var target_fieldname = jQuery(obj).parent().parent().find('.editoptions').attr('fieldname');
			var field = jQuery('#editForm [name="'+target_fieldname+'"]');
			if (value == 'seq') {
				var sequence = parseInt(jQuery('#tablefields_seq_'+fieldname).val());
				if (isNaN(sequence) || sequence <= 0) return false;
				else value += ':'+sequence;
			}
			insertAtCursor(field.get(0), parent_value+':'+value);
		}
		//crmv@106857e
	}
}

if (typeof(ActionUpdateScript) == 'undefined') {
	ActionUpdateScript = {
		objectName: 'ActionUpdateScript',
		loadForm: function(record_involved,processid,id,action_type,action_id) {
			var me = ActionUpdateScript;
			if (record_involved == '') {
				jQuery('#editForm').html('');
			} else {
				jQuery.fancybox.showLoading();
				//crmv@135190
				var tmp = record_involved.split(':'),
					module = tmp[1],
					url = 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker/actions/UpdateForm&record_involved='+record_involved+'&id='+processid+'&elementid='+id+'&action_id='+action_id;
					url += '&cycle_fieldname='+encodeURIComponent(jQuery('input[name=cycle_fieldname]').val() || '');//crmv@203075 add fieldname to check in CreateForm and not retrieve prod blocks in cycle related
				//crmv@135190e
				jQuery.ajax({
					'url': url,
					'type': 'POST',
					success: function(data) {
						var res = data.split('|&|&|&|');
						if (res[0] != '') var involved_records = JSON.parse(res[0]); else var involved_records = {};
						if (res[1] != '') var form_data = JSON.parse(res[1]); else var form_data = {};
						if (res[2] != '') var picklist_values = JSON.parse(res[2]); else var picklist_values = {};
						if (res[3] != '') var reference_values = JSON.parse(res[3]); else var reference_values = {};
						if (res[4] != '') var reference_users_values = JSON.parse(res[4]); else var reference_users_values = {};
						if (res[5] != '') var boolean_values = JSON.parse(res[5]); else var boolean_values = {};
						if (res[6] != '') var date_values = JSON.parse(res[6]); else var date_values = {};
						if (res[7] != '') var dynaform_options = JSON.parse(res[7]); else var dynaform_options = {};
						if (res[8] != '') var elements_actors = JSON.parse(res[8]); else var elements_actors = {};	//crmv@100591
						if (res[9] != '') var extws_options = JSON.parse(res[9]); else var extws_options = {}; // crmv@146671
						try {
							jQuery('#editForm').html(res[10]); // crmv@146671
						} catch(err) {
						    console.error(err.message);
						}
						var params = {
							'processid':processid,
							'involved_records':involved_records,
							'form_data':form_data,
							'picklist_values':picklist_values,
							'reference_values':reference_values,
							'reference_users_values':reference_users_values,
							'boolean_values':boolean_values,
							'date_values':date_values,
							'dynaform_options':dynaform_options,
							'elements_actors':elements_actors,
							'extws_options':extws_options, // crmv@146671
						}
						ActionTaskScript.loadFormEditOptions(me,module,params);
						
						jQuery('#editForm form[name="EditView"] :input').bind('change onchange',function(e){
							var name = jQuery(this).attr('name');
							if (name == 'other_assigned_user_id') name = 'assigned_user_id';
							ActionUpdateScript.setMasseditCheck(name);
						});
						jQuery.each(form_data,function(name,value){
							ActionUpdateScript.setMasseditCheck(name);
						});
					}
				});
			}
		},
		//crmv@106857
		populateField: function(field){
			var tagField = jQuery(field);
			var fieldname = jQuery(field).parent().attr('fieldname');
			var field = jQuery('#editForm [name="'+fieldname+'"]');
			var value = jQuery(tagField).val();
			var tablefields_fieldname = fieldname.replace('other_','');
			//crmv@112299
			if (value == 'back') {
				restorePopulateFieldGroup(tagField);
			//crmv@112299e
			} else if (value.indexOf('::') != -1) {
				// show table fields options
				jQuery(field).parent().parent().find('.editoptions .populateField').css('max-width','300px');
				jQuery("#tablefields_options_"+tablefields_fieldname+" option:eq(0)").prop('selected', true);
				jQuery("#tablefields_options_"+tablefields_fieldname).show();
			} else {
				// hide table fields options
				jQuery(field).parent().parent().find('.editoptions .populateField').css('max-width','400px');
				jQuery("#tablefields_options_"+tablefields_fieldname).hide();
				jQuery('#tablefields_seq_'+tablefields_fieldname).hide();
				jQuery('#tablefields_seq_btn_'+tablefields_fieldname).hide();
				// end
				if (value != '') insertAtCursor(field.get(0), value);
				ActionUpdateScript.setMasseditCheck(fieldname);
			}
		},
		setMasseditCheck: function(fieldname){
			if (fieldname.indexOf('[]') > -1) fieldname = fieldname.replace('[]',''); //crmv@166678
			// crmv@191206
			if (fieldname.indexOf('other_') == 0) {
				var tmp_fieldname = fieldname.replace('other_','');
				if (jQuery('#'+tmp_fieldname+'_mass_edit_check').length > 0) fieldname = tmp_fieldname;
			}
			// crmv@191206e
			// crmv@204994
			if (fieldname.indexOf('_opt_num') > -1) {
				var tmp_fieldname = fieldname.replace('_opt_num','');
				if (jQuery('#'+tmp_fieldname+'_mass_edit_check').length > 0) fieldname = tmp_fieldname;
			}
			// crmv@204994e
			jQuery('#editForm form[name="EditView"] #'+fieldname+'_mass_edit_check').prop('checked',true);
		},
		changeTableFieldOpt: function(obj, fieldname){
			var me = this,
				value = obj.value;
			if (value == 'seq') {
				jQuery('#tablefields_seq_'+fieldname).show().focus();
				jQuery('#tablefields_seq_btn_'+fieldname).show();
			} else {
				jQuery('#tablefields_seq_'+fieldname).hide();
				jQuery('#tablefields_seq_btn_'+fieldname).hide();
				if (value != '') me.insertTableFieldValue(obj, fieldname, value);
			}
		},
		insertTableFieldValue: function(obj, fieldname, value){
			var tagField = jQuery(obj).parent().parent().find('.editoptions .populateField');
			var parent_value = jQuery(tagField).val();
			var target_fieldname = jQuery(obj).parent().parent().find('.editoptions').attr('fieldname');
			var field = jQuery('#editForm [name="'+target_fieldname+'"]');
			if (value == 'seq') {
				var sequence = parseInt(jQuery('#tablefields_seq_'+fieldname).val());
				if (isNaN(sequence) || sequence <= 0) return false;
				else value += ':'+sequence;
			}
			insertAtCursor(field.get(0), parent_value+':'+value);
			ActionUpdateScript.setMasseditCheck(fieldname);
		}
		//crmv@106857e
	}
}

if (typeof(ActionEmailScript) == 'undefined') {
	ActionEmailScript = {
		loadForm: function(processid,id,action_type,action_id,involved_records,dynaform_options,elements_actors,extws_options) { // crmv@100591 crmv@147433
			if (involved_records != '') involved_records = JSON.parse(involved_records); else involved_records = {}; //crmv@179315
			dynaform_options = JSON.parse(dynaform_options);
			elements_actors = JSON.parse(elements_actors);	//crmv@100591
			extws_options = JSON.parse(extws_options);	//crmv@147433
			var me = this,
				i = 0,
				vtinst = new VtenextWebservices("webservice.php",undefined,undefined,true)
			last = function(){
				jQuery('#task-fieldnames-busyicon').hide();
				jQuery('#task-subjectfields-busyicon').hide();
				jQuery('#task-emailfields-busyicon').hide();
				jQuery('#task-emailfields_sender-busyicon').hide();
				jQuery('#task-emailfieldscc-busyicon').hide();
				jQuery('#task-emailfieldsbcc-busyicon').hide();
				jQuery('#task-emailfieldsreplyto-busyicon').hide(); // crmv@200330
				//crmv@140599
				//time_changes
				jQuery('#task_timefields').unbind('change');
				jQuery('#task_timefields').change(function(){
					var value = jQuery(this).val();
					if (value.indexOf('crmdetailviewurl') > -1 || value.indexOf('portaldetailviewurl') > -1) {
						jQuery('#task_timefields_metavars').parent().show();
						jQuery('#task_timefields_metavars').val('');
					} else {
						jQuery('#task_timefields_metavars').parent().hide();
						var textarea = CKEDITOR.instances.save_content;
						textarea.insertHtml(value);
					}
				});
				jQuery('#task_timefields_metavars').unbind('change');
				jQuery('#task_timefields_metavars').change(function(){
					var textarea = CKEDITOR.instances.save_content;
					var value = jQuery(this).val();
					var pieces = value.split(':');
					var metaid = pieces[0];
					var module = pieces[1];
					value = jQuery('#task_timefields').val().replace('$','$'+metaid+'-');
					textarea.insertHtml(value);
				});
				//crmv@140599e
				filterPopulateField();	//crmv@112299
			}
			vtinst.extendSession(handleError(function(result){
				vtinst.listTypes(handleError(function(accessibleModules) {
					accessibleModulesInfo = accessibleModules;
					if (jQuery.isEmptyObject(involved_records)) { //crmv@179315 check if there are involved records
						last();
						return false;
					}
					jQuery.each(involved_records,function(key,involved_record){
						var moduleName = involved_record.module;
						if (moduleName == '' || moduleName == null) {	// check if there are involved records
							i++;
							if (i == jQuery(involved_records).length) last();	// check last
							return;
						}
						ActionTaskScript.getCache('processmaker_describe_modules_'+processid, function(){
							getDescribeObjects(vtinst, accessibleModules, moduleName, processid, involved_record, handleError(function(describeObjectResult){
								modules = describeObjectResult[0];
								moduleName = describeObjectResult[1];
								involved_record = describeObjectResult[2];
								i++;

								fillSelectBox('task-fieldnames', modules, moduleName, involved_record);
								jQuery('#task-fieldnames').prev('.populateFieldGroup').show();
								
								fillSelectBox('task-subjectfields', modules, moduleName, involved_record, function(e){return (e['type']['name']!='file' && e['type']['name']!='text');});
								jQuery('#task-subjectfields').prev('.populateFieldGroup').show();
								
								fillSelectBox('task-emailfields', modules, moduleName, involved_record, function(e){return e['type']['name']=='email';});
								
								if (i == jQuery(involved_records).length) {
									appendDynaformOptions(jQuery('#task-fieldnames'),dynaform_options,'all');
									jQuery('#task-fieldnames').unbind('change');
									jQuery('#task-fieldnames').change(function(){
										me.populateField('append_textarea',CKEDITOR.instances.save_content,this,'content'); //crmv@106857
									});
									
									appendDynaformOptions(jQuery('#task-subjectfields'),dynaform_options,'all');
									jQuery('#task-subjectfields').unbind('change');
									jQuery('#task-subjectfields').change(function(){
										me.populateField('append_input_space',jQuery(jQuery('#save_subject').get()),this,'subject'); //crmv@106857
									});
									
									appendDynaformOptions(jQuery('#task-emailfields'),dynaform_options,'email');
									//crmv@100591
									if (jQuery(elements_actors).length > 0 && !checkSelectBoxDuplicates(jQuery('#task-emailfields'),alert_arr.LBL_PM_ELEMENTS_ACTORS)) {
										var append = '<optgroup label="'+alert_arr.LBL_PM_ELEMENTS_ACTORS+'">';
										jQuery.each(elements_actors, function(fieldvalue, fieldlabel){
											append += '<option value="'+fieldvalue+'">'+fieldlabel+'</value>';
										});
										append += '</optgroup>';
										jQuery('#task-emailfields').append(append);
									}
									//crmv@100591e
									jQuery('#task-emailfields').unbind('change');
									jQuery('#task-emailfields').change(function(){
										me.populateField('append_input_comma',jQuery(jQuery('#save_recepient').get()),this,'recepient'); //crmv@106857
									});
									jQuery('#task-emailfields').show();
									
									jQuery('#task-emailfields_sender').html(jQuery('#task-emailfields').html());
									jQuery('#task-emailfields_sender').unbind('change');
									jQuery('#task-emailfields_sender').change(function(){
										me.populateField('overwrite_input',jQuery(jQuery('#save_sender').get()),this,'sender'); //crmv@106857
									});
									jQuery('#task-emailfields_sender').show();
									
									jQuery('#task-emailfieldscc').html(jQuery('#task-emailfields').html());
									jQuery('#task-emailfieldscc').unbind('change');
									jQuery('#task-emailfieldscc').change(function(){
										me.populateField('append_input_comma',jQuery(jQuery('#save_emailcc').get()),this,'emailcc'); //crmv@106857
									});
									jQuery('#task-emailfieldscc').show();
									
									jQuery('#task-emailfieldsbcc').html(jQuery('#task-emailfields').html());
									jQuery('#task-emailfieldsbcc').unbind('change');
									jQuery('#task-emailfieldsbcc').change(function(){
										me.populateField('append_input_comma',jQuery(jQuery('#save_emailbcc').get()),this,'emailbcc'); //crmv@106857
									});
									jQuery('#task-emailfieldsbcc').show();
									
									// crmv@200330
									jQuery('#task-emailfieldsreplyto').html(jQuery('#task-emailfields').html());
									jQuery('#task-emailfieldsreplyto').unbind('change');
									jQuery('#task-emailfieldsreplyto').change(function(){
										me.populateField('append_input_comma',jQuery(jQuery('#save_emailreplyto').get()),this,'emailbcc'); //crmv@106857
									});
									jQuery('#task-emailfieldsreplyto').show();
									// crmv@200330e
									
									// crmv@147433
									appendExtWSOptions(jQuery('#task-subjectfields'),extws_options);
									appendExtWSOptions(jQuery('#task-fieldnames'),extws_options);
									// crmv@147433e
									
									last();
								}
							}));
						});
					});
				}));
			}));
		},
		//crmv@106857
		populateField: function(mode,target,field,fieldname){
			var me = this,
				value = jQuery(field).val();
			//crmv@112299
			if (value == 'back') {
				restorePopulateFieldGroup(field);
			//crmv@112299e
			} else if (value.indexOf('::') != -1) {
				// show table fields options
				if (jQuery('#actionform [name="cycle_action"]').val() != '') {
					// check if I am in a cycle
					//crmv@182891
					var cycle_field = jQuery('#actionform [name="cycle_field"]').val().split(':');
					if (value.indexOf('$'+cycle_field[0]+'-'+cycle_field[3]+'::') == 0
						|| (value.indexOf('$'+cycle_field[0]+'-(') == 0 && value.indexOf(' '+cycle_field[3]+'::') > -1)
						|| value.indexOf('$DF'+cycle_field[0]+'-'+cycle_field[1]+'::') == 0) {
					//crmv@182891e
						jQuery("#tablefields_options_"+fieldname+" .cycle_opt").show();
					} else {
						jQuery("#tablefields_options_"+fieldname+" .cycle_opt").hide();
					}
				}
				//jQuery(field).parent().parent().find('.editoptions .populateField').css('max-width','300px');
				jQuery("#tablefields_options_"+fieldname+" option:eq(0)").prop('selected', true);
				jQuery("#tablefields_options_"+fieldname).show();
			} else {
				// hide table fields options
				//jQuery(field).parent().parent().find('.editoptions .populateField').css('max-width','400px');
				jQuery("#tablefields_options_"+fieldname).hide();
				jQuery('#tablefields_seq_'+fieldname).hide();
				jQuery('#tablefields_seq_btn_'+fieldname).hide();
				// end
				me.insertAtCursor(mode,target,value);
			}
		},
		changeTableFieldOpt: function(mode,target,fieldname,dropdownid,obj){
			var me = this,
				value = obj.value;
			if (value == 'seq') {
				jQuery('#tablefields_seq_'+fieldname).show().focus();
				jQuery('#tablefields_seq_btn_'+fieldname).show();
			} else {
				jQuery('#tablefields_seq_'+fieldname).hide();
				jQuery('#tablefields_seq_btn_'+fieldname).hide();
				if (value != '') me.insertTableFieldValue(mode,target,fieldname,dropdownid,value);
			}
		},
		insertTableFieldValue: function(mode,target,fieldname,dropdownid,value){
			var me = this,
				parent_value = jQuery('#'+dropdownid).val();
			if (value == 'seq') {
				var sequence = parseInt(jQuery('#tablefields_seq_'+fieldname).val());
				if (isNaN(sequence) || sequence <= 0) return false;
				else value += ':'+sequence;
			}
			me.insertAtCursor(mode,target,parent_value+':'+value);
		},
		insertAtCursor: function(mode,target,value) {
			if (mode == 'append_textarea') {
				target.insertHtml(value);
			} else if (mode == 'append_input_space') {
				target.val(target.val()+' '+value);
			} else if (mode == 'append_input_comma') {
				var oldvalue = target.val().trim();
				target.val((oldvalue ? oldvalue+',' : '')+value);
			} else if (mode == 'overwrite_input') {
				target.val(value);
			}
		}
		//crmv@106857e
	}
}

// crmv@126696
if (typeof(ActionNewsletterScript) == 'undefined') {
	ActionNewsletterScript = {
		
		loadForm: function(processid,id,action_type,action_id,involved_records,dynaform_options,elements_actors,extws_options) {	//crmv@100591 crmv@147433
			if (involved_records != '') involved_records = JSON.parse(involved_records); else involved_records = {}; //crmv@179315
			dynaform_options = JSON.parse(dynaform_options);
			elements_actors = JSON.parse(elements_actors);	//crmv@100591
			extws_options = JSON.parse(extws_options);	//crmv@147433
			var me = this,
				i = 0,
				vtinst = new VtenextWebservices("webservice.php",undefined,undefined,true)
			last = function(){
				jQuery('#task-fieldnames-busyicon').hide();
				jQuery('#task-subjectfields-busyicon').hide();
				jQuery('#task-emailfields-busyicon').hide();
				jQuery('#task-emailfields_sendername-busyicon').hide();
				jQuery('#task-emailfields_sender-busyicon').hide();
				jQuery('#task-emailfields_recipients-busyicon').hide();
				//crmv@140599
				//time_changes
				jQuery('#task_timefields').unbind('change');
				jQuery('#task_timefields').change(function(){
					var value = jQuery(this).val();
					if (value.indexOf('crmdetailviewurl') > -1 || value.indexOf('portaldetailviewurl') > -1) {
						jQuery('#task_timefields_metavars').parent().show();
						jQuery('#task_timefields_metavars').val('');
					} else {
						jQuery('#task_timefields_metavars').parent().hide();
						var textarea = CKEDITOR.instances.save_content;
						textarea.insertHtml(value);
					}
				});
				jQuery('#task_timefields_metavars').unbind('change');
				jQuery('#task_timefields_metavars').change(function(){
					var textarea = CKEDITOR.instances.save_content;
					var value = jQuery(this).val();
					var pieces = value.split(':');
					var metaid = pieces[0];
					var module = pieces[1];
					value = jQuery('#task_timefields').val().replace('$','$'+metaid+'-');
					textarea.insertHtml(value);
				});
				//crmv@140599e
				jQuery('#recipients_selects').show(); //crmv@181281
				filterPopulateField();	//crmv@112299
			}
			vtinst.extendSession(handleError(function(result){
				vtinst.listTypes(handleError(function(accessibleModules) {
					accessibleModulesInfo = accessibleModules;
					if (jQuery.isEmptyObject(involved_records)) { //crmv@179315 check if there are involved records
						last();
						return false;
					}
					jQuery.each(involved_records,function(key,involved_record){
						var moduleName = involved_record.module;
						if (moduleName == '' || moduleName == null) {	// check if there are involved records
							i++;
							return;
						}
						ActionTaskScript.getCache('processmaker_describe_modules_'+processid, function(){
							getDescribeObjects(vtinst, accessibleModules, moduleName, processid, involved_record, handleError(function(describeObjectResult){
								modules = describeObjectResult[0];
								moduleName = describeObjectResult[1];
								involved_record = describeObjectResult[2];
								i++;
							
								fillSelectBox('task-fieldnames', modules, moduleName, involved_record);
								jQuery('#task-fieldnames').prev('.populateFieldGroup').show();
								
								fillSelectBox('task-subjectfields', modules, moduleName, involved_record, function(e){return (e['type']['name']!='file' && e['type']['name']!='text');});
								jQuery('#task-subjectfields').prev('.populateFieldGroup').show();
								
								fillSelectBox('task-emailfields_sendername', modules, moduleName, involved_record, function(e){return (e['type']['name']!='file' && e['type']['name']!='text');});
								jQuery('#task-emailfields_sendername').prev('.populateFieldGroup').show();
								
								fillSelectBox('task-emailfields', modules, moduleName, involved_record, function(e){return e['type']['name']=='email';});
								
								if (i == jQuery(involved_records).length) {
									appendDynaformOptions(jQuery('#task-fieldnames'),dynaform_options,'all');
									jQuery('#task-fieldnames').unbind('change');
									jQuery('#task-fieldnames').change(function(){
										me.populateField('append_textarea',CKEDITOR.instances.save_content,this,'content'); //crmv@106857
									});
									
									appendDynaformOptions(jQuery('#task-subjectfields'),dynaform_options,'all');
									jQuery('#task-subjectfields').unbind('change');
									jQuery('#task-subjectfields').change(function(){
										me.populateField('append_input_space',jQuery(jQuery('#save_subject').get()),this,'subject'); //crmv@106857
									});
									
									appendDynaformOptions(jQuery('#task-emailfields_sendername'),dynaform_options,'all');
									jQuery('#task-emailfields_sendername').unbind('change');
									jQuery('#task-emailfields_sendername').change(function(){
										me.populateField('append_input_space',jQuery(jQuery('#save_sendername').get()),this,'sendername'); //crmv@106857
									});
									
									appendDynaformOptions(jQuery('#task-emailfields'),dynaform_options,'email');
									//crmv@100591
									if (jQuery(elements_actors).length > 0 && !checkSelectBoxDuplicates(jQuery('#task-emailfields'),alert_arr.LBL_PM_ELEMENTS_ACTORS)) {
										var append = '<optgroup label="'+alert_arr.LBL_PM_ELEMENTS_ACTORS+'">';
										jQuery.each(elements_actors, function(fieldvalue, fieldlabel){
											append += '<option value="'+fieldvalue+'">'+fieldlabel+'</value>';
										});
										append += '</optgroup>';
										jQuery('#task-emailfields').append(append);
									}
									//crmv@100591e
									jQuery('#task-emailfields').unbind('change');
									jQuery('#task-emailfields').change(function(){
										me.populateField('append_input_comma',jQuery(jQuery('#save_sender').get()),this,'sender'); //crmv@106857
									});
									jQuery('#task-emailfields').show();
									
									//crmv@181281 removed code
									
									// crmv@147433
									appendExtWSOptions(jQuery('#task-emailfields_sendername'),extws_options);
									appendExtWSOptions(jQuery('#task-subjectfields'),extws_options);
									appendExtWSOptions(jQuery('#task-fieldnames'),extws_options);
									// crmv@147433e
									
									last();
								}
							}));
						});
					});
				}));
			}));
		},
		
		changeCampaignType: function() {
			var ctype = jQuery('#campaign_type').val();
			
			if (ctype == 'process') {
				jQuery('#campaign_proc_cont').show();
			} else {
				jQuery('#campaign_proc_cont').hide();
			}
			if (ctype == 'existing') {
				jQuery('#campaign_id_cont').show();
			} else {
				jQuery('#campaign_id_cont').hide();
			}
		},
		
		setReturnCampaign: function(campaignid, entityname, fieldname) {
			var form = document.actionform;
			if (form) {
				var domnode_id = form.elements[fieldname];
				var domnode_display = form.elements[fieldname+'_display'];
				if (domnode_id) domnode_id.value = campaignid;
				if(domnode_display) domnode_display.value = entityname.replace(/&amp;/g, '&');
			}
		},
		
		openSelectRecipients: function() {
			var url = "index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=select_nl_recipients";
			openPopup(url,"NewsletterWizard","width=750,height=602,menubar=no,toolbar=no,location=no,status=no,resizable=no,scrollbars=yes");
		},
		
		popupSelectRecipients: function() {
			var list = nlwGetRecipients();
			
			// populate the parent list
			jQuery.each(list, function(module, item) {
				// find the entityname
				//jQuery.each(item, function(idx, ))
				if (item.ids) {
					jQuery.each(item.ids, function(idx, crmid) {
						var spanid = 'nlw_item_'+module+'_'+crmid;
						var ename = jQuery('#'+spanid).find('td').first().text();
						parent.ActionNewsletterScript.addRecipient(module, crmid, ename, ename);
					});
				}
			});
			// and close the popup
			closePopup();
		},
		
		addProcessRecipient: function() {
			var me = this,
				value = jQuery('#recipient_proc_record').val();
				label = jQuery('#recipient_proc_record option:selected').text();
			
			if (!value) return;
			
			var pieces = value.split(':');
			var crmid = pieces[0];
			var module = pieces[1];
			
			// shorten the label
			if (label.length > 20) {
				var shortlabel = label.substr(0,17) + '...';
			} else {
				var shortlabel = label;
			}
			
			me.addRecipient(module, value, shortlabel, label);
		},
		
		loadRecipients: function(boxlist) {
			var me = this;
				boxlist = JSON.parse(boxlist);
			jQuery.each(boxlist, function(idx, box) {
				me.addRecipient(box.module, box.crmid, box.ename, box.entityname, true);
			});
		},
		
		// ename is already the complete name for the box
		addRecipient: function(module, crmid, ename, fullname, initial) {
			var box = jQuery('#recipients_boxes');
				spanid = 'nlw_item_'+module+'_'+crmid,
				existing = document.getElementById(spanid);
				/*singlelabel = jQuery('#SLVContainer_'+listid).find('#mod_singlelabel').val(),
				shortname = (entityname.length > 20 ? entityname.substr(0,10) + '...' : entityname),
				ename = singlelabel+': '+shortname;
				*/

			if (existing) return;

			// create a box
			var span = '<span id="'+spanid+'" class="addrBubble" title="'+fullname.replace('"', '&quot;')+'">'
				+'<table cellpadding="3" cellspacing="0" class="small">'
				+'<tr>'
				+	'<td>'+ename+'</td>'
				+	'<td rowspan="2" align="right" valign="top"><div class="ImgBubbleDelete" onClick="ActionNewsletterScript.removeRecipient(\''+spanid+'\', \''+crmid+'\');"><i class="vteicon small">clear</i></div></td>'
				+'</tr>'
				+'</table>'
				+'</span>';

			box.append(span);
			
			if (!initial) {
				// add it to the hidden field
				var listid = ''+crmid,
					list = jQuery('#save_recipients').val().split(';');
					
				if (list.indexOf(crmid) < 0) {
					list.push(crmid);
					jQuery('#save_recipients').val(list.join(';'));
				}
			}
		},
		
		removeRecipient: function(spanid, crmid) {
			var box = jQuery('#recipients_boxes'),
				span = document.getElementById(spanid);

			if (span) {
				jQuery(span).remove();
			}
				
			// remove it from the hidden field
			var listid = ''+crmid,
				list = jQuery('#save_recipients').val().split(';'),
				listidx = list.indexOf(crmid);
				
			if (listidx >= 0) {
				list.splice(listidx, 1);
				jQuery('#save_recipients').val(list.join(';'));
			}
		},
		
		openSelectTemplate: function() {
			var url = "index.php?module=Newsletter&action=NewsletterAjax&file=widgets/TemplateEmailList&record=0&mode=processmaker";
			openPopup(url,"TemplateEmailList","width=750,height=602,menubar=no,toolbar=no,location=no,status=no,resizable=no,scrollbars=yes");
		},
		
		selectTemplate: function(nlid, templateid, templatename) {
			parent.jQuery('#templatename').val(templatename);
			parent.jQuery('#save_template').val(templateid);
			closePopup();
			parent.ActionNewsletterScript.loadTemplate();
		},
		
		loadTemplate: function() {
			var me = this,
				templateid = jQuery('#save_template').val();
				
			jQuery.ajax({
				url: "index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=load_nl_template&templateid="+templateid,
				type: 'GET',
				success: function(data) {
					try {
						data = JSON.parse(data);
					} catch (e) {
						// error!
						return;
					}
					jQuery('#save_subject').val(data.subject);
					CKEDITOR.instances.save_content.setData(data.body);
				}
			});
		},
		
		//crmv@106857
		populateField: function(mode,target,field,fieldname){
			var me = this,
				value = jQuery(field).val();
			//crmv@112299
			if (value == 'back') {
				restorePopulateFieldGroup(field);
			//crmv@112299e
			} else if (value.indexOf('::') != -1) {
				// show table fields options
				if (jQuery('#actionform [name="cycle_action"]').val() != '') {
					// check if I am in a cycle
					//crmv@182891
					var cycle_field = jQuery('#actionform [name="cycle_field"]').val().split(':');
					if (value.indexOf('$'+cycle_field[0]+'-'+cycle_field[3]+'::') == 0
						|| (value.indexOf('$'+cycle_field[0]+'-(') == 0 && value.indexOf(' '+cycle_field[3]+'::') > -1)
						|| value.indexOf('$DF'+cycle_field[0]+'-'+cycle_field[1]+'::') == 0) {
					//crmv@182891e
						jQuery("#tablefields_options_"+fieldname+" .cycle_opt").show();
					} else {
						jQuery("#tablefields_options_"+fieldname+" .cycle_opt").hide();
					}
				}
				//jQuery(field).parent().parent().find('.editoptions .populateField').css('max-width','300px');
				jQuery("#tablefields_options_"+fieldname+" option:eq(0)").prop('selected', true);
				jQuery("#tablefields_options_"+fieldname).show();
			} else {
				// hide table fields options
				//jQuery(field).parent().parent().find('.editoptions .populateField').css('max-width','400px');
				jQuery("#tablefields_options_"+fieldname).hide();
				jQuery('#tablefields_seq_'+fieldname).hide();
				jQuery('#tablefields_seq_btn_'+fieldname).hide();
				// end
				me.insertAtCursor(mode,target,value);
			}
		},
		changeTableFieldOpt: function(mode,target,fieldname,dropdownid,obj){
			var me = this,
				value = obj.value;
			if (value == 'seq') {
				jQuery('#tablefields_seq_'+fieldname).show().focus();
				jQuery('#tablefields_seq_btn_'+fieldname).show();
			} else {
				jQuery('#tablefields_seq_'+fieldname).hide();
				jQuery('#tablefields_seq_btn_'+fieldname).hide();
				if (value != '') me.insertTableFieldValue(mode,target,fieldname,dropdownid,value);
			}
		},
		insertTableFieldValue: function(mode,target,fieldname,dropdownid,value){
			var me = this,
				parent_value = jQuery('#'+dropdownid).val();
			if (value == 'seq') {
				var sequence = parseInt(jQuery('#tablefields_seq_'+fieldname).val());
				if (isNaN(sequence) || sequence <= 0) return false;
				else value += ':'+sequence;
			}
			me.insertAtCursor(mode,target,parent_value+':'+value);
		},
		insertAtCursor: function(mode,target,value) {
			if (mode == 'append_textarea') {
				target.insertHtml(value);
			} else if (mode == 'append_input_space') {
				target.val(target.val()+' '+value);
			} else if (mode == 'append_input_comma') {
				var oldvalue = target.val().trim();
				target.val((oldvalue ? oldvalue+',' : '')+value);
			} else if (mode == 'overwrite_input') {
				target.val(value);
			}
		}
	}
}
// crmv@126696e

//crmv@146671 crmv@OPER10174
if (typeof(ActionCallWSScript) == 'undefined') {
	ActionCallWSScript = {
		objectName: 'ActionCallWSScript',
		
		wsinfo: null, // crmv@147433
		
		loadForm: function(processid,id,action_type,action_id) {
			var me = this,
				extwsid = jQuery('#extwsid').val();
			
			if (!extwsid) {
				jQuery('#editForm').html('');
				return;
			}
				
			jQuery.fancybox.showLoading();
			var url = 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker/actions/CallExtWSForm&id='+processid+'&elementid='+id+'&action_id='+action_id+'&extwsid='+extwsid;
			jQuery.ajax({
				url: url,
				method: 'POST',
				success: function(data) {

					// crmv@147433
					var res = data.split('|&|&|&|');
					if (res[0] != '') var involved_records = JSON.parse(res[0]); else var involved_records = {};
					if (res[1] != '') var wsinfo = JSON.parse(res[1]); else var wsinfo = {};
					if (res[2] != '') var metadata = JSON.parse(res[2]); else var metadata = {};
					if (res[3] != '') var dynaform_options = JSON.parse(res[3]); else var dynaform_options = {};
					if (res[4] != '') var elements_actors = JSON.parse(res[4]); else var elements_actors = {};	//crmv@100591
					if (res[5] != '') var extws_options = JSON.parse(res[5]); else var extws_options = {};
					if (res[6] != '') var wsinitfields = JSON.parse(res[6]); else var wsinitfields = {};
					try {
						jQuery('#editForm').html(res[7]);
					} catch(err) {
					    console.error(err.message);
					}
					
					me.wsinfo = wsinfo;
					
					var params = {
						'processid':processid, //crmv@164486
						'involved_records':involved_records,
						/*'form_data':form_data,
						'picklist_values':picklist_values,
						'reference_values':reference_values,
						'reference_users_values':reference_users_values,
						'boolean_values':boolean_values,
						'date_values':date_values,*/
						'dynaform_options':dynaform_options,
						'elements_actors':elements_actors,
						'extws_options':extws_options
					}
					ActionTaskScript.loadFormEditOptions(me,'',params,function(){
						jQuery.each(wsinitfields, function(k,field){
							if (field[2] == '!DEFAULT!' || field[2] == '!DONTUSE!') {
								jQuery('.editoptions[fieldname="'+field[0]+'"] .populateFieldGroup').focus().val(field[2]).change();
							} else if (field[2] != '') {
								me.populateFieldValue(field[0], field[2]);
							}
						});
					});
					// crmv@147433e
					
					if (metadata.extra_params && Object.prototype.toString.call(metadata.extra_params) == '[object Object]') {
						for (var pname in metadata.extra_params) {
							me.addParam(pname, metadata.extra_params[pname]);
						}
					}
					if (metadata.extra_results && Object.prototype.toString.call(metadata.extra_results) == '[object Object]') {
						for (var rname in metadata.extra_results) {
							me.addResult(rname, metadata.extra_results[rname]);
						}
					}
				}
			});
		},
		
		// crmv@147433
		handleSpecialValue: function(self) {
			var me = this,
				val = jQuery(self).val(),
				fieldname = jQuery(self).closest('div.editoptions').attr('fieldname'),
				type = (fieldname.match(/^param_/) ? 'params' : 'results'),
				fieldid = parseInt(fieldname.replace('param_', '').replace('result_', ''));
				
			if (val == '!DEFAULT!' || val == '!DONTUSE!') {
				// find the original value
				var oval = me.wsinfo[type][fieldid].value;
				var $field = jQuery('#'+fieldname),
					cont = $field.closest('div');
				
				// put it back in readonly
				var newinput = $field.clone().attr('type', 'hidden').val(val);
				cont.removeClass('dvtCellInfo').addClass('dvtCellInfoOff').html(newinput).append(oval);
				
				if (val == '!DONTUSE!') {
					// strike it
					cont.css('text-decoration', 'line-through');
				} else {
					cont.css('text-decoration', '');
				}
			}
		},
		
		populateField: function(field){
			var me = this;
			var tagField = jQuery(field);
			var fieldname = jQuery(field).parent().attr('fieldname');
			var $field = jQuery('#editForm [name="'+fieldname+'"]');
			var value = jQuery(tagField).val();
			
			// first make the field rw if it was ro
			if ($field.attr('type') == 'hidden') {
				var newinput = $field.clone().attr('type', 'text').val('');
				$field.closest('div').removeClass('dvtCellInfoOff').addClass('dvtCellInfo').html(newinput);
			}
			// then populate it!
			ActionCreateScript.populateField.call(ActionCreateScript, field);
		},
		populateFieldValue: function(fieldname, fieldvalue){
			var field = jQuery('.editoptions[fieldname="'+fieldname+'"] .populateField'),
				$field = jQuery('#editForm [name="'+fieldname+'"]');
			
			// first make the field rw if it was ro
			if ($field.attr('type') == 'hidden') {
				var newinput = $field.clone().attr('type', 'text').val('');
				$field.closest('div').removeClass('dvtCellInfoOff').addClass('dvtCellInfo').html(newinput);
			}
			//crmv@182561
			if (fieldname == 'auth_password' && jQuery('#auth_password').prop('type') == 'password' && value != 'back') {
				jQuery('#auth_password').val('');
				jQuery('#auth_password').prop('type','text');
			}
			//crmv@182561e
			// then populate it!
			ActionCreateScript.populateField.call(ActionCreateScript, field, fieldvalue);
		},
		// crmv@147433e
		
		addAuth: function() {
			var me = this;
			jQuery('#table_auth').show();
			jQuery('#button_auth').hide();
		},
		
		addParam: function(name, value) {
			var me = this,
				lastid = parseInt(jQuery('#last_param_id').val()),
				newid = lastid+1,
				header = jQuery('#header_custom_params'),
				table = jQuery('#table_custom_params'),
				tpl = jQuery('#param_row_tpl');
				
			var newrow = tpl.clone().attr('id', null).show();
			
			// fix id and name
			newrow.find('input[name=param_name]').attr('name', 'param_name_'+newid).attr('id', 'param_name_'+newid);
			newrow.find('input[name=param_value]').attr('name', 'param_value_'+newid).attr('id', 'param_value_'+newid);
			newrow.find('div.editoptions[fieldname=param_name]').attr('fieldname', 'param_name_'+newid);
			newrow.find('div.editoptions[fieldname=param_value]').attr('fieldname', 'param_value_'+newid);
			
			// set the value also
			if (name) {
				newrow.find('input[name=param_name_'+newid+']').val(name);
				newrow.find('input[name=param_value_'+newid+']').val(value);
			}
			
			table.append(newrow);
			table.show();
			header.show();
			
			jQuery('#last_param_id').val(newid);
		},
		
		delParam: function(self) {
			var me = this,
				header = jQuery('#header_custom_params'),
				table = jQuery('#table_custom_params');
				
			jQuery(self).closest('tr').remove();
			
			if (table.find('tr').length <= 1) {
				table.hide();
				header.hide();
			}
		},
		
		addResult: function(name, value) {
			var me = this,
				lastid = parseInt(jQuery('#last_result_id').val()),
				newid = lastid+1,
				header = jQuery('#header_custom_results'),
				table = jQuery('#table_custom_results'),
				tpl = jQuery('#result_row_tpl');
				
			var newrow = tpl.clone().attr('id', null).show();
			
			// fix id and name
			newrow.find('input[name=result_name]').attr('name', 'result_name_'+newid).attr('id', 'result_name_'+newid);
			newrow.find('input[name=result_value]').attr('name', 'result_value_'+newid).attr('id', 'result_value_'+newid);
			newrow.find('div.editoptions[fieldname=result_name]').attr('fieldname', 'result_name_'+newid);
			newrow.find('div.editoptions[fieldname=result_value]').attr('fieldname', 'result_value_'+newid);
			
			// set the value also
			if (name) {
				newrow.find('input[name=result_name_'+newid+']').val(name);
				newrow.find('input[name=result_value_'+newid+']').val(value);
			}
			
			table.append(newrow);
			table.show();
			header.show();
			
			jQuery('#last_result_id').val(newid);
		},
		
		delResult: function(self) {
			var me = this,
				header = jQuery('#header_custom_results'),
				table = jQuery('#table_custom_results');
				
			jQuery(self).closest('tr').remove();
			
			if (table.find('tr').length <= 1) {
				table.hide();
				header.hide();
			}
		},
		
		// crmv@147433
		validate: function(values) {
			var me = this;
			var retlist = [];
			
			// add the standard names
			if (me.wsinfo && me.wsinfo.results.length > 0) {
				for (var i=0; i<me.wsinfo.results.length; ++i) {
					retlist.push(me.wsinfo.results[i].name);
				}
			}
			
			// check the additional ones
			for (var key in values) {
				var value = values[key];
				if (key.match(/^result_name_/)) {
					var rid = parseInt(key.replace('result_name_', ''));
					var rval = values['result_value_'+rid];
					// check if value is empty
					if (rval == '') {
						vtealert(alert_arr.LBL_EXTWS_EMPTY_RETURN_FIELD)
						return false;
					}
					// check if name is duplicate
					if (retlist.indexOf(value) >= 0) {
						vtealert(alert_arr.LBL_EXTWS_DUP_RETURN_FIELDS)
						return false;
					}
					retlist.push(value);
				}
			}
			
			return true;
		}
		// crmv@147433e
	}
}
// crmv@146671e crmv@OPER10174e

//crmv@183346
if (typeof(ActionModNotificationScript) == 'undefined') {
	ActionModNotificationScript = {
		objectName: 'ActionModNotificationScript',
		loadForm: function(module,processid,id,action_type,action_id,tablerow_mode) {
			var me = ActionModNotificationScript;
			if (typeof(tablerow_mode) == 'undefined') var tablerow_mode = false;
			jQuery.fancybox.showLoading();
			
			var url = 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker/actions/ModNotificationForm&mod='+module+'&id='+processid+'&elementid='+id+'&action_id='+action_id;
			url += '&cycle_field='+encodeURIComponent(jQuery('input[name=cycle_field]').val() || '');
			url += '&cycle_action='+encodeURIComponent(jQuery('input[name=cycle_action]').val() || '');
			url += '&cycle_fieldname='+encodeURIComponent(jQuery('input[name=cycle_fieldname]').val() || '');//crmv@203075 add fieldname to check in CreateForm and not retrieve prod blocks in cycle related
			if (tablerow_mode) url += '&tablerow_mode=1'; else url += '&tablerow_mode=0';
			
			jQuery.ajax({
				'url': url,
				'type': 'POST',
				success: function(data) {
					var res = data.split('|&|&|&|');
					if (res[0] != '') var involved_records = JSON.parse(res[0]); else var involved_records = {};
					if (res[1] != '') var form_data = JSON.parse(res[1]); else var form_data = {};
					if (res[2] != '') var picklist_values = JSON.parse(res[2]); else var picklist_values = {};
					if (res[3] != '') var reference_values = JSON.parse(res[3]); else var reference_values = {};
					if (res[4] != '') var reference_users_values = JSON.parse(res[4]); else var reference_users_values = {};
					if (res[5] != '') var boolean_values = JSON.parse(res[5]); else var boolean_values = {};
					if (res[6] != '') var date_values = JSON.parse(res[6]); else var date_values = {};
					if (res[7] != '') var dynaform_options = JSON.parse(res[7]); else var dynaform_options = {};
					if (res[8] != '') var elements_actors = JSON.parse(res[8]); else var elements_actors = {};	//crmv@100591
					if (res[9] != '') var extws_options = JSON.parse(res[9]); else var extws_options = {}; // crmv@146671
					var params = {
						'processid':processid,
						'involved_records':involved_records,
						'form_data':form_data,
						'picklist_values':picklist_values,
						'reference_values':reference_values,
						'reference_users_values':reference_users_values,
						'boolean_values':boolean_values,
						'date_values':date_values,
						'dynaform_options':dynaform_options,
						'elements_actors':elements_actors,
						'extws_options':extws_options, // crmv@146671
					}
					// TODO check if is string
					jQuery('#other_related_to').val(form_data['related_to']);
					ActionTaskScript.loadFormEditOptions(me,module,params);
				}
			});
		},
		//crmv@106857
		populateField: function(field, value){ //crmv@OPER10174
			ActionCreateScript.populateField(field, value);
		},
		changeTableFieldOpt: function(obj, fieldname){
			ActionCreateScript.changeTableFieldOpt(obj, fieldname);
		},
		insertTableFieldValue: function(obj, fieldname, value){
			ActionCreateScript.insertTableFieldValue(obj, fieldname, value);
		}
		//crmv@106857e
	}
}
//crmv@183346e

//crmv@187729
if (typeof(ActionCreatePDFScript) == 'undefined') {
	ActionCreatePDFScript = {
		objectName: 'ActionCreatePDFScript',
		loadForm: function(module,processid,id,action_type,action_id,tablerow_mode,mode) {
			var me = ActionCreatePDFScript;
			if (typeof(tablerow_mode) == 'undefined') var tablerow_mode = false;
			jQuery.fancybox.showLoading();
			
			var url = 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker/actions/CreatePDFForm&mode='+mode+'&mod='+module+'&id='+processid+'&elementid='+id+'&action_id='+action_id;
			url += '&cycle_field='+encodeURIComponent(jQuery('input[name=cycle_field]').val() || '');
			url += '&cycle_action='+encodeURIComponent(jQuery('input[name=cycle_action]').val() || '');
			url += '&cycle_fieldname='+encodeURIComponent(jQuery('input[name=cycle_fieldname]').val() || '');//crmv@203075 add fieldname to check in CreateForm and not retrieve prod blocks in cycle related
			if (tablerow_mode) url += '&tablerow_mode=1'; else url += '&tablerow_mode=0';
			
			jQuery.ajax({
				'url': url,
				'type': 'POST',
				success: function(data) {
					var res = data.split('|&|&|&|');
					if (res[0] != '') var involved_records = JSON.parse(res[0]); else var involved_records = {};
					if (res[1] != '') var form_data = JSON.parse(res[1]); else var form_data = {};
					if (res[2] != '') var picklist_values = JSON.parse(res[2]); else var picklist_values = {};
					if (res[3] != '') var reference_values = JSON.parse(res[3]); else var reference_values = {};
					if (res[4] != '') var reference_users_values = JSON.parse(res[4]); else var reference_users_values = {};
					if (res[5] != '') var boolean_values = JSON.parse(res[5]); else var boolean_values = {};
					if (res[6] != '') var date_values = JSON.parse(res[6]); else var date_values = {};
					if (res[7] != '') var dynaform_options = JSON.parse(res[7]); else var dynaform_options = {};
					if (res[8] != '') var elements_actors = JSON.parse(res[8]); else var elements_actors = {};	//crmv@100591
					if (res[9] != '') var extws_options = JSON.parse(res[9]); else var extws_options = {}; // crmv@146671
					var params = {
						'processid':processid,
						'involved_records':involved_records,
						'form_data':form_data,
						'picklist_values':picklist_values,
						'reference_values':reference_values,
						'reference_users_values':reference_users_values,
						'boolean_values':boolean_values,
						'date_values':date_values,
						'dynaform_options':dynaform_options,
						'elements_actors':elements_actors,
						'extws_options':extws_options, // crmv@146671
					}
					// TODO check if is string
					jQuery('#other_related_to_entity').val(form_data['related_to_entity']);
					ActionTaskScript.loadFormEditOptions(me,module,params);
				}
			});
		},
		
		reload_createpdf_form: function(module,processid,id,action_type,action_id,tablerow_mode,load_editview) {
			jQuery('#block_0').css("display", "none");
			jQuery('#block_1').css("display", "none");
			jQuery('#error_container').empty();
			var pdf_entity = jQuery('[name="pdf_entity"]').val();
			
			jQuery.ajax({
				'url': 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=reload_create_pdf&pdf_entity='+jQuery('[name="pdf_entity"]').val()+'&entity_mode='+load_editview+'&id='+jQuery('[name="id"]').val()+'&elementid='+jQuery('[name="elementid"]').val(),
				'type': 'POST',
				success: function(data) {
					if(data != ''){
						try {
							data = JSON.parse(data);
						} catch (e) {
							// error!
							return;
						}
						jQuery("#templatename").empty();
						jQuery("#foldername").empty();
						jQuery("#other_templatename").val("");
						jQuery("#other_foldername").val("");
						for(ct = 0; ct < data.templates.length; ct++){ 
							var option = jQuery('<option></option>').attr("value", data.templates[ct]).text(data.templates[ct]);
							jQuery("#templatename").append(option);
						}
						for(ct = 0; ct < data.folders.length; ct++){ 
							var option = jQuery('<option></option>').attr("value", data.folders[ct]).text(data.folders[ct]);
							jQuery("#foldername").append(option);
						}
						if(data.templates.length > 0 && data.folders.length > 0){
							jQuery('#block_0').css("display", "block");
							jQuery('#block_1').css("display", "block");
						}
						else{
							jQuery('#error_container').text(data.error);
						}
					}
				}
			});
		},
		//crmv@106857
		populateField: function(field, value){ //crmv@OPER10174
			ActionCreateScript.populateField(field, value);
		},
		changeTableFieldOpt: function(obj, fieldname){
			ActionCreateScript.changeTableFieldOpt(obj, fieldname);
		},
		insertTableFieldValue: function(obj, fieldname, value){
			ActionCreateScript.insertTableFieldValue(obj, fieldname, value);
		}
		//crmv@106857e
	}
}
// crmv@187729e

if (typeof(ProcessHelperScript) == 'undefined') {
	ProcessHelperScript = {
		
		initPopulateFields(element,elements_actors,form_data) {
			switch(element) {
			    case 'task-fieldnames':
			    	//crmv@100591
					if (jQuery(elements_actors).length > 0 && !checkSelectBoxDuplicates(jQuery('#task-fieldnames'),alert_arr.LBL_PM_ELEMENTS_ACTORS)) {
						var append = '<optgroup label="'+alert_arr.LBL_PM_ELEMENTS_ACTORS+'">';
						jQuery.each(elements_actors, function(fieldvalue, fieldlabel){
							append += '<option value="'+fieldvalue+'">'+fieldlabel+'</value>';
						});
						append += '</optgroup>';
						jQuery('#task-fieldnames').append(append);
					}
					//crmv@100591e
					//crmv@109685
					jQuery('#editForm .editoptions[optionstype="fieldnames"]').each(function(){
						jQuery(this).html('<select class="populateFieldGroup" onfocus="ActionTaskScript.populateSelectBox(this,\'fieldnames\')"><option value="">'+alert_arr.LBL_SELECT_OPTION_DOTDOTDOT+'</option></select><select style="display:none" class="populateField" onchange="ActionUpdateScript.populateField(this)"></select>');	//crmv@112299 crmv@139690
					});
					//crmv@109685e
			        break;
			        
			    case 'task-pickfieldnames':
			    	jQuery('#editForm .editoptions[optionstype="pickfieldnames"]').each(function(){
						jQuery(this).html('<select class="populateFieldGroup" onfocus="ActionTaskScript.populateSelectBox(this,\'pickfieldnames\')"><option value="">'+alert_arr.LBL_SELECT_OPTION_DOTDOTDOT+'</option></select><select style="display:none" class="populateField" onchange="ActionUpdateScript.populateField(this)"></select>');	//crmv@112299 crmv@139690
					});
			        break;
			        
			    case 'task-smownerfieldnames':
			    	//crmv@100591
					if (jQuery(elements_actors).length > 0 && !checkSelectBoxDuplicates(jQuery('#task-smownerfieldnames'),alert_arr.LBL_PM_ELEMENTS_ACTORS)) {
						var append = '<optgroup label="'+alert_arr.LBL_PM_ELEMENTS_ACTORS+'">';
						jQuery.each(elements_actors, function(fieldvalue, fieldlabel){
							append += '<option value="'+fieldvalue+'">'+fieldlabel+'</value>';
						});
						append += '</optgroup>';
						jQuery('#task-smownerfieldnames').append(append);
					}
					//crmv@100591e
					
					jQuery('#editForm .editoptions[optionstype="smownerfieldnames"]').each(function(){
						jQuery(this).html('<select class="populateFieldGroup" onfocus="ActionTaskScript.populateSelectBox(this,\'smownerfieldnames\')"><option value="">'+alert_arr.LBL_SELECT_OPTION_DOTDOTDOT+'</option></select><select style="display:none" class="populateField" onchange="ActionUpdateScript.populateField(this)"></select>');	//crmv@112299 crmv@139690
					});
					
					if (jQuery('#assigned_user_id_type').val() == 'O' && form_data['assigned_user_id'] != undefined) {
						jQuery('#other_assigned_user_id').val(form_data['assigned_user_id']);
					}
					//crmv@106856
					if (form_data['assigned_user_id'] == 'advanced_field_assignment') {
						jQuery('#assigned_user_id_type').val('A');
						jQuery('.editoptions[fieldname="other_assigned_user_id"]').hide();
						jQuery("#other_assigned_user_id").hide();
						jQuery('#advanced_field_assignment_button_assigned_user_id').show();
					}
					if (jQuery('#other_assigned_user_id').length > 0) ActionTaskScript.showSdkParamsInput(jQuery('#other_assigned_user_id'),'assigned_user_id');	//crmv@113527
					//crmv@106856e
			    	break;
			    	
			    case 'task-referencefieldnames':
		    		jQuery('#editForm .editoptions[optionstype="referencefieldnames"]').each(function(){
						jQuery(this).html('<select class="populateFieldGroup" onfocus="ActionTaskScript.populateSelectBox(this,\'referencefieldnames\')"><option value="">'+alert_arr.LBL_SELECT_OPTION_DOTDOTDOT+'</option></select><select style="display:none" class="populateField" onchange="ActionUpdateScript.populateField(this)"></select>');	//crmv@112299 crmv@139690
					});
		    		jQuery('#related_to').parent().hide();
		    		jQuery('#div_other_related_to').show();
		    		jQuery('.editoptions[fieldname="other_related_to"]').show();
		    		jQuery('#other_related_to').val(jQuery('#related_to').val());
			    	break;
			}
		},
			
		initPopulateField: function(processid,involved_records,dynaform_options,elements_actors,form_data) {
			var me = this,
				i = 0,
				involved_records = JSON.parse(involved_records),
				dynaform_options = JSON.parse(dynaform_options),
				elements_actors = JSON.parse(elements_actors);	//crmv@100591
			
			if (ActionTaskScript.log_time) var start = Date.now();

			if (typeof(form_data) != 'undefined') form_data = JSON.parse(form_data); else var form_data = {};

			jQuery.fancybox.showLoading();
			ActionTaskScript.getCache('processmaker_entity_options_'+processid, function(processmaker_entity_options){

				if (processmaker_entity_options) {
					
					jQuery.each(processmaker_entity_options,function(element,optgroups){
						var append = '';
						jQuery.each(optgroups,function(optgrouplabel,options){
							if (!checkSelectBoxDuplicates(jQuery('#'+element),optgrouplabel)) {
								if (optgrouplabel != '') append += '<optgroup label="'+optgrouplabel+'">';
								jQuery.each(options,function(key,value){
									append += '<option value="'+key+'">'+value+'</option>';
								});
								if (optgrouplabel != '') append += '</optgroup>';
							}
						});
						jQuery('#'+element).append(append);
						me.initPopulateFields(element,elements_actors,form_data);
					});
					filterPopulateField();	//crmv@112299
					me.afterLastIteration();
					
					if (ActionTaskScript.log_time) {
						var end = Date.now();
						var total_time = end - start;
						console.log('ends ProcessHelperScript > initPopulateField with cache in ',total_time);
					}
					
				} else {
					var vtinst = new VtenextWebservices("webservice.php",undefined,undefined,true);
					vtinst.extendSession(handleError(function(result){
						vtinst.listTypes(handleError(function(accessibleModules) {
							accessibleModulesInfo = accessibleModules;
							if (jQuery.isEmptyObject(involved_records)) { //crmv@179315 check if there are involved records
								me.afterLastIteration();
								return false;
							}
							ActionTaskScript.getCache('processmaker_describe_modules_'+processid, function(){
								jQuery.each(involved_records,function(key,involved_record){
									var moduleName = involved_record.module;
									if (moduleName == '' || moduleName == null) {	// check if there are involved records
										i++;
										if (i == jQuery(involved_records).length) me.afterLastIteration();	// check last
										return;
									}
									getDescribeObjects(vtinst, accessibleModules, moduleName, processid, involved_record, handleError(function(describeObjectResult){
										modules = describeObjectResult[0];
										moduleName = describeObjectResult[1];
										involved_record = describeObjectResult[2];
										
										i++;
										fillSelectBox('task-fieldnames', modules, moduleName, involved_record);
										fillSelectBox('task-pickfieldnames', modules, moduleName, involved_record, function(e){return (e['type']['name']=='picklist' || e['type']['name']=='multipicklist');});
										fillSelectBox('task-smownerfieldnames', modules, moduleName, involved_record, function(e){return (e['type']['name']=='reference' && e['type']['refersTo'][0]=='Users');});
										fillSelectBox('task-referencefieldnames', modules, moduleName, involved_record, function(e){return (e['type']['name']=='reference' && e['type']['refersTo'][0]!='Users' && e['type']['refersTo'][0]!='Currency');});
										// last
										if (i == jQuery(involved_records).length) {
											
											if (ActionTaskScript.log_time) {
												var getDescribeObjects_time_end = Date.now();
												var getDescribeObjects_time = getDescribeObjects_time_end - start;
												console.log('ends ProcessHelperScript > getDescribeObjects in ',getDescribeObjects_time);
											}
											
											// text
											appendDynaformOptions(jQuery('#task-fieldnames'),dynaform_options,'all');
											me.initPopulateFields('task-fieldnames',elements_actors,form_data);
											
											// picklist
											appendDynaformOptions(jQuery('#task-pickfieldnames'),dynaform_options,'all');
											me.initPopulateFields('task-pickfieldnames',elements_actors,form_data);
											
											// owner
											appendDynaformOptions(jQuery('#task-smownerfieldnames'),dynaform_options,'user');
											me.initPopulateFields('task-smownerfieldnames',elements_actors,form_data);
											
											// reference
											appendDynaformOptions(jQuery('#task-referencefieldnames'),dynaform_options,'reference');
											me.initPopulateFields('task-referencefieldnames',elements_actors,form_data);
											
											filterPopulateField();	//crmv@112299
											
											if (ActionTaskScript.enable_cache && ActionTaskScript.__overwriteCache) ActionTaskScript.setCache('processmaker_describe_modules_'+processid, ActionTaskScript.__cache['processmaker_describe_modules_'+processid]);
											
											me.afterLastIteration();
											
											if (ActionTaskScript.log_time) {
												var end = Date.now();
												var display_time = end - getDescribeObjects_time_end;
												var total_time = end - start;
												console.log('ends ProcessHelperScript > initPopulateField : display:',display_time,' total:',total_time);
											}
										}
									}));
								});
							});
						}));
					}));
				}
			});
		},
		afterLastIteration: function() {
			jQuery.fancybox.hideLoading();
		},
		loadPopulateField: function(fieldinfo) {
			// TODO load the correct interface using uitype of the field "fieldprop_default" and populateFieldOptions with the relative field
			jQuery('#fieldprop_default').val(fieldinfo['default']);
			jQuery('#defaultValueContainer').html('<select class="populateFieldGroup" onfocus="ActionTaskScript.populateSelectBox(this,\'fieldnames\')"><option value="">'+alert_arr.LBL_SELECT_OPTION_DOTDOTDOT+'</option></select><select style="display:none" class="populateField" onchange="ProcessHelperScript.populateField(this)"></select>');	//crmv@112299 crmv@139690
			filterPopulateField();	//crmv@112299
		},
		populateField: function(field){
			var tagField = jQuery(field);
			var field = jQuery('#fieldprop_default');
			var value = jQuery(tagField).val();
			//crmv@112299
			if (value == 'back') {
				restorePopulateFieldGroup(tagField);
			//crmv@112299e
			} else if (value != '') insertAtCursor(field.get(0), value);
		},
		openImportDynaformBlocks: function(){
			if (!ProcessMakerScript.sessionCheck()) return false; // crmv@189903
			
			var processid = jQuery('.form-helper-shape #processid').val();
			var id = jQuery('.form-helper-shape #elementid').val();
			//crmv@160837 some code removed
			openPopup('index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=openimportdynaformblocks&id='+processid+'&elementid='+id); //crmv@160837
		},
		checkAllDynaformBlocks: function(elementid,checked){
			jQuery('[id^="import_'+elementid+'"]').prop('checked',checked);
		},
		importDynaformBlocks: function(processmakerid,elementid){
			//crmv@160837
			var mmaker = {};
			jQuery.each(parent.jQuery('#module_maker_form').serializeArray(), function(){
				mmaker[this.name] = this.value;
			});
			//crmv@160837e
			var dynaformblocks = [];
			jQuery.each(jQuery('[id^="import_"]').serializeArray(), function(){
				dynaformblocks.push(this.value);
			});
			jQuery.ajax({
				'url': 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=importdynaformblocks&id='+processmakerid+'&elementid='+elementid,
				'type': 'POST',
				'data': jQuery.param({'dynaformblocks':dynaformblocks,'mmaker':mmaker}), //crmv@160837
				success: function(data) {
					if (data != '') parent.jQuery('#mmaker_div_allblocks').html(data);
					closePopup();
				}
			});
		},
		//crmv@160837
		openImportModuleBlocks: function(){
			if (!ProcessMakerScript.sessionCheck()) return false; // crmv@189903
			
			var processid = jQuery('.form-helper-shape #processid').val();
			var id = jQuery('.form-helper-shape #elementid').val();
			openPopup('index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=openimportmoduleblocks&id='+processid+'&elementid='+id);
		},
		loadModuleBlocks: function(processmakerid,elementid,record_involved){
			var me = this;
			
			_load = function() {
				jQuery.fancybox.showLoading();
				jQuery.ajax({
					'url': 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=loadmoduleblocks&id='+processmakerid+'&record_involved='+record_involved,
					'type': 'POST',
					success: function(data) {
						jQuery('#blocks_container').html(data);
						jQuery.fancybox.hideLoading();
					}
				});
			}
			
			if (jQuery('[id^="import_"]').serializeArray().length > 0) {
				vteconfirm('Vuoi importare i blocchi selezionati?', function(yes) {
					if (yes) {
						me.importModuleBlocks(processmakerid,elementid,function(){
							_load();
						})
					} else {
						_load();
					}
				});
			} else {
				_load();
			}
		},
		importModuleBlocks: function(processmakerid,elementid,callback){
			var mmaker = {};
			jQuery.each(parent.jQuery('#module_maker_form').serializeArray(), function(){
				mmaker[this.name] = this.value;
			});
			var module_mmaker = {};
			var import_blocks = [];
			jQuery.each(jQuery('[id^="import_"]').serializeArray(), function(){
				import_blocks.push(this.value);
				var import_block = this.value;
				jQuery.each(jQuery('#module_maker_form').serializeArray(), function(){
					if (this.name.indexOf('block'+import_block+'_') > -1 || this.name.indexOf('field'+import_block+'_') > -1) {
						module_mmaker[this.name] = this.value;
					}
				});
			});
			jQuery.ajax({
				'url': 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=importmoduleblocks&id='+processmakerid+'&elementid='+elementid,
				'type': 'POST',
				'data': jQuery.param({'mmaker':mmaker,'module_mmaker':module_mmaker,'import_blocks':import_blocks}),
				success: function(data) {
					if (data != '') parent.jQuery('#mmaker_div_allblocks').html(data);
					if (typeof callback == 'function') {
						callback(data);
					} else {
						closePopup();
					}
				}
			});
		}
		//crmv@160837e
	}
}

//crmv@112299
function filterPopulateField() {
	jQuery('.populateFieldGroup').each(function(){
		var obj = this,
			str = '';
		str += '<option value="">'+alert_arr.LBL_SELECT_OPTION_DOTDOTDOT+'</option>';
		if (jQuery(obj).next('.populateField').length > 0) {
			var populateField = jQuery(obj).next('.populateField');
			populateField.find('optgroup').each(function(){
				str += '<option value="'+this.label+'">'+this.label+'</option>';
			});
			jQuery(obj).html(str).change(function(){
				jQuery(obj).hide();
				populateField.find('optgroup').hide();
				populateField.find('optgroup[label="'+this.value+'"]').show();
				jQuery(populateField).show();
				populateField.val(populateField.find("option:first").val());
			});
		}
	});
}

function restorePopulateFieldGroup(tagField) {
	jQuery(tagField).hide();
	var populateFieldGroup = jQuery(tagField).prev('.populateFieldGroup');
	populateFieldGroup.show();
	populateFieldGroup.val(populateFieldGroup.find("option:first").val());
}
//crmv@112299e

function NewTaskPopup($,context){
	function close(){
		$('#new_task_div',context).css('display', 'none');
	}
	function show(module){
		$('#new_task_div',context).css('display', 'block');
	}
	$('#new_task_div_close',context).click(close);
	$('#new_task_div_cancel',context).click(close);
	return {
		close:close,show:show
	};
}

function insertAtCursor(element, value){
	//http://alexking.org/blog/2003/06/02/inserting-at-the-cursor-using-javascript
	if (document.selection) {
		element.focus();
		var sel = document.selection.createRange();
		sel.text = value;
		element.focus();
	}else if (element.selectionStart || element.selectionStart == '0') {
		var startPos = element.selectionStart;
		var endPos = element.selectionEnd;
		var scrollTop = element.scrollTop;
		element.value = element.value.substring(0, startPos)
			+ value
			+ element.value.substring(endPos,
			element.value.length);
		element.focus();
		element.selectionStart = startPos + value.length;
		element.selectionEnd = startPos + value.length;
		element.scrollTop = scrollTop;
	}	else {
		element.value += value;
		element.focus();
	}
}

function getDescribeObjects(vtinst, accessibleModules, moduleName, processid, involved_record, callback){
	var me = this,
		processmaker_describe_modules;

	if (ActionTaskScript.__cache['processmaker_describe_modules_'+processid]) processmaker_describe_modules = ActionTaskScript.__cache['processmaker_describe_modules_'+processid]; else processmaker_describe_modules = {};

	processDescribeObject = function(moduleName,involved_record,result,callback){
		var parent = referencify(result);
		var fields = parent['fields'];

		var referenceFields = filter(function(e){return e['type']['name']=='reference';}, fields);
		var referenceFieldModules =
			map(function(e){ return e['type']['refersTo'];},
				referenceFields
			);

		function union(a, b){
			var newfields = filter(function(e){return !contains(a, e);}, b);
			return a.concat(newfields);
		}
		var relatedModules = reduceR(union, referenceFieldModules, []);	//skip duplicate call
		if (relatedModules.length == 0) relatedModules = [moduleName];	//crmv@113775 force module even if there aren't fields in order to prevent error
		if (!(moduleName in ActionTaskScript.__describe_object_cache)) ActionTaskScript.__describe_object_cache[moduleName] = result;
		
		// Remove modules that is no longer accessible
		relatedModules = diff(accessibleModules, relatedModules);
		
		// crmv@195745
		if (result.isInventory) {
			relatedModules.push('ProductsBlock');
		}
		// crmv@195745e
		
		function executer(parameters){
			var failures = filter(function(e){return e[0]==false;}, parameters);
			if(failures.length!=0){
				var firstFailure = failures[0];
				callback(false, firstFailure[1]);
			}else{
				var moduleDescriptions = map(function(e){return e[1];}, parameters);
				var modules = dict(map(function(e){return [e['name'], referencify(e)];}, moduleDescriptions));
				modules[moduleName] = ActionTaskScript.__describe_object_cache[moduleName];	//skip duplicate call
				jQuery.each(modules, function(k,v){
					if (!(k in ActionTaskScript.__describe_object_cache)) ActionTaskScript.__describe_object_cache[k] = v;
				});
				callback(true, [modules, moduleName, involved_record]);
			}
		}
		var p = parallelExecuter(executer, relatedModules.length);
		jQuery.each(relatedModules, function(i, v){
			if (!(v in ActionTaskScript.__describe_object_cache)) {
				p(function(c){
					if (!ActionTaskScript.enable_cache || typeof(processmaker_describe_modules[v]) == 'undefined') {
						// if cache is not set call the describeObject and cache the result
						//ActionTaskScript.__describeObjectCount++;
						vtinst.describeObject(v,handleError(function(relatedResult){
							//ActionTaskScript.__describeObjectCount--;
							processmaker_describe_modules[v] = relatedResult;
							if (ActionTaskScript.enable_cache) {
								// save result in the object ActionTaskScript.__cache and save it at the end
								ActionTaskScript.__overwriteCache = true;	// to overwrite the cache after all the describe
								if (!ActionTaskScript.__cache['processmaker_describe_modules_'+processid]) ActionTaskScript.__cache['processmaker_describe_modules_'+processid] = {};
								ActionTaskScript.__cache['processmaker_describe_modules_'+processid][v] = processmaker_describe_modules[v];
							}
							c(true,processmaker_describe_modules[v]);
						}));
					} else {
						c(true,processmaker_describe_modules[v]);
					}
				});
			} else {
				p(function(c){
					c(true,ActionTaskScript.__describe_object_cache[v]);
				});
			}
		});
	}
	
	if (!ActionTaskScript.enable_cache || typeof(processmaker_describe_modules[moduleName]) == 'undefined') {
		// if cache is not set call the describeObject and cache the result
		//ActionTaskScript.__describeObjectCount++;
		vtinst.describeObject(moduleName, handleError(function(result){
			//ActionTaskScript.__describeObjectCount--;
			processmaker_describe_modules[moduleName] = result;
			if (ActionTaskScript.enable_cache) {
				// save result in the object ActionTaskScript.__cache and save it at the end
				ActionTaskScript.__overwriteCache = true;	// to overwrite the cache after all the describe
				if (!ActionTaskScript.__cache['processmaker_describe_modules_'+processid]) ActionTaskScript.__cache['processmaker_describe_modules_'+processid] = {};
				ActionTaskScript.__cache['processmaker_describe_modules_'+processid][moduleName] = processmaker_describe_modules[moduleName];
			}
			processDescribeObject(moduleName,involved_record,result,callback);
		}));
	} else {
		processDescribeObject(moduleName,involved_record,processmaker_describe_modules[moduleName],callback);
	}
}

function checkSelectBoxDuplicates(field, label) {
	var optgroups = jQuery(field).find('optgroup');
	var check_duplicate = function(){
		var check = false;
		jQuery(field).find('optgroup').each(function(){
			if (this.label == label) {
				check = true;
				return true;
			}
		});
		return check;
	}();
	return check_duplicate;
}

function fillSelectBox(id, modules, parentModule, involved_record, filterPred){
	if(filterPred==null){
		filterPred = function(){
			return true;
		};
	}
	var select = jQuery('#'+id);
	if (select.length == 0) select = jQuery('[name="'+id+'"]');
	
	if (checkSelectBoxDuplicates(select, involved_record.label)) return true;
	
	var parent = modules[parentModule];
	var fields = parent['fields'];

	function filteredFields(fields){
		return filter(
			function(e){
				var fieldCheck = !contains(['autogenerated', 'owner', 'multipicklist', 'password'], e.type.name);	//reference
				var predCheck = filterPred(e);
				return fieldCheck && predCheck;
			},
			fields
		);
	}
	var parentFields = map(function(e){return[e['name'],e['label']];}, filteredFields(parent['fields']));

	var referenceFieldTypes = filter(function(e){
			return (e['type']['name']=='reference');
		},parent['fields']
	);

	var moduleFieldTypes = {};
	jQuery.each(modules, function(k, v){
			moduleFieldTypes[k] = dict(map(function(e){return [e['name'], e['type']];},filteredFields(v['fields'])));
		}
	);

	function getFieldType(fullFieldName){
		var group = fullFieldName.match(/(\w+) : \((\w+)\) (\w+)/);
		if(group==null){
			var fieldModule = parentModule;
			var fieldName = fullFieldName;
			}else{
				var fieldModule = group[2];
			var fieldName = group[3];
		}
		return moduleFieldTypes[fieldModule][fieldName];
	}

	function fieldReferenceNames(referenceField){
		var name = referenceField['name'];
		var label = referenceField['label'];
		function forModule(parentModule){
			// If module is not accessible return no field information
			if(!contains(accessibleModulesInfo, parentModule)) return [];
			if (typeof(modules[parentModule]) == 'undefined') return [];	//crmv@131239

			return map(function(field){
				return ['('+name+' : '+'('+parentModule+') '+field['name']+')',label+' : '+'('+modules[parentModule]['label']+') '+field['label']]; //crmv@42329
				},
				filteredFields(modules[parentModule]['fields']));
		}
		return reduceR(concat,map(forModule,referenceField['type']['refersTo']),[]);
	}
	//crmv@36510
	if (id == 'task-emailfields_sender'){
		var accessibleModulesInfo_backup = accessibleModulesInfo;
		accessibleModulesInfo = ['Users'];
	}
	var referenceFields = reduceR(concat,map(fieldReferenceNames,referenceFieldTypes), []);
	if (id == 'task-emailfields_sender'){
		accessibleModulesInfo = accessibleModulesInfo_backup;
	}
	//crmv@36510 e
	var referenceFields = reduceR(concat,map(fieldReferenceNames,referenceFieldTypes), []);
	var fieldLabels = dict(parentFields.concat(referenceFields));
	var optionClass = id+'_option';
	var append = '';
	append += '<optgroup label="'+involved_record.label+'">';
	if (typeof(involved_record.meta_processid) == 'undefined') var rk = involved_record.seq; else rk = involved_record.meta_processid+':'+involved_record.seq;
	if (id == 'task-fieldnames' || id == 'task-referencefieldnames' || id == 'task-subjectfields') {
		append += '<option class="'+optionClass+'" '+ 'value="$'+rk+'-crmid">ID</option>';
	}
	jQuery.each(fieldLabels, function(k, v){
		append += '<option class="'+optionClass+'" '+ 'value="$'+rk+'-'+k+'">' + v + '</option>';
	});
	append += '</optgroup>';
	select.append(append);
}

//crmv@146187
function appendDynaformOptions(field,options,type) {
	var string = '';
	var optgroup_exists = false;
	var optgroup = null;
	if (typeof(options[type]) == "object") {
		jQuery.each(options[type], function(grouplabel, fields){
			if (checkSelectBoxDuplicates(field, grouplabel)) {
				optgroup_exists = true;
				jQuery(field).find('optgroup').each(function(){
					if (this.label == grouplabel) optgroup = this;
				});
			}
			if (!optgroup_exists) string += '<optgroup label="'+grouplabel+'">';
			jQuery.each(fields, function(fieldvalue, fieldlabel){
				string += '<option value="'+fieldvalue+'">'+fieldlabel+'</value>';	// TODO check duplicates?
			});
			if (!optgroup_exists) string += '</optgroup>';
		});
	}
	if (string != '') {
		if (optgroup_exists)
			jQuery(optgroup).append(string);
		else
			jQuery(field).append(string);
	}
}
//crmv@146187e

// crmv@146671
function appendExtWSOptions(jfield,options) {
	var string = '';
	if (options && options.length > 0) {
		jQuery.each(options, function(index, group){
			if (group && group.fields && group.fields.length > 0) {
				string += '<optgroup label="'+group.label+'">';
				jQuery.each(group.fields, function(index2, field){
					string += '<option value="'+field.value+'">'+field.label+'</value>';
				});
				string += '</optgroup>';
			}
		});
	}
	if (string != '') jQuery(jfield).append(string);
	
}
// crmv@146671e

function id(v){
	return v;
}

function map(fn, list){
	var out = [];
	jQuery.each(list, function(i, v){
		out[out.length]=fn(v);
	});
	return out;
}

function field(name){
	return function(object){
		if(typeof(object) != 'undefined') {
			return object[name];
		}
	};
}

function zip(){
	var out = [];

	var lengths = map(field('length'), arguments);
	var min = reduceR(function(a,b){return a<b?a:b;},lengths,lengths[0]);
	for(var i=0; i<min; i++){
		out[i]=map(field(i), arguments);
	}
	return out;
}

function dict(list){
	var out = {};
	jQuery.each(list, function(i, v){
		out[v[0]] = v[1];
	});
	return out;
}

function filter(pred, list){
	var out = [];
	jQuery.each(list, function(i, v){
		if(pred(v)){
			out[out.length]=v;
		}
	});
	return out;
}

function diff(reflist, list) {
	var out = [];
	jQuery.each(list, function(i, v) {
		if(contains(reflist, v)) {
			out.push(v);
		}
	});
	return out;
}


function reduceR(fn, list, start){
	var acc = start;
	jQuery.each(list, function(i, v){
		acc = fn(acc, v);
	});
	return acc;
}

function contains(list, value){
	var ans = false;
	jQuery.each(list, function(i, v){
		if(v==value){
			ans = true;
			return false;
		}
	});
	return ans;
}

function concat(lista,listb){
	return lista.concat(listb);
}

function errorDialog(message){
	alert(message);
}

function handleError(fn){
	return function(status, result){
		if(status){
			fn(result);
		}else{
			errorDialog('Failure:'+result);
		}
	};
}

function implode(sep, arr){
	var out = "";
	jQuery.each(arr, function(i, v){
		out+=v;
		if(i<arr.length-1){
			out+=sep;
		}
	});
	return out;
}

function mergeObjects(obj1, obj2){
	var res = {};
	for(var k in obj1){
		res[k] = obj1[k];
	}
	for(var k in obj2){
		res[k] = obj2[k];
	}
	return res;
}

function referencify(desc){
  var fields = desc['fields'];
  for(var i=0; i<fields.length; i++){
	var field = fields[i];
	var type = field['type'];
	if(type['name']=='owner'){
	  type['name']='reference';
	  type['refersTo']=['Users'];
	}
  }
  return desc;
}