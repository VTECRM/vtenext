{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
 
{* crmv@3082m *}

<form name="EditFilter" action="index.php">
	<input type="hidden" name="module" value="Messages">
	<input type="hidden" name="action" value="MessagesAjax">
	<input type="hidden" name="file" value="Settings/index">
	<input type="hidden" name="operation" value="SaveFilter">
	<input type="hidden" name="sequence" value="{$SEQUENCE}">
	<input type="hidden" name="account" value="{$ACCOUNT}">
	
	<table border="0" cellpadding="0" cellspacing="5" width="100%" align="center" style="padding-top:20px">
		<tr>
			<td align="right" width="40%" style="padding:5px">{'LBL_FILTER_WHERE'|getTranslatedString}</td>
			<td align="left" width="40%" style="padding:5px">
				<select name="filter_where" class="detailedViewTextBox">
					{foreach key=value item=label from=$WHERE_LIST}
						<option value="{$value}" {if $FILTER_WHERE eq $value}selected{/if}>{$label.label}</option>
					{/foreach}
				</select>
			</td>
			<td width="20%"></td>
		</tr>
		<tr>
			<td align="right" style="padding:5px">{'LBL_FILTER_WHAT'|getTranslatedString}</td>
			<td align="left" style="padding:5px"><input type="text" size="32" name="filter_what" value="{$FILTER_WHAT}" class="detailedViewTextBox"></td>
			<td></td>
		</tr>
		<tr>
			<td align="right" style="padding:5px">{'LBL_FILTER_FOLDER'|getTranslatedString}</td>
			<td align="left" style="padding:5px">
				<select name="filter_folder" class="detailedViewTextBox">
	    			{foreach key=value item=info from=$FOLDER_LIST}
	    				<option value="{$value}" {if $FILTER_FOLDER eq $value}selected{/if}>{$value}</option>
	    			{/foreach}
	    		</select>
			</td>
			<td></td>
		</tr>
	</table>
</form>