{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
 
{* crmv@3082m *}

<form name="SaveFolders" action="index.php">
	<input type="hidden" name="module" value="Messages">
	<input type="hidden" name="action" value="MessagesAjax">
	<input type="hidden" name="file" value="Settings/index">
	<input type="hidden" name="operation" value="SaveFolders">
	<input type="hidden" name="account" value="">
	
	<table border="0" cellpadding="0" cellspacing="0" width="100%" align="center">
		{foreach name=messagesettings key=special item=folder from=$SPECIAL_FOLDERS}
			{assign var=i value=$smarty.foreach.messagesettings.iteration}
			{if $i is odd}
		    	<tr valign="top">
		    {/if}
		    	<td width="50%">
		    		<div class="cpanel_div">
			    		<div class="listMessageFrom" height="20px" style="font-weight:normal;vertical-align:middle;text-align:center;">
			    			{* crmv@178019 crmv@192843 *}
			    			{if isset($FOLDER_IMGS.$special)}<i class="vteicon" style="vertical-align:middle;">{$FOLDER_IMGS.$special}</i>{/if}
			    			<span style="padding-left:10px;">{'LBL_Folder_'|cat:$special|getTranslatedString:'Messages'}</span>
							{* crmv@178019e crmv@192843e *}
			    		</div>
			    		<div style="padding-top:15px;" align="center">
			    			<select name="{$special}" class="detailedViewTextBox input-inline">
			    				<option value="">{'LBL_NONE'|getTranslatedString}</option>
			    				{foreach key=value item=info from=$FOLDER_LIST}
			    					<option value="{$value}" {if $value eq $folder}selected{/if}>{$value}</option>
			    				{/foreach}
			    			</select>
						</div>
			    	</div>
		    	</td>
			{if $i is even}
		    	</tr>
		    {/if}
	    {/foreach}
	</table>
</form>