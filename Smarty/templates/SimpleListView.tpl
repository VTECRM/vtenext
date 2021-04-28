{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@43611 crmv@83340 *}

{if $RENDER_MODE neq 'ajax'}

<script type="text/javascript" src="include/js/SimpleListView.js"></script>

{* crmv@56603 *}
<div id="SLVPersistentCont_{$LISTID}" style="display:none">
	<input type="hidden" name="selected_ids" value="{$SELECTED_IDS|@implode:':'}" />
</div>
{* crmv@56603e *}

<div id="SLVContainer_{$LISTID}" style="position:relative">
{/if}

<div id="SLVGreyLayer" class="SLVGreyLayer" style="display:none;position:absolute;"></div>

<input type="hidden" name="mod" id="mod" value="{$MODULE}">
<input type="hidden" name="mod_label" id="mod_label" value="{$MODULE|getTranslatedString:$MODULE}">
<input type="hidden" name="mod_singlelabel" id="mod_singlelabel" value="{'SINGLE_'|cat:$MODULE|getTranslatedString:$MODULE}">

{* crmv@107991 *}
<input type="hidden" name="slv_sortcol" value="{$LIST_SORTCOL}">
<input type="hidden" name="slv_sortdir" value="{$LIST_SORTDIR}">
{* crmv@107991e *}

<div class="extraInputs">
	{if count($EXTRA_INPUTS) > 0}
	{foreach item=EINPUTVAL key=EINPUTKEY from=$EXTRA_INPUTS}
		<input type="hidden" name="{$EINPUTKEY}" value="{$EINPUTVAL}" />
	{/foreach}
	{/if}
</div>

{if $SHOW_FILTERS || $SHOW_NAVIGATION || $SHOW_SEARCH || $SHOW_CREATE || $EXTRA_BUTTONS_HTML || $SDK_SHOW_ALL_BUTTON}
<table border="0" class="small" width="100%" height="36px"><tr>

	<td align="left" width="20%">

		{* filters *}
		{if $SHOW_FILTERS eq true}
		{$APP.LBL_VIEW}&nbsp;
		<div class="dvtCellInfo" style="display:inline-block;width:100px">
			<select name="viewname" id="viewname" class="detailedViewTextBox" onchange="SLV.change_filter('{$LISTID}')">
				{$CUSTOMVIEW_OPTION}
			</select>
		</div>
		{/if}

	</td><td align="center">

		{* navigation *}
		{if $LIST_TOT_PAGES > 1 && $SHOW_NAVIGATION eq true}
		<form id="paginationForm" name="paginationForm" method="POST" action="index.php" onsubmit="return SLV.go_to_page('{$LISTID}')">
			<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
			<input type="hidden" name="navigationPageOrig" id="navigationPageOrig" value="{$LIST_PAGE}">
			<input type="hidden" name="navigationPageTotal" id="navigationPageTotal" value="{$LIST_TOT_PAGES}">
			<nav class="slv-container">
			<ul class="pagination pagination-sm">
				<li><a href="javascript:void(0);" onclick="SLV.go_to_page('{$LISTID}', 1)" title="{$APP.LBL_FIRST}">&laquo;</a></li>
				<li><a href="javascript:void(0);" onclick="SLV.go_to_prev_page('{$LISTID}')" title="{$APP.LNK_LIST_PREVIOUS}">&lt;</a></li>
				<li><a href="javascript:void(0);"><label for="navigationPage">{$APP.Page}</label> <input id="navigationPage" name="navigationPage" type="text" class="form-control pagenumber" maxlength="5" value="{$LIST_PAGE}"> <label for="navigationPage">{$APP.LBL_LIST_OF} {$LIST_TOT_PAGES}</label></a></li>
				<li><a href="javascript:void(0);" onclick="SLV.go_to_next_page('{$LISTID}')" title="{$APP.LNK_LIST_NEXT}">&gt;</a></li>
				<li><a href="javascript:void(0);" onclick="SLV.go_to_page('{$LISTID}', '{$LIST_TOT_PAGES}')" title="{$APP.LBL_LAST}">&raquo;</a></li>
			</ul>
			</nav>
		</form>
		{/if}

	</td><td align="right" width="16px">

		{include file="LoadingIndicator.tpl" LIID="slv_busy_indicator" LIEXTRASTYLE="display:none"}

	</td><td align="right" width="25%">

		{if $SHOW_SEARCH eq true}

		{* basic search *}
		<form id="basicSearch" name="basicSearch" method="post" action="index.php" onSubmit="return SLV.search('{$LISTID}');">
			<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
			<input type="hidden" name="searchtype" value="BasicSearch" />
	        <input type="hidden" name="module" value="{$MODULE}" />
	        <input type="hidden" name="parenttab" value="{$CATEGORY}" />
			<input type="hidden" name="action" value="index" />
	        <input type="hidden" name="query" value="true" />
			<input type="hidden" id="basic_search_cnt" name="search_cnt" />
			{assign var=mod_label value=$MODULE|getTranslatedString:$MODULE}
			{assign var=search_placeholder value=$APP.LBL_SEARCH_TITLE|cat:$mod_label}
			<input type="hidden" id="search_placeholder" name="search_placeholder" value="{$search_placeholder}" />
			
			<div class="form-group basicSearch">
				<input type="text" class="form-control searchBox" id="basic_search_text" name="search_text" value="{if $LIST_SEARCH}{$LIST_SEARCH}{else}{$search_placeholder}{/if}" onclick="SLV.clear_search('{$LISTID}')" onblur="SLV.restore_search('{$LISTID}', '{$search_placeholder}')" />
				<span class="cancelIcon">
					<i class="vteicon md-link md-sm" id="basic_search_icn_canc" style="{if $LIST_SEARCH}{else}display:none{/if}" title="Reset" onclick="SLV.cancel_search('{$LISTID}', '{$APP.LBL_SEARCH_TITLE}{$MODULE|getTranslatedString:$MODULE}')">cancel</i>&nbsp;
				</span>
				<span class="searchIcon">
					<i id="basic_search_icn_go" class="vteicon" title="{$APP.LBL_FIND}" style="cursor:pointer" onclick="jQuery(this).closest('form').submit();" >search</i>
				</span>
			</div>
		</form>

		{/if}

	</td><td align="right" width="2%" nowrap>
		{if $SHOW_CHECKBOXES}
			<input type="button" class="small crmbutton save slvButtonAdd" value="{$APP.LBL_ADD_SELECTED}" onclick="{$ADDSELECTED_FUNC}('{$LISTID}')" />
		{/if}
		{if $SHOW_CREATE}
			<input type="button" class="small crmbutton save slvButtonCreate" value="{$APP.LBL_CREATE}..." onclick="{$CREATE_FUNC}('{$LISTID}')" /> {* crmv@43050 *}
		{/if}
		{* crmv@126184 *}
		{if $SHOW_CANCEL}
			<input type="button" class="small crmbutton cancel slvButtonCancel" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" onclick="{$CANCEL_FUNC}('{$LISTID}')" />
		{/if}
		{* crmv@126184e *}
		{$EXTRA_BUTTONS_HTML}
		{* crmv@48964 *}
		{if $SDK_SHOW_ALL_BUTTON}
			<input type="button" class="small crmbutton save slvButtonShowAll" value="{$APP.SHOW_ALL}" onclick="jQuery('#sdk_view_all').val(1); LPOP.clickLinkModule('{$MODULE}', 'list');" />
		{/if}
		{* crmv@48964e *}
	</td>

</tr></table>
{else}
	<div style="height:10px"></div>
{/if}

{if count($LIST_ENTRIES) > 0}

<div class="vte-card"> {* crmv@198701 *}
	<table class="vtetable">
		{if count($LIST_HEADER) > $LIST_MAXROWFIELDS}
			{assign var=hasextrarow value=true}
		{/if}
		<thead>
			<tr class="popupLinkListTitleRow">
				{if $SHOW_CHECKBOXES}
					<th class="popupLinkListTitleCell" nowrap="" >&nbsp;</th>
				{/if}
				{foreach name=listrowtitlefor item=headcol from=$LIST_HEADER}
					{if $smarty.foreach.listrowtitlefor.iteration <= $LIST_MAXROWFIELDS}
					{* crmv@43864 *}
						<th class="popupLinkListTitleCell" nowrap="" >
						{if $SHOW_SORTING && $headcol.fieldname}
							{if $headcol.fieldname eq $LIST_SORTCOL}
								{if $LIST_SORTDIR eq 'ASC'}
									<a href="javascript:SLV.change_sorting('{$LISTID}', '{$headcol.fieldname}', 'DESC');">{$headcol.text} <span class="vteicon vtesorticon md-text">arrow_drop_down</span></a>
								{else}
									<a href="javascript:SLV.change_sorting('{$LISTID}', '{$headcol.fieldname}', 'ASC');">{$headcol.text} <span class="vteicon vtesorticon md-text">arrow_drop_up</span></a>
								{/if}
							{else}
								<a href="javascript:SLV.change_sorting('{$LISTID}', '{$headcol.fieldname}')">{$headcol.text}</a>
							{/if}
						{else}
							{$headcol.text}
						{/if}
						</th>
					{* crmv@43864e *}
					{/if}
				{/foreach}
			</tr>
		</thead>
		<tbody>
			{foreach item=listrow key=crmid from=$LIST_ENTRIES}
			{* crmv@66773 *}
			{* <tr class="popupLinkListDataRow popupLinkListDataRow{cycle name=popupLinkCycle1 values='0,1'}" onclick="{$SELECT_FUNC}('{$LISTID}', '{$MODULE}', '{$crmid}', '{$listrow.entityname|addslashes}')" {if $hasextrarow eq true}onmouseover="jQuery(this).next().addClass('hovered')" onmouseout="jQuery(this).next().removeClass('hovered')"{/if}> *} {* crmv@49389 *}
			<tr id="list_id_{$crmid}" onclick="{$SELECT_FUNC}('{$LISTID}', '{$MODULE}', '{$crmid}', '{$listrow.entityname|addslashes}')" {if $hasextrarow eq true}onmouseover="jQuery(this).next().addClass('hovered')" onmouseout="jQuery(this).next().removeClass('hovered')"{/if}> {* crmv@49389 *}	{* crmv@197575 *}
			{* crmv@66773e *}
				{if $SHOW_CHECKBOXES}
					<td class="popupLinkListDataCell popupLinkListCboxCell linkNoPropagate" onclick="SLV.click_tdcheckbox('{$LISTID}', '{$crmid}', this)"><input class="linkNoPropagate" type="checkbox" name="list_cbox_{$crmid}" id="list_cbox_{$crmid}" onclick="SLV.click_checkbox('{$LISTID}', '{$crmid}', this)" {if $listrow.slv_selected}checked="checked"{/if}/></td> {* crmv@56603 *}
				{/if}
				{foreach name=listrowfor item=listcell key=kk from=$listrow}
					{if $smarty.foreach.listrowfor.iteration <= $LIST_MAXROWFIELDS && $kk|is_numeric}
						{assign var=foreground value=$listrow.clv_foreground}
						{assign var=cell_class value="listview-cell listview-cell-simple popupLinkListDataCell"}
						
						{if !empty($foreground)}
							{assign var=cell_class value=$cell_class|cat:" color-`$foreground`"}
						{/if}
						
						<td bgcolor="{$listrow.clv_color}" class="{$cell_class}" style="position:relative;">{$listcell}</td> {* crmv@59091 crmv@168489 *}
					{/if}
				{/foreach}
				{* add link for details *}
				<!-- td class="popupLinkListDataCell linkNoPropagate"><a class="linkListDetails linkNoPropagate" href="#" onclick="popupClickLinkModule('{$MODULE}', '{$crmid}');">{$MOD.LBL_LINK_ACTION}</a></td-->
			</tr>
			{if $hasextrarow eq true}
				<tr class="popupLinkListDataRow popupLinkListDataExtraRow popupLinkListDataRow{cycle name=popupLinkCycle2 values='0,1'}" onmouseover="jQuery(this).prev().addClass('hovered')" onmouseout="jQuery(this).prev().removeClass('hovered')" onclick="{$SELECT_FUNC}('{$LISTID}', '{$MODULE}', '{$crmid}', {$listrow.entityname}')">
					<td colspan="{$LIST_MAXROWFIELDS}" class="popupLinkListDataCell popupLinkListDataExtraCell" >
						{foreach name=listrowforextra item=listcell from=$listrow}
							{if $smarty.foreach.listrowforextra.iteration > $LIST_MAXROWFIELDS && $kk|is_numeric}
								{$listcell}{if $smarty.foreach.listrowforextra.last eq false},{/if}
							{/if}
						{/foreach}
					</td>
				</tr>
			{/if}
			{/foreach}
		</tbody>
	</table>
</div>

<script type="text/javascript">
// handler for details link crmv@SHAK
	jQuery('#SLVContainer_{$LISTID} .linkNoPropagate').click(function(e) {ldelim}
		e.stopPropagation();
		e.cancelBubble = true;	// IE specific
		return true;
	{rdelim});
</script>


{else}
	<div class="popupLinkListNoData">
	<p>{$APP.LBL_NO_DATA}</p>
	</div>
{/if}

{if $RENDER_MODE neq 'ajax'}
</div>
{/if}