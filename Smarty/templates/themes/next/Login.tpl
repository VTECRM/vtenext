{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@94525 crmv@91082 crmv@148514 crmv@170412 *}

{* header has already been printed *}

{* style for login page *}
<link rel="stylesheet" href="{"themes/`$THEME`/style_login.css"|resourcever}">

{capture name="loginbox"}

<div class="loginBox boxHidden">
	<div class="row">
		<div class="col-xs-12 text-left">
			{if $LOGOUT_REASON}
				<div id="logout_reason_msg">
					<div class="alert alert-danger">
						{$LOGOUT_REASON}
					</div>
				</div>
			{/if}
		</div>
	</div>

	<!-- Sign in form -->
	{if !$LOGIN_AJAX}
	<form action="index.php" method="post" name="DetailView" id="form" {Users::m_de_cryption_get(13)}> {* crmv@181170 *}
		<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
		<input type="hidden" name="module" value="Users">
		<input type="hidden" name="action" value="Authenticate">
		<input type="hidden" name="return_module" value="Users">
		<input type="hidden" name="return_action" value="Login">
		<input type="hidden" name="free_params" value="">	<!-- crmv@35153 -->
	{/if}

	<div class="row logoContainer">
		<div class="col-xs-12 text-center">
			<img src="{$LOGOIMG}" />
		</div>
	</div>
	
	<div class="row">
		<div class="col-xs-10 col-xs-offset-1">
			<div class="row">
				<div class="col-xs-12">
					<div class="form-group inputContainer">
						<input id="login_user_name" class="form-control" type="text" name="user_name" value="{$USERNAME}" autofocus required="true" />
						<label for="login_user_name">{$MOD.LBL_USER_NAME}</label>
						<i class="vteicon nohover">person</i>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-xs-12">
					<div class="form-group inputContainer">
						<input id="login_password" class="form-control" type="password" name="user_password" value="{$PASSWORD}" required="true" autocomplete="new-password" />
						<label for="login_password">Password</label>
						<i class="vteicon nohover">lock_outline</i>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-xs-12">
					<div class="checkbox">
						<label for="savelogin">
							<input type="checkbox" name="savelogin" id="savelogin" {if $SAVELOGIN}checked="checked"{/if}" />
			         		&nbsp;{$MOD.LBL_KEEP_ME_LOGGED_IN}
		         		</label>
					</div>
					<div class="spacer-10"></div>
				</div>
			</div>
			<div class="row">
				<div class="col-xs-12">
					<div class="form-group inputContainer">
						{if $LOGIN_AJAX}
							<input type="button" class="btn btn-lg btn-raised btn-primary" name="Login" value="{$MOD.LBL_LOGIN_BUTTON_LABEL}" onclick="SessionValidator.doLogin();" />
						{else}
							<input type="submit" class="btn btn-lg btn-raised btn-primary" name="Login" value="{$MOD.LBL_LOGIN_BUTTON_LABEL}" />
						{/if}
					</div>
				</div>
			</div>
			{if !empty($ERROR_STR) & $ERROR_STR neq '&nbsp;'}
				<div class="row">
					<div class="col-xs-12">
						<div class="form-group inputContainer text-center">
							{$ERROR_STR}
						</div>
					</div>
				</div>
			{/if}
			<div class="row">
				<div class="col-xs-12">
					<div class="form-group text-center recoverContainer">
						<a href="hub/rpwd.php" {if $LOGIN_AJAX}target="_blank"{/if}>{$MOD.LBL_FORGOT_YOUR_PASSWORD}</a> {* crmv@192078 *}
					</div>
				</div>
			</div>
		</div>
	</div>
	
	{if !$LOGIN_AJAX}
		</form>
	{/if}
</div>

{/capture}

{if !$LOGIN_AJAX}
	<script language="JavaScript" type="text/javascript" src="modules/Morphsuit/MorphsuitCommon.js"></script>
	<script language="JavaScript" type="text/javascript" src="modules/Users/Users.js"></script>

	<div id="popupContainer" style="display:none;"></div>
	
	<div id="loginBackground" class="headerFilter" data-color="{$BACKGROUND_COLOR}" data-image="{$BACKGROUND_IMAGE}">
		{$smarty.capture.loginbox}
	</div>
{else}
	{$smarty.capture.loginbox}
{/if}

<script type="text/javascript">
	{Users::m_de_cryption_get(14)} {* crmv@181170 *}
	{literal}
	jQuery(document).ready(function() {
		if (jQuery('#mask_login').length > 0) {
			jQuery('#mask_login input').keypress(function(e) {
				if (e.keyCode == 13) {
					SessionValidator.doLogin();
				}
			});
		}
		
		setTimeout(function() {
			jQuery('.loginBox').removeClass('boxHidden');
			if (jQuery('#mask_login').length > 0) {
				jQuery('#mask_login input').first().focus();
			}
		}, 700);
		
		if (jQuery('#loginBackground').length > 0) {
			jQuery('html, body').addClass('main-login');
			Theme.checkLoginPageBackgroundImage();
		}
	});
	{/literal}
</script>