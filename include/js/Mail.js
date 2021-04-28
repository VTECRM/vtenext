/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//added by raju for emails

//crmv@fix email
function eMail(module,oButton)
{
	var allids = get_real_selected_ids(module).replace(/;/g,",");
	if (allids == "" || allids == ",")
	{
		alert(alert_arr.SELECT);
		return false;
	}
	
	if (allids.substr('0','1') == ',') allids = allids.substr('1');
	var strlen = allids.length;
	if (strlen > 0 && allids.substring(strlen-1,strlen) == ',') allids = allids.substring(0,strlen-1);

	fnvshobj(oButton,'sendmail_cont');
	sendmail(module,allids);
}
//crmv@fix email end

function massMail(module)
{

	var select_options  =  document.getElementsByName('selected_id');
	x = select_options.length;
	var viewid =getviewId();		
	idstring = "";

	xx = 0;
	for(i = 0; i < x ; i++)
	{
		if(select_options[i].checked)
		{
			idstring = select_options[i].value +";"+idstring
				xx++
		}
	}
	if (xx != 0)
	{
		//crmv@fix idlist
		document.getElementById('selected_ids').value=idstring;
		//crmv@fix idlist end
	}
	else
	{
		alert(alert_arr.SELECT);
		return false;
	}
	document.massdelete.action="index.php?module=CustomView&action=SendMailAction&return_module="+module+"&return_action=index&viewname="+viewid;
}

//added by rdhital for better emails
function set_return_emails(entity_id,email_id,parentname,emailadd,emailadd2,perm,noclose){//crmv@22366
	if(perm == 0 || perm == 3)
	{
		if(emailadd2 == '')
		{			
			alert(alert_arr.LBL_DONT_HAVE_EMAIL_PERMISSION);
			return false;
		}
		else
			emailadd = emailadd2;
	}
	else
	{
		if(emailadd == '')
			emailadd = emailadd2;
	}	
	if(emailadd != '')
	{
		//crmv@25356
		var span = '<span id="to_'+entity_id+'@'+email_id+'" class="addrBubble">'+parentname
					+'<div id="to_'+entity_id+'@'+email_id+'_parent_id" style="display:none;">'+entity_id+'@'+email_id+'</div>'
					+'<div id="to_'+entity_id+'@'+email_id+'_parent_name" style="display:none;">'+parentname+'</div>'
					+'<div id="to_'+entity_id+'@'+email_id+'_hidden_toid" style="display:none;">'+emailadd+'</div>'
					+'<div id="to_'+entity_id+'@'+email_id+'_remove" class="ImgBubbleDelete" onClick="removeAddress(\'to\',\''+entity_id+'@'+email_id+'\');"><i class="vteicon small">clear</i></div>'
					+'</span>';
		parent.jQuery("#autosuggest_to").prepend(span);
		//crmv@25356
		//crmv@21048m
		parent.document.EditView.parent_id.value = parent.document.EditView.parent_id.value+entity_id+'@'+email_id+'|';
		parent.document.EditView.parent_name.value = parent.document.EditView.parent_name.value+parentname+'<'+emailadd+'>,';
		parent.document.EditView.hidden_toid.value = emailadd+','+parent.document.EditView.hidden_toid.value;
		//crmv@22366
		if (typeof(noclose) == 'undefined' || noclose != 'noclose') {
			closePopup();	
		}
		//crmv@22366e
		//crmv@21048me

	}else
	{
		alert('"'+parentname+alert_arr.DOESNOT_HAVE_AN_MAILID);
		return false;
	}
}
//added by raju for emails

function validate_sendmail(idlist,module)
{
	if (checkJSOverride(arguments)) return callJSOverride(arguments); //crmv@35654
	if (checkJSExtension(arguments)) if (!callJSExtension(arguments)) return false; // crmv@65067
	
	var j=0;
	var chk_emails = document.SendMail.elements.length;
	var oFsendmail = document.SendMail.elements
	email_type = new Array();
	for(var i=0 ;i < chk_emails ;i++)
	{
		if(oFsendmail[i].type != 'button')
		{
			if(oFsendmail[i].checked != false)
			{
				email_type [j++]= oFsendmail[i].value;
			}
		}
	}
	if(email_type != '')
	{
		var field_lists = email_type.join(':');
		var url= 'index.php?module=Emails&action=EmailsAjax&pmodule='+module+'&file=EditView&sendmail=true&field_lists='+field_lists+'&pid='+idlist; //crmv@27096 //crmv@26639 +'&send_mode=multiple' //crmv@58554
		//crmv@31197
		//openPopUp('xComposeEmail',this,url,'createemailWin',820,689,'menubar=no,toolbar=no,location=no,status=no,resizable=no');
		window.open(url,'_blank');
		//crmv@31197e
		clear_checked_all(module);	//crmv@19139
		hideFloatingDiv('sendmail_cont');
		return true;
	}
	else
	{
		alert(alert_arr.SELECT_MAILID);
	}
}

/*
crmv@fix sendmail div
crmv@26639	&send_mode=multiple
crmv@2963m
crmv@31197
*/
function sendmail(module,idstrings)
{
	if (checkJSOverride(arguments)) return callJSOverride(arguments); //crmv@35654
	if (checkJSExtension(arguments)) if (!callJSExtension(arguments)) return false; // crmv@65067
	
	jQuery.ajax({
		'url': 'index.php',
		'type': 'POST',
		'data': "module=Emails&return_module="+module+"&action=EmailsAjax&file=mailSelect&idlist="+idstrings,
		'async': false,
		success: function(data) {
			if(data == "Mail Ids not permitted" || data == "No Mail Ids")
			{
                window.open('index.php?module=Emails&action=EmailsAjax&pmodule='+module+'&file=EditView&sendmail=true&pid='+idstrings,'_blank');
			}	
			else
			{
				jQuery('#sendmail_cont').css('min-width', '400px');
				jQuery('#sendmail_cont').css('min-height', '200px');
				getObj('sendmail_cont').innerHTML = data;
				showFloatingDiv('sendmail_cont');
			}
		},
	});
}

function rel_eMail(module,oButton,relmod)
{
	var select_options='';
	var allids='';
	var cookie_val=get_cookie(relmod+"_all");
	if(cookie_val != null)
		select_options=cookie_val;
	//Added to remove the semi colen ';' at the end of the string.done to avoid error.
	var x = select_options.split(";");
	var viewid ='';
	var count=x.length
		var idstring = "";
	select_options=select_options.slice(0,(select_options.length-1));

	if (count > 1)
	{
	//crmv@14455
		idstring=select_options.replace(/;/g,',')
	//crmv@14455 end		
			allids=idstring;
	}
	else
	{
		alert(alert_arr.SELECT);
		return false;
	}
	fnvshobj(oButton,'sendmail_cont');
	sendmail(relmod,allids);
}

//crmv@25356
function set_return_emails_cc(entity_id,email_id,parentname,emailadd,emailadd2,perm,noclose){
	set_return_emails_c('cc_name',entity_id,email_id,parentname,emailadd,emailadd2,perm,noclose)
}
function set_return_emails_bcc(entity_id,email_id,parentname,emailadd,emailadd2,perm,noclose){
	set_return_emails_c('bcc_name',entity_id,email_id,parentname,emailadd,emailadd2,perm,noclose)
}
function set_return_emails_c(field,entity_id,email_id,parentname,emailadd,emailadd2,perm,noclose){
	if(perm == 0 || perm == 3) {
		if(emailadd2 == '') {			
			alert(alert_arr.LBL_DONT_HAVE_EMAIL_PERMISSION);
			return false;
		} else {
			emailadd = emailadd2;
		}
	} else {
		if(emailadd == '') {
			emailadd = emailadd2;
		}
	}	
	if(emailadd != '') {
		parent.getObj(field).value = parent.getObj(field).value+emailadd+',';
		if (typeof(noclose) == 'undefined' || noclose != 'noclose') {
			closePopup();	
		}
	} else {
		alert('"'+parentname+alert_arr.DOESNOT_HAVE_AN_MAILID);
		return false;
	}
}
//crmv@25356e