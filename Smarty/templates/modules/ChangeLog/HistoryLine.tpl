{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@104566 *}
<div class="history_line">
	<div class="history_line_img">
		{if $line.log.img.element eq 'i'}
			<i class="{$line.log.img.class}" {if !empty($line.log.img.data_first_letter)}data-first-letter="{$line.log.img.data_first_letter}"{/if}>{$line.log.img.html}</i>
		{/if}
	</div>
	<div class="history_line_info">
		<div class="history_line_title">
			<div>
				<div class="history_line_user_img">
					<img src="{$line.user.img}" alt="" title="{$line.user.full_name}" class="userAvatar">
				</div>
				<div class="history_line_user_name">
					{* crmv@164655 *}
					{if $line.user.link}
						<a href="{$line.user.link}">{$line.user.full_name}</a>
					{else}
						{$line.user.full_name}
					{/if}
					{* crmv@164655e *}
				</div>
				<div class="history_line_text">
					{$line.log.text}
				</div>
			</div>
			<div class="history_line_details">
				{include file="modules/ChangeLog/HistoryDetails.tpl"}
			</div>
		</div>
		<div class="history_line_date">
			<a href="javascript:;" title="{$line.date.formatted}" style="color: gray; text-decoration: none;">{$line.date.friendly}</a>
		</div>
	</div>
</div>