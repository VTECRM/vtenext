/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@167371 crmv@201309 */

window.Utils209 = window.Utils209 || {

	initialized: false,

	load: function() {
		if (this.initialized) return;

		if (!window.wheelzoom) {
			console.error('wheelzoom plugin not found.');
			return false;
		}

		wheelzoom(document.querySelectorAll(".img_zoom"), { 
			zoom: 0.1,
			maxZoom: 10 
		});

		this.initialized = true;
	},

	doZoom: function(action, id) {
		if (action == "in") {
			jQuery("#" + id)[0].doZoomIn();
		} else if (action == "out") {
			jQuery("#" + id)[0].doZoomOut();
		}
	},
};

jQuery(document).ready(function() {
	Utils209.load();
});