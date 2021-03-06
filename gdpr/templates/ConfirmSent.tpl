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
				<h2>{'confirm_sent_title'|_T}</h2>
			</div>
			<div class="row">
				<div class="col-md-5 mx-auto">
					<div class="card special-skill-item border-0">
						<div class="card-header bg-transparent border-0">
							<i class="icon ion-email-unread"></i>
						</div>
						<div class="card-body">
							<h3 class="card-title">{'confirm_sent_subtitle'|_T:$CONTACT_EMAIL}</h3>
							<p class="card-text" style="margin-bottom:40px;">{'confirm_sent_description'|_T}</p>
							<a class="btn btn-primary btn-lg" href="index.php?action=detailview&accesstoken={$ACCESS_TOKEN|urlencode}">{'confirm_sent_mainpage'|_T}</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
</main>

{include file="Footer.tpl"}