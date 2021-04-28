{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@96233 *}

<div>
	<p>{$MOD.LBL_WMAKER_STEP2_INTRO}</p>
</div>
<br>

<table border="0" width="100%">
	
	<tr>
		<td class="mmaker_step_field_cell" align="right"><span>{$APP.LBL_MODULE}</span>&nbsp;&nbsp;</td>
		<td align="left">
			<div class="dvtCellInfoM">
				<select class="detailedViewTextBox" name="wmaker_module" id="wmaker_module">
					{foreach item=pmod from=$MAINMODULES}
						<option value="{$pmod.name}" {if $STEPVARS.wmaker_module eq $pmod.name}selected=""{/if}>{$pmod.label}</option>
					{/foreach}
				</select>
			</div>
		</td>
		<td>&nbsp;</td>
		<td>
			{$MOD.LBL_MAIN_MODULE_DESC}
		</td>
	</tr>

</table>