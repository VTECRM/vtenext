/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

//crmv@29190 crmv@69568
function set_return(product_id, product_name) {
	var formName = getReturnFormName();
	var form = (formName ? getReturnForm(formName) : null);
	if (form) {
		form.parent_name.value = product_name;
		form.parent_id.value = product_id;
		disableReferenceField(form.parent_name,form.parent_id,form.parent_id_mass_edit_check);
	}
}
//crmv@29190e crmv@69568e

//crmv@187568
if (typeof(ProcessGraph) == 'undefined') {
	ProcessGraph = {
		getFillColor: function(element, defaultColor) {
			if (['bpmn:ScriptTask','bpmn:UserTask','bpmn:ReceiveTask','bpmn:SendTask','bpmn:ManualTask','bpmn:BusinessRuleTask','bpmn:ServiceTask','bpmn:CallActivity'].indexOf(element.type) > -1) {
				defaultColor = '#80cbc4';
			} else if (element.type == 'bpmn:Task') {
				defaultColor = '#90caf9';
			} else if (element.type == 'bpmn:SubProcess') {
				defaultColor = '#b2dfdb';
			}
			return defaultColor;
		},
		getStrokeColor: function(element, defaultColor) {
			if (['bpmn:Task','bpmn:SubProcess','bpmn:ScriptTask','bpmn:UserTask','bpmn:ReceiveTask','bpmn:SendTask','bpmn:ManualTask','bpmn:BusinessRuleTask','bpmn:ServiceTask','bpmn:CallActivity'].indexOf(element.type) > -1) {
				defaultColor = '#eeeeee';
			}
			return defaultColor;
		},
	}
}
// crmv@187568e

if (typeof(ProcessScript) == 'undefined') {
	ProcessScript = {
		//crmv@100495
		runManuelly: function(module,record) {
			jQuery('#status').show();
			jQuery.ajax({
				'url': 'index.php?module=Processes&action=ProcessesAjax&file=DetailViewAjax&ajxaction=RUNPROCESSMANUALLY&pmodule='+module+'&record='+record,
				'type': 'POST',
				success: function(data) {
					jQuery('#status').hide();
					releaseOverAll('detailViewActionsContainer');
					if (data == 'LBL_NO_RUN_PROCESSES' || data == 'LBL_RUN_PROCESSES_OK') alert(alert_arr[data]); else alert(alert_arr.LBL_RUN_PROCESSES_ERROR);
				}
			});
		},
		//crmv@100495e
		//crmv@101506	crmv@105937
		hideTab: function(module,record) {
			jQuery('#ProcessGraph').hide();
		},
		showTab: function(module,record) {
			var me = this;
			jQuery('#ProcessGraph').show(); //crmv@176621
			jQuery('#turboLiftContainer').hide();
			if (jQuery('#ProcessGraph #canvas').html() == '') {
				if (module == 'Processes') me.showGraph(record);
				else me.showGraph(jQuery('#selectProcessGraph').val());
			}
		},
		showGraph: function(record) {
			jQuery('#status').show();
			jQuery('#canvas').html('');
			jQuery.ajax({
				'url': 'index.php?module=Processes&action=ProcessesAjax&file=DetailViewAjax&ajxaction=SHOWGRAPH&record='+record,
				'type': 'POST',
				'dataType': 'json',
				success: function(data) {
					bpmnLoad(window.BpmnJS, data, function(){
						jQuery('#status').hide();
					});
				}
			});
		},
		//crmv@101506e	crmv@105937e
		//crmv@112539
		deleteRecord: function(processesid, elementid, module, record, obj) {
			var me = this;
			vteconfirm(alert_arr.ARE_YOU_SURE, function(yes) {
				if (yes) {
					jQuery('#status').show();
					jQuery.ajax({
						'url': 'index.php?module=Processes&action=ProcessesAjax&file=DetailViewAjax&ajxaction=DELETERECORD&processesid='+processesid+'&elementid='+elementid+'&record_module='+module+'&record='+record,
						'type': 'POST',
						success: function(data) {
							jQuery(obj).closest('tr').remove();
							jQuery('#status').hide();
							me.showGraph(processesid);
						}
					});
				}
			});
		},
		mode: 'view',	// view, rollback
		changeMode: function(new_mode) {
			var me = this;
			me.mode = new_mode;
		},
		changeRollbackMode: function(rollback) {
			var me = this;
			if (rollback) {
				me.changeMode('rollback');
				jQuery('#rollback_btn_1').hide();
				jQuery('#rollback_btn_2').show();
				jQuery('#rollback_btn_3').hide();
			} else {
				me.changeMode('view');
				jQuery('#rollback_btn_1').show();
				jQuery('#rollback_btn_2').hide();
				if (jQuery('#running_process_active').val() == '0') jQuery('#rollback_btn_3').show();
			}
		},
		changePosition: function (processesid,elementid) {
			var me = this;
			vteconfirm(alert_arr.ARE_YOU_SURE, function(yes) {
				if (yes) {
					jQuery('#status').show();
					jQuery.ajax({
						'url': 'index.php?module=Processes&action=ProcessesAjax&file=DetailViewAjax&ajxaction=CHANGEPOSITION&record='+processesid+'&elementid='+elementid,
						'type': 'POST',
						success: function(data) {
							me.changeRollbackMode(false);
							if (data == 'SUCCESS') {
								// show button for continue execution
								jQuery('#running_process_active').val(0);
								jQuery('#rollback_btn_3').show();
							} else if (data == 'FAILED') {
								alert('Failed');
							} else {
								alert('Some errors');
							}
							jQuery('#status').hide();
							me.showGraph(processesid);
						}
					});
				}
			});
		},
		continueExecution: function (processesid) {
			var me = this;
			vteconfirm(alert_arr.ARE_YOU_SURE, function(yes) {
				if (yes) {
					jQuery('#status').show();
					jQuery.ajax({
						'url': 'index.php?module=Processes&action=ProcessesAjax&file=DetailViewAjax&ajxaction=CONTINUEEXECUTION&record='+processesid,
						'type': 'POST',
						success: function(data) {
							if (data == 'SUCCESS') {
								// hide button for continue execution
								jQuery('#running_process_active').val(1);
								jQuery('#rollback_btn_3').hide();
							} else if (data == 'FAILED') {
								alert('Failed');
							} else {
								alert('Some errors');
							}
							jQuery('#status').hide();
							me.showGraph(processesid);
						}
					});
				}
			});
		},
		//crmv@112539e
		//crmv@112297 crmv@115268
		fields_bind: [],
		fieldFocused: false,
		fieldNameFocused: false,
		condition_fields: [], // crmv@134058
		
		initEditViewConditionals: function(condition_fields) { // crmv@134058
			var me = this;
			
			// crmv@134058 crmv@137451
			me.condition_fields = condition_fields || [];
			/* crmv@153321
			// Check for linked picklist fields and exclude them
			var linkedPicklist = [];
			for (var i = 0; i < fieldname.length; i++) {
				if (fielduitype[i] == 300) {
					linkedPicklist.push(fieldname[i]);
				}
			}
			// Check for other fields ........

			me.condition_fields = me.condition_fields.filter(function(fld) {
				return linkedPicklist.indexOf(fld);
			});
			*/
			jQuery.each(window.fieldname || [], function(i,name){ // crmv@165598
				if (me.condition_fields && me.condition_fields.indexOf(name) < 0) return;
				// crmv@134058e crmv@137451e
				var field = {'fieldname':name,'label':fieldlabel[i],'datatype':fielddatatype[i],'uitype':fielduitype[i],'type':fieldwstype[i]};
				if (me.fields_bind.indexOf(name) == -1) {
					me.initFieldEditViewConditionals(field, '=');
					me.fields_bind.push(name);
					// track new field on focus
					jQuery('[name="'+name+'"]').focus(function(){
						me.fieldFocused = true;
						me.fieldNameFocused = name;
					});
				}
			});
		},
		
		initFieldEditViewConditionals: function(field, selector) {
			var me = this,
				field_on_event = field['fieldname'],
				event = 'change';

			if (field['uitype'] == 10 || field['uitype'] == 53) {
				field_on_event = field['fieldname']+'_display';
				event = 'blur';
			/*
			} else if (field['type'] == 'picklist' || field['type'] == 'date' || field['type'] == 'reference' || field['type'] == 'boolean' || field['type'] == 'file') {
				event = 'change';
			*/
			}
			var obj = jQuery('[name'+selector+'"'+field_on_event+'"]');
			
			var reload_form = false;
			if (event == 'blur') {
				// unbind ???
				if (field['uitype'] == 10 || field['uitype'] == 53) {
					jQuery(obj).data("previous-value", jQuery(obj).val());
					jQuery(obj)
						.blur(function(e) {
							var old_v = jQuery(this).data("previous-value");
							var new_v = jQuery(this).val();
							if (old_v == 'Cerca...') old_v = '';
							if (new_v == 'Cerca...') new_v = '';
							if (old_v != new_v) {
								//console.log('changed', old_v, new_v);
								me.reloadForm();
							}
							jQuery(this).data("previous-value", new_v);
						});
					if (field['uitype'] == 53) {
						var obj1 = jQuery('[name'+selector+'"assigned_group_id_display"]');
						jQuery(obj1)
							.blur(function(e) {
								var old_v = jQuery(obj).data("previous-value");
								var new_v = jQuery(this).val();
								if (old_v == 'Cerca...') old_v = '';
								if (new_v == 'Cerca...') new_v = '';
								if (old_v != new_v) {
									//console.log('changed', old_v, new_v);
									me.reloadForm();
								}
								jQuery(obj).data("previous-value", new_v);
							});
					}
				} else {
					jQuery(obj)
						.focus(function(){
							jQuery(this).data("previous-value", jQuery(this).val());
						})
						.blur(function() {
							var old_v = jQuery(this).data("previous-value");
							var new_v = jQuery(this).val();
							if (old_v != new_v) {
								//console.log('changed', old_v, new_v);
								me.reloadForm();
							}
						});
				}
			} else {
				//jQuery(obj).unbind(event);
				jQuery(obj)
					.on(event, function(){
						//console.log('changed', jQuery(this), jQuery(this).val());
						me.reloadForm();
					});
			}
		},
		disableKeyboard: function(objEvent){
			objEvent.preventDefault();
		},
		reloadingForm: false,
		reloadForm: function() {
			var me = this;
			
			// prevent multiple calls
			if (me.reloadingForm == true) return false;
			me.reloadingForm = true;
			
			var fieldFocused = false;
			if (me.fieldFocused) fieldFocused = me.fieldFocused;
			jQuery(document).on('keydown', ProcessScript.disableKeyboard);

			jQuery('#status').show();
			VteJS_DialogBox.block();
		
			var form = {};
			jQuery.each(jQuery('form[name="EditView"]').serializeArray(), function(){
				//crmv@159424
				if (this.name.indexOf('[]') != -1){
					this.name = this.name.replace('[]','');
					if(typeof(form[this.name]) == 'undefined') form[this.name] = [];
					form[this.name].push(this.value);
				} else {
					form[this.name] = this.value;
				}
				//crmv@159424e
			});
			// crmv@141423
			// pass ckeditor fields
			for (var i=0; i<fielduitype.length; ++i) {
				var uitype = fielduitype[i];
				var fname = fieldname[i];
				if (uitype == 210 && window.CKEDITOR) {
					var textObj = CKEDITOR.instances[fname];
					if (textObj) {
						form[fname] = textObj.getData();
					}
				// crmv@205121 crmv@205097
				} else if (uitype == 56) {
					form[fname] = (form[fname] == 'on') ? 1 : 0;
				}
				// crmv@205121e crmv@205097e
			}
			// crmv@141423e
			
			var module = form['module'];
			
			var formData = new FormData();
			jQuery.each(jQuery('form[name="EditView"]').find("input[type='file']"), function(i, tag) {
		        jQuery.each(jQuery(tag)[0].files, function(i, file) {
					formData.append(tag.name, file);
		        });
		        jQuery.each(jQuery('form[name="EditView"]').serializeArray(), function(i, val){
					formData.append(val.name, val.value);
				});
		    });
			formData.append('module', module);
			formData.append('action', module+'Ajax');
			formData.append('file', 'EditViewConditionals');
			formData.append('ajax', 'true');
			formData.append('form', JSON.stringify(form));
			
			jQuery.ajax({
				url: 'index.php',
				type: 'POST',
				dataType: 'json',
				data: formData,
				cache: false,
		        contentType: false,
		        processData: false,
				success: function(data) {
					fieldname = data['VALIDATION_DATA_FIELDNAME'];
					fielddatatype = data['VALIDATION_DATA_FIELDDATATYPE'];
					fieldlabel = data['VALIDATION_DATA_FIELDLABEL'];
					fielduitype = data['VALIDATION_DATA_FIELDUITYPE'];
					fieldwstype = data['VALIDATION_DATA_FIELDWSTYPE'];
					condition_fields = data['CONDITIONAL_FIELDS'];	//crmv@134058

					if (jQuery(data['BLOCKS']).length > 0) {
						jQuery.each(data['BLOCKS'], function(blockid,html){
							jQuery('.blockrow_'+blockid).show();
							if (jQuery('#displayfields_'+blockid).length > 0) jQuery('#displayfields_'+blockid).html(html);
						});
					}
					if (jQuery(data['BLOCKVISIBILITY']).length > 0) {
						jQuery.each(data['BLOCKVISIBILITY'], function(blockid,value){
							if (value == 0) jQuery('.blockrow_'+blockid).hide();
						});
					}

					ProcessScript.fields_bind = [];
					ProcessScript.initEditViewConditionals(condition_fields); //crmv@134058
					jQuery('#status').hide();
					VteJS_DialogBox.unblock();
					ProcessScript.reloadingForm = false;
					
					if (fieldFocused && me.fieldNameFocused != false) {
						me.fieldFocused = false;
						jQuery('[name="'+me.fieldNameFocused+'"]').focus();
					}
					jQuery(document).off('keydown', ProcessScript.disableKeyboard);
				}
			});
		}
		//crmv@112297e crmv@115268e
	}
}

//crmv@188364
if (typeof(ProcessHistoryScript) == 'undefined') {
	ProcessHistoryScript = {
		hideTab: function(module,record) {
			jQuery('#ProcessHistory').hide();
		},
		showTab: function(module,record) {
			var me = this;
			jQuery('#ProcessHistory').show(); //crmv@176621
			jQuery('#turboLiftContainer').hide();
			if (jQuery('#ProcessHistory #processChangeLog').html() == '') {
				if (module == 'Processes') me.showGraph(record);
				else me.showGraph(jQuery('#selectProcessHistory').val());
			}
		},
		showGraph: function(record) {
			jQuery('#status').show();
			jQuery('#ProcessHistory #processChangeLog').html('');
			jQuery.ajax({
				'url': 'index.php?module=Processes&action=ProcessesAjax&file=DetailViewAjax&ajxaction=SHOWHISTORY&record='+record,
				'type': 'POST',
				success: function(data) {
					jQuery('#ProcessHistory #processChangeLog').html(data)
					jQuery('#status').hide();
				}
			});
		},
	}
}
// crmv@188364e