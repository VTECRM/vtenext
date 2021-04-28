{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
 
{* crmv@181161 *}

<link rel="stylesheet" type="text/css" href="include/js/snackbar/snackbar.min.css">
<script type="text/javascript" src="include/js/snackbar/snackbar.min.js"></script>
<script type="text/javascript" src="{"modules/Update/Update.js"|resourcever}"></script>

<div id="updatePopupContent" style="display:none">
	<div class="systemUpdateDialogHeader">
		<p class="systemUpdateDialogTitle">{$POPUP_TITLE}</p>
		{if $POPUP_SUBTITLE}
			<p class="systemUpdateDialogTitle">{$POPUP_SUBTITLE}</p>
		{/if}
	</div>
	<br>
	<div class="systemUpdateDialogButtons">
		<button type="button" class="btn btn-info btn-block btn-raised btn-round primary" onclick="VTE.Update.scheduleUpdate()">{"LBL_SCHEDULE_UPDATE"|getTranslatedString:"Update"} <i class="vteicon md-sm">update</i></button>
		<div class="dropup">
			<button type="button" class="btn btn-info btn-block btn-raised btn-round primary dropdown-toggle" data-toggle="dropdown">{"LBL_REMIND"|getTranslatedString:"Update"} <span class="caret"></span></button>
			<ul class="dropdown-menu dropdown-menu-right">
				{foreach key="VALUE" item="LABEL" from=$REMINDER_OPTIONS}
				<li><a href="javascript:void(0);" onclick="VTE.Update.remindUpdate('{$VALUE}')">{$LABEL}</a></li>
				{/foreach}
			</ul>
		</div>
		<button type="button" class="btn delete btn-block btn-raised btn-round" onclick="VTE.Update.ignoreUpdate()">{"LBL_IGNORE_UPDATE"|getTranslatedString:"Update"} <i class="vteicon md-sm">cancel</i></button>
	</div>
</div>

{literal}
<style>
	.systemUpdateDialog {
		background-color: rgba(50,50,50, 0.9) !important;
		border-radius: 5px;
		box-shadow: 0 14px 28px rgba(0,0,0,0.25), 0 10px 10px rgba(0,0,0,0.22);
	}
	.systemUpdateDialog > p {
		width: 100%;
	}
	.systemUpdateDialogTitle {
		font-size: 18px;
		line-height: 24px;
		text-align: center;
	}
	.systemUpdateDialogButtons .btn i {
		vertical-align: middle;
		color: white;
		margin-left: 5px;
	}
	.systemUpdateDialogButtons .btn i:hover {
		color: white !important;
	}
</style>

<script type="text/javascript">
	
	// show after a while
	setTimeout(function() {
		VTE.Update.showPopup();
		// then set in the db that it has been shown
		setTimeout(function() {
			VTE.Update.setPopupShown();
		}, 500);
	}, {/literal}{$POPUP_DELAY}{literal});
	
</script>
{/literal}