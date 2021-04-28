{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@188364 *}
<div class="history_line">
	<div class="history_line_img">
		<i class="vteicon">{$line.img}</i>
	</div>
	<div class="history_line_info">
		<div class="history_line_title">
			<div>
				<div class="history_line_user_img">
					<img src="{$line.userimg}" alt="" title="{$line.username}" class="userAvatar">
				</div>
				<div class="history_line_date">
					{if isset($line.interval)}
						<span style="color: gray; text-decoration: none;">{$line.interval} ({$line.fulldate})</span>
					{else}
						<span style="color: gray; text-decoration: none;">{$line.fulldate}</span>
					{/if}
				</div>
				<div class="history_line_user_name">
					{if $line.userlink}
						<a href="{$line.userlink}">{$line.userfullname}</a>
					{else}
						{$line.userfullname}
					{/if}
				</div>
				<div class="history_line_text">
					{$line.text}
				</div>
			</div>
			<div class="history_line_details">
				{include file="modules/Processes/HistoryDetails.tpl"}
			</div>
		</div>
		<div class="history_line_date">
			{if $line.duration !== false}
				<span style="color: gray;">{'Duration'|getTranslatedString:'Calendar'}: {$line.duration}</span>
			{/if}
		</div>
	</div>
</div>