{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}

{* crmv@161554 *}

	<section class="portfolio-block website gradient" style="padding-bottom:70px;padding-top:70px;">
		<div class="container">
			<div class="row align-items-center">
				<div class="col-md-12 col-lg-5 offset-lg-1">
					<h3>{'footer_title'|_T}</h3>
					<p>{'footer_subtitle'|_T}</p>
				</div>
				<div class="col-md-12 col-lg-5">
					<div class="portfolio-laptop-mockup">
						<div class="screen">
							<div class="screen-content" style="background-image:url('assets/img/gdpr-desk-background.png');"></div>
						</div>
						<div class="keyboard"></div>
					</div>
				</div>
			</div>
		</div>
	</section>

	<footer class="page-footer">
		<div class="container">
			<div class="links">
				<span>&copy; 2008-{'Y'|date}</span>
				<a href="http://www.vtenext.com/">vtenext.com</a>
			</div>
		</div>
	</footer>

	<div class="modal fade" role="dialog" tabindex="-1" id="support-request-modal">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title">{'support_request_title'|_T}</h4>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<p>{'support_request_subtitle'|_T}</p>
					<form id="support-request-form" style="max-width:initial;">
						<input type="hidden" name="cid" value="{$CONTACT_ID}" />
						<div class="form-group">
							<div class="form-row">
								<div class="col-md-6 col-md-12">
									<label for="support-request-subject">{'support_request_subject'|_T}</label>
									<input class="form-control" name="request_subject" id="support-request-subject" type="text">
								</div>
							</div>
						</div>
						<div class="form-group">
							<div class="form-row">
								<div class="col-md-6 col-md-12">
									<label for="support-request-description">{'support_request_description'|_T}</label>
									<textarea class="form-control" name="request_description" id="support-request-description" style="min-height:150px;"></textarea>
								</div>
							</div>
						</div>
					</form>
				</div>
				<div class="modal-footer">
					<button class="btn btn-light" type="button" data-dismiss="modal">{'support_request_close_button'|_T}</button>
					<button class="btn btn-primary" type="button" onclick="VTGDPR.process('supportRequest');">{'support_request_send_button'|_T}</button>
				</div>
			</div>
		</div>
	</div>

	<div id="loader">
		<div class="d-flex h-100 align-items-center">
			<div class="sk-circle mx-auto">
				<div class="sk-circle1 sk-child"></div>
				<div class="sk-circle2 sk-child"></div>
				<div class="sk-circle3 sk-child"></div>
				<div class="sk-circle4 sk-child"></div>
				<div class="sk-circle5 sk-child"></div>
				<div class="sk-circle6 sk-child"></div>
				<div class="sk-circle7 sk-child"></div>
				<div class="sk-circle8 sk-child"></div>
				<div class="sk-circle9 sk-child"></div>
				<div class="sk-circle10 sk-child"></div>
				<div class="sk-circle11 sk-child"></div>
				<div class="sk-circle12 sk-child"></div>
			</div>
		</div>
	</div>

	<script src="assets/js/jquery.min.js"></script>
	<script src="assets/bootstrap/js/bootstrap.min.js"></script>
	<script src="assets/js/theme.js"></script>
	<script src="assets/js/vtenext-gdpr.js"></script>
	
	<script type="text/javascript">
	{if $TRANSLATIONS}
		var LANG = {$TRANSLATIONS|replace:"'":"\'"};
	{else}
		var LANG = {ldelim}{rdelim};
	{/if}
	</script>
	
	{if !empty($JS_SCRIPT)}
		{$JS_SCRIPT}
	{/if}
</body>

</html>