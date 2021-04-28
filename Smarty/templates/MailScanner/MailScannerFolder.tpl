{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@56233 *}

<script language="JAVASCRIPT" type="text/javascript" src="include/js/smoothscroll.js"></script>
<script type="text/javascript">
{literal}
function vtmailscanner_folders_resetAll_To(checktype) {
	var form = document.getElementById('form'); // crmv@192033
	var inputs = form.getElementsByTagName('input');
	for(var index = 0; index < inputs.length; ++index) {
		var input = inputs[index];
		if(input.type == 'checkbox' && input.name.indexOf('folder_') == 0) {
			input.checked = checktype;
		}
	}
}
{/literal}
</script>


<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
<tbody>
<tr>
	<td valign="top"></td>
    <td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->

	<form action="index.php" method="post" id="form" onsubmit="VteJS_DialogBox.block();">
		<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
		<input type='hidden' name='module' value='Settings'>
		<input type='hidden' name='action' value='MailScanner'>
		<input type='hidden' name='scannername' value="{$SCANNERINFO.scannername}">
		<input type='hidden' name='mode' value='foldersave'>
		<input type='hidden' name='return_action' value='MailScanner'>
		<input type='hidden' name='return_module' value='Settings'>
		<input type='hidden' name='parenttab' value='Settings'>
		<div align=center>
			{include file='SetMenu.tpl'}
			{include file='Buttons_List.tpl'} {* crmv@30683 *}
				<!-- DISPLAY -->
				<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
				<tr>
					<td width=50 rowspan=2 valign=top><img src="{'mailScanner.gif'|resourcever}" alt="{$MOD.LBL_MAIL_SCANNER}" width="48" height="48" border=0 title="{$MOD.LBL_MAIL_SCANNER}"></td>
					<td class=heading2 valign=bottom><b><a {$MOD.LBL_SETTINGS} > {$MOD.LBL_MAIL_SCANNER}</b></td> <!-- crmv@30683 -->
				</tr>
				<tr>
					<td valign=top class="small">{$MOD.LBL_MAIL_SCANNER_DESCRIPTION}</td>
				</tr>
				</table>
				
				<br>
				<table border=0 cellspacing=0 cellpadding=10 width=100% >
				<tr>
				<td>
				
				<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
				<tr>
					<td class="dvtCellLabel" width="50%">{$MOD.LBL_SELECT} {$MOD.LBL_FOLDERS}</td>
					<td align="right">
						<input type="submit" class="crmbutton small create" onclick="this.form.mode.value='folderupdate'" value="{$MOD.LBL_UPDATE}"> 
						<a href='javascript:void(0);' onclick="vtmailscanner_folders_resetAll_To(true);">{$MOD.LBL_SELECT} {$MOD.LBL_ALL}</a> |
						<a href='javascript:void(0);' onclick="vtmailscanner_folders_resetAll_To(false);">{$MOD.LBL_UNSELECT} {$MOD.LBL_ALL}</a>
					</td>
				</tr>
				</table>

				{assign var="FOLDER_COL_LIMIT" value="4"}				
				{assign var="FOLDER_COL_INDEX" value="0"}				
				{assign var="FOLDER_ROW_OPEN" value="false"}

				<table border=0 cellspacing=0 cellpadding=5 width=100%>
				<tr valign=top>
	         	    <td class="small" valign="top">
	         	    	<table width="100%" border="0" cellspacing="0" cellpadding="5">
						{foreach item=FOLDER key=FOLDERNAME from=$FOLDERINFO}
						{if ($FOLDER_COL_INDEX % $FOLDER_COL_LIMIT) eq 0}
						<tr>
						{assign var="FOLDER_ROW_OPEN" value="true"}
						{/if}
							<td>
								<input type="checkbox" id="check_folder_{$FOLDER.folderid}" name="folder_{$FOLDER.folderid}" value="{$FOLDERNAME}" {if $FOLDER.enabled}checked="true"{/if}>
								<label for="check_folder_{$FOLDER.folderid}" title='Lastscan: {$FOLDER.lastscan}'>{$FOLDERNAME}</label>
							</td>
						{if ($FOLDER_COL_INDEX % $FOLDER_COL_LIMIT) eq ($FOLDER_COL_LIMIT-1)}
						</tr>
						{assign var="FOLDER_ROW_OPEN" value="false"}
						{/if}
						{assign var="FOLDER_COL_INDEX" value=$FOLDER_COL_INDEX+1}
						{/foreach}
						{if $FOLDER_ROW_OPEN}</tr>{/if}
						</table>
					</td>
				</tr>
				<tr>
					<td colspan="{$FOLDER_COL_LIMIT}" nowrap align="center" class="listRow"></td>
				</tr>
				<tr>
					<td colspan="{$FOLDER_COL_LIMIT}" class="listRow">
						<table width="25%" border="0" cellspacing="0" cellpadding="10">
						<tr><td>
							<div class="dvtCellLabel">{$MOD.LBL_SELECT_SPAM_FOLDER}</div><br />
							<div class="dvtCellInfo">
								<select name="spam_folder" class="detailedViewTextBox">
									<option value="">{$APP.LBL_NONE}</option>
									{foreach item=FOLDER key=FOLDERNAME from=$FOLDERINFO}
										<option value="{$FOLDER.folderid}" {if $FOLDER.spam eq 1}selected{/if}>{$FOLDERNAME}</option>
									{/foreach}
								</select>
							</div>
						</td></tr></table>
					</td>
				</tr>
				<tr height="50">
					<td colspan="{$FOLDER_COL_LIMIT}" nowrap align="center">
						<input type="submit" class="crmbutton small save" value="{$APP.LBL_SAVE_LABEL}" />
						<input type="button" class="crmbutton small cancel" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" 
							onclick="location.href='index.php?module=Settings&action=MailScanner&parenttab=Settings&scannername={$SCANNERINFO.scannername}'"/>
					</td>
				</tr>
				</table>	
				
				</td>
				</tr>
				</table>
			
			</td>
			</tr>
			</table>
		</td>
	</tr>
	</table>
		
	</div>

</td>
        <td valign="top"></td>
   </tr>
</tbody>
</form>
</table>

</tr>
</table>

</tr>
</table>