{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
 
{* crmv@168573 *}
 
<div class="notes-wrapper">
	<div class="notes-loader hidden">
		<div class="lds-ring"><div></div><div></div><div></div><div></div></div>
	</div>
	<div class="notes-list-header">
		<div class="notes-list-header-content">
			<div class="notes-header-left">
				<i class="note-header-btn material-icons active switch-view switch-carousel-button" title="{'LBL_SLIDER'|getTranslatedString}" data-toggle="tooltip" data-placement="right">view_carousel</i>
				<i class="note-header-btn material-icons switch-view switch-list-button" title="{'LBL_LIST'|getTranslatedString}" data-toggle="tooltip" data-placement="right">view_list</i>
			</div>
			<div class="notes-header-title">
				<span class="title"></span>
			</div>
			<div class="btn btn-primary note-action-btn create-note hidden"><i class="material-icons">add</i><span>{'LBL_CREATE_BUTTON_LABEL'|getTranslatedString}</span></div> {* crmv@180154 *}
			<div class="btn btn-primary note-action-btn save-note hidden"><i class="material-icons">save</i><span>{'LBL_SAVE_BUTTON_LABEL'|getTranslatedString}</span></div> {* crmv@180154 *}
		</div>
	</div>
	<div class="notes-list-container">
		<!-- Create View -->
		<div class="notes-create-view hidden">
			<div class="notes-create-title">
				<i class="material-icons close-create-view">close</i>
			</div>
			<div class="notes-create-content">
				<form id="notes-create-form" name="notes-create-form" class="" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"> {* crmv@188147 *} 
					<input type="hidden" name="module" value="MyNotes">
					<input type="hidden" name="action" value="Save">
					<input type="hidden" name="record" value="">
					<input type="hidden" name="mode" value="">
					<input type="hidden" name="parent" value="{$PARENT_RECORD}">
					<input type="hidden" name="sub_mode" value="DetailViewMyNotesWidget">
					<input type="hidden" name="assigned_user_id" value="{$CURRENT_USER_ID}">
					<div class="form-group">
						<input type="text" id="notes-subject" class="form-control notes-subject-field" name="subject" placeholder="{'LBL_SUBJECT'|getTranslatedString}" maxlength="{$MAX_TEXTLENGTH}"> {* crmv@180154 *}
					</div>
					<div class="form-group">
						<textarea id="notes-description" class="form-control notes-description-field" name="description" placeholder="{'LBL_DESCRIPTION'|getTranslatedString:'APP_STRINGS'}"></textarea> {* crmv@180154 *}
					</div>
				</form>
			</div>
		</div>
		
		<!-- Carousel View -->
		<div class="carousel slide notes-slider hidden">
			<!-- Indicators -->
			<ol class="carousel-indicators"></ol> 
			<div class="carousel-inner"></div>
			<!-- Left and right controls -->
			<a class="left carousel-control">
				<span class="glyphicon glyphicon-chevron-left"></span>
			</a>
			<a class="right carousel-control">
				<span class="glyphicon glyphicon-chevron-right"></span>
			</a>
		</div>
		
		<!-- List View -->
		<div class="notes-list hidden"></div>
		
		<!-- Empty View -->
		<div class="vte-collection-empty hidden empty-notes">
			<div class="collection-item">
				<div class="circle">
					<i class="vteicon nohover">description</i>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Templates -->

<div id="note-element-tpl" class="note-element" data-note-id="" style="display:none;">
	<div class="note-element-title"></div>
	<div class="note-element-description wrap-content"></div>
	<div class="note-element-footer">
		<div class="note-element-picture">
			<img src="" alt="" title="" class="userAvatar">
		</div>
		<div class="note-element-user-timestamp">
			<div class="note-element-user"></div>
			<div class="note-element-timestamp"></div>
		</div>
		<div class="note-element-operation">
			<i class="note-element-btn material-icons edit-note hidden" title="{'LBL_EDIT'|getTranslatedString}" data-toggle="tooltip">edit</i>
			<i class="note-element-btn material-icons delete-note hidden" title="{'LBL_DELETE'|getTranslatedString}" data-toggle="tooltip">delete</i>
			<i class="note-element-btn material-icons link-note hidden" title="{'LBL_CONVERT_ACTION'|getTranslatedString}" data-toggle="tooltip">launch</i>
		</div>
	</div>
</div>

<script type="text/javascript" src="{"modules/MyNotes/MyNotes.js"|resourcever}"></script>

<script type="text/javascript">
	var data = null;
	{if $DATA}
		data = {$DATA|replace:"'":"\'"};
	{else}
		data = {ldelim}{rdelim};
	{/if}
	window.VTE.MyNotesWidget.noteList = data['list'];
	window.VTE.MyNotesWidget.permissions = data['permissions'];
	window.VTE.MyNotesWidget.parentRecord = {$PARENT_RECORD};
	window.VTE.MyNotesWidget.maxSubjectLength = parseInt('{$MAX_TEXTLENGTH}');
	window.VTE.MyNotesWidget.labels = {ldelim}
		'delete_confirm_message': "{'NTC_DELETE_CONFIRMATION'|getTranslatedString}",
		'notes': "{'MyNotes'|getTranslatedString}",
		'list': "{'LBL_LIST'|getTranslatedString}",
		'slider': "{'LBL_SLIDER'|getTranslatedString}",
	{rdelim};
	window.VTE.MyNotesWidget.init();
</script>