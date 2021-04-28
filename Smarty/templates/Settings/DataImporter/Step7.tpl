{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@65455 *}

<div>
	<p>{$MOD.LBL_DIMPORT_STEP7_INTRO}</p>
</div>
<br>

<table border="0" width="100%">
	
	<tr>
		<td class="dimport_step_field_cell" align="right" width="20%"><span>{$APP.LBL_USER}</span>&nbsp;&nbsp;</td>
		<td align="left" width="250">
			<div class="dvtCellInfo">
				<select class="detailedViewTextBox" name="dimport_notifyto" id="dimport_notifyto">
					<option value="" {if $STEPVARS.dimport_notifyto eq 0}selected=""{/if}>{$APP.LBL_NONE}</option>
					{foreach item=label key=userid from=$USERS_LIST}
						<option value="{$userid}" {if $STEPVARS.dimport_notifyto eq $userid}selected=""{/if}>{$label}</option>
					{/foreach}
				</select>
			</div>
		</td>
		<td width="50">&nbsp;</td>
		<td>
			{$MOD.LBL_DIMPORT_NOTIFYTO_DESC}
		</td>
	</tr>
	
</table>