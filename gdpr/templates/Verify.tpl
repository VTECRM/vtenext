{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}

{* crmv@161554 *}

{include file="Header.tpl"}

{include file="NavbarOut.tpl"}

<main class="page">
	<section class="portfolio-block skills" style="padding-bottom:70px;padding-top:70px;">
		<div class="container">
			<div class="heading">
				<h2>{'verify_welcome'|_T:$CONTACT_EMAIL}</h2>
			</div>
			<div class="row">
				<div class="col-md-5 mx-auto">
					<div class="card special-skill-item border-0">
						<div class="card-header bg-transparent border-0">
							<i class="icon ion-email"></i>
						</div>
						<div class="card-body">
							<h3 class="card-title">{'verify_title'|_T}</h3>
							<p class="card-text">{'verify_subtitle'|_T}</p>
							<form id="verify-form">
								<input type="hidden" name="cid" value="{$CONTACT_ID}" />
								<input type="hidden" name="authtoken" value="{$AUTH_TOKEN}" />
								<button class="btn btn-primary btn-lg" type="button" onclick="VTGDPR.process('verify');">{'verify_send_button'|_T}</button>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
</main>

{include file="Footer.tpl"}