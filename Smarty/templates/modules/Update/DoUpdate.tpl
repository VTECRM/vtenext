{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
 
{* crmv@183486 *}

<style type="text/css">
	#updateFrame {ldelim}
		border: none;
		width: 80%;
		height: 400px;
	{rdelim}
</style>
<div class="container text-center">
	<h3>{$MOD.LBL_UPDATE_RUNNING_WAIT}</h3>
	{literal}
	<script type="text/javascript">
		// block screen
		(function() {
			if (window.VteJS_DialogBox) VteJS_DialogBox.progress();
		})();
		function updateCompleted(time) {
			jQuery('#elapsedTime').text(time);
			jQuery('#updateResultOK').show();
			if (window.VteJS_DialogBox) VteJS_DialogBox.hideprogress();
		}
		function updateFrameLoaded() {
			// wait a bit to see if the ok message has been shown
			setTimeout(function() {
				if (!jQuery('#updateResultOK').is(':visible')) {
					// update died...
					if (window.VteJS_DialogBox) VteJS_DialogBox.hideprogress();
					jQuery('#updateResultKO').show();
				}
			}, 500);
		}
	</script>
	{/literal}
	<iframe id="updateFrame" src="{$IFRAME_URL|escape}" onload="updateFrameLoaded()"></iframe>
	<br><br>
	<div id="updateResultOK" style="display:none">
		<form action="index.php" method="post" name="form" id="form">
			<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
			<b>{$MOD.LBL_UPDATE_FINISHED}</b> {$APP.LBL_IN|strtolower} <span id="elapsedTime"></span> {$APP.LBL_MINUTES|strtolower}<br><br>
			<button type="submit" class="crmbutton small save" title="{$MOD.LBL_CONTINUE}">{$MOD.LBL_CONTINUE}</button>
		</form>
	</div>
	<div id="updateResultKO" style="display:none">
		<b>{$MOD.LBL_UPDATE_FAILED}</b>
	</div>
</div>