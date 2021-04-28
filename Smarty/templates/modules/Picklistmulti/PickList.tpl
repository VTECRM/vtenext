{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@192033 *}

{* crmv@193430 - jquery ui is already included *}
<link rel="stylesheet" type="text/css" media="screen" href="include/js/jquery_plugins/jqgrid/css/ui.jqgrid.css" />
<script language="JavaScript" type="text/javascript" src="include/js/jquery_plugins/jqgrid/i18n/grid.locale-{$LANGUAGE_SUFFIX}.js"></script>
<script language="JavaScript" type="text/javascript" src="include/js/jquery_plugins/jqgrid/jqgrid.js"></script>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
<tbody><tr>
	<td valign="top"></td>
	<td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->

	<div align=center>
		{include file='SetMenu.tpl'}
		{include file='Buttons_List.tpl'} {* crmv@30683 *}
		<!-- DISPLAY -->
		<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
		<tr>
			<td width=50 rowspan=2 valign=top><img src="{'picklist_multilanguage.gif'|resourcever}" width="48" height="48" border=0 ></td>
			{if $smarty.request.module_manager eq 'yes'}
			<td class="heading2" valign="bottom">
				<b><a href="index.php?module=Settings&action=ModuleManager&parenttab=Settings">{$MOD.VTLIB_LBL_MODULE_MANAGER}</a>
				&gt;<a href="index.php?module=Settings&action=ModuleManager&module_settings=true&formodule={$MODULE}&parenttab=Settings">{if $APP.$MODULE } {$APP.$MODULE} {elseif $MOD.$MODULE} {$MOD.$MODULE} {else} {$MODULE} {/if}</a> &gt;
				{$MOD.LBL_PICKLIST_EDITOR_MULTI}</b>
			</td>
			{else}
			<td class=heading2 valign=bottom><b>{$MOD.LBL_SETTINGS} > {$MOD.LBL_PICKLIST_EDITOR_MULTI}</b></td> <!-- crmv@30683 -->
			{/if}
		</tr>
		<tr>
			<td valign=top class="small">{$MOD.LBL_PICKLIST_DESCRIPTION_MULTI}</td>
		</tr>
		</table>

		<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
			<tr class="tableHeading">
				<td class="small" width="20%" nowrap>
					<strong>{$MOD.LBL_SELECT_MODULE}</strong>&nbsp;&nbsp;
				</td>
				<td align="left" width="30%">
					<div class="dvtCellInfo">
						{if $smarty.request.module_manager eq 'yes'}
							{$MODULE|getTranslatedString:$MODULE}
							<input type="hidden" name="pickmodule" id="pickmodule" value="{$MODULE}">
						{else}
							<select name="pickmodule" id="pickmodule" class="detailedViewTextBox" onChange="changeModule();">
							{foreach key=modulelabel item=module from=$MODULE_LIST}
								{assign var="modulelabel" value=$module|@getTranslatedString:$module}	<!-- crmv@16886 -->
								{if $MODULE eq $module}
									<option value="{$module}" selected>{$modulelabel}</option>
								{else}
									<option value="{$module}">{$modulelabel}</option>
								{/if}
							{/foreach}
							</select>
						{/if}
					</div>
				</td>
				<td colspan = "2" class="small" align="right">&nbsp;</td>
			</tr>
			<tr class="tableHeading">
				<td class="small" width="20%" nowrap>
					<strong>{$MOD.LBL_SELECT_PICKLIST}</strong>&nbsp;&nbsp;
				</td>
				<td width="30%" align="left">
					<div class="dvtCellInfo">
						<select name="picklist_field" id="picklist_field" class="small detailedViewTextBox" onChange="changeField();" style="font-weight: normal;">
							<option value="">{$APP.LBL_NONE}</option>
							{foreach key=field_name item=field_label from=$FIELD_LIST}
								<option value="{$field_name}" {if ($FLD_NAME eq $field_name)} selected {/if}>{$field_label}</option>
							{/foreach}
						</select>
					</div>
				</td>
				<td class="small" align="right">
					<div id="info_panel" align="center">
					</div>
				</td>
				<td class="small" align="right">
					<font color="red">* {$MOD_PICKLIST.LBL_DISPLAYED_VALUES}</font>
				</td>				
			</tr>
			<tr>
				<td colspan = "4">
					<table id="table_picklist"></table> 
					<div id="table_picklist_nav"></div>
				</td>
			</tr>
		</table>
		{include file='Settings/ScrollTop.tpl'}
	</div>
	</td>
</tr>
</tbody>
</table>
<script>
fieldname = '{$FLD_NAME}';
fieldmodule = '{$MODULE}';
fieldlabel = '{$FLD_LABEL}';
function changeModule(){ldelim}
	var module=getObj('pickmodule').value;
	var result = getFile("index.php?module=Picklistmulti&action=PicklistmultiAjax&file=LoadField&module_name="+encodeURIComponent(module))
	 result = eval('(' + result + ')');
	if (result == null){ldelim}
		 rm_all_opt('picklist_field');
		 add_opt('picklist_field',"{$APP.LBL_NONE}","");
		fieldname = "";
		fieldmodule = "";
		fieldlabel = "";
		{literal}
		jQuery("#table_picklist").jqGrid('setGridParam',{editurl:"index.php?module=Picklistmulti&action=PicklistmultiAjax&file=edit&field="+fieldname+"&field_module="+fieldmodule,url:'index.php?module=Picklistmulti&action=PicklistmultiAjax&file=load&field='+fieldname+'&field_module='+fieldmodule}).jqGrid('setCaption',fieldlabel).trigger('reloadGrid');
		{/literal}			
	{rdelim}
	else {ldelim}
		var field_obj = getObj('picklist_field');
		resetpicklist('picklist_field');
		for (var key in result){ldelim}
	    	add_opt('picklist_field',result[key],key);
	    {rdelim}
	    changeField();
	{rdelim}
{rdelim}
/**
 * this function is used to update the page with the new field selected
 */
function changeField(){ldelim}
	var module_obj=getObj('pickmodule');
	var field_obj = getObj('picklist_field');
	fieldname = field_obj.value;
	fieldmodule = module_obj.value;
	fieldlabel = field_obj.options[field_obj.selectedIndex].text;
	{literal}
	jQuery("#table_picklist").jqGrid('setGridParam',{editurl:"index.php?module=Picklistmulti&action=PicklistmultiAjax&file=edit&field="+fieldname+"&field_module="+fieldmodule,url:'index.php?module=Picklistmulti&action=PicklistmultiAjax&file=load&field='+fieldname+'&field_module='+fieldmodule}).jqGrid('setCaption',fieldlabel).trigger('reloadGrid');
	{/literal}
{rdelim}
function init_table(){ldelim}
	jQuery("#table_picklist").jqGrid({ldelim}
		url:'index.php?module=Picklistmulti&action=PicklistmultiAjax&file=load&field='+fieldname+'&field_module='+fieldmodule, 
		datatype: "json", 
	   	colNames:[{$PICKLIST_COLUMN_NAMES}],
	   	colModel:[{counter start=1 skip=1 print=false assign=j}{foreach item=data from=$PICKLIST_COLUMNS}{ldelim}{counter start=1 skip=1 print=false assign=i}{foreach key=property item=value from=$data name=loop}{if $i neq 1},{/if}{$property}:{if $property eq 'hidden' or $property eq 'sortable' or $property eq 'editable'}{$value}{elseif $property eq 'editoptions' or $property eq 'editrules' or $property eq 'formoptions'}{ldelim}{$value}{rdelim}{else}'{$value}'{/if}{counter}{/foreach}{rdelim}{if ($j-1) neq $data|@count},{/if}{/foreach}],
		autowidth: true,
		pager: '#table_picklist_nav', 
		rowNum:{$MAX_ROWS}, 
		rowList:[{$MAX_ROWS},{$MAX_ROWS*2},{$MAX_ROWS*3},'{$APP.SHOW_ALL}'], 
	   	editurl:"index.php?module=Picklistmulti&action=PicklistmultiAjax&file=edit&field="+fieldname+"&field_module="+fieldmodule, 
	   	caption: fieldlabel,
	   	multiselect: true, 
	   	drag:true,
	   	jqModal:true,
	{rdelim});
//	jQuery('#table_picklist_nav_right').remove();
//	jQuery('#table_picklist_nav_center').remove();
	jQuery('#table_picklist').navGrid('#table_picklist_nav',
		{ldelim}refresh:true,search:false{rdelim}, //options
		{ldelim}beforeSubmit:before,afterSubmit:after,bottominfo:'{$MOD_PICKLIST.LBL_MANDATORY}'{rdelim},	// edit options 
		{ldelim}beforeSubmit:before,afterSubmit:after,bottominfo:'{$MOD_PICKLIST.LBL_MANDATORY}'{rdelim},	// add options
		{ldelim}jqModal:true,afterSubmit:after,closeAfterDelete:true{rdelim},	//del options
		{ldelim}{rdelim}	//search options
		);
	function after(response, postdata){ldelim}
		jQuery('#status').hide();
		var status = eval('(' + response.responseText + ')');
		if (status['status']){ldelim}
			message = '{$MOD_PICKLIST.LBL_SUCCESS}';
			color = 'green';
			jQuery('#info_panel').addClass("ui-state-highlight");
			jQuery('#info_panel').html("<span class=\"ui-icon ui-icon-info\" style=\"float: left; margin-right: .3em;\"></span>");
		{rdelim}	
		else {ldelim}
			message = status['message'];
			color = 'red';	
			jQuery('#info_panel').addClass("ui-state-error");		
			jQuery('#info_panel').html("<span class=\"ui-icon ui-icon-alert\" style=\"float: left; margin-right: .3em;\"></span>");
		{rdelim}	
		jQuery('#delmodtable_picklist').fadeOut('fast');
		jQuery('#jqmOverlay').fadeOut('fast');
		jQuery('<p>'+message+'</p>').appendTo('#info_panel');
		jQuery('#info_panel').fadeIn("slow",function () {ldelim}jQuery('#info_panel').fadeOut(4000);{rdelim});		
		return [status['status'],message,''];
	{rdelim}
	function before(postdata, formid){ldelim}
		jQuery('#status').show();
		return [true,"",""]
	{rdelim}	
	function checkcode(value, colname){ldelim}
		var code_system = jQuery("input#id_g", document.FormPost).val();
		var mode;
		if (code_system == '_empty')
			mode = 'add';
		else	
			mode='edit';
		jQuery('#status').show();
		data = getFile("index.php?module=Picklistmulti&action=PicklistmultiAjax&file=edit&field="+fieldname+"&field_module="+fieldmodule+"&oper=checkcode&value="+value+"&code_system="+code_system+"&mode="+mode);
		var result = eval('(' + data + ')');
		jQuery('#status').hide();
		if (result['status'])
			return [true,"",""];
		else
			return [false,result['message'],""];
	{rdelim}	
{rdelim}
</script>
<script>
{literal}
jQuery(document).ready(function() {
	init_table();
});
{/literal}
</script>