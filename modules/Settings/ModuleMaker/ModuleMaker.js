/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@64542 crmv@69398 crmv@102879 crmv@105127 */

var ModuleMaker = {
	
	busy: false,
	
	// I know this is a bad idea, but the Unicode support in javascript regex sucks!
	allowedSpecialChars: 'àèìòùÀÈÌÒÙáéíóúýÁÉÍÓÚÝâêîôûÂÊÎÔÛãñõÃÑÕäëïöüÿÄËÏÖÜŸçÇßØøÅåÆæœ',
	
	
	showBusy: function() {
		var me = this;
		me.busy = true;
		jQuery('#mmaker_busy').show();
	},
	
	hideBusy: function() {
		var me = this;
		me.busy = false;
		jQuery('#mmaker_busy').hide();
	},
	
	createNew: function() {
		if (this.busy) return;
		this.showBusy();
		location.href = 'index.php?module=Settings&action=ModuleMaker&parentTab=Settings&mode=create&module_maker_step=1';
	},
	
	importNew: function() {
		if (this.busy) return;
		this.showBusy();
		location.href = 'index.php?module=Settings&action=ModuleMaker&parentTab=Settings&mode=import&module_maker_step=1';
	},
	
	editModule: function(modid) {
		if (this.busy) return;
		this.showBusy();
		location.href = 'index.php?module=Settings&action=ModuleMaker&parentTab=Settings&mode=edit&module_maker_step=1&moduleid='+modid;
	},
	
	deleteModule: function(modid) {
		var me = this;
		if (me.busy) return;
		if (confirm(alert_arr.SURE_TO_DELETE)) {
			me.ajaxCall('delete_module', {moduleid:modid}, function(data) {
				me.gotoList();
			});
		}
	},
	
	installModule: function(modid) {
		var me = this;
		if (me.busy) return;

		me.showPopupMessage('mmaker_message_installing');
		me.ajaxCall('install_module', {moduleid:modid}, function(data) {
			me.gotoList();
		}, {
			jsonData: true,
			callbackOnError: true,
			hidePopupMessage: true,
		});
	},
	
	uninstallModule: function(modid) {
		var me = this;
		if (me.busy) return;
		
		if (confirm(alert_arr.LBL_SURE_TO_UNINSTALL_MODULE)) {
			me.showPopupMessage('mmaker_message_uninstalling');
			me.ajaxCall('uninstall_module', {moduleid:modid}, function(data) {
				me.gotoList();
			}, {
				jsonData: true,
				callbackOnError: true,
				hidePopupMessage: true,
			});
		}
	},
	
	hidePopupMessage: function() {
		ModuleMakerFields.hideFloatingDiv('mmaker_div_message');
	},
	
	showPopupMessage: function(msg) {
		jQuery('.mmaker_message_text').hide();
		jQuery('#'+msg).show();
		ModuleMakerFields.showFloatingDiv('mmaker_div_message');
	},
	
	ajaxCall: function(action, params, callback, options) {
		var me = this;
		
		// return if busy
		if (me.busy) return;
		
		options = options || {
			includeForm: false,
			jsonData: true,
			callbackOnError: false,
			hidePopupMessage: true,
		};
		params = params || {};
		var url = "index.php?module=Settings&action=SettingsAjax&file=ModuleMaker&ajax=1&subaction="+action;
		
		if (options.includeForm) {
			var form = jQuery('#module_maker_form').serialize();
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
	
	exportModule: function(modid, callback) {
		var me = this;
		
		me.ajaxCall('export', {moduleid: modid}, function(data) {
			if (data && data.url) {
				location.href = data.url;
			}
		});
	},
	
	gotoList: function() {
		var me = this;
		if (me.busy) return;
		me.showBusy();
		location.href = 'index.php?module=Settings&action=ModuleMaker&parentTab=Settings';
	},
	
	openLogsPopup: function(modid) {
		var me = this;
		
		jQuery('#mmaker_logs_moduleid').val(modid);
		jQuery('#mmaker_select_log').val('');
		jQuery('#mmaker_log_text').val('');
		ModuleMakerFields.showFloatingDiv('mmaker_div_logs');
	},
	
	selectLog: function(callback) {
		var me = this;
		var modid = jQuery('#mmaker_logs_moduleid').val();
		var logname = jQuery('#mmaker_select_log').val();
		
		if (!logname) return;
		
		me.ajaxCall('getlog', {moduleid: modid, logname: logname}, function(data) {
			if (data && data.log) {
				jQuery('#mmaker_log_text').val(data.log);
			}
		});

	},
	
	gotoStep: function(step) {
		// set the inputs
		jQuery('#module_maker_step').val(step);
		
		// get the form and submit
		var form = document.getElementById('module_maker_form');
		if (form) form.submit();
		//location.href = 'index.php?module=Settings&action=ModuleMaker&parentTab=Settings&mode=create&step='+step+'&prevstep='+prevstep;
	},
	
	getCurrentStep: function() {
		var step = parseInt(jQuery('#module_maker_prev_step').val());
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
		
		if (!me.validateStep(step)) return false;
		
		me.hideError();
		me.gotoStep(pstep);
	},
	
	hideNavigationButtons: function() {
		jQuery('#mmaker_div_navigation').hide();
	},
	
	showNavigationButtons: function() {
		jQuery('#mmaker_div_navigation').show();
	},
	
	validateStep: function(step) {
		var me = this,
			fname = 'validateStep'+step;
		
		if (typeof me[fname] == 'function') {
			return me[fname]();
		}
		return true;
	},
	
	saveModule: function() {
		var me = this;
		
		jQuery('#module_maker_savedata').val('1');
		me.gotoNextStep();
	},
	
	displayError: function(text) {
		var me = this;
		
		var div = jQuery('#mmaker_error_box');
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
		jQuery('#mmaker_error_box').hide().text('');
	},
	
	step1_getFieldLabel: function(fname) {
		var obj = jQuery('#'+fname).closest('tr').children('td').first().find('span').text() || fname;
		return obj
	},
	
	step1_onModuleLabelKey: function(self) {
		var me = this,
			value = self.value;
		
		var slabel = me.step1_calculateSingleModuleLabel(value);
		jQuery('#mmaker_single_modlabel').val(slabel);
		
		var modname = me.step1_calculateModuleName(value);
		jQuery('#mmaker_modname').val(modname);
		
		var mainfield = alert_arr.LBL_NAME_S.replace('%s', slabel);
		jQuery('#mmaker_mainfield').val(mainfield);
	},
	
	step1_calculateSingleModuleLabel: function(value) {
		var me = this;
		// :( there's no easy way to transform plural to singular, and in several languages
		
		// for now, just remove bad characters and keep latin chars
		var re = new RegExp('[^A-Za-z0-9_ '+me.allowedSpecialChars+'-]', "g");
		value = value.replace(re, '');
		
		return value;
	},
	
	step1_calculateModuleName: function(value) {
		var me = this;
		
		if (!value) return '';
		
		// the module name consists only of alphabetic chars, pure ASCII, no spaces or any other special symbols, camel case if possible
		var modname = value.replace(/_/g, ' ').replace(/[^a-zA-Z ]/g, '').toLowerCase();
		modname = modname.replace(/ +(.)/g, function(match, group) {
			return group.toUpperCase();
		});
		
		modname = modname.charAt(0).toUpperCase() + modname.slice(1);
		modname = modname.substr(0, 20);
		
		return modname;
	},
	
	validateStep1: function() {
		var me = this;
		
		var mlabel = jQuery('#mmaker_modlabel').val();
		var mslabel = jQuery('#mmaker_single_modlabel').val();
		var modname = jQuery('#mmaker_modname').val();
		var mainfield = jQuery('#mmaker_mainfield').val();

		// check emptyness
		if (!mlabel) return me.displayError(alert_arr.CANNOT_BE_EMPTY.replace('%s', me.step1_getFieldLabel('mmaker_modlabel')));
		if (!mslabel) return me.displayError(alert_arr.CANNOT_BE_EMPTY.replace('%s', me.step1_getFieldLabel('mmaker_single_modlabel')));
		if (!modname) return me.displayError(alert_arr.CANNOT_BE_EMPTY.replace('%s', me.step1_getFieldLabel('mmaker_modname')));
		if (!mainfield) return me.displayError(alert_arr.CANNOT_BE_EMPTY.replace('%s', me.step1_getFieldLabel('mmaker_mainfield')));

		// check length
		if (mlabel.length > 40) return me.displayError(alert_arr.LBL_TOO_LONG.replace('%s', me.step1_getFieldLabel('mmaker_modlabel')));
		if (mslabel.length > 40) return me.displayError(alert_arr.LBL_TOO_LONG.replace('%s', me.step1_getFieldLabel('mmaker_single_modlabel')));
		if (modname.length > 20) return me.displayError(alert_arr.LBL_TOO_LONG.replace('%s', me.step1_getFieldLabel('mmaker_modname')));
		if (mainfield.length > 40) return me.displayError(alert_arr.LBL_TOO_LONG.replace('%s', me.step1_getFieldLabel('mmaker_mainfield')));
		
		// check bad chars
		var re = new RegExp('[^A-Za-z0-9_ '+me.allowedSpecialChars+'-]');
		var re2 = new RegExp('[^A-Za-z0-9]');
		if (re.test(mlabel)) return me.displayError(alert_arr.NO_SPECIAL_CHARS_DOCS);
		if (re.test(mslabel)) return me.displayError(alert_arr.NO_SPECIAL_CHARS_DOCS);
		if (re2.test(modname)) return me.displayError(alert_arr.NO_SPECIAL_CHARS_DOCS);
		if (re.test(mainfield)) return me.displayError(alert_arr.NO_SPECIAL_CHARS_DOCS);
		
		return true;
	},
	
	validateStep2: function() {
		var me = this;
		
		var uitype4Count = 0;
		var uitype10Mods = [];

		var block_count = jQuery('.blockheaderrow').length;
		for (var i=0; i<block_count; ++i) {
			var field_count = parseInt(jQuery('#fieldcount_'+i).val()) || 0;
			for (var j=0; j<field_count; ++j) {
				var field = {
					fieldname: jQuery('#field_'+i+'_'+j+'_fieldname').val(),
					uitype: jQuery('#field_'+i+'_'+j+'_uitype').val(),
				}
				// check for a single uitype4 field
				if (field.uitype == 4) {
					if (++uitype4Count > 1) {
						alert(alert_arr.LBL_TOO_MANY_UITYPE4);
						return false;
					}
				
				// check for multiple uitype10 related mods
				} else if (field.uitype == 10) {
					var relmods = jQuery('#field_'+i+'_'+j+'_relatedmods').val().split(',') || [];
					for (var k=0; k<relmods.length; ++k) {
						if (uitype10Mods.indexOf(relmods[k]) >= 0) {
							// already present
							alert(alert_arr.LBL_SAMEMODULERELATED.replace('%s', relmods[k]));
							return false;
						} else {
							uitype10Mods.push(relmods[k]);
						}
					}
				}
			}
			
		}
		return true;
	},
	
	validateStep3: function() {
		var me = this;
		
		var maxFields = parseInt(jQuery('#filter_tot_columns').val()) || 9;
		var filternos = [
			parseInt(jQuery('#filter_no').val()),
			parseInt(jQuery('#relfilter_no').val()),
		];
		
		// check the fields for uniqueness and emptyness
		for (var j=0; j<filternos.length; ++j) {
			var cols = [];
			for (var i=0; i<maxFields; ++i) {
				var col = jQuery('#filtercol_'+filternos[j]+'_'+i).val();
				if (col) {
					if (cols.indexOf(col) >= 0) {
						alert(alert_arr.LBL_FILTER_FIELD_MORE_THAN_ONCE);
						return false;
					} else {
						cols.push(col);
					}
				}
			}
			if (cols.length == 0) {
				alert(alert_arr.LBL_SELECT_AT_LEAST_ONE_FIELD);
				return false;
			}
		}
		
		return true;
	},
	
	validateStep4: function() {
		var me = this;
		// nothing to validate here
		return true;
	},
	
	validateStep5: function() {
		var me = this;
		
		return true;
	},
	
	validateStep6: function() {
		var me = this;
		
		return true;
	},
	
};

// handles the fields/blocks
var ModuleMakerFields = {
	
	busy: false,
	activeFloatingDiv: null,
	
	newFieldSelected: null,
	
	newFieldProperties: ['label', 'length', 'decimals', 'picklistvalues', 'relatedmods', 'autoprefix', 'onclick', 'code', 'users', 'columns', 'newline'],	//crmv@98570 crmv@101683 crmv@102879 crmv@106857
	newFieldsDefaults: null,	// this is populted from the tpl
	newTableFieldsDefaults: null,
	
	centerFloatingDiv: function(div) {
		var me = this;
		var divobj = me.getJQueryObject(div);
		
		divobj.css("top", Math.max(0, ((jQuery(window).height() - divobj.outerHeight()) / 2)) + "px");
		divobj.css("left", Math.max(0, ((jQuery(window).width() - divobj.outerWidth()) / 2)) + "px");
	},
	
	getJQueryObject: function(ref) {
		if (typeof ref == 'string') {
			ref = '#'+ref;
		}
		return jQuery(ref);
	},
	
	showFloatingDiv: function(div, center) {
		var me = this;
		
		if (me.activeFloatingDiv) return;

		if (typeof center == 'undefined') center = true;
		
		var divobj = me.getJQueryObject(div);
		
		divobj.show();
		if (center) me.centerFloatingDiv(div);
		
		divobj.css({
			'z-index': findZMax()+1,
		});
	},
	
	hideFloatingDiv: function(div) {
		var me = this;
		var divobj = me.getJQueryObject(div);		
		
		divobj.hide();
		me.activeFloatingDiv = null;
	},
	
	isBusy: function() {
		return this.busy;
	},
	
	showBusy: function() {
		var me = this;
		me.busy = true;
		jQuery('#mmaker_busy').show();
	},
	
	hideBusy: function() {
		var me = this;
		me.busy = false;
		jQuery('#mmaker_busy').hide();
	},
	
	ajaxCall: function(action, params, callback, options) {
		var me = this;
		
		// return if busy
		if (me.isBusy()) return;
		
		options = options || {};
		params = params || {};
		var url = "index.php?module=Settings&action=SettingsAjax&file=ModuleMaker&ajax=1&subaction="+action;
		if (options.processMakerMode) url += '&processMakerMode=yes';	//crmv@96450
		
		if (options.includeForm) {
			var form = jQuery('#module_maker_form').serialize();
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
				jQuery('#mmaker_div_allblocks').hide().html('');
				jQuery('#mmaker_div_allblocks').html(data).show();
				if (typeof callback == 'function') callback();
			},
			error: function() {
				if (typeof callback == 'function') callback();
			}
		});
		
	},
	
	changePanel: function(panelno, self) {
		var me = this;
		
		panelno = parseInt(panelno);
		
		// change inputs
		jQuery('#mmaker_currentpanelno').val(panelno);
		
		// change blocks
		jQuery('div.layoutBlock').each(function(idx, el) {
			var blockno = parseInt(el.id.replace('block_', ''));
			var blockpanel = parseInt(jQuery('#block_'+blockno+'_panelno').val());
			
			if (blockpanel == panelno) {
				// show it!
				jQuery(el).show();
			} else {
				// hide it!
				jQuery(el).hide();
			}
		});
		
		// change tabs
		var cell = jQuery(self).closest('td');
		jQuery('#LayoutEditTabs .dvtSelectedCell').removeClass('dvtSelectedCell').addClass('dvtUnSelectedCell');

		cell.removeClass('dvtUnSelectedCell').addClass('dvtSelectedCell');
		
	},
	
	openAddPanelPopup: function() {
		var me = this;
		
		// prepare values
		jQuery('#mmaker_addpanelname').val('');
		
		me.showFloatingDiv('mmaker_div_addpanel');
	},
	
	savePanelsOrder: function() {
		var me = this,
			list = [];
			
		jQuery('#LayoutEditTabs input[name$=_panelno]').each(function(idx, el) {
			list.push(parseInt(jQuery(el).val()));
		});
		
		me.ajaxCall('reorderpanels', {
			panelsorder: JSON.stringify(list),
		}, null, {
			includeForm: true,
		});
	},
	
	openRemovePanelPopup: function(panelno) {
		var me = this;

		jQuery('#delpanelno').val(panelno);
		
		jQuery('#delpanellist').val('');
		// show all options
		jQuery('#delpanellist option').show();
		// hide the one i want to remove
		jQuery('#delpanellist option[value='+panelno+']').hide();
		
		me.showFloatingDiv('mmaker_div_delpanel');
		
		// select the first visible
		jQuery('#delpanellist option:not(:hidden):eq(0)').prop('selected', true);
	},
	
	delPanel: function() {
		var me = this;
		
		var delpanelno = jQuery('#delpanelno').val();
		var movepanelno = jQuery('#delpanellist').val();
		if (movepanelno === undefined || movepanelno === null || movepanelno === '') {
			return false;
		}
		
		me.ajaxCall('delpanel', {
			delpanelno: delpanelno,
			moveblocksno: parseInt(movepanelno),
		}, null, {
			includeForm: true,
		});
		
	},
	
	openMoveBlockPanelPopup: function(blockno) {
		var me = this,
			currpanel = jQuery('#mmaker_currentpanelno').val();
		
		jQuery('#moveblockno').val(blockno);
		
		jQuery('#moveblockpanellist').val('');
		// show all options
		jQuery('#moveblockpanellist option').show();
		// hide the one i want to remove
		jQuery('#moveblockpanellist option[value='+currpanel+']').hide();
		
		me.showFloatingDiv('mmaker_div_moveblockpanel');
		
		// select the first visible
		jQuery('#moveblockpanellist option:not(:hidden):eq(0)').prop('selected', true);
		
	},
	
	moveBlockToPanel: function() {
		var me = this,
			blockno = jQuery('#moveblockno').val(),
			panelno = jQuery('#moveblockpanellist').val();
			
		me.ajaxCall('moveblockpanel', {
			moveblockno: blockno,
			movepanelno: panelno,
		}, null, {
			includeForm: true,
		});
	},
	
	getBlocksForPanel: function(panelno) {
		var me = this,
			blocks = [];
		
		jQuery('div.layoutBlock').each(function(idx, el) {
			if (jQuery(el).data('panelno') == panelno) {
				var blockno = parseInt(el.id.replace('block_', ''));
				blocks.push(blockno);
			}
		});
		
		return blocks;
	},
	
	openAddBlockPopup: function() {
		var me = this,
			currpanel = jQuery('#mmaker_currentpanelno').val(),
			blocks = me.getBlocksForPanel(currpanel);
		
		// prepare values
		jQuery('#mmaker_addblockname').val('');
		
		var first = null;
		jQuery('#addafterblock option').each(function(idx, el) {
			var bno = parseInt(el.value);
			if (blocks.indexOf(bno) >= 0) {
				jQuery(el).show();
				if (!first) first = el;
			} else {
				jQuery(el).hide();
			}
		});
		
		if (first) {
			jQuery(first).prop('selected', true);
		} else {
			jQuery('#addafterblock').val('');
		}
			
		
		me.showFloatingDiv('mmaker_div_addblock');
	},
	
	openAddFieldPopup: function(blockno) {
		var me = this;
		
		// unselect the previous field
		if (me.newFieldSelected) jQuery(me.newFieldSelected).removeClass('newFieldMnuSelected');
		me.newFieldSelected = null;
		
		// hide all the properties
		jQuery('#mmaker_newfield_props').find('.newfieldprop').hide();
		
		// set the block number
		jQuery('#mmaker_newfield_blockno').val(blockno);
		
		// show div
		me.showFloatingDiv('mmaker_div_addfield');
	},
	
	openAddTableFieldPopup: function(blockno, newfieldno, editno) {
		var me = this;
		
		// set the block number
		jQuery('#mmaker_newtablefield_blockno').val(blockno);
		jQuery('#mmaker_newtablefield_editfieldno').val(editno || '');
		
		// show div
		me.showFloatingDiv('mmaker_div_addtablefield');
		
		// fix the position and size
		function fixPosition() {
			
			var top = jQuery('#vte_menu').height() - 1;
			jQuery('#mmaker_div_addtablefield').css({
				'top' : top + 'px',
				'height' : (jQuery(document).height() - top) + 'px',
			});
			jQuery('#selectedcolumns').css({
				'height' : (jQuery(document).height() - top - 400) + 'px',
			});
		}
		
		fixPosition();
		// and do it also on resizing
		jQuery(window).resize(fixPosition);
		
		TableFieldConfig.removeAllBoxes();
		
		var cont = jQuery('#selectedcolumns');
		
		// initialize the sortable
		cont.sortable({
			axis: 'x',
			containment: 'parent',
			distance: 10,
			opacity: 0.8,
		});
	},
	
	openEditTableFieldPopup: function(blockno, fieldno) {
		var me = this,
			fieldinfo = {};
			
		jQuery('input[name^=field_'+blockno+'_'+fieldno+'_]').each(function(idx, el) {
			var pname = el.name.replace(/field_[0-9]+_[0-9]+_/, '');
			fieldinfo[pname] = el.value;
		});
		
		if (fieldinfo.columns) fieldinfo.columns = JSON.parse(fieldinfo.columns);
		
		me.openAddTableFieldPopup(blockno, null, fieldno);

		TableFieldConfig.setValues(fieldinfo);
	
	},
	
	openAddRelatedField: function(blockno) {
		var me = this,
			fieldno = parseInt(jQuery('#newrelfieldno').val());
		
		me.openAddFieldPopup(blockno);
		var link = jQuery('#newfield_'+fieldno),
			linkEl = link.get(0);
		
		if (linkEl && linkEl.onclick) {
			// click the item
			linkEl.onclick();
			
			// scroll the div
			var div = link.closest('div'),
				divEl = div.get(0);
			if (divEl) {
				var divTop = div.position().top;
					trTop = link.closest('tr').position().top;
				div.scrollTop(trTop-divTop-20);
			}
		}
	},
	
	openMoveFieldsPopup: function(blockno) {
		var me = this;
		
		// set the block number
		jQuery('#mmaker_movefields_blockno').val(blockno);
		
		// hide both divs
		jQuery('#mmaker_div_nomovefields').hide();
		jQuery('#mmaker_div_selmovefields').hide();
		
		var fieldCount = 0;
		
		// clean the values
		jQuery('#movefields_select').val('');
		var opts = jQuery('#movefields_select').find('option');
		opts.each(function(index, opt) {
			if (opt && opt.value) {
				var pieces = opt.value.split('_'),
					fblock = pieces[1];
				if (fblock == blockno) {
					// hide the field
					jQuery(opt).hide();
				} else {
					// show the field
					jQuery(opt).show();
					++fieldCount;
				}
			}
		});
		var ogroups = jQuery('#movefields_select').find('optgroup');
		ogroups.each(function(index, opt) {
			var name = jQuery(opt).attr('name');
			if (name) {
				var bid = name.replace('movefield_group_', '');
				if (bid == blockno) {
					jQuery(opt).hide();
				} else {
					jQuery(opt).show();
				}
			}
		});
		
		// show the select if there are fields
		if (fieldCount > 0) {
			jQuery('#mmaker_div_selmovefields').show();
		} else {
			jQuery('#mmaker_div_nomovefields').show();
		}
	
		// show div
		me.showFloatingDiv('mmaker_div_movefields');
	},
	
	openEditFieldPopup: function(blockno, fieldno, uitype) {
		var me = this;
		
		// if it's a table field, open the special form
		if (uitype == 220) {
			return me.openEditTableFieldPopup(blockno, fieldno);
		}
		
		// set the block and field number
		jQuery('#mmaker_editfield_blockno').val(blockno);
		jQuery('#mmaker_editfield_fieldno').val(fieldno);
		
		// now check the values
		var mand = parseInt(jQuery('#field_'+blockno+'_'+fieldno+'_mandatory').val());
		var massed = parseInt(jQuery('#field_'+blockno+'_'+fieldno+'_masseditable').val());
		var uitype = parseInt(jQuery('#field_'+blockno+'_'+fieldno+'_uitype').val());
		
		jQuery('#fieldprop_mandatory').prop('checked', mand);
		jQuery('#fieldprop_masseditable').prop('checked', massed);
		
		//crmv@96450 crmv@98570
		jQuery('#fieldprop_readonly').val(jQuery('#field_'+blockno+'_'+fieldno+'_readonly').val());

		jQuery('.fieldprop_picklistvalues').hide();
		if (jQuery('#field_'+blockno+'_'+fieldno+'_picklistvalues').length > 0) {
			jQuery('.fieldprop_picklistvalues').show();
			jQuery('#fieldprop_picklistvalues').val(jQuery('#field_'+blockno+'_'+fieldno+'_picklistvalues').val());
		}
		
		jQuery('.fieldprop_default').hide();
		if (uitype == 213) {
			jQuery('.fieldprop_onclick').show();
			jQuery('.fieldprop_code').show();
			jQuery('#fieldprop_onclick').val(jQuery('#field_'+blockno+'_'+fieldno+'_onclick').val());
			jQuery('#fieldprop_code').html(jQuery('#field_'+blockno+'_'+fieldno+'_code').val().replace('\"','"'));
		} else {
			jQuery('.fieldprop_onclick').hide();
			jQuery('.fieldprop_code').hide();
			if (jQuery('#defaultValueContainer').length > 0) {
				jQuery('.fieldprop_default').show();
				var fieldinfo = {};
				jQuery("[id^='field_"+blockno+"_"+fieldno+"_']").each(function(k,v){	//crmv@110058
					fieldinfo[v.name.replace("field_"+blockno+"_"+fieldno+"_",'')] = v.value;
				});
				ProcessHelperScript.loadPopulateField(fieldinfo);
			}
		}
		//crmv@96450e crmv@98570e
		
		//crmv@101683
		jQuery('.fieldprop_users').hide();
		if (jQuery('#field_'+blockno+'_'+fieldno+'_users').length > 0) {
			jQuery('#fieldprop_users option').prop('selected',false);
			var users = jQuery('#field_'+blockno+'_'+fieldno+'_users').val().split(',');
			jQuery.each(users,function(k,v){
				jQuery('#fieldprop_users option[value="'+v+'"]').prop('selected',true);
			});
			jQuery('.fieldprop_users').show();
		} 
		//crmv@101683e
		
		//crmv@160837
		if (jQuery('#fieldprop_fieldlabel').length > 0) {
			jQuery('#fieldprop_fieldlabel').val(jQuery('#field_'+blockno+'_'+fieldno+'_fieldlabel').val());
		}
		//crmv@160837e
		
		// show div
		me.showFloatingDiv('mmaker_div_editfield');
		
	},
	
	//crmv@160837
	openEditBlockPopup: function(blockno) {
		var me = this;
		
		jQuery('#mmaker_editblock_blockno').val(blockno);
		if (jQuery('#editlabel').length > 0) jQuery('#editlabel').val(jQuery('#block_'+blockno+'_label').val());
		//if (jQuery('#editblocklabel').length > 0) jQuery('#editblocklabel').val(jQuery('#block_'+blockno+'_blocklabel').val());
		
		// show div
		me.showFloatingDiv('mmaker_div_editblock');
	},
	
	editBlock: function(blockno) {
		var me = this;
		
		var blockno = jQuery('#mmaker_editblock_blockno').val();
		
		var blockname = jQuery('#editlabel').val();
		if (!blockname) {
			console.log('Block name is empty');
			return;
		}
		
		me.ajaxCall('editblock', {
			editblockno: blockno,
			editlabel: jQuery('#editlabel').val(),
			//editblocklabel: jQuery('#editblocklabel').val(),
		}, function() {
			me.hideFloatingDiv('mmaker_div_editblock');
		}, {
			includeForm: true,
		});
	},
	//crmv@160837e
	
	addPanel: function(panelname) {
		var me = this;
		
		var panelname = jQuery('#addpanelname').val();
		if (!panelname) {
			console.log('Panel name is empty');
			return;
		}
		
		me.ajaxCall('addpanel', {
			addpanelname: panelname,
		}, function() {
			me.hideFloatingDiv('mmaker_div_addpanel');
		}, {
			includeForm: true,
		});
	},
	
	addBlock: function(blockname, after) {
		var me = this;
		
		var blockname = jQuery('#addblockname').val();
		if (!blockname) {
			console.log('Block name is empty');
			return;
		}
		
		me.ajaxCall('addblock', {
			addblockname: blockname,
			addafterblock: jQuery('#addafterblock').val(),
		}, function() {
			me.hideFloatingDiv('mmaker_div_addblock');
		}, {
			includeForm: true,
		});
	},
	
	delBlock: function(blockno) {
		var me = this;
		
		me.ajaxCall('delblock', {
			delblockno: blockno,
		}, null, {
			includeForm: true,
		});
	},
	
	moveBlock: function(blockno, direction) {
		var me = this;
		
		me.ajaxCall('moveblock', {
			moveblockno: blockno,
			direction: direction,
		}, null, {
			includeForm: true,
		});
	},
	
	addField: function() {
		var me = this;
		
		var props = me.checkNewFieldProps();
		if (!props) return;
		
		var fieldno = me.newFieldSelected.attr('id').replace('newfield_', '');
		var blockno = jQuery('#mmaker_newfield_blockno').val();
		
		me.ajaxCall('addfield', {
			blockno: blockno,
			addfieldno: fieldno,
			properties: JSON.stringify(props),
		}, null, {
			includeForm: true,
		});
	},
	
	moveField: function(blockno, fieldno, direction) {
		var me = this;
		
		me.ajaxCall('movefield', {
			blockno: blockno,
			movefieldno: fieldno,
			direction: direction,
		}, null, {
			includeForm: true,
		});
	},
	
	delField: function(blockno, fieldno) {
		var me = this;
		
		me.ajaxCall('delfield', {
			blockno: blockno,
			delfieldno: fieldno,
		}, null, {
			includeForm: true,
		});
	},
	
	checkNewFieldProps: function(baseid) {
		var me = this,
			props = {};
		
		if (!baseid) baseid = 'newfield';
		
		if (!me.newFieldSelected) {
			alert(alert_arr.FIELD_TYPE_NOT_SELECTED);
			return false;
		}
	
		for (var i=0; i<me.newFieldProperties.length; ++i) {
			var row = jQuery('#'+baseid+'prop_'+me.newFieldProperties[i]);
			if (row.length > 0 && row.is(':visible')) {
				var cont1 = row.find('input'),
					cont2 = row.find('textarea'),
					cont3 = row.find('select');
				var cont = null;
				if (cont1.length > 0) cont = cont1.val();
				else if (cont2.length > 0) cont = cont2.val();
				else if (cont3.length > 0) cont = cont3.val();
				if (!cont) {
					alert(alert_arr.LBL_FILL_ALL_FIELDS);
					return false;
				} else {
					props[me.newFieldProperties[i]] = cont;
				}
			}
		}
		
		// crmv@70622
		// now check some numeric values
		var fieldno = me.newFieldSelected.attr('id').replace(baseid+'_', '');

		if (fieldno == 2 && 'length' in props) { // TODO: don't use the raw number here
			if (parseInt(props.length) > 9) {
				var label = jQuery('#'+baseid+'prop_length').find('.dataLabel').text().replace(':', '').trim();
				alert(label + alert_arr.SHOULDBE_LESS_EQUAL + '9');
				return false;
			}
		}
		// crmv@70622e
		
		//crmv@106857
		if (typeof(fieldcont) != 'undefined') {
			var fldname = fieldcont.find('input[name=fldname]').val();
			props.fldname = fldname;
		}
		//crmv@106857e
		
		// TODO: check numeric values
		
		return props;
	},
	
	checkNewTableFieldProps: function(baseid, index) {
		var me = this,
			props = {};
		
		if (!baseid) baseid = 'newtablefield';
		
		if (baseid == 'newtablefield') {
			var fieldcont = jQuery('#selectedcolumns div.selectedField').eq(index);
		}
		
		var allProps = me.newFieldProperties;
		allProps.push('readonly');
		allProps.push('mandatory');
		
		for (var i=0; i<allProps.length; ++i) {
			var propname = allProps[i];
			if (propname == 'label') {
				var row = fieldcont.find('td.fieldname');
			} else {
				var row = fieldcont.find('.newfieldprop[name='+baseid+'prop_'+propname+']');
			}
			if (row.length > 0 && row.is(':visible')) {
				var cont1 = row.find('input[type=checkbox]'),
					cont2 = row.find('input'),
					cont3 = row.find('textarea'),
					cont4 = row.find('select');
				var cont = null;
				if (cont1.length > 0) cont = cont1.prop('checked');
				else if (cont2.length > 0) cont = cont2.val();
				else if (cont3.length > 0) cont = cont3.val();
				else if (cont4.length > 0) cont = cont4.val();
				if (cont === null) {
					alert(alert_arr.LBL_FILL_ALL_FIELDS);
					return false;
				} else {
					props[propname] = cont;
				}
			}
		}
		
		// crmv@70622
		// now check some numeric values
		var fieldno = fieldcont.find('input[name=fldno]').val();
		
		props.fieldno = fieldno;

		if (fieldno == 2 && 'length' in props) { // TODO: don't use the raw number here
			if (parseInt(props.length) > 9) {
				var label = jQuery('#'+baseid+'prop_length').find('.dataLabel').text().replace(':', '').trim();
				alert(label + alert_arr.SHOULDBE_LESS_EQUAL + '9');
				return false;
			}
		}
		// crmv@70622e
		
		//crmv@106857
		if (typeof(fieldcont) != 'undefined') {
			var fldname = fieldcont.find('input[name=fldname]').val();
			props.fldname = fldname;
		}
		//crmv@106857e
		
		// TODO: check numeric values
		
		return props;
	},
	
	getNewFieldDefault: function(fieldno, property) {
		var me = this,
			value = '';
		
		if (me.newFieldsDefaults && me.newFieldsDefaults[fieldno]) {
			if (typeof me.newFieldsDefaults[fieldno][property] != 'undefined') {
				value = me.newFieldsDefaults[fieldno][property];
			}
		}
		
		return value;
	},
	
	getNewTableFieldsDefaults: function(fieldno, property) {
		var me = this,
		value = '';
		
		if (me.newTableFieldsDefaults && me.newTableFieldsDefaults[fieldno]) {
			if (typeof me.newTableFieldsDefaults[fieldno][property] != 'undefined') {
				value = me.newTableFieldsDefaults[fieldno][property];
			}
		}
		
		return value;
	},
	
	selectNewField: function(newfieldno, props, uitype) { // crmv@102879
		var me = this;
		
		if (uitype == 220) {
			var blockno = jQuery('#mmaker_newfield_blockno').val();
			me.hideFloatingDiv('mmaker_div_addfield');
			return me.openAddTableFieldPopup(blockno, newfieldno);
		}
		
		if (typeof props == 'string') {
			props = props.split(',');
		} else {
			props = [];
		}
		
		// unselect the previous field
		if (me.newFieldSelected) jQuery(me.newFieldSelected).removeClass('newFieldMnuSelected');
		me.newFieldSelected = null;

		// hide all the properties
		jQuery('#mmaker_newfield_props').find('.newfieldprop').hide();
		
		// select the field
		var fldobj = jQuery('#newfield_'+newfieldno);
		fldobj.addClass('newFieldMnuSelected');
		me.newFieldSelected = fldobj;
		
		// show field properties
		for (var i=0; i<props.length; ++i) {
			var row = jQuery('#newfieldprop_'+props[i]);
			// set the default value
			if (row.length > 0) {
				var defaultValue = me.getNewFieldDefault(newfieldno, props[i]);
				row.find('input').val(defaultValue);
				row.find('select').val(defaultValue);
				row.find('textarea').val(defaultValue);
				row.show();
				
				if (props[i] == 'label') {
					// set the focus for the title
					var el = row.find('input').get(0);
					if (el && el.focus) el.focus();
				}
			}
		}

	},
	
	moveFieldsToBlock: function() {
		var me = this,
			fieldstruct = {};
		
		var blockno = jQuery('#mmaker_movefields_blockno').val();
		var fields = jQuery('#movefields_select').val();
		
		for (var i=0; i<fields.length; ++i) {
			var pieces = fields[i].split('_'),
				fblock = pieces[1],
				fno = pieces[2];
			
			if (!(fblock in fieldstruct)) fieldstruct[fblock] = [];
			fieldstruct[fblock].push(fno);
		}
		
		me.ajaxCall('movefieldstoblock', {
			movetoblockno: blockno,
			fields: JSON.stringify(fieldstruct),
		}, null, {
			includeForm: true,
		});
		
	},
	
	editField: function() {
		var me = this;
		
		var blockno = jQuery('#mmaker_editfield_blockno').val();
		var fieldno = jQuery('#mmaker_editfield_fieldno').val();
		
		var props = {};
		
		props.mandatory = (jQuery('#fieldprop_mandatory').get(0).checked ? 1 : 0);
		//crmv@96450
		if (jQuery('#fieldprop_masseditable').length > 0) props.masseditable = (jQuery('#fieldprop_masseditable').get(0).checked ? 1 : 0);
		if (jQuery('#fieldprop_readonly').length > 0) props.readonly = jQuery('#fieldprop_readonly').val();
		if (jQuery('#fieldprop_picklistvalues').length > 0 && jQuery('#fieldprop_picklistvalues').is(':visible')) props.picklistvalues = jQuery('#fieldprop_picklistvalues').val();
		if (jQuery('#fieldprop_default').length > 0 && jQuery('#fieldprop_default').is(':visible')) props.default = jQuery('#fieldprop_default').val();
		//crmv@96450e
		//crmv@98570
		if (jQuery('#fieldprop_onclick').length > 0 && jQuery('#fieldprop_onclick').is(':visible')) props.onclick = jQuery('#fieldprop_onclick').val();
		if (jQuery('#fieldprop_code').length > 0 && jQuery('#fieldprop_code').is(':visible')) props.code = jQuery('#fieldprop_code').val();
		//crmv@98570e
		if (jQuery('#fieldprop_users').length > 0 && jQuery('#fieldprop_users').is(':visible')) props.users = jQuery('#fieldprop_users').val().join();	//crmv@101683
		//crmv@160837
		if (jQuery('#fieldprop_fieldlabel').length > 0) {
			props.fieldlabel = jQuery('#fieldprop_fieldlabel').val();
			props.label = jQuery('#fieldprop_fieldlabel').val();
		}
		//crmv@160837e
		
		me.ajaxCall('editfield', {
			blockno: blockno,
			editfieldno: fieldno,
			properties: JSON.stringify(props),
		}, null, {
			includeForm: true,
		});
		
	}
	
}


var TableFieldConfig = {

	addBox: function(fieldno, value, element, props, uitype) {
		var me = this,
			cont = jQuery('#selectedcolumns'),
			tpl = jQuery('#selectColumnTemplate'),
			box = tpl.clone();

		if (typeof props == 'string') {
			props = props.split(',');
		} else {
			props = [];
		}

		var fieldCont = jQuery(element).closest('td');
		var iconEl = fieldCont.find('i.vteicon');
		if (iconEl.length == 0) {
			iconEl = fieldCont.find('i.vteicon2');
		}
		if (iconEl.length > 0) {
			var boxIcon = box.find('i[name=fieldIcon]');
			boxIcon.text(iconEl.text());
			boxIcon.get(0).className = iconEl.get(0).className;
		}
		
		var labelEl = fieldCont.find('span.newFieldLabel');
		if (labelEl.length > 0) {
			var boxName = box.find('span[name=fieldModuleName]');
			boxName.text(labelEl.text());
		}

		box.find('input[name=fldno]').val(fieldno);
		if (value != null && typeof(value.fieldname) != 'undefined') box.find('input[name=fldname]').val(value.fieldname);	//crmv@106857
		box.removeAttr('id');
		box.show().removeAttr('style');
		
		// show field properties
		for (var i=0; i<props.length; ++i) {
			var row = box.find('[name=newtablefieldprop_'+props[i]+']');
			// set the default value
			if (row.length > 0) {
				
				if (props[i] == 'label') {
					// set the focus for the title
					var el = box.find('input[name=fieldLabel]').get(0);
					if (el && el.focus) {
						// set the default
						if (value && value.label) {
							jQuery(el).val(value.label);
						} else {
							// wait until the box is added to the dom
							setTimeout(function() {
								el.focus();
							}, 100);
						}
					}
				} else {
					if (value && props[i] in value) {
						var defaultValue = value[props[i]];
					} else {
						var defaultValue = ModuleMakerFields.getNewTableFieldsDefaults(fieldno, props[i]);
					}
					//crmv@131239
					if (props[i] == 'relatedmods') {
						if (typeof(defaultValue) == 'string') defaultValue = defaultValue.split(',');
					}
					//crmv@131239e
					row.find('input').val(defaultValue);
					row.find('input[type=checkbox]').prop('checked', !!defaultValue);
					row.find('select').val(defaultValue);
					row.find('textarea').val(defaultValue);
					if (props[i] == 'readonly') {
						var readSelect = row.find('select').get(0); 
						setTimeout(function() {
							me.changePermission(readSelect);
						}, 100);
					}
					//crmv@106857
					if (props[i] == 'users') {
						if (value != null && typeof(value.selected_values) == 'undefined' && typeof(value.users) != 'undefined') value.selected_values = (value.users).split(',');	//crmv@155093
						if (value != null && typeof(value.selected_values) != 'undefined') {
							jQuery.each(value.selected_values,function(k,v){
								row.find('select').find('option[value="'+v+'"]').prop('selected',true);
							});
						}
					}
					//crmv@106857e
					//crmv@131239
					if (props[i] == 'relatedmods_selected') {
						if (defaultValue != null && typeof(defaultValue) != 'undefined') {
							if (typeof(defaultValue) == 'string') defaultValue = defaultValue.split(',');
							if (jQuery(defaultValue).length > 0) {
								jQuery.each(defaultValue,function(k,v){
									box.find('[name=newtablefieldprop_relatedmods]').find('select').find('option[value="'+v+'"]').prop('selected',true);
								});
							}
						}
					}
					//crmv@131239e
					row.show();
				}
			}
		}
		
		cont.append(box);
	},
	
	removeBox: function(self) {
		var me = this,
			target = jQuery(self).closest('.selectedField');
		
		target.remove();
	},
	
	removeAllBoxes: function() {
		jQuery('#selectedcolumns').html('');
	},
	
	setValues: function(info) {
		var me = this,
			cont = jQuery('#selectedcolumns');

		// field info
		jQuery('#newtablefieldprop_val_label').val(info.label);
		// crmv@190916
		if (info.readonly == '0') info.readonly = '1';
		jQuery('#newtablefieldprop_val_readonly').val(info.readonly);
		if ((typeof(info.mandatory) == 'boolean' && info.mandatory) || (typeof(info.mandatory) == 'string' && info.mandatory == '1')) jQuery('#newtablefieldprop_val_mandatory').prop('checked', true);
		TableFieldConfig.changePermission(jQuery('#newtablefieldprop_val_readonly'));
		// crmv@190916e
		
		// columns
		jQuery.each(info.columns, function(idx, col) {
			//crmv@131239
			if (typeof(col.decimals) != 'undefined' && col.decimals > 0)
				var el = me.getElementByUitype(col.uitype, 'decimals');
			else if (col.uitype == 300)	// TODO
				var el = me.getElementByUitype(15);
			else
				var el = me.getElementByUitype(col.uitype);
			//crmv@131239e
			if (el) {
				me.addBox(el.dataset.fieldno, col, el, el.dataset.props, col.uitype);
			}
		});
		
	},
	
	getElementByUitype: function(uitype, prop) {	//crmv@131239
		var me = this,
			list = jQuery('#newtablefields .newFieldMnu');

		for (var i=0; i<list.length; ++i) {
			//crmv@131239
			if (typeof(prop) != 'undefined') {
				if (list[i].dataset.uitype == uitype && list[i].dataset.props.indexOf(prop) > -1) return list[i];
			} else {
				if (list[i].dataset.uitype == uitype) return list[i];
			}
			//crmv@131239e
		}
		
		return false;
	},

	cancelConfig: function() {
		var me = this;
		me.closeConfig();
	},
	
	saveConfig: function() {
		var me = this,
			cont = jQuery('#selectedcolumns'),
			cols = cont.find('.selectedField');

		if (!me.validateConfig()) return false;
		
		var fieldno = jQuery('#mmaker_newtablefield_fieldno').val();
		var editno = jQuery('#mmaker_newtablefield_editfieldno').val();
		var blockno = jQuery('#mmaker_newtablefield_blockno').val();
		var props = {
			label: jQuery('#newtablefieldprop_val_label').val(),
			columns: [],
			// crmv@190916
			readonly: jQuery('#newtablefieldprop_val_readonly').val(),
			mandatory: (jQuery('#newtablefieldprop_val_mandatory:checked').length > 0)?true:false,
			// crmv@190916e
		};
		
		cols.each(function(idx, el) {
			var fprops = ModuleMakerFields.checkNewTableFieldProps('newtablefield', idx);
			if (!fprops) {
				props = false;
				return false;
			}
			props.columns.push(fprops);
		});
		
		if (!props) return false;
		
		props.columns = JSON.stringify(props.columns);
		
		ModuleMakerFields.ajaxCall(editno ? 'editfield' : 'addfield', {
			blockno: blockno,
			addfieldno: fieldno,
			editfieldno: editno,
			properties: JSON.stringify(props),
		}, null, {
			includeForm: true,
		});

		me.closeConfig();
	},

	closeConfig: function() {
		ModuleMakerFields.hideFloatingDiv('mmaker_div_addtablefield');
	},
	
	validateConfig: function() {
		var me = this,
			cont = jQuery('#selectedcolumns'),
			cols = cont.find('.selectedField');
			
		// check table name
		var tname = jQuery('#newtablefieldprop_val_label').val();
		if (!tname) {
			vtealert(alert_arr.LBL_PLEASE_CHOOSE_FIELDNAME);
			return false;
		}
		
		// check if no columns
		if (cols.length == 0) {
			vtealert(alert_arr.LBL_PLEASE_ADD_COLUMNS);
			return false;
		}
		
		// check column names
		for (var i=0; i<cols.length; ++i) {
			var $el = jQuery(cols.get(i));
			var label = $el.find('input[name=fieldLabel]').val();
			if (!label) {
				vtealert(alert_arr.LBL_PLEASE_NAME_ALL_COLUMNS);
				return false;
			}
			// crmv@167159
			var re2=/[&\<\>\:\'\"\,\_]/
			if (re2.test(label)) {
				vtealert(alert_arr.SPECIAL_CHARACTERS+" & < > ' \" : , _ "+alert_arr.NOT_ALLOWED);
				return false;
			}
			// crmv@167159e
		}
			
		return true;
	},
	
	addSelectedFields: function() {
		var me = this;
		
		jQuery('#newtablefields .newFieldMnu.newFieldMnuSelected').each(function(idx, el) {
			var data = jQuery(el).data();
			me.addBox(data.fieldno, null, el, data.props, data.uitype);
		});
		
	},
	
	toggleField: function(newfieldno, quickinsert) {
		var me = this;
		
		// unselect the previous field
		if (me.newFieldSelected) jQuery(me.newFieldSelected).removeClass('newFieldMnuSelected');
		me.newFieldSelected = null;
		
		// select the field
		var fldobj = jQuery('#newtablefield_'+newfieldno);
		
		if (fldobj.hasClass('newFieldMnuSelected')) {
			fldobj.removeClass('newFieldMnuSelected');
		} else {
			fldobj.addClass('newFieldMnuSelected');
		}
		
		me.newFieldSelected = fldobj;
		
		if (quickinsert) {
			me.addSelectedFields();
		}
		
	},

	changePermission: function(self) {
		var me = this,
			readonly = jQuery(self).val(),
			cont = jQuery(self).closest('table'),
			contMand = cont.find('tr[name=newtablefieldprop_mandatory]');
			
		if (readonly == 1) {
			contMand.show();
		} else {
			contMand.hide();
			contMand.find('input[type=checkbox]').prop('checked', false);
		}
	}
}



var ModuleMakerRelations = {
	
	busy: false,
	
	isBusy: function() {
		return this.busy;
	},
	
	showBusy: function() {
		var me = this;
		me.busy = true;
		jQuery('#mmaker_busy').show();
	},
	
	hideBusy: function() {
		var me = this;
		me.busy = false;
		jQuery('#mmaker_busy').hide();
	},
	
	ajaxCall: function(action, params, callback, options) {
		var me = this;
		
		// return if busy
		if (me.isBusy()) return;
		
		options = options || {};
		params = params || {};
		var url = "index.php?module=Settings&action=SettingsAjax&file=ModuleMaker&ajax=1&subaction="+action;
		
		if (options.includeForm) {
			var form = jQuery('#module_maker_form').serialize();
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
				/*jQuery('#mmaker_div_allblocks').hide().html('');
				jQuery('#mmaker_div_allblocks').html(data).show();*/
				if (options.reloadList) {
					jQuery('#mmaker_div_relations').hide().html('');
					jQuery('#mmaker_div_relations').html(data).show();
				}
				if (typeof callback == 'function') callback(data);
			},
			error: function() {
				if (typeof callback == 'function') callback();
			}
		});
		
	},
	
	hideAddButton: function() {
		jQuery('#mmaker_div_addrelation_button').hide();
	},
	
	showAddButton: function() {
		jQuery('#mmaker_div_addrelation_button').show();
	},
	
	showCreateRelation: function() {
		var me = this;
		
		me.hideAddButton();
		ModuleMaker.hideNavigationButtons();
		jQuery('#mmaker_div_relations').hide();
		jQuery('#mmaker_div_addrelation').show();
		
		// reset fields
		jQuery('#relation_type').val('1ton');
		jQuery('#relation_module').val('');
		jQuery('#relation_block').find('option').remove();
		jQuery('#relation_field').val('');
		
		var cont = jQuery('#mmaker_div_addrelation');
		cont.find('.add_relation_field_1ton').show();
		cont.find('.add_relation_field_nton').hide();
	},
	
	hideCreateRelation: function() {
		var me = this;
		
		jQuery('#mmaker_div_addrelation').hide();
		jQuery('#mmaker_div_relations').show();
		ModuleMaker.showNavigationButtons();
		me.showAddButton();
	},
	
	changeFirstModule: function() {
		var me = this,
			types = ['1ton', 'nton'],
			myself = jQuery('#new_module_name').val(),
			mysingle = jQuery('#new_module_single_name').val(),
			firstmod = jQuery('#relation_first_module').val(),
			type = jQuery('#relation_type').val(),
			othertype = types[1-types.indexOf(type)];
		
		var cont = jQuery('#mmaker_div_addrelation');

		if (!firstmod) {
			jQuery('#relation_module').val('');
			jQuery('#relation_module option').show();
			jQuery('#relation_module').attr('disabled', true);
		} else if (firstmod == myself) {
			jQuery('#relation_module').val('');
			jQuery('#relation_module option').show();
			jQuery('#relation_module option[value='+firstmod+']').hide();
			jQuery('#relation_module').attr('disabled', false);
		} else {
			jQuery('#relation_module option[value!='+myself+']').hide();
			jQuery('#relation_module').val(myself);
			jQuery('#relation_module').attr('disabled', false);
		}
		me.changeModule();

	},
	
	changeRelationType: function() {
		var me = this,
			types = ['1ton', 'nton'],
			firstmod = jQuery('#relation_first_module').val(),
			type = jQuery('#relation_type').val(),
			othertype = types[1-types.indexOf(type)];
			
		var cont = jQuery('#mmaker_div_addrelation');
		cont.find('.add_relation_field_'+type).show();
		cont.find('.add_relation_field_'+othertype).hide();
		
		if (type == '1ton') {
			me.changeModule();
		}
	},
	
	changeModule: function() {
		var me = this,
			myself = jQuery('#new_module_name').val(),
			mysingle = jQuery('#new_module_single_name').val(),
			firstmod = jQuery('#relation_first_module').val(),
			module = jQuery('#relation_module').val(),
			type = jQuery('#relation_type').val();

		if (type == '1ton') {
			// update blocks
			
			// remove the old options
			jQuery('#relation_block').find('option').remove();
			
			// get the new ones
			if (module) {
				me.ajaxCall('getmoduleblocks', {
					blockmodule: module,
					firstmodule: firstmod,
				}, function(data) {
					if (data) {
						try {
							data = JSON.parse(data);
							var blocks = data.blocks;
						} catch (e) {
							var blocks = [];
						}
						var opts = '';
						for (var i=0; i<blocks.length; ++i) {
							opts += '<option value="'+blocks[i].blocklabel+'">'+blocks[i].label+'</option>';
						}
						jQuery('#relation_block').append(opts);
						if (data.singlename) {
							jQuery('#relation_field').val(data.singlename);
						} else if (module != myself) {
							jQuery('#relation_field').val(mysingle);
						} else {
							jQuery('#relation_field').val('');
						}
					}
				});
			} else {
				jQuery('#relation_block').find('option').remove();
				jQuery('#relation_field').val('');
			}
		}
	},
	
	validateFields: function() {
		var me = this;
		
		var type = jQuery('#relation_type').val();
		var module = jQuery('#relation_module').val();
		var block = jQuery('#relation_block').val();
		var field = jQuery('#relation_field').val();
		
		if (!module || !type) {
			alert(alert_arr.LBL_FILL_ALL_FIELDS);
			return false;
		}
		
		if (type ==  '1ton') {
			if (!block || !field) {
				alert(alert_arr.LBL_FILL_ALL_FIELDS);
				return false;
			}
		}
		
		return true;
	},
	
	createRelation: function() {
		var me = this;
		
		// validate the fields
		if (!me.validateFields()) return false;
		
		// save it!
		var type = jQuery('#relation_type').val();
		var firstmodule = jQuery('#relation_first_module').val();
		var module = jQuery('#relation_module').val();
		var block = jQuery('#relation_block').val();
		var field = jQuery('#relation_field').val();
		
		me.ajaxCall('addrelation', {
			type: type,
			firstrelationmod: firstmodule,
			relationmod: module,
			blockname: block,
			fieldname: field,
		}, function(data) {
			me.hideCreateRelation();
		}, {
			reloadList: true,
			includeForm: true,
		});
	},
	
	delRelation: function(relno) {
		var me = this;
		
		me.ajaxCall('delrelation', {
			delrelationno: relno,
		}, null, {
			reloadList: true,
			includeForm: true,
		});
		
	},
	
	delRelationN1: function(fieldname) {
		var me = this;
		
		me.ajaxCall('delrelation', {
			rel_n1: 1,
			delrelationfield: fieldname,
		}, null, {
			reloadList: true,
			includeForm: true,
		});
	}
	
}


var ModuleMakerLanguages = {
	
	baseUrl: 'index.php?module=Settings&action=SettingsAjax&file=ModuleMaker&ajax=1',
	
	init: function(mode) {
		var me = this;
		
		var module = jQuery('#module_select').val();
		var language = jQuery('#language_select').val();
		var filter = jQuery('#filter_select').val();

		var colNames = new Array();
		var colModel = new Array();
		var moduleoptions = {}
		var prop = '';
		var propval = '';
		jQuery('#module_select option').each(function(){
			prop = jQuery(this).val();
			propval = jQuery(this).text();
			if (prop != ''){
				moduleoptions[prop] = propval;
			}
		});
		var grid = jQuery("#trans_table"),
			lastSel;
		
		var myEditOptions = {
			//beforeShowForm:me.before_show_edit,
			beforeSubmit:me.before,
			afterSubmit:me.after,
			bottominfo: ModuleMakerTrans.LBL_TRANS_MANDATORY
		};

		// action column
		colNames.push(ModuleMakerTrans.LBL_TRANS_ACTIONS);
		colModel.push({name:'act',index:'act',width:25,align:'center',sortable:false,search:false,formatter:'actions',
			formatoptions:{
				keys: true,
				delbutton: false,
				editbutton: true,
				editformbutton: true,             	
				editOptions:myEditOptions,
			}});			
		
		// module column
		colNames.push(ModuleMakerTrans.LBL_TRANS_MODULE+' *');
		colModel.push({
			name:'modulename',
			index:'modulename',
			editable: false,
			editrules:{required:true},
			edittype:'select',
			editoptions:{value:moduleoptions},
			search: false
		});
		
		// label column
		/*colNames.push(ModuleMakerTrans.LBL_TRANS_LABEL+' *');
		colModel.push({
			name:'label',
			index:'label',
			editable:true,
			editrules:{required:true}
		});*/
		
		// language columns
		var val = '';
		var txt = '';
		if (jQuery('#language_select').val() == '') {
			jQuery('#language_select option').each(function(){
				if (jQuery(this).val() != ''){
					colNames.push(jQuery(this).text());
					val = jQuery(this).val();
					colModel.push({
						name: val,
						index: val,
						editable: true,
						edittype: 'textarea',
						editoptions: {size:"20"},
						search: true
					});
				}
			});
		} else {
			val = jQuery('#language_select :selected').val();
			colNames.push(jQuery('#language_select :selected').text());
			colModel.push({name:val,index:val,editable:true,edittype:'textarea',editoptions:{size:"20"}});
		}
		
		// urls
		var list_url = me.baseUrl+'&subaction=labels_list&module_select='+module+'&language_select='+language+'&filter_select='+filter;
		var edit_url = me.baseUrl+'&subaction=labels_edit';
		var edit_cell_url = me.baseUrl+'&subaction=labels_edit_cell';
		
		// reload all
		if (mode == 'reload') {
			jQuery("#trans_table").jqGrid("setGridParam",{'url':list_url}).trigger("reloadGrid");
			return;
		// rebuild (columns are different)
		} else if (mode == 'rebuild') {
			jQuery("#trans_table").jqGrid("GridUnload");
			me.init();
			return;
		}
		
		// init the grid
		grid.jqGrid({
			url: list_url, 
			editurl: edit_url,
			cellurl: edit_cell_url,
			
			cellEdit: true, // SUPER BUGGED, but with the handler on before selection, we can avoid the bug
			datatype: "json",
			colNames: colNames,
			colModel: colModel,
			repeatitems: false,
			autowidth: true,
			pager: '#trans_table_nav',
			gridview: true,
			rownumbers: true,
			viewrecords: true,			
			rowNum: 20, 
			rowList: [20,40,60,ModuleMakerTrans.LBL_TRANS_ALL],
			caption: '',
			multiselect: false,
			toppager:true,
			drag:true,
			height: '480px',
			//height:jQuery(document).height() - jQuery('#trans_table').offset().top - jQuery('#vte_footer').height()-85,
			jqModal:true,
			
			// BUGFIX: avoid selection bug with cellEdit enabled
			beforeSelectRow: function (rowid, e) {
				return false;
			},
			
			
		});
		
		// init the navigation bar
		grid.navGrid('#trans_table_nav', {
			refresh:false,
			search:false,
			edit:false,
			del: false,
			add: false,
			cloneToTop:true
		});
		
	},
	
	
	/*before_show_edit: function(formid) {
		jQuery('#modulename',formid).attr('disabled',true);
		jQuery('#label',formid).attr('readonly',true);
	},*/
	
	/*
	before_show_add: function before_show_add(formid) {
		if (jQuery('#module_select').val() != ''){
			jQuery('#modulename',formid).val(jQuery('#module_select').val());
		}
		jQuery('#modulename',formid).attr('disabled',false);
		jQuery('#label',formid).attr('readonly',false);
	},
	*/
	
	before: function(postdata, formid) {
		return [true,"",""];
	},
	
	after: function(response, postdata) {
		var response = response.responseText;
		response = eval('('+response+')');
		if (response['confirm']){
			if (confirm(response['msg'])){
				response['success'] = true;
			}
			else{
				response['success'] = false;
				response['msg'] = '';
			}
		}
		return [response['success'],response['msg'],""];
	},
		
}

var ModuleMakerCodeEditor = {
	
	busy: false,
	openFile: '',
	pendingChanges: false,
	
	showBusy: function() {
		var me = this;
		me.busy = true;
		jQuery('#mmaker_busy').show();
		jQuery('#mmaker_code_editor').attr('disabled', true);
		if (me.codeMirror) me.codeMirror.setOption('readOnly', true);
	},
	
	hideBusy: function() {
		var me = this;
		me.busy = false;
		jQuery('#mmaker_busy').hide();
		jQuery('#mmaker_code_editor').attr('disabled', false);
		if (me.codeMirror) me.codeMirror.setOption('readOnly', false);
	},
	
	initEditor: function() {
		var me = this;
		
		var ta = document.getElementById('mmaker_code_editor');
		if (ta) {
			// first set the size
			var w = jQuery(ta).width();
			jQuery(ta).parent().css({width: w+'px'});
			// then transform
			me.codeMirror = CodeMirror.fromTextArea(ta, {
				theme: 'eclipse',
				lineWrapping: false,
				indentUnit: 4,
				tabSize: 4,
				indentWithTabs: true,
			});
			me.codeMirror.on("change", function() {
				me.changeCode();
			});
		} else {
			console.log('Textarea not found');
		}
	},
	
	fixEditorSize: function() {
		var me = this;
		
		if (me.codeMirror) {
			var ta = jQuery('#mmaker_code_editor').parent();
				w = ta.width(),
				doch = jQuery(window).height(),
				pos = ta.position();
			me.codeMirror.setSize(w, Math.max(300, doch-pos.top-80));
		}
	},
	
	getEditorText: function() {
		var me = this,
			text;
		
		if (me.codeMirror) {
			text = me.codeMirror.getDoc().getValue();
		} else {
			// fallback on standard textarea
			text = jQuery('#mmaker_code_editor').val();
		}
		
		return text;
	},
	
	setEditorText: function(text, mode) {
		var me = this;
		
		if (!mode) mode = 'php';
		
		if (me.codeMirror) {
			me.codeMirror.getDoc().setValue('');
			me.codeMirror.setOption('mode', mode);
			me.fixEditorSize();
			me.codeMirror.getDoc().setValue(text);
		} else {
			// fallback on standard textarea
			jQuery('#mmaker_code_editor').val(text);
		}
	},
	
	ajaxCall: function(action, params, callback, options) {
		var me = this;
		
		// return if busy
		if (me.busy) return;
		
		options = options || {};
		params = params || {};
		var url = "index.php?module=Settings&action=SettingsAjax&file=ModuleMaker&ajax=1&subaction="+action;
		
		if (options.includeForm) {
			var form = jQuery('#module_maker_form').serialize();
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
				if (typeof callback == 'function') callback(data);
			},
			error: function() {
				me.hideBusy();
				if (typeof callback == 'function') callback();
			}
		});
		
	},
	
	openScriptEditor: function() {
		var me = this;
		
		ModuleMaker.hideNavigationButtons();
		jQuery('#mmaker_step6_properties').hide();
		jQuery('#mmaker_div_code_editor').show();
	},
	
	closeScriptEditor: function() {
		var me = this;
		
		jQuery('#mmaker_step6_properties').show();
		jQuery('#mmaker_div_code_editor').hide();
		ModuleMaker.showNavigationButtons();
	},
	
	loadEditableScript: function(moduleid) {
		var me = this,
			file = jQuery('#mmaker_code_select').val();
		
		if (!file) return;
		
		if (me.pendingChanges) {
			if (confirm(alert_arr.LBL_WANT_TO_SAVE_PENDING_CHANGES)) {
				me.saveEditableScript(moduleid, function() {
					me.loadEditableScript(moduleid);
				});
				return;
			}
		}
		
		me.openFile = false;
		me.ajaxCall('load_script', {
			moduleid: moduleid,
			script_file: file,
		}, function(data) {
			// load the script
			var mode = '';
			// try to guess the mode
			if (file.match(/\.php$/i)) {
				mode = 'php';
			} else if (file.match(/\.js$/i)) {
				mode = 'javascript';
			} else if (file.match(/\.css$/i)) {
				mode = 'css';
			}
			me.setEditorText(data, mode);
			me.pendingChanges = false;
			jQuery('#mmaker_code_save_btn').css({visibility: 'hidden'});
			me.openFile = file;
		});
	},
	
	saveEditableScript: function(moduleid, callback) {
		var me = this,
			useredit = parseInt(jQuery('#mmaker_useredit').val()),
			file = me.openFile;
			
		if (!file) return;
		
		me.ajaxCall('save_script', {
			moduleid: moduleid,
			script_file: file,
			script_data: me.getEditorText(),
		}, function(data) {
			try {
				data = JSON.parse(data);
			} catch (e) {
			}
			if (data && data.success) {
				me.pendingChanges = false;
				jQuery('#mmaker_code_save_btn').css({visibility: 'hidden'});
				if (useredit) {
					// already modified
				} else {
					// new modification
					jQuery('#mmaker_code_cancel_btn').hide();
					window.location.reload();
				}
				if (typeof callback == 'function') callback();
			} else if (data.error) {
				alert(data.error);
			} else {
				console.log('Unknown error');
				console.log(data);
			}
		});
	},
	
	resetEditableScripts: function(moduleid) {
		var me = this;
		
		if (!confirm(alert_arr.LBL_MMAKER_CONFIRM_RESET)) {
			return;
		}
		
		me.ajaxCall('reset_scripts', {
			moduleid: moduleid,
		}, function(data) {
			try {
				data = JSON.parse(data);
			} catch (e) {
			}
			if (data && data.success) {
				location.href = 'index.php?module=Settings&action=ModuleMaker&parentTab=Settings&mode=edit&module_maker_step=6&moduleid='+moduleid;
			} else if (data.error) {
				alert(data.error);
			} else {
				console.log('Unknown error');
				console.log(data);
			}
		});
	},
	
	changeCode: function() {
		var me = this;
		if (me.openFile) {
			me.pendingChanges = true;
			jQuery('#mmaker_code_save_btn').css({visibility: 'visible'});
		}
	},
	
}