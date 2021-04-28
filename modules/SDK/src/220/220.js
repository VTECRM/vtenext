/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@104180 crmv@106857 crmv@112297 */

if (typeof window.TableField == 'undefined') {

var TableField = {
	
	busy: false,
	
	fieldInfo: {},
	
	showBusy: function() {
		this.busy = true;
	},
	
	hideBusy: function() {
		this.busy = false;
	},
	
	setFieldInfo: function(fieldname, columns) {
		this.fieldInfo[fieldname] = columns;
	},
	
	addRow: function(fieldname, duplicate_rowno, after) {
		var me = this,
			info = this.fieldInfo[fieldname],
			cont = jQuery('table[name='+fieldname+']'),
			rows = cont.find('.tablefield_rows'),
			rownocont = jQuery('input[name='+fieldname+'_lastrowno]'),
			rowno = parseInt(rownocont.val());
		
		var params = {
			fieldname: fieldname,
			rowno: rowno,
		}
		if (typeof(after) != 'undefined') {
			var duplicate_form = {};
			jQuery.each(jQuery(after).find(':input').serializeArray(), function(){
				duplicate_form[this.name] = this.value;
			});
			params['duplicate_rowno'] = duplicate_rowno;
			params['duplicate_form'] = JSON.stringify(duplicate_form);
		}
		me.ajaxCall('getrow', params, function(res) {
			res = jQuery.parseJSON(res);
	
			if (typeof(after) != 'undefined') {
				jQuery(after).after(res.html);
			} else {
				rows.append(res.html);
			}

			// increment rowno
			rownocont.val(rowno + 1);
			
			// set the fake field value, for the mandatory check
			jQuery('input[name='+fieldname+']').val('1');

			// add the field information to to array for validation
			me.addValidationInfo(fieldname, rowno, JSON.stringify(res.typeofdata));
			
			me.reloadSequence(fieldname);
			me.showUpDownRow(fieldname);
			
			if (jQuery('#enable_dfconditionals').val() == '1') {
				DynaFormScript.initEditViewConditionals(jQuery('input[name="record"]').val(),jQuery('#df_fields').text(),true);
			}
			if (jQuery('#enable_conditionals').val() == '1') {
				ProcessScript.reloadForm();
			}
		});
	},

	addValidationInfo: function(fname, rowno, typeofdata) {
		var me = this,
			info = this.fieldInfo[fname],
			typeofdata = jQuery.parseJSON(typeofdata);

		if (!info || !window.fieldname) return;
		
		jQuery.each(info, function(idx, colinfo) {
			if (fieldname.indexOf(fname+'_'+colinfo.fieldname+'_'+rowno) == -1) {
				fieldname.push(fname+'_'+colinfo.fieldname+'_'+rowno);
				fieldlabel.push(colinfo.label);
				fielduitype.push(parseInt(colinfo.uitype));
				fielddatatype.push(typeofdata[fname+'_'+colinfo.fieldname+'_'+rowno]);
				fieldwstype.push(colinfo.fieldwstype);
			}
		});
	},
	
	removeValidationInfo: function(fname, rowno) {
		var me = this,
			info = this.fieldInfo[fname];	
			
		if (!info || !window.fieldname) return;
		
		jQuery.each(info, function(idx, colinfo) {
			var idx = fieldname.indexOf(fname+'_'+colinfo.fieldname+'_'+rowno);
			if (idx >= 0) {
				fieldname.splice(idx, 1);
				fieldlabel.splice(idx, 1);
				fielddatatype.splice(idx, 1);
				fielduitype.splice(idx, 1);
				fieldwstype.splice(idx, 1);
			}
		});
	},
	
	deleteRow: function(self, fieldname, rowno) {
		var me = this,
			cont = jQuery('table[name='+fieldname+']'),
			rows = cont.find('.tablefield_rows'),
			row = jQuery(self).closest('tr');
		
		row.remove();
		
		if (rows.get(0).rows.length == 0) {
			// set the fake field value, for the mandatory check
			jQuery('input[name='+fieldname+']').val('');
		}
		
		// remove the info for the validation
		me.removeValidationInfo(fieldname, rowno);
		
		me.reloadSequence(fieldname);
		me.showUpDownRow(fieldname);
		
		if (jQuery('#enable_dfconditionals').val() == '1') {
			DynaFormScript.initEditViewConditionals(jQuery('input[name="record"]').val(),jQuery('#df_fields').text(),true);
		}
		if (jQuery('#enable_conditionals').val() == '1') {
			ProcessScript.reloadForm();
		}
	},
	
	duplicateRow: function(self, fieldname, rowno) {
		var me = this;
		me.addRow(fieldname, rowno, jQuery(self).closest('tr'));
	},
	showUpDownRow: function(fieldname) {
		var me = this,
			cont = jQuery('table[name='+fieldname+']'),
			rows = cont.find('.tablefield_rows');
		
		jQuery.each(rows.find('tr'), function(seq,row){
			if (jQuery(row).prev('tr').length == 0) jQuery(row).find('.tablefield_row_icon_up').css('visibility','hidden'); else jQuery(row).find('.tablefield_row_icon_up').css('visibility','visible');
			if (jQuery(row).next('tr').length == 0) jQuery(row).find('.tablefield_row_icon_down').css('visibility','hidden'); else jQuery(row).find('.tablefield_row_icon_down').css('visibility','visible');
		});
	},
	moveUpDownRow: function(mode, self, fieldname, rowno) {
		var me = this,
			cont = jQuery('table[name='+fieldname+']'),
			rows = cont.find('.tablefield_rows'),
			row = jQuery(self).closest('tr');
		
		if (mode == 'UP') {
			row.insertBefore(row.prev());
		} else if (mode == 'DOWN') {
			row.insertAfter(row.next());
		}
		
		me.reloadSequence(fieldname);
		me.showUpDownRow(fieldname);
	},
	reloadSequence: function(fieldname) {
		var me = this,
			cont = jQuery('table[name='+fieldname+']'),
			rows = cont.find('.tablefield_rows');
		
		jQuery.each(rows.find('tr'), function(seq,row){
			jQuery(row).find('.tablefield_row_seq').val(seq);
		});
	},
	
	ajaxCall: function(action, params, callback, options) {
		var me = this;
		
		// return if busy
		if (me.busy) return;
		
		options = options || {};
		params = jQuery.extend({
			recordid: document.EditView && document.EditView.record.value,
			processid: jQuery('#processmaker').val(),
			running_process: jQuery('#running_process').val(),
		}, params || {});
		var url = "index.php?module=SDK&action=SDKAjax&file=src/220/220Ajax&subaction="+action;
		
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
	
}

}