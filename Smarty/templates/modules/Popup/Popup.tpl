{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@42752 crmv@43050 crmv@43864 crmv@82831 crmv@98810 *}

{include file="SmallHeader.tpl" BODY_EXTRA_CLASS="popup-link-record"}
{include file="modules/SDK/src/Reference/Autocomplete.tpl"}

<link href="include/js/jquery_plugins/mCustomScrollbar/jquery.mCustomScrollbar.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="include/js/jquery_plugins/mCustomScrollbar/jquery.mCustomScrollbar.concat.min.js"></script>
<script type="text/javascript" src="include/js/jquery_plugins/slimscroll/jquery.slimscroll.min.js"></script>
<link href="include/js/jquery_plugins/mCustomScrollbar/VTE.mCustomScrollbar.css" rel="stylesheet" type="text/css" />

<script type="text/javascript" src="{"include/js/ListView.js"|resourcever}"></script>
<script type="text/javascript" src="{"include/js/dtlviewajax.js"|resourcever}"></script>
<script type="text/javascript" src="{"include/js/vtlib.js"|resourcever}"></script>
<script type="text/javascript" src="{"modules/SDK/src/Notifications/NotificationsCommon.js"|resourcever}"></script>
<script type="text/javascript" src="{"modules/Popup/Popup.js"|resourcever}"></script>

{* populate global JS variables *}
<script type="text/javascript">setGlobalVars('{$JS_GLOBAL_VARS|replace:"'":"\'"}');</script> {* crmv@70731 *}

{* crmv@73108 *}
{if $HEADERSCRIPTS}
	{foreach item=HEADERSCRIPT from=$HEADERSCRIPTS}
		<script type="text/javascript" src="{$HEADERSCRIPT->linkurl}"></script>
	{/foreach}
{/if}
{* crmv@73108 e *}

{if count($EXTRA_JS) > 0}
	{foreach item=JSPATH from=$EXTRA_JS}
	<script type="text/javascript" src="{$JSPATH}"></script>
	{/foreach}
{/if}

<form id="extraInputs" name="extraInputs">
{foreach key=name item=value from=$EXTRA_INPUTS}
	{if strpos($value, '"') !== false}
		<input type="hidden" id="{$name}" name="{$name}" value='{$value}' />
	{else}
		<input type="hidden" id="{$name}" name="{$name}" value="{$value}" />
	{/if}
{/foreach}
</form>

{* popup status *}
<div id="status" name="status" style="display:none;position:fixed;right:2px;top:45px;z-index:100">
	{include file="LoadingIndicator.tpl"}
</div>

<table id="linkMsgMainTab" border="0" height="100%">
	<tr>
		{* crmv@98866 *}
		<td id="linkMsgLeftPane" class="nopadding">
		{* modules list *}
		<div id="linkMsgModCont" height="100%" style="overflow-y:hidden">
			<table id="linkMsgModTab">
				{foreach item=mod from=$LINK_MODULES}
					<tr>
						{assign var="module" value=$mod.module}
						{assign var="module_lower" value=$module|strtolower}
						{assign var="trans_module" value=$module|getTranslatedString:$module}
						{assign var="first_letter" value=$trans_module|substr:0:1|strtoupper}
						
						<td class="linkMsgModTd" id="linkMsgMod_{$module}" onclick="LPOP.clickLinkModule('{$module}', '{$mod.action}', '{$mod.relation_id}')">
							<div class="vcenter text-left" style="width:15%">
								<i class="icon-module icon-{$module_lower}" data-first-letter="{$first_letter}"></i>
							</div>
							<span class="vcenter">{$trans_module}</span>
						</td>
					</tr> {* crmv@56603 *}
				{/foreach}
			</table>
		</div>
		{* crmv@98866 end *}

		</td>
		<td id="linkMsgRightPane">

			<table border="0" cellspacing="0" cellpadding="0" width="100%" height="100%">
			<tr><td id="linkMsgRightPaneTop">

				{* placeholder *}
				<div id="linkMsgDescrCont">{'LBL_SELECT_A_MODULE'|getTranslatedString}</div>

				{* list *}
				<div id="linkMsgListCont" style="display:none"></div>

				{* details *}
				<div id="linkMsgDetailCont" style="display:none"></div>

				{* edit *}
				<div id="linkMsgEditCont" style="display:none"></div>

			</td></tr>

			{if is_array($ATTACHMENTS) && count($ATTACHMENTS) > 0} {* crmv@167234 *}
			<tr><td id="linkMsgRightPaneBottom">
				<div id="linkMsgAttachCont" class="vte-card" style="display:none;">
					{* attachments *}
					<div id="popupAttachDiv">
						<div id="popupMsgAttachTitle">
							<input id="popupMsgAttachMainCheck" type="checkbox" name="" value="" checked="" onchange="messagesChangeAttach()" /> {'LBL_INCLUDE_ATTACH'|getTranslatedString:'Messages'}
						</div>
						<div id="popupMsgAttachList">
							<table class="vtetable table-condensed">
								<tbody>
									{foreach item=ATT from=$ATTACHMENTS}
										{assign var=inputName value='msgattach_'|cat:$ATT.contentid}
										<tr>
											<td><input class="popupMsgAttachCheck" type="checkbox" name="{$inputName}" id="{$inputName}" value="" checked="" onchange="messagesChangeSingleAtt(this)" /> <label for="{$inputName}">{$ATT.name}</label></td>
										</tr>
									{/foreach}
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</td></tr>
			{/if}

			</table>

		</td>

	</tr>
</table>
<script type="text/javascript">
{literal}

// crmv@103862
// slightly delay the initialization
jQuery(document).ready(function() {
	setTimeout(function() {
		
		jQuery('#linkMsgModCont').slimScroll({
			wheelStep: 10,
			height: jQuery('body').height()+20+'px',
			width: '100%'
		});

		var msgRightTopHeight = jQuery('#linkMsgRightPaneTop').height();
		var msgRightBottomHeight = jQuery('#linkMsgRightPaneBottom').height();

		// scroll for attachments
		jQuery('#popupMsgAttachList').slimScroll({
			wheelStep: 10,
			height: (msgRightBottomHeight-8)+'px',
			width: '100%'
		});

		(function(){
			var show_module = jQuery('#show_module').val();
			if (show_module) {
				jQuery('#linkMsgMod_'+show_module).click();
			}
		})();
		
	}, 100);
	
});
// crmv@103862e

{/literal}
</script>

</body>
</html>