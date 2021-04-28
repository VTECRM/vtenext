{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@82419 *}
 
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
	{* crmv@9183 *}
	<input name="selected_ids" type="hidden" id="selected_ids" value="{$SELECTED_IDS}">
	<input name="all_ids" type="hidden" id="all_ids" value="{$ALL_IDS}">
	<input name="import_flag" type="hidden" id="import_flag" value="{$HIDE_CUSTOM_LINKS}">
	{* crmv@9183e *}

	<!-- List View Master Holder starts -->
	<table border=0 cellspacing=1 cellpadding=0 width=100% class="lvtBg">
		<tr>
			<td>
			<!-- List View's Buttons and Filters starts -->

            {* crmv@21723 *}
			{if $HIDE_CUSTOM_LINKS neq '1'}
				<div class="drop_mnu" id="customLinks" onmouseover="fnShowDrop('customLinks');" onmouseout="fnHideDrop('customLinks');" style="width:150px;">
					<table cellspacing="0" cellpadding="0" border="0" width="100%">
						{* crmv@22259 *}
						{if $ALL eq 'All'}
							<tr>
								<td><a class="drop_down" href="index.php?module={$MODULE}&action=CustomView&duplicate=true&record={$VIEWID}&parenttab={$CATEGORY}">{$APP.LNK_CV_DUPLICATE}</a></td>
							</tr>
							<tr>
								<td><a class="drop_down" href="index.php?module={$MODULE}&action=CustomView&parenttab={$CATEGORY}">{$APP.LNK_CV_CREATEVIEW}</a></td>
							</tr>
					    {else}
							{if $CV_EDIT_PERMIT eq 'yes'}
								<tr>
									<td><a class="drop_down" href="index.php?module={$MODULE}&action=CustomView&record={$VIEWID}&parenttab={$CATEGORY}">{$APP.LNK_CV_EDIT}</a></td>
								</tr>
							{/if}
							<tr>
								<td><a class="drop_down" href="index.php?module={$MODULE}&action=CustomView&duplicate=true&record={$VIEWID}&parenttab={$CATEGORY}">{$APP.LNK_CV_DUPLICATE}</a></td>
							</tr>
							{if $CV_DELETE_PERMIT eq 'yes'}
								<tr>
									<td><a class="drop_down" href="javascript:confirmdelete('index.php?module=CustomView&action=Delete&dmodule={$MODULE}&record={$VIEWID}&parenttab={$CATEGORY}')">{$APP.LNK_CV_DELETE}</a></td>
								</tr>
							{/if}
							{if $CUSTOMVIEW_PERMISSION.ChangedStatus neq '' && $CUSTOMVIEW_PERMISSION.Label neq ''}
								<tr>
							   		<td><a class="drop_down" href="#" id="customstatus_id" onClick="ChangeCustomViewStatus({$VIEWID},{$CUSTOMVIEW_PERMISSION.Status},{$CUSTOMVIEW_PERMISSION.ChangedStatus},'{$MODULE}','{$CATEGORY}')">{$CUSTOMVIEW_PERMISSION.Label}</a></td>
							   	</tr>
							{/if}
							<tr>
								<td><a class="drop_down" href="index.php?module={$MODULE}&action=CustomView&parenttab={$CATEGORY}">{$APP.LNK_CV_CREATEVIEW}</a></td>
							</tr>
					    {/if}
					    {* crmv@22259e *}
					</table>
				</div>
			{/if}
			{* crmv@21723 e *}

			<table width="100%" style="min-height:50px"> {* crmv@104013 *}

				{* crmv@30967 *}
				{if $FOLDERID > 0}
					<tr style="margin:2px">
						<td colspan="3"><span class="dvHeaderText">{$APP.LBL_FOLDER}: {$FOLDERINFO.foldername}</span></td>
					</tr>
				{/if}
				{* crmv@30967e *}

				<tr>
					{* crmv@31245 crmv@21723 crmv@10759 crmv@22259 *}
					<td id="rec_string" align="left" width="33%" class="small" nowrap>{$RECORD_COUNTS}</td>
					<td id="nav_buttons" align="center" width="33%" style="padding:5px;">{$NAVIGATION}</td>
					<td width="33%" align="right">
				        <!-- Filters -->
		                <table border=0 cellspacing=0 cellpadding=0 class="small"><tr>
		                	{* crmv@OPER6288 crmv@83340 crmv@102334 *}
		                	{if !$SKIP_SWITCHBTN}
		                    <td style="padding-right:20px" nowrap>
								<i class="vteicon" title="{'LBL_LIST'|getTranslatedString}" data-toggle="tooltip">view_headline</i>
								<a href="index.php?module={$MODULE}&amp;action=HomeView&amp;modhomeid={$MODHOMEID}&viewmode=KanbanView"><i class="vteicon disabled" style="cursor:pointer" title="Kanban" data-toggle="tooltip">view_column</i></a>
							</td>
							{/if}
		                	{* crmv@OPER6288e crmv@83340e crmv@102334e *}
		                    <td>{$APP.LBL_VIEW}</td>
		                    <td style="padding-left:5px;padding-right:5px">
								<div class="dvtCellInfo">
									<select name="viewname" id="viewname" class="detailedViewTextBox" onchange="showDefaultCustomView(this,'{$MODULE}','{$CATEGORY}','{$FOLDERID}','ListView','{$MODHOMEID}')">{$CUSTOMVIEW_OPTION}</select> {* crmv@141557 *}
								</div>
							</td>
							<td>
								{* crmv@21723 crmv@21827 crmv@22622 *}
								<div style="white-space:nowrap">
								{if $HIDE_CUSTOM_LINKS neq '1'}
									<i data-toggle="tooltip" class="vteicon md-link" id="filter_option_img" title="{$APP.LBL_FILTER_OPTIONS}" onmouseover="fnDropDown(this,'customLinks');" onmouseout="fnHideDrop('customLinks');" >settings</i>{* crmv@120023 *}
								{/if}
								{* crmv@21723e crmv@21827e crmv@22622e *}
								{* crmv@29617 crmv@42752 *}
								{if $HIDE_CV_FOLLOW neq '1'}
									{* crmv@83305 *}
									{assign var=FOLLOWIMG value=$VIEWID|@getFollowImg:'customview'}
									{if preg_match('/_on/', $FOLLOWIMG)}
										{assign var=FOLLOWTITLE value='LBL_UNFOLLOW'|getTranslatedString:'ModNotifications'}
									{else}
										{assign var=FOLLOWTITLE value='LBL_FOLLOW'|getTranslatedString:'ModNotifications'}
									{/if}
									<i data-toggle="tooltip" id="followImgCV" title="{$FOLLOWTITLE}" class="vteicon md-link" onClick="ModNotificationsCommon.followCV();">{$VIEWID|@getFollowCls:'customview'}</i>
									{* crmv@83305e *}
								{/if}
								</div>
								{* crmv@29617e crmv@42752e *}
							</td> {* crmv@30967 *}
							{* crmv@7634 *}
							{if $OWNED_BY eq 0}
								<td style="padding-left:10px" nowrap>{$APP.LBL_ASSIGNED_TO}</td>
								<td style="padding-left:5px;"><div class="dvtCellInfo">{$LV_USER_PICKLIST}</div></td>
							{/if}
							{* crmv@7634e *}
						</tr></table>
					</td>
					{* crmv@31245e crmv@21723e crmv@10759e crmv@22259e *}
				</tr>
			</table>

            <div>
            <table border=0 cellspacing=1 cellpadding=3 width=100% class="lvt small">
            <!-- Table Headers -->
            <tr>
	            {if $HIDE_LIST_CHECKBOX neq '1'} {* crmv@42752 *}
	            	<td class="lvtCol"><div class="checkbox"><label><input type="checkbox" class="" id="selectall" name="selectall" onClick="select_all_page(this.checked,this.form);"></label></div></td> {* crmv@82419 *}
	            {/if} {* crmv@42752 *}
				{foreach name="listviewforeach" item=header from=$LISTHEADER}
					<td class="lvtCol">{$header}</td>
				{/foreach}
            </tr>
			{if !empty($GRIDSEARCHARR)}
	            <tr id="gridSrc" valign="top" bgcolor="white">
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
								{if isset($search.module)} {* crmv@75024 *}
									{include file="EditViewUI.tpl" NOLABEL=true GRIDSEARCH=true MODULE=$search.module DIVCLASS=$search.divclass uitype=$search.uitype keymandatory=$search.mandatory fldlabel=$search.label fldname=$search.name fldvalue=$search.value}
								{/if} {* crmv@75024 *}
							{/if}
						</td>
					{/foreach}
	            </tr>
			{/if}
			<!-- Table Contents -->
			{foreach item=entity key=entity_id from=$LISTENTITY}
				<!-- crmv@7230 -->
				{assign var=color value=$entity.clv_color}
				{assign var=foreground value=$entity.clv_foreground}
				{assign var=cell_class value="listview-cell"}
				
				{if !empty($foreground)}
					{assign var=cell_class value=$cell_class|cat:" color-`$foreground`"}
				{/if}
				
				<tr bgcolor=white onMouseOver="this.className='lvtColDataHover'" onMouseOut="this.className='lvtColData'" id="row_{$entity_id}">

					{* crmv@82419 *}
					{if $HIDE_LIST_CHECKBOX neq '1'} {* crmv@42752 *}
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
					{/if} {* crmv@42752 *}
					{* crmv@82419e *}

					{foreach name="entityforeach" key=colname item=data from=$entity}
						{if ($colname neq 'clv_color' and $colname neq 'clv_status' and $colname neq 'clv_foreground') or $colname eq '0'} {* crmv@106058 *}
							<td bgcolor="{$color}" class="{$cell_class}"{if $smarty.foreach.entityforeach.index eq 0} nowrap{/if}>{$data}</td>
						{/if}
					{/foreach}
				</tr>
				<!-- crmv@7230e -->
            {foreachelse}
            <tr><td style="background-color:#ffffff;height:340px" align="center" colspan="{$smarty.foreach.listviewforeach.iteration+1}">
            <div style="border: 1px solid rgb(246, 249, 252); background-color: rgb(255, 255, 255); width: 45%; position: relative;">	<!-- crmv@18592 -->
                {assign var=vowel_conf value='LBL_A'}
                {if $CHECK.EditView eq 'yes' && $MODULE neq 'Emails'}
	                <table border="0" cellpadding="5" cellspacing="0" width="98%">
	                <tr>
	                    <td rowspan="2" width="25%"><img src="{$IMAGE_PATH}empty.jpg" height="60" width="61"></td>
	                    <td class="small" align="left" style="border-bottom: 1px solid rgb(204, 204, 204);" nowrap="nowrap" width="75%"><span class="genHeaderSmall">
	                    <!-- crmv@10453 -->
	                    {$APP.LBL_NO_M} {$APP.LBL_RECORDS} {$APP.LBL_FOUND} !
	                    <!-- crmv@10453e -->
	                    </span></td>
	                </tr>
	                {if $MODULE neq 'Charts'} {* crmv@30967 *}
	                <tr>
	                    <td class="small" align="left" nowrap="nowrap">
							{$APP.LBL_EMPTY_LIST_YOU_CAN_CREATE_RECORD_NOW}<br>
							{if $MODULE neq 'Calendar'}
								- <a href="index.php?module={$MODULE}&action=EditView&return_action=DetailView&parenttab={$CATEGORY}">{$MODULE|getNewModuleLabel}</a><br>	{* crmv@59091 *}
		                    {else}
			                    - <a href="index.php?module={$MODULE}&amp;action=EditView&amp;return_module=Calendar&amp;activity_mode=Events&amp;return_action=DetailView&amp;parenttab={$CATEGORY}">{'Event'|getNewModuleLabel}</a><br>	{* crmv@59091 *}
			                    - <a href="index.php?module={$MODULE}&amp;action=EditView&amp;return_module=Calendar&amp;activity_mode=Task&amp;return_action=DetailView&amp;parenttab={$CATEGORY}">{'Task'|getNewModuleLabel}</a>	{* crmv@59091 *}
	                    	{/if}
	                    </td>
	                </tr>
	                {/if}
	                </table>
				{else}
	                <table border="0" cellpadding="5" cellspacing="0" width="98%">
	                <tr>
	                <td rowspan="2" width="25%"><img src="{'denied.gif'|resourcever}"></td>
	                <td style="border-bottom: 1px solid rgb(204, 204, 204);" nowrap="nowrap" width="75%"><span class="genHeaderSmall">
	                {$APP.LBL_NO_M} {$APP.LBL_RECORDS} {$APP.LBL_FOUND} !
	                </tr>
	                <tr>
	                <td class="small" align="left" nowrap="nowrap">{$APP.LBL_YOU_ARE_NOT_ALLOWED_TO_CREATE} {$APP.$vowel_conf} {$APP.LBL_RECORDS}
	                <br>
	                </td>
	                </tr>
	                </table>
                {/if}
                </div>
                </td></tr>
                 {/foreach}
             </table>
             </div>

			{* crmv@10759 crmv@18592 *}
			<table width=100%>
				<tr>
					<td id="rec_string2" align="left" class="small" width="33%" nowrap>{$RECORD_COUNTS}</td>
					<td id="nav_buttons2" align="center" style="padding:5px;" width="33%">{$NAVIGATION}</td>
					<td width="33%" align="right">
						{if $CAN_EDIT_COUNTS} {* crmv@173746 *}
						{* crmv@30967 crmv@31245 *}
						<table border=0 cellspacing=0 cellpadding=0 class="small listViewCounts"><tr>
							<td>{$APP.LBL_LIST_SHOW}</td>
		                    <td style="padding-left:5px;padding-right:5px">
								<div class="dvtCellInfo">
									<SELECT NAME="counts" id="counts" class="detailedViewTextBox" onchange="ModuleHome.editListViewCounts(this, '{$MODULE}', '{$FOLDERID}')">{$CUSTOMCOUNTS_OPTION}</SELECT>
								</div>
							</td>
							<td>{$APP.LBL_ELEMENTS}</td>
						</tr></table>
						{/if}					
					</td>
				</tr>
			</table>
			{* crmv@10759e crmv@18592e *}
			
			<!-- List View's Buttons and Filters ends -->
			</td>
		</tr>
	</table>
</form>
{$SELECT_SCRIPT}

<div id="basicsearchcolumns" style="display:none;"><select name="search_field" id="bas_searchfield" class="txtBox" style="width:150px">{html_options  options=$SEARCHLISTHEADER}</select></div>

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

{* crmv@18592 crmv@7216 crmv@10759 crmv@16627 *}
<script type="text/javascript">
function unselectAllIds()
{ldelim}
	var button_top = document.getElementById("select_all_button_top");
	button_top.value = "{$APP.LBL_SELECT_ALL_IDS}";
{rdelim}

function selectAllIds()
{ldelim}
	var button_top = document.getElementById("select_all_button_top");
	var choose_id = document.getElementById("select_ids");

	if (button_top.value == "{$APP.LBL_SELECT_ALL_IDS}")
	{ldelim}
		button_top.innerHTML = button_top.value = "{$APP.LBL_UNSELECT_ALL_IDS}";
		document.getElementById("all_ids").value = 1;
		document.getElementById("selected_ids").value = '';
		document.getElementById("selectall").checked=true;
		if (isdefined("selected_id")){ldelim}
			if (typeof(getObj("selected_id").length)=="undefined")
			{ldelim}
				getObj("selected_id").checked=true;
			{rdelim} else {ldelim}
				for (var i=0;i<getObj("selected_id").length;i++){ldelim}
					getObj("selected_id")[i].checked=true;
				{rdelim}
			{rdelim}
		{rdelim}
	{rdelim} else {ldelim}
		button_top.innerHTML = button_top.value = "{$APP.LBL_SELECT_ALL_IDS}";
		choose_id.value = "";
		document.getElementById("all_ids").value = '';
		document.getElementById("selected_ids").value="";
		document.getElementById("selectall").checked=false;
		if (typeof(getObj("selected_id").length)=="undefined")
		{ldelim}
			getObj("selected_id").checked=false;
		{rdelim} else {ldelim}
			for (var i=0;i<getObj("selected_id").length;i++){ldelim}
				getObj("selected_id")[i].checked=false;
			{rdelim}
		{rdelim}
	{rdelim}
{rdelim}

{literal}
jQuery(document).ready(function() {
	var loader = jQuery('#modhome_loader');
	if (loader.length == 0) {
		update_navigation_values(window.location.href,gVTModule);
	}
});
{/literal}
</script>