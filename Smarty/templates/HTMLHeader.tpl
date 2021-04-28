{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@94525 crmv@94125 *}
{* Use this variable to choose what to include in the html header *}
{if $head_include eq 'all'}
	{assign var=INCLUDES value="all"}
{else}
	{assign var=INCLUDES value=","|explode:$head_include}
{/if}
{* if the called file is not in the VTE root, you should set this variable to correctly include the resources *}
{if $RELPATH eq ""}
	{if $PATH}
		{assign var="RELPATH" value=$PATH}
	{else}
		{assign var="RELPATH" value=""}
	{/if}
{/if}
{assign var=HTML_CLASS value="vte-app-root"}
{assign var=HTML_EXTRA_CLASS value=$HTML_EXTRA_CLASS|default:''}
{if !empty($HTML_EXTRA_CLASS)}
	{assign var=HTML_CLASS value=$HTML_CLASS|cat:" `$HTML_EXTRA_CLASS`"}
{/if}
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html class="{$HTML_CLASS}">
<head>

	{* Meta tags *}
	{if $APP.LBL_CHARSET}
	<meta http-equiv="Content-Type" content="text/html; charset={$APP.LBL_CHARSET}">
	{/if}
	
	{* crmv@25620 - Browser Title *}
	{if $BROWSER_TITLE eq ''}
		<title>{if $MODULE_NAME}{$MODULE_NAME|getTranslatedString:$MODULE_NAME} - {/if}{$APP.LBL_BROWSER_TITLE}</title>
		<script type="text/javascript">
			var browser_title = '{$APP.LBL_BROWSER_TITLE}';
		</script>
	{else}
		<title>{$BROWSER_TITLE}</title>
		<script type="text/javascript">
			var browser_title = '{$BROWSER_TITLE}';
		</script>
	{/if}
	{* crmv@25620e *}
	
	{* Base/compatibility scripts *}
	<script language="JavaScript" type="text/javascript" src="{$RELPATH}include/js/json2.js"></script>
	
	{* jQuery *}
	{if $INCLUDES == 'all' || in_array('jquery', $INCLUDES)}
	<script language="JavaScript" type="text/javascript" src="{$RELPATH}{"include/js/jquery.js"|resourcever}"></script>
	
		{* jQuery plugins *}
		{if $INCLUDES == 'all' || in_array('jquery_plugins', $INCLUDES)}
			<script language="JavaScript" type="text/javascript" src="{$RELPATH}{"include/js/jquery_plugins/dimensions.min.js"|resourcever}"></script>
			<link rel="stylesheet" href="{$RELPATH}include/js/jquery_plugins/css/scrollableFixedHeaderTable_style.css">
			<script language="JavaScript" type="text/javascript" src="{$RELPATH}include/js/jquery_plugins/scrollableFixedHeaderTable.js"></script>
			<script language="JavaScript" type="text/javascript" src="{$RELPATH}include/js/jquery_plugins/form.js"></script>
			<script language="JavaScript" type="text/javascript" src="{$RELPATH}include/js/jquery_plugins/jquery.debounce.min.js"></script>
			<script language="JavaScript" type="text/javascript" src="{$RELPATH}include/js/jquery_plugins/jquery.vtenext.js"></script> {* crmv@157124 *}
			<!-- script language="JavaScript" type="text/javascript" src="{$RELPATH}include/js/jquery_plugins/timers.js"></script -->
		{/if}
		
		{* Fancybox *}
		{if $INCLUDES == 'all' || in_array('fancybox', $INCLUDES)}
			<script type="text/javascript" src="{$RELPATH}include/js/jquery_plugins/fancybox/jquery.mousewheel-3.0.6.pack.js"></script>
			<script type="text/javascript" src="{$RELPATH}include/js/jquery_plugins/fancybox/jquery.fancybox.pack.js"></script>
			<link rel="stylesheet" type="text/css" href="{$RELPATH}include/js/jquery_plugins/fancybox/jquery.fancybox.css" media="screen" />
		{/if}
		
		{* jQuery UI *}
		{if $INCLUDES == 'all' || in_array('jquery_ui', $INCLUDES)}
			<link rel="stylesheet" href="{$RELPATH}include/js/jquery_plugins/ui/jquery-ui.min.css">
			{* jquery ui theme is added later in the theme *}
			<script type="text/javascript" src="{$RELPATH}include/js/jquery_plugins/ui/jquery-ui.min.js"></script>
			<script type="text/javascript" src="{$RELPATH}include/js/jquery_plugins/vte-ui.js"></script> {* crmv@198024 *}
			<script type="text/javascript">
				// fix for some collision between bootstrap and jQuery UI
				jQuery.widget.bridge('uibutton', jQuery.ui.button);
				jQuery.widget.bridge('uitooltip', jQuery.ui.tooltip);
			</script>
		{/if}
		
		{* crmv@140887 *}
		{* Slim Scroll *}
		<link rel="stylesheet" type="text/css" href="{$RELPATH}include/js/jquery_plugins/mCustomScrollbar/jquery.mCustomScrollbar.css">
		<link rel="stylesheet" type="text/css" href="{$RELPATH}include/js/jquery_plugins/mCustomScrollbar/VTE.mCustomScrollbar.css">
		<script language="JavaScript" type="text/javascript" src="{$RELPATH}include/js/jquery_plugins/mCustomScrollbar/jquery.mCustomScrollbar.concat.min.js"></script>
		<script language="JavaScript" type="text/javascript" src="{$RELPATH}include/js/jquery_plugins/slimscroll/jquery.slimscroll.min.js"></script>
		{* crmv@140887e *}
	{/if}
	
	{* Prototype and scriptaculous *}
	{if $INCLUDES == 'all' || in_array('prototype', $INCLUDES)}
		{* Prototype removed. If you need it, restore the link here *}
		{* <script language="javascript" type="text/javascript" src="{$RELPATH}include/scriptaculous/prototype.js"></script> *}
		{* This is a small polyfill for most used use cases of prototype, it will be removed in the future *}
		<script language="javascript" type="text/javascript" src="{$RELPATH}include/scriptaculous/protofill.js"></script>{* crmv@192033 *}
		{* crmv@168103 - removed scriptaculous *}
	{/if}
	
	{* Theme *}
	{include file="Theme.tpl" THEME_MODE="head"}
	
	{* Language *}
	{if $CURRENT_LANGUAGE}
		<script language="JavaScript" type="text/javascript" src="{$RELPATH}include/js/{$CURRENT_LANGUAGE}.lang.js"></script>
	{else}
		<script language="JavaScript" type="text/javascript" src="{$RELPATH}include/js/{$AUTHENTICATED_USER_LANGUAGE}.lang.js"></script> {* crmv@181170 *}
	{/if}

	{* VTE scripts *}
	<script language="JavaScript" type="text/javascript" src="{$RELPATH}{"include/js/vtlib.js"|resourcever}"></script>
	<script language="JavaScript" type="text/javascript" src="{$RELPATH}{"include/js/general.js"|resourcever}"></script>
	<script language="JavaScript" type="text/javascript" src="{$RELPATH}{"include/js/csrf.js"|resourcever}"></script> {* crmv@171581 *}
	<script language="JavaScript" type="text/javascript" src="{$RELPATH}{"include/js/session.js"|resourcever}"></script> {* crmv@91082 *}
	<script language="JavaScript" type="text/javascript" src="{$RELPATH}{"include/js/QuickCreate.js"|resourcever}"></script>
	<script language="JavaScript" type="text/javascript" src="{$RELPATH}{"include/js/menu.js"|resourcever}"></script>
	{* crmv@208475 *}
	<script language="JavaScript" type="text/javascript" src="{$RELPATH}{"modules/Calendar/script.js"|resourcever}"></script>
	
	<script language="JavaScript" type="text/javascript" src="{$RELPATH}{"include/js/notificationPopup.js"|resourcever}"></script>
	<script language="JavaScript" type="text/javascript" src="{$RELPATH}{"modules/Popup/Popup.js"|resourcever}"></script> {* crmv@43864 *}
	<script language="JavaScript" type="text/javascript" src="{$RELPATH}{"modules/Area/Area.js"|resourcever}"></script>
	<script language="JavaScript" type="text/javascript" src="{$RELPATH}{"include/js/Color.js"|resourcever}"></script> {* crmv@98866 *}
	<script language="JavaScript" type="text/javascript" src="{$RELPATH}{"include/js/Blockage.js"|resourcever}"></script> {* crmv@140887 *}
	<script language="JavaScript" type="text/javascript" src="{$RELPATH}{"include/js/DropArea.js"|resourcever}"></script> {* crmv@167019 *}
	
	{* Charts *}
	{if $INCLUDES == 'all' || in_array('charts', $INCLUDES)}
		<script language="JavaScript" type="text/javascript" src="{$RELPATH}include/chartjs/Chart.min.js"></script> {* crmv@82770 *}
		<script language="JavaScript" type="text/javascript" src="{$RELPATH}include/chartjs/Chart.HorizontalBar.min.js"></script> {* crmv@82770 *}
		<script language="JavaScript" type="text/javascript" src="{$RELPATH}include/chartjs/Chart.Scatter.min.js"></script> {* crmv@82770 *}
		<script language="JavaScript" type="text/javascript" src="{$RELPATH}{"include/chartjs/VTEChart.js"|resourcever}"></script> {* crmv@82770 *}
	{/if}
	
	{* JSCalendar - Obsolete! *}
	{if $INCLUDES == 'all' || in_array('jscalendar', $INCLUDES)}
		<link rel="stylesheet" type="text/css" media="all" href="{$RELPATH}include/js/jscalendar/calendar-win2k-cold-1.css">
		<script type="text/javascript" src="{$RELPATH}include/js/jscalendar/calendar.js"></script>
		<script type="text/javascript" src="{$RELPATH}include/js/jscalendar/calendar-setup.js"></script>
		{if $APP.LBL_JSCALENDAR_LANG}
		<script type="text/javascript" src="{$RELPATH}include/js/jscalendar/lang/calendar-{$APP.LBL_JSCALENDAR_LANG}.js"></script>
		{/if}
	{/if}
	{* crmv@82419e *}
	
	{* File uploads *}
	{if $INCLUDES == 'all' || in_array('file_upload', $INCLUDES)}
		<link rel="stylesheet" href="{$RELPATH}modules/Emails/plupload/jquery.plupload.queue/css/jquery.plupload.queue.css" type="text/css" media="screen" />
		<script type="text/javascript" src="{$RELPATH}modules/Emails/plupload/plupload.js"></script>
		<script type="text/javascript" src="{$RELPATH}modules/Emails/plupload/plupload.gears.js"></script>
		<script type="text/javascript" src="{$RELPATH}modules/Emails/plupload/plupload.silverlight.js"></script>
		<script type="text/javascript" src="{$RELPATH}modules/Emails/plupload/plupload.flash.js"></script>
		<script type="text/javascript" src="{$RELPATH}modules/Emails/plupload/plupload.browserplus.js"></script>
		<script type="text/javascript" src="{$RELPATH}modules/Emails/plupload/plupload.html4.js"></script>
		<script type="text/javascript" src="{$RELPATH}modules/Emails/plupload/plupload.html5.js"></script>
		<script type="text/javascript" src="{$RELPATH}modules/Emails/plupload/jquery.plupload.queue/jquery.plupload.queue.js"></script>
		<script type="text/javascript" src="{$RELPATH}modules/Emails/plupload/i18n/{$SHORT_LANGUAGE}.js"></script>	{* crmv@24568 *} {* crmv@181170 *}
	{/if}
	
	<script language="javascript" type="text/javascript" src="{$RELPATH}include/js/deprecate.js"></script> {* crmv@168103 *}
	
	{* crmv@42024 populate global JS variables *}
	<script type="text/javascript">setGlobalVars('{$JS_GLOBAL_VARS|replace:"'":"\'"}');</script> {* crmv@70731 *}
	{* crmv@42024e *}
	
	{* crmv@171581 - csrf protection *}
	<script type="text/javascript">
		VTE.CSRF.initialize('__csrf_token', '{$CSRF_TOKEN}');
	</script>
	{* crmv@171581e *}
	
	{* Asterisk Integration *}
	{* crmv@169305 *}
	{if $USE_ASTERISK eq 'true'}
		<script type="text/javascript">
			if (typeof(use_asterisk) == 'undefined') use_asterisk = true;
		</script>
	{/if}
	{if $USE_ASTERISK_INCOMING eq 'true'}
		<script type="text/javascript" src="{$RELPATH}{"include/js/asterisk.js"|resourcever}"></script>
	{/if}
	{* crmv@169305e *}
	
	{if $INCLUDES == 'all' || in_array('sdk_headers', $INCLUDES)}
	
		{* Inclusion of custom CSS *}
		{if $HEADERCSS}
			{foreach item=HDRCSS from=$HEADERCSS}
				<link rel="stylesheet" type="text/css" href="{$RELPATH}{$HDRCSS->linkurl}">
			{/foreach}
		{/if}
		
		{* Inclusion of custom javascript *}
		{if $HEADERSCRIPTS}
			{foreach item=HEADERSCRIPT from=$HEADERSCRIPTS}
				<script type="text/javascript" src="{$RELPATH}{$HEADERSCRIPT->linkurl}"></script>
			{/foreach}
		{/if}
	{/if}
	
	{* crmv@181170 *}
	{if $FAST_MODE}
		<base target="_parent">
	{/if}
	{* crmv@181170e *}
</head>