{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<script type="text/javascript" src="modules/Import/resources/Import.js"></script>

{include file='Buttons_List1.tpl'}

<div class="container mainContainer pt-5">
	<div class="row">
		<div class="col-sm-10 col-sm-offset-1">
			<div class="vte-card">
				<form onsubmit="VteJS_DialogBox.block();" action="index.php" enctype="multipart/form-data" method="POST" name="importStatusForm">
					<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
					<input type="hidden" name="module" value="{$FOR_MODULE}" />
					<input type="hidden" name="action" value="Import" />
					{if $CONTINUE_IMPORT eq 'true'}
						<input type="hidden" name="mode" value="continue_import" />
					{else}
						<input type="hidden" name="mode" value="" />
					{/if}
				</form>
				<div class="row">
					<div class="col-sm-12">
						<div class="dvInnerHeader mb-5">
							<div class="dvInnerHeaderTitle">
								{'LBL_IMPORT'|@getTranslatedString:$MODULE} {$FOR_MODULE|@getTranslatedString:$FOR_MODULE} - 
								<span class="style1">{'LBL_RUNNING'|@getTranslatedString:$MODULE} ... </span>
							</div>
						</div>
					</div>
				</div>
				{if $ERROR_MESSAGE neq ''}
					<div class="row">
						<div class="col-sm-12 style1">
							{$ERROR_MESSAGE}
						</div>
					</div>
				{/if}
				<div class="row">
					<div class="col-sm-12">
						<table class="vtetable vtetable-props mb-3 mx-auto" style="width:50%">
							<tr>
								<td class="cellLabel text-nowrap">{'LBL_TOTAL_RECORDS_IMPORTED'|@getTranslatedString:$MODULE}</td>
								<td class="cellText">{$IMPORT_RESULT.IMPORTED} / {$IMPORT_RESULT.TOTAL}</td>
							</tr>
							<tr>
								<td class="cellLabel text-nowrap">{'LBL_NUMBER_OF_RECORDS_CREATED'|@getTranslatedString:$MODULE}</td>
								<td class="cellText">{$IMPORT_RESULT.CREATED}</td>
							</tr>
							<tr>
								<td class="cellLabel text-nowrap">{'LBL_NUMBER_OF_RECORDS_UPDATED'|@getTranslatedString:$MODULE}</td>
								<td class="cellText">{$IMPORT_RESULT.UPDATED}</td>
							</tr>
							<tr>
								<td class="cellLabel text-nowrap">{'LBL_NUMBER_OF_RECORDS_SKIPPED'|@getTranslatedString:$MODULE}</td>
								<td class="cellText">{$IMPORT_RESULT.SKIPPED}</td>
							</tr>
							<tr>
								<td class="cellLabel text-nowrap">{'LBL_NUMBER_OF_RECORDS_MERGED'|@getTranslatedString:$MODULE}</td>
								<td class="cellText">{$IMPORT_RESULT.MERGED}</td>
							</tr>
						</table>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-12 text-center my-3">
						<button type="button" name="cancel" class="crmbutton delete" onclick="location.href='index.php?module={$FOR_MODULE}&action=Import&mode=cancel_import&import_id={$IMPORT_ID}'">
							{'LBL_CANCEL_IMPORT'|@getTranslatedString:$MODULE}
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

{literal}
<script type="text/javascript">
jQuery(document).ready(function() {
	setTimeout(function() {
		jQuery("[name=importStatusForm]").get(0).submit();
		}, 5000); // crmv@90169
});
</script>
{/literal}