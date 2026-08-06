{*************************************
 * SPDX-FileCopyrightText: 2026 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 *************************************}

<div id="alert-dialog" class="modal fade" tabindex="-1">
	<div class="modal-dialog modal-sm">
		<div class="modal-content">
			<div class="modal-body">
				<p id="alert-dialog-content"></p>
			</div>
			<div class="hidden modal-footer">
				<button class="btn btn-primary btn-ok" data-dismiss="modal">OK</button>
			</div>
		</div>
	</div>
</div>

<div id="confirm-dialog" class="modal fade" tabindex="-1">
	<div class="modal-dialog modal-sm">
		<div class="modal-content">
			<div class="modal-body">
				<p id="confirm-dialog-content"></p>
			</div>
			<div class="modal-footer">
				<button class="btn btn-primary btn-exit" msg="exit" style="display:none">{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
				<button class="btn btn-primary btn-cancel" msg="cancel">{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
				<button class="btn btn-primary btn-ok" msg="ok">OK</button>
			</div>
		</div>
	</div>
</div>
