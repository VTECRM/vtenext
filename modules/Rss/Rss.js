/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

window.VTE = window.VTE || {};

VTE.Rss = VTE.Rss || {
	
	GetRssFeedList: function(id) {
		jQuery("#status").show();
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'module=Rss&action=RssAjax&file=ListView&directmode=ajax&record='+id,
			success: function(result) {
				jQuery("#status").hide();
				jQuery("#rssfeedscont").html(result);
			}
		});
	},

	DeleteRssFeeds: function(id) {
		if (id != '') {
			if (confirm(alert_arr.DELETE_RSSFEED_CONFIRMATION)) {
				jQuery("#status").show();
				var feed = 'feed_' + id;
				jQuery('#' + feed).remove();
				jQuery.ajax({
					url: 'index.php',
					method: 'POST',
					data: 'module=Rss&return_module=Rss&action=RssAjax&file=Delete&directmode=ajax&record='+id,
					success: function(result) {
						jQuery("#status").hide();
						jQuery("#rssfeedscont").html(result);
						jQuery("#mysite").attr('src', '');
						jQuery("#rsstitle").html("&nbsp");
					}
				});
			}
		} else {
			alert(alert_arr.LBL_NO_FEEDS_SELECTED);
		}
	},

	SaveRssFeeds: function() {
		jQuery("#status").show();
		var rssurl = jQuery('#rssurl').val();
		rssurl = rssurl.replace(/&/gi, "##amp##");
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'module=Rss&action=RssAjax&file=Popup&directmode=ajax&rssurl='+rssurl,
			success: function(result) {
				jQuery("#status").hide();
				jQuery('#rssurl').val("");
				if (isNaN(parseInt(result))) {
					alert(result);
				} else {
					VTE.Rss.GetRssFeedList(result);
					VTE.Rss.getrssfolders();
					hideFloatingDiv('PopupLay');
				}
			}
		});
	},

	makedefaultRss: function(id) {
		if (id != '') {
			jQuery("#status").show();
			jQuery.ajax({
				url: 'index.php',
				method: 'POST',
				data: 'module=Rss&action=RssAjax&file=Popup&directmode=ajax&record='+id,
				success: function(result) {
					jQuery("#status").hide();
					VTE.Rss.getrssfolders();
				}
			});
		}
	},

	getrssfolders: function() {
		jQuery("#status").show();
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'module=Rss&action=RssAjax&file=ListView&folders=true',
			success: function(result) {
				jQuery("#status").hide();
				jQuery("#rssfolders").html(result);
			}
		});
	},

	display: function(url, id) {
		document.getElementById('rsstitle').innerHTML = document.getElementById(id).innerHTML;
		document.getElementById('mysite').src = url;
	}

};

/**
 * @deprecated
 * This function has been moved to VTE.Rss class.
 */

function GetRssFeedList(id) {
	return VTE.callDeprecated('GetRssFeedList', VTE.Rss.GetRssFeedList, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Rss class.
 */

function DeleteRssFeeds(id) {
	return VTE.callDeprecated('DeleteRssFeeds', VTE.Rss.DeleteRssFeeds, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Rss class.
 */

function SaveRssFeeds() {
	return VTE.callDeprecated('SaveRssFeeds', VTE.Rss.SaveRssFeeds, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Rss class.
 */

function makedefaultRss(id) {
	return VTE.callDeprecated('makedefaultRss', VTE.Rss.makedefaultRss, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Rss class.
 */

function getrssfolders() {
	return VTE.callDeprecated('getrssfolders', VTE.Rss.getrssfolders, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Rss class.
 */

function display(url, id) {
	return VTE.callDeprecated('display', VTE.Rss.display, arguments);
}