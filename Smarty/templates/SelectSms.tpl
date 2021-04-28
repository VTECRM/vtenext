{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{assign var="FLOAT_TITLE" value=$MOD.SELECT_SMS}
{if $ONE_RECORD neq 'true'}
	{assign var="FLOAT_TITLE" value="`$FLOAT_TITLE` (`$MOD.LBL_MULTIPLE` `$APP[$FROM_MODULE]`)"}
{/if}
{assign var="FLOAT_WIDTH" value="400px"}
{assign var="FLOAT_BUTTONS" value=""}
{capture assign="FLOAT_CONTENT"}
<table border=0 cellspacing=0 cellpadding=5 align=center style="width:{$FLOAT_WIDTH}">
	<tr>
		<td align="left">
		{if $ONE_RECORD eq 'true'}
			<b>{$ENTITY_NAME}</b> {$MOD.LBL_SMSSELECT_INFO}.<br><br>
		{else}
			{$MOD.LBL_SMSSELECT_INFO1} {$APP[$FROM_MODULE]}.{$MOD.LBL_SMSSELECT_INFO2}<br><br>
		{/if}
			<div style="height:120px;overflow-y:auto;overflow-x:hidden;" align="center">
				<table border="0" cellpadding="5" cellspacing="0" width="90%">
					{foreach name=smsids key=fieldid item=elements from=$SMSINFO}
					<tr>
						{if $smarty.foreach.smsids.iteration eq 1}	
						<td align="center"><input type="checkbox" checked value="{$fieldid}" name="sesms" /></td>
						{else}
						<td align="center"><input type="checkbox" value="{$fieldid}" name="sesms"  /></td>
						{/if}
						{if $PERMIT eq '0'}
						{if $ONE_RECORD eq 'true'}	
						<td align="left"><b>{$elements.0}</b><br>{$SMSDATA[$smarty.foreach.smsids.index]}</td>
						{else}
						<td align="left"><b>{$elements.0}</b></td>
						{/if}
						{else}
						<td align="left"><b>{$elements.0}</b><br>{$SMSDATA[$smarty.foreach.smsids.index]}</td>
						{/if}
					</tr>
					{/foreach}
				</table>
			</div>
		</td>	
	</tr>
	<tr>
		<td align=center class="small">
			<input type="button" name="{$APP.LBL_SELECT_BUTTON_LABEL}" value=" {$APP.LBL_SELECT_BUTTON_LABEL} " class="crmbutton small create" onClick="validate_sendsms('{$IDLIST}','{$FROM_MODULE}');"/>&nbsp;&nbsp;
			<input type="button" name="{$APP.LBL_CANCEL_BUTTON_LABEL}" value=" {$APP.LBL_CANCEL_BUTTON_LABEL} " class="crmbutton small cancel" onclick="hideFloatingDiv('roleLaySms');" />
		</td>
	</tr>
</table>
{/capture}
{include file="FloatingDiv.tpl" FLOAT_ID="roleLaySms"}