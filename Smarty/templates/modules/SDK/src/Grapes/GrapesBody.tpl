{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@197575 crmv@205899 *}

{* crmv@202326 *}
{SDK::checkJsLanguage()}
{include file='CachedValues.tpl'}
{* crmv@202326e *}

<div id="gjs-container">
	<div id="gjs"></div>
</div>

<textarea id="old_content" style="display:none">{$CONTENT}</textarea>

<script src="include/js/grapesjs/jquery-3.4.1.min.js"></script>
<script src="include/js/grapesjs/popper.min.js"></script>
<script src="include/js/grapesjs/bootstrap.min.js"></script>
<script src="include/js/grapesjs/grapes.min.js"></script>
<script src="include/js/grapesjs/grapesjs-preset-newsletter.min.js"></script>
<script src="include/js/grapesjs/grapesjs-image-manager.min.js"></script>	<!-- crmv@201352 -->
<script src="{"include/js/general.js"|resourcever}"></script>
<script src="modules/SDK/src/Grapes/Grapes.js"></script>

<script type="text/javascript">
	{if $ALL_VARIABLES}
		VTE.GrapesEditor.templateVariables = {$ALL_VARIABLES|replace:"'":"\'"};
	{else}
		VTE.GrapesEditor.templateVariables = {ldelim}{rdelim};
	{/if}

	var current_endpoint = window.location.href.split('?')[0];	//crmv@201352
	current_endpoint = current_endpoint.replace('index.php', '');

	VTE.GrapesEditor.tpl_id = '{$TPL_ID}';
	VTE.GrapesEditor.images_uploaded = '{$IMAGES_UPLOADED}';
	VTE.GrapesEditor.upload_endpoint = '{$UP_ENDPOINT}';
	VTE.GrapesEditor.vte_csrf_token = '{$CSRF_TOKEN}';
	VTE.GrapesEditor.images_folder = '{$IMAGES_FOLDER}';
	VTE.GrapesEditor.vte_site_url = current_endpoint; //'{$SITE_URL}';	//crmv@201352

	VTE.GrapesEditor.initialize();

	{if $TPL_SUBJECT neq ''}
		parent.jQuery('#nlw_template_subject').val("{$TPL_SUBJECT|replace:'"':'\"'}"); {* crmv@201988 *}
	{/if}
</script>