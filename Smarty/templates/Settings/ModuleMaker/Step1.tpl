{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@64542 *}

<div>
	<p>{$MOD.LBL_MMAKER_STEP1_INTRO}</p>
</div>
<br>

<table border="0" width="100%">
	<tr>
		<td class="mmaker_step_field_cell" align="right" width="20%"><span>{$MOD.LBL_MODULELABEL}</span>&nbsp;&nbsp;</td>
		<td align="left" width="250">
			<div class="dvtCellInfoM">
				<input class="detailedViewTextBox" type="text" name="mmaker_modlabel" id="mmaker_modlabel" value="{$STEPVARS.mmaker_modlabel}" maxlength="40" onkeyup="ModuleMaker.step1_onModuleLabelKey(this)" />
			</div>
		</td>
		<td width="50">&nbsp;</td>
		<td>
			{$MOD.LBL_MODULELABEL_DESC}
		</td>
	</tr>
	
	<tr>
		<td class="mmaker_step_field_cell" align="right"><span>{$MOD.LBL_MODULESINGLELABEL}</span>&nbsp;&nbsp;</td>
		<td align="left">
			<div class="dvtCellInfoM">
				<input class="detailedViewTextBox" type="text" name="mmaker_single_modlabel" id="mmaker_single_modlabel" value="{$STEPVARS.mmaker_single_modlabel}" maxlength="40" />
			</div>
		</td>
		<td>&nbsp;</td>
		<td>
			{$MOD.LBL_MODULESINGLELABEL_DESC}
		</td>
	</tr>
	
	<tr>
		<td class="mmaker_step_field_cell" align="right"><span>{$MOD.LBL_MODULENAME}</span>&nbsp;&nbsp;</td>
		<td align="left">
			<div class="dvtCellInfo">
				<input class="detailedViewTextBox" type="text" name="mmaker_modname" id="mmaker_modname" value="{$STEPVARS.mmaker_modname}" maxlength="20" readonly="" />
			</div>
		</td>
		<td>&nbsp;</td>
		<td>
			{$MOD.LBL_MODULENAME_DESC}
		</td>
	</tr>
	
	<tr>
		<td class="mmaker_step_field_cell" align="right"><span>{$MOD.LBL_RECORD_IDENTIFIER}</span>&nbsp;&nbsp;</td>
		<td align="left">
			<div class="dvtCellInfoM">
				<input class="detailedViewTextBox" type="text" name="mmaker_mainfield" id="mmaker_mainfield" value="{$STEPVARS.mmaker_mainfield}" maxlength="40" />
			</div>
		</td>
		<td>&nbsp;</td>
		<td>
			{$MOD.LBL_RECORD_IDENTIFIER_DESC}
		</td>
	</tr>
	
	{if $AREAS_SUPPORT}
	<tr>
		<td class="mmaker_step_field_cell" align="right"><span>{$APP.LBL_AREA}</span>&nbsp;&nbsp;</td>
		<td align="left">
			<div class="dvtCellInfo">
				<select class="detailedViewTextBox" name="mmaker_areaid" id="mmaker_areaid">
					<option value="0" {if $STEPVARS.mmaker_areaid eq 0}selected=""{/if}>{$APP.LBL_NONE}</option>
					{foreach item=area from=$AREAS}
						<option value="{$area.areaid}" {if $STEPVARS.mmaker_areaid eq $area.areaid}selected=""{/if}>{$area.label}</option>
					{/foreach}
				</select>
			</div>
		</td>
		<td>&nbsp;</td>
		<td>
			{$MOD.LBL_AREA_DESC}
		</td>
	</tr>
	{/if}

	{if $CAN_CREATE_INVENTORY}
	<tr>
		<td class="mmaker_step_field_cell" align="right"><span>{$MOD.LBL_INVENTORYMODULE}</span>&nbsp;&nbsp;</td>
		<td align="left">
			<div class="dvtCellInfo">
				<input type="checkbox" name="mmaker_inventory" id="mmaker_inventory" {if $STEPVARS.mmaker_inventory}checked=""{/if}/>
			</div>
		</td>
		<td>&nbsp;</td>
		<td>
			{$MOD.LBL_INVENTORYMODULE_DESC}
		</td>
	</tr>
	{/if}
	
</table>