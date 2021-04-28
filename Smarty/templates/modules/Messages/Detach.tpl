{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@44775 crmv@94525 crmv@192614 *}
{include file="HTMLHeader.tpl" head_include="icons,jquery,jquery_plugins,jquery_ui,fancybox,prototype,sdk_headers"}

<body class="small">

{* crmv@82419 crmv@159110 used to insert some extra code in the body *}
{include file="Theme.tpl" THEME_MODE="body"}
{* crmv@82419e crmv@159110e *}

{* extra script *}
<script language="javascript" type="text/javascript" src="modules/{$MODULE}/{$MODULE}.js"></script>

{include file='CachedValues.tpl'}
{include file='modules/SDK/src/Reference/Autocomplete.tpl'}

<div id="popupContainer" style="display:none;"></div>
<script language="JavaScript" type="text/javascript">
	var messageMode = '{$MESSAGE_MODE}';
	var current_account = '{$CURRENT_ACCOUNT}';
	var current_folder = '{$CURRENT_FOLDER}';
	var list_status = 'view';
	var ajax_enable = true;
	var current_record = {$ID};			// current message selected
	var preview_current_record = '';	// when I display the preview of a linked record preview_current_record = current_record
	var preview_id = '';				// last preview displayed
</script>

{include file='modules/Messages/Move2Folder.tpl'}

<table cellpadding="0" cellspacing="0" border="0" class="level3Bg" width="100%" style="position:fixed;z-index:10;" id="menuButtonList">
	<tr>
		<td width="100%">
			<div class="closebutton" style="display: block; top:1px; left:0px;" onclick="window.close();"></div>
			<div style="float:right;padding-right:5px;" id="Button_List_Detail">
				{include file="modules/Messages/DetailViewButtons.tpl"}
			</div>
			<div id="status" style="float:right;padding:5px;display:none;">{include file="LoadingIndicator.tpl"}</div>
		</td>
	</tr>
</table>
<div id="vte_menu"></div>
<script language="javascript" type="text/javascript">
	jQuery('#vte_menu').height(jQuery('#menuButtonList').height());
</script>

<div id="DetailViewContents">
{include file="modules/Messages/DetailView.tpl"}
</div>

</body>
</html>