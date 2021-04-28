/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@215597 */

var KlondikeConfig = {
	
	authorize: function() {
		/* var klourl = jQuery('#klondike_url').val().trim();
		if (!klourl || klourl == 'https://cloud.klondike.ai/0000') {
			vtealert(alert_arr.SITEURL_CANNOT_BE_EMPTY);
			return;
		}*/
		//window.open('index.php?module=Settings&action=SettingsAjax&ajax=true&file=KlondikeAI/oauthPanel&klondike_url='+encodeURIComponent(klourl),'OauthPanel', 'width=520,height=680,location=no');
		window.open('index.php?module=Settings&action=SettingsAjax&ajax=true&file=KlondikeAI/oauthPanel','OauthPanel', 'width=520,height=680,location=no');
	},
	
	unlink: function() {
		vteconfirm(alert_arr.LBL_KLONDIKE_UNLINK_CONFIRM, function(yes) {
			if (yes) {
				window.location.href = 'index.php?module=Settings&action=SettingsAjax&ajax=true&file=KlondikeAI&remove_link=true';
			}
		});
	}
}
