{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
 
{* crmv@3082m *}

{foreach item=list from=$LIST}
	<div class="cpanel_div" style="min-height: 0px;">
		<table border="0" cellpadding="0" cellspacing="5" width="100%">
			<tr>
				{if $list.edit eq true}
					<td width="16"><a href="index.php?module=Messages&action=MessagesAjax&file=Settings/index&operation=EditPop3&id={$list.id}"><img src="{'small_edit.png'|resourcever}" title="{'LBL_EDIT'|getTranslatedString}" alt="{'LBL_EDIT'|getTranslatedString}" border="0"></a></td>
				{/if}
				{if $list.delete eq true}
					<td width="16"><a href="index.php?module=Messages&action=MessagesAjax&file=Settings/index&operation=DeletePop3&id={$list.id}"><img src="{'small_delete.png'|resourcever}" title="{'LBL_DELETE'|getTranslatedString}" alt="{'LBL_DELETE'|getTranslatedString}" border="0"></a></td>
				{/if}
				<td align="left" style="padding-left:10px;">
					{'LBL_POP3_FETCH_FROM'|getTranslatedString:'Messages'} <b>{$list.username}</b> {'LBL_POP3_FETCH_IN'|getTranslatedString:'Messages'} <b>{$list.folder}</b>
				</td>
			</tr>
		</table>
	</div>
{/foreach}