{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@104568 *}

<span id="crmspanid" style="display:none;position:absolute;" onmouseover="show('crmspanid');">
   <a class="edit" href="javascript:;">{$APP.LBL_EDIT_BUTTON}</a>
</span>

<div id="lstRecordLayout" class="layerPopup crmvDiv" style="display:none;width:320px;height:300px;z-index:21;position:fixed;"></div>	{* crmv@18592 *}

{include file="MapLocation.tpl"} {* crmv@194390 *}

<!-- Contents -->
{* crmv@18592 *}
<table border=0 cellspacing=0 cellpadding=0 width=100% align=center>
	<tr>
		<td>
			<table border=0 cellspacing=0 cellpadding=0 width="100%">
				<tr>
					<td colspan="2" align="left" class="listMessageFrom">{$NAME}</td>
				</tr>
				<tr>
					<td align="left" style="color: gray;">{$UPDATEINFO}</td>
					<td align="right">
						{if $HIDE_BUTTON_LIST neq '1'} {* crmv@42752 *}
							<a href="javascript:;" onClick="preView('{$MODULE}',{$ID});">{$RETURN_MODULE|getSingleModuleName}</a>&nbsp;-
							<a href="index.php?module={$MODULE}&action=DetailView&record={$ID}">{$APP.LBL_SHOW_DETAILS}</a>
						{/if}
					</td>
				</tr>
				<tr><td colspan="2"><hr class="my-1" /></td></tr>
			</table>
		</td>
	</tr>
	<tr>
		<td class="showPanelBg" valign=top width=100% style="padding:5px;">
			{* crmv@104568 *}
			{if !empty($DETAILTABS) && count($DETAILTABS) > 1}
				<table border=0 cellspacing=0 cellpadding=3 width=100% class="small" id="DetailViewTabs">
					<tr>
						{* crmv@45699 *}
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
						<td class="dvtTabCache" align="right" style="width:100%"></td>
						{* crmv@45699e *}
					</tr>
				</table>
			{/if}
			{* crmv@104568e *}
			
			<!-- Account details tabs -->
			<table border=0 cellspacing=0 cellpadding=0 width=100% align=center>
				<tr>
					<td valign=top align=left >
						<table border=0 cellspacing=0 cellpadding=0 width=100%>
							<tr>
								<td align=left valign="top"> {* crmv@20260 *}
									<!-- content cache -->
									<table border=0 cellspacing=0 cellpadding=0 width=100%>
										<tr>
											<td>
												<!-- NOTE: We should avoid form-inside-form condition, which could happen when Singlepane view is enabled. -->
												<form action="index.php" method="post" name="DetailView" id="form">
													<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
													{include file='DetailViewHidden.tpl'}

													{assign var=BLOCKINITIALSTATUS value=VteSession::get('BLOCKINITIALSTATUS')} {* crmv@181170 *}

													{* crmv@104568 *}
													{foreach item=detail from=$BLOCKS}
														{assign var="header" value=$detail.label}
														{assign var="blockid" value=$detail.blockid}
														<div id="block_{$blockid}" class="vte-card detailBlock" style="{if $PANELID != $detail.panelid}display:none{/if}">
														<!-- Detailed View Code starts here-->
														<table border=0 cellspacing=0 cellpadding=0 width=100% class="small">
															<tr>{strip}
																<td>
																	<div class="dvInnerHeader">
																		<div class="dvInnerHeaderLeft">
																			<div class="dvInnerHeaderTitle">{$header}</div>
																		</div>
																		<div class="dvInnerHeaderRight">
																			{if $header eq $MOD.LBL_ADDRESS_INFORMATION && ($MODULE eq 'Accounts' || $MODULE eq 'Contacts' || $MODULE eq 'Leads')}
																				{if $MODULE eq 'Leads'}
																					<button name="mapbutton" class="crmbutton create" type="button" onclick="VTE.MapLocation.searchMapLocation('{$ID}', 'Main');">{$APP.LBL_LOCATE_MAP}</button> {* crmv@194390 *}
																				{else}
																					<button name="mapbutton" class="crmbutton create" type="button" onclick="VTE.MapLocation.showAvailableAddresses();">{$APP.LBL_LOCATE_MAP}</button> {* crmv@194390 *}
																				{/if}
																			{/if}
																			{if $BLOCKINITIALSTATUS[$header] eq 1}
																				<i class="vteicon md-sm md-link" id="aid{$header|replace:' ':''}" title="Hide" onclick="showHideStatus('tbl{$header|replace:' ':''}','aid{$header|replace:' ':''}','{$IMAGE_PATH}');">video_label</i>
																			{else}
																				<i class="vteicon md-sm md-link" id="aid{$header|replace:' ':''}" title="Display" style="opacity:0.5" onclick="showHideStatus('tbl{$header|replace:' ':''}','aid{$header|replace:' ':''}','{$IMAGE_PATH}');">video_label</i>
																			{/if}
																		</div>
																	</div>
																</td>{/strip}
															</tr>
														</table>
														{if $BLOCKINITIALSTATUS[$header] eq 1}
															<div id="tbl{$header|replace:' ':''}" >
														{else}
															<div id="tbl{$header|replace:' ':''}" >
														{/if}
															{include file="DetailViewBlock.tpl" detail=$detail.fields}
														</div>
														</div>
													{/foreach}
													{*-- End of Blocks--*}
													{* crmv@104568e *}
												</form>
											</td>
										</tr>
										<tr>
											<td>
												{* crmv@181170 *}
												{* vtlib Customization: Embed DetailViewWidget block:// type if any *}
												{if $CUSTOM_LINKS && !empty($CUSTOM_LINKS.DETAILVIEWWIDGET)}
													<table border=0 cellspacing=0 cellpadding=5 width=100% id="DetailViewWidgets">
														{foreach item=CUSTOM_LINK_DETAILVIEWWIDGET from=$CUSTOM_LINKS.DETAILVIEWWIDGET}
															{if !$CUSTOM_LINK_DETAILVIEWWIDGET->validateDisplayWidget($ID)}
																{continue}
															{/if}
															<tr>
																<td style="padding:5px;">
																	{$CUSTOM_LINK_DETAILVIEWWIDGET->displayWidgetContent($ID)}
																</td>
															</tr>
														{/foreach}
													</table>
												{/if}
												{* END *}
												{* crmv@181170e *}
											</td>
										</tr>
										<!-- Inventory - Product Details informations -->
										<tr>
											<td>{$ASSOCIATED_PRODUCTS}</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>

<!-- added for validation -->
<script language="javascript">
  var fieldname = new Array({$VALIDATION_DATA_FIELDNAME});
  var fieldlabel = new Array({$VALIDATION_DATA_FIELDLABEL});
  var fielddatatype = new Array({$VALIDATION_DATA_FIELDDATATYPE});
  var fielduitype = new Array({$VALIDATION_DATA_FIELDUITYPE}); // crmv@83877
  var fieldwstype = new Array({$VALIDATION_DATA_FIELDWSTYPE}); //crmv@112297
</script>

<form name="SendMail" onsubmit="VteJS_DialogBox.block();"><div id="sendmail_cont" style="z-index:100001;position:absolute;"></div></form>
<form name="SendFax" onsubmit="VteJS_DialogBox.block();"><div id="sendfax_cont" style="z-index:100001;position:absolute;width:300px;"></div></form>
<!-- crmv@16703 -->
<form name="SendSms" id="SendSms" onsubmit="VteJS_DialogBox.block();" method="POST" action="index.php"><div id="sendsms_cont" style="z-index:100001;position:absolute;width:300px;"></div></form>
<!-- crmv@16703e -->

<script language="javascript">
//showHideStatus('tblModCommentsDetailViewBlockCommentWidget','aidModCommentsDetailViewBlockCommentWidget','{$IMAGE_PATH}');
</script>

{* crmv@104568 *}
<script type="text/javascript">
	{if $PANEL_BLOCKS}
	var panelBlocks = {$PANEL_BLOCKS};
	{else}
	var panelBlocks = {ldelim}{rdelim};
	{/if}
</script>
{* crmv@104568e *}