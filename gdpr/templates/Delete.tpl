{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}

{* crmv@161554 *}

{include file="Header.tpl"}
 
{include file="NavbarIn.tpl"}

<main class="page">
	<section class="portfolio-block skills" style="padding-bottom:70px;padding-top:70px;">
		<div class="container">
			<div class="heading" style="margin-bottom:50px;">
				<h2>{'delete_title'|_T}</h2>
			</div>
			<div class="row">
				<div class="col-md-5 mx-auto">
					<div class="card special-skill-item border-0">
						<div class="card-header bg-transparent border-0">
							<i class="icon ion-android-delete"></i>
						</div>
						<div class="card-body">
							<h3 class="card-title">{'delete_subtitle'|_T}</h3>
							<p class="card-text" style="margin-bottom:40px;">{'delete_description'|_T}</p>
							<form id="delete-form" style="max-width:initial;">
								<input type="hidden" name="accesstoken" value="{$ACCESS_TOKEN}" />
								<button class="btn btn-primary btn-lg" id="delete-button" type="button" onclick="VTGDPR.process('deleteContact');">{'delete_confirm_button'|_T}</button>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
</main>

{include file="Footer.tpl"}