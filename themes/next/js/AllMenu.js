/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@140887

var AllMenuObj = AllMenuObj || {

	menu_search_submitted: false,

	searchInMenu: function() {
		AllMenuObj.menu_search_submitted = true;

		jQuery('.highlighted').removeClass('highlighted');
		jQuery('.drop_down_hover').removeClass('drop_down_hover');

		var searchText = jQuery('#menu_search_text').val();
		if (searchText == '') {
			jQuery('#menu_search_icn_canc').hide();
		} else {
			jQuery('#menu_search_icn_canc').show();
		}

		if (searchText != '') {
			jQuery('.menu_entry').each(function(i, ele) {
				var content = jQuery(ele).text();
				var contentNew = content.replace(new RegExp(searchText, "gi"), '<span class="highlighted">$&</span>');

				if (contentNew != content) {
					jQuery(ele).html(contentNew);
				}
			});

			if (jQuery(".highlighted").length == 1) {
				var el = jQuery('.highlighted');
				el.parent().addClass('drop_down_hover');
				el.removeClass('highlighted');

				jQuery(document).keyup(function(e) {
					if (e.which == 13) {
						if (el.parent().attr('href') != undefined && el.parent().attr('href') != '') {
							location.href = el.parent().attr('href');
						}
					}
				});
			}
		}
	},

	clearMenuSearchText: function(elem) {
		var jelem = jQuery(elem);
		jelem.focus();
		jelem.val('');
		AllMenuObj.restoreMenuSearchDefaultText(elem);
	},

	restoreMenuSearchDefaultText: function(elem) {
		var jelem = jQuery(elem);
		if (jelem.val() == '') {
			jQuery('#menu_search_icn_canc').hide();
			if (AllMenuObj.menu_search_submitted == true) {
				AllMenuObj.searchInMenu();
			} else {
				jelem.val('');
			}
		}
	},

	cancelMenuSearchSearchText: function() {
		jQuery('#menu_search_text').val('');
		AllMenuObj.restoreMenuSearchDefaultText(document.getElementById('menu_search_text'));
	},

	initialize: function() {
		jQuery('#menu_search_text').keyup(function() {
			AllMenuObj.searchInMenu();
		});
	},

}