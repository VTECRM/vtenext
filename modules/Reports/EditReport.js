/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@100905 */
/* crmv@98866 */

var EditReport = {
	
	mode: '',
	
	busy: false,
	
	modulesCache: {},
	fieldsCache: {},
	totalsFieldsCache: {},
	
	addedFirstAdvGroup: false,
	
	blockFrame: function() {
		VteJS_DialogBox.block(undefined, 0.2);
	},
	
	unblockFrame: function() {
		VteJS_DialogBox.unblock();
	},
	
	showBusy: function() {
		var me = this;
		me.busy = true;
		jQuery('#editreport_busy').show();
		me.blockFrame();
	},
	
	hideBusy: function() {
		var me = this;
		me.busy = false;
		jQuery('#editreport_busy').hide();
		me.unblockFrame();
	},
	
	ajaxCall: function(action, params, options, callback) {
		var me = this;
		
		// return if busy
		if (me.busy) return;
		
		options = jQuery.extend({}, {
			jsonData: true,
			includeForm: false,
		}, options || {});
		
		params = params || {};
		var url = "index.php?module=Reports&action=ReportsAjax&file=EditReportAjax&subaction="+action;
		
		if (options.includeForm) {
			var form = jQuery('#NewReport').serialize();
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
				if (options.hidePopupMessage) me.hidePopupMessage();
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
				if (options.hidePopupMessage) me.hidePopupMessage();
				if (options.callbackOnError) {
					if (typeof callback == 'function') callback();
				}
			}
		});
		
	},
	
	// crmv@128369
	prepareAdvFilters: function(containerid) {
		var me = this;

		var advfilters = [];
		jQuery('#'+containerid+' div.advFilterGroup').each(function() {
			var groupfields = [];
			jQuery('select.filterFields', this).each(function() {
				var row = jQuery(this).closest('tr');
				var name = JSON.parse(this.value);
				var comparator = jQuery('select[name=advFilterComparator]', row).val();
				
				var refid = null;
				var refchain = null;
				var reference = jQuery('input[name=advFilterReferenceValue]', row).val();
				if (reference) {
					reference = JSON.parse(reference);
					refid = reference.fieldid;
					refchain = reference.chain;
				}
				var cond = {
					fieldid: name.fieldid,
					chain: name.chain,
					comparator: comparator,
					value: jQuery('input[name=advFilterValue]', row).val(),
					value2: (comparator == 'bw' ? jQuery('input[name=advFilterValue2]', row).val() : null),
					glue: jQuery('select[name=advFilterGlue]', row).val(),
					reference: !!reference,
					reffieldid: refid,
					refchain: refchain
				};
				groupfields.push(cond);
			});
			advfilters.push({
				conditions: groupfields,
				glue: jQuery('div.advFilterGroupGlue select', this).val(),
			});
		});
		
		return advfilters;
	},
	// crmv@128369e
	
	saveReport: function() {
		var me = this,
			reptype = jQuery('input[name=reportType]:checked').val(),
			step = me.getCurrentStep();
		
		// validate this step
		if (!me.validateStep(step)) return;
		
		// prepare the fields
		var selfields = [];
		jQuery('#selectedfields .selectedField').each(function() {
			var value = jQuery('input[name=fldvalue]', this).val();
			selfields.push(JSON.parse(value));
		});
		
		// stdfilters
		var stdfilters = [];
		jQuery('#stdFiltersTable select.filterFields').each(function() {
			var row = jQuery(this).closest('tr'),
				rowid = row.attr('id');
			if (rowid && rowid.match('Master')) return;
			if (!this.value) return;

			var name = JSON.parse(this.value);
			var cond = {
				fieldid: name.fieldid,
				chain: name.chain,
				value: jQuery('select[name=stdDateFilter]', row).val(),
				startdate: jQuery('input[name=startdate]', row).val(),
				enddate: jQuery('input[name=enddate]', row).val(),
			};
			stdfilters.push(cond);
		});
		
		// crmv@128369
		var advfilters = me.prepareAdvFilters('advFiltersContainer');
		
		var clusters = jQuery('#clusters').val(); // already json
		// crmv@128369e
		
		// totals and summary
		var totals = [];
		var summary = [];
		jQuery('#totalsTable select.summaryFields').each(function(idx) {
			if (idx == 0) return; // skip the first
			var val = jQuery(this).val();
			var cont = jQuery(this).closest('tr');
			var allformulas = cont.find('input[name^=aggregator]');
			var aggregators = [];
			// check if at least one formula is checked
			if (val && allformulas.is(':checked')) {
				val = JSON.parse(val);
				allformulas.each(function() {
					if (jQuery(this).is(':checked')) {
						var aggr = this.name.replace('aggregator', '');
						aggregators.push(aggr);
						var tot = jQuery.extend({}, val, {aggregator: aggr});
						totals.push(tot);
					}
				});
				if (cont.find('input.summaryTotal').is(':checked')) {
					var summ = jQuery.extend({}, val, {aggregators: aggregators});
					summary.push(summ);
				}
			}
			
		});
		
		var sharinginfo = [];
		jQuery('#sharedmembers option').each(function() {
			sharinginfo.push(this.value);
		});
		
		var chartinfo = {};
		if (reptype == 'summary' && jQuery('#chartCheckbox').is(':checked')) {
			jQuery('#chartEditor :input').each(function() {
				if (this.name) {
					chartinfo[this.name] = jQuery(this).val();
				}
			});
		}
		
		// crmv@139057
		var scheduling = {};
		if (jQuery("#isReportScheduled").is(':checked') && jQuery('#scheduledReportFormat').length > 0) {
			var selectedUsers = [],
				selectedGroups = [],
				selectedRoles = [],
				selectedRolesAndSub = [];
				
			scheduling.format = jQuery('#scheduledReportFormat').val();

			var recipOpts = jQuery('#selectedRecipients option');
			recipOpts.each(function(idx, opt) {
				var selectedColArr = opt.value.split("::");
				if(selectedColArr[0] == "users")
					selectedUsers.push(selectedColArr[1]);
				else if(selectedColArr[0] == "groups")
					selectedGroups.push(selectedColArr[1]);
				else if(selectedColArr[0] == "roles")
					selectedRoles.push(selectedColArr[1]);
				else if(selectedColArr[0] == "rs")
					selectedRolesAndSub.push(selectedColArr[1]);
			});
			var selectedRecipients = {
				users: selectedUsers,
				groups: selectedGroups,
				roles: selectedRoles,
				rs: selectedRolesAndSub
			};
			scheduling.recipients = selectedRecipients;

			var scheduledInterval = {
				scheduletype: document.NewReport.scheduledType.value,
				month: document.NewReport.scheduledMonth.value,
				date: document.NewReport.scheduledDOM.value,
				day: document.NewReport.scheduledDOW.value,
				time: document.NewReport.scheduledTime.value
			};
			scheduling.schedule = scheduledInterval;
		}
		// crmv@139057e

		var params = {
			selectedfields: JSON.stringify(selfields),
			stdfilters: JSON.stringify(stdfilters),
			advfilters: JSON.stringify(advfilters),
			clusters: clusters, // crmv@128369
			totals: JSON.stringify(totals),
			summary: JSON.stringify(summary),
			sharinginfo: JSON.stringify(sharinginfo),
			chartinfo: JSON.stringify(chartinfo),
			scheduling: JSON.stringify(scheduling), // crmv@139057
		};
		
		// crmv@172355 - validate global save
		var charts_count = jQuery('#existing_charts').val();
		if (charts_count > 0) {
			// check if there is summary
			var hasSummary = false;
			if (reptype == 'summary') {
				for (var i=0; i<selfields.length; ++i) {
					if (selfields[i].summary) {
						hasSummary = true;
						break;
					}
				}
				// now check clusters
				if (!hasSummary && clusters) {
					hasSummary = true;
				}
			}
			if (!hasSummary) {
				if (charts_count == 1) {
					var str = alert_arr.LBL_REPORT_REMOVE_CHARTS_1;
				} else {
					var str = alert_arr.LBL_REPORT_REMOVE_CHARTS_N.replace('{n}', charts_count);
				}
				vteconfirm(str, function(yes) {
					jQuery('#remove_charts').val(yes ? 1 : 0);
					// go on saving!
					me.doReportSave(params);
				}, {
					btn_cancel_label: alert_arr.NO,
					btn_ok_label: alert_arr.YES,
				});
				return;
			}
		}
		
		me.doReportSave(params);
	},
	
	doReportSave: function(params) {
		var me = this;
		
		me.ajaxCall('SaveReport', params, {includeForm: true}, function(data) {
			if (data && data.result) {
				// crmv@139858
				var return_module = jQuery('#return_module').val();
				if (return_module == 'CustomView') {
					parent.return_report_to_cv(data.result.reportid,jQuery('#reportname').val());
					closePopup();
				} else if (return_module.match(/^Targets/)) {
					var pieces = return_module.split(':');
					return_module = pieces[0];
					var return_field = pieces[1];
					parent.return_report_to_rl(data.result.reportid,jQuery('#reportname').val(), return_field);
					closePopup();
				} else {
					// ok, saved, redirect to this new report
					window.parent.location.href = '?module=Reports&action=SaveAndRun&folderid='+data.result.folderid+'&record='+data.result.reportid;
				}
				// crmv@139858e
			}
		});
	},
	// crmv@172355e
	
	getCurrentStep: function() {
		return parseInt(jQuery('#rightPane div[id^=reportStep]:visible').attr('id').replace('reportStep', ''));
	},
	
	// crmv@128369
	gotoStep: function(step) {
		var me = this,
			currStep = me.getCurrentStep(),
			cells = jQuery('#reportStepTable td'),
			lastStep = parseInt(cells.last().attr('id').replace(/[^0-9]+/g, ''));
		
		step = parseInt(step);
		if (step <= 0 || step > lastStep) return false;
		
		// check if cluster step is present and fix the step
		var hasCluster = (jQuery('#reportStep5').length > 0);
		if (!hasCluster && step == 5) {
			if (step < currStep) {
				--step;
			} else {
				++step;
			}
		}
		
		// crmv@139057 
		// check if report has charts
		var hasCharts = (jQuery('#reportStep9').length > 0);
		if (!hasCharts && step == 9) {
			if (step < currStep) {
				--step;
			} else {
				++step;
			}
		}
		// crmv@139057e
		
		if (step != currStep) {
			var valid = (step < currStep || me.validateStep(currStep));
			if (valid) {
				jQuery('#reportStep'+currStep).hide();
				jQuery('#reportStep'+step).show();
				jQuery('#step'+(currStep)+'label').removeClass('reportStepCellSelected');
				jQuery('#step'+(step)+'label').addClass('reportStepCellSelected');
				jQuery('#nextButton')[step == lastStep ? 'hide' : 'show']();
				jQuery('#saveButton')[step == lastStep ? 'show' : 'hide']();
				jQuery('#backButton')[step == 1 ? 'hide' : 'show']();
				return me.initializeStep(step);
			}
		}
		return false;
	},
	// crmv@128369e
	
	gotoNextStep: function() {
		var me = this,
			step = me.getCurrentStep();
			
		me.gotoStep(step+1);
	},
	
	gotoPrevStep: function() {
		var me = this,
			step = me.getCurrentStep();
		
		me.gotoStep(step-1);
	},
	
	initializeStep: function(step) {
		var me = this,
			fname = 'initializeStep'+step;
		
		if (typeof me[fname] == 'function') {
			return me[fname]();
		}
		return true;
	},
	
	validateStep: function(step) {
		var me = this,
			fname = 'validateStep'+step;
		
		if (typeof me[fname] == 'function') {
			return me[fname]();
		}
		return true;
	},
	
	initializeStep1: function() {
		var me = this,
			reportid = me.getReportId();

		if (reportid > 0)
			me.mode = 'edit';
		else
			me.mode = 'create';
	},
	
	validateStep1: function() {
		var me = this;
		
		if (!jQuery('#reportname').val()) {
			var label = jQuery('#reportname').closest('tr').find('td span').text();
			alert(label+" "+alert_arr.IS_MANDATORY_FIELD);
			return false;
		}
		if (jQuery('#createNewFolderRow').is(':visible') && !jQuery('#reportnewfolder').val()) {
			var label = jQuery('#reportnewfolder').closest('tr').find('td span').text();
			alert(label+" "+alert_arr.IS_MANDATORY_FIELD);
			return false;
		}
		
		return true;
	},
	
	initializeStep3: function() {
		var me = this,
			modlabel = me.getMainModuleLabel(),
			stdcont = jQuery('#stdFiltersTable');
		
		stdcont.find('.chainMainModule').text(modlabel);
		
		// now populate the standard filters
		var chain = me.getModulesChain(jQuery('#stdFilterMasterRow0'));
		me.fetchModulesList(chain, true, 'stdfilter', function(data) {
			if (data && data.modules) {
				me.populateModulesPicklist(jQuery('#stdFilterMasterRow0 select.chainModule'), data.modules);
				if (data.fields) me.populateFieldsPicklist(jQuery('#stdFilterMasterRow2 select.filterFields'), data.fields, false);
				// add the filter if not already there!
				var nrows = stdcont.get(0).rows.length;
				if (nrows <= 3) {
					me.addStdFilter();
				}
			}
		});
		
	},
	
	// crmv@161265
	validateStep3: function() {
		var me = this;
		
		var filtertype = jQuery('#stdFiltersTable').find('select[name=stdDateFilter]').last().val();
		var value1 = jQuery('#stdFiltersTable').find('input[name=startdate]').last().val();
		var value2 = jQuery('#stdFiltersTable').find('input[name=enddate]').last().val();
		
		// if custom is chosen and the start date is set, the end date must be set
		if (filtertype == 'custom' && value1 != '') {
			if (value2 == '') {
				alert(alert_arr.ENTER_VALID+' '+alert_arr.ENDDATE);
				return false;
			}
		}
		return true;
	},
	// crmv@161265e
	
	initializeStep4: function() {
		var me = this,
			modlabel = me.getMainModuleLabel(),
			mastercont = jQuery('#advFiltersMaster'),
			cont = jQuery('#advFiltersContainer');
		
		mastercont.find('.chainMainModule').text(modlabel);
		cont.find('.chainMainModule').text(modlabel);
		
		var chain = me.getModulesChain(jQuery('#advFilterMasterRow0'));
		me.fetchModulesList(chain, true, 'advfilter', function(data) {
			if (data && data.modules) {
				me.populateModulesPicklist(jQuery('#advFilterMasterRow0 select.chainModule'), data.modules);
				if (data.fields) me.populateFieldsPicklist(jQuery('#advFilterMasterRow1 select.filterFields'), data.fields);
				var nrows = cont.find('.advFilterGroup').length;
				if (me.mode == 'create' && nrows == 0 && !me.addedFirstAdvGroup) {
					// prepare an empty group the first time
					me.addFilterGroup();
					me.addedFirstAdvGroup = true;
				}
			}
		});
		
	},
	
	validateStep4: function() {
		var me = this,
			valid = true,
			cont = jQuery('#advFiltersContainer');
		
		cont.find('select[name=advFilterComparator]').each(function() {
			var cond = jQuery(this).closest('tr'),
				opt = cond.find('select.filterFields option:selected'),
				label = opt.text(),
				finfo = (opt ? opt.data() : {}),
				comparator = jQuery(this).val(),
				value = cond.find('input[name=advFilterValue]').val(),
				value2 = cond.find('input[name=advFilterValue2]').val(),
				refvalue = cond.find('input[name=advFilterReferenceValue]').val();
			
			// check for empty comparators
			if (!comparator) {
				alert(alert_arr.MISSING_COMPARATOR);
				valid = false;
				return false;
			}
			
			// now check the single fields
			if (!me.validateField(label, finfo, comparator, value, value2, refvalue)) {
				valid = false;
				return false;
			}
		});
		
		return valid;
	},
	
	// crmv@128369
	initializeStep5: function() {
		var me = this;
		
		var text = '';
		var indent = '';
		// show the chosen advanced filters
		var ngroups = jQuery('#advFiltersContainer .advFilterGroup').length;
		jQuery('#advFiltersContainer .advFilterGroup').each(function(idx, item) {
			var groupglue = jQuery(item).find('div.advFilterGroupGlue').find('select option:selected').text();
			if (ngroups > 1) {
				text += "(\n";
				indent = "\t";
			}
			var nconds = jQuery(item).find('.filterFields').length;
			jQuery(item).find('.filterFields').each(function(idx2, item2) {
				var row = jQuery(item2).closest('tr');
				var fieldlabel = jQuery(item2).find('option:selected').text();
				var comparator = row.find('select[name=advFilterComparator]').val();
				var complabel = row.find('select[name=advFilterComparator] option:selected').text();
				var value = row.find('input[name=advFilterValue]').val();
				var value2 = row.find('input[name=advFilterValue2]').val();
				var valueref = row.find('input[name=advFilterReferenceLabel]').val();
				var glue = row.find('select[name=advFilterGlue] option:selected').text();
				
				var condtext = indent + fieldlabel + ' ' + complabel + ' ';
				if (comparator == 'bw') {
					condtext += '"' + value + '" ' + alert_arr.LBL_AND + ' "' + value2 + '"';
				} else {
					if (valueref) {
						condtext += valueref;
					} else {
						condtext += '"' + value + '"';
					}
				}
				if (idx2 < nconds-1) {
					condtext += ' ' + glue;
				}
				text += condtext + "\n";
			});
			if (ngroups > 1) text += ')';
			if (idx < ngroups-1) {
				text += ' ' + groupglue + "\n";
			}
		});
		
		// now translate to html
		text = text.replace(/\n/g, "<br>\n").replace(/\t/g, "&nbsp;&nbsp;&nbsp;&nbsp;");
		
		jQuery('#filterListReviewContent').html(text);
		if (text == '') {
			jQuery('#filterListReview').hide();
		} else {
			jQuery('#filterListReview').show();
		}
	},
	// crmv@128369e
	
	validateField: function(label, fieldinfo, comparator, value, value2, refvalue) {
		var me = this,
			uitype = fieldinfo.uitype,
			wstype = fieldinfo.wstype;
		
		// if it's empty or a reference, don't check the field value
		if (refvalue) return true;
		
		// allow empty comparison when checking equal or different
		if (!value && (comparator == 'e' || comparator == 'n')) return true;
		
		if (wstype == 'integer') {
			return intValidate(fieldinfo.fieldname,label, uitype, value);
		} else if (wstype == 'double' || wstype == 'currency') {
			return numValidate(fieldinfo.fieldname,label,"any",true, uitype, value);
		} else if (wstype == 'boolean') {
			if (value != '0' && value != '1' && value != 'yes' && value != 'no') {
				alert(alert_arr.ENTER_VALID+label);
				return false;
			}
		} else if (wstype == 'date') {
			if (!re_dateValidate(value,label, "OTH")) return false;
			if (comparator == 'bw') {
				if (!re_dateValidate(value2,label,"OTH")) return false
			}
		} else if (wstype == 'datetime') {
			var dtime = value.split(" ");
			if (!re_dateValidate(dtime[0],label, "OTH")) return false;
			if (dtime.length > 1) {
				if (!re_patternValidate(dtime[1],label,"TIMESECONDS")) return false;
			}
			if (comparator == 'bw') {
				var dtime2 = value2.split(" ");
				if (!re_dateValidate(dtime2[0],label,"OTH")) return false
				if (dtime2.length > 1) {
					if (!re_patternValidate(dtime2[1],label,"TIMESECONDS")) return false;
				}
			}
		}
		
		return true;
	},
	
	initializeStep6: function() { // crmv@128369
		var me = this;
			reptype = jQuery('input[name=reportType]:checked').val();
		var modlabel = me.getMainModuleLabel();
		var cont = jQuery('#selectedfields');
		
		// fix dimensions
		var w = cont.parent().width() - 10;
		var h = jQuery(document).height() - cont.offset().top - 10;
		cont.css({
			width: w,
			height: h,
		});
		
		// make it sortable
		cont.sortable({
			axis: 'x',
			containment: 'parent',
			distance: 10,
			opacity: 0.8,
			update: function(event, ui) {
				// reposition to fix grouped fields
				me.repositionField(jQuery('input[name=fieldGroupCheck]', ui.item));
			}
		});
		
		jQuery('#fieldsMainModule').text(modlabel);
		jQuery('#fieldsModuleChain1').nextAll('select').remove();
		jQuery('#fieldsModuleChain1').nextAll('span.chainArrow').remove();
		
		var chain = me.getModulesChain(jQuery('#fieldsChainModules'));
		me.fetchModulesList(chain, true, false, function(data) {
			if (data && data.modules) {
				me.populateModulesPicklist(jQuery('#fieldsModuleChain1'), data.modules);
				if (data.fields) me.populateFieldsPicklist(jQuery('#availfields'), data.fields);
			}
		});
		
		// check the formulas and grouping for existing fields
		jQuery('#selectedfields .selectedField').each(function() {
			var data = jQuery('input[name=flddata]', this).data();
			if (me.hasFieldFormula(data)) {
				me.filterFieldFormula(this);
				jQuery('tr[name=fieldPropFormula]', this).show();
			}
			if (reptype == 'tabular') {
				jQuery('tr[name=fieldPropGrouping]', this).hide();
				jQuery('input[name=fieldGroupCheck]', this).prop('checked', false);
				jQuery('tr[name=fieldPropSummary]', this).hide();
				jQuery('input[name=fieldSummary]', this).prop('checked', false);
				jQuery('tr[name=fieldPropSortorder]', this).hide();
			} else if (reptype == 'summary') {
				jQuery('tr[name=fieldPropGrouping]', this).show();
				jQuery('tr[name=fieldPropSortorder]', this).show();
			}
		});
	},
	
	validateStep6: function() { // crmv@128369
		var me = this;
		
		if (jQuery('#selectedfields .selectedField').length == 0) {
			alert(alert_arr.LBL_SELECT_AT_LEAST_ONE_FIELD);
			return false;
		}
		
		return true;
	},
	
	initializeStep7: function() { // crmv@128369
		var me = this,
			cont = jQuery('#totalsTable');
		
		var modlabel = me.getMainModuleLabel();
		cont.find('.chainMainModule').text(modlabel);
		
		var chain = me.getModulesChain(jQuery('#totalsMasterRow0'));
		me.fetchModulesList(chain, true, 'total', function(data) {
			if (data && data.modules) {
				me.populateModulesPicklist(jQuery('#totalsMasterRow0 select.chainModule'), data.modules);
				if (data.fields) me.populateFieldsPicklist(jQuery('#totalsMasterRow2 select.summaryFields'), data.fields, false);
			}
		});
	},
	
	initializeStep8: function() { // crmv@128369
		var me = this;
		
		me.changeSharing();
	},
	
	validateStep8: function() { // crmv@128369
		var me = this,
			type = jQuery('#sharingtype').val();

		if (type == 'Shared') {
			if (jQuery('#sharedmembers option').length == 0) {
				// TODO: alert
				return false;
			}
		}
		
		return true;
	},
	
	initializeStep9: function() { // crmv@128369
		var me = this,
			visible = true;
			reptype = jQuery('input[name=reportType]:checked').val();
			
		if (reptype != 'summary') {
			//chart not possible
			visible = false;
		}
		
		if (visible) {
			jQuery('#chartNotAvailable').hide();
			jQuery('#chartEditor').show();
			
			// automatically set the name if empty
			if (!jQuery('#chartname').val()) {
				jQuery('#chartname').val(jQuery('#reportname').val());
			}
		} else {
			jQuery('#chartNotAvailable').show();
			jQuery('#chartEditor').hide();
		}
	},
	
	validateStep9: function() { // crmv@128369
		var me = this,
			checked = jQuery('#chartCheckbox').is(':checked');
			
		if (!checked) return true;
		
		// check chart type and name
		var chtype = jQuery('#chart_type').val();
		var chname = jQuery('#chartname').val();
		
		if (!chtype) {
			var label = jQuery('#chartTypeLabel').text();
			alert(alert_arr.ENTER_VALID+' '+label);
			return false;
		} else if (!chname) {
			var label = jQuery('#chartname').closest('td').find('.dvtCellLabel').text();
			alert(alert_arr.ENTER_VALID+' '+label);
			return false;
		}
		
		return true;
	},
	
	// crmv@139057
	initializeStep10: function() {
		this.setScheduleOptions();
		this.generateRecipientOption();
	},
	
	validateStep10: function() {
		var isScheduled = jQuery("#isReportScheduled").is(':checked');
		
		if (isScheduled && jQuery('#scheduledReportFormat').length > 0) {
			if (jQuery('#selectedRecipients option').length == 0) {
				alert(alert_arr.LBL_SELECT_RECIPIENTS);
				return false;
			}
		}

		return true;
	},
	// crmv@139057e
	
	getReportId: function() {
		return parseInt(jQuery('#reportid').val()) || 0;
	},
	
	getMainModuleLabel: function() {
		if (jQuery('#primarymodule_display').length > 0) {
			var modlabel = jQuery('#primarymodule_display').val();
		} else {
			var modlabel = jQuery('#primarymodule option:selected').text();
		}
		return modlabel;
	},
	
	changePrimaryModule: function() {
		var me = this;
		
		// remove everything
		me.removeAllStdFilters();
		me.removeAllFilterGroups();
		me.removeAllFields();
		me.removeAllTotalFields();
		
		// remove chart
		jQuery('#chartCheckbox').prop('checked', false);
	},

	toggleCreateFolder: function() {
		var me = this;
		if (jQuery('#selectFolderRow').is(':visible')) {
			// show create
			jQuery('#selectFolderRow').hide();
			jQuery('#reportnewfolder').val('');
			jQuery('#createNewFolderRow').show();
		} else {
			// show choose
			jQuery('#createNewFolderRow').hide();
			jQuery('#reportnewfolder').val('');
			jQuery('#selectFolderRow').show();
		}
	},
	
	changeReportType: function() {
		var me = this,
			reptype = jQuery('input[name=reportType]:checked').val();
			
		// nothing at the moment
	},
	
	// crmv@100585
	addStdFilter: function() {
		var me = this,
			src = [jQuery('#stdFilterMasterRow0'), jQuery('#stdFilterMasterRow1'), jQuery('#stdFilterMasterRow2')],
			target = jQuery('#stdFiltersTable');
			
		for (var i=0; i<src.length; ++i) {
			var newrow = src[i].clone();
			newrow.removeAttr('id');
			
			var $inputStart = newrow.find('input[name=startdateTpl]').attr('name', 'startdate');
			var $inputEnd = newrow.find('input[name=enddateTpl]').attr('name', 'enddate');
			
			// warning: setting the id is ok, as long as there is only one field
			newrow.find('select.filterFields').attr('id', 'stdFilterFields');
			
			// add the id for the stupid js code in reports.php
			var $triggerStart = newrow.find('i.iconDateStart').attr('id', 'jscal_trigger_date_start');
			var $triggerEnd = newrow.find('i.iconDateEnd').attr('id', 'jscal_trigger_date_end');
			
			// setup the datepicker
			var jscal_format = jQuery('#jscal_dateformat').val();
			var jscal_lang = jQuery('#jscal_language').val();
			setupDatePicker($inputStart, {
				trigger: $triggerStart,
				date_format: jscal_format,
				language: jscal_lang,
			});
			setupDatePicker($inputEnd, {
				trigger: $triggerEnd,
				date_format: jscal_format,
				language: jscal_lang,
			});
			
			target.append(newrow);
			newrow.show();
		}
		
		target.show();
		// add button is never shown
		//jQuery('#stdFilterAddButton').hide();
	},
	// crmv@100585e
	
	removeStdFilter: function(self) {
		var me = this;
		var cont = jQuery(self).closest('table');
		
		jQuery(self).closest('tr').prev().remove();
		jQuery(self).closest('tr').remove();
		
		// remove the group if empty
		if (cont.find('tr').length <= 3) {
			cont.hide();
		}
		
		//jQuery('#stdFilterAddButton').show();
	},
	
	removeAllStdFilters: function() {
		var me = this;
		var cont = jQuery('#stdFiltersTable');
		
		cont.find('tr').each(function() {
			if (!this.id.match('Master')) {
				jQuery(this).remove();
			}
		});
		
		cont.hide();
		
		//jQuery('#stdFilterAddButton').show();
	},
	
	// crmv@106510
	alignStdFilterFields: function(firstload) {
		var me = this;
		
		jQuery('#stdFiltersTable').find('select[name=stdDateFilter]').each(function() {
			var opt = this.options[this.selectedIndex].value;
			if (!firstload || opt != 'custom') {
				showDateRange(opt);
			}
		});
		
	},
	// crmv@106510e
	
	addFilter: function(self) {
		var me = this,
			src = [jQuery('#advFilterMasterRow0'), jQuery('#advFilterMasterRow1')],
			target = jQuery(self).closest('table').prev();
			
		// show the glue for the previous field
		target.find('select.advFilterGlue').show();
		
		// find the group and cond idx
		var groupidx = target.closest('div.advFilterGroup').index();
		var condidx = target.get(0).rows.length / src.length;
		
		for (var i=0; i<src.length; ++i) {
			var newrow = src[i].clone();
			newrow.removeAttr('id');
			//newrow.find('input').prop('checked', false);
			
			var chainmod = newrow.find('select.chainModule'),
				ochange = chainmod.attr('onchange');
				
			if (ochange) {
				ochange = ochange.replace('GROUPIDX', groupidx).replace('CONDIDX', condidx);
				chainmod.attr('onchange', ochange);
			}
			
			newrow.find('select.filterFields').attr('id', 'advFilterFields_'+groupidx+'_'+condidx);
			
			target.append(newrow);
			newrow.show();
		}
		
		me.alignFilterField(newrow.find('select.filterFields'));		
		//target.show();
	},
	
	addFilterGroup: function() {
		var me = this;
			src = jQuery('#advFiltersMaster'),
			target = jQuery('#advFiltersContainer');
		
		// show the glue for the previous group
		target.find('div.advFilterGroupGlue').show();
		
		var groupidx = target.find('div.advFilterGroup').length;
		var condidx = 0;
			
		var newgroup = src.clone();
		
		// remove master ids
		newgroup.removeAttr('id');
		newgroup.find('#advFiltersBlockMaster').removeAttr('id');
		newgroup.find('#advFiltersBlockGlueMaster').removeAttr('id');
		newgroup.find('#advFilterMasterRow0').removeAttr('id');
		newgroup.find('#advFilterMasterRow1').removeAttr('id');
		
		var chainmod = newgroup.find('select.chainModule'),
			ochange = chainmod.attr('onchange');
		
		if (ochange) {
			ochange = ochange.replace('GROUPIDX', groupidx).replace('CONDIDX', condidx);
			chainmod.attr('onchange', ochange);
		}
		
		newgroup.find('select.filterFields').attr('id', 'advFilterFields_'+groupidx+'_'+condidx);
		newgroup.show();

		target.append(newgroup);
		
		me.alignFilterField(newgroup.find('select.filterFields'));		
	},
	
	removeFilter: function(self) {
		var me = this;
		var group = jQuery(self).closest('table');
		
		jQuery(self).closest('tr').prev().remove();
		jQuery(self).closest('tr').remove();
		
		// remove the group if empty
		if (group.find('tr').length == 0) {
			me.removeFilterGroup(group);
		} else {
			// hide the glue for the last field
			group.find('select.advFilterGlue').last().hide();
		}
		
	},
	
	removeFilterGroup: function(self) {
		var me = this,
			groupcont = jQuery(self).closest('div.advFilterGroup'),
			parent = groupcont.parent();

		groupcont.remove();
		
		// hide the glue for the last group
		parent.find('div.advFilterGroupGlue').last().hide();
	},
	
	removeAllFilterGroups: function() {
		var me = this,
			cont = jQuery('#advFiltersContainer');
			
		cont.find('div.advFilterGroup').each(function() {
			jQuery(this).remove();
		});
	},
	
	changeFilterComparator: function(self) {
		var me = this,
			cont = jQuery(self).closest('tr'),
			comparator = jQuery(self).val();
			
		if (comparator == 'bw') {
			// show second value
			me.clearReferenceFilter(self);
			jQuery('input[name=advFilterValue]', cont).width('40%');
			jQuery('input[name=advFilterValue2]', cont).val('').show();
			jQuery('span[name=advFilterValueAnd]', cont).show();
			jQuery('i[name=setReferenceIcon]').hide();
		} else {
			// hide it
			jQuery('input[name=advFilterValue]', cont).css('width', '');
			jQuery('input[name=advFilterValue2]', cont).hide().val('');
			jQuery('span[name=advFilterValueAnd]', cont).hide();
			jQuery('i[name=setReferenceIcon]').show();
		}
			
	},
	
	alignFilterField: function(self, keepvalues) {
		var me = this,
			row = jQuery(self).closest('tr'),
			tokeep = ['e', 'n', 's', 'ew', 'c', 'k'],
			finfo = jQuery(self).find('option:selected').data();

		if (finfo.wstype == 'integer' || finfo.wstype == 'double' || finfo.wstype == 'currency') {
			tokeep = ['e','n','l','g','m','h'];
		} else if (finfo.wstype == 'date' || finfo.wstype == 'datetime' || finfo.wstype == 'time') {	//crmv@128159
			tokeep = ['e','n','l','g','m','h','bw','b','a'];
		} else if (finfo.wstype == 'boolean') {
			tokeep = ['e','n'];
		}
		
		var optselect = row.find('select[name=advFilterComparator]');
		var condvalue = row.find('input[name=advFilterValue]');
		var condvalue2 = row.find('input[name=advFilterValue2]');
		//var condref = row.find('input[name=advFilterReference]');
		optselect.find('option').each(function() {
			var optval = this.value;
			if (tokeep.indexOf(optval) >= 0) {
				jQuery(this).show();
			} else {
				jQuery(this).hide();
			}
		});
		
		if (!keepvalues) {
			optselect.val('');
			condvalue.val('').prop('disabled', false);
			condvalue2.hide().val('');
			//condref.val('0');
			me.changeFilterComparator(optselect);
		}
		
	},
	
	alignFilterFields: function() {
		var me = this,
			cont = jQuery('#advFiltersContainer');
			
		cont.find('select.filterFields').each(function() {
			me.alignFilterField(this, true);
		});
		
	},
	
	setReferenceFilter: function(self) {
		var me = this,
			cont = jQuery('#CompareField');
		var modlabel = me.getMainModuleLabel();
		
		var selectid = jQuery(self).closest('tr').find('select.filterFields').attr('id');

		jQuery('#compareFieldRef').val(selectid);
		
		cont.find('.chainMainModule').text(modlabel);
		jQuery('#chainModuleComp1').nextAll('select').remove();
		jQuery('#chainModuleComp1').nextAll('span.chainArrow').remove();
		
		var chain = me.getModulesChain(cont.find('.rptChainContainer'));
		me.fetchModulesList(chain, true, 'advfilter', function(data) {
			if (data && data.modules) {
				me.populateModulesPicklist(jQuery('#chainModuleComp1'), data.modules);
				if (data.fields) me.populateFieldsPicklist(jQuery('#selectCompareField'), data.fields);
			}
			showFloatingDiv('CompareField');
		});
		
	},
	
	clearReferenceFilter: function(self) {
		var me = this,
			cont = jQuery(self).closest('tr');
		
		cont.find('input[name=advFilterReferenceValue]').val('').hide();
		cont.find('input[name=advFilterReferenceLabel]').val('').hide();
		
		cont.find('input[name=advFilterValue]').val('').show();
		cont.find('input[name=advFilterValue2]').val('').hide();
		
		cont.find('i[name=setReferenceIcon]').show();
		cont.find('i[name=clearReferenceIcon]').hide();
	},
	
	applyReferenceFilter: function() {
		var me = this,
			opt = jQuery('#selectCompareField option:selected'),
			selectid = jQuery('#compareFieldRef').val(),
			cont = jQuery('#'+selectid).closest('tr');
		
		cont.find('input[name=advFilterReferenceValue]').val(opt.val());
		cont.find('input[name=advFilterReferenceLabel]').val(opt.text()).show();
		
		cont.find('input[name=advFilterValue]').val('').hide();
		cont.find('input[name=advFilterValue2]').val('').hide();
		
		cont.find('i[name=setReferenceIcon]').hide();
		cont.find('i[name=clearReferenceIcon]').show();
		
		hideFloatingDiv('CompareField');
	},
	
	// crmv@128369
	createCluster: function(reportid) {
		var module = jQuery('#primarymodule').val();
		openPopup('index.php?module=Reports&action=ReportsAjax&file=EditCluster&reportid='+reportid+'&primodule='+module);
	},
	
	editCluster: function(reportid, self) {
		var module = jQuery('#primarymodule').val();
		var row = jQuery(self).closest('tr');
		var index = row.index()-1; // remove header row
		openPopup('index.php?module=Reports&action=ReportsAjax&file=EditCluster&reportid='+reportid+'&clusteridx='+index+'&primodule='+module);
	},
	
	validateCluster: function(newcluster, clusters, clusteridx) {
		var me = this;
		
		// validation
		if (!newcluster.name) {
			var label = jQuery('#clustername').closest('tr').find('td span').text();
			alert(label+" "+alert_arr.IS_MANDATORY_FIELD);
			return false;
		}
		
		// check if existing (only when creating)
		if (clusteridx === undefined || clusteridx === null || clusteridx === '') {
			for (var i=0; i<clusters.length; ++i) {
				var oname = clusters[i].name;
				if (newcluster.name.toLowerCase() == oname.toLowerCase()) {
					alert(alert_arr.LBL_DUPLICATE_VALUE_EXISTS);
					return false;
				}
			}
		}
		
		if (newcluster.conditions.length == 0) {
			alert(alert_arr.LBL_LEAST_ONE_CONDITION);
			return false;
		}
		
		if (!me.validateStep(4)) {
			return false;
		}
		
		return true;
	},
	
	saveCluster: function(reportid, clusteridx) {
		var me = this;
		var clustername = jQuery('#clustername').val().trim();
		
		var cluster = {
			name: clustername,
			color: jQuery('#clustercolor').val(), // crmv@133997
			conditions: me.prepareAdvFilters('advFiltersContainer'),
		}
		
		var parentCont = parent.jQuery('#clusters');
		try {
			var clusters = JSON.parse(parentCont.val()) || [];
		} catch (e) {
			var clusters = [];
		}
		
		// validation
		if (!me.validateCluster(cluster, clusters, clusteridx)) return false;
		
		// add it to the hidden parent field
		if (typeof clusteridx != 'undefined' && clusteridx !== null && clusteridx !== '') {
			// replace
			clusters.splice(clusteridx, 1, cluster);
		} else {
			// add new cluster
			clusters.push(cluster);
		}
		
		parentCont.val(JSON.stringify(clusters));
		
		// reload the list
		parent.EditReport.reloadClustersList(reportid);
		
		closePopup();
	},
	
	removeCluster: function(reportid, self) {
		var me = this;
		
		var row = jQuery(self).closest('tr');
		var index = row.index()-1; // remove header row
		row.remove();
		
		var cont = jQuery('#clusters');
		try {
			var clusters = JSON.parse(cont.val()) || [];
		} catch (e) {
			var clusters = [];
		}
		
		clusters.splice(index, 1);
		cont.val(JSON.stringify(clusters));
	},
	
	loadEditCluster: function(reportid, clusteridx) {
		var me = this;
		
		var parentCont = parent.jQuery('#clusters');
		try {
			var clusters = JSON.parse(parentCont.val()) || [];
		} catch (e) {
			var clusters = [];
		}
		var cluster = clusters[clusteridx];
		if (cluster) {
			jQuery('#clustername').val(cluster.name);
			// crmv@133997
			if (cluster.color) {
				// this class doesn't provide a way to change the color :(
				jQuery('#clustercolor').val(cluster.color);
				jQuery('#clustercolor').next('.colorPicker-picker').css("background-color", cluster.color);
			}
			// crmv@133997e
			var params = {
				reportid: reportid,
				clusteridx: clusteridx,
				primodule: jQuery('#primarymodule').val(), // crmv@181858
				cluster: JSON.stringify(cluster),
			}
			me.ajaxCall('LoadClusterFilters', params, null, function(data) {
				jQuery('#clusterfilters').html(data.result);
				me.initializeStep(4);
			});
		}
		
	},
	
	reloadClustersList: function(reportid) {
		var me = this;
		
		var params = {
			reportid: reportid,
			clusterdata: jQuery('#clusters').val()
		}
		
		me.ajaxCall('LoadClustersList', params, null, function(data) {
			jQuery('#clustersList').html(data.result);
		});
	},
	// crmv@128369e
	
	addFields: function() {
		var me = this,
			reptype = jQuery('input[name=reportType]:checked').val(),
			src = jQuery('#availfields'),	
			target = jQuery('#selectedfields');
		
		src.find('option:selected').each(function() {
			// add the field
			if (!me.isFieldChosen(this.value)) {
				var data = jQuery(this).data();
				var tpl = jQuery('#selectFieldTemplate');
				
				var newfield = tpl.clone();
				jQuery('input[name=fldvalue]', newfield).val(this.value);
				jQuery('input[name=flddata]', newfield).data(data);
				jQuery('span[name=fieldLabel]', newfield).text(jQuery(this).text());
				jQuery('span[name=fieldModuleName]', newfield).text(data.singlelabel);
				
				if (data.transModule) {
					var moduleLower = data.module.toLowerCase();
					var transModule = data.transModule;
					var firstLetter = transModule.substr(0, 1).toUpperCase();
					jQuery('i[name=fieldIcon]', newfield).addClass('icon-' + moduleLower).attr('data-first-letter', firstLetter);
				}
				
				newfield.removeAttr('id')
				newfield.show().removeAttr('style');
				
				if (me.hasFieldFormula(data)) {
					me.filterFieldFormula(newfield);
					jQuery('tr[name=fieldPropFormula]', newfield).show();
				}
				
				// remove the grouping and summary if the report is tabular
				if (reptype != 'summary') {
					// hide the fields
					jQuery('tr[name=fieldPropGrouping]', newfield).hide();
					jQuery('tr[name=fieldPropSummary]', newfield).hide();
					jQuery('tr[name=fieldPropSortorder]', newfield).hide();
				}
				
				target.append(newfield);
			}
		});
		
		// refresh the sortable
		target.sortable('refresh');
		
		// scroll to end
		target.stop().animate({
			scrollLeft: target.get(0).scrollWidth
		}, 500);
	},
	
	isFieldChosen: function(value) {
		var me = this,
			exists = false,
			target = jQuery('#selectedfields');
		
		value = JSON.parse(value);
		
		target.find('.selectedField input[name=fldvalue]').each(function() {
			var tval = JSON.parse(this.value);
			// weak way to compare arrays, but here is enough
			if (value.fieldid == tval.fieldid && value.chain.toString() == tval.chain.toString()) {
				exists = true;
				return false;
			}
		});
		
		return exists;
	},
	
	removeField: function(self) {
		var me = this,
			target = jQuery(self).closest('.selectedField');
		
		target.remove();
	},
	
	removeAllFields: function() {
		var me = this,
			target = jQuery('#selectedfields');
		
		target.find('.selectedField').remove();
	},

	
	hasFieldFormula: function(fdata) {
		var me = this,
			hasit = false;
		
		if (fdata && window.ReportFieldFormulas) {
			jQuery.each(ReportFieldFormulas, function(idx, formula) {
				if (formula.uitypes.indexOf(fdata.uitype) >= 0 && formula.wstypes.indexOf(fdata.wstype) >= 0) {
					hasit = true;
					return false;
				}
			});
		}
		
		return hasit;
	},
	
	filterFieldFormula: function(fieldcont) {
		var me = this,
			target = jQuery('select[name=fieldFormula]', fieldcont),
			fldvalue = jQuery('input[name=fldvalue]', fieldcont).val(),
			fval = JSON.parse(fldvalue) || {},
			fdata = fldvalue = jQuery('input[name=flddata]', fieldcont).data(),
			hasit = false;
			
		if (fdata && window.ReportFieldFormulas) {
			target.html('');
			var opts = "";
			opts += '<option value="">--'+alert_arr.LBL_NONE+'--</option>';
			jQuery.each(ReportFieldFormulas, function(idx, formula) {
				if (formula.uitypes.indexOf(fdata.uitype) >= 0 && formula.wstypes.indexOf(fdata.wstype) >= 0) {
					var selected = '';
					if (fval.formula == formula.name) selected = 'selected=""';
					opts += '<option value="'+formula.name+'"'+selected+'>'+formula.label+'</option>';
				}
			});
			target.html(opts);
		}
		
	},
	
	changeFieldProperties: function(self) {
		var me = this,
			cont = jQuery(self).closest('table');
		
		var formula = jQuery('select[name=fieldFormula]', cont).val();
		var grouping = jQuery('input[name=fieldGroupCheck]', cont).is(':checked:visible');
		var sortorder = jQuery('select[name=fieldGroupOrder]', cont).val();
		var summary = grouping && jQuery('input[name=fieldSummary]', cont).is(':checked:visible');
		
		jQuery('tr[name=fieldPropSummary]', cont)[grouping ? 'show' : 'hide']();
		if (!grouping) jQuery('input[name=fieldSummary]', cont).prop('checked', false);
		
		// move to the beginning
		if (self && (self.name == 'fieldGroupCheck' || self.name == 'fieldSummary')) { // crmv@104156
			me.repositionField(self);
		}

		// align the field
		var field = {
			formula: formula,
			group: !!grouping,
			sortorder: sortorder || 'ASC',
			summary: !!summary
		}
		me.alignOption(cont.closest('.selectedField'), field);
		
	},
	
	// crmv@104156
	repositionField: function(self) {
		var me = this,
			cont = jQuery(self).closest('table');
			
		if (self.name == 'fieldGroupCheck') {
			var changed = 'grouping';
		} else {
			var changed = 'summary';
		}
		
		var afterlast = false;
		var grouping = jQuery('input[name=fieldGroupCheck]', cont).is(':checked:visible');
		var summary = jQuery('input[name=fieldSummary]', cont).is(':checked:visible');
		
		// find new position
		var pos = 0,
			movedir = null,
			mypos = cont.closest('.selectedField').index(),
			parentCont = jQuery('#selectedfields'),
			allsels = parentCont.find('.selectedField');
		
		if (grouping && summary) {
			// find the first non checked
			allsels.each(function() {
				var fsumm = jQuery('input[name=fieldSummary]', this);
				if (!fsumm.is(':checked')) {
					return false;
				}
				++pos;
			});
			movedir = 'left';
		} else if (grouping && changed == 'grouping') {
			// can move left
			// find the first non checked
			allsels.each(function() {
				var fgroup = jQuery('input[name=fieldGroupCheck]', this);
				if (!fgroup.is(':checked')) {
					return false;
				}
				++pos;
			});
			movedir = 'left';
		} else if (grouping && changed == 'summary') {
			// can move right
			// find the last checked
			pos = allsels.length
			jQuery(allsels.get().reverse()).each(function() {
				var fsumm = jQuery('input[name=fieldSummary]', this);
				if (fsumm.is(':checked')) {
					return false;
				}
				--pos;
			});
			if (pos == allsels.length) {
				afterlast = true;
				--pos;
			}
			movedir = 'right';
		} else {
			// find the last checked
			pos = allsels.length
			jQuery(allsels.get().reverse()).each(function() {
				var fgroup = jQuery('input[name=fieldGroupCheck]', this);
				if (fgroup.is(':checked')) {
					return false;
				}
				--pos;
			});
			if (pos == allsels.length) {
				afterlast = true;
				--pos;
			}
			movedir = 'right';
			
		}
		
		if ((movedir == 'left' && (pos < mypos)) || (movedir == 'right' && (pos > mypos))) {
			// move it!
			var prev = parentCont.find('.selectedField:eq('+pos+')');
			var selffield = cont.closest('.selectedField');
			// clone the field to keep the position
			var cloned = selffield.clone();
			cloned.css('opacity', 0.5).insertAfter(selffield);
			// do with a nice animation
			selffield.css({
				position: 'absolute',
				left: selffield.position().left
			});
			selffield.animate({left: prev.position().left}, 400, 'swing', function() {
				selffield[afterlast ? 'insertAfter' : 'insertBefore'](prev);
				selffield.css({
					position: '',
					left: ''
				});
				cloned.remove();
				parentCont.sortable("refresh");
			});
		}
	},
	// crmv@104156e
	
	alignOption: function(option, field) {
		var me = this;
		var rawValue = jQuery('input[name=fldvalue]', option).val(),
			value = JSON.parse(rawValue) || {},
			label = option.text().replace(/\s*\[.*\]/g, '').trim();

		delete value.formula
		delete value.group;
		delete value.sortorder;
		delete value.summary;
		
		if (field.formula) {
			value.formula = field.formula;
		}
		
		var arrow = '';
		if (field.group) {
			value.group = true;
			value.sortorder = field.sortorder || 'ASC';
			
			arrow = ' '+(value.sortorder == 'DESC' ? '&uarr;' : '&darr;');
			if (field.summary) {
				value.summary = true;
			}
		}
		
		if (value.group) label += ' ['+alert_arr.LBL_GROUPBY+arrow+']';
		if (value.summary) label += ' ['+alert_arr.LBL_SUMMARY+']';
		
		// unicitÃ , ma non serve qui
		/*if (value.summary) {
			// remove all other summary fields
			option.closest('select').find('option').each(function() {
				if (rawValue != this.value) {
					var otherValue = JSON.parse(this.value);
					delete otherValue.summary;
					me.alignOption(jQuery(this), otherValue);
				}
			});
		}*/
		
		// TODO: controlla i 7 livelli

		jQuery('input[name=fldvalue]', option).val(JSON.stringify(value));
		//option.html(label);
		
		// TODO: move grouped fields to the top (or better, when I change page)
	},
	
	getModulesChain: function(container) {
		var me = this,
			module = jQuery('#primarymodule').val(),
			chain = [module];
		
		jQuery(container).find('select').each(function(select) {
			var val = jQuery(this).val();
			if (val) chain.push(val);
		});
		return chain;
	},

	fetchModulesList: function(chain, fields, fieldstype, callback) {
		var me = this;

		var cachedModules = me.getCachedModules(chain);
		var cachedFields = me.getCachedFields(chain, fieldstype);
		if (cachedFields) {
			fields = false;
		}
		
		if (cachedFields && cachedModules) {
			var cached = {
				modules: cachedModules, 
				fields: cachedFields,
			};
			if (typeof callback == 'function') callback(cached);
			return;
		}
		
		var params = {
			chain: JSON.stringify(chain),
			getfields: (fields ? 1 : 0),
			fieldstype: fieldstype,
		};
		me.ajaxCall('GetModulesList', params, null, function(data) {
			if (data && data.result) {
				if (cachedFields) {
					data.result.fields = cachedFields;
				} else {
					me.setCachedFields(chain, fieldstype, data.result.fields);
				}
				me.setCachedModules(chain, data.result.modules);
			}
			if (typeof callback == 'function') callback(data.result);
		});
	},
	
	fetchFieldsList: function(chain, fieldstype, callback) {
		var me = this;
		
		var cached = me.getCachedFields(chain, fieldstype);
		if (cached) {
			if (typeof callback == 'function') callback(cached);
			return;
		}
		
		var ws = '';
		if (fieldstype == 'stdfilter') {
			ws = 'GetStdFiltersFieldsList';
		} else if (fieldstype == 'advfilter') {
			ws = 'GetAdvFiltersFieldsList';
		} else if (fieldstype == 'total') {
			ws = 'GetTotalsFieldsList';
		} else {
			ws = 'GetFieldsList';
		}
		
		var params = {
			chain: JSON.stringify(chain),
		};
		me.ajaxCall(ws, params, null, function(data) {
			if (data && data.result) {
				me.setCachedFields(chain, fieldstype, data.result);
				if (typeof callback == 'function') callback(data.result);
			}
		});
	},
	
	getCachedModules: function(chain) {
		var me = this,
			key = chain.join('_');
			
		return me.modulesCache[key];
	},
	
	setCachedModules: function(chain, fields) {
		var me = this,
			key = chain.join('_');
			
		me.modulesCache[key] = fields;
	},
	
	getCachedFields: function(chain, type) {
		var me = this,
			key = chain.join('_');
			
		if (!type) type = 'std';
			
		return (me.fieldsCache[type] ? me.fieldsCache[type][key] : null);
	},
	
	setCachedFields: function(chain, type, fields) {
		var me = this,
			key = chain.join('_');
		
		if (!type) type = 'std';
		if (!me.fieldsCache[type]) me.fieldsCache[type] = {};
		
		me.fieldsCache[type][key] = fields;
	},
		
	preloadCache: function(data) {
		var me = this;
		
		if (data) {
			jQuery.each(data, function(idx, cache) {
				if (cache.type == 'modules') {
					me.setCachedModules(cache.chain, cache.data);
				} else if (cache.type == 'fields') {
					me.setCachedFields(cache.chain, cache.fieldstype, cache.data);
				}
			});
		}
	},
	
	changeModulesPicklist: function(select, fieldsselect, fieldstype) {
		var me = this,
			$select = jQuery(select),
			val = $select.val(),
			cont = $select.parent();

		// remove all other picklists (and fields ?)
		$select.nextAll('select').remove();
		$select.nextAll('span.chainArrow').remove();
		if (fieldsselect) {
			jQuery(fieldsselect).html('');
		}
		
		var chain = me.getModulesChain(cont);
		var showblocks = (fieldstype != 'total' && fieldstype != 'stdfilter');
		
		if (val) {
			// create a new picklist
			me.fetchModulesList(chain, true, fieldstype, function(data) {
				if (data && data.modules) {
					var select = me.createModulesPicklist(cont, fieldsselect, fieldstype);
					me.populateModulesPicklist(select, data.modules);
				}
				if (data && data.fields && fieldsselect) me.populateFieldsPicklist(fieldsselect, data.fields, showblocks);
			});
		} else {
			// restore the previous fields
			me.fetchFieldsList(chain, fieldstype, function(fields) {
				if (fields) {
					me.populateFieldsPicklist(fieldsselect, fields, showblocks);
				}
			});
		}
		
	},
	
	createModulesPicklist: function(container, fieldselect, fieldstype) {
		var me = this;
		
		var newidx = jQuery(container).find('select').length + 1;
		var arrow = '<span class="chainArrow">&gt;</span>';
		var select = arrow+'<select id="fieldsModuleChain'+newidx+'" class="detailedViewTextBox chainModule" onchange="EditReport.changeModulesPicklist(this, \''+fieldselect+'\', \''+fieldstype+'\')"></select>';
		
		container.append(select);
		
		select = jQuery(container).find('select').last();
		return select;
	},
	
	populateModulesPicklist: function(select, data) {
		var me = this,
			target = jQuery(select);
		
		var opts = '';
		jQuery.each(data, function(idx, opt) {
			var value = opt.value.replace(/"/g, '&quot;');
			opts += '<option value="'+value+'">'+opt.label+'</option>';
		});
		
		target.html(opts);
	},
	
	populateFieldsPicklist: function(select, data, showgroups) {
		var me = this,
			target = jQuery(select);
		
		if (showgroups === undefined) showgroups = true;
		
		var opts = '';
		jQuery.each(data, function(idx, group) {
			if (group && group.fields) {
				if (showgroups) opts += '<optgroup label="'+group.label+'">';
				jQuery.each(group.fields, function(idx, opt) {
					var value = opt.value.replace(/"/g, '&quot;');
					var optdata = '';
					var selected = '';
					if (opt.wstype) optdata += ' data-wstype="'+opt.wstype+'"';
					if (opt.uitype) optdata += ' data-uitype="'+opt.uitype+'"';
					if (opt.module) optdata += ' data-module="'+opt.module+'"';
					if (opt.trans_module) optdata += ' data-trans-module="'+opt.trans_module+'"';
					if (opt.single_label) optdata += ' data-singlelabel="'+opt.single_label+'"';
					if (opt.fieldname) optdata += ' data-fieldname="'+opt.fieldname+'"';
					if (opt.selected) selected = ' selected=""';
					opts += '<option value="'+value+'"'+optdata+selected+'>'+opt.label+'</option>';
				});
				if (showgroups) opts += '</optgroup>';
			}
		});
		
		target.html(opts);
	},
	
	addTotalField: function() {
		var me = this,
			src = [jQuery('#totalsMasterRow0'), jQuery('#totalsMasterRow1'), jQuery('#totalsMasterRow2')],
			target = jQuery('#totalsTable');
			
		var totidx = target.get(0).rows.length / src.length;
		
		for (var i=0; i<src.length; ++i) {
			var newrow = src[i].clone();
			newrow.removeAttr('id');
			newrow.find('input[type=checkbox]').prop('checked', false);
			newrow.find('select.summaryFields').attr('id', 'totalFields'+totidx);
			
			var chainmod = newrow.find('select.chainModule'),
				ochange = chainmod.attr('onchange');
			
			if (ochange) {
				ochange = ochange.replace('IDX', totidx);
				chainmod.attr('onchange', ochange);
			}
			
			target.append(newrow);
			newrow.show();
		}
		target.show();
	},
	
	removeTotalField: function(self) {
		var me = this;
		
		var selfrow = jQuery(self).closest('tr');
		selfrow.prev().prev().remove();
		selfrow.prev().remove();
		selfrow.remove();
		
		// hide if empty
		if (jQuery('#totalsTable').find('tr.totalsRow0').length <= 1) {
			jQuery('#totalsTable').hide();
		}
	},
	
	removeAllTotalFields: function() {
		var me = this;
		
		jQuery('#totalsTable').find('tr').each(function() {
			if (!this.id.match('Master') && this.className.match('totalsRow')) {
				jQuery(this).remove();
			}
		});
	},
	
	changeFormulaTotal: function(self) {
		var me = this;
		var cont = jQuery(self).closest('tr');
		
		if (self.checked) {
			cont.find('input.summaryTotal').prop('disabled', false);
		} else {
			// check all other formulas
			var allformulas = cont.find('input[name^=aggregator]');
			if (!allformulas.is(':checked')) {
				cont.find('input.summaryTotal').prop('checked', false).prop('disabled', true);
			}
		}
	},
	
	changeSummaryTotal: function(self) {
		var me = this;
		
		if (self.checked) {
			jQuery('input.summaryTotal').not(self).prop('checked', false);
		}
	},
	
	changeSharing: function() {
		var me = this,
			sharing = jQuery('#sharingtype').val();
			
		if (sharing == 'Shared') {
			// show list of users
			me.changeMemberType();
			jQuery('#shareMembersTable').show();
		} else {
			// hide
			jQuery('#shareMembersTable').hide();
			jQuery('#sharedmembers').html('');
		}
	},
	
	changeMemberType: function() {
		var me = this,
			type = jQuery('#shareMemberType').val(),
			opts = "";
			
		jQuery('#availmembers').html('');
		
		if (type == 'groups' && window.reportGroups) {
			jQuery.each(reportGroups, function(groupid, grp) {
				opts += '<option value="'+grp.value+'">'+grp.label+'</option>';
			});
		} else if (type == 'users' && window.reportUsers) {
			jQuery.each(reportUsers, function(userid, usr) {
				opts += '<option value="'+usr.value+'">'+usr.label+'</option>';
			});
		}
		
		if (opts) {
			jQuery('#availmembers').html(opts);
		}
	},
	
	addMembers: function() {
		var me = this,
			src = jQuery('#availmembers'),
			target = jQuery('#sharedmembers');
		
		src.find('option:selected').each(function() {
			// add the field
			if (!me.isMemberChosen(this.value)) {
				jQuery(this).prop('selected', false);
				jQuery(this).clone().appendTo(target);
			}
		});
	},
	
	isMemberChosen: function(value) {
		var me = this,
			exists = false,
			target = jQuery('#sharedmembers');
		
		target.find('option').each(function() {
			if (value == this.value) {
				exists = true;
				return false;
			}
		});
		
		return exists;
	},
	
	removeMembers: function() {
		var me = this,
			target = jQuery('#sharedmembers');
			
		target.find('option:selected').remove();
	},
	
	changeChartCheckbox: function() {
		var me = this,
			checked = jQuery('#chartCheckbox').is(':checked');
			
		if (checked) {
			jQuery('#chartFields').show();
		} else {
			jQuery('#chartFields').hide();
		}
	},
	
	generateChartPreview: function(event) {
		var chtype = jQuery('#chart_type').val();
		if (chtype == '') return;
		
		if (event.target.name == 'button' || event.target.name == 'chartname') return;
		
		if (generatePreview.ajaxrun == true) return;
		
		var formdata = jQuery('#chartEditor :input').serialize();
		return generatePreview(event, formdata);
	},
	
	// crmv@139057
	changeScheduledReport: function() {
		var scheduled = jQuery('#isReportScheduled').is(':checked');
		jQuery('#tableScheduledConfig')[scheduled ? 'show' : 'hide']();
	},
	
	setScheduleOptions: function() {
		var me = this;
		var stid = jQuery('#scheduledType').val();
		
		switch( stid ) {
			case "0": // nothing choosen
			case "1": // hourly
				jQuery('#scheduledMonthSpan').hide();
				jQuery('#scheduledDOMSpan').hide();
				jQuery('#scheduledDOWSpan').hide();
				jQuery('#scheduledTimeSpan').hide();
				break;
			case "2": // daily
				jQuery('#scheduledMonthSpan').hide();
				jQuery('#scheduledDOMSpan').hide();
				jQuery('#scheduledDOWSpan').hide();
				jQuery('#scheduledTimeSpan').show();
				break;
			case "3": // weekly
			case "4": // bi-weekly
				jQuery('#scheduledMonthSpan').hide();
				jQuery('#scheduledDOMSpan').hide();
				jQuery('#scheduledDOWSpan').show();
				jQuery('#scheduledTimeSpan').show();
				break;
			case "5": // monthly
				jQuery('#scheduledMonthSpan').hide();
				jQuery('#scheduledDOMSpan').show();
				jQuery('#scheduledDOWSpan').hide();
				jQuery('#scheduledTimeSpan').show();
				break;
			case "6": // annually
				jQuery('#scheduledMonthSpan').show();
				jQuery('#scheduledDOMSpan').show();
				jQuery('#scheduledDOWSpan').hide();
				jQuery('#scheduledTimeSpan').show();
				break;
		}
	},
	
	generateRecipientOption: function() {
		var me = this;
			type = jQuery('#recipient_type').val(),
			opts = "";
			
		jQuery('#availableRecipients').html('');
		
		if (window.reportSchedRecipients && reportSchedRecipients[type]) {
			jQuery.each(reportSchedRecipients[type], function(entryid, entry) {
				opts += '<option value="'+entry[0]+'">'+entry[1]+'</option>';
			});
		}
		
		if (opts) {
			jQuery('#availableRecipients').html(opts);
		}
	},
	
	isSchedRecipientChosen: function(value) {
		var me = this,
			exists = false,
			target = jQuery('#selectedRecipients');
		
		target.find('option').each(function() {
			if (value == this.value) {
				exists = true;
				return false;
			}
		});
		
		return exists;
	},
	
	addScheduledRecipient: function() {
		var me = this,
			src = jQuery('#availableRecipients'),
			target = jQuery('#selectedRecipients');
		
		src.find('option:selected').each(function() {
			// add the field
			if (!me.isSchedRecipientChosen(this.value)) {
				jQuery(this).prop('selected', false);
				jQuery(this).clone().appendTo(target);
			}
		});

	},

	delScheduledRecipient: function() {
		var me = this,
			target = jQuery('#selectedRecipients');
			
		target.find('option:selected').remove();
	},
	// crmv@139057e
	
}