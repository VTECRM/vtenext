/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@OPER6317 crmv@96233 */

if (typeof(Wizard) == 'undefined') {
	
	Wizard = {
		
		/**
		 * The current step
		 */
		currStep : 1,
		
		stepInitialized: {},
		
		/**
		 * This object organizes the linked records
		 */
		records: {
			
			list: [],
			
			// these are for quick access
			byStep: {},
			byModule: {},
			byField: {},
			
			/**
			 * 
			 */
			add: function(step, module, crmid) {
				var me = this,
					wiz = Wizard,
					cfg = wiz.getStepConfig(step);
					
				crmid = parseInt(crmid);
				
				if (me.exists(module, crmid)) return;

				me.list.push({
					step: step,
					module: module,
					crmid: crmid,
					field: cfg.field
				});
				
				if (!me.byStep[step]) me.byStep[step] = [];
				me.byStep[step].push(crmid);
				
				if (!me.byModule[module]) me.byModule[module] = [];
				me.byModule[module].push(crmid);
				
				if (cfg.field) {
					if (!me.byField[cfg.field]) me.byField[cfg.field] = [];
					me.byField[cfg.field].push(crmid);
				}
		
			},
			
			exists: function(module, crmid) {
				var me = this;
				return (me.byModule[module] && me.byModule[module].indexOf(crmid) >= 0);
			},
			
			countForStep: function(step) {
				var me = this;
				
				return parseInt(me.byStep[step] && me.byStep[step].length) || 0;
			},
			
			remove: function(crmid) {
				var me = this;
				
				crmid = parseInt(crmid);
				
				var step = null;
				var module = null;
				var field = null;
				
				for (var i=0; i<me.list.length; ++i) {
					if (me.list[i].crmid == crmid) {
						step = me.list[i].step;
						module = me.list[i].module;
						field = me.list[i].field;
						me.list.splice(i, 1);
						break;
					}
				}
				
				if (step && me.byStep[step]) {
					var idx = me.byStep[step].indexOf(crmid);
					if (idx >= 0) me.byStep[step].splice(idx, 1);
				}
				if (module && me.byModule[module]) {
					var idx = me.byModule[module].indexOf(crmid);
					if (idx >= 0) me.byModule[module].splice(idx, 1);
				}
				if (field && me.byField[field]) {
					var idx = me.byField[field].indexOf(crmid);
					if (idx >= 0) me.byField[field].splice(idx, 1);
				}
			},
			
			removeAllForStep: function(step) {
				var me = this;
			},
			
			removeAllForModule: function(module) {
				var me = this;
			},
			
			removeAllForField: function(field) {
				var me = this;
			},
			
			removeForFields: function() {
				var me = this;
				var crmids = [];
				
				jQuery.each(me.list, function(idx, obj) {
					if (obj.field) crmids.push(obj.crmid);
				});
				
				jQuery.each(crmids, function(idx, crmid) {
					me.remove(crmid);
				});
			},
			
			removeAll: function() {
				var me = this;
				me.list = {};
				me.byStep = me.byModule = me.byField = {};
			},
			
			getAll: function() {
				var me = this;
				return me.list;
			},
			
			getForFields: function() {
				var me = this,
					ret = [];
					
				jQuery.each(me.list, function(idx, obj) {
					if (obj.field) ret.push(obj);
				});
				return ret;				
			}
			
		},
		
		
		openWizard : function(module, wizardid, parentModule, parentId, extraParams) {
			var url = "index.php?module="+module+"&action="+module+"Ajax&file=Wizard&wizardid="+wizardid;
			if (parentModule) url += '&parentModule='+encodeURIComponent(parentModule);
			if (parentId) url += '&parentId='+encodeURIComponent(parentId);
			if (extraParams) {
				if (typeof extraParams == 'string') {
					// single param
					url += "&params="+encodeURIComponent(extraParams);
				} else if (typeof extraParams == 'object') {
					url += '&' + jQuery.param(extraParams);
				}
			}
			openPopup(url,"Wizard","width=750,height=602,menubar=no,toolbar=no,location=no,status=no,resizable=no,scrollbars=yes");
		},
		
		getCurrentStep : function() {
			var me = this;
			
			var step = parseInt(jQuery('#nlWizRightPane div[id^=nlWizStep]:visible').attr('id').replace('nlWizStep', ''));
			me.currStep = step;
			
			return step;
		},
		
		getStepConfig: function(step) {
			// in the js they are 0 based
			return WizardInfo.steps[step-1];
		},
		
		gotoStep : function (step) {
			var me = this,
				currStep = me.getCurrentStep(),
				cells = jQuery('#nlWizStepTable .nlWizStepCell'),
				totalSteps = cells.length;
		
			step = parseInt(step);
			if (step <= 0 || step > totalSteps) return false;
		
			if (step != currStep) {
				var valid = (step < currStep || me.validateStep(currStep));
				if (valid) {
					this.currStep = step;
					jQuery('#nlWizStep'+currStep).hide();
					jQuery('#nlWizStep'+step).show();			
					jQuery(cells[currStep-1]).removeClass('nlWizStepCellSelected');
					
					var currCircleIndicator = jQuery(cells[currStep-1]).find('.circleIndicator');
					currCircleIndicator.removeClass('circleEnabled');
					
					jQuery(cells[step-1]).addClass('nlWizStepCellSelected');
					
					var prevCircleIndicator = jQuery(cells[step-1]).find('.circleIndicator');
					prevCircleIndicator.addClass('circleEnabled');
					jQuery('#nlw_nextButton')[step == totalSteps ? 'hide' : 'show']();
					jQuery('#nlw_endButton')[step == totalSteps ? 'show' : 'hide']();
					jQuery('#nlw_backButton')[step == 1 ? 'hide' : 'show']();
					return me.initializeStep(step);
				}
			}
			return false;
		},	
		
		gotoPrevStep : function() {
			var me = this;
			me.gotoStep(me.currStep - 1);
		},
		
		gotoNextStep : function() {
			var me = this;
			me.gotoStep(me.currStep + 1);
		},
		
		validateStep : function(step) {
			var me = this;
			
			step = parseInt(step);
			
			var cfg = me.getStepConfig(step);
			
			if (cfg.type == 'select') {
				return me.validateSelectStep(step);
			} else if (cfg.type == 'create') {
				return me.validateCreateStep(step);
			} else {
				// unknown case, nothing to be validated
			}
			
			return true;
		},
		
 		validateSelectStep: function(step) {
			var me = this,
				cfg = me.getStepConfig(step),
				selCount = me.records.countForStep(step);
				
			// check if there is 0 or 1 entries
			if (cfg.mode == 'exclusive') {
				if (selCount > 1) {
					// shoulnd't arrive here anyway
					return false;
				}
			}
			
			// check only if mandatory
			if (cfg.mandatory === true) {
				if (selCount == 0) {
					alert(alert_arr.SELECT);
					return false;
				}
			}
			return true;
		},
		
		validateCreateStep: function(step) {
			var me = this,
				cfg = me.getStepConfig(step);

			// populate some global vars
			if (!window.gVTModule) window.gVTModule = cfg.module;
			
			if (!window.fieldname) {
				window.fieldname = [];
				window.fieldlabel = [];
				window.fielddatatype = [];
				window.fielduitype = [];
			
				jQuery.each(cfg.fields, function(idx, fld) {
					fieldname.push(fld.name);
					fieldlabel.push(fld.label);
					fielddatatype.push(fld.fielddatatype);
					fielduitype.push(parseInt(fld.uitype));
				});
			}
			
			return formValidate(document.forms['EditView']);
		},
		
		initializeStep : function (step) {
			var me = this;
			
			step = parseInt(step);
			if (me.stepInitialized[step]) return true;

			var cfg = me.getStepConfig(step),
				parentModule = jQuery('#wizard_parent_module').val(),
				parentId = jQuery('#wizard_parent_id').val();
			
			// pre-select the record with the parent id if passed
			if (cfg && cfg.parent && cfg.modules) {
				if (parentModule && parentId && cfg.modules.indexOf(parentModule) >= 0) {
					if (cfg.modules.length > 1) {
						// select the correct list
						jQuery('#radioSelect'+step+'_'+parentModule).prop('checked', true);
						me.changeSelectList(step, parentModule);
					}
					// find the listid
					var listid = me.findListId(step, parentModule);
					// select the record
					if (listid) {
						me.recordSelect1(listid, parentModule, parentId, '');
					}
				}
			}
			
			// crmv@165671
			var currStep = me.getCurrentStep(),
				cells = jQuery('#nlWizStepTable .nlWizStepCell'),
				totalSteps = cells.length;
			if (step == 1 && step == totalSteps) {
				jQuery('#nlw_nextButton').hide();
				jQuery('#nlw_endButton').show();
				jQuery('#nlw_backButton').hide();
			}
			// crmv@165671e
			
			me.stepInitialized[step] = true;
			
			return true;
		},
		
		findListId: function(step, module) {
			var listid = null;

			jQuery('.nlWizTargetList').each(function(idx, el) {
				if (el.id == 'nlw_targetList'+step+'_'+module) {
					var cont = jQuery(el).find('div[id^=SLVContainer_]');
					if (cont.length > 0) {
						listid = cont.attr('id').replace('SLVContainer_', '');
						return false;
					}
				}
			});
			
			return listid;
		},
		
		/**
		 * Select a single record and add it to the bottom panel
		 */
		recordSelect1 : function(listid, module, crmid, entityname) {
			var me = this,
				currStep = me.currStep;
			
			jQuery('#selectList'+currStep).children('div').each(function(){
				me.recordRemove(listid,this.id);
			});
			me.recordSelect(listid, module, crmid, entityname);
		},
		
		/**
		 * Select a record and add it to the bottom panel
		 */
		recordSelect : function(listid, module, crmid, entityname) {
			var me = this,
				currStep = me.currStep,
				box = jQuery('#selectList'+currStep),
				spanid = 'nlw_item_'+module+'_'+crmid,
				existing = box.find('#'+spanid),
				singlelabel = jQuery('#SLVContainer_'+listid).find('#mod_singlelabel').val(),
				shortname = (entityname.length > 20 ? entityname.substr(0,10) + '...' : entityname),
				ename = singlelabel+': '+shortname;

			if (existing.length > 0) return;
		
			jQuery('#status').show();
			jQuery.ajax({
				url: 'index.php?module=Utilities&action=UtilitiesAjax&file=Card', // crmv@137471
				data: '&idlist='+crmid,
				type: 'POST',
				complete: function() {
					jQuery('#status').hide();
				},
				success: function(data) {
					// create a box
					var span = '<div id="'+spanid+'" style="float:left">'
						+'<table cellpadding="0" cellspacing="0" class="small">'
						+'<tr>'
						+	'<td></td>'
						+	'<td align="right"><a href="javascript:Wizard.recordRemove(\''+listid+'\',\''+spanid+'\');"><i class="vteicon md-sm" title="Elimina">clear</i></a>'
						+'</tr>'
						+'<tr><td colspan="2">'+data+'</td><tr>'
						+'</table>'
						+'</div>';
						
					box.append(span);
					
					me.records.add(currStep, module, crmid);
				}
			});
		},
		
		recordRemove : function(listid,spanid) {
			var me = this,
				currStep = me.currStep,
				box = jQuery('#selectList'+currStep),
				span = box.find('#'+spanid);
			var crmid = spanid.split('_');
			crmid = crmid[3];
		
			span.remove();
			me.records.remove(crmid);
		},
		
		prepareSaveData: function() {
			var me = this;
			
			// values for the creation
			var forms = {
				'EditView' : {}
			}
			
			var values = jQuery(document.forms['EditView']).serializeArray();
			jQuery.each(values, function(idx, obj) {
				// crmv@128382
				if (obj.name.match(/\[\]$/)) {
					// array (multiple select)
					var fname = obj.name.replace(/\[\]$/, '');
					if (!forms['EditView'][fname]) {
						forms['EditView'][fname] = [];
					}
					forms['EditView'][fname].push(obj.value);
				} else {
					forms['EditView'][obj.name] = obj.value;
				}
				// crmv@128382e
			});
			
			// values for related records
			var fieldsIds = me.records.getForFields();
			jQuery.each(fieldsIds, function(idx, obj) {
				forms['EditView'][obj.field] = obj.crmid;
			});
			me.records.removeForFields();
			
			// now values for related
			var related_ids = me.records.getAll();
			
			var postData = {
				wizardid : jQuery('#wizardid').val(),
				selectedRecords : JSON.stringify(related_ids),
				forms: JSON.stringify(forms)
			};
			
			return postData;
		},
		
		save: function(options, extraData, callback, callbackError) {
			var me = this,
				module = jQuery('#module').val(),
				currStep = me.currStep;
				
			options = jQuery.extend({}, {
				beSilent: false,
			}, options || {});
			
			if (!me.validateStep(currStep)) return false;
			
			var postData = me.prepareSaveData();
			
			// merge with post data
			postData = jQuery.extend({}, postData, extraData || {});

			jQuery('#status').show();
			jQuery.ajax({
				url: 'index.php?module='+module+'&action='+module+'Ajax&file=WizardAjax&ajaxaction=save',
				type: 'POST',
				data: postData,
				success: function(data) {
					jQuery('#status').hide();
					var error = false;
					try {
						var retData = JSON.parse(data);
						if (retData.success != '1') error = true;
					} catch (e) {
						error = true;
					}
					if (error) {
						if (!options.beSilent) alert(alert_arr.ERROR+(retData ? ': '+retData.error : ''));
						if (jQuery.isFunction(callbackError)) callbackError(data);
						return false;
					} else {
						if (jQuery.isFunction(callback)) callback(retData);
						if (retData.url != '') {
							parent.location.href = retData.url
						} else {
							closePopup();
						}
					}
				},
				error: function() {
					jQuery('#status').hide();
					if (!options.beSilent) alert(alert_arr.ERROR+': Ajax request failed');
					if (jQuery.isFunction(callbackError)) callbackError();
				}
			});
			
		},
		
		changeSelectList: function(step, module) {
			var me = this;
			
			jQuery('#nlWizStep'+step).find('.nlWizTargetList').hide();
			jQuery('#nlw_targetList'+step+'_'+module).show();
			
			var label = jQuery('#listlabel'+step+'_'+module).val();
			if (label) {
				jQuery('#nlw_targetsBoxCont'+step).find('.selectListTitle').text(label);
			}
		}
		
	}
	
}