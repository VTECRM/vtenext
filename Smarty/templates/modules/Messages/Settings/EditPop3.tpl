{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
 
{* crmv@3082m *}

<form name="EditPop3" action="index.php">
	<input type="hidden" name="module" value="Messages">
	<input type="hidden" name="action" value="MessagesAjax">
	<input type="hidden" name="file" value="Settings/index">
	<input type="hidden" name="operation" value="SavePop3">
	<input type="hidden" name="id" value="{$ID}">
	
	<table border="0" cellpadding="0" cellspacing="5" width="100%" align="center" style="padding-top:20px">
		<tr>
			<td align="right" width="40%" style="padding:5px">{'LBL_OUTGOING_MAIL_SERVER'|getTranslatedString:'Settings'}</td>
			<td align="left" width="40%" style="padding:5px"><input type="text" size="32" name="server" value="{$SERVER}" class="detailedViewTextBox"></td>
			<td width="20%"></td>
		</tr>
		<tr>
			<td align="right" style="padding:5px">{'LBL_OUTGOING_MAIL_PORT'|getTranslatedString:'Settings'}</td>
			<td align="left" style="padding:5px"><input type="text" size="32" name="port" value="{$PORT}" class="detailedViewTextBox"></td>
			<td></td>
		</tr>
		<tr>
			<td align="right" style="padding:5px">{'LBL_USERNAME'|getTranslatedString:'Settings'}</td>
			<td align="left" style="padding:5px"><input type="text" size="32" name="username" value="{$USERNAME}" class="detailedViewTextBox"></td>
			<td></td>
		</tr>
		<tr>
			<td align="right" style="padding:5px">{'LBL_PASWRD'|getTranslatedString:'Settings'}</td>
			<td align="left" style="padding:5px">
				{* crmv@43764 *}
				<input type="password" value="{if !empty($PASSWORD)}********{/if}" class="detailedViewTextBox" onFocus="this.value='';" onChange="document.getElementById('password').value=this.value;" />
				<input type="hidden" id="password" name="password" value="">
				{* crmv@43764e *}
			</td>
			<td></td>
		</tr>
		<tr>
			<td align="right" style="padding:5px">SSL/TLS</td>
			<td align="left" style="padding:5px">
				<select name="secure" class="detailedViewTextBox">
	    			<option value="" {if $SECURE eq ''}selected{/if}>--Nessuno--</option>
	    			<option value="ssl" {if $SECURE eq 'ssl'}selected{/if}>SSL</option>
	    			<option value="tls" {if $SECURE eq 'tls'}selected{/if}>TLS</option>
	    		</select>
			</td>
			<td></td>
		</tr>
		<tr>
			<td align="right" style="padding:5px">{'LBL_FETCH'|getTranslatedString:'Messages'} {'LBL_POP3_FETCH_IN'|getTranslatedString:'Messages'}</td>
			<td align="left" style="padding:5px">
				{$ACCOUNTS}
				<span id="folderpicklistcontainer">
					<select name="folder" class="detailedViewTextBox">
		    			{foreach key=value item=info from=$FOLDER_LIST}
		    				<option value="{$value}" {if $FOLDER eq $value}selected{/if}>{$value}</option>
		    			{/foreach}
		    		</select>
		    	</span>
			</td>
			<td></td>
		</tr>
		<tr>
			<td align="right" style="padding:5px"><label for="lmos">{'LBL_LEAVES_FETCH_IN'|getTranslatedString:'Messages'}</label></td>
			<td align="left" style="padding:5px"><input type="checkbox" id="lmos" name="lmos" {if $LMOS eq '1'}checked{/if}></td>
			<td></td>
		</tr>
		<tr>
			<td align="right" style="padding:5px"><label for="active">{'LBL_ACTIVE'|getTranslatedString:'Settings'}</label></td>
			<td align="left" style="padding:5px"><input type="checkbox" id="active" name="active" {if $ACTIVE eq '1'}checked{/if}></td>
			<td></td>
		</tr>
	</table>
</form>