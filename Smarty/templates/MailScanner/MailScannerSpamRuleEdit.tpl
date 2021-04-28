{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@27618 *}
<form action="index.php" method="post" id="form" name="form" onsubmit="VteJS_DialogBox.block();">
	<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
	<input type='hidden' name='module' value='SDK'>
	<input type='hidden' name='action' value='SDKAjax'>
	<input type='hidden' name='file' value='src/modules/HelpDesk/MailScannerSpam'>
	<input type='hidden' name='mode' value='spamsave'>
	<input type='hidden' name='ruleid' value="{$SCANNERRULE->ruleid}">
	<input type='hidden' name='prev_action' value='{$PREV_ACTION}'>
	<input type='hidden' name='rule_actiontext' value='DO_NOTHING'>

	<div align=center>		
		<table border=0 cellspacing=0 cellpadding=0 width=100%>
		<tr>
             <td class="small" valign=top >
             <table width="100%"  border="0" cellspacing="0" cellpadding="5">
				<tr>
					<td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_SCANNER} {$MOD.LBL_NAME}</strong></td>
					<td width="80%" colspan=2>{$SCANNERINFO.scannername}
						<input type="hidden" name="scannername" class="small" value="{$SCANNERINFO.scannername}" size=50 readonly></td>
				</tr>
				<tr>
					<td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_FROM}</strong></td>
					<td width="80%" colspan=2><input type="text" name="rule_from" class="small" value="{$EMAIL_FROM}" size=50></td>
				</tr>
				<tr>
					<td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_TO}</strong></td>
					<td width="80%" colspan=2><input type="text" name="rule_to" class="small" value="" size=50></td>
				</tr>
				<tr>
					<td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_SUBJECT}</strong></td>
					<td width="10%">
						<select name="rule_subjectop" class="small">
							<option value=''>-- {$MOD.LBL_SELECT} {$MOD.LBL_CONDITION} --</option>
							<option value='Contains' selected=true>{$MOD.LBL_CONTAINS}</option>
							<option value='Not Contains'>{$MOD.LBL_NOT} {$MOD.LBL_CONTAINS}</option>
							<option value='Equals'>{$MOD.LBL_EQUALS}</option>
							<option value='Not Equals'>{$MOD.LBL_NOT} {$MOD.LBL_EQUALS}</option>
							<option value='Begins With'>{$MOD.LBL_BEGINS} {$MOD.LBL_WITH}</option>
							<option value='Ends With'>{$MOD.LBL_ENDS} {$MOD.LBL_WITH}</option>
							<option value='Regex'>{$MOD.LBL_REGEX}</option> 
						</select>
					</td>
					<td width="70%">
						<input type="text" name="rule_subject" class="small" value="{$TICKET_TITLE}" size="65"/>
					</td>
				</tr>
				<tr>
					<td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_BODY}</strong></td>
					<td width="10%">
						<select name="rule_bodyop" class="small">
							<option value=''>-- {$MOD.LBL_SELECT} {$MOD.LBL_CONDITION} --</option>
							<option value='Contains'>{$MOD.LBL_CONTAINS}</option>
							<option value='Not Contains'>{$MOD.LBL_NOT} {$MOD.LBL_CONTAINS}</option>
							<option value='Equals'>{$MOD.LBL_EQUALS}</option>
							<option value='Not Equals'>{$MOD.LBL_NOT} {$MOD.LBL_EQUALS}</option>
							<option value='Begins With'>{$MOD.LBL_BEGINS} {$MOD.LBL_WITH}</option>
							<option value='Ends With'>{$MOD.LBL_ENDS} {$MOD.LBL_WITH}</option>
							{* TODO: Provide Regex support *}
							{* <option value='Regex'>{$MOD.LBL_REGEX}</option> *}
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
						<input type="radio" class="small" name="rule_matchusing" value="AND" {$rule_match_all}> {$MOD.LBL_ALL} {$MOD.LBL_CONDITION}
						<input type="radio" class="small" name="rule_matchusing" value="OR" {$rule_match_or}> {$MOD.LBL_ANY} {$MOD.LBL_CONDITION}
					</td>
				</tr>
			</table>
		    </td>
            </tr>
		</table>	
	</div>
</form>
{* crmv@27618e *}