{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{if $smarty.request.ajax neq ''}
&#&#&#{$ERROR}&#&#&#
{/if}
{if $HIDE_CUSTOM_LINKS eq 1}
<div id="ListViewContents">
{/if}
<form name="massdelete" method="POST" id="massdelete" onsubmit="VteJS_DialogBox.block();">
	 <input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
	 <input name='search_url' id="search_url" type='hidden' value='{$SEARCH_URL}'>
     {if $HIDE_CUSTOM_LINKS eq 1}
      <input id="modulename" name="modulename" type="hidden" value="{$MODULE}">
     {/if}
     <input name="change_owner" type="hidden">
     <input name="change_status" type="hidden">
     {* crmr@188766 removed input action *}
     <input name="where_export" type="hidden" value="{to_html(VteSession::get('export_where'))}"> {* crmv@181170 *}
     <input name="step" type="hidden">
<!-- //crmv@9183  -->
     <input name="selected_ids" type="hidden" id="selected_ids" value="{$SELECTED_IDS}">
     <input name="all_ids" type="hidden" id="all_ids" value="{$ALL_IDS}">
     <input name="import_flag" type="hidden" id="import_flag" value="{$HIDE_CUSTOM_LINKS}">
<!-- //crmv@9183 e -->
	<input type="hidden" name="account" value="{$CURRENT_ACCOUNT}" />
	<input type="hidden" name="folder" value="{$CURRENT_FOLDER}" />
	<input type="hidden" name="thread" value="{$CURRENT_THREAD}" />
	<!-- List View Master Holder starts -->
	<table border=0 cellspacing=0 cellpadding=0 width=100% class="lvtBg">
	<tr>
	<td>
	<!-- List View's Buttons and Filters starts -->

            {*<!-- crmv@18592e -->*}
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
			
            <!-- List View's Buttons and Filters ends -->
            <div>
            
            <input type="checkbox" id="selectall" name="selectall" style="display:none;">
            
            <table cellspacing=0 cellpadding=2 width=100% class="messages-list" id="MessagesRowList">
            	<!-- Table Contents -->
            	{include file="modules/Messages/ListViewRows.tpl"}
             </table>
             </div>

            <table width=100%>
			<tr>
				<td align="center">
					<div id="nav_buttons" align="center">{$NAVIGATION}</div>
					<div id="nav_buttons_messages" align="center" style="display:none;">{$NAVIGATION}</div> {* crmv@103872 necessary! otherwise appendNextListViewEntries gets removed after ajax call *}
					<div id="indicatorAppend" align="center" style="padding:8px; display:none;">{include file="LoadingIndicator.tpl" LIOLDMODE=true}</div>
				</td>
			</tr>
			</table>
            <!-- List View's Buttons and Filters ends -->
<!--			//crmv@10759 e-->
	</td>
	</tr>
	</table>
</form>
{$SELECT_SCRIPT}
<div id="basicsearchcolumns" style="display:none;"><select name="search_field" id="bas_searchfield" class="txtBox" style="width:150px">{html_options  options=$SEARCHLISTHEADER}</select></div>
{if $HIDE_CUSTOM_LINKS eq 1}
</div>
{/if}
<script type="text/javascript">
{*<!-- crmv@18592 -->*}
function unselectAllIds()
{ldelim}
	var button_top = document.getElementById("select_all_button_top");
	var choose_id = document.getElementById("select_ids");
	button_top.value = "{$APP.LBL_SELECT_ALL_IDS}";
	choose_id.value = "";
	document.getElementById("all_ids").value = '';
	document.getElementById("selected_ids").value="";
	document.getElementById("selectall").checked=false;

	if (typeof(getObj("selected_id"))=="undefined")
	{ldelim}
		//do nothing
	{rdelim} else if (typeof(getObj("selected_id"))=="undefined" || typeof(getObj("selected_id").length)=="undefined") {ldelim}
		getObj("selected_id").checked=false;
	{rdelim} else {ldelim}
		for (var i=0;i<getObj("selected_id").length;i++){ldelim}
			getObj("selected_id")[i].checked=false;
		{rdelim}
	{rdelim}
{rdelim}

function selectAllIds()
{ldelim}
   var button_top = document.getElementById("select_all_button_top");
   var choose_id = document.getElementById("select_ids");

   if (button_top.value == "{$APP.LBL_SELECT_ALL_IDS}")
   {ldelim}

      button_top.value = "{$APP.LBL_UNSELECT_ALL_IDS}";
      //crmv@7216
      document.getElementById("all_ids").value = 1;
      document.getElementById("selected_ids").value = '';
	  //crmv@7216e
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
      button_top.value = "{$APP.LBL_SELECT_ALL_IDS}";
      choose_id.value = "";
      //crmv@7216
      document.getElementById("all_ids").value = '';
	  document.getElementById("selected_ids").value="";
	  //crmv@7216e
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
//crmv@10759	//crmv@16627
jQuery(document).ready(function(){ldelim}
	{if $smarty.request.search neq 'true' && $IMAP_NAVIGATION neq true}
		update_navigation_values(window.location.href+'&folder='+current_folder+'&account='+current_account,'{$MODULE}');
	{/if}
	jQuery("#status").hide();	// hide status img crmv@192033
{rdelim});
//crmv@10759e	//crmv@16627e
{*<!-- crmv@18592e -->*}
</script>