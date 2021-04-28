/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
function reloadFoldersSettings() {
	location.href = 'index.php?module=Messages&action=MessagesAjax&file=Settings/index&operation=Folders&account='+jQuery('#accountspicklist').val();
}
function setFolderSettings() {
	jQuery('[name="account"]').val(jQuery('#accountspicklist').val());
}
function reloadFiltersSettings() {
	location.href = 'index.php?module=Messages&action=MessagesAjax&file=Settings/index&operation=Filters&account='+jQuery('#accountspicklist').val();
}
function reloadPOP3FoldersSettings() {
	jQuery('#folderpicklistcontainer').html('');
	jQuery.ajax({
		url: 'index.php?module=Messages&action=MessagesAjax&file=Settings/index&operation=FolderPicklist&account='+jQuery('#accountspicklist').val(),
		dataType: 'html',
		success: function(data){
			jQuery('#folderpicklistcontainer').html(data);
		}
	});
}