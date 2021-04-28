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
			<div class="heading" style="margin-bottom:50px;">
				<h2>{'merge_title'|_T:$CONTACT_EMAIL}</h2>
				<h4>{'merge_subtitle'|_T}</h4>
			</div>
			<div class="row">
				<div class="col-md-12 col-lg-12">
					<div class="project-card-no-image">
						<div class="table-responsive">
							<table id="merge-contacts" class="table">
								<thead>
									<tr>
										<th>#</th>
										<th>{'merge_contact'|_T}</th>
										<th></th>
									</tr>
								</thead>
								<tbody>
									{foreach from=$CONTACT_DUPLICATES item=contact}
										<tr class="merge-row" onclick="VTGDPR.checkMainContact(this);">
											<td>
												<input type="radio" data-contactid="{$contact.crmid}" onclick="VTGDPR.checkMainContact(this);">
											</td>
											<td>
												<span>{$contact.entityname}</span>
												{if $contact.suggested}
													<span class="badge badge-success">{'merge_suggested'|_T}</span>
												{/if}
											</td>
											<td>
												<button class="btn btn-primary btn-sm float-right" type="button" data-toggle="collapse" data-target="#accordation-{$contact.crmid}">{'merge_details'|_T}</button>
											</td>
										</tr>
										<tr id="accordation-{$contact.crmid}" class="collapse">
											<td colspan="3">
												<div>
													<div class="table-responsive">
														<table class="table">
															<thead>
																<tr>
																	<th>{'merge_field'|_T}</th>
																	<th>{'merge_value'|_T}</th>
																</tr>
															</thead>
															<tbody>
																{foreach from=$contact.details item=detailField}
																	<tr>
																		<td>{$detailField.fieldlabel}</td>
																		<td>{$detailField.value}</td>
																	</tr>
																{/foreach}
															</tbody>
														</table>
													</div>
												</div>
												<br>
											</td>
										</tr>
									{/foreach}
								</tbody>
							</table>
						</div>
						<div style="text-align:center;margin:20px 0px;">
							<form id="merge-form" style="max-width:initial;">
								<input type="hidden" name="accesstoken" value="{$ACCESS_TOKEN}" />
								<button class="btn btn-primary btn-lg d-none" id="merge-button" type="button" onclick="VTGDPR.process('mergeContact');">{'merge_next_button'|_T}</button>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
</main>

{include file="Footer.tpl"}