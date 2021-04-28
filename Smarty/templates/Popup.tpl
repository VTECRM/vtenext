{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@82831 crmv@94525 *}
{include file="HTMLHeader.tpl" head_include="jquery,jquery_plugins,jquery_ui,fancybox,prototype,jscalendar,sdk_headers"}

<body class="small">

{* include some additional scripts *}
<script language="JavaScript" type="text/javascript" src="{"include/js/Inventory.js"|resourcever}"></script>
<script language="JavaScript" type="text/javascript" src="{"include/js/ListView.js"|resourcever}"></script>
<script language="JavaScript" type="text/javascript" src="{"modules/`$MODULE`/`$MODULE`.js"|resourcever}"></script>
<script type="text/javascript" src="{"include/js/Mail.js"|resourcever}"></script> {* crmv@21048m *}

{* This is necessary for the quickcreate, in order to open other nested popups *}
<div id="popupContainer" style="display:none;"></div>

<script language="javascript" type="text/javascript">
var image_pth = '{$IMAGE_PATH}';
{literal}
function showAllRecords()
{
		modname = document.getElementById("relmod").name;
		idname= document.getElementById("relrecord_id").name;
		var locate = location.href;
		url_arr = locate.split("?");
		emp_url = url_arr[1].split("&");
		for(i=0;i< emp_url.length;i++)
		{
				if(emp_url[i] != '')
				{
						split_value = emp_url[i].split("=");
						if(split_value[0] == modname || split_value[0] == idname )
								emp_url[i]='';
						else if(split_value[0] == "fromPotential" || split_value[0] == "acc_id")
								emp_url[i]='';

				}
		}
		correctUrl =emp_url.join("&");
		Url = "index.php?"+correctUrl;
		return Url;
}
//crmv@26265
function showAllRecordsSDK()
{
		var locate = location.href;
		url_arr = locate.split("?");
		emp_url = url_arr[1].split("&");
		for(i=0;i< emp_url.length;i++)
		{
				if(emp_url[i] != '')
				{
						split_value = emp_url[i].split("=");
						if(split_value[0] == "sdk_view_all")
							emp_url[i]='';
				}
		}
		correctUrl = emp_url.join("&");
		Url = "index.php?"+correctUrl+'&sdk_view_all=true';
		return Url;
}
//crmv@26265e
//function added to get all the records when parent record doesn't relate with the selection module records while opening/loading popup.
function redirectWhenNoRelatedRecordsFound() {
	var loadUrl = showAllRecords();
	window.location.href = loadUrl;
}
{/literal}

function add_data_to_relatedlist(entity_id,recordid,mod) {ldelim}
	parent.document.location.href="index.php?module={$RETURN_MODULE}&action=updateRelations&destination_module="+mod+"&entityid="+entity_id+"&parentid="+recordid+"&return_module={$RETURN_MODULE}&return_action={$RETURN_ACTION}&parenttab={$CATEGORY}"; {* crmv@21048m *}
{rdelim}
//crmv@8839
function add_data_to_relatedlist_4you(mode,entity_id,recordid,mod) {ldelim}
	parent.document.location.href="index.php?module={$RETURN_MODULE}&action=updateRelations&mode="+mode+"&destination_module="+mod+"&entityid="+entity_id+"&parid="+recordid+"&return_module={$RETURN_MODULE}&return_action={$RETURN_ACTION}&parenttab={$CATEGORY}"; {* crmv@21048m *}
{rdelim}
//crmv@8839e

var otherPagesSelections = {ldelim}{rdelim}; // crmv@107661
</script>

{include file="Theme.tpl" THEME_MODE="body"}

{include file='CachedValues.tpl'}	{* crmv@26316 *}
{include file='modules/SDK/src/Reference/Autocomplete.tpl'}	{* crmv@29190 *}


<input type="hidden" id="user_dateformat" name="user_dateformat" value="{$DATEFORMAT}">
<textarea name="select_ids" id="select_ids" style="display:none;"></textarea>


{* crmv@72993 - some fields to be populated with data that must not be lost with page changes *}
<input type="hidden" name="popup_select_actions" id="popup_select_actions" value="" />
<input type="hidden" name="popup_data_custom" id="popup_data_custom" value="" />
{* crmv@72993e *}

<table id="moduleTable" width="100%" border="0" cellpadding="0" cellspacing="0" class="mailClientWriteEmailHeader level2Bg menuSeparation"> {* crmv@20172 *}
	<tr>
		{*//crmv@24577*}
		<td width="100%"><a href="javascript:void(0);" onclick="reset_search_popup();" title="{$APP.LBL_RESET}">{$APP[$MODULE]}{if $recid_var_value neq ''}&nbsp;{$APP.RELATED_PARENT}{/if}</a></td>
		{*//crmv@24577e*}
	</tr>
</table>

<table id="searchTable" width="100%" cellpadding="5" cellspacing="0" border="0"> {* crmv@20172 *}
	<tr>
		<td>
	    	<!-- SIMPLE SEARCH -->
			<div id="searchAcc" style="position:relative;">
				<form name="basicSearch" action="index.php" onsubmit="callSearch('Basic');return false;" style="margin:0px;">
					<input type="hidden" name="search_cnt">
					<table width="100%" cellpadding="0" cellspacing="0">
						<tr>
							<td width="20%" class="dvtCellLabel">
								<a href="#" title="{$APP.LBL_GO_TO} {$APP.LNK_ADVANCED_SEARCH}" onClick="fnhide('searchAcc');window.show('advSearch');updatefOptions(document.getElementById('Fields0'), 'Condition0');document.basicSearch.searchtype.value='advance';"> <!-- crmv@98866 -->
									<i class="vteicon md-text md-lg">search</i>
								</a>
							</td>
							<td width="30%" class="dvtCellInfo"><input type="text" name="search_text" class="detailedViewTextBox"> </td>
							<td class="dvtCellLabel" align="center"><b>{$APP.LBL_IN}</b></td> <!-- crmv@98866 -->
							<td width="30%" class="dvtCellInfo">
								<select name ="search_field" class="detailedViewTextBox"> <!-- crmv@98866 -->
									{html_options options=$SEARCHLISTHEADER}
								</select>
								<input type="hidden" name="searchtype" value="BasicSearch">
								<input type="hidden" name="module" value="{$MODULE}">
								<input type="hidden" name="action" value="Popup">
								<input type="hidden" name="query" value="true">
								<input type="hidden" name="select_enable" id="select_enable" value="{$SELECT}">
								<input type="hidden" name="curr_row" id="curr_row" value="{$CURR_ROW}">
								<input type="hidden" name="fldname_pb" value="{$FIELDNAME}">
								<input type="hidden" name="productid_pb" value="{$PRODUCTID}">
								<input type="hidden" name="form" value="{$FORM}">
								{* //sk@2 *}
								{if $MODULE eq 'Users' && $smarty.request.popuptype eq 'select_worker'}
									<input name="inputid" id="inputid" type="hidden" value="{$smarty.request.inputid}">
								{/if}
								{* //sk@2e *}
								<input name="popuptype" id="popup_type" type="hidden" value="{$POPUPTYPE}">
								<input name="recordid" id="recordid" type="hidden" value="{$RECORDID}">
								<input name="return_module" id="return_module" type="hidden" value="{$RETURN_MODULE}">
								<input name="from_link" id="from_link" type="hidden" value="{$smarty.request.fromlink.value}">
								<input name="maintab" id="maintab" type="hidden" value="{$MAINTAB}">
								<input type="hidden" id="relmod" name="{$mod_var_name}" value="{$mod_var_value}">
								<input type="hidden" id="relrecord_id" name="{$recid_var_name}" value="{$recid_var_value}">
								<input type="hidden" id="mode" name="{$MODE}" value="{$MODE}">
								{* vtlib customization: For uitype 10 popup during paging *}
								{if $smarty.request.form eq 'vtlibPopupView' || $smarty.request.form eq 'HelpDeskEditView'} {* crmv@26920 *}
									<input name="form"  id="popupform" type="hidden" value="{$smarty.request.form|@vtlib_purify}">
									<input name="forfield"  id="forfield" type="hidden" value="{$smarty.request.forfield|@vtlib_purify}">
									<input name="srcmodule"  id="srcmodule" type="hidden" value="{$smarty.request.srcmodule|@vtlib_purify}">
									<input name="forrecord"  id="forrecord" type="hidden" value="{$smarty.request.forrecord|@vtlib_purify}">
								{/if}
								{if $smarty.request.currencyid neq ''}
									<input type="hidden" name="curr_row" id="currencyid" value="{$smarty.request.currencyid|@vtlib_purify}">
								{/if}
								{* END *}
								{* crmv@26807 *}
								{if $FROM_CALENDAR eq 'fromCalendar'}
									<input type="hidden" id="fromCalendar" name="fromCalendar" value="{$smarty.request.fromCalendar|@vtlib_purify}">
									<input type="hidden" id="parentId" name="parentId" value="{$smarty.request.parentId|@vtlib_purify}">
								{/if}
								{* crmv@26807e *}
								<input type="hidden" id="sdk_view_all" name="sdk_view_all" value="{$sdk_view_all}">	{* crmv@26265 *}
								{* crmv@26920 *}
								{if $SDK_POPUP_PARAMS}
									{foreach item=field from=$SDK_POPUP_PARAMS}
										<input type="hidden" id="{$field}" name="{$field}" value="{$smarty.request.$field|@vtlib_purify}">
									{/foreach}
								{/if}
								{* crmv@26920e *}
							</td>
							<td width="20%" class="dvtCellLabel">
								<input type="button" name="search" value=" &nbsp;{$APP.LBL_SEARCH_NOW_BUTTON}&nbsp; " onClick="callSearch('Basic');" class="crmbutton small create">
							</td>
						</tr>
						<tr>
							<td colspan="5" align="center">
								<table width="100%" class="table-fixed">
									<tr>
										{$ALPHABETICAL}
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</form>
			</div>
			<!-- ADVANCED SEARCH -->
			<div id="advSearch" style="position:relative;display:none;" >
				<form name="advSearch" method="post" action="index.php" onSubmit="totalnoofrows();return callSearch('Advanced');" style="margin:0px;">
					<input type="hidden" id="basic_search_cnt" name="search_cnt" /> {* crmv@31979 *}
					<table width="100%" cellpadding="0" cellspacing="0">
						<tr class="small">
							<td width="20%" class="dvtCellLabel" valign="top">
								<a href="#" title="{$APP.LBL_GO_TO} {$APP.LNK_ADVANCED_SEARCH}" onClick="window.show('searchAcc');fnhide('advSearch')"> <!-- crmv@98866 -->
									<i class="vteicon md-text md-lg">search</i>
								</a>
							</td>
							<td class="dvtCellLabel" width="80%" colspan="3">
								<table width="100%" border="0" cellpadding="10" cellspacing="0" align="left">
									<tr>
										<td nowrap class="small"><b><input id="matchtype_all" name="matchtype" type="radio" value="all" onclick="updatefOptionsAll(this.value);">&nbsp;<label for="matchtype_all">{$APP.LBL_ADV_SEARCH_MSG_ALL}</label></b></td>
										<td nowrap class="small"><b><input id="matchtype_any" name="matchtype" type="radio" value="any" checked onclick="updatefOptionsAll(this.value);">&nbsp;<label for="matchtype_any">{$APP.LBL_ADV_SEARCH_MSG_ANY}</label></b></td>
										<td width=60%></td>
									<tr>
								</table>
								<div id="fixed" style="position:relative;width:100%;max-height:250px;padding:5px;overflow:auto;border:1px solid #CCCCCC;">
									<table width="100%"  border="0" cellpadding="2" cellspacing="0" id="adSrc" align="left">
										<tr>
											<td width="30%">
												<div class="dvtCellInfo">
													<select name="Fields0" id="Fields0" class="detailedViewTextBox" onchange="updatefOptions(this, 'Condition0')">{$FIELDNAMES}</select>
												</div>
											</td>
											<td width="25%">
												<div class="dvtCellInfo">
													<select name="Condition0" id="Condition0" class="detailedViewTextBox">{$CRITERIA}</select>
												</div>
											</td>
											<td width="40%">
												<div class="dvtCellInfo">
													<input type="text" name="Srch_value0" id="Srch_value0"  class="detailedViewTextBox">
												</div>
											</td>
											<td width="5%"><div id="andFields0" name="and0" width="10%"><script type="text/javascript">getcondition(false)</script></div></td>
										</tr>
									</table>
								</div>
								<table width="100%" border="0" cellpadding="5" cellspacing="0" align="left">
									<tr>
										<td nowrap width=20% class="small" valign="top">
											<a href="#" onClick="fnAddSrch()" >{$APP.LBL_MORE}</a> |
											<a href="#" onClick="delRow()" >{$APP.LBL_FEWER_BUTTON}</a>
										</td>
										<td nowrap width=60% class="dvtCellLabel" align="center">
											<input type="button" class="crmbutton small create" value=" {$APP.LBL_SEARCH_NOW_BUTTON} " onClick="totalnoofrows();callSearch('Advanced');">
										</td>
										<td width=20%></td>
									<tr>
								</table>
							</td>
						</tr>
					</table>
				</form>
			</div>
		</td>
	</tr>
	{if $recid_var_value neq ''}
	<tr>
		{*//crmv@23147*}
		<td align="right"><input id="all_contacts" alt="{$APP.LBL_SELECT_BUTTON_LABEL} {$APP.$MODULE}" title="{$APP.LBL_SELECT_BUTTON_LABEL} {$APP.$MODULE}" accessKey="" class="crmbutton small edit" value="{$APP.SHOW_ALL}&nbsp;{$APP.$MODULE}" LANGUAGE=javascript onclick="window.location.href=showAllRecords();" type="button" name="button"></td>
		{*//crmv@23147 end*}
	</tr>
	{* crmv@26265 *}
	{elseif $sdk_show_all_button eq 'true'}
		<tr>
			<td align="right"><input id="all_contacts" alt="{$APP.LBL_SELECT_BUTTON_LABEL} {$APP.$MODULE}" title="{$APP.LBL_SELECT_BUTTON_LABEL} {$APP.$MODULE}" accessKey="" class="crmbutton small edit" value="{$APP.SHOW_ALL}&nbsp;{$APP.$MODULE}" LANGUAGE=javascript onclick="window.location.href=showAllRecordsSDK();" type="button" name="button"></td>
		</tr>
	{/if}
	{* crmv@26265e *}
</table>

<div id="status" style="position:absolute;display:none;right:30px;top:65px;white-space:nowrap;">{include file="LoadingIndicator.tpl"}</div> {* crmv@98866 *}

<!-- crmv@16265 : QuickCreatePopup -->
{if $QUICKCREATE eq 'permitted'}
	<div id="qcform" class="qcform" style="display:none;"></div>
	<div id="create" align="left" style="padding: 10px;">
		{* crmv@26265 *}
		{if $MODULE eq 'Calendar' && $CVID_TASKS neq ''}
			<script type="text/javascript">var cvid_tasks = '{$CVID_TASKS}';</script>
		{/if}
		{* crmv@26265e *}
		<a href="javascript:;" onclick="QCreate('{$MODULE}');">
			<i class="vteicon md-text md-lg">add</i>
		</a>
	</div>
{/if}
<!-- crmv@16265e -->

<div id="ListViewContents">
	{include file="PopupContents.tpl"}
</div>

<script type="text/javascript">
var gPopupAlphaSearchUrl = '';
var gsorder ='';
var gstart ='';
var ajaxcall_list = null;
var ajaxcall_count = null;
var popupSearchType = '';	//crmv@44854

function callSearch(searchtype)
{ldelim}
	if (ajaxcall_list){ldelim}
		ajaxcall_list.abort();
	{rdelim}
	if (ajaxcall_count){ldelim}
		ajaxcall_count.abort();
	{rdelim}
	popupSearchType = searchtype;	//crmv@44854
	jQuery("#status").show();
	gstart='';
	for(i=1;i<=26;i++)
	{ldelim}
		var data_td_id = 'alpha_'+ eval(i);
		getObj(data_td_id).className = 'crmbutton edit searchAlph';
	{rdelim}
	gPopupAlphaSearchUrl = '';
	search_fld_val= document.basicSearch.search_field[document.basicSearch.search_field.selectedIndex].value;
	search_txt_val= encodeURIComponent(document.basicSearch.search_text.value.replace(/\'/,"\\'"));
	var urlstring = '';
	if(searchtype == 'Basic')
	{ldelim}
	urlstring = 'search_field='+search_fld_val+'&searchtype=BasicSearch&search_text='+search_txt_val;
	{rdelim}
	else if(searchtype == 'Advanced')
	{ldelim}
			var no_rows = document.advSearch.search_cnt.value;  //crmv@31979
			for(jj = 0 ; jj < no_rows; jj++)
			{ldelim}
					var sfld_name = getObj("Fields"+jj);
					var scndn_name= getObj("Condition"+jj);
					var srchvalue_name = getObj("Srch_value"+jj);
					var p_tab = document.getElementsByName("parenttab");
					urlstring = urlstring+'Fields'+jj+'='+sfld_name[sfld_name.selectedIndex].value+'&';
					urlstring = urlstring+'Condition'+jj+'='+scndn_name[scndn_name.selectedIndex].value+'&';
					urlstring = urlstring+'Srch_value'+jj+'='+encodeURIComponent(srchvalue_name.value)+'&';
			{rdelim}
			urlstring += 'matchtype='+jQuery("input[name=matchtype]:checked").val()+'&';
			urlstring += 'search_cnt='+no_rows+'&';
			urlstring += 'searchtype=advance&'
	{rdelim}
	popuptype = jQuery('#popup_type').val();
	act_tab = jQuery('#maintab').val();
	urlstring += '&popuptype='+popuptype;
	urlstring += '&maintab='+act_tab;
	urlstring = urlstring +'&query=true&file=Popup&module={$MODULE}&action={$MODULE}Ajax&ajax=true&search=true';
	urlstring +=gethiddenelements();
	urlstring += "&start=1";
	jQuery.ajax({ldelim}
		url: 'index.php',
		method: 'POST',
		data: urlstring,
		success: function(result) {ldelim}
			jQuery("#status").hide();
			jQuery("#ListViewContents").html(result);
			update_navigation_values(urlstring);

			setListHeight(); {* crmv@21048m *}
		{rdelim}
	{rdelim});
{rdelim}

function alphabetic(module,url,dataid)
{ldelim}
	if (ajaxcall_list){ldelim}
		ajaxcall_list.abort();
	{rdelim}
	if (ajaxcall_count){ldelim}
		ajaxcall_count.abort();
	{rdelim}
	popupSearchType = 'alphabetic';	//crmv@44854
	gstart='';
	document.basicSearch.search_text.value = '';
	for(i=1;i<=26;i++)
	{ldelim}
	var data_td_id = 'alpha_'+ eval(i);
	getObj(data_td_id).className = 'crmbutton edit searchAlph';
	{rdelim}
	jQuery("#status").show();
	getObj(dataid).className = 'crmbutton edit searchAlphselected';
	gPopupAlphaSearchUrl = '&'+url;
	var urlstring ="module="+module+"&action="+module+"Ajax&file=Popup&ajax=true&search=true&"+url;
	//crmv@21088
	popuptype = jQuery('#popup_type').val();
	act_tab = jQuery('#maintab').val();
	urlstring += '&popuptype='+popuptype;
	urlstring += '&maintab='+act_tab;
	//crmv@21088e
	urlstring +=gethiddenelements();
	jQuery.ajax({ldelim}
		url: 'index.php',
		method: 'POST',
		data: urlstring,
		success: function(result) {ldelim}
			jQuery("#status").hide();
			jQuery("#ListViewContents").html(result);
			update_navigation_values(urlstring);

			setListHeight(); {* crmv@21048m *}
		{rdelim}
	{rdelim});
{rdelim}

function gethiddenelements()
{ldelim}
	gstart='';
	var urlstring=''
	if(getObj('select_enable').value != '')
		urlstring +='&select=enable';
	if(document.getElementById('curr_row').value != '')
		urlstring +='&curr_row='+document.getElementById('curr_row').value;
	if(getObj('fldname_pb').value != '')
		urlstring +='&fldname='+getObj('fldname_pb').value;
	if(getObj('productid_pb').value != '')
		urlstring +='&productid='+getObj('productid_pb').value;
	if(getObj('recordid').value != '')
		urlstring +='&recordid='+getObj('recordid').value;
	if(getObj('relmod').value != '')
		urlstring +='&'+getObj('relmod').name+'='+getObj('relmod').value;
		if(getObj('relrecord_id').value != '')
			urlstring +='&'+getObj('relrecord_id').name+'='+getObj('relrecord_id').value;
	//crmv@15310
	if(isdefined('form') && getObj('form').value != '')
		urlstring +='&'+getObj('form').name+'='+getObj('form').value;
	//crmv@15310 e
	var return_module = document.getElementById('return_module').value;
	if(return_module != '')
		urlstring += '&return_module='+return_module;

	if (isdefined('selected_ids'))
		urlstring += "&selected_ids=" + document.getElementById('selected_ids').value;
	if (isdefined('all_ids'))
		urlstring += "&all_ids=" + document.getElementById('all_ids').value;
	if (isdefined('mode'))
		urlstring += "&mode=" + document.getElementById('mode').value;

	// vtlib customization: For uitype 10 popup during paging
	if(document.getElementById('popupform'))
		urlstring +='&form='+encodeURIComponent(getObj('popupform').value);
	if(document.getElementById('forfield'))
		urlstring +='&forfield='+encodeURIComponent(getObj('forfield').value);
	if(document.getElementById('srcmodule'))
		urlstring +='&srcmodule='+encodeURIComponent(getObj('srcmodule').value);
	if(document.getElementById('forrecord'))
		urlstring +='&forrecord='+encodeURIComponent(getObj('forrecord').value);
	// END
	{* //sk@2 *}
	if(document.getElementById('inputid'))
		urlstring +='&inputid='+encodeURIComponent(getObj('inputid').value);
	{* crmv@21048m *}
	if(parent.document.getElementById('all_tickets_ids'))
		urlstring += '&stopids='+parent.document.getElementById('all_tickets_ids').value;
	{* crmv@21048me *}
	{* //sk@2e *}

	{* crmv@26807 *}
	if(document.getElementById('fromCalendar')) {ldelim}
		urlstring += '&fromCalendar='+getObj('fromCalendar').value;
		urlstring += '&parentId='+getObj('parentId').value;
	{rdelim}
	{* crmv@26807e *}

	//crmv@26265
	if(document.getElementById('sdk_view_all'))
		urlstring += '&sdk_view_all='+getObj('sdk_view_all').value;
	//crmv@26265e

	{* crmv@26920 *}
	{if $SDK_POPUP_PARAMS}
		{foreach item=field from=$SDK_POPUP_PARAMS}
			if(document.getElementById('{$field}'))
				urlstring +='&{$field}='+encodeURIComponent(getObj('{$field}').value);
		{/foreach}
	{/if}
	{* crmv@26920e *}

	return urlstring;
{rdelim}

function getListViewEntries_js(module,url,quickcreatepopup)	<!-- crmv@16265 : QuickCreatePopup -->
{ldelim}
	if (ajaxcall_list){ldelim}
	ajaxcall_list.abort();
	{rdelim}
	if (ajaxcall_count){ldelim}
	ajaxcall_count.abort();
	{rdelim}
	jQuery("#status").show();
	gstart="&"+url;
	popuptype = document.getElementById('popup_type').value;
	var urlstring ="module="+module+"&action="+module+"Ajax&file=Popup&ajax=true&"+url;
	urlstring += gethiddenelements();
	//crmv@44854
	if(popupSearchType == 'Basic'){ldelim}
		search_fld_val = document.basicSearch.search_field[document.basicSearch.search_field.selectedIndex].value;
		search_txt_val = document.basicSearch.search_text.value;
		if(search_txt_val != '')
			urlstring += '&query=true&search_field='+search_fld_val+'&searchtype=BasicSearch&search_text='+search_txt_val;
	{rdelim}
	else if (popupSearchType == 'Advanced'){ldelim}
		urlstring += '&';
		var no_rows = document.advSearch.search_cnt.value;  //crmv@31979
		for(jj = 0 ; jj < no_rows; jj++)
		{ldelim}
			var sfld_name = getObj("Fields"+jj);
			var scndn_name= getObj("Condition"+jj);
			var srchvalue_name = getObj("Srch_value"+jj);
			var p_tab = document.getElementsByName("parenttab");
			urlstring += 'Fields'+jj+'='+sfld_name[sfld_name.selectedIndex].value+'&';
			urlstring += 'Condition'+jj+'='+scndn_name[scndn_name.selectedIndex].value+'&';
			urlstring += 'Srch_value'+jj+'='+encodeURIComponent(srchvalue_name.value)+'&';
		{rdelim}
		urlstring += 'matchtype='+jQuery("input[name=matchtype]:checked").val()+'&'; // crmv@141035
		urlstring += 'search_cnt='+no_rows+'&';
		urlstring += 'searchtype=advance&';
		urlstring += 'query=true&';
	{rdelim}
	//crmv@44854e
	if(gPopupAlphaSearchUrl != '')
		urlstring += gPopupAlphaSearchUrl;
	else
		urlstring += '&popuptype='+popuptype;
	urlstring += (gsorder !='') ? gsorder : '';

	jQuery.ajax({ldelim}
		url: 'index.php',
		method: 'POST',
		data: urlstring,
		success: function(result) {ldelim}
			jQuery("#status").hide();
			jQuery("#ListViewContents").html(result);
			//crmv@16265 : QuickCreatePopup
			if (quickcreatepopup == true)
				update_navigation_values(urlstring);
			//crmv@16265e

			setListHeight(); {* crmv@21048m *}
		{rdelim}
	{rdelim});
{rdelim}

function getListViewSorted_js(module,url) {ldelim}
	if (ajaxcall_list){ldelim}
	ajaxcall_list.abort();
	{rdelim}
	if (ajaxcall_count){ldelim}
	ajaxcall_count.abort();
	{rdelim}
	jQuery("#status").show();
	gsorder=url;
	var urlstring ="module="+module+"&action="+module+"Ajax&file=Popup&ajax=true"+url;
	urlstring +=gethiddenelements();
	urlstring += (gstart !='') ? gstart : '';
	jQuery.ajax({ldelim}
		url: 'index.php',
		method: 'POST',
		data: urlstring,
		success: function(result) {ldelim}
			jQuery("#status").hide();
			jQuery("#ListViewContents").html(result);
			update_navigation_values(urlstring);

			setListHeight(); {* crmv@21048m *}
		{rdelim}
	{rdelim});
{rdelim}

function updateIdstrings(obj,entityid) {ldelim}
	var idstring = document.selectall.selected_ids.value;
	document.selectall.select_all.checked = false;

	if (idstring == "") idstring = ";";

	if (obj.checked == true)
	{ldelim}
		document.selectall.selected_ids.value = idstring + entityid + ";";
	{rdelim}
	else
	{ldelim}
		newidstring = idstring.replace(";" + entityid + ";", ";");
		document.selectall.selected_ids.value = newidstring;
	{rdelim}
{rdelim}

function saveRelation(mod,parmod) {ldelim}
	{* crmv@21048m *}
	var module = parent.document.getElementById('RLreturn_module').value;
	var entity_id = parent.document.getElementById('RLparent_id').value;
	var parenttab = parent.document.getElementById('parenttab').value;
	var idstring = document.selectall.selected_ids.value;
	{* crmv@21048me *}
	if (idstring != "" && idstring !=";")
	{ldelim}
		parent.document.location.href="index.php?module="+module+"&parentid="+entity_id+"&action=updateRelations&destination_module="+mod+"&idlist="+idstring+"&parenttab="+parenttab; {* crmv@21048m *}
		return true;
	{rdelim} else {ldelim}
		alert(alert_arr.SELECT);
		return false;
	{rdelim}
{rdelim}

{literal}

function update_selected_ids(checked,entityid,form) {
	var idstring = form.selected_ids.value;
	if (idstring == "") idstring = ";";
	if (checked == true)
	{
		form.selected_ids.value = idstring + entityid + ";";
	}
	else
	{
		form.selectall.checked = false;
		form.selected_ids.value = idstring.replace(entityid + ";", '');
	}

	// crmv@107661 - memorize the values to use them if I change page
	if (window.otherPagesSelections) {
		if (checked == true) {
			// add the json data to the object
			var $el = jQuery('#popup_product_'+entityid),
				prodData = $el.attr('vt_prod_arr');
			if (prodData) {
				otherPagesSelections[entityid] = prodData;
			}
		} else {
			// remove it
			delete otherPagesSelections[entityid];
		}
	}
	// crmv@107661e

	update_invitees_actions(checked, entityid, form); // crmv@72993
}

function select_all_page(state,form)
{
	if (typeof(form.selected_id.length)=="undefined"){
		if (form.selected_id.checked != state){
			form.selected_id.checked = state;
			update_selected_ids(state,form.selected_id.value,form)
		}
	}
	else {
		for (var i=0;i<form.selected_id.length;i++){
			obj_check = form.selected_id[i];
			if (obj_check.checked != state){
				obj_check.checked = state;
				update_selected_ids(state,obj_check.value,form)
			}
		}
	}
}

//crmv@98866
//crmv@16265 : QuickCreatePopup
function QCreate(module){
	// copia della funzione in Header.tpl
	// cambia solo che passo la variabile module invece che ricavarla da qcoptions
	// e poi viene passato il parametro quickcreatepopup a urlstr

	if(module != 'none'){
		jQuery("#status")[0].style.display="inline";
		//crmv@26265
		if(module == 'Calendar' && cvid_tasks != undefined){
			if (jQuery('#viewname').val() != cvid_tasks) {
				module = 'Events';
			}
		}
		//crmv@26265e
		if(module == 'Events'){
			module = 'Calendar';
			var urlstr = '&activity_mode=Events';
		}else if(module == 'Calendar'){
			module = 'Calendar';
			var urlstr = '&activity_mode=Task';
		}else{
			var urlstr = '';
		}
		urlstr += "&quickcreatepopup=true";
		//crmv@26631
		search_fld_val = document.basicSearch.search_field[document.basicSearch.search_field.selectedIndex].value;
		search_txt_val = encodeURIComponent(document.basicSearch.search_text.value.replace(/\'/,"\\'"));
		if (search_fld_val != '' && search_txt_val != '') {
			urlstr += '&search_field='+search_fld_val+'&search_text='+search_txt_val;
		}
		//crmv@36532
		urlstr+=gethiddenelements();
		//crmv@36532 e    	
		//crmv@26631e
		jQuery.ajax({
			url: 'index.php',
			method: 'post',
			dataType: 'html',
			data: 'module='+module+'&action='+module+'Ajax&file=QuickCreate'+urlstr,
			success: function(response){
				jQuery("#status")[0].style.display="none";
				
				VTE.showModal('qcform', '', response, {
					large: true,
				});

				var options = {
					beforeSubmit:	'',  					// pre-submit callback
					success:		reloadAfterQCreate,  	// post-submit callback
					dataType:		'html'					// 'xml', 'script', or 'json' (expected server response type)
				};
				jQuery('#QcEditView').ajaxForm(options);
			}
		});
	}else{
		VTE.hideModal('qcform');
	}
}

// crmv@98866 e
function reloadAfterQCreate(response) {
	VTE.hideModal('qcform');
	response = response.replace(/(<([^>]+)>)/ig,""); //strip html to get json response
 	response = eval('('+response+')');
	//crmv@26265
	getObj('sdk_view_all').value = 'true';
	if (getObj('all_contacts') != undefined) {
		getObj('all_contacts').style.display = 'none';
	}
	//crmv@26265e
	var url = '&query=true&search_field=crmid&searchtype=BasicSearch&search_text='+response['record'];
	document.basicSearch.search_text.value = '';
	getListViewEntries_js(response['module'],url,true);
}

function getFormValidate(divValidate)
{
// copia della funzione in Header.tpl
	var st = document.getElementById('qcvalidate');
	eval(st.innerHTML);
	for (var i=0; i<qcfieldname.length; i++) {
		var curr_fieldname = qcfieldname[i];
		if(window.document.QcEditView[curr_fieldname] != null)
		{
			var type=qcfielddatatype[i].split("~")
			var input_type = window.document.QcEditView[curr_fieldname].type;
			if (type[1]=="M") {
					if (!qcemptyCheck(curr_fieldname,qcfieldlabel[i],input_type))
						return false
				}
			switch (type[0]) {
				case "O"  : break;
				case "V"  : 
					//crmv@135491 - textlength check
					if (type[2] && type[3]){
						if (!lengthComparison(qcfieldname[i],qcfieldlabel[i],type[2],type[3]))
							return false;
					};
					//crmv@135491e
					break;
				case "C"  : break;
				case "DT" :
					if (window.document.QcEditView[curr_fieldname] != null && window.document.QcEditView[curr_fieldname].value.replace(/^\s+/g, '').replace(/\s+$/g, '').length!=0)
					{
						if (type[1]=="M")
							if (!qcemptyCheck(type[2],qcfieldlabel[i],getObj(type[2]).type))
								return false
						if(typeof(type[3])=="undefined") var currdatechk="OTH"
						else var currdatechk=type[3]

						if (!qcdateTimeValidate(curr_fieldname,type[2],qcfieldlabel[i],currdatechk))
							return false
						if (type[4]) {
							if (!dateTimeComparison(curr_fieldname,type[2],qcfieldlabel[i],type[5],type[6],type[4]))
								return false

						}
					}
				break;
				case "D"  :
					if (window.document.QcEditView[curr_fieldname] != null && window.document.QcEditView[curr_fieldname].value.replace(/^\s+/g, '').replace(/\s+$/g, '').length!=0)
					{
						if(typeof(type[2])=="undefined") var currdatechk="OTH"
						else var currdatechk=type[2]

							if (!qcdateValidate(curr_fieldname,qcfieldlabel[i],currdatechk))
								return false
									if (type[3]) {
										if (!qcdateComparison(curr_fieldname,qcfieldlabel[i],type[4],type[5],type[3]))
											return false
									}
					}
				break;
				case "T"  :
					if (window.document.QcEditView[curr_fieldname] != null && window.document.QcEditView[curr_fieldname].value.replace(/^\s+/g, '').replace(/\s+$/g, '').length!=0)
					{
						if(typeof(type[2])=="undefined") var currtimechk="OTH"
						else var currtimechk=type[2]

							if (!timeValidate(curr_fieldname,qcfieldlabel[i],currtimechk))
								return false
									if (type[3]) {
										if (!timeComparison(curr_fieldname,qcfieldlabel[i],type[4],type[5],type[3]))
											return false
									}
					}
				break;
				case "I"  :
					if (window.document.QcEditView[curr_fieldname] != null && window.document.QcEditView[curr_fieldname].value.replace(/^\s+/g, '').replace(/\s+$/g, '').length!=0)
					{
						if (window.document.QcEditView[curr_fieldname].value.length!=0)
						{
							if (!qcintValidate(curr_fieldname,qcfieldlabel[i]))
								return false
							if (type[2]) {
								if (!qcnumConstComp(curr_fieldname,qcfieldlabel[i],type[2],type[3]))
									return false
							}
						}
					}
				break;
				case "N"  :
					case "NN" :
					if (window.document.QcEditView[curr_fieldname] != null && window.document.QcEditView[curr_fieldname].value.replace(/^\s+/g, '').replace(/\s+$/g, '').length!=0)
					{
						if (window.document.QcEditView[curr_fieldname].value.length!=0)
						{
							if (typeof(type[2])=="undefined") var numformat="any"
							else var numformat=type[2]
								// crmv@83877
								if (type[0]=="NN") {
									if (!numValidate(curr_fieldname,qcfieldlabel[i],numformat,true,qcfielduitype[i]))
										return false
								} else {
									if (!numValidate(curr_fieldname,qcfieldlabel[i],numformat,false,qcfielduitype[i]))
										return false
								}
								// crmv@83877e
							if (type[3]) {
								if (!numConstComp(curr_fieldname,qcfieldlabel[i],type[3],type[4]))
									return false
							}
						}
					}
				break;
				case "E"  :
					if (window.document.QcEditView[curr_fieldname] != null && window.document.QcEditView[curr_fieldname].value.replace(/^\s+/g, '').replace(/\s+$/g, '').length!=0)
					{
						if (window.document.QcEditView[curr_fieldname].value.length!=0)
						{
							var etype = "EMAIL"
								if (!qcpatternValidate(curr_fieldname,qcfieldlabel[i],etype))
									return false
						}
					}
				break;
			}
		}
	}
	//added to check Start Date & Time,if Activity Status is Planned.//start
	for (var j=0; j<qcfieldname.length; j++)
	{
		curr_fieldname = qcfieldname[j];
		if(window.document.QcEditView[curr_fieldname] != null)
		{
			if(qcfieldname[j] == "date_start")
			{
				var datelabel = qcfieldlabel[j]
					var datefield = qcfieldname[j]
					var startdatevalue = window.document.QcEditView[datefield].value.replace(/^\s+/g, '').replace(/\s+$/g, '')
			}
			if(qcfieldname[j] == "time_start")
			{
				var timelabel = qcfieldlabel[j]
					var timefield = qcfieldname[j]
					var timeval=window.document.QcEditView[timefield].value.replace(/^\s+/g, '').replace(/\s+$/g, '')
			}
			if(qcfieldname[j] == "eventstatus" || qcfieldname[j] == "taskstatus")
			{
				var statusvalue = window.document.QcEditView[curr_fieldname].options[window.document.QcEditView[curr_fieldname].selectedIndex].value.replace(/^\s+/g, '').replace(/\s+$/g, '')
				var statuslabel = qcfieldlabel[j++]
			}
		}
	}
	if(statusvalue == "Planned")
	{
		var dateelements=splitDateVal(startdatevalue)
		var hourval=parseInt(timeval.substring(0,timeval.indexOf(":")))
		var minval=parseInt(timeval.substring(timeval.indexOf(":")+1,timeval.length))
		var dd=dateelements[0]
		var mm=dateelements[1]
		var yyyy=dateelements[2]

		var chkdate=new Date()
		chkdate.setYear(yyyy)
		chkdate.setMonth(mm-1)
		chkdate.setDate(dd)
		chkdate.setMinutes(minval)
		chkdate.setHours(hourval)
	}//end
	//crmv@sdk-18501	//crmv@sdk-26260
	sdkValidate = SDKValidate(window.document.QcEditView);
	if (sdkValidate) {
		sdkValidateResponse = eval('('+sdkValidate.responseText+')');
		if (!sdkValidateResponse['status']) {
			return false;
		}
	}
	//crmv@sdk-18501e	//crmv@sdk-26260e
	return true;
}
//crmv@16265e
{/literal}

{* //sk@2 *}
{if $file != "" }
function set_return_specific(item_id, item_name)
{ldelim}
	{* crmv@21048m *}
	parent.document.getElementById('all_tickets_ids').value += ':'+item_id;
	parent.document.getElementById('{$file}_id').value = item_id;
	parent.document.getElementById('{$file}_name').value = item_name;
	{* crmv@21048me *}
{rdelim}
{/if}
{* //sk@2e *}

function reset_search_popup()
{ldelim}
	jQuery('#search_url').val('');
	var reset_url = window.location.href;
	if (window.location.href.indexOf('reset_query') == -1)
	{ldelim}
		reset_url=reset_url+'&reset_query=true';
	{rdelim}
	window.location.href=reset_url;
{rdelim}

bindButtons(window.top);	//crmv@59626
</script>

</body>
</html>