{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@28295 crmv@36871 crmv@82419 *}
<link href="modules/SDK/src/Events/css/Events.css" rel="stylesheet" type="text/css" />

{* Mini calendar floating div *}
{assign var="FLOAT_TITLE" value=$APP.Events}
{assign var="FLOAT_WIDTH" value="520px"}
{capture assign="FLOAT_BUTTONS"}
&nbsp;{'LBL_EVENTS_FROM'|getTranslatedString:'Calendar'} <span id="Events_Range_Title_from"></span> {'LBL_EVENTS_TO'|getTranslatedString:'Calendar'} <span id="Events_Range_Title_to"></span>&nbsp;
<input type="button" value="{$APP.LBL_CREATE}" name="button" class="crmbutton small create" title="{$APP.LBL_CREATE}" onClick="hideFloatingDiv('events');NewQCreate('Events');">
{/capture}
{capture assign="FLOAT_CONTENT"}
<table border="0" cellpadding="5" cellspacing="0" style="width:100%">
	<tr>
		<td id="events_calendar_container" valign="top">
			<div id="events_calendar"></div>
		</td>
		<td valign="top" id="events_list" width="100%"></td>
	</tr>
</table>
{/capture}
{include file="FloatingDiv.tpl" FLOAT_ID="events"}

{* crmv@28295e crmv@36871e crmv@82419e *}