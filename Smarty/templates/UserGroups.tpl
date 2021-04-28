{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
<table class="listTable" border="0" cellpadding="5" cellspacing="0" width="100%">
<tr>
<td class="colHeader small" valign="top">#</td>
<td class="colHeader small" valign="top">{$UMOD.LBL_GROUP_NAME}</td>
<td class="colHeader small" valign="top">{$UMOD.LBL_DESCRIPTION}</td>
</tr>
{foreach name=groupiter key=id item=groupname from=$GROUPLIST}
<tr>
<td class="listTableRow small" valign="top" width="5%">{$smarty.foreach.groupiter.iteration}</td>
{if $IS_ADMIN}
<td class="listTableRow small" valign="top"><a href="index.php?module=Settings&action=GroupDetailView&parenttab=Settings&groupId={$id}">{$groupname.1}</a></td>
{else}
<td class="listTableRow small" valign="top">{$groupname.1}</td>
{/if}
<td class="listTableRow small" valign="top" width="65%">{$groupname.2}</td>
</tr>
{/foreach}
</table>