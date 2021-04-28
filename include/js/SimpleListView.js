/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@43611 crmv@43864 crmv@56603 */

SLV = {
	search_submitted: [],
	loading: [],

	show_activity: function(listid) {
		var listCont = jQuery('#SLVContainer_'+listid);

		listCont.find('#slv_busy_indicator').show();
		listCont.find('#SLVGreyLayer').css({
			'width': listCont.width(),
			'height': listCont.height(),
		}).show();
	},

	hide_activity: function(listid) {
		var listCont = jQuery('#SLVContainer_'+listid);

		listCont.find('#SLVGreyLayer').hide();
		listCont.find('#slv_busy_indicator').hide();
	},

	load: function(listid, module, viewid, searchstr, page, sortcol, sortdir) {
		var me = this,
			listCont = jQuery('#SLVContainer_'+listid),
			search_plc = listCont.find('#search_placeholder').val(),
			extraInputs = me.get_extra_inputs(listid),
			selids = me.get_selected_ids(listid) || [];

		if (!viewid) viewid = '';
		if (!searchstr || searchstr == search_plc) searchstr = '';
		if (!page) page = 1;
		if (!sortcol) sortcol = '';
		if (!sortdir) sortdir = '';

		var postParams = {
			'listid' : listid,
			'mod' : module,
			'viewid' : viewid,
			'searchstr' : searchstr,
			'page' : page,
			'sortcol' : sortcol,
			'sortdir' : sortdir,
			'selected_ids' : selids.join(':'),
		};
		jQuery.extend(postParams, extraInputs);

		me.show_activity(listid);
		me.loading[listid] = true;
		jQuery.ajax({
			url: 'index.php?module=Utilities&action=UtilitiesAjax&file=SimpleListViewAjax',
			type: 'POST',
			data: jQuery.param(postParams),
			complete: function() {
				me.hide_activity(listid);
				me.loading[listid] = false;
			},
			success: function(data) {
				listCont.hide();
				listCont.html(data).fadeIn('fast');
			}
		});
	},

	get_module: function(listid) {
		var listCont = jQuery('#SLVContainer_'+listid),
			module = listCont.find('#mod').val();
		return module;
	},

	add_selected: function(listid) {
		var crmids = this.get_selected_ids(listid);

		// now crmids contains a list of ids
		// you can call this function from your js to optain the id list
		return crmids;
	},

	get_extra_inputs: function(listid) {
		var listCont = jQuery('#SLVContainer_'+listid),
			extraInputs = listCont.find('.extraInputs input'),
			ret = {};

		extraInputs.each(function(index, item) {
			ret[item.name] = item.value;
		});
		return ret;
	},

	change_filter: function(listid) {
		var listCont = jQuery('#SLVContainer_'+listid),
			module = listCont.find('#mod').val(),
			viewid = listCont.find('#viewname').val(),
			search = listCont.find('#basic_search_text').val();
		
		// removed, so it works like vte 4, remembering the selection even when changing filter
		//this.remove_all_selected_ids(listid);
		return this.load(listid, module, viewid, search);
	},

	change_sorting: function(listid, sortcol, sortdir) {
		if (!sortdir) sortdir = 'ASC';

		var listCont = jQuery('#SLVContainer_'+listid),
			module = listCont.find('#mod').val(),
			viewid = listCont.find('#viewname').val(),
			search = listCont.find('#basic_search_text').val();

		return this.load(listid, module, viewid, search, 1, sortcol, sortdir);
	},

	go_to_page: function(listid, page) {
		var listCont = jQuery('#SLVContainer_'+listid),
			module = listCont.find('#mod').val(),
			viewid = listCont.find('#viewname').val(),
			currPage = parseInt(listCont.find('#navigationPageOrig').val()),
			totPages = parseInt(listCont.find('#navigationPageTotal').val()),
			search_plc = listCont.find('#search_placeholder').val(),
			search = listCont.find('#basic_search_text').val();
			
		// crmv@107991
		var sortcol = listCont.find('input[name=slv_sortcol]').val();
		var sortdir = listCont.find('input[name=slv_sortdir]').val();
		// crmv@107991e

		if (page === undefined || page === null || page === '') {
			// get the page from, the text box
			page = parseInt(listCont.find('#navigationPage').val());
		}

		page = Math.max(1, Math.min(parseInt(page), totPages));
		if (page == currPage) return false;

		if (search == search_plc) search = '';
		this.load(listid, module, viewid, search, page, sortcol, sortdir); // crmv@107991
		return false;
	},

	go_to_next_page: function(listid) {
		var listCont = jQuery('#SLVContainer_'+listid),
			currPage = parseInt(listCont.find('#navigationPageOrig').val());
		return this.go_to_page(listid, currPage + 1);
	},

	go_to_prev_page: function(listid) {
		var listCont = jQuery('#SLVContainer_'+listid),
			currPage = parseInt(listCont.find('#navigationPageOrig').val());
		return this.go_to_page(listid, currPage - 1);
	},

	search: function(listid) {
		var listCont = jQuery('#SLVContainer_'+listid),
			module = listCont.find('#mod').val(),
			viewid = listCont.find('#viewname').val(),
			search = listCont.find('#basic_search_text').val();

		this.load(listid, module, viewid, search);
		this.search_submitted[listid] = true;

		return false;
	},

	clear_search: function(listid) {
		var listCont = jQuery('#SLVContainer_'+listid),
			jelem = listCont.find('#basic_search_text'),
			rest = jQuery.data(jelem.get(), 'restored');
		if (rest === undefined || rest == true) {
			jelem.val('');
			listCont.find('#basic_search_icn_canc').show();
			jQuery.data(jelem.get(), 'restored', false);
		}
		return false;
	},

	restore_search: function(listid, text) {
		var listCont = jQuery('#SLVContainer_'+listid),
			jelem = listCont.find('#basic_search_text');

		if (this.loading[listid] === true) return false;

		if (jelem.val() == '') {
			jQuery('#basic_search_icn_canc').hide();
			jQuery.data(jelem.get(), 'restored', true);
			if (this.search_submitted[listid] === true) {
				this.search(listid);
			}
			jelem.val(text);
		}
		return false;
	},

	cancel_search: function(listid, text) {
		var listCont = jQuery('#SLVContainer_'+listid),
			jelem = listCont.find('#basic_search_text');

		listCont.find('#basic_search_text').val('');
		this.restore_search(listid, text);
		if (this.search_submitted[listid] === true) {
			//this.change_filter(listid);
			this.search_submitted[listid] = false;
	 	}
		return false;
	},

	// this function should be replaced with custom handler
	select: function(listid, module, crmid, entityname) {
		//window.alert('click on '+crmid);
	},

	create_new: function(listid) {
		//window.alert('create new record');
	},
	
	get_selected_ids: function(listid) {
		var cont = jQuery('#SLVPersistentCont_'+listid).find('input[name=selected_ids]');
		if (cont.length == 0) var idlist = [];	//crmv@58410
		else var idlist = cont.val().split(':') || [];
		
		if (idlist.length == 1 && !idlist[0]) idlist = [];
		return idlist;
	},
	
	add_selected_id: function(listid, crmid) {
		var cont = jQuery('#SLVPersistentCont_'+listid).find('input[name=selected_ids]'),
			idlist = this.get_selected_ids(listid);
		
		if (idlist.indexOf(crmid) == -1) {
			idlist.push(crmid);
			cont.val(idlist.join(':'));
		}
	},
	
	remove_selected_id: function(listid, crmid) {
		var cont = jQuery('#SLVPersistentCont_'+listid).find('input[name=selected_ids]'),
			idlist = this.get_selected_ids(listid),
			idx = idlist.indexOf(crmid);
		
		if (idx >= 0) {
			idlist.splice(idx, 1);
			cont.val(idlist.join(':'));
		}
	},
	
	remove_all_selected_ids: function(listid) {
		var cont = jQuery('#SLVPersistentCont_'+listid).find('input[name=selected_ids]');
		cont.val('');
	},
	
	click_checkbox: function(listid, crmid, self) {
		if (self.checked) {
			this.add_selected_id(listid, crmid);
		} else {
			this.remove_selected_id(listid, crmid);
		}
	},
	
	click_tdcheckbox: function(listid, crmid, self) {
		jQuery(self).find('input').prop('checked', !jQuery('#list_cbox_'+crmid).prop('checked'));
		this.click_checkbox(listid, crmid, jQuery('#list_cbox_'+crmid).get(0));
	},

};