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
				<h2>{'op_denied_welcome'|_T:$EMAIL}</h2>
			</div>
			<div class="row">
				<div class="col-md-5 mx-auto">
					<span class="border-top my-3"></span>
					<div class="card special-skill-item border-0">
						<div class="card-header bg-transparent border-0" style="margin-bottom:20px;">
							<i class="icon ion-ios-person-outline"></i>
						</div>
						<div class="card-body">
							<h3 class="card-title">{'op_denied_subtitle'|_T}</h3>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
</main>

{include file="Footer.tpl"}