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
		{if $SDK->isOldUitype($uitype)}
			{assign var="usefldlabel" value=$fldlabel}
			<div><table cellpadding="0" cellspacing="0" width="100%"><tr>
				{include file=$sdk_file}
			</tr></table></div>
		{else}
			{include file=$sdk_file}
		{/if}
	{/if}
{* crmv@sdk-18509 e *}
{* vtlib customization *}
{elseif $uitype eq '10'}
	{assign var="tmpFieldLabel" value=$fldlabel.displaylabel}
	{if count($fldlabel.options) eq 1}
		{assign var="use_parentmodule" value=$fldlabel.options.0}
		<input type='hidden' class='small' name="{$fldname}_type" value="{$use_parentmodule}">{*//crmv@3652*}
	{else}
		{foreach item=option from=$fldlabel.options}
			{if $fldlabel.selected == $option}
				{assign var=relmodule_label value=$option|@getTranslatedString:$MODULE}
				{assign var=relmodule_value value="$option"}
			{/if}
		{/foreach}
		<input type="hidden" id="{$fldname}_type" class="small" name="{$fldname}_type" value="{$relmodule_value}">
		{assign var="tmpFieldLabel" value=$fldlabel.displaylabel|cat:' '|cat:$relmodule_label}
	{/if}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$tmpFieldLabel}
	<div class="{$DIVCLASS}">
		<input id="{$fldname}" name="{$fldname}" type="hidden" value="{$fldvalue.entityid}" id="{$fldname}">
		<input id="{$fldname}_display" name="{$fldname}_display" id="edit_{$fldname}_display" readonly type="text" style="border:0;" value="{$fldvalue.displayvalue}" class="detailedViewTextBox"> {*crmv@33994*}
	</div>
{* END *}
{elseif $uitype eq 3 || $uitype eq 4}<!-- Non Editable field, only configured value will be loaded -->
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel}
	<div class="{$DIVCLASS}">
		{if $hide_number eq 'yes'}
			<input type="hidden" tabindex="{$vt_tab}" name="{$fldname}" id ="{$fldname}" {if $MODE eq 'edit'} value="{$fldvalue}" {else} value="{$inv_no}" {/if} class="detailedViewTextBox">
			<font color="#FF0000">{$LBL_CREATE_NO}</font> {* //ds@43 - Create No output in CreateView *}
		{else}
			<input type="hidden" tabindex="{$vt_tab}" name="{$fldname}" id ="{$fldname}" {if $MODE eq 'edit'} value="{$fldvalue}" {assign var="value_backup" value="$fldvalue"} {else} value="{$inv_no}" {assign var="value_backup" value="$inv_no"} {/if} class="detailedViewTextBox">
		{$value_backup}
		{/if}
	</div>
<!--   //crmv@8056e -->		
 <!--   //crmv@7231 - crmv@7216 crmv@7220 crmv@18338-->   
 {*//crmv@33466*}                            
{elseif $uitype eq 11 || $uitype eq 1014}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel}
	<div class="{$DIVCLASS}">
		<input type="hidden" name="{$fldname}" tabindex="{$vt_tab}" id ="{$fldname}" value="{$fldvalue}" class="detailedViewTextBox">	
		{*//crmv@36559*}
		{if ''|@get_use_asterisk eq 'true'}
			<a href='javascript:;' onclick='startCall("{$keyval}", "{$ID}")'>{$fldvalue}</a>
		{else}
			{$fldvalue}								
		{/if}
		{*//crmv@36559 e*}
	</div>
{*//crmv@33466e*}			
{elseif $uitype eq 1 || $uitype eq 13 || $uitype eq 7 || $uitype eq 9 || $uitype eq 1112 || $uitype eq 1013}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel}
	<div class="{$DIVCLASS}">
		<!--   //ds@26 -->
		{if $fldname eq 'if_yes_products' && $MODULE eq 'Visitreport'}
			<input type="hidden" tabindex="{$vt_tab}" name="{$fldname}" id ="{$fldname}" value="{$fldvalue|escape}" class="detailedViewTextBox" >
			{$fldvalue}
		{elseif $fldname eq 'account_nr' && $MODULE eq 'Visitreport' }
			<input type="hidden" tabindex="{$vt_tab}" name="{$fldname}" id ="{$fldname}" value="{$fldvalue|escape}" class="detailedViewTextBox">
			{$fldvalue}
		<!--   //ds@26e -->
		{else}
			<!--   //crmv@7231 --> 
			{if $uitype eq 1112 && ($fldvalue neq '' && $fldvalue neq '--None--') }
				<input type="hidden"  name="{$fldname}" id ="{$fldname}" value="{$fldvalue}">
				{$fldvalue}
			{else}
				<input type="hidden" tabindex="{$vt_tab}" name="{$fldname}" id ="{$fldname}" value="{$fldvalue}" class="detailedViewTextBox">
				{$fldvalue}
			{/if}
		{/if}
	</div>
{elseif $uitype eq 19}
	<!-- In Add Comment are we should not display anything -->
	{if $fldlabel eq $MOD.LBL_ADD_COMMENT}
		{assign var=fldvalue value=""}
	{/if}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel}
	<div class="{$DIVCLASS}">
		{$fldvalue}
		<div style="display:none;">
			<textarea readonly class="detailedViewTextBox" tabindex="{$vt_tab}" name="{$fldname}" cols="90" rows="8">{$fldvalue}</textarea>
		</div>
		{if $fldlabel eq $MOD.Solution}
			<input type = "hidden" name="helpdesk_solution" value = '{$fldvalue}'>
		{/if}
	</div>
{elseif $uitype eq 21}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel}
	<div class="{$DIVCLASS}">
		<div style="display:none;">
			<textarea  value="{$fldvalue}" name="{$fldname}" tabindex="{$vt_tab}" class="detailedViewTextBox" rows=2>{$fldvalue}</textarea>
		</div>
		{$fldvalue|nl2br}
	</div>
{* <!-- ds@8 project tool --> *}	
{elseif $uitype eq 25}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel}
	<div class="{$DIVCLASS}">
		<div style="display:none;">
			<textarea  style="width:40%" value="{$fldvalue|escape}" name="{$fldname}" tabindex="{$vt_tab}">{$fldvalue|escape}</textarea>
		</div>
		{$fldvalue}
		<input type="hidden" name="projects_ids" value="{$PROJECTS_IDS}">
	</div>
{* <!--  ds@8e --> *}	 
<!-- //crmv@8982 --> 
{elseif $uitype eq 15 || $uitype eq 16 || $uitype eq 1015}	{* <!-- DS-ED VlMe 31.3.2008 - add uitype 504 --> *}
<!-- //crmv@8982e --> 
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel}
	<div class="{$DIVCLASS}">
		{foreach item=arr from=$fldvalue}
			{if $arr[2] eq 'selected'}
				{assign var="value_15" value=$arr[1]}
				{assign var="value_15_label" value=$arr[0]}
			{/if}	
		{/foreach}
		<input type="hidden" name="{$fldname}" tabindex="{$vt_tab}" class="small" value="{$value_15}">
		{$value_15_label}
	</div>	
{elseif $uitype eq 33}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel}
	<div class="{$DIVCLASS}">
		<div id='select{$fldname}' style='display:none'>
			<select MULTIPLE name="{$fldname}[]" size="4" style="width:160px;" tabindex="{$vt_tab}" class="small">
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
		{$value_33_label}
	</div>
{elseif $uitype eq 53}
	{assign var="style_53" value="display:none"}
	{assign var="assigntype" value="assigntype"}
	{assign var="assign_user" value="assign_user"}
	{assign var="assign_team" value="assign_team"}
	{assign var="assigned_group_name" value="assigned_group_id"}	{* crmv@19981 *}	
	{assign var="assigned_user_id" value=$fldname}
	{assign var="select_user" value=""}
	{assign var="select_group" value=""}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel}
	<div class="{$DIVCLASS}">
		{assign var=check value=2}
		{foreach key=key_one item=arr from=$fldvalue}
			{foreach key=sel_value item=value from=$arr}
				{if $value eq 'selected'}
					{assign var="value_backup" value=$sel_value}
					{assign var=check value=0}
				{/if}
			{/foreach}
		{/foreach}
		{foreach key=key_one item=arr from=$secondvalue}
			{foreach key=sel_value item=value from=$arr}
				{if $value eq 'selected'}
					{assign var="value_backup" value=$sel_value}
					{assign var=check value=1}
				{/if}
			{/foreach}
		{/foreach}
		{if $check eq 0}
			{assign var=select_user value='checked'}
			{assign var=style_user value='display:block'}
			{assign var=style_group value='display:none'}
		{elseif $check eq 1}
			{assign var=select_group value='checked'}
			{assign var=style_user value='display:none'}
			{assign var=style_group value='display:block'}
		{else}
			{assign var=select_nobody value='checked'}
			{assign var=style_user value='display:none'}
			{assign var=style_group value='display:none'}															
		{/if}				
		<div id='all_data{$fldname}' style='{$style_53}'>
		<input type="radio" tabindex="{$vt_tab}" name="{$assigntype}" {$select_user} value="U" onclick="toggleAssignType(this.value)" >&nbsp;{$APP.LBL_USER}
		{if $secondvalue neq ''}
			<input type="radio" name="{$assigntype}" {$select_group} value="T" onclick="toggleAssignType(this.value)">&nbsp;{$APP.LBL_GROUP}
		{/if}
		<span id="{$assign_user}" style="{$style_user}">
			<select name="{$assigned_user_id}" class="small">
				{foreach key=key_one item=arr from=$fldvalue}
					{foreach key=sel_value item=value from=$arr}
						<option value="{$key_one}" {$value}>{$sel_value}</option>
					{/foreach}
				{/foreach}
			</select>
		</span>
		{if $secondvalue neq ''}
			<span id="{$assign_team}" style="{$style_group}">
				<select name="{$assigned_group_name}" class="small">';
					{foreach key=key_one item=arr from=$secondvalue}
						{foreach key=sel_value item=value from=$arr}
							<option value="{$key_one}" {$value}>{$sel_value}</option>	{* crmv@19981 *}
						{/foreach}
					{/foreach}
				</select>
			</span>
		{/if}
		</div>
		{$value_backup}
	</div>
{elseif $uitype eq 52 || $uitype eq 77 || $uitype eq 54}	{* crmv@101683 *}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel}
	<div class="{$DIVCLASS}">
		<div id='select{$fldname}' style='display:none'>
			{if $uitype eq 77}
				<select name="assigned_user_id1" tabindex="{$vt_tab}" class="small">
			{else}
				<select name="{$fldname}" tabindex="{$vt_tab}" class="small">
			{/if}
			{foreach key=key_one item=arr from=$fldvalue}
				{foreach key=sel_value item=value from=$arr}
					{if $value eq 'selected'}
						{assign var="value_backup" value=$sel_value}
					{/if}
					<option value="{$key_one}" {$value}>{$sel_value}</option>
				{/foreach}
			{/foreach}
			</select>
		</div>
		{$value_backup}
	</div>
{elseif $uitype eq 17}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel}
	<div class="{$DIVCLASS}">
		http://
		<input style="width:74%;" class="detailedViewTextBox" type="hidden" tabindex="{$vt_tab}" name="{$fldname}" style="border:1px solid #bababa;" size="27" value="{$fldvalue}">
		{$fldvalue}
	</div>
{elseif $uitype eq 85}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel}
	<div class="{$DIVCLASS}">
		<img src="{'skype.gif'|resourcever}" alt="Skype" title="Skype" LANGUAGE=javascript align="absmiddle"></img><input type="hidden" tabindex="{$vt_tab}" name="{$fldname}" style="border:1px solid #bababa;" size="27" value="{$fldvalue}">
		{$fldvalue}
	</div>
{elseif $uitype eq 71 || $uitype eq 72}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel}
	<div class="{$DIVCLASS}">
		<input name="{$fldname}" tabindex="{$vt_tab}" type="hidden" class="detailedViewTextBox"  value="{$fldvalue}">
		{$fldvalue}
	</div>
{elseif $uitype eq 56}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel}
	<div class="{$DIVCLASS}">
		<div style="display:none;">
			{if $fldname eq 'notime' && $ACTIVITY_MODE eq 'Events'}
				{if $fldvalue eq 1}
					<input readonly name="{$fldname}" type="checkbox" tabindex="{$vt_tab}"  checked>
				{else}
					<input readonly name="{$fldname}" tabindex="{$vt_tab}" type="checkbox"  >
				{/if}
			<!-- For Portal Information we need a hidden field existing_portal with the current portal value -->
			{elseif $fldname eq 'portal'}
				<input type="hidden" name="existing_portal" value="{$fldvalue}">
				<input readonly name="{$fldname}" type="checkbox" tabindex="{$vt_tab}" {if $fldvalue eq 1}checked{/if}>
			{else}
				{if $fldvalue eq 1}
					<input readonly name="{$fldname}" type="checkbox" tabindex="{$vt_tab}" checked>
				{else}
					<input readonly name="{$fldname}" tabindex="{$vt_tab}" type="checkbox" {if ( $PROD_MODE eq 'create' &&  $fldname|substr:0:3 neq 'cf_') ||( $fldname|substr:0:3 neq 'cf_' && $PRICE_BOOK_MODE eq 'create' ) || $USER_MODE eq 'create'}checked{/if}>
				{/if}
			{/if}
		</div>
		<input disabled name="visual{$fldname}" type="checkbox" tabindex="{$vt_tab}" {if $fldvalue eq 1}checked{/if}>
	</div>
{elseif $uitype eq 23 || $uitype eq 5 || $uitype eq 6}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel}
	<div class="{$DIVCLASS}">
		{foreach key=date_value item=time_value from=$fldvalue}
			{assign var=date_val value="$date_value"}
			{assign var=time_val value="$time_value"}
		{/foreach}

		<input name="{$fldname}" tabindex="{$vt_tab}" id="jscal_field_{$fldname}" type="hidden" size="11" maxlength="10" value="{$date_val}">
		{$date_val}
		
		{if $uitype eq 6}
			<input name="time_start" tabindex="{$vt_tab}" size="5" maxlength="5" type="hidden" value="{$time_val}">
			{$time_val}
		{/if}

		{foreach key=date_format item=date_str from=$secondvalue}
			{assign var=dateFormat value="$date_format"}
			{assign var=dateStr value="$date_str"}
		{/foreach}

		{if $uitype eq 5 || $uitype eq 23}
			<br><font size=1><em old="(yyyy-mm-dd)">({$dateStr})</em></font>
		{else}
			<br><font size=1><em old="(yyyy-mm-dd)">({$dateStr})</em></font>
		{/if}

		{* crmv@190519 *}
		<script type="text/javascript">
			(function() {ldelim}
				setupDatePicker('jscal_field_{$fldname}', {ldelim}
					trigger: 'jscal_trigger_{$fldname}',
					date_format: "{$dateStr|strtoupper}",
					language: "{$APP.LBL_JSCALENDAR_LANG}",
				{rdelim});
			{rdelim})();
		</script>
		{* crmv@190519e *}
	</div>
{* crmv@91629 *}
{elseif $uitype eq 26}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
	<div class="{$DIVCLASS}">
		{foreach item=v key=k from=$fldvalue}
			{if $realval eq ''}
				{assign var=realval value="$v"}
			{/if}
		{/foreach}
		<input type="hidden" name="{$fldname}" tabindex="{$vt_tab}" value="{$realval}" tabindex="{$vt_tab}" class="detailedViewTextBox">{$realval}
	</div>
{elseif $uitype eq 27}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$maindata[1][3] massedit=$MASS_EDIT}
	<div class="{$DIVCLASS}">
		<input type="hidden" name="{$fldname}" tabindex="{$vt_tab}" value="{$fldvalue}" tabindex="{$vt_tab}" class="detailedViewTextBox">
		{$fldvalue}
	</div>
{* crmv@91629e *}
{* crmv@104365 *}
{elseif $uitype eq 28}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel}
	<div class="{$DIVCLASS}">
		<input type="hidden" name="{$fldname}" tabindex="{$vt_tab}" value="{$fldvalue}" tabindex="{$vt_tab}" class="detailedViewTextBox">
		{$thirdvalue}
	</div>
{* crmv@104365e *}
{elseif $uitype eq 357}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label="To"}
	<div class="{$DIVCLASS}">
		<input name="{$fldname}" type="hidden" value="{$secondvalue}">
		<textarea readonly name="parent_name" cols="70" rows="2">{$fldvalue}</textarea>&nbsp;
		<div id='select{$fldname}' style='display:none'>
		<select name="parent_type" class="small">
			{foreach key=labelval item=selectval from=$fldlabel}
				{if {$selectval} eq 'selected'}
					{assign var="value_backup" value="{$labelval}"}
				{/if}						
				<option value="{$labelval}" {$selectval}>{$labelval}</option>
			{/foreach}
		</select>
		</div>
		{$value_backup}
	</div>
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label="Cc"}
	<div class="{$DIVCLASS}">
		<input readonly name="ccmail" type="text" class="detailedViewTextBox"  value="">
	</div>
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label="Bcc"}
	<div class="{$DIVCLASS}">
		<input readonly name="bccmail" type="text" class="detailedViewTextBox"  value="">
	</div>
{elseif $uitype eq 55 || $uitype eq 255} 
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel}
	<div class="{$DIVCLASS}">
		{if $fldvalue neq ''}
			<div id='select{$fldname}' style='display:none'>
				<select name="salutationtype" class="small">
					{foreach item=arr from=$fldvalue}
						{if $arr[2] eq 'selected'}
							{assign var="value_backup" value=$arr[0]} {* crmv@181170 *}
						{/if}						
						<option value="{$arr[1]}" {$arr[2]}>{$arr[0]}</option>
					{/foreach}
				</select>
			</div>
			{$value_backup}
		{/if}
		<input type="hidden" name="{$fldname}" tabindex="{$vt_tab}" class="detailedViewTextBox" style="width:58%;" value= "{$secondvalue}" >
		{$secondvalue}
	</div>
{elseif $uitype eq 69}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel}
	<div class="{$DIVCLASS}">
		{if $MODULE eq 'Products'}
			<input name="del_file_list" type="hidden" value="">
			<div id="files_list" style="padding: 5px; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; font-size: x-small">{$APP.Files_Maximum_6}
				<!--input type="hidden" name="file_1_hidden" value=""/-->
				{assign var=image_count value=0}
				{if $maindata[3].0.name neq '' && $DUPLICATE neq 'true'}
				   {foreach name=image_loop key=num item=image_details from=$maindata[3]}
					<div>
						<img src="{$image_details.path}{$image_details.name}" height="50">&nbsp;&nbsp;[{$image_details.orgname}]
					</div>
			   	   {assign var=image_count value=$smarty.foreach.image_loop.iteration}
			   	   {/foreach}
				{/if}
			</div>
			<script>
				{* Create an instance of the multiSelector class, pass it the output target and the max number of files *}
				var multi_selector = new MultiSelector( document.getElementById( 'files_list' ), 6 );
				multi_selector.count = {$image_count}
				{* Pass in the file element *}
				multi_selector.addElement( document.getElementById( 'my_file_element' ) );
			</script>
		{else}
			<input name="{$fldname}"  type="hidden" value="{$maindata[3].0.name}" tabindex="{$vt_tab}" onchange="validateFilename(this);" />
			<input name="{$fldname}_hidden"  type="hidden" value="{$maindata[3].0.name}" />
			<input type="hidden" name="id" value=""/>
			{if $maindata[3].0.name != "" && $DUPLICATE neq 'true'}
				<div style="display:none;" id="replaceimage">[{$maindata[3].0.orgname}] <a href="javascript:;" onClick="delimage({$ID})">Del</a></div>
				{$maindata[3].0.orgname}
			{/if}
		{/if}
	</div>
{elseif $uitype eq 61}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel}
	<div class="{$DIVCLASS}">
		<input name="{$fldname}"  type="hidden" value="{$secondvalue}" tabindex="{$vt_tab}" onchange="validateFilename(this)"/>
		<input type="hidden" name="{$fldname}_hidden" value="{$secondvalue}"/>
		<input type="hidden" name="id" value=""/>{$fldvalue}
	</div>
{elseif $uitype eq 156}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel}
	<div class="{$DIVCLASS}">
		{if $fldvalue eq 'on'}
			{if ($secondvalue eq 1 && $CURRENT_USERID != $smarty.request.record) || ($MODE == 'create')}
				<input name="{$fldname}" tabindex="{$vt_tab}" type="hidden" checked>
			{else}
				<input name="{$fldname}" type="hidden" value="on">
				<input name="{$fldname}" disabled tabindex="{$vt_tab}" type="hidden" checked>
			{/if}
			<input name="visual_{$fldname}" disabled checked tabindex="{$vt_tab}" type="checkbox">
		{else}
			{if ($secondvalue eq 1 && $CURRENT_USERID != $smarty.request.record) || ($MODE == 'create')}
				<input name="{$fldname}" tabindex="{$vt_tab}" type="hidden">
			{else}
				<input name="{$fldname}" disabled tabindex="{$vt_tab}" type="hidden">
			{/if}
			<input name="visual_{$fldname}" disabled tabindex="{$vt_tab}" type="checkbox">
		{/if}
	</div>
{elseif $uitype eq 98}<!-- Role Selection Popup -->		
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel}
	<div class="{$DIVCLASS}">
		{if $thirdvalue eq 1}
		{assign var="value_backup" value=$secondvalue}
			<input name="role_name" id="role_name" class="txtBox" tabindex="{$vt_tab}" value="{$secondvalue}" type="hidden">&nbsp;				
		{else}
			{assign var="value_backup" value=$secondvalue}
			<input name="role_name" id="role_name" tabindex="{$vt_tab}" class="txtBox" value="{$secondvalue}" type="hidden">&nbsp;
		{/if}	
		<input name="user_role" id="user_role" value="{$fldvalue}" type="hidden">
		{$value_backup}
	</div>
{elseif $uitype eq 104}<!-- Mandatory Email Fields -->			
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel}
	<div class="{$DIVCLASS}">
         <input type="hidden" name="{$fldname}" id ="{$fldname}" value="{$fldvalue}" tabindex="{$vt_tab}" class="detailedViewTextBox">
         {$fldvalue}
	</div>
{elseif $uitype eq 115}<!-- for Status field Disabled for nonadmin -->
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel}
	<div class="{$DIVCLASS}">
		<div style="display: none;">
			{if $secondvalue eq 1 && $CURRENT_USERID != $smarty.request.record}
				<select id="user_status" name="{$fldname}" tabindex="{$vt_tab}" class="small">
			{else}
				<select id="user_status" disabled name="{$fldname}" class="small">
			{/if} 
			{foreach item=arr from=$fldvalue}
				{if $arr[2] eq 'selected'}
					{assign var="value_backup" value=$arr[0]}
				{/if}
				<option value="{$arr[1]}" {$arr[2]}>{$arr[0]}</option>
			{/foreach}
			</select>
		</div>
		{$value_backup}
	</div>
{elseif $uitype eq 105}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel}
	<div class="{$DIVCLASS}">
		{if $MODE eq 'edit' && $IMAGENAME neq ''}
			<input name="{$fldname}"  type="hidden" value="{$maindata[3].0.name}" tabindex="{$vt_tab}" onchange="validateFilename(this);" />[{$IMAGENAME}]<br>
			<input name="{$fldname}_hidden"  type="hidden" value="{$maindata[3].0.name}" />
		{else}
			<input name="{$fldname}"  type="hidden" value="{$maindata[3].0.name}" tabindex="{$vt_tab}" onchange="validateFilename(this);" /><br>
			<input name="{$fldname}_hidden"  type="hidden" value="{$maindata[3].0.name}" />
		{/if}
		<input type="hidden" name="id" value=""/>
		{$maindata[3].0.name}
	</div>
{elseif $uitype eq 103}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel}
	<div class="{$DIVCLASS}">
		<input type="text" name="{$fldname}" value="{$fldvalue}" tabindex="{$vt_tab}" class="detailedViewTextBox">
	</div>
{elseif $uitype eq 116}<!-- for currency in users details-->	
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel}
	<div class="{$DIVCLASS}">
		<div style="display: none;">
		   {if $secondvalue eq 1}
		   	<select name="{$fldname}" tabindex="{$vt_tab}" class="small">
		   {else}
		   	<select disabled name="{$fldname}" tabindex="{$vt_tab}" class="small">
		   {/if}			   
			{foreach item=arr key=uivalueid from=$fldvalue}
				{foreach key=sel_value item=value from=$arr}
					<option value="{$uivalueid}" {$value}>{$sel_value}</option>
					<!-- code added to pass Currency field value, if Disabled for nonadmin -->
					{if $value eq 'selected' && $secondvalue neq 1}
						{assign var="curr_stat" value="$uivalueid"}
					{/if}
					{if $value eq 'selected'}
					{assign var="value_backup" value=$sel_value}
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
		{$value_backup}
		<!--code ends -->
	</div>
{elseif $uitype eq 106}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel}
	<div class="{$DIVCLASS}">
		{if $MODE eq 'edit'}
			<input type="hidden" readonly name="{$fldname}" value="{$fldvalue}" tabindex="{$vt_tab}" class="detailedViewTextBox">
			{$fldvalue}
		{else}
			<input type="text" name="{$fldname}" value="{$fldvalue}" tabindex="{$vt_tab}" class="detailedViewTextBox">
		{/if}
	</div>
{elseif $uitype eq 99}
	{if $MODE eq 'create'}
		{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel}
		<div class="{$DIVCLASS}">
			<input type="hidden" name="{$fldname}" tabindex="{$vt_tab}" value="{$fldvalue}" class="detailedViewTextBox">
		</div>
	{/if}
{elseif $uitype eq 30}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel}
	<div class="{$DIVCLASS}">
		{assign var=check value=$secondvalue[0]}
		{assign var=yes_val value=$secondvalue[1]}
		{assign var=no_val value=$secondvalue[2]}

		<input type="radio" name="set_reminder" tabindex="{$vt_tab}" value="Yes" {$check}>&nbsp;{$yes_val}&nbsp;
		<input type="radio" name="set_reminder" value="No">&nbsp;{$no_val}&nbsp;

		{foreach item=val_arr from=$fldvalue}
			{assign var=start value=$val_arr[0]}
			{assign var=end value=$val_arr[1]}
			{assign var=sendname value=$val_arr[2]}
			{assign var=disp_text value=$val_arr[3]}
			{assign var=sel_val value=$val_arr[4]}
			<select name="{$sendname}" class="small">
				{section name=reminder start=$start max=$end loop=$end step=1 }
					{if $smarty.section.reminder.index eq $sel_val}
						{assign var=sel_value value="SELECTED"}
					{else}
						{assign var=sel_value value=""}
					{/if}
					<OPTION VALUE="{$smarty.section.reminder.index}" {$sel_value}>{$smarty.section.reminder.index}</OPTION>
				{/section}
			</select>
			&nbsp;{$disp_text}
		{/foreach}
	</div>
<!-- crmv@16265 -->
{elseif $uitype eq 199}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel}
	<div class="{$DIVCLASS}">
		<input type="password" tabindex="{$vt_tab}" name="{$fldname}" id ="{$fldname}" value="" class="detailedViewTextBox" readonly>
	</div>
<!-- crmv@16265e -->
<!-- crmv@18338 end -->
{elseif $uitype eq 1020}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel}
	<div class="{$DIVCLASS}">
		<input type="hidden" tabindex="{$vt_tab}" name="{$fldname}" id ="{$fldname}" value="{$fldvalue}" class="detailedViewTextBox">
		{$secondvalue}
	</div>
{elseif $uitype eq 1021}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel}
	<div class="{$DIVCLASS}">
		<input type="hidden" tabindex="{$vt_tab}" name="{$fldname}" id ="{$fldname}" value="{$fldvalue}" class="detailedViewTextBox">
		{$secondvalue}
	</div>
<!-- crmv@18338 end -->
{* //crmv@36559 *}
{else}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel}
	<div class="{$DIVCLASS}">
		<input type="hidden" name="{$fldname}" tabindex="{$vt_tab}" value="{$fldvalue}" tabindex="{$vt_tab}" class="detailedViewTextBox">
		{$fldvalue}
	</div>
{/if}
{* //crmv@36559 e *}