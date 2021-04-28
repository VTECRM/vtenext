{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@173271 *}

<form name="comments" action="index.php" method="post">
	<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> <!--crmv@171581 -->
	<input type="hidden" name="module" value="HelpDesk">
	<input type="hidden" name="action" value="index">
	<input type="hidden" name="fun" value="detail">
	<input type="hidden" name="ticketid" id="ticketid" value="{$ID}">

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
				    <input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> <!--crmv@171581 -->
					<input type="hidden" name="module" value="HelpDesk">
					<input type="hidden" name="action" value="index">
					<input type="hidden" name="fun" value="provideconfinfo">
					<input type="hidden" name="ticketid" id="ticketid" value="{$ID}">
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
				<button type="button" class="btn btn-success" onclick="VTEPortal.HelpDesk.ConfidentialInfo.provideInfo('HelpDesk', '{$ID}');">{'LBL_SAVE'|getTranslatedString}</button>
			</div>
		</div>
	</div>
</div>
{* crmv@160733e *}