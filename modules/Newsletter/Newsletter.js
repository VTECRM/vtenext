/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

 function sendNewsletter(record,mode) {
 	jQuery("#status").show();
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: 'module=Newsletter&action=NewsletterAjax&file=SendEmail&record='+record+'&mode='+mode,
		success: function(result) {
			jQuery("#status").hide();
			alert(result);
		}
	});
 }

//crmv@80155
function previewTemplate(record,templateid,templatename)
{
	window.document.location.href = "index.php?module=Newsletter&action=NewsletterAjax&file=widgets/TemplateEmailPreview&record="+record+"&templateid="+templateid+"&templatename="+templatename;
}
//crmv@80155e

function submittemplate(record,templateid,templatename)
{
    res = getFile("index.php?module=Newsletter&action=NewsletterAjax&file=widgets/TemplateEmailSave&record="+record+"&templateid="+templateid);
	parent.getObj('templateemail_name').value = templatename;
	closePopup();
}

function freezeBackground() {
    var oFreezeLayer = document.createElement("DIV");
    oFreezeLayer.id = "freeze";
    oFreezeLayer.className = "small veil_new";

     if (browser_ie) oFreezeLayer.style.height = (document.body.offsetHeight + (document.body.scrollHeight - document.body.offsetHeight)) + "px";
     else if (browser_nn4 || browser_nn6) oFreezeLayer.style.height = document.body.offsetHeight + "px";

    oFreezeLayer.style.width = "100%";
    document.body.appendChild(oFreezeLayer);
    document.getElementById('confId').style.display = 'block';
    hideSelect();
}

//crmv@38592 crmv@43611
function previewNewsletter(record, newwindow, crmid) {
	var url = "index.php?module=Newsletter&action=NewsletterAjax&file=ShowPreview&record="+record;
	if (crmid) url += '&crmid='+crmid; // crmv@151466
	if (newwindow) {
		window.open(url, '_blank');
	} else {
		url += '&show_back_button=true'; // crmv@135115
		openPopup(url,"ShowPreview","width=750,height=602,menubar=no,toolbar=no,location=no,status=no,resizable=no,scrollbars=yes");
	}
}
//crmv@38592e

function openNewsletterWizard(module, id) {
	var url = "index.php?module=Campaigns&action=CampaignsAjax&file=NewsletterWizard&from_module="+encodeURIComponent(module)+'&from_record='+id;
	openPopup(url,"NewsletterWizard","width=750,height=602,menubar=no,toolbar=no,location=no,status=no,resizable=no,scrollbars=yes");
}
//crmv@43611e

//crmv@55961
function lockUnlockReceivingNewsletter(record, mode) {
	jQuery("#vtbusy_info").show();
	jQuery.ajax({
		url: 'index.php?module=Newsletter&action=NewsletterAjax&file=DetailViewAjax&ajxaction=LOCKRECEIVINGNEWSLETTER&record='+record+'&mode='+mode,
		type: 'POST',
		success: function(data) {
			jQuery("#vtbusy_info").hide();
			if (mode == 'lock') {
				jQuery("#receivingNewsletterButton2").show();
				jQuery("#receivingNewsletterButton1").hide();
			} else {
				jQuery("#receivingNewsletterButton2").hide();
				jQuery("#receivingNewsletterButton1").show();
			}
		}
	});
}
//crmv@55961e

//crmv@195115 crmv@192033
function stopNewsletter(record) {
	jQuery("#status").show();
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: 'module=Newsletter&action=NewsletterAjax&file=DetailViewAjax&ajxaction=STOPSENDING&record='+record,
		success: function(result) {
			jQuery("#status").hide();
			if (result != 'SUCCESS') {
				alert(alert_arr.ERROR);
			}
			else window.location.reload();
		}
	});
}
// crmv@195115e crmv@192033e