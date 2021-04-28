/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@104568 crmv@146434 crmv@201359 */

var LayoutEditor = {
		
	busy: false,
	
	isBusy: function() {
		return this.busy;
	},
	
	showBusy: function() {
		var me = this;
		me.busy = true;
		jQuery('#status').show();
	},
	
	hideBusy: function() {
		var me = this;
		me.busy = false;
		jQuery('#status').hide();
	},

	ajaxCall: function(service, params, options, callback) {
		var me = this,
			module = jQuery('input[name=fld_module]').val(),
			panelid = jQuery('input[name=panelid]').val();
		
		// return if busy
		if (me.isBusy()) return;
			
		options = jQuery.extend({}, {
			rawResult: false,
			container: 'cfList',
			reloadVersion: true,
			checkError: false,
		}, options || {});
		
		var url = 'index.php?module=Settings&action=SettingsAjax&file=LayoutBlockList&ajax=true&parenttab=Settings&sub_mode='+service;
		url += '&fld_module='+module+'&mobile='+for_mobile+'&panelid='+panelid;
		
		me.showBusy();
		
		jQuery.ajax({
			url: url,
			method: 'POST',
			data: params,
			success: function(response) {
				me.hideBusy();
				if (!options.rawResult && options.container) {
					var error = false;
					if (options.checkError) {
						if (response == 'DUPLICATE') {
							alert(alert_arr.LABEL_ALREADY_EXISTS);
							error = true;
						} else if(response == 'LENGTH_ERROR') {
							alert(alert_arr.LENGTH_OUT_OF_RANGE);
							error = true;
						// crmv@150747
						} else if (response == 'DB_ROW_LIMIT_REACHED') {
							alert(alert_arr.DB_ROW_LIMIT_REACHED);
							error = true;
						}
						// crmv@150747e
					}
					if (!error) jQuery("#"+options.container).html(response);
				}
				if (options.reloadVersion) me.ajaxCallVersion();
				if (typeof callback == 'function') callback(response);
			}
		});
	},
	
	ajaxCallRelated: function(service, params, options, callback) {
		var me = this;
		
		options = jQuery.extend(options || {}, {
			container: 'RelatedList_div',
		});
		
		return me.ajaxCall(service, params, options, callback);
	},

	ajaxCallVersion: function() {
		var me = this;
		return me.ajaxCall('layoutBlockVersion', null, {container: 'layoutBlockVersionContainer', reloadVersion: false});
	},
	
	changeTab: function(panelid) {
		var me = this,
			curpanelid = jQuery('input[name=panelid]').val(),
			module = jQuery('input[name=fld_module]').val();
			
		if (curpanelid == panelid) return;
		
		var url = 'index.php?module=Settings&action=LayoutBlockList&formodule='+module+'&panelid='+panelid;
		location.href = url;
	},
	
	saveTabOrder: function() {
		var me = this,
			cont = jQuery('#LayoutEditTabs'),
			ids = [];
		
		cont.find('td.tabCell').each(function(idx, el) {
			var panelid = jQuery(el).data('panelid');
			ids.push(panelid);
		});
		
		me.ajaxCall('reorderTabs', {panelids: ids.join(';')}, {rawResult: true});
	},
	
	showEditTab: function(panelid, label, self) {
		if (panelid > 0) {
			// edit
			var divname = 'editTab';
			jQuery('#editTabPanelId').val(panelid);
			jQuery('#editTabName').val(label);
		} else {
			// create
			var divname = 'createTab';
		}
		showFloatingDiv(divname, self, panelid);
	},
	
	createTab: function() {
		var me = this,
			label = jQuery('#newTabName').val();
		if (!label) return;
		
		me.ajaxCall('addTab', {
			tablabel: label
		}, {
			checkError: true
		}, function(result) {
			hideFloatingDiv('createTab');
		});
	},

	editTab: function() {
		var me = this,
			label = jQuery('#editTabName').val(),
			panelid = jQuery('#editTabPanelId').val();
		
		if (!label || !panelid) return;
		
		me.ajaxCall('editTab', {
			editpanelid: panelid,
			tablabel: label
		}, {
			checkError: true
		}, function(result) {
			hideFloatingDiv('editTab');
		});
	},
	
	preDeleteTab: function(self) {
		var me = this,
			panelid = jQuery('#editTabPanelId').val(),
			pinfo = panelBlocks[panelid];
			
		// check if there are blocks in this panel
		if (pinfo && pinfo.blockids && pinfo.blockids.length > 0) {
			// show the panel to choose where the blocks should be moved
			showFloatingDiv('chooseTabForBlocks', self);
			hideFloatingDiv('editTab');
		} else {
			// go on with deletion
			vteconfirm(alert_arr.SURE_TO_DELETE, function(yes) {
				if (yes) me.deleteTab();
			});
			
		}
	},
	
	deleteTab: function() {
		var me = this,
			module = jQuery('input[name=fld_module]').val(),
			panelid = jQuery('#editTabPanelId').val(),
			desttab = jQuery('#delTabSelect').val();
		
		me.ajaxCall('deleteTab', {delpanelid: panelid, move_blocks: desttab}, {rawResult: false}, function() {
			// reload page
			var url = 'index.php?module=Settings&action=LayoutBlockList&formodule='+module;
			location.href = url;
		});
	},
	
	preMoveBlock: function(blockid) {
		var me = this;
		
		jQuery('#moveBlockTabId').val(blockid);
		showFloatingDiv('chooseTabForBlock');
	},
	
	moveBlock: function() {
		var me = this,
			blockid =jQuery('#moveBlockTabId').val(),
			desttab = jQuery('#delTabSelectBlock').val();
			
		me.ajaxCall('moveBlockToTab', {blockid: blockid, destpanel: desttab});
	},
	
	changeFieldorder: function(what_to_do, fieldid, blockid, modulename) {
		var me = this;
	
		me.ajaxCall('changeOrder', {
			what_to_do: what_to_do,
			fieldid: fieldid,
			blockid: blockid
		});
	},
	
	changeShowstatus: function(tabid,blockid,modulename) {
		var me = this,
			display_status = jQuery('#display_status_'+blockid).val();
			
		if (display_status == 'move') {
			return me.preMoveBlock(blockid);
		}
	
		me.ajaxCall('changeOrder', {
			what_to_do: display_status,
			tabid: tabid,
			blockid: blockid
		});
		
	},
	
	changeBlockorder: function(what_to_do, tabid, blockid, modulename) {
		var me = this;
	
		me.ajaxCall('changeOrder', {
			what_to_do: what_to_do,
			tabid: tabid,
			blockid: blockid
		});
	},
	
	deleteCustomField: function(id, fld_module, colName, uitype) {
		var me = this;
		
		vteconfirm(alert_arr.ARE_YOU_SURE_YOU_WANT_TO_DELETE, function(yes) {
			if (yes) {
				me.ajaxCall('deleteCustomField', {
					fld_id: id,
					colName: colName,
					uitype: uitype
				}, null, function() {
					hideFloatingDiv('editfield_'+id);
					gselected_fieldtype = '';
				});
			} else {
				hideFloatingDiv('editfield_'+id);
			}
		});
       
	},
	
	deleteCustomBlock: function(module,blockid,no) {
		var me = this;
		
		if (no > 0) {
			alert(alert_arr.PLEASE_MOVE_THE_FIELDS_TO_ANOTHER_BLOCK);
			return false;
		} else {
			vteconfirm(alert_arr.ARE_YOU_SURE_YOU_WANT_TO_DELETE_BLOCK, function(yes) {
				if (yes) {
					me.ajaxCall('deleteCustomBlock', {
						blockid: blockid,
					});
				}
			});
		}
	},
	
	getCreateCustomBlockForm: function(modulename,mode) {
		var me = this,
			blockid = jQuery('#after_blockid').val(),
			blocklabel = trim(jQuery('#blocklabel').val());
		
		// check label
		if (blocklabel == "") {
			alert(alert_arr.BLOCK_NAME_CANNOT_BE_BLANK);
			return false;
		}
		
		me.ajaxCall('addBlock', {
			mode: mode,
			blocklabel: blocklabel,
			after_blockid: blockid
		}, {
			checkError: true
		}, function(result) {
			hideFloatingDiv('addblock');
			gselected_fieldtype = '';
		});
	},
	
	alignAddBlockList: function() {
		var me = this,
			hasone = false,
			panelid = jQuery('input[name=panelid]').val(),
			target = jQuery('#after_blockid'),
			pinfo = panelBlocks[panelid];

		target.val('');
		target.find('option').each(function(idx, el) {
			var blockid = parseInt(el.value);
			if (pinfo.blockids.indexOf(blockid) >= 0) {
				jQuery(el).show();
				hasone = true;
			} else {
				jQuery(el).hide();
			}
		});
		if (hasone) {
			target.get(0).selectedIndex = 0;
		}
	},
	
	getCreateCustomFieldForm: function(modulename, blockid, mode) {
		var me = this;

		if (!validateLayoutEditor(blockid)) return false;

		var type = jQuery("#fieldType_"+blockid).val();
		var label = jQuery("#fldLabel_"+blockid).val();
		var fldLength = jQuery("#fldLength_"+blockid).val();
		var fldDecimal = jQuery("#fldDecimal_"+blockid).val();
		var fldPickList = jQuery("#fldPickList_"+blockid).val();
		//crmv@113771
		var fldOnclick = jQuery("#fldOnclick_"+blockid).val();
		var fldCode = jQuery("#fldCode_"+blockid).val();
		//crmv@113771e
		
		//crmv@101683
		var fldCustomUserPick = jQuery('#fldCustomUserPick_'+blockid).val();
		if (fldCustomUserPick != null && fldCustomUserPick.length > 0) fldCustomUserPick = JSON.stringify(fldCustomUserPick); else fldCustomUserPick = '';
		//crmv@101683e
		
		me.ajaxCall('addCustomField', {
			blockid: blockid,
			fieldType: type,
			fldLabel: label,
			fldLength: fldLength,
			fldDecimal: fldDecimal,
			fldPickList: fldPickList,
			//crmv@113771
			fldOnclick: fldOnclick,
			fldCode: fldCode,
			//crmv@113771e
			fldCustomUserPick: fldCustomUserPick
		}, {
			checkError: true
		}, function(result) {
			hideFloatingDiv('addfield_'+blockid)
			gselected_fieldtype = '';
		});
		
	},
	
	saveFieldInfo: function(fieldid,module,sub_mode) {
		var me = this;
		
		//crmv@101683 crmv@113771
		var info = {};
		if (jQuery('#editCustomUserPick_'+fieldid).length > 0) {
			var prop = jQuery('#editCustomUserPick_'+fieldid).val();
			if (prop != null && prop.length > 0) info['users'] = prop;
		}
		if (jQuery('#editOnclick_'+fieldid).length > 0) {
			var prop = jQuery('#editOnclick_'+fieldid).val();
			if (prop != null && prop.length > 0) info['onclick'] = prop;
		}
		if (jQuery('#editCode_'+fieldid).length > 0) {
			var prop = jQuery('#editCode_'+fieldid).val();
			if (prop != null && prop.length > 0) info['code'] = prop;
		}
		//crmv@101683e crmv@113771e
		
		me.ajaxCall(sub_mode, {
			fieldid: fieldid,
			ismandatory: jQuery('#mandatory_check_'+fieldid).is(':checked'),
			isPresent: jQuery('#presence_check_'+fieldid).is(':checked'),
			quickcreate: jQuery('#quickcreate_check_'+fieldid).is(':checked'),
			massedit: jQuery('#massedit_check_'+fieldid).is(':checked'),
			info: JSON.stringify(info),	//crmv@113771
		}, null, function() {
			hideFloatingDiv('editfield_'+fieldid);
		});
	},
	
	show_move_hiddenfields: function(modulename,tabid,blockid,sub_mode) {
		var me = this;
		
		if (sub_mode == 'showhiddenfields') {
			var list = jQuery('#hiddenfield_assignid_'+blockid).val() || [];
		} else {
			var list = jQuery('#movefield_assignid_'+blockid).val() || [];
		}
		
		me.ajaxCall(sub_mode, {
			tabid: tabid,
			blockid: blockid,
			selected: list.join(':'),
		}, null, function() {
			(sub_mode == 'showhiddenfields') ? hideFloatingDiv('hiddenfield_'+blockid) : hideFloatingDiv('movefield_'+blockid); // crmv@166864
		});
	},
	
	changeRelatedListorder: function(what_to_do,tabid,sequence,id,module) {
		var me = this;
		
		me.ajaxCallRelated('changeRelatedInfoOrder', {
			sequence: sequence,
			what_to_do: what_to_do,
			tabid: tabid,
			id: id
		});
	},
	
	changeRelatedListVisibility: function(tabid,visible,id,module) {
		var me = this,
			curpanelid = jQuery('input[name=panelid]').val();
		
		me.ajaxCallRelated('changeRelatedInfoVisibility', {
			visible: visible,
			tabid: tabid,
			id: id
		}, null, function() {
			if (visible == 0) {
				// hide the related, reload the blocks
				me.ajaxCall('NoService');
			}
		});
		
	},
	
	changeRelatedOption: function(module, option, value) {
		var me = this;
		
		me.ajaxCallRelated('changeRelatedOption', {
			option: option,
			optionValue: value
		});
	},

	callRelatedList: function(module) {
		var me = this;
		
		me.ajaxCallRelated('getRelatedInfoOrder');
	},
	
	
	addRelatedList: function() {
		var me = this,
			ids = jQuery('#addRelatedSelect').val() || [];
		
		if (ids && ids.length > 0) {
			me.ajaxCall('addRelatedToTab', {
				relids: ids.join(';'),
			});
		}
	},
	
	removeTabRelated: function(relationid) {
		var me = this;
		
		me.ajaxCall('removeTabRelated', {
			relationid: relationid,
		}, {
			rawResult: true,
		}, function() {
			jQuery('#tabrelated_'+relationid).remove();
		});
	},
	
	saveRelatedTabOrder: function() {
		var me = this
			cont = jQuery('#tabRelatedLists'),
			ids = [];
		
		cont.find('div.tabrelated').each(function(idx, el) {
			var relid = jQuery(el).data('relationid');
			ids.push(relid);
		});
		
		me.ajaxCall('reorderTabRelateds', {relationids: ids.join(';')}, {rawResult: true});
	},
	
	closeVersion: function(callback) {
		var me = this;
		me.ajaxCall('closeVersion', null, {rawResult: true, reloadVersion: false}, function(response){
			if (response != '') alert(response);
			else me.ajaxCallVersion();
			if (typeof callback == 'function') callback();
		});
	},
	
	exportVersion: function() {
		var me = this,
			module = jQuery('input[name=fld_module]').val(),
			url = 'index.php?module=Settings&action=SettingsAjax&file=LayoutBlockList&formodule='+module+'&sub_mode=exportVersion';
		
		me.ajaxCall('checkExportVersion', null, {rawResult: true, reloadVersion: false}, function(response){
			if (response != '') alert(response);
			else location.href = url;
		});
	}
}

function enableDisableCheckBox(obj, elementName) {

	var ele = jQuery(elementName);
	if (obj == null || ele.length == 0) return;
	if (obj.checked == true) {
		ele.prop('checked', true).prop('disabled', true);
	} else {
		ele.prop('disabled', false);
	}
}

function makeFieldSelected(oField,fieldid,blockid)
{
	//crmv@106857 crmv@113771
	if (fieldid == 19) {	// uitype 220
		//return ModuleMakerFields.openAddTableFieldPopup(blockid,fieldid);
		return MlTableFieldConfig.openAddTableFieldPopup(blockid);
	}
	//crmv@106857e crmv@113771e
	if(gselected_fieldtype != '')
	{
		jQuery('#'+gselected_fieldtype).removeClass('customMnuSelected');
	}
	oField.className = 'customMnu customMnuSelected';
	gselected_fieldtype = oField.id;
	selFieldTypeLayoutEditor(fieldid,'','',blockid);
	document.getElementById('selectedfieldtype_'+blockid).value = fieldid;
}

function saveMobileInfo(module){
	jQuery('#status').show();
	var mobileFields = jQuery('#layoutMobileInfo form').serialize();
	VteJS_DialogBox.block();
	jQuery.ajax({
		url: 'index.php?module=Settings&action=SettingsAjax&file=LayoutBlockList&sub_mode=saveMobileInfo&parenttab=Settings&formodule='+module+'&ajax=true&mobile='+for_mobile,
		method: 'POST',
		data: mobileFields,
		success: function() {
			jQuery('#status').hide();
			VteJS_DialogBox.unblock();
		}
	});
}

function nav(val) {
	var theUrl = 'index.php?module=Settings&action=LayoutBlockList&parenttab=Settings&formodule='+val+'&mobile='+for_mobile;
	location.href = theUrl;
}

//crmv@106857
var MlTableFieldConfig = {

	openAddTableFieldPopup: function(blockno, editno) {
		hideFloatingDiv('addfield_'+blockno);
		var url = 'index.php?module=Settings&action=SettingsAjax&file=LayoutTableField&blockid='+blockno;
		if (typeof(editno) != 'undefined') url += '&fieldid='+editno;
		openPopup(url);
	},
	initAddTableFieldPopup: function(blockno, editfieldno, fieldinfo) {
		var cont = jQuery('#selectedcolumns');
		
		var height = (jQuery(document).height() - jQuery('#vte_menu_white').height() - jQuery('#Buttons_List_white').height() - jQuery('.crmvDivContent table').first().height() - 50) + 'px';
		cont.css({
			'height' : height
		});
		// initialize the sortable
		cont.sortable({
			axis: 'x',
			containment: 'parent',
			distance: 10,
			opacity: 0.8,
		});
		
		jQuery('#mmaker_newtablefield_blockno').val(blockno);
		jQuery('#mmaker_newtablefield_editfieldno').val(editfieldno);
		
		if (fieldinfo != '') {
			fieldinfo = JSON.parse(fieldinfo);
			TableFieldConfig.setValues(fieldinfo);
			//crmv@145432 length and decimals are readonly in editview
			if (jQuery('[name="newtablefieldprop_val_length"]').length > 0) {
				jQuery('#selectedcolumns [name="newtablefieldprop_val_length"]').prop('readonly', true);
				jQuery('#selectedcolumns [name="newtablefieldprop_val_length"]').addClass('dvtCellInfoOff');
				jQuery('#selectedcolumns [name="newtablefieldprop_val_decimals"]').prop('readonly', true);
				jQuery('#selectedcolumns [name="newtablefieldprop_val_decimals"]').addClass('dvtCellInfoOff');
			}
			//crmv@145432e
		}
	},
	cancelConfig: function() {
		var me = this;
		me.closeConfig();
	},
	closeConfig: function() {
		closePopup();
	},
	validateConfig: function() {
		return TableFieldConfig.validateConfig();
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
		
		jQuery.fancybox.showLoading();
		me.ajaxCall(editno ? 'editfield' : 'addfield', {
			blockno: blockno,
			addfieldno: fieldno,
			editfieldno: editno,
			properties: JSON.stringify(props),
		}, function(result){
			if(result == 'DUPLICATE'){
				jQuery.fancybox.hideLoading();
				alert(alert_arr.LABEL_ALREADY_EXISTS);
				return false;
			}else if(result == 'LENGTH_ERROR'){
				jQuery.fancybox.hideLoading();
				alert(alert_arr.LENGTH_OUT_OF_RANGE);
				return false;
			}else{
				parent.jQuery("#cfList").html(result);
				parent.LayoutEditor.ajaxCallVersion();
				jQuery.fancybox.hideLoading();
			}
			me.closeConfig();
		}, {
			includeForm: true,
		});
	},
	deleteConfig: function() {
		var me = this;
		var blockno = jQuery('#mmaker_newtablefield_blockno').val();
		var editno = jQuery('#mmaker_newtablefield_editfieldno').val();
		
		vteconfirm(alert_arr.SURE_TO_DELETE, function(yes) {
			if (yes) {
				jQuery.fancybox.showLoading();
				me.ajaxCall('deletefield', {
					blockno: blockno,
					editfieldno: editno,
				}, function(result){
					jQuery.fancybox.hideLoading();
					parent.jQuery("#cfList").html(result);
					parent.LayoutEditor.ajaxCallVersion();
					me.closeConfig();
				}, {
					includeForm: true,
				});
			}
		});
	},
	
	busy: false,
	isBusy: function() {
		return this.busy;
	},
	showBusy: function() {
		var me = this;
		me.busy = true;
	},
	hideBusy: function() {
		var me = this;
		me.busy = false;
	},
	ajaxCall: function(action, params, callback, options) {
		var me = this;
		
		// return if busy
		if (me.isBusy()) return;
		
		options = options || {};
		params = params || {};
		var url = "index.php?module=Settings&action=SettingsAjax&file=LayoutTableFieldAjax&ajax=1&subaction="+action;
		
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
				/*
				if (options.reloadList) {
					jQuery('#mmaker_div_relations').hide().html('');
					jQuery('#mmaker_div_relations').html(data).show();
				}*/
				if (typeof callback == 'function') callback(data);
			},
			error: function() {
				if (typeof callback == 'function') callback();
			}
		});
		
	},
}
//crmv@106857e