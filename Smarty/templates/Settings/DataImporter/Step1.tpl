{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@65455 *}

<div>
	<p>{$MOD.LBL_DIMPORT_STEP1_INTRO}</p>
</div>
<br>

<table border="0" width="100%">
	
	<tr>
		<td class="dimport_step_field_cell" align="right" width="20%"><span>{$APP.LBL_MODULE}</span>&nbsp;&nbsp;</td>
		<td align="left" width="250">
			<div class="dvtCellInfo">
				{if $MODE eq "edit"}
				<input type="text" class="detailedViewTextBox" name="dimport_module" id="dimport_module" value="{$STEPVARS.dimport_module}" readonly="" />
				{else}
				<select class="detailedViewTextBox" name="dimport_module" id="dimport_module" onchange="DataImporter.step1_onModuleSelect()">
					<option value="" {if $STEPVARS.dimport_module eq 0}selected=""{/if}>{$APP.LBL_NONE}</option>
					{foreach item=label key=mod from=$DIMPORT_MODULES}
						<option value="{$mod}" {if $STEPVARS.dimport_module eq $mod}selected=""{/if}>{$label}</option>
					{/foreach}
				</select>
				{/if}
			</div>
		</td>
		<td width="50">&nbsp;</td>
		<td>
			{if $MODE eq "edit"}
			{$MOD.LBL_DIMPORT_MODULE_DESC_RO}
			{else}
			{$MOD.LBL_DIMPORT_MODULE_DESC}
			{/if}
		</td>
	</tr>
	
	<tr id="dimport_cell_invmodule" class="dimport_step_field_cell" {if !$STEPVARS.dimport_invmodule && $STEPVARS.dimport_module != 'ProductRows'}style="display:none"{/if}>
		<td class="dimport_step_field_cell" align="right" width="20%"><span>{$APP.LBL_ASSOCIATED_MODULE}</span>&nbsp;&nbsp;</td>
		<td align="left" width="250">
			<div class="dvtCellInfo">
				{if $MODE eq "edit"}
				<input type="text" class="detailedViewTextBox" name="dimport_invmodule" id="dimport_invmodule" value="{$STEPVARS.dimport_invmodule}" readonly="" />
				{else}
				<select class="detailedViewTextBox" name="dimport_invmodule" id="dimport_invmodule">
					<option value="" {if $STEPVARS.dimport_invmodule eq 0}selected=""{/if}>{$APP.LBL_NONE}</option>
					{foreach item=label key=mod from=$DIMPORT_INVMODULES}
						<option value="{$mod}" {if $STEPVARS.dimport_invmodule eq $mod}selected=""{/if}>{$label}</option>
					{/foreach}
				</select>
				{/if}
			</div>
		</td>
		<td width="50">&nbsp;</td>
		<td>
			{if $MODE eq "edit"}
			{$MOD.LBL_ASSOCIATED_MODULE_DESC_RO}
			{else}
			{$MOD.LBL_ASSOCIATED_MODULE_DESC}
			{/if}
		</td>
	</tr>
	
</table>