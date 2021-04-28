{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
 
 {* crmv@140887 *}

<!-- module header -->
<script language="JavaScript" type="text/javascript" src="{"include/js/ListView.js"|resourcever}"></script>
<script language="JavaScript" type="text/javascript" src="{"include/js/search.js"|resourcever}"></script>
<script language="JavaScript" type="text/javascript" src="{"include/js/Merge.js"|resourcever}"></script> {* crmv@8719 *}
<script language="JavaScript" type="text/javascript" src="{"modules/`$MODULE`/`$MODULE`.js"|resourcever}"></script>

{* crmv@160778 *}
{if $MODULE eq 'Calendar'}
	<script language="JavaScript" type="text/javascript" src="{"include/js/ModuleHome.js"|resourcever}"></script>
{/if}
{* crmv@160778e *}

{* crmv@43835 *}
<script type="text/javascript">
var gridsearch;
</script>
{* crmv@43835e *}

<input type="hidden" id="user_dateformat" name="user_dateformat" value="{$DATEFORMAT}">
<textarea name="select_ids" id="select_ids" style="display:none;"></textarea>

{include file='Buttons_List.tpl'}

<div class="container-fluid mainContainer">
	<div class="row">
		<div class="col-sm-12">
			{* crmv@8719 *}
			<div id="mergeDup" class="menuSeparation" style="z-index:1;display:none;position:relative;padding:10px;">
				{include file="MergeColumns.tpl"}
			</div>
			{* crmv@8719e *}

			<!-- PUBLIC CONTENTS STARTS-->
		    <div id="ListViewContents" class="small" style="width:100%;position:relative;">
		    	{include file="ListViewEntries.tpl" MOD=$MOD} {* crmv@vte10usersFix *}
		    </div>
		    
			<div id="UnifiedSearchAreasUnifiedRow1_Cont" style="display:none;">
				<form id="basicSearch1" name="basicSearch1" method="post" action="index.php" onsubmit="callSearch('BasicGlobalSearch', {$FOLDERID});return false;">
					<input type="hidden" name="searchtype" value="BasicSearch" />
					<input type="hidden" name="module" value="{$MODULE}" />
					<input type="hidden" name="parenttab" value="{$CATEGORY}" />
					<input type="hidden" name="action" value="index" />
					<input type="hidden" name="query" value="true" />
					<input type="hidden" id="basic_search_cnt" name="search_cnt" />
					
					<table cellspacing="0" cellpadding="0" width="100%">
						<tr>
							<td width="100%">
								<button class="crmbutton" style="width:100%">{$APP.LBL_SEARCH_TITLE}{$MODULE|getTranslatedString:$MODULE}</button>
							</td>
							<td nowrap>
								<span class="advSearchIcon" style="padding-left:10px">
									<a href="javascript:;" onclick="advancedSearchOpenClose();updatefOptions(document.getElementById('Fields0'), 'Condition0');">
										{$APP.LNK_ADVANCED_SEARCH}
										<i id="adv_search_icn_go" class="vteicon" title="{$APP.LNK_ADVANCED_SEARCH}" style="vertical-align:middle">keyboard_arrow_down</i>
									</a>
								</span>
							</td>
						</tr>
					</table>
				</form>
				
				{include file="AdvancedSearch.tpl"}
			</div>
		</div>
	</div>
</div>

{assign var="FLOAT_TITLE" value=$APP.LBL_MASSEDIT_FORM_HEADER}
{assign var="FLOAT_WIDTH" value="780px"}
{capture assign="FLOAT_BUTTONS"}
	<button type="button" title="{$APP.LBL_SAVE_BUTTON_TITLE}" accessKey="{$APP.LBL_SAVE_BUTTON_KEY}" class="crmbutton small save" onclick="jQuery('#massedit_form input[name=action]').val('MassEditSave'); if (massEditFormValidate()) jQuery('#massedit_form').submit();" name="button" style="min-width:70px" >{$APP.LBL_SAVE_BUTTON_LABEL}</button>
{/capture}
{capture assign="FLOAT_CONTENT"}
<div id="massedit_form_div" style="overflow:auto"></div>
{/capture}
{include file="FloatingDiv.tpl" FLOAT_ID="massedit"}

<form name="SendMail"><div id="sendmail_cont" style="z-index:100001;position:absolute;"></div></form>
<form name="SendFax"><div id="sendfax_cont"></div></form>
<form name="SendSms" id="SendSms" method="POST" action="index.php"><div id="sendsms_cont"></div></form>	{* crmv@16703 *}

<script type="text/javascript">
	var mainContainer = jQuery('body').get(0);
	var wrapperHeight = parseInt(visibleHeight(mainContainer));
	
	var buttonList = jQuery('#Buttons_List_HomeMod').get(0);
	var buttonListHeight = parseInt(visibleHeight(buttonList));
	
	jQuery('#ModuleHomeMatrix').css('min-height', (wrapperHeight-buttonListHeight-33) + 'px');
	
	{* crmv@163519 *}
	{if $SEARCH_URL neq ''}
		setTimeout(function() {ldelim}
			var target = jQuery('[data-module="GlobalSearch"]');
			VTE.FastPanelManager.mainInstance.visible = false;
			VTE.FastPanelManager.mainInstance.mask = false;
			VTE.FastPanelManager.mainInstance.showModuleHome(target.attr('data-id'), 'GlobalSearch');
			VTE.FastPanelManager.mainInstance.mask = true;
			VTE.FastPanelManager.mainInstance.visible = true;
			VTE.FastPanelManager.mainInstance.isOpen = false;
		{rdelim}, 300);
	{/if}
	{* crmv@163519e *}
</script>