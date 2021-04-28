{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@29190 crmv@82419 crmv@82831 *}
{assign var="AUTOCOMPLETE_MODULE" value=$MODULE_NAME}
{if $AUTOCOMPLETE_MODULE eq ''}
	{assign var="AUTOCOMPLETE_MODULE" value=$MODULE}
{/if}
<script type="text/javascript">
	var autocomplete_module = '{$AUTOCOMPLETE_MODULE}';	//crmv@108227
	var autocomplete_include_script;
	{literal}
	//crmv@160843
	function changeReferenceType(obj,fromlink,fldname,popup_params) {
		reloadAutocomplete(fldname,fldname+"_display","module="+obj.value+popup_params);
		if (fromlink == 'qcreate') {
			document.QcEditView[fldname+'_display'].value="";
			document.QcEditView[fldname].value="";
			enableReferenceField(document.QcEditView[fldname+'_display']);
		} else {
			document.EditView[fldname+'_display'].value="";
			document.EditView[fldname].value="";
			jQuery("#qcform").html(''); // crmv@192033
			enableReferenceField(document.EditView[fldname+'_display']);
		}
	}
	//crmv@160843e
	function reloadAutocomplete(id,display,popup_params,sdk_popup_hidden_elements, useCategories) { // crmv@198024
		initAutocomplete(id,display,encodeURIComponent(popup_params),sdk_popup_hidden_elements, useCategories); // crmv@198024
	}
	function initAutocomplete(id,display,params,sdk_popup_hidden_elements, useCategories) { // crmv@198024

		if (jQuery.type(id) == 'string') {
			var id_str = id;
			var id_obj = jQuery('#'+id);
		} else if (jQuery.type(id) == 'object') {
			var id_str = jQuery(id).attr('name');
			var id_obj = jQuery(id);
		}
		if (jQuery.type(display) == 'string') {
			var display_str = display;
			var display_obj = jQuery('#'+display);
		} else if (jQuery.type(display) == 'object') {
			var display_str = jQuery(display).attr('name');
			var display_obj = jQuery(display);
		}
		//crmv@92272 crmv@108227 crmv@160843
		if (autocomplete_module == 'Calendar' && id_str == 'parent_id') var reference_field_type = 'parent_type'; else var reference_field_type = id_str+'_type';
		if (jQuery('#'+reference_field_type).val() == 'Other') {
			id_obj.parent('div').hide();
			jQuery('#div_other_'+id_str).show();
			
			jQuery('.editoptions[fieldname="other_'+id_str+'"]').show();
			jQuery('.editoptions[fieldname="other_'+id_str+'"]').find('.populateField').val('');
			jQuery('#other_'+id_str).val('');
		} else {
			jQuery('#div_other_'+id_str).hide();
			id_obj.parent('div').show();
			
			jQuery('.editoptions[fieldname="other_'+id_str+'"]').hide();
		}
		//crmv@92272e crmv@108227e crmv@160843e
		
		var empty_str = '{/literal}{"LBL_SEARCH_STRING"|getTranslatedString}{literal}';
		
		display_obj
			.focus(function(){
				var term = this.value;
				if ( term.length == 0 || this.value == empty_str) {
					this.value = '';
				}
			})
			.blur(function(){
				var term = this.value;
				if ( term.length == 0 ) {
					this.value = empty_str;
				}
			})
			.vteautocomplete({ // crmv@198024
				useCategories: useCategories, // crmv@198024
				source: function( request, response ) {
					// crmv@91082
					if (!SessionValidator.check()){
						SessionValidator.showLogin();
						return false;
					}
					// crmv@91082e
					jQuery.getJSON( "index.php?module=SDK&action=SDKAjax&file=src/Reference/Autocomplete", {
						term: request.term,
						field: id_str,
						params: params
					}, function(data) {
						var url = "index.php?"+decodeURIComponent(params);
						if (sdk_popup_hidden_elements != '') {
        					for (var label in sdk_popup_hidden_elements) {  
								url += "&"+label+"="+eval(sdk_popup_hidden_elements[label]);
							} 
						}
						jQuery.getJSON(url, {
							autocomplete: 'yes',
							autocomplete_select: data[0],
							autocomplete_where: data[1] //crmv@42329
						}, response );
					});
				},
				open: function() {
					if (typeof window.findZMax == 'function') {
						var zmax = findZMax();
						jQuery(this).vteautocomplete('widget').css('z-index', zmax+2); // crmv@198024
					}
					return false;
				},
				search: function() {
					// custom minLength
					var term = this.value;
					if ( term.length < 3 ) {
						return false;
					}
				},
				focus: function() {
					// prevent value inserted on focus
					return false;
				},
				select: function( event, ui, ret_funct ) {
					if (ui.item.return_function_file != '') {
						autocomplete_include_script = 'yes';
						jQuery.getScript(ui.item.return_function_file, function(data){
							eval(data);
							eval(ui.item.return_function);
							jQuery.getScript('modules/{/literal}{$AUTOCOMPLETE_MODULE}/{$AUTOCOMPLETE_MODULE}{literal}.js', function(data){eval(data);});
							autocomplete_include_script = 'no';
						});
					}
					return false;
				},
			}
		);
	}
	//crmv@31171
	function initAutocompleteUG(type,id,display,values,label,form) {
		var empty_str = '{/literal}{"LBL_SEARCH_STRING"|getTranslatedString}{literal}';
		var values = eval("("+values+")");
		var source = new Array();
		var curr_form = form;
		//crmv@36944
		if (values == null){
			return;
		}
		//crmv@36944 e		
		jQuery.each(values, function(index,obj) {
			jQuery.each(obj, function(user) {
				var tmp = {'id':index,'label':user};
				source.push(tmp) 
			});
		});
		source.sort(function(a,b){
			if (a.label < b.label){
				return -1;
			}
			if (a.label > b.label){
				return 1;
			}
			return 0;
		});
		jQuery('#'+display)
			.focus(function(){
				var term = this.value;
				if ( term.length == 0 || this.value == empty_str) {
					this.value = '';
				}
			})
			.blur(function(){
				var term = this.value;
				if ( term.length == 0 ) {
					this.value = empty_str;
				}
			})
			.autocomplete({
				minLength: 0,
				source: source,
				// crmv@105046
				open: function() {
					if (typeof window.findZMax == 'function') {
						var zmax = findZMax();
						jQuery(this).autocomplete('widget').css('z-index', zmax+2);
					}
					return false;
				},
				// crmv@105046e
				select: function( event, ui ) {
					if (curr_form != undefined) {
						var form = curr_form;
					} else {
						var formName = getReturnFormName();
						var form = getReturnForm(formName);
					}
					form.elements[id].value = ui.item.id;
					form.elements[display].value = ui.item.label;
					if (label != undefined) {
						form.elements['hdtxt_'+label].value = ui.item.label;
					}
					//crmv@34104
					var mass_edit_check = id+'_mass_edit_check';
					if (id == 'assigned_group_id') {
						mass_edit_check = 'assigned_user_id_mass_edit_check';
					}
					//crmv@34104e
					disableReferenceField(form.elements[display],form.elements[id],form.elements[mass_edit_check]);
					return false;
				}
			}
		);
	}
	function toggleAutocompleteList(display) {
		if ( jQuery("#"+display).autocomplete( "widget" ).is( ":visible" ) ) {
			jQuery("#"+display).autocomplete( "close" );
			return;
		}
		//jQuery( this ).blur();	//crmv@44794
		jQuery("#"+display).autocomplete("search","");
	}
	function closeAutocompleteList(display) {
		jQuery("#"+display).autocomplete( "close" );
	}
	//crmv@31171e
	function enableReferenceField(field) {
		//crmv@34627
		if (field.name == 'report_display') {
			var module = document.forms['EditView'].module.value;
			if (module != undefined && module != 'undefined' && module == 'CustomView') {
				reloadColumns(document.forms['EditView'].cvmodule.value,document.forms['EditView'].report.value);
			}
		}
		//crmv@34627e
		// crmv@198024
		if (field.name == 'confproductid_display' && typeof window.reload_variant_block == 'function') {
			reload_variant_block(0, 'Products', 'confproductid');
		}
		// crmv@198024e
		field.readOnly = false;
		if (jQuery(field).parent('div').length > 0) {
			var div = jQuery(field).parent('div');
			div.attr('class','dvtCellInfoOn');
			div.focusin(function(){
				div.attr('class','dvtCellInfoOn');
			}).focusout(function(){
				div.attr('class','dvtCellInfo');
			});
		}
		jQuery(field).focus();
	}
	function disableReferenceField(field,realfield,checkbox) {	//crmv@32341
		//crmv@34627
		if (field.name == 'report_display') {
			var module = document.forms['EditView'].module.value;
			if (module != undefined && module != 'undefined' && module == 'CustomView') {
				reloadColumns(document.forms['EditView'].cvmodule.value,document.forms['EditView'].report.value);
			}
		}
		//crmv@34627e
		jQuery(field).attr('readonly','readonly');
		if (jQuery(field).parent('div').length > 0) {
			var div = jQuery(field).parent('div');
			div.attr('class','dvtCellInfoOff');
			div.focusin(function(){
				div.attr('class','dvtCellInfoOff');
			}).focusout(function(){
				div.attr('class','dvtCellInfoOff');
			});
		}
		jQuery(field).blur();
		if(checkbox && checkbox != undefined && checkbox != 'undefined') checkbox.checked = true;	//crmv@32341
	}
	function resetReferenceField(field) {
		field.readOnly = false;
		if (jQuery(field).parent('div').length > 0) {
			var div = jQuery(field).parent('div');
			div.attr('class','dvtCellInfo');
			div.focusin(function(){
				div.attr('class','dvtCellInfoOn');
			}).focusout(function(){
				div.attr('class','dvtCellInfo');
			});
		}
		field.value = '{/literal}{"LBL_SEARCH_STRING"|getTranslatedString}{literal}';
	}
	{/literal}
</script>
{* crmv@29190e *}