{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@102334 *}
<div id="Buttons_List_3">
	<table id="bl3" border=0 cellspacing=0 cellpadding=2 width=100% class="small">{*crmv@22259*}
		<tr>
			<!-- Buttons -->
			<td style="padding:5px" nowrap>

				{* crmv@vte10usersFix *}
                {if $MODULE eq 'Calendar'}
                	<button type="button" class="crmbutton small edit" onclick="listToCalendar('Today')">{$MOD.LBL_DAY}</button>
                	<button type="button" class="crmbutton small edit" onclick="listToCalendar('This Week')">{$MOD.LBL_WEEK}</button>
                	<button type="button" class="crmbutton small edit" onclick="listToCalendar('This Month')">{$MOD.LBL_MON}</button>
                	<button type="button" class="crmbutton small edit">{$MOD.LBL_CAL_TO_FILTER}</button>
                {/if}
                {* crmv@vte10usersFix e *}

				{* crmv@30967 crmv@7216 crmv@7217 crmv@9183 *}
				{foreach key=button_check item=button_label from=$BUTTONS}
					{if $button_check eq 'back'}
						{if $FOLDERID > 0}
							<a href="index.php?module={$MODULE}&action=index"><img src="{'folderback.png'|resourcever}" alt="{$APP.LBL_GO_BACK}" title="{$APP.LBL_GO_BACK}" align="absbottom" border="0" /></a>
						{else}
							<button type="button" class="crmbutton small edit" onclick="location.href='index.php?module={$MODULE}&amp;action=index';" >{$APP.LBL_FOLDERS}</button>
						{/if}
					{elseif $button_check eq 'del'}
						<button type="button" class="crmbutton small delete" onclick="return massDelete('{$MODULE}')">{$button_label}</button>
					{elseif $button_check eq 's_mail'}
						<button type="button" class="crmbutton small edit" onclick="return eMail('{$MODULE}',this);">{$button_label}</button>
					{elseif $button_check eq 's_fax'}
						<button type="button" class="crmbutton small edit" onclick="return Fax('{$MODULE}',this);">{$button_label}</button>
					{elseif $button_check eq 's_sms'}
						<button type="button" class="crmbutton small edit" onclick="return Sms('{$MODULE}',this);">{$button_label}</button>
					{elseif $button_check eq 's_cmail'}
						<button type="button" class="crmbutton small edit" onclick="return massMail('{$MODULE}')">{$button_label}</button>
					{elseif $button_check eq 'c_status'}
						<button type="button" class="crmbutton small edit" onclick="return change(this,'changestatus')">{$button_label}</button>
					{elseif $button_check eq 'mass_edit'}
						<button type="button" class="crmbutton small edit" onclick="return mass_edit(this, 'massedit', '{$MODULE}', '{$CATEGORY}')">{$button_label}</button>
						
						{assign var="FLOAT_TITLE" value=$APP.LBL_MASSEDIT_FORM_HEADER}
						{assign var="FLOAT_WIDTH" value="760px"}
						{capture assign="FLOAT_BUTTONS"}
							<button type="button" title="{$APP.LBL_SAVE_BUTTON_TITLE}" accessKey="{$APP.LBL_SAVE_BUTTON_KEY}" class="crmbutton small save" onclick="jQuery('#massedit_form input[name=action]').val('MassEditSave'); if (massEditFormValidate()) jQuery('#massedit_form').submit();" name="button" style="min-width:70px" >{$APP.LBL_SAVE_BUTTON_LABEL}</button>
						{/capture}
						{capture assign="FLOAT_CONTENT"}
						<div id="massedit_form_div" style="overflow:auto"></div>	{* crmv@34588 *}
						{/capture}
						{include file="FloatingDiv.tpl" FLOAT_ID="massedit"}

                     {/if}
				{/foreach}
				{if ($ALL_IDS eq 1)}
					<button type="button" class="crmbutton small edit" id="select_all_button_top" {if $AJAX neq 'true'} style="display:none;"{/if} onClick="selectAllIds();" value="{$APP.LBL_UNSELECT_ALL_IDS}">{$APP.LBL_UNSELECT_ALL_IDS}</button>
				{else}
					<button type="button" class="crmbutton small edit" id="select_all_button_top" {if $AJAX neq 'true'} style="display:none;"{/if} onClick="selectAllIds();" value="{$APP.LBL_SELECT_ALL_IDS}">{$APP.LBL_SELECT_ALL_IDS}</button>
				{/if}

                {* vtlib customization: Custom link buttons on the List view basic buttons *}
				{if $CUSTOM_LINKS && $CUSTOM_LINKS.LISTVIEWBASIC}
					{foreach item=CUSTOMLINK from=$CUSTOM_LINKS.LISTVIEWBASIC}
						{assign var="customlink_href" value=$CUSTOMLINK->linkurl}
						{assign var="customlink_label" value=$CUSTOMLINK->linklabel}
						{if $customlink_label eq ''}
							{assign var="customlink_label" value=$customlink_href}
						{else}
							{* Pickup the translated label provided by the module *}
							{assign var="customlink_label" value=$customlink_label|@getTranslatedString:$CUSTOMLINK->module()}
						{/if}
						<button type="button" class="crmbutton small edit" onclick="{$customlink_href}">{$customlink_label}</button>
					{/foreach}
				{/if}

				{* vtlib customization: Custom link buttons on the List view *}
				{if $CUSTOM_LINKS && !empty($CUSTOM_LINKS.LISTVIEW)}
					&nbsp;
					<a href="javascript:;" onmouseover="fnvshobj(this,'vtlib_customLinksLay');" onclick="fnvshobj(this,'vtlib_customLinksLay');">
						<b>{$APP.LBL_MORE} {$APP.LBL_ACTIONS} <img src="{'arrow_down.gif'|resourcever}" border="0"></b>
					</a>
					<div class="drop_mnu" style="display: none; left: 193px; top: 106px;width:155px; position:absolute;" id="vtlib_customLinksLay"
						onmouseout="fninvsh('vtlib_customLinksLay')" onmouseover="fnvshNrm('vtlib_customLinksLay')">
						<table border="0" cellpadding="0" cellspacing="0" width="100%">
						<tr><td style="border-bottom: 1px solid rgb(204, 204, 204); padding: 5px;"><b>{$APP.LBL_MORE} {$APP.LBL_ACTIONS} &#187;</b></td></tr>
						<tr>
							<td>
								{foreach item=CUSTOMLINK from=$CUSTOM_LINKS.LISTVIEW}
									{assign var="customlink_href" value=$CUSTOMLINK->linkurl}
									{assign var="customlink_label" value=$CUSTOMLINK->linklabel}
									{if $customlink_label eq ''}
										{assign var="customlink_label" value=$customlink_href}
									{else}
										{* Pickup the translated label provided by the module *}
										{assign var="customlink_label" value=$customlink_label|@getTranslatedString:$CUSTOMLINK->module()}
									{/if}
									<a href="{$customlink_href}" class="drop_down">{$customlink_label}</a>
								{/foreach}
							</td>
						</tr>
						</table>
					</div>
				{/if}
				{* END *}
                </td>
			{* crmv@31245 crmv@82419 crmv@105588 *}
			<td align="right" width="100%">
				<form id="basicSearch" name="basicSearch" method="post" action="index.php" onSubmit="return callSearch('Basic', '{$FOLDERID}');">
					<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
					<input type="hidden" name="searchtype" value="BasicSearch" />
                        <input type="hidden" name="module" value="{$MODULE}" />
                        <input type="hidden" name="parenttab" value="{$CATEGORY}" />
            		<input type="hidden" name="action" value="index" />
                        <input type="hidden" name="query" value="true" />
            		<input type="hidden" id="basic_search_cnt" name="search_cnt" />

					<div class="form-group basicSearch advIconSearch">
						<input type="text" class="form-control searchBox" id="basic_search_text" name="search_text" value="{$APP.LBL_SEARCH_TITLE}{$MODULE|getTranslatedString:$MODULE}" onclick="clearText(this)" onblur="restoreDefaultText(this, '{$APP.LBL_SEARCH_TITLE}{$MODULE|getTranslatedString:$MODULE}')" />
						<span class="cancelIcon">
							<i class="vteicon md-link md-sm" id="basic_search_icn_canc" style="display:none" title="Reset" onclick="cancelSearchText('{$APP.LBL_SEARCH_TITLE}{$MODULE|getTranslatedString:$MODULE}')">cancel</i>&nbsp;
						</span>
						<span class="searchIcon">
							<i id="basic_search_icn_go" class="vteicon" title="{$APP.LBL_FIND}" style="cursor:pointer" onclick="jQuery('#basicSearch').submit();" >search</i>
						</span>
						<span class="advSearchIcon">
							<i id="adv_search_icn_go" class="vteicon" title="{$APP.LNK_ADVANCED_SEARCH}" style="cursor:pointer" onclick="advancedSearchOpenClose();updatefOptions(document.getElementById('Fields0'), 'Condition0');">keyboard_arrow_down</i>
						</span>
					</div>
				</form>
			</td>
			<!-- <td align="right" nowrap>
				<button type="button" class="crmbutton small create" onclick="jQuery('#advSearch').toggle();updatefOptions(document.getElementById('Fields0'), 'Condition0');">{$APP.LNK_ADVANCED_SEARCH}</button>
			</td> -->
			{* crmv@31245e crmv@22259e  crmv@82419e crmv@105588e *}
		</tr>
	</table>
</div>
<script type="text/javascript">
	calculateButtonsList3();
	{if $smarty.request.query eq true && $smarty.request.searchtype eq 'BasicSearch' && !empty($smarty.request.search_text)}
		clearText(jQuery('#basic_search_text'));
		jQuery('#basic_search_text').data('restored', false); // crmv@104119
		jQuery('#basic_search_text').val('{$smarty.request.search_text}');
		basic_search_submitted = true;
	{/if}
</script>