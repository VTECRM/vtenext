{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>{$BROWSERNAME} - {'customerportal'|getTranslatedString}</title>
<link rel="shortcut icon" href="{'favicon'|get_logo}">


<!-- Bootstrap Core CSS -->
<link href="css/bootstrap.min.css" rel="stylesheet">

<!-- CSS -->
{if $LOGINPAGE}
<link href="css/login.css" rel="stylesheet" type="text/css">
{else}
<link href="css/style.css" rel="stylesheet" type="text/css">
{/if}

<!-- Comments timeline CSS -->
<link href="css/timeline.css" rel="stylesheet">

<!-- Material Design -->
<link href="css/material_design/material.min.css" rel="stylesheet">
<link href="css/material_design/roboto.min.css" rel="stylesheet">
<link href="css/material_design/material-fullpalette.min.css" rel="stylesheet">
<link href="css/material_design/ripples.min.css" rel="stylesheet">
<link href="css/material_design/material-icon.css" rel="stylesheet">

<script src="js/jquery-1.11.0.js"></script>

<script src="js/bootstrap.min.js"></script> {* crmv@173271 *}

{* crmv@195833 - prototype removed *}
	
{* crmv@160733 *}
{if $JSLANGUAGE}
	<script language="javascript" type="text/javascript" src="js/language/{$JSLANGUAGE}.lang.js"></script>
{/if}
{* crmv@160733e *}

{* crmv@168297 *}
{if $GLOBAL_CSS}
	{foreach item="CSSFILE" from=$GLOBAL_CSS}
		<link href="{$CSSFILE}" rel="stylesheet">
	{/foreach}
{/if}
{if $GLOBAL_JS}
	{foreach item="JSFILE" from=$GLOBAL_JS}
		<script language="javascript" type="text/javascript" src="{$JSFILE}"></script>
	{/foreach}
{/if}
{if $MODULE_JS}
	{foreach item="JSFILE" from=$MODULE_JS}
		<script language="javascript" type="text/javascript" src="{$JSFILE}"></script>
	{/foreach}
{/if}
{* crmv@168297e *}

<script language="javascript" type="text/javascript" src="js/general.js"></script>

{* crmv@171581 - csrf protection *}
<script language="JavaScript" type="text/javascript" src="js/csrf.js"></script>
<script type="text/javascript">
	VTEPortal.CSRF.initialize('__csrf_token', '{$CSRF_TOKEN}');
</script>
{* crmv@171581e *}

</head>