{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<script language="JAVASCRIPT" type="text/javascript" src="include/js/smoothscroll.js"></script>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
<tbody>
<tr>
	<td valign="top"></td>
    <td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->

	<form action="index.php" method="post" id="form" onsubmit="VteJS_DialogBox.block();">
		<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
		<input type='hidden' name='module' value='Settings'>
		<input type='hidden' name='action' value='MailScanner'>
		<input type='hidden' name='mode' value='rulesave'>
		<input type='hidden' name='ruleid' value="{$SCANNERRULE->ruleid}">
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
					<td class=heading2 valign=bottom><b> {$MOD.LBL_SETTINGS} > {$MOD.LBL_MAIL_SCANNER}</b></td> <!-- crmv@30683 -->
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
				<td class="big" width="70%"><strong>{$MOD.LBL_MAIL_SCANNER} {$MOD.LBL_RULE} {$MOD.LBL_INFORMATION}</strong></td>
				</tr>
				</table>
				
				<table border=0 cellspacing=0 cellpadding=0 width=100% class="listRow">
				<tr>
	         	    <td class="small" valign=top ><table width="100%"  border="0" cellspacing="0" cellpadding="5">
						<tr>
                            <td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_SCANNER} {$MOD.LBL_NAME}</strong></td>
                            <td width="80%" colspan=2>{$SCANNERINFO.scannername}
								<input type="hidden" name="scannername" class="small" value="{$SCANNERINFO.scannername}" size=50 readonly></td>
                        </tr>
                        <tr>
                            <td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_FROM}</strong></td>
                            <td width="80%" colspan=2><input type="text" name="rule_from" class="small" value="{$SCANNERRULE->fromaddress}" size=50></td>
                        </tr>
                        <tr>
                            <td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_TO}</strong></td>
                            <td width="80%" colspan=2><input type="text" name="rule_to" class="small" value="{$SCANNERRULE->toaddress}" size=50></td>
                        </tr>
						<tr>
                            <td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_SUBJECT}</strong></td>
                            <td width="10%">
								<select name="rule_subjectop" class="small" onChange="setSubjectDefault(this.value)">	{* crmv@78745 *}
									<option value=''>-- {$MOD.LBL_SELECT} {$MOD.LBL_CONDITION} --</option>
									<option value='Contains'    {if $SCANNERRULE->subjectop eq 'Contains'}selected=true{/if}
									>{$MOD.LBL_CONTAINS}</option>
									<option value='Not Contains' {if $SCANNERRULE->subjectop eq 'Not Contains'}selected=true{/if}
									>{$MOD.LBL_NOT} {$MOD.LBL_CONTAINS}</option>
									<option value='Equals'      {if $SCANNERRULE->subjectop eq 'Equals'}selected=true{/if}
									>{$MOD.LBL_EQUALS}</option>
									<option value='Not Equals'  {if $SCANNERRULE->subjectop eq 'Not Equals'}selected=true{/if}
									>{$MOD.LBL_NOT} {$MOD.LBL_EQUALS}</option>
									<option value='Begins With' {if $SCANNERRULE->subjectop eq 'Begins With'}selected=true{/if}
									>{$MOD.LBL_BEGINS} {$MOD.LBL_WITH}</option>
									<option value='Ends With'   {if $SCANNERRULE->subjectop eq 'Ends With'}selected=true{/if}
									>{$MOD.LBL_ENDS} {$MOD.LBL_WITH}</option>
									<option value='Regex'       {if $SCANNERRULE->subjectop eq 'Regex'}selected=true{/if}
									>{$MOD.LBL_REGEX}</option> 
								</select>
							</td>
							<td width="70%">
								<input type="text" name="rule_subject" class="small" value="{$SCANNERRULE->subject}" size="65"/>
							</td>
                        </tr>
						<tr>
                            <td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_BODY}</strong></td>
                            <td width="10%">
								<select name="rule_bodyop" class="small">
									<option value=''>-- {$MOD.LBL_SELECT} {$MOD.LBL_CONDITION} --</option>
									<option value='Contains'    {if $SCANNERRULE->bodyop eq 'Contains'}selected=true{/if}
									>{$MOD.LBL_CONTAINS}</option>
									<option value='Not Contains' {if $SCANNERRULE->subjectop eq 'Not Contains'}selected=true{/if}
									>{$MOD.LBL_NOT} {$MOD.LBL_CONTAINS}</option>
									<option value='Equals'      {if $SCANNERRULE->bodyop eq 'Equals'}selected=true{/if}
									>{$MOD.LBL_EQUALS}</option>
									<option value='Not Equals'  {if $SCANNERRULE->bodyop eq 'Not Equals'}selected=true{/if}
									>{$MOD.LBL_NOT} {$MOD.LBL_EQUALS}</option>
									<option value='Begins With' {if $SCANNERRULE->bodyop eq 'Begins With'}selected=true{/if}
									>{$MOD.LBL_BEGINS} {$MOD.LBL_WITH}</option>
									<option value='Ends With'   {if $SCANNERRULE->bodyop eq 'Ends With'}selected=true{/if}
									>{$MOD.LBL_ENDS} {$MOD.LBL_WITH}</option>
									{* TODO: Provide Regex support *}
									{* <option value='Regex'       {if $SCANNERRULE->bodyop eq 'Regex'}selected=true{/if}
									>{$MOD.LBL_REGEX}</option> 
									 *}
								</select>
							</td>
							<td width="70%">
								<textarea name="rule_body" class="small">{$SCANNERRULE->body}</textarea> 
							</td>
                        </tr>
						<tr>
                            <td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_MATCH}</strong></td>
                            <td width="70%" colspan=2>
								{if $SCANNERRULE->matchusing eq 'OR'}
									{assign var="rule_match_or" value="checked='true'"}
									{assign var="rule_match_all" value=""}
								{else}
									{assign var="rule_match_or" value=""}
									{assign var="rule_match_all" value="checked='true'"}
								{/if}
								<input type="radio" class="small" name="rule_matchusing" id="rule_matchusing_and" value="AND" {$rule_match_all}> <label for="rule_matchusing_and">{$MOD.LBL_ALL} {$MOD.LBL_CONDITION}</label>	{* crmv@78745 *}
								<input type="radio" class="small" name="rule_matchusing" id="rule_matchusing_or" value="OR" {$rule_match_or}> <label for="rule_matchusing_or">{$MOD.LBL_ANY} {$MOD.LBL_CONDITION}</label>	{* crmv@78745 *}
							</td>
                        </tr>
						<tr valign="top">	{* crmv@78745 *}
                            <td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_ACTION}</strong></td>
                            <td width="70%" colspan=2>
								{assign var="RULEACTIONTEXT" value=""}
								{if $SCANNERRULE->useaction}
									{assign var="RULEACTIONTEXT" value=$SCANNERRULE->useaction->actiontext}
									<input type="hidden" class="small" name="actionid" value="{$SCANNERRULE->useaction->actionid}">
								{else}
									<input type="hidden" class="small" name="actionid" value="">
								{/if}

								<select name="rule_actiontext" class="small" onChange="ruleAction()">	{* crmv@78745 *}
									{* <option value="">-- None --</option> *}{* EMPTY ACTION NOT SUPPORTED *}
									<option value="CREATE,HelpDesk,FROM" {if $RULEACTIONTEXT eq 'CREATE,HelpDesk,FROM'}selected=true{/if}
									>{$MOD.LBL_CREATE} {$MOD.LBL_TICKET}</option>
									<option value="UPDATE,HelpDesk,SUBJECT" {if $RULEACTIONTEXT eq 'UPDATE,HelpDesk,SUBJECT'}selected=true{/if}
									>{$MOD.LBL_UPDATE} {$MOD.LBL_TICKET}</option>
									<option value="LINK,Contacts,FROM" {if $RULEACTIONTEXT eq 'LINK,Contacts,FROM'}selected=true{/if}
									>{$MOD.LBL_ADD} {$MOD.LBL_TO_SMALL} {$MOD.LBL_CONTACT} [{$MOD.LBL_FROM_CAPS}]</option>
									<option value="LINK,Contacts,TO" {if $RULEACTIONTEXT eq 'LINK,Contacts,TO'}selected=true{/if}
									>{$MOD.LBL_ADD} {$MOD.LBL_TO_SMALL} {$MOD.LBL_CONTACT} [{$MOD.LBL_TO_CAPS}]</option>
									<option value="LINK,Accounts,FROM" {if $RULEACTIONTEXT eq 'LINK,Accounts,FROM'}selected=true{/if}
									>{$MOD.LBL_ADD} {$MOD.LBL_TO_SMALL} {$MOD.LBL_ACCOUNT} [{$MOD.LBL_FROM_CAPS}]</option>
									<option value="LINK,Accounts,TO" {if $RULEACTIONTEXT eq 'LINK,Accounts,TO'}selected=true{/if}
									>{$MOD.LBL_ADD} {$MOD.LBL_TO_SMALL} {$MOD.LBL_ACCOUNT} [{$MOD.LBL_TO_CAPS}]</option>
									{* crmv@2043m *}
									<option value="LINK,Leads,FROM" {if $RULEACTIONTEXT eq 'LINK,Leads,FROM'}selected=true{/if}
									>{$MOD.LBL_ADD} {$MOD.LBL_TO_SMALL} {'SINGLE_Leads'|getTranslatedString:'Leads'} [{$MOD.LBL_FROM_CAPS}]</option>
									<option value="LINK,Leads,TO" {if $RULEACTIONTEXT eq 'LINK,Leads,TO'}selected=true{/if}
									>{$MOD.LBL_ADD} {$MOD.LBL_TO_SMALL} {'SINGLE_Leads'|getTranslatedString:'Leads'} [{$MOD.LBL_TO_CAPS}]</option>
									{* crmv@2043me *}
									<option value="DO_NOTHING" {if $SCANNERRULE->useaction->actiontype eq 'DO_NOTHING'}selected=true{/if}>{$MOD.LBL_DO_NOTHING}</option>	{* crmv@27618 *}
								</select>
							</td>
                        </tr>
                        {* crmv@78745 *}
                        <tr id="div_compare_parentid" style="display:none">
                        	<td width="20%"></td>
                        	<td width="10%">
                        		<label for="compare_parentid">{$MOD.LBL_FORCE_CHECK_RELATED_TO}</label>
                        	</td>
                        	<td width="70%">
                        		<input type="checkbox" class="small" name="compare_parentid" id="compare_parentid" {if $SCANNERRULE->compare_parentid eq 1}checked{/if}>
                        	</td>
                        </tr>
                        {* crmv@78745e *}
                        {* crmv@81643 *}
                        <tr id="div_match_field" style="display:none">
                        	<td width="20%"></td>
                        	<td width="10%">
                        		{'LBL_MAILCONV_MATCH_FIELD'|getTranslatedString:'Settings'}
                        	</td>
                        	<td width="70%">
                        		<select name="match_field" class="small">
									<option value="crmid" {if $SCANNERRULE->match_field eq 'crmid'}selected=true{/if}>{'LBL_MAILCONV_MATCH_FIELD_CRMID'|getTranslatedString:'Settings'}</option>
									<option value="external_code" {if $SCANNERRULE->match_field eq 'external_code'}selected=true{/if}>{'External Code'|getTranslatedString:'HelpDesk'}</option>
								</select>
                        	</td>
                        </tr>
                        {* crmv@81643e *}
				    </td>
            	</tr>
				<tr>
					<td colspan=3 nowrap align="center">
						<input type="submit" class="crmbutton small save" value="{$APP.LBL_SAVE_LABEL}" />
						<input type="button" class="crmbutton small cancel" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" 
							onclick="location.href='index.php?module=Settings&action=MailScanner&parenttab=Settings&mode=rule&scannername={$SCANNERINFO.scannername}'"/>
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
{* crmv@78745 *}
{literal}
<script type="text/javascript">
function setSubjectDefault(option) {
	if (option == 'Regex' && jQuery('[name="rule_subject"]').val() == '') {
		jQuery('[name="rule_subject"]').val('{/literal}{$DEFAULT_SUBJECT_REGEX}{literal}');
	}
	ruleAction();	//crmv@81643
}
function ruleAction() {
	if (jQuery('[name="rule_actiontext"]').val() == 'UPDATE,HelpDesk,SUBJECT') {
		jQuery('#div_compare_parentid').show();
	} else {
		jQuery('#div_compare_parentid').hide();
	}
	//crmv@81643
	if (jQuery('[name="rule_actiontext"]').val() == 'UPDATE,HelpDesk,SUBJECT' && jQuery('[name="rule_subjectop"]').val() == 'Regex') {
		jQuery('#div_match_field').show();
	} else {
		jQuery('#div_match_field').hide();
	}
	//crmv@81643e
}
ruleAction();
</script>
{/literal}
{* crmv@78745e *}