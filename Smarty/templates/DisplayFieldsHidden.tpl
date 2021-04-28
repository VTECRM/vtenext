{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@181170 *}

{* crmv@sdk-18509 *}
{if $SDK->isUitype($uitype) eq 'true'}
	{assign var="sdk_mode" value="edit"}
	{assign var="sdk_file" value=$SDK->getUitypeFile('tpl',$sdk_mode,$uitype)}
	{if $sdk_file neq ''}
		{include file=$sdk_file}
	{/if}
{* crmv@sdk-18509 e *}
{* vtlib customization *}
{elseif $uitype eq '10'}
	{if count($fldlabel.options) eq 1}
		{assign var="use_parentmodule" value=$fldlabel.options.0}
		<input type='hidden' name="{$fldname}_type" value="{$use_parentmodule}">
	{else}
		{foreach item=option from=$fldlabel.options}
			{if $fldlabel.selected == $option}
				{assign var=relmodule_value value="$option"}
			{/if}
		{/foreach}
		<input type="hidden" id="{$fldname}_type" name="{$fldname}_type" value="{$relmodule_value}">
	{/if}
	<input id="{$fldname}" name="{$fldname}" type="hidden" value="{$fldvalue.entityid}" id="{$fldname}">
{* END *}
{elseif $uitype eq 3 || $uitype eq 4}<!-- Non Editable field, only configured value will be loaded -->
	{if $hide_number eq 'yes'}
		<input type="hidden" tabindex="{$vt_tab}" name="{$fldname}" id ="{$fldname}" {if $MODE eq 'edit'} value="{$fldvalue}" {else} value="{$inv_no}" {/if}>
	{else}
		<input type="hidden" tabindex="{$vt_tab}" name="{$fldname}" id ="{$fldname}" {if $MODE eq 'edit'} value="{$fldvalue}" {assign var="value_backup" value="$fldvalue"} {else} value="{$inv_no}" {assign var="value_backup" value="$inv_no"} {/if}>
	{/if}
 <!--   //crmv@7231 - crmv@7216 crmv@7220 crmv@18338-->                               
{elseif $uitype eq 11 || $uitype eq 1 || $uitype eq 13 || $uitype eq 7 || $uitype eq 9 || $uitype eq 1112 || $uitype eq 1013 || $uitype eq 1014 || $uitype eq 1021 || $uitype eq 1020}
	{if $fldname eq 'if_yes_products' && $MODULE eq 'Visitreport'}
			<input type="hidden" tabindex="{$vt_tab}" name="{$fldname}" id ="{$fldname}" value="{$fldvalue|escape}">
	{elseif $fldname eq 'account_nr' && $MODULE eq 'Visitreport' }
			<input  type="hidden" tabindex="{$vt_tab}" name="{$fldname}" id ="{$fldname}" value="{$fldvalue|escape}">
	{else}
		{if $uitype eq 1112 && ($fldvalue neq '' && $fldvalue neq '--None--') }
			<input type="hidden"  name="{$fldname}" id ="{$fldname}" value="{$fldvalue}">
		{else}
			<input type="hidden" tabindex="{$vt_tab}" name="{$fldname}" id ="{$fldname}" value="{$fldvalue}" >
		{/if}
	{/if}
{elseif $uitype eq 19}
	<!--crmv@7207--> {$fldvalue}
	<div style="display:none;">
		<textarea readonly tabindex="{$vt_tab}" name="{$fldname}" cols="90" rows="8">{$fldvalue}</textarea>
	</div>
	{if $fldlabel eq $MOD.Solution}
		<input type = "hidden" name="helpdesk_solution" value = '{$fldvalue}'>
	{/if}			
{elseif $uitype eq 21}
	<div style="display:none;">
		<textarea  value="{$fldvalue}" name="{$fldname}" tabindex="{$vt_tab}"  rows=2>{$fldvalue}</textarea>
	</div>
{elseif $uitype eq 25}
	<div style="display:none;">
		<textarea  style="width:40%" value="{$fldvalue|escape}" name="{$fldname}" tabindex="{$vt_tab}">{$fldvalue|escape}</textarea>
	</div>
	<input type="hidden" name="projects_ids" value="{$PROJECTS_IDS}">
{elseif $uitype eq 15 || $uitype eq 16 || $uitype eq 1015}	{* <!-- DS-ED VlMe 31.3.2008 - add uitype 504 --> *}
	{foreach item=arr from=$fldvalue}
		{if $arr[2] eq 'selected'}
			{assign var="value_15" value=$arr[1]}
			{assign var="value_15_label" value=$arr[0]}
		{/if}	
	{/foreach}
	<input  type="hidden" name="{$fldname}" tabindex="{$vt_tab}" value="{$value_15}">
{elseif $uitype eq 33}
	<div id='select{$fldname}' style='display:none;'>
		<select MULTIPLE name="{$fldname}[]" size="4" style="width:160px;" tabindex="{$vt_tab}">
		{foreach item=arr from=$fldvalue}
			{if $arr[2] eq 'selected'}
				{if $value_33_label eq ''}
					{assign var="value_33_label" value=$arr[0]}
				{else}	
					{assign var="value_33_label" value=$value_33_label|cat:","|cat:$arr[0]}
				{/if}	
			{/if}
			<option value="{$arr[1]}" {$arr[2]}>{$arr[0]}</option>
		{/foreach}                       
		</select>  
	</div>
{elseif $uitype eq 53}
	{assign var=check value=1}
	{foreach key=key_one item=arr from=$fldvalue}
		{foreach key=sel_value item=value from=$arr}
			{if $value ne ''}
				{assign var=check value=$check*0}
			{else}
				{assign var=check value=$check*1}
			{/if}
		{/foreach}
	{/foreach}

	{if $check eq 0}
		<input type="hidden" name="assigntype" value="U" onclick="toggleAssignType(this.value)" >
	{elseif $secondvalue neq ''}
		<input type="hidden" name="assigntype" value="T" onclick="toggleAssignType(this.value)">
	{/if}

	<span id="assign_user" style="display: none;">
		<select name="{$fldname}">
			{foreach key=key_one item=arr from=$fldvalue}
				{foreach key=sel_value item=value from=$arr}
					<option value="{$key_one}" {$value}>{$sel_value}</option>
				{/foreach}
			{/foreach}
		</select>
	</span>

	{if $secondvalue neq ''}
		<span id="assign_team" style="display: none;">
			<select name="assigned_group_id">';
				{foreach key=key_one item=arr from=$secondvalue}
					{foreach key=sel_value item=value from=$arr}
						<option value="{$key_one}" {$value}>{$sel_value}</option>
					{/foreach}
				{/foreach}
			</select>
		</span>
	{/if}
{* crmv@101683 *}
{elseif $uitype eq 52 || $uitype eq 77 || $uitype eq 54}
	{foreach key=key_one item=arr from=$fldvalue}
		{foreach key=sel_value item=value from=$arr}
			{if $value eq 'selected'}
				{assign var="selected_value" value=$key_one}
				{assign var="selected_label" value=$sel_value}
			{/if}
		{/foreach}
	{/foreach}
	<input name="{$fldname}" type="hidden" value="{$selected_value}">
{* crmv@101683e *}
{elseif $uitype eq 17}
	<input style="width:74%;" type="hidden" tabindex="{$vt_tab}" name="{$fldname}" style="border:1px solid #bababa;" size="27" value="{$fldvalue}">
{elseif $uitype eq 85}
{elseif $uitype eq 71 || $uitype eq 72}
	<input name="{$fldname}" tabindex="{$vt_tab}" type="hidden"   value="{$fldvalue}">
{elseif $uitype eq 56}
	<div style="display:none">
		<!-- crmv@14511 -->
		{if $fldname eq 'notime' && $ACTIVITY_MODE eq 'Events'}
			{if $fldvalue eq 1}
				<input readonly name="{$fldname}" type="checkbox" tabindex="{$vt_tab}" checked>
			{else}
				<input readonly name="{$fldname}" type="checkbox" tabindex="{$vt_tab}">
			{/if}
		<!-- For Portal Information we need a hidden field existing_portal with the current portal value -->
		{elseif $fldname eq 'portal'}
			<input type="hidden" name="existing_portal" value="{$fldvalue}">
			<input readonly name="{$fldname}" type="checkbox" tabindex="{$vt_tab}" {if $fldvalue eq 1}checked{/if}>
		{else}
			{if $fldvalue eq 1}
				<input readonly name="{$fldname}" type="checkbox" tabindex="{$vt_tab}" checked>
			{else}
				<input readonly name="{$fldname}" type="checkbox" tabindex="{$vt_tab}"> {* crmv@150125 *}
			{/if}
		{/if}
		<!-- crmv@14511e -->
		<input disabled name="visual{$fldname}" type="hidden" tabindex="{$vt_tab}" {if $fldvalue eq 1}value="1"{/if}>
	</div>
{elseif $uitype eq 23 || $uitype eq 5 || $uitype eq 6}
	{foreach key=date_value item=time_value from=$fldvalue}
		{assign var=date_val value="$date_value"}
		{assign var=time_val value="$time_value"}
	{/foreach}
	<input name="{$fldname}" tabindex="{$vt_tab}" id="jscal_field_{$fldname}" type="hidden" style="border:1px solid #bababa;" size="11" maxlength="10" value="{$date_val}">
	{if $uitype eq 6}
		<input name="time_start" tabindex="{$vt_tab}" style="border:1px solid #bababa;" size="5" maxlength="5" type="hidden" value="{$time_val}">
	{/if}
	{foreach key=date_format item=date_str from=$secondvalue}
		{assign var=dateFormat value="$date_format"}
		{assign var=dateStr value="$date_str"}
	{/foreach}
{elseif $uitype eq 63}
	<input readonly name="{$fldname}" type="text" size="2" value="{$fldvalue}" tabindex="{$vt_tab}" >&nbsp;
	<div id='select{$fldname}' style='display:none'>
		<select name="duration_minutes" tabindex="{$vt_tab}">
			{foreach key=labelval item=selectval from=$secondvalue}
			{if $selectval eq 'selected'}
				{assign var="value_backup" value="{$labelval}"}
			{/if}	
				<option value="{$labelval}" {$selectval}>{$labelval}</option>
			{/foreach}
		</select>
	</div>
{elseif $uitype eq 62}
	<div id='select{$fldname}' style='display:none'>
		<select name="parent_type" onChange='document.EditView.parent_name.value=""; document.EditView.parent_id.value=""'>
			{section name=combo loop=$fldlabel}
				{if $fldlabel_sel[combo] eq 'selected'}
					{assign var="value_backup" value=$fldlabel[combo]} {* crmv@181170 *}
				{/if}	
				<option value="{$fldlabel_combo[combo]}" {$fldlabel_sel[combo]}>{$fldlabel[combo]}</option>
			{/section}
		</select>
	</div>
	<!--crmv@7207--> {$value_backup}
	<input name="{$fldname}" type="hidden" value="{$secondvalue}">
	<input name="parent_name" id = "parentid" type="hidden" style="border:1px solid #bababa;" value="{$fldvalue}">
{elseif $uitype eq 357}
	<input name="{$fldname}" type="hidden" value="{$secondvalue}">
	<div id='select{$fldname}' style='display:none;'>
		<textarea readonly name="parent_name" cols="70" rows="2">{$fldvalue}</textarea>&nbsp;
		<select name="parent_type">
			{foreach key=labelval item=selectval from=$fldlabel}
				{if {$selectval} eq 'selected'}
					{assign var="value_backup" value="{$labelval}"}
				{/if}						
				<option value="{$labelval}" {$selectval}>{$labelval}</option>
			{/foreach}
		</select>
	</div>
	<!--crmv@7207--> {$value_backup}
	<input name="ccmail" type="hidden"   value="">
	<input name="bccmail" type="hidden"   value="">
{elseif $uitype eq 55 || $uitype eq 255} 
	{if $fldvalue neq ''}
		<div id='select{$fldname}' style='display:none'>
			<select name="salutationtype">
				{foreach item=arr from=$fldvalue}
					{if $arr[2] eq 'selected'}
						{assign var="value_backup" value=$arr[0]} {* crmv@181170 *}
					{/if}						
					<option value="{$arr[1]}" {$arr[2]}>{$arr[0]}</option>
				{/foreach}
			</select>
		</div>
	{/if}
	<input type="hidden" name="{$fldname}" tabindex="{$vt_tab}"  style="width:58%;" value= "{$secondvalue}" >
{elseif $uitype eq 69}
	{if $MODULE eq 'Products'}
		<input name="del_file_list" type="hidden" value="">
		<div id="files_list" style="display:none; border: 1px solid grey; width: 500px; padding: 5px; background: rgb(255, 255, 255) none repeat scroll 0%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; font-size: x-small">{$APP.Files_Maximum_6}
			<!--input type="hidden" name="file_1_hidden" value=""/-->
			{assign var=image_count value=0}
			{if $maindata[3].0.name neq '' && $DUPLICATE neq 'true'}
			   {foreach name=image_loop key=num item=image_details from=$maindata[3]}
				<div align="center">
					<img src="{$image_details.path}{$image_details.name}" height="50">&nbsp;&nbsp;[{$image_details.orgname}]
				</div>
		   	   {assign var=image_count value=$smarty.foreach.image_loop.iteration}
		   	   {/foreach}
			{/if}
		</div>
	{else}
		<input name="{$fldname}"  type="hidden" value="{$maindata[3].0.name}" tabindex="{$vt_tab}" onchange="validateFilename(this);" />
		<input name="{$fldname}_hidden"  type="hidden" value="{$maindata[3].0.name}" />
		<input type="hidden" name="id" value=""/>
		{if $maindata[3].0.name != "" && $DUPLICATE neq 'true'}
			<div style="display:none;" id="replaceimage">[{$maindata[3].0.orgname}] <a href="javascript:;" onClick="delimage({$ID})">Del</a></div>
			{$maindata[3].0.orgname}
		{/if}
	{/if}
{elseif $uitype eq 61}
	<input name="{$fldname}"  type="hidden" value="{$secondvalue}" tabindex="{$vt_tab}" onchange="validateFilename(this)"/>
	<input type="hidden" name="{$fldname}_hidden" value="{$secondvalue}"/>
	<input type="hidden" name="id" value=""/> <!--crmv@7207--> {$fldvalue}
{elseif $uitype eq 156}
	{if $fldvalue eq 'on'}
		{if ($secondvalue eq 1 && $CURRENT_USERID != $smarty.request.record) || ($MODE == 'create')}
			<input name="{$fldname}" tabindex="{$vt_tab}" type="hidden" checked>
		{else}
			<input name="{$fldname}" type="hidden" value="on">
			<input name="{$fldname}" disabled tabindex="{$vt_tab}" type="hidden" checked>
		{/if}
		<input name="visual_{$fldname}" tabindex="{$vt_tab}" type="hidden">
	{else}
		{if ($secondvalue eq 1 && $CURRENT_USERID != $smarty.request.record) || ($MODE == 'create')}
			<input name="{$fldname}" tabindex="{$vt_tab}" type="hidden">
		{else}
			<input name="{$fldname}" disabled tabindex="{$vt_tab}" type="hidden">
		{/if}
		<input name="visual_{$fldname}" disabled tabindex="{$vt_tab}" type="">
	{/if}
{elseif $uitype eq 98}<!-- Role Selection Popup -->		
	{if $thirdvalue eq 1}
	{assign var="value_backup" value=$secondvalue}
		<input name="role_name" id="role_name" tabindex="{$vt_tab}" value="{$secondvalue}" type="hidden">				
	{else}
		{assign var="value_backup" value=$secondvalue}
		<input name="role_name" id="role_name" tabindex="{$vt_tab}" value="{$secondvalue}" type="hidden">
	{/if}	
	<input name="user_role" id="user_role" value="{$fldvalue}" type="hidden">
{elseif $uitype eq 104}<!-- Mandatory Email Fields -->			
	<input type="hidden" name="{$fldname}" id ="{$fldname}" value="{$fldvalue}" tabindex="{$vt_tab}" >
{elseif $uitype eq 115}<!-- for Status field Disabled for nonadmin -->
	<div style="display: none;">
		{if $secondvalue eq 1 && $CURRENT_USERID != $smarty.request.record}
			<select id="user_status" name="{$fldname}" tabindex="{$vt_tab}" >
		{else}
			<select id="user_status" disabled name="{$fldname}">
		{/if} 
		{foreach item=arr from=$fldvalue}
			{if $arr[2] eq 'selected'}
				{assign var="value_backup" value=$arr[0]}
			{/if}
            <option value="{$arr[1]}" {$arr[2]}>{$arr[0]}</option>
		{/foreach}
	</select></div>
{elseif $uitype eq 105}
	{if $MODE eq 'edit' && $IMAGENAME neq ''}
		<input name="{$fldname}" type="hidden" value="{$maindata[3].0.name}" tabindex="{$vt_tab}" onchange="validateFilename(this);" />[{$IMAGENAME}]<br>
		<input name="{$fldname}_hidden" type="hidden" value="{$maindata[3].0.name}" />
	{else}
		<input name="{$fldname}" type="hidden" value="{$maindata[3].0.name}" tabindex="{$vt_tab}" onchange="validateFilename(this);" /><br>
		<input name="{$fldname}_hidden" type="hidden" value="{$maindata[3].0.name}" />
	{/if}
		<input type="hidden" name="id" value=""/>
{elseif $uitype eq 103}
	<input type="hidden" name="{$fldname}" value="{$fldvalue}" tabindex="{$vt_tab}" >
{elseif $uitype eq 116}<!-- for currency in users details-->	
	<div style="display: none;">
		{if $secondvalue eq 1}
			<select name="{$fldname}" tabindex="{$vt_tab}">
		{else}
			<select disabled name="{$fldname}" tabindex="{$vt_tab}">
		{/if}			   
		{foreach item=arr key=uivalueid from=$fldvalue}
			{foreach key=sel_value item=value from=$arr}
				<option value="{$uivalueid}" {$value}>{$sel_value}</option>
				<!-- code added to pass Currency field value, if Disabled for nonadmin -->
				{if $value eq 'selected' && $secondvalue neq 1}
					{assign var="curr_stat" value="$uivalueid"}
				{/if}
				<!--code ends -->
			{/foreach}
		{/foreach}				
		</select>
	</div>
	<!-- code added to pass Currency field value, if Disabled for nonadmin -->
	{if $curr_stat neq ''}
		<input name="{$fldname}" type="hidden" value="{$curr_stat}">
	{/if}
	<!--code ends -->
{elseif $uitype eq 106}
	{if $MODE eq 'edit'}
		<input type="hidden" name="{$fldname}" value="{$fldvalue}" tabindex="{$vt_tab}" >
	{else}
		<input type="hidden" name="{$fldname}" value="{$fldvalue}" tabindex="{$vt_tab}" >
	{/if}
{elseif $uitype eq 99}
	{if $MODE eq 'create'}
		<input type="hidden" name="{$fldname}" tabindex="{$vt_tab}" value="{$fldvalue}" >
	{/if}
{elseif $uitype eq 30}
	{assign var=check value=$secondvalue[0]}
	{assign var=yes_val value=$secondvalue[1]}
	{assign var=no_val value=$secondvalue[2]}
	<input type="hidden" name="set_reminder" tabindex="{$vt_tab}" value="Yes" {$check}>&nbsp;{$yes_val}&nbsp;
	<input type="hidden" name="set_reminder" value="No">&nbsp;{$no_val}&nbsp;

	{foreach item=val_arr from=$fldvalue}
		{assign var=start value=$val_arr[0]}
		{assign var=end value=$val_arr[1]}
		{assign var=sendname value=$val_arr[2]}
		{assign var=disp_text value=$val_arr[3]}
		{assign var=sel_val value=$val_arr[4]}
		<select name="{$sendname}" style="display:none;">
			{section name=reminder start=$start max=$end loop=$end step=1 }
				{if $smarty.section.reminder.index eq $sel_val}
					{assign var=sel_value value="SELECTED"}
				{else}
					{assign var=sel_value value=""}
				{/if}
				<option VALUE="{$smarty.section.reminder.index}" {$sel_value}>{$smarty.section.reminder.index}</option>
			{/section}
		</select>
	{/foreach}
{elseif $uitype eq 83} <!-- Handle the Tax in Inventory -->
	{foreach item=tax key=count from=$TAX_DETAILS}
		{if $tax.check_value eq 1}
			{assign var=check_value value="checked"}
			{assign var=show_value value="visible"}
		{else}
			{assign var=check_value value=""}
			{assign var=show_value value="hidden"}
		{/if}
		<input type="hidden" name="{$tax.check_name}" id="{$tax.check_name}" onclick="fnshowHide(this,'{$tax.taxname}')" {$check_value}></div>
		<input type="hidden" name="visual{$tax.check_name}" id="{$tax.check_name}" onclick="fnshowHide(this,'{$tax.taxname}')" {$check_value}>
		<input type="hidden" name="{$tax.taxname}" id="{$tax.taxname}" value="{$tax.percentage_fmt}" style="visibility:{$show_value};" onBlur="fntaxValidation('{$tax.taxname}')"> {* crmv@118512 *}
	{/foreach}
<!-- crmv@16265 -->
{elseif $uitype eq 199}
	<input type="hidden" tabindex="{$vt_tab}" name="{$fldname}" id="{$fldname}" value="">
<!-- crmv@16265e -->
<!-- crmv@18338 -->
{else}
	<input type="hidden" name="{$fldname}" id="{$fldname}" value="{$fldvalue}">
<!-- crmv@18338 end -->	
{/if}