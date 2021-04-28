{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@42752 crmv@43050 crmv@43864 crmv@43942 crmv@54707 *}

{include file="SmallHeader.tpl" BODY_EXTRA_CLASS="popup-area-settings"}
{include file="modules/SDK/src/Reference/Autocomplete.tpl"}

<link href="include/js/jquery_plugins/mCustomScrollbar/jquery.mCustomScrollbar.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="include/js/jquery_plugins/mCustomScrollbar/jquery.mCustomScrollbar.concat.min.js"></script>
<script type="text/javascript" src="include/js/jquery_plugins/slimscroll/jquery.slimscroll.min.js"></script>
<link href="include/js/jquery_plugins/mCustomScrollbar/VTE.mCustomScrollbar.css" rel="stylesheet" type="text/css" />

<script type="text/javascript" src="{"include/js/ListView.js"|resourcever}"></script>
<script type="text/javascript" src="{"modules/Area/Area.js"|resourcever}"></script>
<script type="text/javascript" src="{"include/js/dtlviewajax.js"|resourcever}"></script>
<script type="text/javascript" src="{"modules/SDK/src/Notifications/NotificationsCommon.js"|resourcever}"></script>
<script type="text/javascript" src="include/js/{$AUTHENTICATED_USER_LANGUAGE}.lang.js"></script> {* crmv@181170 *}
<script type="text/javascript" src="{"modules/Popup/Popup.js"|resourcever}"></script>

{if count($EXTRA_JS) > 0}
	{foreach item=JSPATH from=$EXTRA_JS}
	<script type="text/javascript" src="{$JSPATH}"></script>
	{/foreach}
{/if}

<form id="extraInputs" name="extraInputs">
{foreach key=name item=value from=$EXTRA_INPUTS}
<input type="hidden" id="{$name}" name="{$name}" value="{$value}" />
{/foreach}
</form>

{* popup status *}
<div id="status" name="status" style="display:none;position:fixed;right:2px;top:45px;z-index:100">
	{include file="LoadingIndicator.tpl"}
</div>

<table id="linkMsgMainTab" border="0" height="100%">
	<tr>
		<td id="linkMsgLeftPane">
		{* modules list *}
		<div id="linkMsgModCont" height="100%" style="overflow-y:hidden">
			<table id="linkMsgModTab">
				<tr><td align="right">
					<input type="button" onClick="LPOP.clickLinkModule('', 'CreateArea')" value="{'LBL_CREATE_AREA'|getTranslatedString}" class="crmbutton small save">
				</td></tr>
				{foreach item=mod from=$LINK_MODULES}
					<tr><td class="linkMsgModTd" id="linkMsgMod_{$mod.module}" onclick="LPOP.clickLinkModule('{$mod.module}', '{$mod.action}')">{$mod.label}</td></tr>
				{/foreach}
			</table>
			{if $IS_ADMIN eq '1'}
				<div style="position:absolute;bottom:0;width:100%;" align="center">
					<input type="button" onClick="LPOP.clickLinkModule('', 'AreaTools')" value="{'LBL_AREA_TOOLS'|getTranslatedString}" class="crmbutton small edit">
				</div>
			{/if}
		</div>

		</td>
		<td id="linkMsgRightPane">

			<table border="0" cellspacing="0" cellpadding="0" width="100%" height="100%">
			<tr><td id="linkMsgRightPaneTop">

				{* placeholder *}
				<div id="linkMsgDescrCont"></div>

				{* list *}
				<div id="linkMsgListCont" style="display:none"></div>

				{* details *}
				<div id="linkMsgDetailCont" style="display:none"></div>

				{* edit *}
				<div id="linkMsgEditCont" style="display:none"></div>

			</td></tr>
			</table>

		</td>

	</tr>
</table>

<script type="text/javascript">
{literal}
// slightly delay the initialization
jQuery(document).ready(function(){
	setTimeout(function() {
	
		jQuery('#linkMsgModCont').slimScroll({
			wheelStep: 10,
			height: jQuery('body').height()+'px',
			width: '100%'
		});
		var msgRightTopHeight = jQuery('#linkMsgRightPaneTop').height();
		var msgRightBottomHeight = jQuery('#linkMsgRightPaneBottom').height();
		(function(){
			var show_module = jQuery('#show_module').val();
			if (show_module) {
				jQuery('#linkMsgMod_'+show_module).click();
			}
		})();
	
	}, 200);
});
{/literal}
</script>

</body>
</html>