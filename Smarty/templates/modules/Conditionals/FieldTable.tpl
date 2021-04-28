{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@115268 *}
<table align=center width=100% id='rule_table' > 
<tr>
	<td></td>
	<td class="colHeader small" align="center">
		{$UMOD.LBL_FPOFV_MANAGE_PERMISSION}<br>
		<input type="checkbox" name="FpovManaged" onClick="setAll(this.checked,'FpovManaged');" value="0" id="FpovManaged" >
	</td>
	<td class="colHeader small" align="center">
		{$UMOD.LBL_FPOFV_READ_PERMISSION}<br>
		<input type="checkbox" name="FpovReadPermission" onClick="setAll(this.checked,'FpovReadPermission');" value="0" id="FpovReadPermission" >
	</td>
	<td class="colHeader small" align="center">
		{$UMOD.LBL_FPOFV_WRITE_PERMISSION}<br>
		<input type="checkbox" name="FpovWritePermission" onClick="setAll(this.checked,'FpovWritePermission');" value="0" id="FpovWritePermission" >
	</td>
	<td class="colHeader small" align="center">
		{$UMOD.LBL_FPOFV_MANDATORY_PERMISSION}<br>
		<input type="checkbox" name="FpovMandatoryPermission" onClick="setAll(this.checked,'FpovMandatoryPermission');" value="0" id="FpovMandatoryPermission" >		
	</td>
</tr>       
	{assign var=current_block_label value=""}           
	{foreach from=$FPOFV_PIECE_DATA item=field_piece_of_data key=index}
		<tr>
			{if $current_block_label neq $field_piece_of_data.FpofvBlockLabel}
				<tr>
					<td colspan=5 class="colHeader small">
						{if $TOMOD[$field_piece_of_data.FpofvBlockLabel] neq '' }
							{$TOMOD[$field_piece_of_data.FpofvBlockLabel]}
						{else}
							{$field_piece_of_data.FpofvBlockLabel}
						{/if} 
					</td>
				</tr>       
			
				{assign var=current_block_label value=$field_piece_of_data.FpofvBlockLabel}			
			{/if}
		
			<td align=left class="listTableRow small">
   				{if $TOMOD[$field_piece_of_data.TaskFieldLabel] ne ''}
   					{$TOMOD[$field_piece_of_data.TaskFieldLabel]}
   				{else}
   					{if $APP[$field_piece_of_data.TaskFieldLabel] ne ''}
   						{$APP[$field_piece_of_data.TaskFieldLabel]}
   					{else}
   						{$field_piece_of_data.TaskFieldLabel}
   					{/if}
   				{/if}
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