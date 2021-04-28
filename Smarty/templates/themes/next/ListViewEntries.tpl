{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@140887 *}
 
{if $smarty.request.ajax neq ''}
&#&#&#{$ERROR}&#&#&#
{/if}

{if $HIDE_CUSTOM_LINKS eq 1}
	<script language="JavaScript" type="text/javascript" src="{"include/js/ListView.js"|resourcever}"></script>
	<div id="ListViewContents">
{/if}

<form name="massdelete" method="POST" id="massdelete" onsubmit="VteJS_DialogBox.block();">
	<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
	<input name='search_url' id="search_url" type='hidden' value='{$SEARCH_URL}'>
	<input name='append_url' id="append_url" type='hidden' value='{$APPEND_URL}'>
	{if $HIDE_CUSTOM_LINKS eq 1}
		<input id="modulename" name="modulename" type="hidden" value="{$MODULE}">
	{/if}
	<input name="change_owner" type="hidden">
	<input name="change_status" type="hidden">
	<input name="action" type="hidden">
	<input name="where_export" type="hidden" value="{to_html(VteSession::get('export_where'))}"> {* crmv@181170 *}
	<input name="step" type="hidden">
	<input name="selected_ids" type="hidden" id="selected_ids" value="{$SELECTED_IDS}">
	<input name="all_ids" type="hidden" id="all_ids" value="{$ALL_IDS}">
	<input name="import_flag" type="hidden" id="import_flag" value="{$HIDE_CUSTOM_LINKS}">

	<ul class="vteUlTable">

		{if $FOLDERID > 0}
			<li><span class="dvHeaderText">{$APP.LBL_FOLDER}: {$FOLDERINFO.foldername}</li>
		{/if}

		<li>
			{if $smarty.request.ajax eq ''}
				{include file="Buttons_List3.tpl"}
			{else}
				{include file="Buttons_List3.tpl" REQUEST_ACTION=$smarty.request.file}
			{/if}
		</li>
		
		{if $CAN_EDIT_COUNTS} {* crmv@173746 *}
		<li>
			<table border=0 cellspacing=0 cellpadding=0 class="small listViewCounts">
				<tr>
					<td>{$APP.LBL_LIST_SHOW}</td>
					<td style="padding-left:5px;padding-right:5px">
						<div class="dvtCellInfo">
							<select name="counts" id="counts" class="detailedViewTextBox" onchange="ModuleHome.editListViewCounts(this, '{$MODULE}', '{$FOLDERID}')">{$CUSTOMCOUNTS_OPTION}</select>
						</div>
					</td>
					<td>{$APP.LBL_ELEMENTS}</td>
				</tr>
			</table>
		</li>
		{/if}
					
		<li class="pull-right">
		
			<ul class="vteUlTable">
			
				{if $MODULE eq 'Calendar'}
					{if $smarty.request.file eq 'index'}
					{elseif $smarty.request.file eq 'ListView' || empty($smarty.request.file)}
						<li>
							<div class="dropdown calendarViewButton">
								<a class="crmbutton edit dropdown-toggle" data-toggle="dropdown" aria-expanded="true">
								{$MOD.LBL_CAL_TO_FILTER}
								<span class="caret"></span></a>
								<ul class="dropdown-menu dropdown-autoclose">
									<li><a id="showdaybtn" href="javascript:void(0)" onclick="VTE.CalendarView.listToCalendar('Today')">{$MOD.LBL_DAY}</a></li>
									<li><a id="showweekbtn" href="javascript:void(0)" onclick="VTE.CalendarView.listToCalendar('This Week')">{$MOD.LBL_WEEK}</a></li>
									<li><a id="showmonthbtn" href="javascript:void(0)" onclick="VTE.CalendarView.listToCalendar('This Month')">{$MOD.LBL_MONTH}</a></li>
									<li><a href="javascript:void(0)" onclick="VTE.CalendarView.calendarToList()">{$MOD.LBL_CAL_TO_FILTER}</a></li>
								</ul>
							</div>
						</li>
					{/if}
				{/if}
			
				{if !$SKIP_SWITCHBTN}
					<li>
						<button type="button" class="crmbutton only-icon save" disabled><i class="vteicon" title="{'LBL_LIST'|getTranslatedString}" data-toggle="tooltip" data-placement="bottom">view_headline</i></button>
						<a class="crmbutton only-icon" href="index.php?module={$MODULE}&amp;action=HomeView&amp;modhomeid={$MODHOMEID}&viewmode=KanbanView"><i class="vteicon" title="Kanban" data-toggle="tooltip" data-placement="bottom">view_column</i></a>
					</li>
				{/if}

				<li>{$APP.LBL_VIEW}</li>
							
				<li style="padding-left:5px;padding-right:5px">
					<div class="dvtCellInfo">
						<select name="viewname" id="viewname" class="detailedViewTextBox" onchange="showDefaultCustomView(this,'{$MODULE}','{$CATEGORY}','{$FOLDERID}','ListView','{$MODHOMEID}')">{$CUSTOMVIEW_OPTION}</select> {* crmv@141557 *}
					</div>
				</li>
							
				{if $HIDE_CUSTOM_LINKS neq '1'}
					<li>
						<div class="dropdown">
							<span data-toggle="dropdown">
								<i class="vteicon md-link" id="filter_option_img" data-toggle="tooltip" data-placement="bottom" title="{$APP.LBL_FILTER_OPTIONS}">settings</i>
							</span>
							<ul class="dropdown-menu" id="customLinks">
								{if $ALL eq 'All'}
									<li>
										<a href="index.php?module={$MODULE}&action=CustomView&duplicate=true&record={$VIEWID}&parenttab={$CATEGORY}">{$APP.LNK_CV_DUPLICATE}</a>
									</li>
									<li>
										<a href="index.php?module={$MODULE}&action=CustomView&parenttab={$CATEGORY}">{$APP.LNK_CV_CREATEVIEW}</a>
									</li>
								{else}
									{if $CV_EDIT_PERMIT eq 'yes'}
										<li>
											<a href="index.php?module={$MODULE}&action=CustomView&record={$VIEWID}&parenttab={$CATEGORY}">{$APP.LNK_CV_EDIT}</a>
										</li>
									{/if}
									
									<li>
										<a href="index.php?module={$MODULE}&action=CustomView&duplicate=true&record={$VIEWID}&parenttab={$CATEGORY}">{$APP.LNK_CV_DUPLICATE}</a>
									</li>
									
									{if $CV_DELETE_PERMIT eq 'yes'}
										<li>
											<a href="javascript:confirmdelete('index.php?module=CustomView&action=Delete&dmodule={$MODULE}&record={$VIEWID}&parenttab={$CATEGORY}')">{$APP.LNK_CV_DELETE}</a>
										</li>
									{/if}
									{if $CUSTOMVIEW_PERMISSION.ChangedStatus neq '' && $CUSTOMVIEW_PERMISSION.Label neq ''}
										<li>
											<a href="#" id="customstatus_id" onClick="ChangeCustomViewStatus({$VIEWID},{$CUSTOMVIEW_PERMISSION.Status},{$CUSTOMVIEW_PERMISSION.ChangedStatus},'{$MODULE}','{$CATEGORY}')">{$CUSTOMVIEW_PERMISSION.Label}</a>
										</li>
									{/if}
									<li>
										<a href="index.php?module={$MODULE}&action=CustomView&parenttab={$CATEGORY}">{$APP.LNK_CV_CREATEVIEW}</a>
									</li>
								{/if}
							</ul>
						</div>
					</li>
				{/if}
					    
				{if $HIDE_CV_FOLLOW neq '1'}
					{assign var=FOLLOWIMG value=$VIEWID|@getFollowImg:'customview'}
					{if preg_match('/_on/', $FOLLOWIMG)}
						{assign var=FOLLOWTITLE value='LBL_UNFOLLOW'|getTranslatedString:'ModNotifications'}
					{else}
						{assign var=FOLLOWTITLE value='LBL_FOLLOW'|getTranslatedString:'ModNotifications'}
					{/if}
					<li>
						<i data-toggle="tooltip" data-placement="bottom" id="followImgCV" title="{$FOLLOWTITLE}" class="vteicon md-link" onClick="ModNotificationsCommon.followCV();">{$VIEWID|@getFollowCls:'customview'}</i>
					</li>
					
					{if $OWNED_BY eq 0}
						<li style="padding-left:10px">{$APP.LBL_ASSIGNED_TO}</li>
						<li><div class="dvtCellInfo">{$LV_USER_PICKLIST}</div></li>
					{/if}
				{/if}

			</ul>
		</li>
	</ul>
	
	<div id="listViewCont" class="listview-container">
		<div id="listViewHeader" class="listview-header">
			<div id="rec_string">{$RECORD_COUNTS}</div>
			<div id="nav_buttons">{$NAVIGATION}</div>
			<div id=""></div>
		</div>
		<div class="vte-card listview-entries">
			<table id="listView" class="vtetable">
				<thead>
					<tr>
						{if $HIDE_LIST_CHECKBOX neq '1'} {* crmv@42752 *}
							<td><div class="checkbox"><label><input type="checkbox" class="" id="selectall" name="selectall" onClick="select_all_page(this.checked,jQuery('#massdelete').get(0));"></label></div></td> {* crmv@82419 *}
						{/if}
						{foreach name="listviewforeach" item=header from=$LISTHEADER}
							<td>{$header}</td>
						{/foreach}
					</tr>

					{if !empty($GRIDSEARCHARR)}
						<tr id="gridSrc" valign="top">
							{if $HIDE_LIST_CHECKBOX neq '1'}
								<td></td>
							{/if}
							{foreach name="gridforeach" item=search from=$GRIDSEARCHARR}
								<td>
									{if $smarty.foreach.gridforeach.index eq 0 && empty($search)}
										<a href="javascript:;" onclick="resetListSearch('Grid','{$FOLDERID}',true);">
											<i class="vteicon" >highlight_remove</i>
										</a>
										<a href="javascript:;" onclick="callSearch('Grid','{$FOLDERID}');">
											<i class="vteicon" >search</i>
										</a>
									{else}
										{if isset($search.module)}
											{include file="EditViewUI.tpl" NOLABEL=true GRIDSEARCH=true MODULE=$search.module DIVCLASS=$search.divclass uitype=$search.uitype keymandatory=$search.mandatory fldlabel=$search.label fldname=$search.name fldvalue=$search.value}
										{/if}
									{/if}
								</td>
							{/foreach}
						</tr>
					{/if}
				</thead>
				<tbody>
					{foreach item=entity key=entity_id from=$LISTENTITY}
						{assign var=color value=$entity.clv_color}
						{assign var=foreground value=$entity.clv_foreground}
						{assign var=cell_class value="listview-cell"}
						
						{if !empty($foreground)}
							{assign var=cell_class value=$cell_class|cat:" color-`$foreground`"}
						{/if}
						
						<tr id="row_{$entity_id}">
							{if $HIDE_LIST_CHECKBOX neq '1'}
								<td width="2%"><div class="checkbox"><label><input type="checkbox" name="selected_id" id="{$entity_id}" value="{$entity_id}" onClick="update_selected_ids(this.checked,'{$entity_id}',this.form,true);"
								{if is_array($SELECTED_IDS_ARRAY) && count($SELECTED_IDS_ARRAY) > 0} {* crmv@167234 *}
									{if $ALL_IDS eq 1 && !in_array($entity_id,$SELECTED_IDS_ARRAY)}
										checked
									{else}
										{if ($ALL_IDS neq 1 and $SELECTED_IDS neq "" and in_array($entity_id,$SELECTED_IDS_ARRAY))}
											checked
										{/if}
									{/if}
								{else}
									{if $ALL_IDS eq 1}
										checked
									{/if}
								{/if}
								></label></div></td>
							{/if}

							{foreach name="entityforeach" key=colname item=data from=$entity}
								{if ($colname neq 'clv_color' and $colname neq 'clv_status' and $colname neq 'clv_foreground') or $colname eq '0'}
									<td bgcolor="{$color}" class="{$cell_class}"{if $smarty.foreach.entityforeach.index eq 0} nowrap{/if}>{$data}</td>
								{/if}
							{/foreach}
						</tr>
					{foreachelse}
						<tr>
							<td colspan="{$smarty.foreach.listviewforeach.iteration+1}">
								<div class="listview-emptylist-container">
									<div class="listview-emptylist">
										{assign var=vowel_conf value='LBL_A'}
										{if $CHECK.EditView eq 'yes' && $MODULE neq 'Emails'}
											<div class="listview-emptylist-message">
												<div class="listview-emptylist-icon">
													<i class="vteicon">error_outline</i>
												</div>
												<div class="listview-emptylist-message-content">
													<div class="listview-emptylist-message-not-found">
														<span>
															{$APP.LBL_NO_M} {$APP.LBL_RECORDS} {$APP.LBL_FOUND} !
														</span>
													</div>
													{if $MODULE neq 'Charts'} {* crmv@30967 *}
														<div class="listview-emptylist-message-action">
															{$APP.LBL_EMPTY_LIST_YOU_CAN_CREATE_RECORD_NOW}<br>
															{if $MODULE neq 'Calendar'}
																- <a href="index.php?module={$MODULE}&action=EditView&return_action=DetailView&parenttab={$CATEGORY}">{$MODULE|getNewModuleLabel}</a><br>	{* crmv@59091 *}
												  			{else}
																- <a href="index.php?module={$MODULE}&amp;action=EditView&amp;return_module=Calendar&amp;activity_mode=Events&amp;return_action=DetailView&amp;parenttab={$CATEGORY}">{'Event'|getNewModuleLabel}</a><br>	{* crmv@59091 *}
																- <a href="index.php?module={$MODULE}&amp;action=EditView&amp;return_module=Calendar&amp;activity_mode=Task&amp;return_action=DetailView&amp;parenttab={$CATEGORY}">{'Task'|getNewModuleLabel}</a>	{* crmv@59091 *}
															{/if}
														</div>
													{/if}
												</div>
											</div>
										{else}
											<div class="listview-emptylist-message">
												<div class="listview-emptylist-icon">
													<i class="vteicon">not_interested</i>
												</div>
												<div class="listview-emptylist-message-content">
													<div class="listview-emptylist-message-not-found">
														<span>
															{$APP.LBL_NO_M} {$APP.LBL_RECORDS} {$APP.LBL_FOUND} !
														</span>
													</div>
													<div class="listview-emptylist-message-action">
														{$APP.LBL_YOU_ARE_NOT_ALLOWED_TO_CREATE} {$APP.$vowel_conf} {$APP.LBL_RECORDS}<br>
													</div>
												</div>
											</div>
										{/if}
									</div>
								</div>
							</td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		</div>
		
		<div id="listViewFooter" class="listview-footer">
			<div id="rec_string2">{$RECORD_COUNTS}</div>
			<div id="nav_buttons2">{$NAVIGATION}</div>
			<div id=""></div>
		</div>
	</div>

</form>

{$SELECT_SCRIPT}

<div id="basicsearchcolumns" style="display:none;"><select name="search_field" id="bas_searchfield" class="txtBox" style="width:150px">{html_options options=$SEARCHLISTHEADER}</select></div>

{if $HIDE_CUSTOM_LINKS eq 1}
	</div>
{/if}

{* crmv@43835 *}
{if !empty($GRIDSEARCH_JS_ARRAY)}
	<script type="text/javascript" id="gridsearch_script">
	gridsearch = new Array({$GRIDSEARCH_JS_ARRAY});
	</script>
{/if}
{* crmv@43835e *}

<script type="text/javascript">
	var selectAllLabel = '{$APP.LBL_SELECT_ALL_IDS}';
	var unselectAllLabel = '{$APP.LBL_UNSELECT_ALL_IDS}';
</script>

{literal}
<script type="text/javascript">

function unselectAllIds() {
	jQuery("#select_all_button_top").val(selectAllLabel);
}

function selectAllIds() {
	var selectAllValue = jQuery("#select_all_button_top").val();

	if (selectAllValue == selectAllLabel) {
		jQuery("#select_all_button").html(unselectAllLabel);
		jQuery("#select_all_button_top").val(unselectAllLabel);

		jQuery("#all_ids").val(1);
		jQuery("#selected_ids").val('');
		jQuery("#selectall").prop('checked', true);
		jQuery("[name='selected_id']").prop('checked', true);
	} else {
		jQuery("#select_all_button").html(selectAllLabel);
		jQuery("#select_all_button_top").val(selectAllLabel);
		
		jQuery("#select_ids").val('');
		jQuery("#all_ids").val('');
		jQuery("#selected_ids").val('');
		jQuery("#selectall").prop('checked', false);
		jQuery("[name='selected_id']").prop('checked', false);
	}
}

jQuery(document).ready(function() {
	var loader = jQuery('#modhome_loader');
	if (loader.length == 0) {
		update_navigation_values(window.location.href, gVTModule);
	}
});


</script>
{/literal}