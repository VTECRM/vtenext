/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/**
 * this file will poll the vte_asteriskincomingcalls table
 * for any incoming calls
 *
 * the variable ASTERISK_POLLTIME  denotes the number of milli-seconds after which the crm gets polled
 * the variable ASRERISK_DIV_TIMEOUT denotes the number of milli-seconds after which the incoming call information div times out (i.e. hidden)
 */
function _defAsteriskTimer(){
	var asteriskTimer = null;
	var ASTERISK_POLLTIME = 5000;	//vtecrm polls the asterisk server for incoming calls after every 3 seconds for now
	var ASTERISK_INCOMING_DIV_TIMEOUT = 15;	//the incoming call div is present for this number of seconds
	
	// crmv@192033
	function AsteriskCallback() {
		// crmv@185734 - removed obsolete patch crmv@167644
		jQuery.ajax({
			url: 'index.php?module=PBXManager&action=PBXManagerAjax&file=TraceIncomingCall&mode=ajax&ajax=true',
			method: 'GET',
			success: function(result) {
				popupText = trim(result);
				if(popupText != '' && popupText != 'failure'){
					var div = popupText;
					asterisk_popup = _defPopup();
					asterisk_popup.content = div;
					asterisk_popup.displayPopup(asterisk_popup.content,ASTERISK_INCOMING_DIV_TIMEOUT);
				}
			}
		});
	}
	// crmv@192033e

	function AsteriskRegisterCallback(timeout) {
		if(timeout == null) timeout = ASTERISK_POLLTIME;
		if(asteriskTimer == null) {
			AsteriskCallback();
			asteriskTimer = setInterval(AsteriskCallback, timeout);
		}
	}

	return {
		registerCallback: AsteriskRegisterCallback,
		pollTimer: ASTERISK_POLLTIME
	};
}

AsteriskTimer = _defAsteriskTimer();

AsteriskTimer.registerCallback(AsteriskTimer.pollTimer);