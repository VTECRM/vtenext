/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

window.VTE = window.VTE || {};

VTE.Settings = VTE.Settings || {};

VTE.Settings.Currency = VTE.Settings.Currency || {

	deleteCurrency: function(currid) {
		jQuery("#status").show();
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'action=SettingsAjax&file=CurrencyDeleteStep1&return_action=CurrencyListView&return_module=Settings&module=Settings&parenttab=Settings&id='+currid,
			success: function(result) {
				jQuery("#status").hide();
				jQuery("#currencydiv").html(result);
			}
		});
	},

	transferCurrency: function(del_currencyid) {
		jQuery("#status").show();
		jQuery("#CurrencyDeleteLay").hide();
		var trans_currencyid = jQuery("#transfer_currency_id").val();
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'module=Settings&action=SettingsAjax&file=CurrencyDelete&ajax=true&delete_currency_id='+del_currencyid+'&transfer_currency_id='+trans_currencyid,
			success: function(result) {
				jQuery("#status").hide();
				window.location.reload();
			}
		});
	}

};

/**
 * @deprecated
 * This function has been moved to VTE.Settings.Currency class.
 */

function deleteCurrency(currid) {
	return VTE.callDeprecated('deleteCurrency', VTE.Settings.Currency.deleteCurrency, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Settings.Currency class.
 */

function transferCurrency(del_currencyid) {
	return VTE.callDeprecated('transferCurrency', VTE.Settings.Currency.transferCurrency, arguments);
}