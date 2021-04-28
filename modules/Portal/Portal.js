/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@208173 */

/* crmv@192033 */

function fetchContents(mode)
{
	// Reloading the window is better, If not reloaded ... mysitesArray variable needs to be updated
	// using eval method on javascript. 
	if(mode == 'data') {
		window.location.href = 'index.php?module=Portal&action=ListView&parenttab=Tools';
		return;
	}
	jQuery("#status").show();
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: 'action=PortalAjax&mode=ajax&module=Portal&file=ListView&datamode='+mode,
		success: function(response) {
			jQuery("#status").hide();
			jQuery('#portalcont').html(response);
		}
	});
}

function DeleteSite(id)
{
	if(confirm(alert_arr.SURE_TO_DELETE))
	{
		jQuery("#status").show();
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data:'action=PortalAjax&mode=ajax&file=Delete&module=Portal&record='+id,
			success: function(response) {
				jQuery("#status").hide();
				jQuery('#portalcont').html(response);
			}
		});
	}
}

function fetchAddSite(id, self)
{
	jQuery("#status").show();
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data:'module=Portal&action=PortalAjax&file=Popup&record='+id,
		success: function(response) {
			jQuery("#status").hide();
			jQuery('#editportal_cont').html(response);
			showFloatingDiv('editMySite', self);
		}
	});
}

function SaveSite(id)
{
	if (jQuery('#portalurl').value.replace(/^\s+/g, '').replace(/\s+$/g, '').length===0) {
		alert(alert_arr.SITEURL_CANNOT_BE_EMPTY)
		return false;
	}
	if (jQuery('#portalname').value.replace(/^\s+/g, '').replace(/\s+$/g, '').length===0) {
		alert(alert_arr.SITENAME_CANNOT_BE_EMPTY)
		return false;
	}

	jQuery('#editMySite').fadeOut(); // crmv@168103
	jQuery("#status").show();

	var portalurl = document.getElementById('portalurl').value;
	portalurl = portalurl.replace(/&/g, "#$#$#");
	var portalname = document.getElementById('portalname').value;

	jQuery.ajax({
		url:'index.php',
		method: 'POST',
		data:'action=PortalAjax&mode=ajax&file=Save&module=Portal&portalname='+portalname+'&portalurl='+portalurl+'&record='+id,
		success: function(response) {
			if(response.responseText.indexOf(":#:FAILURE") > -1) {
				alert(alert_arr.VALID_DATA)
			} else {
				jQuery('#portalcont').html(response);
			}
			jQuery("#status").hide();
		}
	});
}

function setSite(oUrllist)
{
	var id = oUrllist.options[oUrllist.options.selectedIndex].value;
	document.getElementById('locatesite').src = mysitesArray[id];
}

//added as an enhancement to set default value
function defaultMysites(oSelectlist)
{
	var id = jQuery("#urllist").value;
	jQuery("#status").show();
	jQuery.ajax({
		url:'index.php',
		method: 'POST',
		data: 'action=PortalAjax&mode=ajax&file=Save&module=Portal&check=true&passing_var='+id,
		success: function(response) {
			jQuery("#status").hide();
		}
	});
}

var oRegex = {} ;
oRegex.UriProtocol = new RegExp('') ;
oRegex.UriProtocol.compile( '^(((http|https|ftp|news):\/\/)|mailto:)', 'gi' ) ;

oRegex.UrlOnChangeProtocol = new RegExp('') ;
oRegex.UrlOnChangeProtocol.compile( '^(http|https|ftp|news)://(?=.)', 'gi' ) ;

function OnUrlChange()
{
	var sUrl;
	var sProtocol;   
				
	sUrl=document.getElementById("portalurl").value ;
	sProtocol=oRegex.UrlOnChangeProtocol.exec( sUrl ) ;
	if ( sProtocol )
	{
		sUrl = sUrl.substr( sProtocol[0].length ) ;
		document.getElementById("portalurl").value = sUrl ;
	}

}