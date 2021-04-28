{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@42266 - tutto *}

<style type="text/css">
{literal}
.currencySymbolBlock {
	background-color: #c0c0c0;
	width: 40px;
	text-align: center;
	font-size: 12pt;
	border: 1px solid #909090;
}
.currencyEditLine {
	height: 18px;
	vertical-align: middle;
}
.currencyBlock {
	background-color: #f0f0f0;
	border: 1px solid #d0d0d0;
	padding: 10px;
}
.currencyCellLabel {
	color: #808080;
	font-weight: 700;
}
.currencyInputBox {
	margin-left: 4px;
	height: 12pt;
	border: 1px solid #bababa;
	background: #ffffff;
	padding: 1px;
	width: 80px;
}
.currencyInputBox[readonly] {
	background: #e0e0e0;
}
.currencyExampleTable {
	margin-top: 10px;
	background-color: #f0f0f0;
	border: 1px solid #d0d0d0;
}
.currencyExampleTitleRow {
	font-weight: 700;
	color: #808080;
}
.currencyExampleValueRow {
}
.currencyExampleResultRow {
}
.currencyExampleTdTitle {
	border-bottom: 1px solid #d0d0d0;
	padding: 6px;
}
.currencyExampleTdValue {
	padding: 4px;
	background: #e0e0e0;
	text-align: right;
	border-left: 1px solid #d0d0d0;
	border-bottom: 1px solid #d0d0d0;
}
.currencyExampleTdResult {
	padding: 4px;
	text-align: right;
	border-left: 1px solid #d0d0d0;
}
{/literal}
</style>

<script language="JAVASCRIPT" type="text/javascript" src="include/js/smoothscroll.js"></script>
<script language="JavaScript" type="text/javascript" src="{"include/js/menu.js"|resourcever}"></script>
<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
<tbody><tr>
        <td valign="top"></td>
        <td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->
	<div align=center>
			{include file='SetMenu.tpl'}
			{include file='Buttons_List.tpl'} {* crmv@30683 *}
			<!-- DISPLAY -->

{* crmv@42266 *}

			<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
			<form action="index.php" method="post" name="index" id="form" onsubmit="VteJS_DialogBox.block();">
			<input type="hidden" name="module" value="Settings">
			<input type="hidden" name="parenttab" value="{$PARENTTAB}">
			<input type="hidden" name="action" value="index">
			<input type="hidden" name="record" value="{$ID}">
			<tr>
				<td width=50 rowspan=2 valign=top><img src="{'currency.gif'|resourcever}" alt="{$MOD.LBL_USERS}" width="48" height="48" border=0 title="{$MOD.LBL_USERS}"></td>
				<td class="heading2" valign="bottom" ><b> {$MOD.LBL_SETTINGS} > <a href="index.php?module=Settings&action=CurrencyListView&parenttab=Settings">{$MOD.LBL_CURRENCY_SETTINGS}</a> > <!-- crmv@30683 -->
				{if $ID neq ''}
					{$MOD.LBL_EDIT} &quot;{$CURRENCY_NAME}&quot;
				{else}
					{$MOD.LBL_NEW_CURRENCY}
				{/if}
				</b></td>
			</tr>
			<tr>
				<td valign=top class="small">{$MOD.LBL_CURRENCY_DESCRIPTION}</td>
			</tr>
			</table>

				<br>
				<table border=0 cellspacing=0 cellpadding=10 width=100% >
				<tr>
				<td>

					<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
					<tr>
						{if $ID neq ''}
							<td class="big"><strong>{$MOD.LBL_SETTINGS} {$APP.LBL_FOR} &quot;{$CURRENCY_NAME|@getTranslatedCurrencyString}&quot;  </strong></td>
						{else}
							<td class="big"><strong>&quot;{$MOD.LBL_NEW_CURRENCY}&quot;  </strong></td>
						{/if}
						<td class="small" align=right>
							<input title="{$APP.LBL_SAVE_BUTTON_LABEL}" accessKey="{$APP.LBL_SAVE_BUTTON_KEY}" class="crmButton small save" onclick="this.form.action.value='SaveCurrencyInfo'; return validate()" type="submit" name="button" value="{$APP.LBL_SAVE_BUTTON_LABEL}" >&nbsp;&nbsp;

							{assign var="FLOAT_TITLE" value=$MOD.LBL_TRANSFER_CURRENCY}
							{assign var="FLOAT_WIDTH" value="450px"}
							{capture assign="FLOAT_CONTENT"}
								<table border=0 cellspacing=0 cellpadding=5 width=95% align=center>
									<tr>
										<td class=small >
											<table border=0 celspacing=0 cellpadding=5 width=100% align=center bgcolor=white>
												<tr>
													<td width="50%" class="cellLabel small"><b>{$MOD.LBL_CURRENT_CURRENCY} CC </b></td>
													<td width="50%" class="cellText small"><b>{$CURRENCY_NAME|@getTranslatedCurrencyString}</b></td>
												</tr>
												<tr>
													<td class="cellLabel small"><b>{$MOD.LBL_TRANSCURR}</b></td>
													<td class="cellText small">
														<select class="select small" name="transfer_currency_id" id="transfer_currency_id">';
														 {foreach key=cur_id item=cur_name from=$OTHER_CURRENCIES}
															 <option value="{$cur_id}">{$cur_name|@getTranslatedCurrencyString}</option>
														 {/foreach}
													</td>
												</tr>
											</table>
										</td>
									</tr>
								</table>
								<table border=0 cellspacing=0 cellpadding=5 width=100% class="layerPopupTransport">
									<tr>
										<td align="center"><input type="button" onclick="form.submit();" name="Update" value="{$APP.LBL_SAVE_BUTTON_LABEL}" class="crmbutton small save">
										</td>
									</tr>
								</table>
							{/capture}
							{include file="FloatingDiv.tpl" FLOAT_ID="CurrencyEditLay" FLOAT_BUTTONS=""}
							
							<input title="{$APP.LBL_CANCEL_BUTTON_LABEL}" accessKey="{$APP.LBL_CANCEL_BUTTON_KEY}" class="crmButton small cancel" onclick="window.history.back()" type="button" name="button" value="{$APP.LBL_CANCEL_BUTTON_LABEL}">
						</td>
					</tr>
					</table>



			<table border="0">
				<tr><td class="currencyBlock">

					<table border="0" cellpadding="0" cellspacing="0" class="small">
						<tr><td class="currencyEditLine currencyCellLabel">{$MOD.LBL_CURRENCY_NAME}</td></tr>
						<tr><td class="currencyEditLine">
							<select name="currency_name" id="currency_name" class="small" onChange='updateSymbolAndCode(); loadDefaultRatio();'>
							{foreach key=header item=currency from=$CURRENCIES}
								{if $header eq $CURRENCY_NAME}
			    		    		<option value="{$header}" selected>{$header|@getTranslatedCurrencyString} ({$currency.0})</option>
								{else}
									<option value="{$header}" >{$header|@getTranslatedCurrencyString} ({$currency.0})</option>
								{/if}
   							{/foreach}
							</select>
						</td></tr>

						<tr><td class="currencyEditLine">&nbsp;</td></tr>

						<tr><td class="currencyEditLine currencyCellLabel"></td></tr>
						<tr><td class="currencyEditLine">

							<table border="0" cellspacing="0" cellpadding="0">
								<tr>
								<td><div class="currencySymbolBlock" id="currency_symbol">{$CURRENCY_SYMBOL}</div></td>
								<td><input type="text" class="currencyInputBox small" readonly="" value="1"></td>
								</tr>
							</table>
						</td></tr>

					</table>

				</td>

				<td style="width:50px;font-size:22pt;text-align:center;color:#606060"> = </td>

				<td class="currencyBlock">

					<table border="0" cellpadding="0" cellspacing="0" class="small">
						<tr><td class="currencyEditLine currencyCellLabel">{$APP.LBL_BASE_CURRENCY}</td></tr>
						<tr><td class="currencyEditLine"><input type="text" readonly="" class="detailedViewTextBox small" value="{$MASTER_CURRENCY} ({$MASTER_CODE})"></td></tr>
						<tr><td class="currencyEditLine">&nbsp;</td></tr>
						<tr><td class="currencyEditLine currencyCellLabel">&nbsp;</td></tr>

						<tr><td class="currencyEditLine">

							<table border="0" cellspacing="0" cellpadding="0">
								<tr>
								<td><div class="currencySymbolBlock">{$MASTER_SYMBOL}</div></td>
								<td><input type="text" class="currencyInputBox small" value="{$CONVERSION_RATE}" name="conversion_rate" id="conversion_rate" onkeyup="updateExamples();"></td>
								<td width="30">
									<span id="currency_loader" style="display:none;">{include file="LoadingIndicator.tpl"}</span>
								</td>
								</tr>
							</table>
						</td></tr>
					</table>

				</td></tr>

			</table>
			<br>

			<table>
				<tr>
					<td nowrap class="small cellLabel"><strong>{$MOD.LBL_CURRENCY_STATUS}</strong></td>
					<td class="small cellText">
						<input type="hidden" value="{$CURRENCY_STATUS}" id="old_currency_status" />
						<select name="currency_status" {$STATUS_DISABLE} class="importBox">
							<option value="Active"  {$ACTSELECT}>{$MOD.LBL_ACTIVE}</option>
				   	        <option value="Inactive" {$INACTSELECT}>{$MOD.LBL_INACTIVE}</option>
						</select>
					</td>
				</tr>
			</table>
			<br>

			<br>
			<span style="font-weight:700">{$APP.LBL_EXAMPLES}</span>
			<br><br>
			<table class="currencyExampleTable" id="currencyExamplesTable1" border="0" width="80%" cellspacing="0">
				<tr class="currencyExampleTitleRow">
					<td colspan="6" class="currencyExampleTdTitle"><span class="currencyName">{$CURRENCY_NAME}</span> &rarr; <span>{$MASTER_CURRENCY}</span></td>
				</tr>
				<tr class="currencyExampleValueRow">
					<td class="currencyExampleTdValue"><span class="currencyExampleValue">1</span> <span class="currencySymbol">&nbsp;</span></td>
					<td class="currencyExampleTdValue"><span class="currencyExampleValue">2</span> <span class="currencySymbol">&nbsp;</span></td>
					<td class="currencyExampleTdValue"><span class="currencyExampleValue">5</span> <span class="currencySymbol">&nbsp;</span></td>
					<td class="currencyExampleTdValue"><span class="currencyExampleValue">10</span> <span class="currencySymbol">&nbsp;</span></td>
					<td class="currencyExampleTdValue"><span class="currencyExampleValue">50</span> <span class="currencySymbol">&nbsp;</span></td>
					<td class="currencyExampleTdValue"><span class="currencyExampleValue">100</span> <span class="currencySymbol">&nbsp;</span></td>
				</tr>
				<tr class="currencyExampleResultRow">
					<td class="currencyExampleTdResult"><span class="currencyExampleResult">&nbsp;</span> {$MASTER_SYMBOL}</td>
					<td class="currencyExampleTdResult"><span class="currencyExampleResult">&nbsp;</span> {$MASTER_SYMBOL}</td>
					<td class="currencyExampleTdResult"><span class="currencyExampleResult">&nbsp;</span> {$MASTER_SYMBOL}</td>
					<td class="currencyExampleTdResult"><span class="currencyExampleResult">&nbsp;</span> {$MASTER_SYMBOL}</td>
					<td class="currencyExampleTdResult"><span class="currencyExampleResult">&nbsp;</span> {$MASTER_SYMBOL}</td>
					<td class="currencyExampleTdResult"><span class="currencyExampleResult">&nbsp;</span> {$MASTER_SYMBOL}</td>
				</tr>
			</table>

			<table class="currencyExampleTable" id="currencyExamplesTable2" border="0" width="80%" cellspacing="0">
				<tr class="currencyExampleTitleRow">
					<td colspan="6" class="currencyExampleTdTitle"><span>{$MASTER_CURRENCY}</span> &rarr; <span class="currencyName">{$CURRENCY_NAME}</span></td>
				</tr>
				<tr class="currencyExampleValueRow">
					<td class="currencyExampleTdValue"><span class="currencyExampleValue">1</span> {$MASTER_SYMBOL}</td>
					<td class="currencyExampleTdValue"><span class="currencyExampleValue">2</span> {$MASTER_SYMBOL}</td>
					<td class="currencyExampleTdValue"><span class="currencyExampleValue">5</span> {$MASTER_SYMBOL}</td>
					<td class="currencyExampleTdValue"><span class="currencyExampleValue">10</span> {$MASTER_SYMBOL}</td>
					<td class="currencyExampleTdValue"><span class="currencyExampleValue">50</span> {$MASTER_SYMBOL}</td>
					<td class="currencyExampleTdValue"><span class="currencyExampleValue">100</span> {$MASTER_SYMBOL}</td>
				</tr>
				<tr class="currencyExampleResultRow">
					<td class="currencyExampleTdResult"><span class="currencyExampleResult">&nbsp;</span> <span class="currencySymbol">&nbsp;</span></td>
					<td class="currencyExampleTdResult"><span class="currencyExampleResult">&nbsp;</span> <span class="currencySymbol">&nbsp;</span></td>
					<td class="currencyExampleTdResult"><span class="currencyExampleResult">&nbsp;</span> <span class="currencySymbol">&nbsp;</span></td>
					<td class="currencyExampleTdResult"><span class="currencyExampleResult">&nbsp;</span> <span class="currencySymbol">&nbsp;</span></td>
					<td class="currencyExampleTdResult"><span class="currencyExampleResult">&nbsp;</span> <span class="currencySymbol">&nbsp;</span></td>
					<td class="currencyExampleTdResult"><span class="currencyExampleResult">&nbsp;</span> <span class="currencySymbol">&nbsp;</span></td>
				</tr>

			</table>


			</td>
			</tr>
			</table>
		</td>
	</tr>
	</form>
	</table>

	</div>
</td>
        <td valign="top"></td>
   </tr>
</tbody>
</table>
{literal}
<script type="text/javascript">
	function validate() {

		if (!emptyCheck("conversion_rate","Conversion Rate","text")) return false
		if (!emptyCheck("currency_status","Currency Status","text")) return false
		if (isNaN(getObj("conversion_rate").value) || eval(getObj("conversion_rate").value) <= 0) {
			{/literal}
            alert("{$APP.ENTER_VALID_CONVERSION_RATE}");
			return false;
			{literal}
		}

		if (getObj("currency_status") != null && getObj("currency_status").value == "Inactive"
				&& getObj("old_currency_status") != null && getObj("old_currency_status").value == "Active")
		{
			showFloatingDiv("CurrencyEditLay");
			return false;
		}
		else
		{
			return true;
		}
	}
{/literal}

var currency_array = {$CURRENCIES_ARRAY}

updateSymbolAndCode();
{if $ID eq ''}
loadDefaultRatio();
{else}
updateExamples();
{/if}

{literal}
function updateSymbolAndCode(){
	var selected_curr = jQuery('#currency_name').val(),
		symbol = currency_array[selected_curr][1];

	// main symbol box
	jQuery('#currency_symbol').html(symbol);

	// update symbol and name for examples
	jQuery('.currencySymbol').html(symbol);
	jQuery('.currencyName').html(selected_curr);
}

function updateExamples() {
	var ratio = parseFloat(jQuery('#conversion_rate').val());

	// currency -> master currency
	var values = [];
	jQuery('#currencyExamplesTable1 span.currencyExampleValue').each(function(index, item) {
		values.push( parseFloat(jQuery(item).html()) * ratio );
	});
	jQuery('#currencyExamplesTable1 span.currencyExampleResult').each(function(index, item) {
		var result = values[index];
		if (isNaN(result)) {
			result = '';
		} else {
			result = result.toFixed(2);
			if (result > 100000) result = '';
		}
		jQuery(item).html(result);
	});

	// currency -> master currency
	values = [];
	jQuery('#currencyExamplesTable2 span.currencyExampleValue').each(function(index, item) {
		values.push( parseFloat(jQuery(item).html()) / ratio );
	});
	jQuery('#currencyExamplesTable2 span.currencyExampleResult').each(function(index, item) {
		var result = values[index];
		if (isNaN(result)) {
			result = '';
		} else {
			result = result.toFixed(2);
			if (result > 100000) result = '';
		}
		jQuery(item).html(result);
	});

}

var activeAjaxRequest = null;

//load default exchange ratio
function loadDefaultRatio() {
	var selected_curr = jQuery('#currency_name').val(),
		rateCont = jQuery('#conversion_rate');

	// abort any previous request
	if (activeAjaxRequest) activeAjaxRequest.abort();
	rateCont.val('').attr('disabled', 'true');
	jQuery('#currency_loader').show();
	activeAjaxRequest = jQuery.ajax({
		{/literal}
		url: 'index.php?module=Settings&action=SettingsAjax&file=getExchangeRatio&to='+encodeURIComponent(currency_array['{$MASTER_CURRENCY}'][0])+'&from='+encodeURIComponent(currency_array[selected_curr][0]),
		{literal}
		success: function(data) {
			if (data && parseFloat(data) > 0) {
				rateCont.val(data);
				updateExamples();
			}
			rateCont.attr('disabled', false);
			jQuery('#currency_loader').hide();
			activeAjaxRequest = null;
		}
	});
}
</script>
{/literal}