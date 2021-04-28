{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@183346 *}
{include file='CachedValues.tpl'}
{include file='modules/SDK/src/Reference/Autocomplete.tpl'}

{include file='Settings/ProcessMaker/actions/Create.tpl' SKIP_EDITFORM=1}

<div id="editForm">
{include file='salesEditView.tpl' HIDE_BUTTON_LIST=1}
</div>

<script type="text/javascript">
jQuery(document).ready(function() {ldelim}
	ActionModNotificationScript.loadForm('ModNotifications','{$ID}','{$ELEMENTID}','{$ACTIONTYPE}','{$ACTIONID}');
{rdelim});
</script>