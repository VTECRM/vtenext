{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@191351 *}

{literal}
<style>
	.container-messages-outofoffice {
		margin-top: 15px;
	}
	.form-messages-outofoffice .checkbox .checkbox-material {
		position: relative;
		top: 0px;
	}
	.form-messages-outofoffice .checkbox {
		padding-top: 7px;
	}
	.form-messages-outofoffice .checkbox.allday-checkbox,
	.form-messages-outofoffice .checkbox.enddate-checkbox {
		padding-top: 3px;
	}
	.form-messages-outofoffice .date-field-container {
		display: flex;
		flex-direction: row;
		justify-content: space-between;
		align-items: center;
	}
	.form-messages-outofoffice .control-label {
		padding-top: 7px;
	}
</style>
{/literal}

<script type="text/javascript" src="modules/Messages/Settings/OutOfOffice.js"></script>
<script type="text/javascript" src="include/ckeditor/ckeditor.js"></script>

<div class="container container-messages-outofoffice">
	<form name="OutOfOffice" action="index.php" class="form-horizontal form-messages-outofoffice">
		<input type="hidden" name="module" value="Messages" />
		<input type="hidden" name="action" value="MessagesAjax" />
		<input type="hidden" name="file" value="Settings/index" />
		<input type="hidden" name="operation" value="SaveOutOfOffice" />

		<div class="form-group">
			<label class="control-label col-sm-3 col-xs-2">Fuori ufficio</label>
			<div class="col-sm-9 col-xs-10">
				<div class="radio radio-primary">
					<label>
						<input type="radio" name="active" value="1" {if $SETTINGS.active}checked{/if} /> Attiva
					</label>
					<label>
						<input type="radio" name="active" value="0" {if !$SETTINGS.active}checked{/if} /> Disattiva 
					</label>
				</div>
			</div>
		</div>
		<div class="form-messages-outofoffice-data" style="display:none;">
			<div class="form-group">
				<label class="control-label col-sm-3">Oggetto</label>
				<div class="dvtCellInfo col-sm-9">
					<input type="text" class="detailedViewTextBox" id="message_subject" name="message_subject" value="{$SETTINGS.message_subject}" />
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-sm-3">Messaggio di risposta automatica</label>
				<div class="col-sm-9">
					<textarea class="detailedViewTextBox" id="message_body" name="message_body">{$SETTINGS.message_body}</textarea>
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-sm-3">Periodo di tempo</label>
				<div class="col-sm-9">
					<div class="row">
						<div class="col-xs-2 form-control-static text-right">
							<strong>Inizio</strong>
						</div>
						<div class="dvtCellInfo col-xs-3 date-field-container">
							{include file="EditViewUI.tpl" uitype=$START_DATE_HTML.0.0 fldname=$START_DATE_HTML.2.0 fldvalue=$START_DATE_HTML.3.0 secondvalue=$START_DATE_HTML.3.1 fldlabel=$START_DATE_HTML.1.0 DIVCLASS="dvtCellInfo" NOLABEL=true}
						</div>
						<div class="dvtCellInfo col-xs-3" id="start_time_container">
							{include file="EditViewUI.tpl" uitype=$START_TIME_HTML.0.0 fldname=$START_TIME_HTML.2.0 fldvalue=$START_TIME_HTML.3.0 fldlabel=$START_TIME_HTML.1.0 DIVCLASS="dvtCellInfo" NOLABEL=true}
						</div>
						<div class="col-xs-4">
							<div class="checkbox allday-checkbox">
								<label>
									<input type="checkbox" id="start_date_allday" name="start_date_allday" {if $SETTINGS.start_date_allday}checked{/if} /> Intera giornata
								</label>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-xs-2">
							<div class="checkbox enddate-checkbox text-right">
								<label>
									<input type="checkbox" id="end_date_active" name="end_date_active" {if $SETTINGS.end_date_active}checked{/if} /> Fine
								</label>
							</div>
						</div>
						<div class="form-messages-outofoffice-enddate-data" style="display:none;">
							<div class="dvtCellInfo col-xs-3 date-field-container">
								{include file="EditViewUI.tpl" uitype=$END_DATE_HTML.0.0 fldname=$END_DATE_HTML.2.0 fldvalue=$END_DATE_HTML.3.0 secondvalue=$END_DATE_HTML.3.1 fldlabel=$END_DATE_HTML.1.0 DIVCLASS="dvtCellInfo" NOLABEL=true}
							</div>
							<div class="dvtCellInfo col-xs-3" id="end_time_container">
								{include file="EditViewUI.tpl" uitype=$END_TIME_HTML.0.0 fldname=$END_TIME_HTML.2.0 fldvalue=$END_TIME_HTML.3.0 fldlabel=$END_TIME_HTML.1.0 DIVCLASS="dvtCellInfo" NOLABEL=true}
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-sm-3">Rispondi solo a indirizzi conosciuti</label>
				<div class="col-sm-9">
					<div class="checkbox">
						<label>
							<input type="checkbox" id="only_known_addresses_active" name="only_known_addresses_active" {if $SETTINGS.only_known_addresses_active}checked{/if} />
						</label>
					</div>
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-sm-3">Attiva per account</label>
				<div class="col-sm-9">
					<select class="detailedViewTextBox" id="accounts" name="accounts[]" multiple size="5">
						{foreach from=$ACCOUNTS item=account}
							<option value="{$account.id}" {if $account.id|in_array:$SETTINGS.accounts}selected{/if}>{$account.name}</option>
						{/foreach}
					</select>
				</div>
			</div>
		</div>
	</form>
</div>

{literal}
<script type="text/javascript">
	jQuery(document).ready(function() {
		CKEDITOR.replace('message_body');
		VTE.Messages.Settings.FormOutOfOffice.initialize();
	});
</script>
{/literal}