{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@215354 crmv@215597 *}

<script type="text/javascript" src="{"modules/Settings/KlondikeAI/resources/KlondikeConfig.js"|resourcever}"></script>

{include file='Buttons_List.tpl'}
<style>
#klondike_buttons {
	border: none;
	background-color: transparent;
	width:100%;
	min-height: 500px;
	overflow: hidden;
}
</style>

<br>
<br>
<div id="klondike_cont" class="container">
	<div class="row">
		<div class="col-sm-12">
			<iframe id="klondike_buttons" src="https://cloud.klondike.ai/vtePanel/index.php?lang={$AUTHENTICATED_USER_LANGUAGE}"></iframe>
		</div>
	</div>
</div>

<div class="container">
	<div class="row">
		<div class="col-sm-12 text-center">
			<button class="crmbutton cancel" onclick="KlondikeConfig.unlink()">{$MOD.LBL_KLONDIKE_UNLINK}</button>
		</div>
	</div>
</div>

<script type="text/javascript"> 
/* resize based on contents */
window.addEventListener("message", function(event) {
	if (event.origin === 'https://cloud.klondike.ai') {
		if (event.data) {
			if (event.data.action === 'set_height') {
				var frame = document.getElementById("klondike_buttons");
				frame.style.height = event.data.height;
			} else if (event.data.action == 'link_not_configured') {
				vtealert('{$MOD.LBL_KLONDIKE_LINK_NOT_CONFIGURED}');
			}
		}
	}
});

jQuery('#klondike_buttons').on('load', function() {
	// send the token
	document.getElementById('klondike_buttons').contentWindow.postMessage({ action:'set_token', token: '{$ATOKEN}', url: '{$KLONDIKE_URL}' }, 'https://cloud.klondike.ai/');
});
</script> 
