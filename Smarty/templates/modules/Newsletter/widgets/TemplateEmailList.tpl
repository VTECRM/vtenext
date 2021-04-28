{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}

{* crmv@126696 *}

<script type="text/javascript" src="{"modules/Newsletter/Newsletter.js"|resourcever}"></script>
<script type="text/javascript" src="modules/Settings/ProcessMaker/resources/ActionTaskScript.js"></script> 

{if $SHOW_CANCEL}
	<table class="small" border="0" cellspacing="0" cellpadding="0" width="100%">
		<tr>
			<td align="right">
				<button type="button" class="crmbutton cancel" onclick="closePopup()">{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
			</td>
		</tr>
	</table>
{/if}

<div class="container-fluid" style="margin-top:15px;">
	<div class="row">
		<div class="col-sm-12">
			<table class="vtetable">
				<thead>
					<tr>
						{if $ENABLE_PREVIEW}
						<th width="5%">{$MOD.LBL_PREVIEW}</th>
						{/if}
						<th width="35%">{"LBL_TEMPLATE_NAME"|getTranslatedString:"Users"|trim}</th>
						<th width="60%">{$APP.LBL_DESCRIPTION}</th>
					</tr>
				</thead>
				<tbody>
					{foreach item="TEMPLATE" from=$TEMPLATES}
						<tr>
							{if $ENABLE_PREVIEW}
								<td><i class="vteicon md-link" title="{$APP.LBL_SHOW_PREVIEW}" onclick="{$PREVIEW_FUNCTION}({$RECORD},{$TEMPLATE.templateid},'{$TEMPLATE.templatename}');">remove_red_eye</i></td>
							{/if}
							<td><a href='javascript:{$SELECT_FUNCTION}({$RECORD},{$TEMPLATE.templateid},"{$TEMPLATE.templatename}");'>{$TEMPLATE.templatename}</a></td>
							<td>{$TEMPLATE.description}</td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		</div>
	</div>
</div>

{literal}
<script type="text/javascript">
jQuery(document).ready(function() {
	loadedPopup();
});
</script>
{/literal}