{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@96233 *}

<div>
	<p>{$MOD.LBL_WMAKER_STEP4_INTRO}</p>
</div>
<br>

{assign var=RELATIONS value=$STEPVARS.wmaker_relations}

<table border="0" width="100%">
	<tr>
		<td class="mmaker_step_field_cell" align="right" width="20%"><span>{$APP.LBL_RELATIONS}</span>&nbsp;&nbsp;</td>
		<td align="left" width="250">
			<div class="dvtCellInfo">
				<select class="detailedViewTextBox" type="text" name="wmaker_relations[]" id="wmaker_relations" multiple size="10">
					{foreach item=rel from=$RELATIONS}
						<option value="{$rel.module}" {if $rel.selected}selected=""{/if}>{$rel.label}</option>
					{/foreach}
				</select>
			</div>
		</td>
		<td width="50">&nbsp;</td>
		<td>
			{$MOD.LBL_RELATIONS_DESC}
		</td>
	</tr>
	
</table>