{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@96233 *}

<div>
	<p>{$MOD.LBL_WMAKER_STEP1_INTRO}</p>
</div>
<br>

<table border="0" width="100%">
	<tr>
		<td class="mmaker_step_field_cell" align="right" width="20%"><span>{$MOD.LBL_WIZARDLABEL}</span>&nbsp;&nbsp;</td>
		<td align="left" width="250">
			<div class="dvtCellInfoM">
				<input class="detailedViewTextBox" type="text" name="wmaker_name" id="wmaker_name" value="{$STEPVARS.wmaker_name}" maxlength="60" />
			</div>
		</td>
		<td width="50">&nbsp;</td>
		<td>
			{$MOD.LBL_WIZARDLABEL_DESC}
		</td>
	</tr>
	
	<tr>
		<td class="mmaker_step_field_cell" align="right"><span>{$APP.LBL_PARENT_MODULE}</span>&nbsp;&nbsp;</td>
		<td align="left">
			<div class="dvtCellInfo">
				<select class="detailedViewTextBox" name="wmaker_parentmodule" id="wmaker_parentmodule">
					<option value="" {if $STEPVARS.wmaker_parentmodule eq ""}selected=""{/if}>{$APP.LBL_NONE}</option>
					{foreach item=pmod from=$PARENTMODULES}
						<option value="{$pmod.name}" {if $STEPVARS.wmaker_parentmodule eq $pmod.name}selected=""{/if}>{$pmod.label}</option>
					{/foreach}
				</select>
			</div>
		</td>
		<td>&nbsp;</td>
		<td>
			{$MOD.LBL_PARENT_MODULE_DESC}
		</td>
	</tr>

	<tr>
		<td class="mmaker_step_field_cell" align="right" width="20%"><span>{$APP.LBL_DESCRIPTION}</span>&nbsp;&nbsp;</td>
		<td align="left" width="250">
			<div class="dvtCellInfo">
				<textarea class="detailedViewTextBox" name="wmaker_description" id="wmaker_description" rows="5" />{$STEPVARS.wmaker_description}</textarea>
			</div>
		</td>
		<td width="50">&nbsp;</td>
		<td>
			&nbsp;
		</td>
	</tr>
	
</table>