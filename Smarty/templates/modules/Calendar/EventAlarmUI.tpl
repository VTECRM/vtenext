{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@98866 *} {* crmv@181170 *}

{if $LABEL.reminder_time != ''}
<table bgcolor="#FFFFFF" width="100%">
	{assign var=secondval value=$calsecondvalue.reminder_time}
	{assign var=check value=$secondval[0]}
	{assign var=yes_val value=$secondval[1]}
	{assign var=no_val value=$secondval[2]}
	<td style="width:25%"><strong>{$MOD.LBL_SENDREMINDER}</strong></td>
	<td style="width:75%">
		<div class="radio radio-primary">
			<label><input type="radio" name="set_reminder" value="Yes" {$check} onClick="showBlock('reminderOptions')">{$yes_val}&nbsp;</label>
			<label><input type="radio" name="set_reminder" value="No" onClick="fnhide('reminderOptions')">{$no_val}&nbsp;</label>
		</div>
	</td>
</table>
{if $check eq 'CHECKED'}
	{assign var=reminstyle value='style="display:block;width:100%"'}
{else}
	{assign var=reminstyle value='style="display:none;width:100%"'}
{/if}
<div id="reminderOptions" {$reminstyle}>
	<table border=0 cellspacing=0 cellpadding=2  width=100% bgcolor="#FFFFFF">
		<tr>
			<td nowrap align=left width=20%>
				<b>{$MOD.LBL_RMD_ON} : 
				</b>
			</td>
			<td width=80%>
				<table border=0 width="100%">
					<tr style="height:15px"></tr>
					<tr>
					{foreach item=val_arr from=$ACTIVITYDATA.reminder_time}
						<td class="dvtCellInfo">
						{assign var=start value=$val_arr[0]}
						{assign var=end value=$val_arr[1]}
						{assign var=sendname value=$val_arr[2]}
						{assign var=disp_text value=$val_arr[3]}
						{assign var=sel_val value=$val_arr[4]}
						<label for="{$sendname}">{$disp_text}</label>
						<br>
						<select name="{$sendname}" class="small detailedViewTextBox" id="{$sendname}">
							{section name=reminder start=$start max=$end loop=$end step=1}
								{if $smarty.section.reminder.index eq $sel_val}
									{assign var=sel_value value="SELECTED"}
								{else}
									{assign var=sel_value value=""}
								{/if}
								<option value="{$smarty.section.reminder.index}" {$sel_value}>{$smarty.section.reminder.index}</option>
							{/section}
						</select>
						</td>
					{/foreach}
					</tr>
					<tr style="height:15px"></tr>
					<tr><td colspan="3" align="center">{$MOD.LBL_BEFOREEVENT}</td></tr>
				</table>
			</td>
		</tr>
	</table>
</div>
{/if}