{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@27020 crmv@38592 *}

{if !$SKIP_HTML_STRUCTURE}
	{assign var=HTML_EXTRA_CLASS value=$BODY_EXTRA_CLASS|default:''}
	{assign var=BODY_CLASS value="small"}
	{assign var=BODY_EXTRA_CLASS value=$BODY_EXTRA_CLASS|default:''}
	
	{if !empty($BODY_EXTRA_CLASS)}
		{assign var=BODY_CLASS value=$BODY_CLASS|cat:" `$BODY_EXTRA_CLASS`"}
	{/if}
	
	{if $HEAD_INCLUDE}
		{include file="HTMLHeader.tpl" head_include=$HEAD_INCLUDE HTML_EXTRA_CLASS=$HTML_EXTRA_CLASS}
	{else}
		{include file="HTMLHeader.tpl" head_include="icons,jquery,jquery_plugins,jquery_ui,fancybox,prototype,jscalendar" HTML_EXTRA_CLASS=$HTML_EXTRA_CLASS}
	{/if}
	
	<body class="{$BODY_CLASS} {if $HIDE_MENUS eq true}hide-menus{/if} {if $THEME_CONFIG['body_light']}body-light{/if}">
	
	{include file="Theme.tpl" THEME_MODE="body"}
{/if}

<div id="popupContainer" style="display:none;"></div>
{* crmv@21048m-e crmv@82419e *}

{* crmv@62447 *}
{if $PAGE_TITLE neq 'SKIP_TITLE'}
	{if $CAL_MODE eq 'on'}
		<script type="text/javascript" src="modules/Messages/Messages.js"></script>
		
		<table id="vte_menu_head" width="100%" cellspacing="0" cellpadding="0" border="0" style="position: fixed; z-index: {if $HEADER_Z_INDEX > 0}{$HEADER_Z_INDEX}{else}0{/if};{if $PAGE_TITLE eq ''}padding: 5px;{/if}"> {* crmv@42752 *}
			<tr>
				<td class="mailClientWriteEmailHeader level2Bg menuSeparation">
					<div class="header-breadcrumbs">
						<h4><a href="javascript:;" onclick="{$PAGE_TITLE_LINK}">{$PAGE_TITLE}</a></h4>
						{if !empty($PAGE_SUB_TITLE)}
							<h4>&gt; <a href="javascript:;" onclick="{$PAGE_SUB_TITLE_LINK}">{$PAGE_SUB_TITLE}</a></h4>
						{/if}
						<h5 style="margin-left:auto;">{$PAGE_RIGHT_TITLE}</h5>
					</div>
				</td>
				<td id="Button_List_Ical" class="mailClientWriteEmailHeader level2Bg menuSeparation" align="right"></td>
			</tr>
		</table>
		<div id="vte_menu_white"></div>
	{else}
		<table id="vte_menu" width="100%" cellspacing="0" cellpadding="0" border="0" style="position: fixed; z-index: {if $HEADER_Z_INDEX > 0}{$HEADER_Z_INDEX}{else}0{/if};{if $PAGE_TITLE eq ''}padding: 5px;{/if}"> {* crmv@42752 *}
			<tr>
				<td width="100%" class="mailClientWriteEmailHeader level2Bg menuSeparation">
					<div class="header-breadcrumbs">
						<h4><a href="javascript:;" onclick="{$PAGE_TITLE_LINK}">{$PAGE_TITLE}</a></h4>
						{if !empty($PAGE_SUB_TITLE)}
							<h4>&gt; <a href="javascript:;" onclick="{$PAGE_SUB_TITLE_LINK}">{$PAGE_SUB_TITLE}</a></h4>
						{/if}
						<h5 style="margin-left:auto;">{$PAGE_RIGHT_TITLE}</h5>
					</div>
				</td>
			</tr>
		</table>
		<div id="vte_menu_white"></div>
	{/if}
{/if}
{* crmv@62447e *}

<div id="Buttons_List" class="level3Bg {$BUTTON_LIST_CLASS}" style="position:fixed; width: 100%; {if $HEADER_Z_INDEX > 0}z-index:{$HEADER_Z_INDEX};{else}z-index:0;{/if};">{$BUTTON_LIST}</div>	{* crmv@92272 *}
<div id="Buttons_List_white"></div>

<script type="text/javascript">
	jQuery(window).ready(function(){ldelim}
		setTimeout(function(){ldelim}
			{if $PAGE_TITLE neq 'SKIP_TITLE'}
				{if $CAL_MODE eq 'on'}
		    		jQuery('#vte_menu_white').height(jQuery('#vte_menu_head').outerHeight());
		    	{else}
		    		jQuery('#vte_menu_white').height(jQuery('#vte_menu').outerHeight());
				{/if}
			{/if}
	    	jQuery('#Buttons_List_white').height(jQuery('#Buttons_List').outerHeight());
	    {rdelim},0);

		loadedPopup();
		bindButtons(window.top);	//crmv@59626
	{rdelim});
</script>