{*+*************************************************************************************
{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}

{* crmv@173153 *}
 
<!-- Javascript -->
<script src="js/bootstrap.min.js"></script>

<script type="text/javascript" src="js/raty/lib/jquery.raty.js"></script> <!-- Star -->
<script type="text/javascript" src="js/raty/lib/pregis_script_raty.js"></script>
<script type="text/javascript" src="js/general.js"></script>
<script type="text/javascript" src="HelpDesk/HelpDesk.js"></script> {* crmv@160733 *}

<div class="row">
	<div class="col-sm-6 text-left">
		<button class="btn btn-default" type="button" onclick="location.href='index.php?module=HelpDesk&action=index'">{'LBL_BACK_BUTTON'|getTranslatedString}</button>
	</div>
	{if $TICKETSTATUS !='Closed'|getTranslatedString}
		<div class="col-sm-6 text-right">
			<button class="btn btn-primary" name="srch" type="button" value="{'LBL_CLOSE_TICKET'|getTranslatedString}" onClick="location.href='index.php?module=HelpDesk&action=index&fun=close_ticket&ticketid={$TICKETID}'">{'LBL_CLOSE_TICKET'|getTranslatedString}</button>
		</div>
	{/if}
</div>

{if $TICKETSTATUS eq 'Closed'|getTranslatedString}
	<div class="alert alert-success alert-dismissable">
		<button type="button" class="close" data-dismiss="alert">&times;</button>
		{'Closed'|getTranslatedString}
	</div>
{/if}
	
<div class="row fieldrow">
	<div class="col-sm-12">
		<div class="row">
			<form>
				<input type="hidden" name="ticketid" id="ticketid" value="{$TICKETID}">
				{foreach from=$FIELDLIST item=FIELD}
					{foreach from=$FIELD item=VALUE}
						<div class="col-md-12">
							<h3><small>{$VALUE.0}</small></h3>
						</div>
						<div class="col-md-12">
							{if $VALUE.0 eq 'description'|getTranslatedString}
								<h3>{$VALUE.1}</h3>
							{elseif $VALUE.0 eq 'Valutation Support'|getTranslatedString}
								<input id="valuestars" type="hidden" value={$VALUE.1}>
								<div id="stars"></div>
							{elseif $VALUE.0 eq 'Status'|getTranslatedString}
								<input type="hidden" value="{$VALUE.1}" id="status">
								<h3>{$VALUE.1|getTranslatedString}</h3>
							{else}
								<h3>{$VALUE.1|getTranslatedString}</h3>
							{/if}
							<div class="linerow"></div>
						</div>
					{/foreach}
				{/foreach}
			</form>
		</div>
	</div>
</div>

{if $TICKETSTATUS != 'Closed'|getTranslatedString} <!--crmv@57342 -->
	<form name="comments" action="index.php" method="post">
		<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> <!--crmv@171581 -->
		<input type="hidden" name="module" value="HelpDesk">
		<input type="hidden" name="action" value="index">
		<input type="hidden" name="fun" value="detail">
		<input type="hidden" name="ticketid" id="ticketid" value="{$TICKETID}">

		<div class="panel panel-default">
			<div class="panel-heading" class="col-md-8">
				<h4 style="line-height:35px;">
					<i class="material-icons">edit</i>
					{'LBL_ADD_COMMENT'|getTranslatedString}
					<div class="pull-right" id="comments">
						<button class="btn btn-success" style="margin:0px;" title="{'LBL_SUBMIT'|getTranslatedString}" accesskey="S" name="submit" type="submit" onclick="this.form.module.value='HelpDesk';this.form.action.value='index';this.form.fun.value='updatecomment'; if(trim(this.form.comments.value) != '') return true; else return false;">{'LBL_SEND'|getTranslatedString}</button>
					</div>
					<div class="clearfix"></div>
				</h4>
			</div>
			<div class="panel-body">
				<textarea name="comments" class="form-control"></textarea>
			</div>
		</div>
	</form>
{/if}

<div class="panel panel-default">
	<div class="panel-heading">
		<h4 style="line-height:35px;">
			<i class="material-icons">chat</i>
			{'LBL_TICKET_COMMENTS'|getTranslatedString}
			<span class="badge">{$BADGE}</span>
			<div class="pull-right" id="comments">
				<button id="comments-close" type="button" class="btn btn-default" style="margin:0px;">
					<i class="material-icons">arrow_downward</i>
				</button>
			</div>
			<div class="clearfix"></div>
		</h4>
	</div>
	<div class="panel-body" id="panel-comments">
		{if !empty($COMMENTS)}
			<ul class="timeline">
				{foreach from=$COMMENTS item=comm} {* crmv@160733 *}
					{if $comm.owner eq 'portal'}
					<li>
						<div class="timeline-badge users">
							<i class="fa fa-check"></i>
						</div>
						<div class="timeline-panel">
							<div class="timeline-heading">
								<h4 class="timeline-title">{$comm.num} {'LBL_COMMENT_BY'|getTranslatedString} : {$comm.owner}</h4> {* crmv@160733 *}
								<p>
									<small class="text-muted">
										<i class="fa fa-clock-o"></i>
									 	{$comm.createdtime}
									</small>
								</p>
							</div>
							<div class="timeline-body">
								<p>{$comm.comments}</p> {* crmv@160733 *}
							</div>
						</div>
					</li>
					{else}
					<li class="timeline-inverted">
						<div class="timeline-badge vtecrm">
							<i class="fa-vtecrm"></i>
						</div>
						<div class="timeline-panel">
							<div class="timeline-heading">
								<h4 class="timeline-title">{$comm.num} {'LBL_COMMENT_BY'|getTranslatedString} : {$comm.owner}</h4> {* crmv@160733 *}
								<p>
									<small class="text-muted">
										<i class="fa fa-clock-o"></i>
										{$comm.createdtime}
									</small>
								</p>
							</div>
							<div class="timeline-body">
								<p>{$comm.comments}</p> {* crmv@160733 *}
							</div>
						</div>
					</li>
					{/if}
				{/foreach}
			</ul>
		{else}
			<b>{'LBL_NO_COMMENTS'|getTranslatedString}</b>
		{/if}
	</div>
</div>

{* crmv@160733 *}
<div class="modal fade" tabindex="-1" role="dialog" id="provideConfInfo">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">{"LBL_CONFIDENTIAL_INFO"|getTranslatedString}</h4>
			</div>
			<div class="modal-body">
				<form name="confinfo_form" id="confinfo_form" action="index.php" method="post">
					<input type="hidden" name="module" value="HelpDesk">
					<input type="hidden" name="action" value="index">
					<input type="hidden" name="fun" value="provideconfinfo">
					<input type="hidden" name="ticketid" id="ticketid" value="{$TICKETID}">
					<input type="hidden" name="confinfo_commentid" id="confinfo_commentid" />
					<p>{'LBL_CONFIDENTIAL_INFO_REPLY_DESC'|getTranslatedString}</p>
					<div class="form-group">
						<label for="confinfo_more">{"LBL_CONFIDENTIAL_REQUEST"|getTranslatedString}</label>
						<textarea id="confinfo_more" name="confinfo_more" class="form-control uneditable-input" rows="3" readonly=""></textarea>
					</div>
					<br>
					<div class="form-group">
						<label for="confinfo_data">{"LBL_CONFIDENTIAL_RESPONSE"|getTranslatedString}</label>
						<textarea id="confinfo_data" name="confinfo_data" class="form-control" rows="10"></textarea>
					</div>
					<br>
					<div class="form-group">
						<label for="confinfo_data_comment">{"LBL_COMMENT"|getTranslatedString} ({"LBL_WONT_BE_ENCRYPTED"|getTranslatedString})</label>
						<textarea id="confinfo_data_comment" name="confinfo_data_comment" class="form-control" rows="3"></textarea>
					</div>
					<br>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-danger" data-dismiss="modal">{'LBL_CANCEL'|getTranslatedString}</button>
				<button type="button" class="btn btn-success" onclick="VTEPortal.HelpDesk.ConfidentialInfo.provideInfo('HelpDesk', '{$TICKETID}');">{'LBL_SAVE'|getTranslatedString}</button>
			</div>
		</div>
	</div>
</div>
{* crmv@160733e *}

{if $FILES.0 != "#MODULE INACTIVE#"}
	<!--  Added for Attachment -->
	<div class="panel panel-default">
		<div class="panel-heading">
			<h4 style="line-height:35px;">
				<i class="material-icons">cloud_upload</i>
				{'LBL_ATTACHMENTS'|getTranslatedString}
			</h4>
		</div>
		<div class="panel-body">
			<table class="table">
				<thead>
					<tr>
						<th>{'LBL_ATTACHMENT_NAME'|getTranslatedString}</th>
					</tr>
				</thead>
				<tbody>
					{foreach from=$FILES item=FILE}
						<tr>
							<td>
								{if !empty($FILE.filelocationtype)}
									{if $FILE.filelocationtype eq "I" || $FILE.filelocationtype eq "B"}
										<a href="index.php?downloadfile=true&fileid={$FILE.fileid}&filename={$FILE.filename}&filetype={$FILE.filetype}&filesize={$FILE.filesize}&ticketid={$TICKETID}">
											{$FILE.filename}
										</a>
									{/if}
									{if $FILE.filelocationtype eq "E"}
										<a href="{$FILE.filename}">{$FILE.filename}</a>
									{/if}
								{else}
									{'NO_ATTACHMENTS'|getTranslatedString}
								{/if}
							</td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		</div>
	</div>
{/if}

{if !empty($UPLOADSTATUS)}
	<div class="row">
		<div class="col-sm-12">
			<div class="alert alert-danger alert-dismissable uploader">
				<button type="button" class="close" data-dismiss="alert">&times;</button>
				{'LBL_FILE_UPLOADERROR'|getTranslatedString} {$UPLOADSTATUS}
			</div>
		</div>
	</div>
{/if}


{if $TICKETSTATUS != 'Closed'|getTranslatedString && $FILES.0 != "#MODULE INACTIVE#"}
	<form name="fileattachment" method="post" enctype="multipart/form-data" action="index.php" onsubmit="return VTEPortal.DropArea.HelpDesk.onFileAttachSubmit(this);">
		<input type="hidden" name="module" value="HelpDesk">
		<input type="hidden" name="action" value="index">
		<input type="hidden" name="fun" value="uploadfile">
		<input type="hidden" name="ticketid" value="{$TICKETID}">

		<div class="panel panel-default">
			<div class="panel-heading">
				<h4 style="line-height:35px;">
					<i class="material-icons">cloud_upload</i>
					Allega
				</h4>
			</div>
			<div class="panel-body">
				<div class="row">
					<div class="col-sm-12">
						<div class="alert alert-info">{'LBL_DROP_INFO'|getTranslatedString}</div>
						<input type="file" name="customerfile[]" class="detailedViewTextBox form-control" multiple />
						<button class="btn btn-primary" name="Attach" type="submit">{'LBL_ATTACH'|getTranslatedString}</button>
					</div>
				</div>
			</div>
		</div>
	</form>
{/if}

{include file="DropArea.tpl"}

{literal}
<script type="text/javascript">
	jQuery(document).ready(function() {
		// Closes Open comments
		jQuery("#comments-close").click(function(e) {
			e.preventDefault();
			jQuery("#panel-comments").toggleClass("active");
		});

		// Stars crmv@80441
		if (jQuery('#valuestars').length > 0) {
			var punteggio = document.getElementById("valuestars").value;
			var status = document.getElementById("status").value;
			
			if (punteggio == 0.00 && (status == 'Closed' || status == 'Chiuso' || status == 'Richiesta risolta')) {
				readOnlyValue = false;
			}else{
				readOnlyValue = true;
			}
			
			jQuery('#stars').raty({
				path: 'js/raty/lib/img/',
				score: punteggio,
				readOnly: readOnlyValue,
			});
			
			if (punteggio == 0.00 && (status == 'Closed' || status == 'Chiuso' || status == 'Richiesta risolta')) {
				var ticketid = document.getElementById("ticketid").value; 
				jQuery('#stars').raty({
					path: 'js/raty/lib/img',
					click: function(score, evt) {
						var res = getFile('index.php?mode=saveTicketStars&valutation_support='+score+'&ticketid='+ticketid);
						jQuery('#stars').raty({
							path: 'js/raty/lib/img/',
							readOnly: true,
						});
					}
				});
			}
		}
		// crmv@80441e
	});
</script>
{/literal}