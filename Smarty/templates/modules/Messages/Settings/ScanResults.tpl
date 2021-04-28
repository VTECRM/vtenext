{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
 
{* crmv@3082m *}

<table border="0" cellpadding="0" cellspacing="0" width="100%">
	{* crmv@63113 *}
	{if !empty($FOLDERS_NOT_FOUND)}
		<tr height="20" valign="middle">
			<td align="center" colspan="2">
				<br /><b>{'LBL_FILTER_FOLDERS_NOT_FOUND'|getTranslatedString:'Messages'}<br /></b>
				{'<br />'|implode:$FOLDERS_NOT_FOUND}
			</td>
		</tr>
	{/if}
	{* crmv@63113e *}
	{foreach name=messagesettings key=folder item=messages from=$FILTERED}
		{assign var=found value=true}
		{assign var=i value=$smarty.foreach.messagesettings.iteration}
		{if $i is odd}
	    	<tr valign="top">
	    {/if}
	    	<td width="50%">
	    		<div class="cpanel_div">
		    		<div class="listMessageFrom" height="20px" style="font-weight:normal;vertical-align:middle;text-align:center;">
		    			<span style="padding-left:10px;">{$folder}</span>
		    		</div>
		    		<div style="padding-top:15px;" align="center">
		    			{$messages|@count}
					</div>
		    	</div>
	    	</td>
		{if $i is even}
	    	</tr>
	    {/if}
	{foreachelse}
		{assign var=found value=false}
		<tr height="300" valign="middle"><td align="center" colspan="2">{'LBL_NO_RECORD'|getTranslatedString}</td></tr>
	{/foreach}
	<tr><td align="center" colspan="2"><a href="javascript:;" onClick="{$FILTER_LINK}">{'LBL_GO_BACK'|getTranslatedString:'Messages'}</a></td></tr>
</table>

{if $found eq true}
	<script type="text/javascript">
		jQuery(document).ready(function(){ldelim}
			parent.VteJS_DialogBox.block();
			parent.jQuery("#status").show(); // crmv@192033
			parent.getListViewEntries_js('Messages','start=1&folder='+parent.current_folder,false);
			parent.setmCustomScrollbar('#ListViewContents');
			parent.update_navigation_values(parent.location.href+'&folder='+parent.current_folder,'Messages',false);
			parent.reloadFolders();
			parent.VteJS_DialogBox.unblock();
			parent.jQuery("#status").hide(); // crmv@192033
		{rdelim});
	</script>
{/if}