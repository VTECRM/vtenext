{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@168103 *}
{* crmv@193710 *}

{include file='Buttons_List.tpl'}

<div id="duplicate_ajax">
	{include file='FindDuplicateAjax.tpl'}
</div>

<div id="current_action" style="display:none">{$smarty.request.action}</div>