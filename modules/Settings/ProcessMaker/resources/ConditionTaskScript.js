/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@92272 crmv@96450 crmv@104180 crmv@112297 crmv@115268 */
 
if (typeof(ConditionTaskScript) == 'undefined') {
	ConditionTaskScript = {
		init: function(processmakerid,elementId,params){
			var context = jQuery('form[shape-id="'+elementId+'"]');
			var moduleName = ConditionTaskScript.getModule(jQuery('[name="moduleName"]',context).val());
			var metaId = ConditionTaskScript.getMetaId(jQuery('[name="moduleName"]',context).val());
			var processmakerId = processmakerid;
			if (typeof(params) == 'undefined') var params = {};
			jQuery('[name="moduleName"]',context).change(function(){
				jQuery('#save_conditions',context).html('');
				moduleName = ConditionTaskScript.getModule(this.value);
				metaId = ConditionTaskScript.getMetaId(this.value);
				jQuery('#group_conditions_add',context).hide();
				if (this.value != '') {
					if (moduleName == 'DynaForm') {
						// crmv@187711 code removed
						GroupConditions.init(jQuery, moduleName, 'save_conditions', context, null, {'otherParams':{'processmakerId':processmakerId,'metaId':metaId}});
					} else {
						// crmv@187711 code removed
						GroupConditions.init(jQuery, moduleName, 'save_conditions', context, null, params);
					}
				}
			});
			if (moduleName != '') {
				if (jQuery('#conditions',context).html() != '') var conditions = JSON.parse(jQuery('#conditions',context).html()); else var conditions = null;
				if (moduleName == 'DynaForm') {
					// crmv@187711 code removed
					GroupConditions.init(jQuery, moduleName, 'save_conditions', context, conditions, {'otherParams':{'processmakerId':processmakerId,'metaId':metaId}});
				} else {
					// crmv@187711 code removed
					GroupConditions.init(jQuery, moduleName, 'save_conditions', context, conditions, params);
				}
			}
			//crmv@97575
			selectModuleName = function(value) {
				if (value == 'ON_SUBPROCESS') {
					jQuery('[name="moduleName"]',context).val('');
					jQuery('[name="moduleName"]',context).prop('disabled', 'disabled');
					jQuery('[name="moduleName"]',context).addClass('disabled');
				} else {
					jQuery('[name="moduleName"]',context).prop('disabled', false);
					jQuery('[name="moduleName"]',context).removeClass('disabled');
				}
			}
			jQuery('[name="execution_condition"]',context).change(function(){
				selectModuleName(this.value);
			});
			selectModuleName(jQuery('[name="execution_condition"]:checked',context).val());
			//crmv@97575e
		},
		getModule: function(str){
			if (str.indexOf(':') > -1) {
				var res = str.split(':');
				str = res[1];
			}
			return str;
		},
		getMetaId: function(str){
			if (str.indexOf(':') > -1) {
				var res = str.split(':');
				str = res[0];
			}
			return str;
		}
	}
}

if (typeof(ActionConditionScript) == 'undefined') {
	ActionConditionScript = {
		
		init: function(processmakerid, elementId, metaId, fieldName, callback) {	//crmv@140949
			var me = this;
			
			var context = jQuery('#actionform'),
				cond = jQuery('#conditions',context).html();
				
			var conditions = (cond != '' ? JSON.parse(cond) : null);
			var oParams = {
				processmakerId: processmakerid,
				metaId: metaId,
				fieldName: fieldName,
				dynaFormConditional: true,
				cycle: true
			}

			//crmv@195745 crmv@203075 crmv@206203
			if (fieldName == 'prodblock') {
				GroupConditions.init(jQuery, 'ProductsBlock', 'save_conditions', context, conditions, {'otherParams':oParams}, callback);
			} else if (jQuery('input[name=action_type]').val() == 'CycleRelated') {
				GroupConditions.init(jQuery, jQuery("#cycle_fieldname").val(), 'save_conditions', context, conditions, {'otherParams':oParams}, callback);	//crmv@140949
			} else {
				GroupConditions.init(jQuery, 'TableField', 'save_conditions', context, conditions, {'otherParams':oParams}, callback);	//crmv@140949
			}
			//crmv@195745e crmv@203075 crmv@206203e
		}
	}
}


// crmv@200009
function getRelatedListModules() {
	if (!window.rel_array) return;
    
	var initialModule = jQuery( "#select_id" ).val(),
		new_value = window.rel_array[initialModule];

    var el = jQuery("#related_mod");
    el.empty(); // remove old options
    jQuery.each(new_value, function(key,value) {
        el.append(jQuery("<option></option>").attr("value", value).text(value));
	});
}
// crmv@200009e