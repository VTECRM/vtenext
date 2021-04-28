{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@167019 *}
{* crmv@176893 *}

<form class="droparea-form" id="saveDocumentForm" name="saveDocumentForm" enctype="multipart/form-data">
	<input type="hidden" name="module" value="{$MODULE}" /> 
	<input type="hidden" name="action" value="Save" /> 
	<input type="hidden" name="mode" value="" /> 
	<input type="hidden" name="return_module" value="{$PARENT_MODULE}" /> 
	<input type="hidden" name="return_id" value="{$PARENT_RECORD}" /> 
	<input type="hidden" name="return_action" value="DetailView" /> 
	<input type="hidden" name="return_entity_name" value="{$PARENT_ENTITYNAME}" />
	<input type="hidden" name="filelocationtype" value="B" />
	<input type="hidden" name="backend_name" value="file" />
	<input type="hidden" name="bu_mc" value="{$BUMC}" />
	<input type="hidden" name="parentid" value="{$PARENT_RECORD}" /> {* crmv@184480 *}

	<div class="form-group">
		<label class="control-label" for="doc_title">{'Title'|getTranslatedString:'Documents'}</label>
		<input type="text" class="form-control" id="doc_title" name="doc_title">
	</div>
	<div class="form-group">
		<label class="control-label" for="doc_folder">{'Folder Name'|getTranslatedString:'Documents'}</label>
		<div class="folder-field-wrapper">
			<input class="form-control field-with-tools" type="text" id="doc_folder" name="doc_folder" /> 
			<input type="hidden" id="doc_folder_id" name="doc_folder_id" />
			<div class="folder-field-tools">
				<i class="vteicon md-link" data-toggle="tooltip" title="{'LBL_SELECT'|getTranslatedString}" id="list-doc-folders">view_list</i> 
				<i class="vteicon md-link" data-toggle="tooltip" title="{'LBL_CLEAR'|getTranslatedString}" id="reset-doc-folders">highlight_off</i>
				<div id="add-folder-dropdown" class="dropdown add-folder-dropdown">
					<i class="vteicon md-link add-doc-folder" id="add-doc-folder" title="{'LBL_ADD_NEW_FOLDER'|getTranslatedString}" data-toggle="dropdown">add</i>
					<div class="add-doc-folder-loader hidden" id="add-doc-folder-loader">
						{include file="LoadingIndicator.tpl"}
					</div>
					<div id="add-folder-menu" class="dropdown-menu dropdown-menu-right add-folder-menu">
						<div class="add-folder">
							<div class="form-group">
								<label class="control-label" for="new_folder_name">{'Title'|getTranslatedString:'Documents'}</label> 
								<input type="text" class="form-control" id="new_folder_name" name="new_folder_name">
							</div>
							<div class="form-group">
								<label class="control-label" for="new_folder_desc">{'Description'|getTranslatedString:'Documents'}</label>
								<textarea class="form-control" id="new_folder_desc" name="new_folder_desc" style="resize:vertical;"></textarea>
							</div>
							<div class="text-center">
								<button type="button" class="btn btn btn-primary save-add-folder">{'LBL_CREATE'|getTranslatedString}</button>
							</div>
							<div id="add-folder-loader" class="add-folder-loader hidden">{include file="LoadingIndicator.tpl"}</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="form-group">
		<label class="control-label" for="doc_description">{'Description'|getTranslatedString:'Documents'}</label>
		<textarea class="form-control" id="doc_description" name="doc_description" style="min-height:150px;resize:vertical;"></textarea>
	</div>
	<div class="form-group">
		<label class="control-label" for="assigned_user">{'LBL_ASSIGNED_TO'|getTranslatedString}</label>
		<div class="assignee-field-wrapper">
			<input class="form-control field-with-tools" type="text" id="assigned_user" name="assigned_user" /> 
			<input type="hidden" id="assigned_user_id" name="assigned_user_id" />
			<div class="assignee-field-tools">
				<i class="vteicon md-link" data-toggle="tooltip" title="{'LBL_SELECT'|getTranslatedString}" id="list-assignee">view_list</i> 
				<i class="vteicon md-link" data-toggle="tooltip" title="{'LBL_CLEAR'|getTranslatedString}" id="reset-assignee">highlight_off</i>
				<div class="dropdown assignee-type">
					<div class="assignee-toggle" data-toggle="dropdown">
						<i class="vteicon md-link">person</i>
						<span class="caret"></span>
					</div>
					<ul class="dropdown-menu">
						<li class="dropdown-item" data-type="user"><a href="javascript:void(0);"><i class="vteicon md-link">person</i> {'LBL_USER'|getTranslatedString}</a></li>
						<li class="dropdown-item" data-type="group"><a href="javascript:void(0);"><i class="vteicon md-link">group</i> {'LBL_GROUP'|getTranslatedString}</a></li>
					</ul>
				</div>
			</div>
		</div>
	</div>
	<div class="form-group checkboxes-wrapper">
		<div class="checkbox">
			<label><input type="checkbox" name="filestatus" checked />&nbsp;{'Active'|getTranslatedString:'Documents'}</label> 
			&nbsp;&nbsp; 
			<label><input type="checkbox" name="active_portal" />&nbsp;{'Portal Active'|getTranslatedString:'Documents'}</label>
		</div>
	</div>
</form>

<script type="text/javascript" src="{"modules/Documents/DropAreaForm.js"|resourcever}"></script>

<script type="text/javascript">
(function() {ldelim}
	{if $FOLDERS}
		var availableFolders = {$FOLDERS|replace:"'":"\'"};
	{else}
		var availableFolders = {ldelim}{rdelim};
	{/if}
	{if $USER_LIST}
		var availableUsers = {$USER_LIST|replace:"'":"\'"};
	{else}
		var availableUsers = {ldelim}{rdelim};
	{/if}
	{if $GROUP_LIST}
		var availableGroups = {$GROUP_LIST|replace:"'":"\'"};
	{else}
		var availableGroups = {ldelim}{rdelim};
	{/if}
	
	if (window.DropAreaForm) {ldelim}
		DropAreaForm.availableFolders = availableFolders;
		DropAreaForm.availableUsers = availableUsers;
		DropAreaForm.availableGroups = availableGroups;
		
		var empty_str = '{"LBL_SEARCH_STRING"|getTranslatedString}';
		DropAreaForm.emptyString = empty_str;
	{rdelim}
{rdelim})();
</script>

{literal}
<script type="text/javascript">
	jQuery(document).ready(function() {
		DropAreaForm.initialize();
	});
</script>
{/literal}