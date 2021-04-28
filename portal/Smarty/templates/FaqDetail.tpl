{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
 
{* crmv@185488 *}

<div class="row rowbotton">
	<div class="col-md-10  col-sm-5 col-xs-4">
		<button align="left" class="btn btn-default" type="button" value="{'LBL_BACK_BUTTON'|getTranslatedString}" onclick="location.href='index.php?module=Faq&action=index'"/>{'LBL_BACK_BUTTON'|getTranslatedString}</button>
	</div>	
</div>

<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
	<div class="col-md-12">
		<h3><small>{'LBL_FAQ_TITLE'|getTranslatedString} {'LBL_FAQ_DETAIL'|getTranslatedString}</small></h3>
	</div>
	<div class="col-md-12 linerow ">
		<h3>{$QUESTION}</h3>
	</div>
	<div class="col-md-12">
		<h3><small>{'LBL_ANSWER'|getTranslatedString}</small></h3>
	</div>
	<div class="col-md-12 linerow ">
		<h3>{$ANSWER}</h3>
	</div>
</div>

<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" style="margin-top:20px;">
	<div class="panel panel-default">
		<div class="panel-heading">
			<h4 style="line-height:35px;">
				<i class="material-icons">insert_drive_file</i>
				{'LBL_DOCUMENTS'|getTranslatedString}
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
					{* crmv@185488 *}
					{if is_array($DOCUMENTS)}
						{* crmv@200139 *}
						{foreach from=$DOCUMENTS.1.Documents.data key=num item=document}
							<tr><td>{$document.1.fielddata}</td></tr>
						{* crmv@200139e *}
						{/foreach}
					{/if}
					{* crmv@185488e *}
				</tbody>
			</table>
		</div>
	</div>
</div>

<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
	<div class="panel panel-default">
		<div class="panel-heading">
			<h4 style="line-height:35px;">
				<i class="material-icons">chat</i>
				{'LBL_COMMENTS'|getTranslatedString}
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
				{foreach from=$COMMENTS key=num item=comment}
					<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 linerow">
						<h4>{$comment.comment}</h4>
						<br><span class="hdr">{'LBL_ADDED_ON'|getTranslatedString}{$comment.date}</span>
					</div>
				{/foreach}
			{else}
				<b>{'LBL_NO_COMMENTS'|getTranslatedString}</b>
			{/if}
		</div>
	</div>
</div>

<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
	<form name="comments" method="POST" action="index.php">
		<input type="hidden" name="module">
		<input type="hidden" name="action">
		<input type="hidden" name="fun">
		<input type=hidden name=faqid value="{$FAQID}">

		<div class="panel panel-default">
			<div class="panel-heading">
				<h4 style="line-height:35px;">
					<i class="material-icons">edit</i>
					{'LBL_ADD_COMMENT'|getTranslatedString}
					<div class="pull-right" id="comments">
						<button class="btn btn-success" style="margin:0px;" title="Invia" accesskey="S" name="submit" type="submit" onclick="this.form.module.value='Faq';this.form.action.value='index';this.form.fun.value='faq_updatecomment'; if(trim(this.form.comments.value) != '') return true; else return false;"/>Invia</button>
					</div>
					<div class="clearfix"></div>
				</h4>
			</div>
			<div class="panel-body">
				<textarea name="comments" class="form-control"></textarea>
			</div>
		</div>
	</form>
</div>

<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
	{$PAGEOPTION}
</div>

<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
	{$LIST}
</div>