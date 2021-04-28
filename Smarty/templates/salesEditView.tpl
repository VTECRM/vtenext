{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@104568 *}

<div class="container-fluid mainContainer">
	<div class="row">
		<div class="col-sm-12">
			{if $HIDE_BUTTON_LIST neq '1'}
			{include file='Buttons_List1.tpl'} {* crmv@43864 *}
			{/if}

			{* crmv@18592 *}
			<table border=0 cellspacing=0 cellpadding=0 width=100% align=center>
			<tr>
				<td class="showPanelBg" valign="top" width="100%" style="padding:0px">
					<!-- PUBLIC CONTENTS STARTS-->
					{include file='EditViewHidden.tpl'}
					{if $HIDE_BUTTON_LIST neq '1'}
					{include file='Buttons_List_Edit.tpl'} {* crmv@43864 *}
					{/if}
					<div class="small">
						<!-- Account details tabs -->
						<table class="margintop" border=0 cellspacing=0 cellpadding=0 width=100% align=center> {* crmv@25128 *}
						<tr>
							<td>
								{* crmv@104568e *}
								{if is_array($EDITTABS) && count($EDITTABS) > 1}
								<table border=0 cellspacing=0 cellpadding=3 width=100% class="vte-tabs" id="EditViewTabs">
								<tr>
									{if !empty($EDITTABS)}
										{foreach item=_tab from=$EDITTABS name="extraDetailForeach"}
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
									{/if}
									<td class="dvtTabCache" align="right" style="width:100%"></td>
								</tr>
								</table>
								{/if}
								{* crmv@104568e *}
							</td>
						</tr>
						<tr>
							<td valign=top align=left >
								<table border=0 cellspacing=0 cellpadding=3 width=100% class="dvtContentSpace">
								<tr>

									<td align=left>
										<!-- content cache -->

										<table border=0 cellspacing=0 cellpadding=0 width=100%>
										<tr>
											<td id ="autocom"></td>
										</tr>
										<tr>
											<td style="padding:5px;padding-top:15px;">

												{* crmv@99316 crmv@104568 crmv@105937 crmv@198388 *}
												{* Blocks *}
												{foreach item=data from=$BLOCKS}
													{assign var="header" value=$data.label}
													{assign var="blockid" value=$data.blockid}
													{if isset($BLOCKVISIBILITY.$blockid) && $BLOCKVISIBILITY.$blockid eq 0}
														{* hide block *}
														{assign var="BLOCKDISPLAYSTATUS" value="display:none"}
													{else}
														{assign var="BLOCKDISPLAYSTATUS" value=""}
													{/if}
													
													<div class="blockrow_{$blockid}" style="{$BLOCKDISPLAYSTATUS}">
														<div id="block_{$blockid}" class="vte-card editBlock" style="{if $PANELID != $data.panelid}display:none{/if}">
															<div class="dvInnerHeader">
																<table border="0" cellspacing="0" cellpadding="{if $OLD_STYLE eq true}2{else}5{/if}" width=100% class="small editBlockHeader">	{* crmv@57221 *}
																	<tr>
																	{* crmv@20176 *}
																	{if $header== $MOD.LBL_ADDRESS_INFORMATION}
																		{include file='AddressCopy.tpl'}
																	{* crmv@20176e *}
																	{else}
																		<td colspan=4>
																			<div class="dvInnerHeaderTitle">{$header}</div>
																	{/if}
																		</td>
																	</tr>
																</table>
															</div>
															<div class="editBlockContent">
																<table border="0" cellspacing="0" cellpadding="{if $OLD_STYLE eq true}2{else}5{/if}" width=100% class="small">	{* crmv@57221 *}
																	<tbody id="displayfields_{$blockid}">
																		{include file="DisplayFields.tpl" data=$data.fields}
																	</tbody>
																</table>
															</div>
														</div>
													</div>
												{/foreach}

												{* Products block *}
												{if $MODULE|isInventoryModule}
													{include file="Inventory/ProductDetailsEditView.tpl"}
												{/if}
												{* crmv@99316e crmv@104568e crmv@105937e crmv@198388e *}
												
												{* crmv@115268 vtlib Customization: Embed DetailViewWidget block:// type if any *}
												{include file='VtlibWidgets.tpl' WIDGETTYPE="EDITVIEWWIDGET"}
												{* END *}

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
					<div>
				</td>
				<td align=right valign=top></td>
			</tr>
			</table>
			<!--added to fix 4600-->
			<input name='search_url' id="search_url" type='hidden' value='{$SEARCH}'>
			</form>
		</div>
	</div>
</div>

<script type="text/javascript">
	var fieldname = new Array({$VALIDATION_DATA_FIELDNAME});
	var fieldlabel = new Array({$VALIDATION_DATA_FIELDLABEL});
	var fielddatatype = new Array({$VALIDATION_DATA_FIELDDATATYPE});
	var fielduitype = new Array({$VALIDATION_DATA_FIELDUITYPE}); // crmv@83877
	var fieldwstype = new Array({$VALIDATION_DATA_FIELDWSTYPE}); //crmv@112297

	var ProductImages=new Array();
	var count=0;

	function delRowEmt(imagename)
	{ldelim}
		ProductImages[count++]=imagename;
	{rdelim}

	function displaydeleted()
	{ldelim}
		var imagelists='';
		for(var x = 0; x < ProductImages.length; x++)
		{ldelim}
			imagelists+=ProductImages[x]+'###';
		{rdelim}

		if(imagelists != '')
			document.EditView.imagelist.value=imagelists
	{rdelim}
	
	{* crmv@104568 *}

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
	{* crmv@104568e *}
	
	{* crmv@171832 *}
	{if $PERFORMANCE_CONFIG.EDITVIEW_CHANGELOG eq 1}
		saveEditViewChangeLogEtag('{$MODULE}','{$ID}');
	{/if}
	{* crmv@171832e *}
	
	{* crmv@198388 *}
	{if !empty($FOCUS_ON_FIELD)}
		jQuery('[name="{$FOCUS_ON_FIELD}"]').focus();
	{/if}
	{* crmv@198388e *}
</script>
<!-- vtlib customization: Help information assocaited with the fields -->
{if $FIELDHELPINFO}
<script type='text/javascript'>
{literal}var fieldhelpinfo = {}; {/literal}
{foreach item=FIELDHELPVAL key=FIELDHELPKEY from=$FIELDHELPINFO}
	fieldhelpinfo["{$FIELDHELPKEY}"] = "{$FIELDHELPVAL}";
{/foreach}
</script>
{/if}
<!-- END -->

{include file="modules/Processes/InitEditViewConditionals.tpl"} {* crmv@112297 *}