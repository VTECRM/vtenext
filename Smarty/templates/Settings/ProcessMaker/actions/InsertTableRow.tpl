{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@115268 crmv@131239 crmv@140949 *}
{if $TABLETYPE eq 'ModLight'}
	{include file='Settings/ProcessMaker/actions/Create.tpl'}
	<script type="text/javascript">
		jQuery(document).ready(function() {ldelim}
			{if $SHOW_ACTION_CONDITIONS}
				jQuery.fancybox.showLoading();
				ActionConditionScript.init('{$ID}','{$ELEMENTID}','{$METAID}','{$CYCLE_FIELDNAME}',function(){ldelim}
				jQuery.fancybox.hideLoading();
			{/if}
				ActionCreateScript.loadForm('{$MODULELIGHT}','{$ID}','{$ELEMENTID}','{$ACTIONTYPE}','{$ACTIONID}',true);
			{if $SHOW_ACTION_CONDITIONS}
				{rdelim});
			{/if}
		{rdelim});
	</script>
{else}
	{include file='modules/SDK/src/Reference/Autocomplete.tpl' MODULE='Accounts'}
	<div id="editForm">
		{include file='CreateView.tpl'}
	</div>
	{include file='Settings/ProcessMaker/actions/Create.tpl'}
	<script type="text/javascript">
		jQuery(document).ready(function() {ldelim}
			{if $SHOW_ACTION_CONDITIONS}
				jQuery.fancybox.showLoading();
				ActionConditionScript.init('{$ID}','{$ELEMENTID}','{$METAID}','{$CYCLE_FIELDNAME}',function(){ldelim}
				jQuery.fancybox.hideLoading();
			{/if}
				var params = JSON.parse('{$EDITOPTIONSPARAMS}');
				jQuery.fancybox.showLoading();
				ActionTaskScript.loadFormEditOptions(ActionCreateScript,'{$EDITOPTIONSMODULE}',params);
			{if $SHOW_ACTION_CONDITIONS}
				{rdelim});
			{/if}
		{rdelim});
	</script>
{/if}