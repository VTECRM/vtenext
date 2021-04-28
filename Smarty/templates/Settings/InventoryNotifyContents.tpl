{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
<table width="100%" cellpadding="5" cellspacing="0" class="listTable" >
	<tr>
	<td class="colHeader small" width="5%">#</td>
	<td class="colHeader small" width="40%">{$CMOD.LBL_NOTIFICATION}</td>
	<td class="colHeader small" width="50%">{$CMOD.LBL_DESCRIPTION}</td>
	<td class="colHeader small" width="5%">{$CMOD.Tools}</td>
	</tr>
	{foreach name=notifyfor item=elements from=$NOTIFICATION}
	<tr>
	<td class="listTableRow small">{$smarty.foreach.notifyfor.iteration}</td>
	<td class="listTableRow small">{$elements.notificationname}</td>
	<td class="listTableRow small">{$elements.label}</td>
	<td class="listTableRow small" align="center" ><img onClick="fnvshobj(this,'editdiv');fetchEditNotify('{$elements.id}');" style="cursor:pointer;" src="{'editfield.gif'|resourcever}" title="{$APP.LBL_EDIT}"></td>
	</tr>
	{/foreach}
	</table>
