{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@192843 crmv@192843 *}

<table class="messages-account-list" cellspacing="0" cellpadding="0" width="100%">
	{if empty($MERGE_ACCOUNT_FOLDERS)}
		<tr>
			<td colspan="2" class="folderMessageRow gray">
				<div style="padding-left: 10px; padding-right: 3px;">
					{'LBL_ACCOUNT_INBOXLIST'|getTranslatedString:'Messages'}
				</div>
			</td>
		</tr>
	{/if}
	{if !empty($FAST_LINKS)}
		{foreach item=entity from=$FAST_LINKS}
			<tr class="lvtColDataMessage" onMouseOut="this.className='lvtColDataMessage'" onMouseOver="this.className='lvtColDataHoverMessage'">
				<td colspan="2" class="folderMessageRow listMessageFrom" style="cursor: pointer;" onClick="selectINBOXFolder('{$DIV_DIMENSION.Folders}','{$DIV_DIMENSION.ListViewContents}','{$entity.account}','{$entity.id}','{$entity.description}','{$entity.id|rawurlencode}')"> {* crmv@131944 *}
					<div style="padding-left: 10px; padding-right: 3px;">
						<table cellpadding="0" cellspacing="0" width="100%">
						<tr>
							<td width="100%">
								{if !empty($entity.vteicon)}
									<i class="vteicon" style="padding-right:10px; vertical-align:middle; ">{$entity.vteicon}</i>
								{/if}
								{$entity.description}
								{if $entity.count gt 0}
									<div style="margin-top:6px;float:right;">
										{include file="BubbleNotification.tpl" COUNT=$entity.count BN_BGCOLOR=$entity.bg_notification_color}
									</div>
								{/if}
							</td>
						</tr>
						</table>
					</div>
				</td>
			</tr>
		{/foreach}
		<tr>
			<td colspan="2" class="folderMessageRow gray">
				&nbsp;
			</td>
		</tr>
		<tr>
			<td colspan="2" class="folderMessageRow gray">
				<div style="padding-left: 10px; padding-right: 3px;">
					{'LBL_ACCOUNTS'|getTranslatedString:'Messages'}
				</div>
			</td>
		</tr>
	{/if}
	{foreach item=entity from=$ACCOUNTS}
		<tr class="lvtColDataMessage" onMouseOut="this.className='lvtColDataMessage'" onMouseOver="this.className='lvtColDataHoverMessage'">
			<td colspan="2" class="folderMessageRow listMessageFrom" style="cursor: pointer;" onClick="selectAccount('folders','{$DIV_DIMENSION.Folders}','{$DIV_DIMENSION.ListViewContents}','{$entity.id}','{$entity.description}')">
				<div style="padding-left: 10px; padding-right: 3px;">
					<table cellpadding="0" cellspacing="0" width="100%">
					<tr>
						<td width="100%">
							{if !empty($entity.img)}<img src="{$entity.img}" style="padding-right:10px;" />{/if}{$entity.description}
						</td>
					</tr>
					</table>
				</div>
			</td>
		</tr>
	{/foreach}
</table>