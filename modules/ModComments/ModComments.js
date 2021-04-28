/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@43864	crmv@43050	crmv@43448	crmv@55506 */

function commentsLinkModule(module, crmid, entityname) {
	var parentid = jQuery('#from_crmid').val(),
		parent_win = parent.popup_opener;

	if (parentid > 0) {
		//crmv@179773
		VTE.ModCommentsCommon.checkAddComment(null, crmid, {'commentid': parentid}, function(parent_permissions){
			linkModules('ModComments', parentid, module, crmid, {},
				function (data) {
					var uikey = jQuery('#uikey_from').val(),
						commentid = jQuery('#from_crmid').val();
	
					if (uikey && commentid && parent_win) {
						parent_win.VTE.ModCommentsCommon.setParentPermissions(uikey, null, commentid, parent_permissions, function(){
							parent_win.VTE.ModCommentsCommon.reloadComment(uikey, null, commentid);
						});
					}
					closePopup();
				}
			);
		});
		//crmv@179773e
	} else {
		// I am creating a new comment
		var container = parent_win.jQuery('#editareaModComm');
		container.find('.commentAddLink').hide();
		container.find('#ModCommentsParentId').val(crmid);
		container.find('#ModCommentsNewRelatedLabel').show();
		if (entityname) {
			container.find('#ModCommentsNewRelatedName').html(entityname).show();
		}
		closePopup();
	}
}

function commentsCreateModule(module) {
	/*
	LPOP.create(module, function(mod, recordid) {
		if (recordid > 0) {
			commentsLinkModule(mod, recordid);
			return false;
		}
	});
	*/
	LPOP.create(module, {});
}