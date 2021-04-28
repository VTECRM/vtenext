{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<script language="JavaScript" type="text/javascript" src="modules/PriceBooks/PriceBooks.js"></script>

{literal}
<script type="text/javascript">
	function editProductListPrice(id,pbid,price,module) {
		jQuery("#status").show();
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'action='+module+'Ajax&file=EditListPrice&return_action=DetailView&return_module=PriceBooks&module='+module+'&record='+id+'&pricebook_id='+pbid+'&listprice='+price,
			success: function(result) {
				jQuery("#status").hide();
				jQuery("#editlistprice").html(result);
			}
		});
	}

	function gotoUpdateListPrice(id,pbid,proid,module) {
		jQuery("#status").show();
		jQuery("#roleLay").hide();
		var listprice = jQuery("#list_price").val();

		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'module='+module+'&action='+module+'Ajax&file=UpdateListPrice&ajax=true&return_action=CallRelatedList&return_module=PriceBooks&record='+id+'&pricebook_id='+pbid+'&product_id='+proid+'&list_price='+listprice,
			success: function(result) {
				jQuery("#status").hide();
				reloadTurboLift('PriceBooks', id, module);
			}
		});
	}
</script>
{/literal}

<form name="SendMail" onsubmit="VteJS_DialogBox.block();"><div id="sendmail_cont" style="z-index:100001;position:absolute;width:300px;"></div></form>
<form name="SendFax" onsubmit="VteJS_DialogBox.block();"><div id="sendfax_cont" style="z-index:100001;position:absolute;width:300px;"></div></form>
<form name="SendSms" onsubmit="VteJS_DialogBox.block();"><div id="sendsms_cont" style="z-index:100001;position:absolute;width:300px;"></div></form>

{include file='Buttons_List1.tpl'}
{include file='Buttons_List_Detail.tpl'}

<div id="editlistprice" style="position:absolute;width:300px;"></div>
		
<table border=0 cellspacing=0 cellpadding=0 width=100% align=center>
<tr>
	<td valign="top" width=100%>
		<table border=0 cellspacing=0 cellpadding=0 width=100% align=center>
			<tr>
				<td style="padding-top:3px;">
					<table border=0 cellspacing=0 cellpadding=3 width=100% class="small">
						<tr>
							{if $OP_MODE eq 'edit_view'}
								{assign var="action" value="EditView"}
							{else}
								{assign var="action" value="DetailView"}
							{/if}
							<td class="dvtTabCache" style="width:10px" nowrap>&nbsp;</td>
							{if $MODULE eq 'Calendar'}
								<td class="dvtUnSelectedCell" align=center nowrap><a href="index.php?action={$action}&module={$MODULE}&record={$ID}&activity_mode={$ACTIVITY_MODE}&parenttab={$CATEGORY}">{$SINGLE_MOD} {$APP.LBL_INFORMATION}</a></td>
							{else}
								<td class="dvtUnSelectedCell" align=center nowrap><a href="index.php?action={$action}&module={$MODULE}&record={$ID}&parenttab={$CATEGORY}">{$SINGLE_MOD} {$APP.LBL_INFORMATION}</a></td>
							{/if}
							{* crmv@22700 *}
							{* crmv@181170 *}
							{if isModuleInstalled('Newsletter')}
								{if $MODULE eq 'Campaigns'}
									<td class="dvtTabCache" style="width:10px" nowrap>&nbsp;</td>
									<td class="dvtUnSelectedCell" align=center nowrap><a href="index.php?action=Statistics&module={$MODULE}&record={$ID}&parenttab={$CATEGORY}">{'LBL_STATISTICS'|@getTranslatedString:'Newsletter'}</a></td>
								{/if}
							{/if}
							{* crmv@181170e *}
							{* crmv@22700e *}
							<td class="dvtTabCache" style="width:10px">&nbsp;</td>
							<td class="dvtSelectedCell" align=center nowrap>{$APP.LBL_MORE} {$APP.LBL_INFORMATION}</td>
							<td class="dvtTabCache" style="width:100%">&nbsp;</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td valign="top">                
					<table border=0 cellspacing=0 cellpadding=0 width=100%>
						<tr>
							<td align=left valign="top">
								{include file='RelatedListsHidden.tpl'}
								<div id="RLContents">
									{include file='RelatedListContents.tpl'}
								</div>
								</form>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</td>
</tr>
</table>