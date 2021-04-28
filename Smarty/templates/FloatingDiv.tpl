{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@94525 *}

{* generate a random id if not provided *}
{if !$FLOAT_ID}
	{assign var="rand" value=10|rand:1000}
	{assign var="FLOAT_ID" value="floatingdiv_`$rand`"}
{/if}

{if !$FLOAT_WIDTH}
	{assign var="FLOAT_WIDTH" value="640px"}
{/if}

<div id="{$FLOAT_ID}" class="crmvDiv" style="display:none;position:fixed;{if $FLOAT_MAX_WIDTH}max-width:{$FLOAT_MAX_WIDTH};{/if} width:{$FLOAT_WIDTH};">
	{* header bar *}
	<table border="0" cellpadding="5" cellspacing="0" style="min-width:240px;width:100%">
		<tr style="cursor:move;" height="34">
			<td id="{$FLOAT_ID}_Handle" style="padding:5px" class="level3Bg">
				<table cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td width="80%" id="{$FLOAT_ID}_Handle_Title"><b>{$FLOAT_TITLE}</b></td>
					<td width="20%" align="right" nowrap>
						{* header loader *}
						{include file="LoadingIndicator.tpl" LIID="indicator$FLOAT_ID" LIEXTRASTYLE="display:none;"}
						{* header buttons *}
						{if $FLOAT_BUTTONS_FILE}
							{include file=$FLOAT_BUTTONS_FILE}
						{elseif $FLOAT_BUTTONS}
							{$FLOAT_BUTTONS}
						{/if}
					</td>
				</tr>
				</table>
			</td>
		</tr>
	</table>
	
	{* content *}
	<div id="{$FLOAT_ID}_div" style="padding: 4px;{if $FLOAT_HEIGHT > 0}height:{$FLOAT_HEIGHT};overflow-y:scroll;-webkit-overflow-scrolling:touch;{/if}">
	{if $FLOAT_CONTENT_FILE}
		{include file=$FLOAT_CONTENT_FILE}
	{elseif $FLOAT_CONTENT}
		{$FLOAT_CONTENT}
	{/if}
	</div>
	
	{* close button *}
	<div class="closebutton" onClick="hideFloatingDiv('{$FLOAT_ID}');"></div>
</div>

{* reset some variables *}
{assign var="FLOAT_TITLE" value=""}
{assign var="FLOAT_WIDTH" value=""}
{assign var="FLOAT_MAX_WIDTH" value=""}
{assign var="FLOAT_HEIGHT" value=""}