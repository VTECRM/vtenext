{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{if $smarty.request.ajax neq '' && $smarty.request.deleteuser eq ''}
&#&#&#&#&#&#
{/if}		
<form name="massdelete" method="POST" id="massdelete" onsubmit="VteJS_DialogBox.block();">
     <input name='search_url' id="search_url" type='hidden' value='{$SEARCH_URL}'>
     {if $HIDE_CUSTOM_LINKS eq 1}
      <input id="modulename" name="modulename" type="hidden" value="{$MODULE}">
     {/if}
     <input name="change_owner" type="hidden">
     <input name="change_status" type="hidden">
     <input name="action" type="hidden">
     <input name="where_export" type="hidden" value="{to_html(VteSession::get('export_where'))}"> {* crmv@181170 *}
     <input name="step" type="hidden">
<!-- //crmv@9183  -->     
     <input name="selected_ids" type="hidden" id="selected_ids" value="{$SELECTED_IDS}">
     <input name="all_ids" type="hidden" id="all_ids" value="{$ALL_IDS}">
<!-- //crmv@9183 e -->     	
<table border=0 cellspacing=0 cellpadding=0 width=100% class="listTableTopButtons">
<tr>
	<td id="rec_string" align="left" class="small" width="33%" nowrap>{$RECORD_COUNTS}</td>
	<!-- Page Navigation -->
	<td id="nav_buttons" align="center" style="padding:5px;" width="33%">{$NAVIGATION}</td>
    <td width="33%" class="small" align="right">&nbsp;</td>
</tr>
</table>
<table border=0 cellspacing=0 cellpadding=5 width=100% class="listTableTopButtons">
{if $ERROR_MSG neq ''}
<tr>
	{$ERROR_MSG}
</tr>
{/if}
</table>
						
<table border=0 cellspacing=0 cellpadding=5 width=100% class="listTable">
<tr>
	<td class="colHeader small" valign=top>#</td>
	<td class="colHeader small" valign=top>{$APP.Tools}</td>
	<td class="colHeader small" valign=top>{$LIST_HEADER.3}</td>
	<td class="colHeader small" valign=top>{$LIST_HEADER.5}</td>
	<td class="colHeader small" valign=top>{$LIST_HEADER.7}</td>
	<td class="colHeader small" valign=top>{$LIST_HEADER.6}</td>
	<td class="colHeader small" valign=top>{$LIST_HEADER.4}</td>
</tr>
	{foreach name=userlist item=listvalues key=userid from=$LIST_ENTRIES}
		{assign var=flag value=0}
<tr>
<td class="listTableRow small" valign=top>{$smarty.foreach.userlist.iteration}</td>
	<td class="listTableRow small" nowrap valign=top>
		<a href="index.php?action=EditView&amp;return_action=ListView&amp;return_module=Users&amp;module=Users&amp;parenttab=Settings&amp;record={$userid}">
			<i class="vteicon" title="{$APP.LBL_EDIT_BUTTON}">create</i>
		</a>
	{foreach item=name key=id from=$USERNODELETE}
		{if $userid eq $id || $userid eq $CURRENT_USERID}
			{assign var=flag value=1}
		{/if}
	{/foreach}
	{if $flag eq 0}
		<i class="vteicon md-link" onclick="deleteUser(this,'{$userid}')" title="{$APP.LBL_DELETE_BUTTON}">delete</i>
	{/if}
	<a href="index.php?action=EditView&amp;return_action=ListView&amp;return_module=Users&amp;module=Users&amp;parenttab=Settings&amp;record={$userid}&amp;isDuplicate=true">
		<i class="vteicon" title="{$APP.LBL_DUPLICATE_BUTTON}">content_copy</i>
	</a>
</td>
	<td class="listTableRow small" valign=top><b><a href="index.php?module=Users&action=DetailView&parenttab=Settings&record={$userid}"> {$listvalues.3} </a></b><br><a href="index.php?module=Users&action=DetailView&parenttab=Settings&record={$userid}"> {$listvalues.1} {$listvalues.0}</a> ({$listvalues.2})</td>
	<td class="listTableRow small" valign=top>{$listvalues.5}&nbsp;</td>
	<td class="listTableRow small" valign=top>{$listvalues.7}&nbsp;</td>
	<td class="listTableRow small" valign=top>{$listvalues.6}&nbsp;</td>
	{if $listvalues.4|@strip_tags|@trim eq 'Active'}
	<td class="listTableRow small active" valign=top>{$APP.Active}</td>
	{else}
	<td class="listTableRow small inactive" valign=top>{$APP.Inactive}</td>
	{/if}	

</tr>
	{foreachelse}
	<tr>
		<td colspan="7">
			<table width="100%">
				<tr>
					<td rowspan="2" width="45%" align="right"><img src="{'empty.jpg'|resourcever}" height="60" width="61"></td>
					<td nowrap="nowrap" width="65%" align="left">
						<span class="genHeaderSmall">
							{$APP.LBL_NO} {$MOD.LBL_USERS} {$APP.LBL_FOUND} !
						</span>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	{/foreach}
</table>
{include file="Settings/ScrollTop.tpl"}
</form>
<script>
	update_navigation_values(window.location.href,'Users');
</script>