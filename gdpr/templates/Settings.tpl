{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}

{* crmv@161554 *}

{include file="Header.tpl"}
 
{include file="NavbarIn.tpl"}

<main class="page">
	<section class="portfolio-block" style="padding-bottom:70px;padding-top:70px;">
		<div class="container">
			{if !$PRIVACY_POLICY_CONFIRMED}
				<div class="alert alert-danger" role="alert" style="margin-bottom:3rem;">
					<h4 class="alert-heading">{'settings_warning'|_T}</h4>
					<span>
						<strong>{'settings_warning_message'|_T}</strong>
						<a class="alert-link text-primary" href="index.php?action=privacy&cid={$CONTACT_ID|urlencode}" target="_blank">{'settings_privacypolicy_click'|_T}</a>
					</span>
				</div>
			{/if}
			<div class="heading" style="margin-bottom:30px;">
				<h2>{'settings_title'|_T}</h2>
			</div>
			<form id="settings-form">
				<input type="hidden" name="accesstoken" value="{$ACCESS_TOKEN}" />
				<div class="form-group">
					<div class="form-row">
						<div class="col-md-12">
							<p class="switch-label">
								{'settings_gdpr_privacypolicy'|_T}
								<a href="index.php?action=privacy&cid={$CONTACT_ID|urlencode}" target="_blank">{'settings_privacypolicy_click'|_T}</a>
							</p>
							<label class="switch">
								<input type="checkbox" name="gdpr_privacypolicy" onclick="VTGDPR.checkPrivacyPolicy(this);" {$SETTINGS_DATA.gdpr_privacypolicy}>
								<span class="slider round"></span>
							</label>
						</div>
					</div>
					<div class="form-row">
						<div class="col-md-12">
							<p class="switch-label">{'settings_gdpr_personal_data'|_T}</p>
							<label class="switch">
								<input type="checkbox" name="gdpr_personal_data" {$SETTINGS_DATA.gdpr_personal_data}>
								<span class="slider round"></span>
							</label>
						</div>
					</div>
					<div class="form-row">
						<div class="col-md-12">
							<p class="switch-label">{'settings_gdpr_marketing'|_T}</p>
							<label class="switch">
								<input type="checkbox" name="gdpr_marketing" {$SETTINGS_DATA.gdpr_marketing}>
								<span class="slider round"></span>
							</label>
						</div>
					</div>
					<div class="form-row">
						<div class="col-md-12">
							<p class="switch-label">{'settings_gdpr_thirdparties'|_T}</p>
							<label class="switch">
								<input type="checkbox" name="gdpr_thirdparties" {$SETTINGS_DATA.gdpr_thirdparties}>
								<span class="slider round"></span>
							</label>
						</div>
					</div>
					<div class="form-row">
						<div class="col-md-12">
							<p class="switch-label">{'settings_gdpr_profiling'|_T}</p>
							<label class="switch">
								<input type="checkbox" name="gdpr_profiling" {$SETTINGS_DATA.gdpr_profiling}>
								<span class="slider round"></span>
							</label>
						</div>
					</div>
					<div class="form-row">
						<div class="col-md-12">
							<p class="switch-label">{'settings_gdpr_restricted'|_T}</p>
							<label class="switch">
								<input type="checkbox" name="gdpr_restricted" {$SETTINGS_DATA.gdpr_restricted}>
								<span class="slider round"></span>
							</label>
						</div>
					</div>
					<div class="form-row">
						<div class="col-md-12">
							<p class="switch-label">{'settings_gdpr_notifychange'|_T:$CONTACT_EMAIL}</p>
							<label class="switch">
								<input type="checkbox" name="gdpr_notifychange" {$SETTINGS_DATA.gdpr_notifychange}>
								<span class="slider round"></span>
							</label>
						</div>
					</div>
				</div>
				<div style="margin-top:30px;text-align:center;">
					<button class="btn btn-primary btn-lg {if !$PRIVACY_POLICY_CONFIRMED}d-none{/if}" id="save-settings-button" type="button" style="margin-right:5px;" onclick="VTGDPR.process('saveSettings');">{'settings_save_button'|_T}</button>
					<button class="btn btn-primary btn-lg {if !$PRIVACY_POLICY_CONFIRMED}d-none{/if}" id="cancel-settings-button" type="button" style="margin-left:5px;" onclick="VTGDPR.process('cancelSettings');">{'settings_cancel_button'|_T}</button>
				</div>
			</form>
		</div>
	</section>
</main>

{include file="Footer.tpl"}