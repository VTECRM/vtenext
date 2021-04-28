{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* vtlib customization: Custom links on the Detail view *}
{* crmv@57221 crmv@60147 crmv@105588 *}

{if $CUSTOM_LINK_TYPE eq "DETAILVIEWBASIC"}
	{if $CUSTOM_LINKS && $CUSTOM_LINKS.DETAILVIEWBASIC}
		{foreach item=CUSTOMLINK from=$CUSTOM_LINKS.DETAILVIEWBASIC}
			{assign var="customlink_href" value=$CUSTOMLINK->linkurl}
			{assign var="customlink_label" value=$CUSTOMLINK->linklabel}
			{if $customlink_label eq ''}
				{assign var="customlink_label" value=$customlink_href}
			{else}
				{* Pickup the translated label provided by the module *}
				{assign var="customlink_label" value=$customlink_label|@getTranslatedString:$CUSTOMLINK->module()}
			{/if}
			
			{* crmv@114595 *}
			{assign var="LINKICON" value=$CUSTOMLINK->linkicon}
			{assign var="ICONPARTS" value=":"|explode:$LINKICON}
			<div class="turboliftEntry1 {if $LINKICON}turboliftEntryWithImage{/if} btn" onClick="releaseOverAll('detailViewActionsContainer'); {$customlink_href}">
				<div class="row no-gutter">
					<div class="col-sm-12">
						<div class="col-sm-6 vcenter text-left">
							<span style="padding:2px;">{$customlink_label}</span>
						</div>
						{if $LINKICON}
							<div class="col-sm-6 vcenter text-right">
								{if count($ICONPARTS) > 1}
									{* class + icon name *}
									<i class="{$ICONPARTS[0]} md-text">{$ICONPARTS[1]}</i>
								{else}
									{* normal image *}
									<img style="height:20px;padding:2px;vertical-align:middle;" border="0" src="{$LINKICON}" />
								{/if}
							</div>
						{/if}
					</div>
				</div>
			</div>
			{* crmv@114595e *}
		{/foreach}
	{/if}
{elseif $CUSTOM_LINK_TYPE eq "DETAILVIEW"}
	{if $CUSTOM_LINKS && $CUSTOM_LINKS.DETAILVIEW}
		{if !empty($CUSTOM_LINKS.DETAILVIEW)}
			<div class="turboliftEntry1" id="detailviewLinksTitle1" onClick="detailviewLinksToggle('detailviewLinksTitle1','vtlib_customLinksLay1');">
				{$APP.LBL_MORE}
				<div style="float:right;"><img border="0" src="{'inactivate.gif'|resourcever}" /></div>
			</div>
			<table border="0" cellpadding="0" cellspacing="0" width="100%" id="vtlib_customLinksLay1" style="display: none;padding-top:5px;">
				{foreach item=CUSTOMLINK from=$CUSTOM_LINKS.DETAILVIEW}
					{assign var="customlink_href" value=$CUSTOMLINK->linkurl}
					{assign var="customlink_label" value=$CUSTOMLINK->linklabel}
					{if $customlink_label eq ''}
						{assign var="customlink_label" value=$customlink_href}
					{else}
						{* Pickup the translated label provided by the module *}
						{assign var="customlink_label" value=$customlink_label|@getTranslatedString:$CUSTOMLINK->module()}
					{/if}
					<tr height="24" class="dvtUnSelectedCell" style="padding-left: 5px; padding-right: 0px; margin-bottom: 3px; display: block;">
						<td align="left" width="100%" height="24">
							<a href="{$customlink_href}" class="webMnu">{$customlink_label}</a>
						</td>
					</tr>
				{/foreach}
			</table>
		{/if}
	{/if}
{elseif $CUSTOM_LINK_TYPE eq "DETAILVIEWWIDGET"}
	<script type="text/javascript">var detailViewActionsContainer2length = 0;</script>
	{assign var="detailViewActionsContainer2length" value=0}
	{foreach key=CUSTOMLINK_NO item=CUSTOMLINK from=$CUSTOM_LINKS.DETAILVIEWWIDGET}
		{assign var="customlink_href" value=$CUSTOMLINK->linkurl}
		{assign var="customlink_label" value=$CUSTOMLINK->linklabel}
		{* Ignore block:// type custom links which are handled earlier *}
		{if !preg_match("/^block:\/\/.*/", $customlink_href)}
			<script type="text/javascript">var detailViewActionsContainer2length = detailViewActionsContainer2length+1;</script>
			{assign var="detailViewActionsContainer2length" value=$detailViewActionsContainer2length+1}
			{if $customlink_label eq ''}
				{assign var="customlink_label" value=$customlink_href}
			{else}
				{* Pickup the translated label provided by the module *}
				{assign var="customlink_label" value=$customlink_label|@getTranslatedString:$CUSTOMLINK->module()}
			{/if}
			<div class="turboliftEntry2" style="cursor:auto;">
				<div class="loadDetailViewWidget" onClick='vtlib_loadDetailViewWidget("{$customlink_href}", "detailview_block_{$CUSTOMLINK_NO}", "detailview_block_{$CUSTOMLINK_NO}_indicator", resizedetailViewActionsContainer);'>
	  				{$customlink_label}
	  				<div style="float:right;">
	  					{include file="LoadingIndicator.tpl" LIID="detailview_block_"|cat:$CUSTOMLINK_NO|cat:"_indicator" LIEXTRASTYLE="display:none;"}
	  				</div>
  				</div>
  				<div id="detailview_block_{$CUSTOMLINK_NO}" style="padding-top:5px;display:none;"></div>
			</div>
		{/if}
	{/foreach}
{/if}