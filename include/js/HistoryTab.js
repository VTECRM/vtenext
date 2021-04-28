/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@104566 crmv@104975 */

if (typeof(HistoryTabScript) == 'undefined') {
	var HistoryTabScript = {
		
		showTab: function(module, record) {
			var me = this;
			if (jQuery('#HistoryTab').length > 0) {
				jQuery('#HistoryTab').show();
				return;
			}
			jQuery('#DetailExtraBlock').append('<div id="HistoryTab" class="detailTabsMainDiv" style="display:none"></div>');
			me.getHistory(module, record);
		},
		
		hideTab: function() {
			jQuery('#HistoryTab').hide();
		},
		
		getHistory: function(module, record, callback) { // crmv@171524
			jQuery('#status').show();
			jQuery.ajax({
				'url': 'index.php?module=ChangeLog&action=ChangeLogAjax&file=HistoryTab&pmodule='+module+'&record='+record,
				'type': 'POST',
				success: function(data) {
					jQuery('#status').hide();
					jQuery('#HistoryTab').html(data);
					if (typeof callback == 'function') callback(); // crmv@171524
				}
			});
		},
	}
}