{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<script type="text/javascript" src="{"include/js/dtlviewajax.js"|resourcever}"></script>
<script type="text/javascript" src="{"modules/Popup/Popup.js"|resourcever}"></script> {* crmv@43864 *}
<script type="text/javascript" src="{"modules/Settings/ProcessMaker/resources/ProcessMakerScript.js"|resourcever}"></script> {* crmv@124729 *}

{* crmv@104568 *}
<link href="include/js/jquery_plugins/mCustomScrollbar/jquery.mCustomScrollbar.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="include/js/jquery_plugins/mCustomScrollbar/jquery.mCustomScrollbar.concat.min.js"></script>
<link href="include/js/jquery_plugins/mCustomScrollbar/VTE.mCustomScrollbar.css" rel="stylesheet" type="text/css" />
{* crmv@104568e *}

<script type="text/javascript">
var fieldname = new Array({$VALIDATION_DATA_FIELDNAME});
var fieldlabel = new Array({$VALIDATION_DATA_FIELDLABEL});
var fielddatatype = new Array({$VALIDATION_DATA_FIELDDATATYPE});
var fielduitype = new Array({$VALIDATION_DATA_FIELDUITYPE}); // crmv@83877
var fieldwstype = new Array({$VALIDATION_DATA_FIELDWSTYPE}); //crmv@112297
</script>

<span id="crmspanid" style="display:none;position:absolute;" onmouseover="show('crmspanid');">
   <a class="edit" href="javascript:;">{$APP.LBL_EDIT_BUTTON}</a>
</span>

{if $MODULE eq 'Leads'}
	<div id="convertleaddiv" style="display:block;position:absolute;left:225px;top:150px;"></div>
{/if}

{if $PDFMAKER_ACTIVE eq true}
	<div id="sendpdfmail_cont" style="display:none;position:absolute;"></div>
	<div id="PDFDocDiv" style="display:none;"></div> {* crmv@163191 *}
	<div id="PDFBreaklineDiv" style="display:none;width:350px;position:absolute;" class="crmvDiv"></div>
	<div id="PDFImagesDiv" style="display:none;width:350px;position:absolute;" class="crmvDiv"></div>   
{/if}

{if $SHOW_DETAIL_TRACKER}
	{include file="modules/SDK/src/CalendarTracking/PopupTracking.tpl"}
{/if}

<div id="lstRecordLayout" class="layerPopup crmvDiv" style="display:none;width:320px;height:300px;z-index:21;position:fixed;"></div>	{*<!-- crmv@18592 -->*}

{include file="MapLocation.tpl"} {* crmv@194390 *}

{if $MODULE eq 'Products'}
	{* crmv@100492 - not needed here, they are included in DetailViewUtils.php *}
	{*
	<script language="JavaScript" type="text/javascript" src="modules/Products/Productsslide.js"></script>
	<script language="JavaScript" type="text/javascript">Carousel();</script -->
	*}
	{* crmv@100492e *}
{/if}

<form name="SendMail" onsubmit="VteJS_DialogBox.block();"><div id="sendmail_cont"></div></form>
<form name="SendFax" onsubmit="VteJS_DialogBox.block();"><div id="sendfax_cont"></div></form>
<form name="SendSms" id="SendSms" onsubmit="VteJS_DialogBox.block();" method="POST" action="index.php"><div id="sendsms_cont"></div></form>

{include file='Buttons_List1.tpl'}
{include file='Buttons_List_Detail.tpl'}

<div class="container-fluid mainContainer">
	<div class="row">
		<div class="col-sm-12">
			<table border=0 cellspacing=0 cellpadding=0 width=100% align=center>
			<tr>
				<td>
					{if count($DETAILTABS) > 1 || ($MODULE eq 'Campaigns' && 'Newsletter'|isModuleInstalled) || ($SinglePane_View eq false && $IS_REL_LIST neq false && $IS_REL_LIST|@count > 0)}{* crmv@203484 *}
						<table border=0 cellspacing=0 cellpadding=3 width=100% class="detailViewTabs" id="DetailViewTabs">
							<tr>
								{* crmv@45699 crmv@104568 *}
								{if !empty($DETAILTABS)}
									{foreach item=_tab from=$DETAILTABS name="extraDetailForeach"}
										{if empty($_tab.href)}
											{assign var="_href" value="javascript:;"}
										{else}
											{assign var="_href" value=$_tab.href}
										{/if}
										{if $smarty.foreach.extraDetailForeach.iteration eq 1}
											{assign var="_class" value="dvtSelectedCell"}
										{else}
											{assign var="_class" value="dvtUnSelectedCell"}
										{/if}
										<td class="{$_class}" align="center" onClick="{$_tab.onclick}" nowrap="" data-panelid="{$_tab.panelid}"><a href="{$_href}">{$_tab.label}</a></td>
									{/foreach}
								{* crmv@45699e *}
								{else}
									<td class="dvtSelectedCell" align=center nowrap>{$APP.LBL_INFORMATION}</td>
									{* crmv@22700 *}
									{if $MODULE eq 'Campaigns' && 'Newsletter'|isModuleInstalled}
										<td class="dvtUnSelectedCell" align=center nowrap><a href="index.php?action=Statistics&module={$MODULE}&record={$ID}&parenttab={$CATEGORY}">{'LBL_STATISTICS'|@getTranslatedString:'Newsletter'}</a></td>
									{/if}
									{* crmv@22700e *}
								{/if}
								{* crmv@104568e *}
								<td class="dvtTabCache" align="right" style="width:100%"></td>
							</tr>
						</table>
					{/if}
				</td>
			</tr>
			<tr>
				<td>
					<table class="table-fixed" border=0 cellspacing=0 cellpadding=0 width=100%>
						<tr>
							{* MAIN COLUMN (fields and related) *}
							<td width="75%" valign="top">
								<form action="index.php" method="post" name="DetailView" id="form" autocomplete="off"> {* crmv@106308 *}
									<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
									{include file='DetailViewHidden.tpl'}
									<div id="DetailViewBlocks">
										{include file="DetailViewBlocks.tpl"}	{* crmv@57221 *}
									</div>
								</form>
								{* crmv@101312 *}
								{if $MODULE eq "Calendar"}
									{include file="modules/Calendar/DetailViewExtra.tpl"}
								{/if}
								{* crmv@101312e *}
								{* crmv@44323 *}
								<div id="DetailExtraBlock">
									{$EXTRADETAILBLOCK}
								</div>
								{* crmv@44323e *}
								{*crmv@104558*}
								{if $MODULE eq 'Newsletter'}
									<br>
									<div id="template_prev">
										<table class="small" width="100%" cellspacing="0" cellpadding="0" border="0">
											<tr>
												<td class="dvInnerHeader">
													<b>{$MOD.LBL_TEMPLATE_PREVIEW}</b>
												</td>
												<td class="dvInnerHeader" width="2%">
													<button class="crmbutton small edit" name="Edit" onclick="openPopup('index.php?module=Newsletter&action=NewsletterAjax&file=widgets/TemplateEmailEdit&record={$ID}&mode=edit','TemplateEmailList','top=100,left=200,height=400,width=500,resizable=yes,scrollbars=yes,menubar=no,addressbar=no,status=yes','auto')" accesskey="E" title="Edit">{$APP.LBL_EDIT}</button>
												</td>
											</tr>
										</table>
										{include file='PreviewEmailTemplate.tpl'}
									</div>
									<br>
								{/if}
								{*crmv@104558e*}
								<div id="editlistprice" style="width:300px; padding-left:20px"></div> {* crmv@43864 crmv@128983 *}
								{include file='RelatedListsHidden.tpl'}	{* crmv@54245 *}
								<div id="RelatedLists" {if empty($RELATEDLISTS)}style="display:none;"{/if}>
									{include file='RelatedListNew.tpl' PIN=true}
								</div>
								<div id="DynamicRelatedList" style="display:none;"></div>
								</form>	{* crmv@54245 close form opened in RelatedListsHidden.tpl *}
								{if !empty($ASSOCIATED_PRODUCTS)}
									{$ASSOCIATED_PRODUCTS}
								{/if}
								{* vtlib Customization: Embed DetailViewWidget block:// type if any *}
								{include file='DetailViewWidgets.tpl'}
								{* END *}
							</td>
							{* crmv@151688 *}
							{if $OLD_STYLE neq true && empty($RELATIONS)}
								{assign var="HIDE_TURBOLIFT" value=true}
							{/if}
							<td width="25%" valign="top" style="padding-top:5px; {if $HIDE_TURBOLIFT}display:none{/if}" {if $HIDE_TURBOLIFT}hide_turbolift="yes"{/if} id="turboLiftContainer"> {* crmv@43864 *}
								<div id="turboLiftContainerDiv" style="width:20%; position:fixed; display:none; right:30px;"> {* crmv@121366 *}
									{include file='Turbolift.tpl' TURBOLIFT_MODE="DetailView"}
								</div>
							</td>
							{* crmv@151688e *}
						</tr>
					</table>
				</td>
			</tr>
			</table>
		</div>
	</div>
</div>

{* crmv@167019 *}
{if $DROPAREA_ACTIVE}
	{include file="DropArea.tpl"}
{/if}
{* crmv@167019e *}

{* crmv@95157 - metadata container *}
{if $MODULE eq 'Documents'}
<div id="metadataContainer" class="layerPopup crmvDiv" style="position:fixed;min-height:300px;min-width:500px;z-index:100;display:none">
	<table border="0" cellpadding="5" cellspacing="0" width="100%">
		<tr style="cursor:move;" height="34">
			<td id="Meta_Handle" style="padding:5px" class="level3Bg">
				<table cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td width="50%"><b>{$MOD.LBL_METADATA}</b></td>
					<td width="50%" align="right">&nbsp;
						<button id="metadataSaveButton" type="button" class="crmbutton save" onclick="saveMetadata('{$ID}')">{$APP.LBL_SAVE_LABEL}</button>
					</td>
				</tr>
				</table>
			</td>
		</tr>
	</table>
	<div class="crmvDivContent">
	</div>
	<div class="closebutton" onClick="fninvsh('metadataContainer');"></div>
</div>
<script type="text/javascript">
	{literal}
	(function() {
		// crmv@192014
		jQuery("#metadataContainer").draggable({
			handle: '#Meta_Handle'
		});
		// crmv@192014e
	})();
	{/literal}
</script>
{/if}
{* crmv@95157e *}

{* crmv@104568 *}
<script type="text/javascript">
	{if $PANEL_BLOCKS}
	var panelBlocks = {$PANEL_BLOCKS};
	{else}
	var panelBlocks = {ldelim}{rdelim};
	{/if}
	{if $PANELID > 0}
	var currentPanelId = {$PANELID};
	{else}
	var currentPanelId = 0;
	{/if}
	
	{* crmv@142262 *}
	{if $CONDITIONAL_FIELDS}
	ProcessMakerScript.conditional_fields = {$CONDITIONAL_FIELDS|@json_encode};
	{/if}
	{* crmv@142262e *}
	
	{* crmv@146652 *}
	{if $OPEN_MYNOTES_POPUP > 0}
		jQuery(document).ready(function(){ldelim}
			openPopup('index.php?module=MyNotes&action=SimpleView&record={$OPEN_MYNOTES_POPUP}');
		{rdelim});
	{/if}
	{* crmv@146652e *}
</script>
{* crmv@104568e *}

{* crmv@93990 crmv@109851 *}
{if $RELATED_PROCESS neq false}
	<script type="text/javascript">
		jQuery(document).ready(function(){ldelim}
			DynaFormScript.popup({$RELATED_PROCESS});
		{rdelim});
	</script>
{/if}
{* crmv@93990e crmv@109851e *}

{* crmv@171524 crmv@196871 *}
{if $STOMP_ENABLED}
	<script type="text/javascript" src="include/js/StompUtils.js"></script>
	<script type="text/javascript" src="include/js/rabbitmq/stomp.js"></script>
	<script type="text/javascript" src="include/js/rabbitmq/sockjs-0.3.js"></script>
	
	<script type="text/javascript">
		var isFreezed = parseInt({$IS_FREEZED}) === 1 ? true : false;
		{if !empty($STOMP_CONNECTION)}
			var stompConnection = {$STOMP_CONNECTION|replace:"'":"\'"};
		{else}
			var stompConnection = null;
		{/if}
	</script>
	
	{literal}
	<script type="text/javascript">
		var form = jQuery('form[name="DetailView"]')[0];
		var module = form.module.value;
		var record = parseInt(form.record.value);
		
		VTE.DetailView.listenForFreeze(module, record, {
			'stomp_connection': stompConnection,
		});
	</script>
	{/literal}
	
	{if $IS_FREEZED}
	{literal}
	<script type="text/javascript">
		var form = jQuery('form[name="DetailView"]')[0];
		var module = form.module.value;
		var record = parseInt(form.record.value);
		
		VTE.DetailView.checkFreezedRecord(module, record, {
			'stomp_connection': stompConnection,
			'mode': 'detailview',
		});
	</script>
	{/literal}
	{/if}
{/if}
{* crmv@171524e crmv@196871e *}