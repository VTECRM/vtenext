{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@187622 *}
<h4>{'LBL_SCHEDULE_SENDING'|getTranslatedString:'Emails'}</h4>
<table class="vtetable" id="scheduleSendingOpt">
	{foreach item="opt" from=$OPTIONS}
		<tr><td style="cursor:pointer;" onclick="ScheduleSending.schedule('{$opt.date} {$opt.hour}','{$MESSAGESID}')">{$opt.date_str} {'LBL_AT_HOUR'|getTranslatedString} {$opt.hour}</td></tr>
	{/foreach}
	<tr><td style="cursor:pointer;" onclick="ScheduleSending.showCustomOptions();">{'LBL_OTHER'|getTranslatedString:'Users'}</td></tr>
</table>{capture assign="FIELD1"}
{include file="EditViewUI.tpl" uitype=$DATE_HTML.0.0 fldname=$DATE_HTML.2.0 fldvalue=$DATE_HTML.3.0 secondvalue=$DATE_HTML.3.1 fldlabel=$DATE_HTML.1.0 DIVCLASS="dvtCellInfo" NOLABEL=true}
{/capture}
{assign var=FIELD1 value="\r"|str_replace:'':$FIELD1}
{assign var=FIELD1 value="\n"|str_replace:'':$FIELD1}
{assign var=FIELD1 value="\t"|str_replace:'':$FIELD1}
{capture assign="FIELD2"}
{include file="EditViewUI.tpl" uitype=$HOUR_HTML.0.0 fldname=$HOUR_HTML.2.0 fldvalue=$HOUR_HTML.3.0 fldlabel=$HOUR_HTML.1.0 DIVCLASS="dvtCellInfo" NOLABEL=true}
{/capture}
{assign var=FIELD2 value="\r"|str_replace:'':$FIELD2}
{assign var=FIELD2 value="\n"|str_replace:'':$FIELD2}
{assign var=FIELD2 value="\t"|str_replace:'':$FIELD2}
<table id="scheduleSendingCustomOpt" align="center" style="display:none">
	<tr><td><h5>{'LBL_CHOOSE_DATE_TIME'|getTranslatedString:'Emails'}</h5></td></tr>
	<tr><td>{$FIELD1}</td></tr>
	<tr><td>{$FIELD2}</td></tr>
</table>