{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@112297 crmv@115268 *}
<table align=center width=100% id='rule_table'>
	<tr>
		<td class="colHeader"></td>
		<td class="colHeader small" align="center">
			{'LBL_FIELD_VALUE'|getTranslatedString:'com_workflow'}
		</td>
		<td class="colHeader small" align="center">
			{'LBL_FPOFV_MANAGE_PERMISSION'|getTranslatedString:'Conditionals'}<br>
			<input type="checkbox" name="FpovManaged" onClick="setAll(this.checked,'FpovManaged');" value="0" id="FpovManaged" >
		</td>
		<td class="colHeader small" align="center">
			{'LBL_FPOFV_READ_PERMISSION'|getTranslatedString:'Conditionals'}<br>
			<input type="checkbox" name="FpovReadPermission" onClick="setAll(this.checked,'FpovReadPermission');" value="0" id="FpovReadPermission" >
		</td>
		<td class="colHeader small" align="center">
			{'LBL_FPOFV_WRITE_PERMISSION'|getTranslatedString:'Conditionals'}<br>
			<input type="checkbox" name="FpovWritePermission" onClick="setAll(this.checked,'FpovWritePermission');" value="0" id="FpovWritePermission" >
		</td>
		<td class="colHeader small" align="center">
			{'LBL_FPOFV_MANDATORY_PERMISSION'|getTranslatedString:'Conditionals'}<br>
			<input type="checkbox" name="FpovMandatoryPermission" onClick="setAll(this.checked,'FpovMandatoryPermission');" value="0" id="FpovMandatoryPermission" >		
		</td>
	</tr>       
	{assign var=current_block_label value=""}           
	{foreach from=$FPOFV_PIECE_DATA item=field_piece_of_data key=index}
		<tr>
			{if $current_block_label neq $field_piece_of_data.FpofvBlockLabel}
				<tr>
					<td colspan=6 class="colHeader small">
						{$field_piece_of_data.FpofvBlockLabel}
					</td>
				</tr>       
				{assign var=current_block_label value=$field_piece_of_data.FpofvBlockLabel}			
			{/if}
			<td align=left class="listTableRow small">
   				{$field_piece_of_data.TaskFieldLabel}
   			</td>
			<td align=left class="listTableRow small">
				{if $field_piece_of_data.HideFpovValue eq true}
   					{assign var="display" value="none"}
   				{else}
   					{assign var="display" value="block"}
   				{/if}
				<div style="float:left; width:10%; display:{$display}"><input type="checkbox" name="FpovValueActive{$field_piece_of_data.TaskField}" value="1" onClick="toggleValue('{$field_piece_of_data.TaskField}')" {if $field_piece_of_data.FpovValueActive eq "1"}checked{/if}>&nbsp;</div>
   				<div style="float:left; width:90%; display:{$display}">
   					<select id="FpovValueOpt{$field_piece_of_data.TaskField}" style="{if $field_piece_of_data.FpovValueActive eq "1"}display:block;{else}display:none{/if}" onChange="ProcessMakerScript.populateField(this,'FpovValueStr{$field_piece_of_data.TaskField}')">
   						<option value="">{'LBL_SELECT_OPTION_DOTDOTDOT'|getTranslatedString:'com_workflow'}</option>
   						{* <option value="current">{'LBL_FPOFV_CURRENT_VALUE'|getTranslatedString:'Settings'}</option> *}
						{if !empty($SDK_CUSTOM_FUNCTIONS)}
							{foreach key=SDK_CUSTOM_FUNCTIONS_BLOCK_LABEL item=SDK_CUSTOM_FUNCTIONS_BLOCK from=$SDK_CUSTOM_FUNCTIONS}
							<optgroup label="{$SDK_CUSTOM_FUNCTIONS_BLOCK_LABEL}">
								{foreach key=k item=i from=$SDK_CUSTOM_FUNCTIONS_BLOCK}
									<option value="{$k}">{$i}</option>
								{/foreach}
							</optgroup>
							{/foreach}
						{/if}
						{if !empty($FPOFV_VALUE_OPTIONS)}
							{foreach key=k item=i from=$FPOFV_VALUE_OPTIONS}
								<option value="{$k}">{$i}</option>
							{/foreach}
						{/if}
   					</select>
   				</div>
   				<input type="text" class="detailedViewTextBox" id="FpovValueStr{$field_piece_of_data.TaskField}" name="FpovValueStr{$field_piece_of_data.TaskField}" value="{$field_piece_of_data.FpovValueStr}" style="{if $field_piece_of_data.FpovValueActive eq "1"}display:block;{else}display:none{/if}">
   			</td>
   			<td align=center class="listTableRow small" width=15%>
   				{if $field_piece_of_data.HideFpovManaged eq true}
   					{assign var="display" value="none"}
   				{else}
   					{assign var="display" value="block"}
   				{/if}
   				&nbsp;<input type="checkbox" name="FpovManaged{$field_piece_of_data.TaskField}" onClick="toggle_permissions('{$field_piece_of_data.TaskField}');" value="1" id="FpovManaged{$field_piece_of_data.TaskField}" {if $field_piece_of_data.FpovManaged eq "1"}checked{/if} style="display:{$display}">&nbsp;
   			</td>
   			<td align=center class="listTableRow small" width=15%>
   				{if $field_piece_of_data.HideFpovReadPermission eq true}
   					{assign var="display" value="none"}
   				{else}
   					{assign var="display" value="block"}
   				{/if}
				&nbsp;<input type="checkbox" name="FpovReadPermission{$field_piece_of_data.TaskField}" onClick="toggle_permissions('{$field_piece_of_data.TaskField}');" value="1" id="FpovReadPermission{$field_piece_of_data.TaskField}" {if $field_piece_of_data.FpovReadPermission eq "1"}checked{/if} {if $field_piece_of_data.FpovManaged eq "1"}{else}disabled{/if} style="display:{$display}">&nbsp;
			</td>
			<td align=center class="listTableRow small" width=15%>
				{if $field_piece_of_data.HideFpovWritePermission eq true}
   					{assign var="display" value="none"}
   				{else}
   					{assign var="display" value="block"}
   				{/if}
				&nbsp;<input type="checkbox" name="FpovWritePermission{$field_piece_of_data.TaskField}" onClick="toggle_permissions('{$field_piece_of_data.TaskField}');" value="1" id="FpovWritePermission{$field_piece_of_data.TaskField}" {if $field_piece_of_data.FpovWritePermission eq "1"}checked{/if} {if $field_piece_of_data.FpovManaged eq "1"}{else}disabled{/if} style="display:{$display}">&nbsp;
			</td>
			<td align=center class="listTableRow small" width=15%>
				{if $field_piece_of_data.HideFpovMandatoryPermission eq true}
   					{assign var="display" value="none"}
   				{else}
   					{assign var="display" value="block"}
   				{/if}
				&nbsp;<input type="checkbox" name="FpovMandatoryPermission{$field_piece_of_data.TaskField}" onClick="toggle_permissions('{$field_piece_of_data.TaskField}');" value="1" id="FpovMandatoryPermission{$field_piece_of_data.TaskField}" {if $field_piece_of_data.FpovMandatoryPermission eq "1"}checked{/if} {if $field_piece_of_data.FpovManaged eq "1"}{else}disabled{/if} style="display:{$display}">&nbsp;
			</td>
		</tr>                   			
	{/foreach}
</table>