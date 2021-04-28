/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@92272 */

if (typeof(ProcessMakerScript) == 'undefined') {
	ProcessMakerScript = {
		
		conditional_fields: [], // crmv@142262
		
		// crmv@189903
		sessionCheck: function() {
			if(!SessionValidator.check()) {
				SessionValidator.showLogin();
				return false;
			}
			return true;
		},		
		// crmv@189903e
		formatType: function(type){
			return type.replace('bpmn:','');
		},
		openMetadata: function(processid,id,structure){
			// crmv@189903
			var me = this;
			if (!me.sessionCheck()) return false;
			// crmv@189903e
			openPopup('index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=load_metadata&id='+processid+'&elementid='+id+'&structure='+encodeURI(JSON.stringify(structure)));
		},
		reloadMetadata: function(processid,id){
			// crmv@189903
			var me = this;
			if (!me.sessionCheck()) return false;
			// crmv@189903e
			window.location.href = 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=load_metadata&id='+processid+'&elementid='+id;
		},
		backToList: function(fieldLabel){
			// crmv@189903
			var me = this;
			if (!me.sessionCheck()) return false;
			// crmv@189903e
			if (jQuery('[name="pm_active"]').val() == '1') {
				window.location.href = 'index.php?module=Settings&action=ProcessMaker';
			} else {
				vteconfirm(alert_arr.LBL_PM_CHECK_ACTIVE, function(yes) {
					if (yes) {
						jQuery('[name="pm_active"]').prop('checked',true);
						ProcessMakerScript.setActive(fieldLabel,'Settings',56,'','pm_active','1',function(){
							window.location.href = 'index.php?module=Settings&action=ProcessMaker';
						});
					} else {
						window.location.href = 'index.php?module=Settings&action=ProcessMaker';
					}
				}, {btn_cancel_label:alert_arr.NO, btn_ok_label:alert_arr.YES});
			}
		},
		saveMetadata: function(processid,id,engineType,callback) {
			// crmv@189903
			var me = this;
			if (!me.sessionCheck()) return false;
			// crmv@189903e
			
			var object = jQuery('.form-config-shape[shape-id="'+id+'"]');
			jQuery('#config_'+id+'_Handle .indicatorMetadata').show();
			
			var metadata = {};
			jQuery.each(jQuery(object).serializeArray(), function(){
				metadata[this.name] = this.value;
			});
			
			// Task
			if (jQuery('#save_conditions',object).length > 0) {
				var conditions = GroupConditions.getJson(jQuery, 'save_conditions', jQuery(object));
				metadata['conditions'] = conditions;
			}
			
			// Process Helper
			var helper = {};
			jQuery.each(jQuery('.form-helper-shape[shape-id="'+id+'"]').serializeArray(), function(){
				helper[this.name] = this.value;
			});
			helper['related_to'] = helper['other_related_to']; //crmv@160843
			if (helper['active'] == 'on' && helper['related_to'] == '') {
				alert(alert_arr.LBL_PMH_SELECT_RELATED_TO);
				jQuery.fancybox.hideLoading();
				return false;
			}
			//crmv@96450
			var mmaker = {};
			jQuery.each(jQuery('#module_maker_form').serializeArray(), function(){
				mmaker[this.name] = this.value;
			});
			//crmv@96450e
			
			saveFunction = function() {
				jQuery.ajax({
					'url': 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=savemetadata&id='+processid+'&elementid='+id,
					'type': 'POST',
					'data': jQuery.param({'vte_metadata':JSON.stringify(metadata),'helper':JSON.stringify(helper),'mmaker':JSON.stringify(mmaker)}),
					success: function(data) {
						jQuery('#config_'+id+'_Handle .indicatorMetadata').hide();
						if (typeof(callback) != 'undefined')
							callback();
						else
							ProcessMakerScript.closeMetadata(id);
					},
					error: function() {}
				});
			}
			validateFunction  = function() {
				if (engineType == 'Condition' && jQuery('#isStartTask',object).val() == '1') {
					if (typeof(jQuery('[name="execution_condition"]:checked',object).val()) == 'undefined') {
						alert(alert_arr.LBL_PM_NO_CHECK_SELECTED);
						return false;
					} else if (jQuery('[name="execution_condition"]:checked',object).val() != 'ON_SUBPROCESS' && jQuery('[name="moduleName"]',object).val() == '') {
						alert(alert_arr.LBL_PM_NO_ENTITY_SELECTED);
						return false;
					}
					saveFunction();
				} else if (engineType == 'Condition') {
					if (jQuery('[name="moduleName"]',object).val() == '') {
						alert(alert_arr.LBL_PM_NO_ENTITY_SELECTED);
						return false;
					}
					if (typeof(jQuery('[name="execution_condition"]:checked',object).val()) == 'undefined') {
						alert(alert_arr.LBL_PM_NO_CHECK_SELECTED);
						return false;
					}
					saveFunction();
				} else if (engineType == 'TimerStart') {
					jQuery.ajax({
						'url': 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=checktimerstart',
						'type': 'POST',
						'data': jQuery.param({'vte_metadata':JSON.stringify(metadata)}),
						'async': false,
						success: function(data) {
							if (data != '') {
								alert(data);
								return false;
							} else saveFunction();
						},
					});
				} else {
					saveFunction();
				}
			}
			validateFunction();
		},
		closeMetadata: function(id) {
			closePopup();
		},
		clearAssignedUserId: function(id) {
			jQuery('.form-helper-shape[shape-id="'+id+'"] #assign_user .dvtCellInfoImgRx img:nth-child(2)').click();
			jQuery('.form-helper-shape[shape-id="'+id+'"] #assign_user #assigned_user_id_display').blur();			
		},
		manageOtherRecords: function(processid) {
			openPopup('index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=manage_other_records&id='+processid);
		},
		previewRecurrence: function(id) {
			var object = jQuery('.form-config-shape[shape-id="'+id+'"]');
			//jQuery('#config_'+id+'_Handle .indicatorMetadata').show();
			
			var metadata = {};
			jQuery.each(jQuery(object).serializeArray(), function(){
				metadata[this.name] = this.value;
			});
			jQuery.ajax({
				'url': 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=recurrence_preview',
				'type': 'POST',
				'data': jQuery.param({'vte_metadata':JSON.stringify(metadata)}),
				success: function(data) {
					//jQuery('#config_'+id+'_Handle .indicatorMetadata').hide();
					//ProcessMakerScript.closeMetadata(id);
					jQuery('#preview').html(data);
				},
				error: function() {}
			})
		},
		setActive: function(fieldLabel,module,uitype,tableName,fieldName,crmId,callback) {
			// crmv@189903
			var me = this;
			if (!me.sessionCheck()) return false;
			// crmv@189903e
            dtlViewAjaxSaveActive = function() {
                if (jQuery('[name="pm_active"]').prop('checked')) var value = '1'; else var value = '0';
                jQuery.ajax({
                    'url': 'index.php?module=Settings&action=SettingsAjax&file=DetailViewAjax&ajxaction=DETAILVIEW&record='+jQuery('[name="id"]').val()+'&recordid='+jQuery('[name="id"]').val()+'&fldName=pm_active&fieldValue='+value,
                    'type': 'POST',
                    success: function(data) {
                        if (data == ':#:SUCCESS') {
                            dtlViewAjaxSave(fieldLabel,module,uitype,tableName,fieldName,crmId);
                            if (typeof(callback) != 'undefined') callback();
                        } else {
                            alert('Error during save');
                            return false;
                        }
                    }
                });
            }
            if(jQuery('[name="pm_active"]').prop('checked')) {
                jQuery.ajax({
                    url: 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=CheckActiveProcesses',
                    type: 'POST',
                    dataType: 'JSON',
                    success: function(data) {
                        if (data && data.success) {
                            jQuery.ajax({
                                'url': 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=checktimerstart&id='+jQuery('[name="id"]').val(),
                                'type': 'POST',
                                success: function(data) {
                                    if (data != '') {
                                        alert(data);
                                        return false;
                                    } else {
                                        dtlViewAjaxSaveActive();
                                    }
                                },
                            });
                        } else {
                            alert(data.message);
                        }
                    },
                });
            } else {
                dtlViewAjaxSaveActive();
            }
        },
		confirmdelete: function(url) {
			// crmv@189903
			var me = this;
			if (!me.sessionCheck()) return false;
			// crmv@189903e
			vteconfirm(alert_arr.ARE_YOU_SURE, function(yes) {
				if (yes) {
					location.href = url;
				}
			});
		},
		//crmv@163905
		download: function(format, processmakerid, current_version, force_increment_version) {
			var me = this,
				url = 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=download&format='+format+'&id='+processmakerid;
			
			if (!me.sessionCheck()) return false; // crmv@189903
			
			__download = function() {
				location.href = url;
			}
			
			if (!force_increment_version) {
				__download();
			} else {
				jQuery.ajax({
					'url': 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=check_pending_changes&id='+processmakerid,
					'type': 'POST',
					success: function(res) {
						if (res == '0') {
							__download();
						} else {
							me.incrementVersion(processmakerid, current_version, function(version){
								__download();
							}, alert_arr.ARE_YOU_SURE_INCREMENT_VERSION_FOR_DOWNLOAD);
						}
					}
				});
			}
		},
		//crmv@163905e
		upload: function(format, processmakerid) {
			// crmv@189903
			var me = this;
			if (!me.sessionCheck()) return false;
			// crmv@189903e
			jQuery('#import_processmakerid').val(processmakerid);
			jQuery('[name=bpmnfile]').click();
		},
		//crmv@99316
		advancedMetadataSettings: function(processmakerid,elementid,save) {
			// crmv@189903
			var me = this;
			if (!me.sessionCheck()) return false;
			// crmv@189903e
			if (typeof(save) == 'undefined') save = false;
			if (save) {
				ProcessMakerScript.saveMetadata(processmakerid,elementid,'Action',function(){
					window.location.href = 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=advanced_metadata&id='+processmakerid+'&elementid='+elementid;
				});
			} else {
				window.location.href = 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=advanced_metadata&id='+processmakerid+'&elementid='+elementid;
			}
		},
		closeAdvMetadata: function(processmakerid,elementid) {
			// crmv@189903
			var me = this;
			if (!me.sessionCheck()) return false;
			// crmv@189903e
			window.location.href = 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=load_metadata&id='+processmakerid+'&elementid='+elementid;
		},
		editDynaFormConditional: function(processmakerid,elementid,ruleid) {
			// crmv@189903
			var me = this;
			if (!me.sessionCheck()) return false;
			// crmv@189903e
			window.location.href = 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=edit_dynaform_conditional&id='+processmakerid+'&elementid='+elementid+'&ruleid='+ruleid;
		},
		deleteDynaFormConditional: function(processmakerid,elementid,ruleid) {
			// crmv@189903
			var me = this;
			if (!me.sessionCheck()) return false;
			// crmv@189903e
			jQuery.ajax({
				'url': 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=delete_dynaform_conditional&id='+processmakerid+'&elementid='+elementid+'&ruleid='+ruleid,
				'type': 'POST',
				success: function(data) {
					ProcessMakerScript.advancedMetadataSettings(processmakerid,elementid);
				}
			});
		},
		saveDynaFormConditional: function() {
			// crmv@189903
			var me = this;
			if (!me.sessionCheck()) return false;
			// crmv@189903e
			var object = jQuery('#DynaformConditionalForm');
			var data = {};
			jQuery.each(jQuery(object).serializeArray(), function(){
				data[this.name] = this.value;
			});
			if (jQuery('#save_conditions',object).length > 0) {
				data['conditions'] = GroupConditions.getJson(jQuery, 'save_conditions', jQuery(object));
			}
			jQuery.ajax({
				'url': 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=save_dynaform_conditional',
				'type': 'POST',
				'data': data,
				success: function(data) {
					ProcessMakerScript.advancedMetadataSettings(jQuery('#processmakerid').val(),jQuery('#elementid').val());
				},
				error: function() {}
			});
		},
		closeDynaFormConditional: function(processmakerid,elementid,ruleid) {
			ProcessMakerScript.advancedMetadataSettings(processmakerid,elementid);
		},
		//crmv@99316e
		//crmv@112297
		// crmv@198388
		alertDisableAjaxSave: function(uitype, label, name, el) {
			VteJS_DialogBox.progress();
			document.forms['DetailView'].focus_on_field.value = name;
			submitFormForAction('DetailView','EditView');
		},
		// crmv@198388e
		// crmv@142262
		// check if this field can be edited in detailview
		checkAjaxSave: function(uitype, label, name, el) {
			var me = this,
				arguments = [uitype, label, name, el]; // crmv@198388
		
			if (me.conditional_fields && me.conditional_fields.indexOf(name) >= 0) {
				// field used in condtional conditions, read only!
				me.alertDisableAjaxSave.apply(me, arguments);
			} else {
				// defautl ajax handler
				hndMouseClick.apply(window, arguments);
			}
		},
		// crmv@142262e
		
		editConditional: function(processmakerid,elementid,ruleid) {
			// crmv@189903
			var me = this;
			if (!me.sessionCheck()) return false;
			// crmv@189903e
			window.location.href = 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=edit_conditional&id='+processmakerid+'&elementid='+elementid+'&ruleid='+ruleid;
		},
		deleteConditional: function(processmakerid,elementid,ruleid) {
			// crmv@189903
			var me = this;
			if (!me.sessionCheck()) return false;
			// crmv@189903e
			jQuery.ajax({
				'url': 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=delete_conditional&id='+processmakerid+'&elementid='+elementid+'&ruleid='+ruleid,
				'type': 'POST',
				success: function(data) {
					ProcessMakerScript.advancedMetadataSettings(processmakerid,elementid);
				}
			});
		},
		saveConditional: function() {
			// crmv@189903
			var me = this;
			if (!me.sessionCheck()) return false;
			// crmv@189903e
			var object = jQuery('#ConditionalForm');
			var data = {};
			jQuery.each(jQuery(object).serializeArray(), function(){
				data[this.name] = this.value;
			});
			if (jQuery('#save_conditions',object).length > 0) {
				data['conditions'] = GroupConditions.getJson(jQuery, 'save_conditions', jQuery(object));
			}
			jQuery.ajax({
				'url': 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=save_conditional',
				'type': 'POST',
				'data': data,
				success: function(data) {
					ProcessMakerScript.advancedMetadataSettings(jQuery('#processmakerid').val(),jQuery('#elementid').val());
				},
				error: function() {}
			});
		},
		closeConditional: function(processmakerid,elementid,ruleid) {
			ProcessMakerScript.advancedMetadataSettings(processmakerid,elementid);
		},
		load_field_permissions_table: function(id){
			if (id.indexOf(':') > -1) {
				var tmp = id.split(':');
				var module = tmp[1];
			} else {
				var module = id;
			}
			jQuery('#field_permissions_table').hide();
			jQuery.ajax({
				'url': 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=load_field_permissions_table&chk_module='+escape(module),
				'type': 'POST',
				success: function(data) {
					jQuery('#field_permissions_table').html(data);
					jQuery('#field_permissions_table').show();
				},
				error: function() {}
			});
		},
		populateField: function(obj,field) {
			var value = jQuery(obj).val();
			if (value != '') insertAtCursor(jQuery('[id="'+field+'"]').get(0), value);
		},
		//crmv@112297e
		//crmv@100731
		addAdvancedPermission: function(processmakerid,elementid) {
			// crmv@189903
			var me = this;
			if (!me.sessionCheck()) return false;
			// crmv@189903e
			var record_involved = jQuery('#record_involved').val();
			var resource_type = jQuery('#assigned_user_id_type').val();
			if (resource_type == 'U') {
				var resource = jQuery('#assigned_user_id').val();
			} else if (resource_type == 'T') {
				var resource = jQuery('#assigned_group_id').val();
			} else if (resource_type == 'O') {
				var resource = jQuery('#other_assigned_user_id').val();
			}
			if (record_involved == '') {
				alert(alert_arr.LBL_PM_SELECT_ENTITY);
				return false;
			}
			if (resource == '') {
				alert(alert_arr.LBL_PM_SELECT_RESOURCE);
				return false;
			}
			jQuery.ajax({
				'url': 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=add_advanced_permission',
				'type': 'POST',
				'data': {'processmakerid':processmakerid,'elementid':elementid,'record_involved':record_involved,'resource_type':resource_type,'resource':resource,'permission':jQuery('#permission').val()},
				success: function(data) {
					ProcessMakerScript.advancedMetadataSettings(processmakerid,elementid);
				},
				error: function() {}
			});
		},
		deleteAdvancedPermission: function(processmakerid,elementid,ruleid) {
			// crmv@189903
			var me = this;
			if (!me.sessionCheck()) return false;
			// crmv@189903e
			jQuery.ajax({
				'url': 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=delete_advanced_permission&id='+processmakerid+'&elementid='+elementid+'&ruleid='+ruleid,
				'type': 'POST',
				success: function(data) {
					ProcessMakerScript.advancedMetadataSettings(processmakerid,elementid);
				}
			});
		},
		//crmv@100731e
		//crmv@100972
		modeler: function(processmakerid) {
			// crmv@189903
			var me = this;
			if (!me.sessionCheck()) return false;
			// crmv@189903e
			window.location.href = 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=modeler&id='+processmakerid;
		},
		saveModel: function(processmakerid, xml, values) {
			jQuery.ajax({
				'url': 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=save_model',
				'type': 'POST',
				'data': {'id':processmakerid,'xml':xml,'values':JSON.stringify(values)},
				success: function(processmakerid) {
					ProcessMakerScript.detailProcessMaker(processmakerid);
				},
				error: function() {}
			});
		},
		detailProcessMaker: function(processmakerid) {
			// crmv@189903
			var me = this;
			if (!me.sessionCheck()) return false;
			// crmv@189903e
			window.location.href = 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&parenttab=Settings&mode=detail&id='+processmakerid;
		},
		//crmv@100972e
		//crmv@147720 crmv@150751 crmv@163905
		incrementVersion: function(processmakerid, current_version, callback, confirm_label) {
			var me = this;
			if (typeof(confirm_label) == 'undefined') confirm_label = alert_arr.ARE_YOU_SURE_INCREMENT_VERSION;
			
			if (!me.sessionCheck()) return false; // crmv@189903
			
			vteconfirm(confirm_label, function(yes) {
				if (yes) {
					me.checkIncrementVersion(processmakerid, current_version, function(force_version){
						jQuery('#status').show(); //crmv@167915
						jQuery.ajax({
							'url': 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=increment_version',
							'type': 'POST',
							'data': {'id':processmakerid,'force_version':force_version},
							success: function(version) {
								jQuery('#status').hide(); //crmv@167915
								if (typeof(callback) != 'undefined') callback(version);
							},
							error: function() {}
						});
					});
				}
			}, {btn_cancel_label:alert_arr.LBL_CANCEL, btn_ok_label:'OK'});
		},
		checkIncrementVersion: function(processmakerid, current_version, callback) {
			jQuery('#status').show(); //crmv@167915
			jQuery.ajax({
				'url': 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=check_increment_version',
				'type': 'POST',
				'data': {'id':processmakerid},
				success: function(check) {
					jQuery('#status').hide(); //crmv@167915
					if (check == '0') {
						callback(false);
					} else {
						if (check == '1') {
							var msg = alert_arr.LBL_INCREMENT_VERSION_ERR_1.replace('%1',current_version);
						} else {
							var msg = alert_arr.LBL_INCREMENT_VERSION_ERR_2.replace('%1',current_version).replace('%2','<br>- '+JSON.parse(check).join('<br>- ')+'<br>');
						}
						setTimeout(function(){
							vteconfirm(msg, function(yes, btn_msg) { //crmv@180014
								if (yes) {
									callback(false);										
								} else if (!yes && btn_msg == 'cancel') { //crmv@180014
									callback(true);
								}
							}, {html:true, btn_cancel_label:alert_arr.LBL_OLD_VERSION, btn_ok_label:alert_arr.LBL_NEW_VERSION, btn_exit:true, width:'500px'});
						},200);
					}
				},
				error: function() {}
			});
		},
		//crmv@147720e crmv@150751e crmv@163905e
		//crmv@182148
		changeTimerTriggerDataType: function(obj) {
			jQuery('.trigger_date_values').hide();
			if (obj.value != '') jQuery('#trigger_date_'+obj.value).show();
		}
		//crmv@182148e
	}
}