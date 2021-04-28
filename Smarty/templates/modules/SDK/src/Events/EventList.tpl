{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@36871 *}
<table border="0" cellpadding="5" cellspacing="0" width="100%" class="hdrNameBg" id="events_list_table">
	{foreach item=eventperiod key=timestampAgo from=$EVENTLIST_DATE}
		{counter assign=rowid}
		{assign var=rowidstr value="events_list_tbody_$rowid"}
		{assign var=period_count value=$eventperiod|@count}
		{assign var=hidenext value=true}
		<tr id="{$rowidstr}_toggle">
			<td colspan="3" onclick="toggleEventPeriod('{$rowidstr}');" class="level3Bg" style="cursor:pointer;">
				<img width="15" border="0" align="top" src="themes/images/{if $rowid neq 1}open_details{else}close_details{/if}.png" id="{$rowidstr}_img">&nbsp;{$timestampAgo} ({$period_count})
			</td>
		</tr>
		<tbody id="{$rowidstr}" style="display:{if $rowid neq 1}none{else}block{/if}">
		{foreach item=eventrow from=$eventperiod}
			<tr id="events_list_row_{$eventrow.activityid}">
				<td class="trackerListBullet small" align="center" width="12">
					<input type="checkbox" id="event_{$eventrow.activityid}" onClick="closeEvent({$eventrow.activityid},this.checked);" title="{'LBL_COMPLETED'|getTranslatedString:'Calendar'}" style="cursor: pointer;" />
				</td>
				<td class="trackerList small {if $eventrow.unseen}ModCommUnseen{/if}" width="288">
					<a href="index.php?module=Calendar&action=DetailView&record={$eventrow.activityid}">{$eventrow.subject}</a>
					<br />{$eventrow.expired_str} <a href="javascript:;" class="{if $eventrow.unseen}ModCommUnseen{/if}" style="color: gray; text-decoration:none;" title="{$eventrow.timestamp}">{$eventrow.timestamp_ago}</span>
				</td>
				<td class="trackerList small {if $eventrow.unseen}ModCommUnseen{/if}" width="288">{$eventrow.description}</td>
			</tr>
		{/foreach}
		</tbody>
	{/foreach}

</table>