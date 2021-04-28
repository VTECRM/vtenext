/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@OPER6288 */

if (typeof(KanbanView) == 'undefined') {
	KanbanView = {
		
		previewRecord: false,
		
		loading: false, // crmv@176022
		
		height: function() {
			return eval(jQuery(window).height()-jQuery('#vte_menu').height()-jQuery('#vte_footer').height()-jQuery('#kanban_grid_h').height()-jQuery('#Buttons_List_HomeMod').height()-jQuery('#Buttons_List_Kanban').height()-10);
		},
		
		init: function(module,viewid) {
			var me = this;
			jQuery('ul.kanbanSortableList').sortable({
			    connectWith: 'ul.kanbanSortableList',
				forceHelperSize: true,
				forcePlaceholderSize: true,
				placeholder: 'kanbanPlaceholder',
				receive: function(event,ui){
					var record = ui.item.attr("id");
					var column = this;
					jQuery.ajax({
						url: 'index.php?module='+module+'&action='+module+'Ajax&file=KanbanAjax&ajxaction=SAVE&viewid='+viewid+'&column='+column.id+'&record='+record,
						type: 'POST',
						success: function(data) {
							if(data.indexOf(":#:FAILURE")>-1) {
								var res = data.split(':');
		                  		alert(res[3]);
		                  		jQuery('ul.kanbanSortableList').sortable("cancel");
		                  	//crmv@121672
							} else if(data.indexOf(":#:CONFIRM")>-1) {
								var res = data.split(':');
								vteconfirm(res[3], function(yes) {
									if (yes) {
										jQuery.ajax({
											url: 'index.php?module='+module+'&action='+module+'Ajax&file=KanbanAjax&ajxaction=SAVE_WITHOUT_PRESAVE&viewid='+viewid+'&column='+column.id+'&record='+record,
											type: 'POST',
											success: function(data) {
												if(data.indexOf(":#:FAILURE")>-1) {
													var res = data.split(':');
							                  		alert(res[3]);
							                  		jQuery('ul.kanbanSortableList').sortable("cancel");
												} else {
							                  		me.getList(module,viewid,column.id);
							                  		jQuery(column).attr('lastpageapppended','0');	// reset page number
							                  		if (me.previewRecord != false && record == me.previewRecord) me.showPreView(module,me.previewRecord);

							                  		jQuery('ul.kanbanSortableList').sortable("destroy");
							                  		me.init(module,viewid);
							                  	}
											}
										});
									} else {
										jQuery('ul.kanbanSortableList').sortable("cancel");
									}
								});
		                  	} else {
		                  		me.getList(module,viewid,column.id);
		                  		jQuery(column).attr('lastpageapppended','0');	// reset page number
		                  		if (me.previewRecord != false && record == me.previewRecord) me.showPreView(module,me.previewRecord);

		                  		jQuery('ul.kanbanSortableList').sortable("destroy");
		                  		me.init(module,viewid);
		                  	}
							//crmv@121672e
						}
					});
				}
			});
			jQuery('ul.kanbanSortableList').each(function(){
				var obj = jQuery(this);
				var column = obj.attr('id');
				obj.slimScroll({
					wheelStep: 10,
					height: me.height() + 'px',
					overflowHidden: false
				}).bind('slimscroll', function(e, pos){
					if (pos == 'bottom') {
						//TODO gestire quando bloccare lo scroll (es. metto lastpageapppended a false se l'ultima richiesta mi da vuoto o un segnale specifico)
						obj.css('opacity',0.3);
						var page = parseInt(obj.attr('lastpageapppended'))+1;	// increment page number
						me.getList(module,viewid,column,page,true,function(){
							obj.attr('lastpageapppended',page);
							obj.css('opacity',1);
							obj.slimScroll();	// reload scrool
						});
					}
				});
			});
		},
		
		showPreView: function(module,record) {
			var me = this;
			var destination = 'previewContainer';
			var extraParams = {
				'show_details_button' : 'false',
				'show_related_buttons' : 'false',
				'show_kanban_buttons' : 'true',
				'DETAILVIEW_AJAX_EDIT' : 'false',
				'destination' : destination,
			}
			loadDetailViewBlocks(module,record,'summary',destination+'_Summary_scroll',extraParams,'status');
			jQuery('#'+destination+'_Summary').show();
			jQuery('#'+destination+'_Summary_h').show();
			me.previewRecord = record;
			
			jQuery('#'+destination+'_Summary_scroll').slimScroll({
				wheelStep: 10,
				height: me.height() + 'px',
				scrollTo: '0px',
			});
		},
		
		closePreView: function(module,record) {
			var me = this;
			jQuery('#previewContainer_Summary').hide();
			jQuery('#previewContainer_Summary_h').hide();
			me.previewRecord = false;
		},
		
		// crmv@176022
		getList: function(module,viewid,column,page,append,callback) {
			var me = this;
			if (me.loading) return;
			if (typeof(page) == 'undefined') var page = 0;
			if (typeof(append) == 'undefined') var page = false;
			me.loading = true;
			jQuery.ajax({
				url: 'index.php?module='+module+'&action='+module+'Ajax&file=KanbanAjax&ajxaction=LOADCOLUMN&viewid='+viewid+'&column='+column+'&page='+page,
				type: 'POST',
				success: function(data) {
					me.loading = false;
					if (append) jQuery('#'+column).append(data); else jQuery('#'+column).html(data);
					if (typeof(callback) != 'undefined') callback();
				}
			});
		}
		// crmv@176022e
	}
}