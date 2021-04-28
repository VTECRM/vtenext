{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@161554 crmv@163697 *}

<form class="form-horizontal" id="gdpr_form" name="gdpr_form" method="POST" action="index.php?module=Settings&action=SettingsAjax&file=GDPRConfig&parentTab=Settings">

	<input type="hidden" name="mode" value="save" />
	<input type="hidden" name="business_id" value="{$BUSINESS_ID}" />
	
	<table border="0" width="100%">
		<tr>
			<td align="right" width="90%">
				<span id="gdpr_busy" style="display:none;">{include file="LoadingIndicator.tpl"}</span>
			</td>
			<td align="right" nowrap="">
				<button title="{$APP.LBL_SAVE_BUTTON_LABEL}" accessKey="{$APP.LBL_SAVE_BUTTON_KEY}" class="crmbutton small save" type="submit">{$APP.LBL_SAVE_BUTTON_LABEL}</button>
				<button title="{$APP.LBL_CANCEL_BUTTON_LABEL}" accessKey="{$APP.LBL_CANCEL_BUTTON_KEY}" class="crmbutton small cancel" type="button" onclick="window.history.back();">{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
			</td>
		</tr>
	</table>
	
	<br>

	<fieldset>
		<legend>{$MOD.LBL_WEBSERVICE}</legend>
		
		<div class="form-group">
			<label class="control-label col-sm-3" for="webservice_endpoint">{$MOD.LBL_WEBSERVICE_ENDPOINT}</label>
			<div class="col-sm-5">
				<input type="text" name="webservice_endpoint" class="form-control" id="webservice_endpoint" value="{$GDPR_INFO.webservice_endpoint}">
			</div>
			<div class="col-sm-4">
				<p class="form-control-static">{$MOD.LBL_WEBSERVICE_ENDPOINT_DESC}</p>
			</div>
		</div>
		
		<div class="form-group">
			<label class="control-label col-sm-3" for="webservice_username">{$MOD.LBL_WEBSERVICE_USERNAME}</label>
			<div class="col-sm-5">
				<input type="text" name="webservice_username" class="form-control" id="webservice_username" value="{$GDPR_INFO.webservice_username}">
			</div>
			<div class="col-sm-4">
				<p class="form-control-static">{$MOD.LBL_WEBSERVICE_USERNAME_DESC}</p>
			</div>
		</div>
		
		<div class="form-group">
			<label class="control-label col-sm-3" for="webservice_accesskey">{$MOD.LBL_WEBSERVICE_ACCESSKEY}</label>
			<div class="col-sm-5">
				<input type="text" name="webservice_accesskey" class="form-control" id="webservice_accesskey" value="{$GDPR_INFO.webservice_accesskey}">
			</div>
			<div class="col-sm-4">
				<p class="form-control-static">{$MOD.LBL_WEBSERVICE_ACCESSKEY_DESC}</p>
			</div>
		</div>
		
		<div class="form-group">
			<label class="control-label col-sm-3" for="default_language">{$MOD.LBL_DEFAULT_LANGUAGE}</label>
			<div class="col-sm-5">
				<select name="default_language" class="form-control" id="default_language">
					<option value="en" {if $GDPR_INFO.default_language eq 'en'}selected{/if}>{$MOD.LBL_ENGLISH_LANG}</option>
					<option value="it" {if $GDPR_INFO.default_language eq 'it'}selected{/if}>{$MOD.LBL_ITALIAN_LANG}</option>
				</select>
			</div>
			<div class="col-sm-4">
				<p class="form-control-static">{$MOD.LBL_DEFAULT_LANGUAGE_DESC}</p>
			</div>
		</div>
		
		<div class="form-group">
			<label class="control-label col-sm-3" for="webservice_accesskey">{$MOD.LBL_WEBSITE_LOGO}</label>
			<div class="col-sm-5">
				<input type="text" name="website_logo" class="form-control" id="website_logo" value="{$GDPR_INFO.website_logo}">
			</div>
			<div class="col-sm-4">
				<p class="form-control-static">{$MOD.LBL_WEBSITE_LOGO_DESC}</p>
			</div>
		</div>
		
		<div class="form-group">
			<label class="control-label col-sm-3" for="sender_name">{$MOD.LBL_SENDER_NAME}</label>
			<div class="col-sm-5">
				<input type="text" name="sender_name" class="form-control" id="sender_name" value="{$GDPR_INFO.sender_name}">
			</div>
			<div class="col-sm-4">
				<p class="form-control-static">{$MOD.LBL_SENDER_NAME_DESC}</p>
			</div>
		</div>
		
		<div class="form-group">
			<label class="control-label col-sm-3" for="sender_email">{$MOD.LBL_SENDER_EMAIL}</label>
			<div class="col-sm-5">
				<input type="text" name="sender_email" class="form-control" id="sender_email" value="{$GDPR_INFO.sender_email}">
			</div>
			<div class="col-sm-4">
				<p class="form-control-static">{$MOD.LBL_SENDER_EMAIL_DESC}</p>
			</div>
		</div>
		
		<div class="form-group">
			<label class="control-label col-sm-3" for="noconfirm_deletion_months">{$MOD.LBL_NOCONFIRM_DELETION_MOTHS}</label>
			<div class="col-sm-5">
				<input type="text" name="noconfirm_deletion_months" class="form-control" id="noconfirm_deletion_months" value="{$GDPR_INFO.noconfirm_deletion_months}">
			</div>
			<div class="col-sm-4">
				<p class="form-control-static">{$MOD.LBL_NOCONFIRM_DELETION_MOTHS_DESC}</p>
			</div>
		</div>
	</fieldset>
	
	<fieldset>
		<legend>{$MOD.LBL_TEMPLATES}</legend>
		
		{foreach from=$GDPR_INFO.templates key=gdpr_template_name item=gdpr_template}
			<div class="form-group">
				<label class="control-label col-sm-3" for="{$gdpr_template_name}">{$gdpr_template.label}</label>
				<div class="col-sm-5">
					<select name="{$gdpr_template_name}" class="form-control" id="{$gdpr_template_name}">
						<option value="0">{$APP.LBL_NONE}</option>
						{foreach from=$EMAIL_TEMPLATES item=template}
							<option value="{$template.id}" {if $gdpr_template.id eq $template.id}selected{/if}>
								{$template.name}
							</option>
						{/foreach}
					</select>
				</div>
				<div class="col-sm-4">
					<p class="form-control-static">{$gdpr_template.description}</p>
				</div>
			</div>
		{/foreach}
	</fieldset>

	<fieldset>
		<legend>{$MOD.LBL_PRIVACY_POLICY}</legend>
		<div class="form-group">
			<div class="col-sm-12">
				<textarea name="privacy_policy" class="form-control" id="privacy_policy">{$GDPR_INFO.privacy_policy}</textarea>
			</div>
		</div>
	</fieldset>

</form>

<script type="text/javascript">
	GDPRConfig.initEditView();
</script>