{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@115268 *} {* crmv@181170 *}
{if $CUSTOM_LINKS && !empty($CUSTOM_LINKS.$WIDGETTYPE)}
	<table border=0 cellspacing=0 cellpadding=5 width=100% id="DetailViewWidgets"> {* crmv@104566 *}
	{assign var="widgetcount" value=0}
	{assign var="widgettotal" value=0}
	{foreach name=detailviewwidget item=CUSTOM_LINK_DETAILVIEWWIDGET from=$CUSTOM_LINKS.$WIDGETTYPE}
		{if !$CUSTOM_LINK_DETAILVIEWWIDGET->validateDisplayWidget($ID)}
			{continue}
		{/if}
		{assign var="widgettotal" value=$widgettotal+1}
		{if $CUSTOM_LINK_DETAILVIEWWIDGET->size eq 2}
			<tr>
				<td colspan="2" id="detailviewwidget{$smarty.foreach.detailviewwidget.iteration}">
					{* crmv@168573 *}
					{if $CUSTOM_LINK_DETAILVIEWWIDGET->linklabel neq 'DetailViewMyNotesWidget'}
						{include file='DetailViewWidgetHeader.tpl'}
					{/if}
					{* crmv@168573e *}
					<div style="min-height:300px;overflow-y:auto;">
						{$CUSTOM_LINK_DETAILVIEWWIDGET->displayWidgetContent($ID)}
					</div>
				</td>
			</tr>
		{elseif $CUSTOM_LINK_DETAILVIEWWIDGET->size eq 1}
			{if $widgetcount eq 0}	
		   		<tr valign="top">
			{/if}
			{assign var="widgetcount" value=$widgetcount+1}
			<td width="50%" id="detailviewwidget{$smarty.foreach.detailviewwidget.iteration}">
				{* crmv@168573 *}
				{if $CUSTOM_LINK_DETAILVIEWWIDGET->linklabel neq 'DetailViewMyNotesWidget'}
					{include file='DetailViewWidgetHeader.tpl'}
				{/if}
				{* crmv@168573e *}
				<div style="min-height:300px;overflow-y:auto;"> 
					{$CUSTOM_LINK_DETAILVIEWWIDGET->displayWidgetContent($ID)}
				</div>
			</td>
			{if $widgetcount eq 2}
				</tr>
				{assign var="widgetcount" value=0}
			{/if}
		{/if}
	{/foreach}
	{if $widgettotal is not even}
		<td width="50%" id="detailviewwidget0"></td>
		</tr>
	{/if}
	</table>
{/if}