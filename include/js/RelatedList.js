/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@25809 crmv@3085m crmv@104975 crmv@192033 */

function alignTabRelated(panelid, goto) {
	var showPanel = panelBlocks[panelid],
		relids = showPanel ? showPanel.relatedids : [],
		cont = jQuery('#RLContents');

	// 1. hide the relations I shouldn't see
	// 2. show the ones that I can see and that are already present
	// 3. load the other ones that i can see, but are not in the page
	// 4. sort them!
	// 5. go to the desired one

	var shown = [];
		
	cont.find(">div[relation_id]").each(function(idx, el) {
		var $el = jQuery(el),
			relid = parseInt($el.data('relationid'));

		if ($el.data('isfixed')) {
			var hidden = $el.is(':hidden');
			if (!hidden && relids.indexOf(relid) < 0) {
				// 1 (hide)
				$el.hide();
			} else if (hidden && relids.indexOf(relid) >= 0) {
				// 2 (show)
				$el.show();
				shown.push(relid);
			}
		}
	});
	
	// 3 load missing
	var missing = jQuery(relids).not(shown).get();
	
	if (missing && missing.length > 0) {
		var calls = [];
		for (var i=0; i<missing.length; ++i) {
			calls.push(loadFixedRelated(missing[i]));
		}
		jQuery.when.apply(this, calls).then(function() {
			sortFixedRelated();
		});
	} else {
		sortFixedRelated();
	}
	
	// 4 (sort)
	function sortFixedRelated() {
		var last = null;

		for (var i=0; i<relids.length; ++i) {
			var relid = relids[i];
			var relcont = cont.find(">div[relation_id="+relid+"]");
			if (relcont.length > 0) {
				if (last == null) {
					// move at the beginning
					cont.prepend(relcont);
				} else {
					// move after the last
					relcont.insertAfter(last);
				}
				last = relcont;
			}
		}
		
		// 5 (goto)
		if (goto) {
			goToRelated(goto);
		}
	}
}

function loadFixedRelated(relid) {
	var fixcont = jQuery('#RLContents'),
		dyncont = jQuery('#DynamicRelatedList'),
		turbocont = jQuery('#turboLiftRelationsContainer'),
		turborel = turbocont.find('li[relation_id='+relid+']'),
		handler = turborel.attr('onclick');

	if (!handler) {
		return jQuery.Deferred().resolve();
	}

	// parse the handler!
	var matches = handler.match(/^([a-z0-9_.]+)\((.*)\)/i);
	var fn = matches[1];

	// crmv@165297
	// parse the arguments using a state machine, since the params might contains commas
	// assume quotation marks are not present inside arguments
	var args = [],
		s = false,
		b = false;
	for (var i=0; i<matches[2].length; ++i) {
		var c = matches[2][i];
		switch (c) {
			case ',': // delimiter
				if (!s) {
					args.push(b);
					b = '';
				} else {
					if (b === false) b = '';
					b += c;
				}
				break;
			case '"': // string delimiters
			case "'":
				s = !s;
			default: // other chars
				if (b === false) b = '';
				b += c;
				break;
		}
	}
	if (b !== false) args.push(b); // last item
	// crmv@165297e
		
	args = jQuery.map(args, function(el, idx) { return eval(el); });
	
	args[3] += '&fixed=true';
	args[4] = true; // fixed
	args[5] = false; // autoscroll
	
	var def = loadDynamicRelatedList.apply(this, args);
	if (!def) {
		def = jQuery.Deferred().resolve();
	}
	
	// now do something after
	return def.then(function() {
		var cont = dyncont.find('>div[relation_id='+relid+']');

		// remove buttons
		cont.find('.dvInnerHeader').find('i').remove();
		
		// move in the fixed container
		fixcont.prepend(cont);
		
		// show the parent
		jQuery('#RelatedLists').show();
	});

}

function loadRelatedListBlockCount(urldata,target,imagesuffix,real_urldata,real_target) {

	if(isRelatedListBlockLoaded(target,urldata) == true){
		jQuery('#'+target).show();
		jQuery('#show_'+imagesuffix).hide();
		jQuery('#hide_'+imagesuffix).show();
		return;
	}
	
	jQuery('#indicator_'+imagesuffix).show();
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: urldata,
		success: function(result) {
			var res = eval("("+result+")");
			var count = res['count'] || 0;
			jQuery('#'+target+'_tl').html("("+count+")");
			jQuery('#'+target).html("("+count+")");
			jQuery('#indicator_'+imagesuffix).hide();
		}
	});
}

function isRelatedListBlockLoaded(id,urldata){
	var elem = document.getElementById(id);
	if(elem == null || typeof elem == 'undefined' || urldata.indexOf('order_by') != -1 ||
		urldata.indexOf('start') != -1 || urldata.indexOf('withCount') != -1){
		return false;
	}
	var tables = elem.getElementsByTagName('table');
	return tables.length > 0;
}

function loadRelatedListBlock(urldata,target,imagesuffix) {
	
	if(isRelatedListBlockLoaded(target,urldata) == true){
		jQuery('#'+target).show();
		jQuery('#show_'+imagesuffix).hide();
		jQuery('#hide_'+imagesuffix).show();
		return;
	}
		
	jQuery('#indicator_'+imagesuffix).show();
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: urldata,
		success: function(result) {
			jQuery('#'+target).html(trim(result)).show();
			jQuery('#indicator_'+imagesuffix).hide();
			jQuery('#show_'+imagesuffix).hide();
			jQuery('#hide_'+imagesuffix).show();
		}
	});
	
}

function hideRelatedListBlock(target, imagesuffix) {
	jQuery('#'+target).hide();
	jQuery('#show_'+imagesuffix).show();
	jQuery('#hide_'+imagesuffix).hide();
	jQuery('#delete_'+imagesuffix).hide();
}

function disableRelatedListBlock(urldata,target,imagesuffix){
	jQuery('#indicator_'+imagesuffix).show();
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: urldata,
		success: function(result) {
			jQuery('#'+target).hide();
			jQuery('#delete_'+imagesuffix).hide();
			jQuery('#hide_'+imagesuffix).show();
			jQuery('#show_'+imagesuffix).show();
			jQuery('#indicator_'+imagesuffix).hide();
		}
	});
}

var currentRelated = '';
function loadDynamicRelatedList(obj, relationid, related, urldata, fixed, autoscroll) {
	if (!relationid && obj) {
		// try to detect from the element
		relationid = jQuery(obj).closest('li').attr('relation_id');
	}
	relationid = parseInt(relationid);
	
	if (typeof autoscroll == 'undefined') {
		autoscroll = true;
	}

	if (fixed) {
		// if isnt in another panel
		var panelInfo = panelBlocks[window.currentPanelId];
		if (panelInfo && panelInfo.relatedids.indexOf(relationid) < 0) {
			// find a panel with that related
			var relpanelid = getPanelidForRelation(relationid);
			if (relpanelid && relpanelid != currentPanelId) {
				var goto = 'container_'+related;
				if (obj) {
					jQuery('.turboliftEntrySelected').addClass('turboliftEntry');
					jQuery('.turboliftEntrySelected').removeClass('turboliftEntrySelected');
					jQuery(obj).addClass('turboliftEntrySelected');
					jQuery(obj).removeClass('turboliftEntry');
				}
				changeTab(gVTModule, null, relpanelid, null, null, goto);
				return;
			}
		}
			
	}

	if (!fixed && currentRelated == related) {
		hideDynamicRelatedList(obj);
		return;
	}
	currentRelated = related;
	if (obj && obj instanceof HTMLElement) {
		jQuery('.turboliftEntrySelected').addClass('turboliftEntry');
		jQuery('.turboliftEntrySelected').removeClass('turboliftEntrySelected');
		jQuery(obj).addClass('turboliftEntrySelected');
		jQuery(obj).removeClass('turboliftEntry');
	}
	if (jQuery('#RelatedLists').find('#container_'+related).length > 0) {
		if (autoscroll) goToRelated('container_'+related);
		return;
	} else {
		jQuery('#DynamicRelatedList').show();
		VteJS_DialogBox.block('DynamicRelatedList');
		jQuery("#status").show();
		if (autoscroll) goToRelated('DynamicRelatedList');
	}

	var xhr = jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: urldata,
        success: function(response) {
					
			// crmv@82419
			try {
				jQuery('#DynamicRelatedList').html(response);
			} catch (e) {
				console.log("Exception: ", e);
			}
			// crmv@82419e
			
      		if (!fixed) showRelatedImg(related,'pin','hideDynamic');
      		VteJS_DialogBox.unblock('DynamicRelatedList');
      		jQuery("#status").hide();
      		//if (obj) fixTurbolift(obj);
      		
		// crmv@167019
      		if (window.VTE && window.VTE.DropArea && window.VTE.DropArea.isSupported()) {
      			jQuery('.drop-area-support').removeClass('hidden');
      		}
		// crmv@167019e
        }
	});
	
	// return a xhr / promise
	return xhr;
}

function showRelatedImg(related,image1,image2) {
	jQuery('#pin_'+related).hide();
	jQuery('#unPin_'+related).hide();
	jQuery('#hideDynamic_'+related).hide();
	jQuery('#'+image1+'_'+related).show();
	jQuery('#'+image2+'_'+related).show();
}

function hideDynamicRelatedList(obj) {
	jQuery(obj).removeClass('turboliftEntrySelected');
	jQuery(obj).addClass('turboliftEntry');
	jQuery('#DynamicRelatedList').hide();
	jQuery('#DynamicRelatedList').empty();
	currentRelated = '';
}

function pinRelated(related,module,relmodule) {
	jQuery('#indicator_'+related).show();

	jQuery('#RelatedLists').append(jQuery('#DynamicRelatedList').html());
	hideDynamicRelatedList('tl_'+related);
	jQuery('#DynamicRelatedList').empty();
	jQuery('#RelatedLists').show();
	
	// remove link from Turbolift
	jQuery('.turboliftEntrySelected').addClass('turboliftEntry');
	jQuery('.turboliftEntrySelected').removeClass('turboliftEntrySelected');
	jQuery.ajax({
		url: 'index.php?module='+module+'&action='+module+'Ajax&file=PinRelatedList&related='+related+'&module='+module+'&relmodule='+relmodule+'&mode=pin',
		type: 'POST',
		dataType: 'html',
		success: function(data){
			showRelatedImg(related,'unPin');
			jQuery('#indicator_'+related).hide();
		}
	});
}

function unPinRelated(related,module,relmodule) {
	jQuery('#indicator_'+related).show();
	jQuery('#container_'+related).remove();
	
	// add link to Turbolift
	jQuery('#tl_'+related).addClass('turboliftEntry');
	jQuery('#tl_'+related).removeClass('turboliftEntrySelected');
	jQuery.ajax({
		url: 'index.php?module='+module+'&action='+module+'Ajax&file=PinRelatedList&related='+related+'&module='+module+'&relmodule='+relmodule+'&mode=unPin',
		type: 'POST',
		dataType: 'html',
		success: function(data){
			showRelatedImg(related,'unPin');
			jQuery('#indicator_'+related).hide();
		}
	});
}

function goToRelated(id){
	var cont = jQuery("#"+id);
	if (cont.length == 0) {
		cont = jQuery("#RelatedLists");
	}
	jQuery('html,body').animate({scrollTop: cont.position().top - jQuery('#vte_menu_white').height()},'slow');	//crmv@152695
	if (window.Turbolift) {
		setTimeout(function() {
			Turbolift.alignScroll();
		}, 0);
	}
}