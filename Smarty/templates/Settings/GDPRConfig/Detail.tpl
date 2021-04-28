{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@161554 crmv@163697 *}

<br>

<div class="row">
	<div class="col-sm-12">

		{if $BUSINESS_UNIT_ENABLED}
			<div class="panel">
				<div class="panel-heading">
					<h4>{$MOD.LBL_GENERAL_SETTINGS}</h4>
				</div>
				<div class="panel-body">
					<form id="general-settings" class="form-horizontal text-left">
						<div class="form-group">
							<label class="control-label col-sm-3">{$MOD.LBL_DEFAULT_BUSINESS_UNIT}</label>
							<div class="col-sm-5">
								<select class="form-control" name="default_business" onchange="GDPRConfig.saveGeneralSettings();">
									{foreach from=$BUSINESS_UNIT item=bunit}
										<option value="{$bunit.organizationid}" {if $GDPR_INFO.default_business eq $bunit.organizationid}selected{/if}>{$bunit.organizationname}</option>
									{/foreach}
								</select>
							</div>
							<div class="col-sm-4">
								<p class="form-control-static">{$MOD.LBL_DEFAULT_BUSINESS_UNIT_DESC}</p>
							</div>
						</div>
					</form>
				</div>
			</div>
		{/if}
		
		<form class="form-inline text-right">
			<span id="gdpr_busy" style="display:none;">{include file="LoadingIndicator.tpl"}</span>
			<div class="form-group">
				{if $BUSINESS_UNIT_ENABLED}
					<select class="form-control" name="business_id" onchange="GDPRConfig.loadBusiness(this);" style="margin-right:10px;">
						{foreach from=$BUSINESS_UNIT item=bunit}
							<option value="{$bunit.organizationid}" {if $BUSINESS_ID eq $bunit.organizationid}selected{/if}>{$bunit.organizationname}</option>
						{/foreach}
					</select>
					&nbsp;
				{/if}
				<input type="button" class="small crmbutton create" value="{$APP.LBL_EDIT_BUTTON}" title="{$APP.LBL_EDIT_BUTTON}" onclick="GDPRConfig.editGDPR('{$BUSINESS_ID}');" />
			</div>
		</form>
		
		<br>

		<div class="panel">
			<div class="panel-heading">
				<h4>{$MOD.LBL_WEBSERVICE}</h4>
			</div>
			<div class="panel-body">
				<form class="form-horizontal">
					<div class="form-group">
						<label class="control-label col-sm-3">{$MOD.LBL_WEBSERVICE_ENDPOINT}</label>
						<div class="col-sm-9">
							<p class="form-control-static">{$GDPR_INFO.webservice_endpoint}</p>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-sm-3">{$MOD.LBL_WEBSERVICE_USERNAME}</label>
						<div class="col-sm-9">
							<p class="form-control-static">{$GDPR_INFO.webservice_username}</p>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-sm-3">{$MOD.LBL_WEBSERVICE_ACCESSKEY}</label>
						<div class="col-sm-9">
							<p class="form-control-static">{$GDPR_INFO.webservice_accesskey}</p>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-sm-3">{$MOD.LBL_DEFAULT_LANGUAGE}</label>
						<div class="col-sm-9">
							<p class="form-control-static">{$GDPR_INFO.default_language}</p>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-sm-3">{$MOD.LBL_WEBSITE_LOGO}</label>
						<div class="col-sm-9">
							<p class="form-control-static">{$GDPR_INFO.website_logo}</p>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-sm-3">{$MOD.LBL_SENDER_NAME}</label>
						<div class="col-sm-9">
							<p class="form-control-static">{$GDPR_INFO.sender_name}</p>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-sm-3">{$MOD.LBL_SENDER_EMAIL}</label>
						<div class="col-sm-9">
							<p class="form-control-static">{$GDPR_INFO.sender_email}</p>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-sm-3">{$MOD.LBL_NOCONFIRM_DELETION_MOTHS}</label>
						<div class="col-sm-9">
							<p class="form-control-static">{$GDPR_INFO.noconfirm_deletion_months}</p>
						</div>
					</div>
				</form>
			</div>
		</div>

		<div class="panel">
			<div class="panel-heading">
				<h4>{$MOD.LBL_TEMPLATES}</h4>
			</div>
			<div class="panel-body">
				<form class="form-horizontal">
					{foreach from=$GDPR_INFO.templates item=gdpr_template}
						<div class="form-group">
							<label class="control-label col-sm-3">{$gdpr_template.label}</label>
							<div class="col-sm-9">
								<p class="form-control-static">{$gdpr_template.name}</p>
							</div>
						</div>
					{/foreach}
				</form>
			</div>
		</div>

		<div class="panel">
			<div class="panel-heading">
				<h4>{$MOD.LBL_PRIVACY_POLICY}</h4>
			</div>
			<div class="panel-body">{$GDPR_INFO.privacy_policy}</div>
		</div>
		
	</div>
</div>