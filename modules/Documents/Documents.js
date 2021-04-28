/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@192033 */

//Added after 504 for renaming a folder
function UpdateAjaxSave(label,fid,fldname,fileOrFolder)
{
	var fldobj = document.getElementById('txtbox_'+label);
	var fldVal = fldobj.value;
	if(fldVal.replace(/^\s+/g, '').replace(/\s+$/g, '').length==0)
	{
		alert(alert_arr.FOLDERNAME_EMPTY);
		return false;
	}
	if(fldVal.replace(/^\s+/g, '').replace(/\s+$/g, '').length>=21)
	{
		alert(alert_arr.FOLDER_NAME_TOO_LONG);
		return false;
	}
	if(fldVal.match(/['"\\%+?]/))
	{
		alert(alert_arr.NO_SPECIAL_CHARS_DOCS);
		return false;
	}
	if(fileOrFolder == 'file')
		var url='action=DocumentsAjax&mode=ajax&file=Save&module=Documents&fileid='+fid+'&fldVal='+fldVal+'&fldname='+fldname+'&act=ajaxEdit';
	else
	{
		var foldername = encodeURIComponent(fldVal);
		foldername = foldername.replace(/^\s+/g, '').replace(/\s+$/g, '');
		foldername = foldername.replace(/&/gi,'*amp*');
		var url='action=DocumentsAjax&mode=ajax&file=SaveFolder&module=Documents&record='+fid+'&foldername='+fldVal+'&savemode=Save';
	}
	if(fldname == 'status')
	{
		var fldbox = 
		fldVal = fldobj.options[fldobj.options.selectedIndex].text;
		gtempselectedIndex = fldobj.options.selectedIndex;
	}
	jQuery('#status').show();
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: url,
		success: function(result) {
			var item = result;
			jQuery('#status').hide();
			if(item.indexOf("Failure") > -1 )
			{
				jQuery("#lblError").html("<table cellpadding=0 cellspacing=0 border=0 width=100%><tr><td class=small bgcolor=red><font color=white size=2><b>"+alert_arr.LBL_UNABLE_TO_UPDATE+"</b></font></td></tr></table>");
				setTimeout(hidelblError,3000);
			}
			else if(item.indexOf('DUPLICATE_FOLDERNAME') > -1)
			{
				alert(alert_arr.DUPLICATE_FOLDER_NAME);
			}
			else
			{
				document.getElementById('dtlview_'+label).innerHTML = fldVal;
				eval("hndCancel('dtlview_"+label+"','editarea_"+label+"','"+label+"')");
				if(fldname == 'status')
					fldobj.selectedIndex = gtempselectedIndex;
				else
					fldobj.value = fldVal;
				eval(item);
			}
		}
	});
}

function closeFolderCreate() {
	jQuery('#folder_id').val('');
	jQuery('#folder_name').val('');
	jQuery('#folder_desc').val('');
	fninvsh('orgLay')
}

function AddFolder()
{
	var fldrname=getObj('folder_name').value;
	var fldrdesc=getObj('folder_desc').value;
	if(fldrname.replace(/^\s+/g, '').replace(/\s+$/g, '').length==0)
	{
		alert(alert_arr.FOLDERNAME_EMPTY);
		return false;
	}
	if(fldrname.replace(/^\s+/g, '').replace(/\s+$/g, '').length>=21)
	{
		alert(alert_arr.FOLDER_NAME_TOO_LONG);
		return false;
	}
	if(fldrdesc.replace(/^\s+/g, '').replace(/\s+$/g, '').length>=51)
	{
		alert(alert_arr.FOLDER_DESCRIPTION_TOO_LONG);
		return false;
	}
	if(fldrname.match(/['"\\%+]/) || fldrdesc.match(/['"\\%+]/))
	{
		alert(alert_arr.NO_SPECIAL_CHARS_DOCS+alert_arr.NAME_DESC);
		return false;
	}
	if(fldrname.match(/[?]+$/) || fldrname.match(/[?]+/))
	{
		alert(alert_arr.NO_SPECIAL_CHARS_DOCS);
		return false;
	}
	fninvsh('orgLay');
	var foldername = encodeURIComponent(getObj('folder_name').value);
	var folderdesc = encodeURIComponent(getObj('folder_desc').value);
		
	foldername = foldername.replace(/^\s+/g, '').replace(/\s+$/g, '');
	foldername = foldername.replace(/&/gi,'*amp*');
	folderdesc = folderdesc.replace(/^\s+/g, '').replace(/\s+$/g, '');
	folderdesc = folderdesc.replace(/&/gi,'*amp*');
	
	getObj('folder_name').value = '';
	getObj('folder_desc').value = '';
	var mode = getObj('fldrsave_mode').value;
	if(mode == 'save')
	{
		url ='&savemode=Save&foldername='+foldername+'&folderdesc='+folderdesc;
	}
	getObj('fldrsave_mode').value = 'save';
	
	jQuery('#status').show();
	
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: 'action=DocumentsAjax&mode=ajax&file=SaveFolder&module=Documents'+url,
		success: function(result) {
			var item = result;
			jQuery('#status').hide();
			if(item.indexOf('Failure') > -1)
			{
				jQuery('#lblError').html("<table cellpadding=0 cellspacing=0 border=0 width=100%><tr><td class=small bgcolor=red><font color=white size=2><b>"+alert_arr.LBL_UNABLE_TO_ADD_FOLDER+"</b></font></td></tr></table>");
				setTimeOutFn();
			}
			else if(item.indexOf('DUPLICATE_FOLDERNAME') > -1)
			{
				alert(alert_arr.DUPLICATE_FOLDER_NAME);
			}
			else
			{
				getObj("ListViewContents").innerHTML = item;
			}
		}
	});
}


function DeleteFolderCheck(folderId)
{
	gtempfolderId = folderId;
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: "module=Documents&action=DocumentsAjax&mode=ajax&file=DeleteFolder&deletechk=true&folderid="+folderId,
		success: function(result) {
			var item = result;
			if(item.indexOf("NOT_PERMITTED") > -1) {
				alert(alert_arr.NOT_PERMITTED);
				return false;
			}
			else if(item.indexOf("FAILURE") > -1)
			{
				alert(alert_arr.LBL_FOLDER_SHOULD_BE_EMPTY);
			}
			else {
				if(confirm(alert_arr.LBL_ARE_YOU_SURE_YOU_WANT_TO_DELETE_FOLDER))
				{
					DeleteFolder(gtempfolderId);
				}
			}
		}
	});
}

function DeleteFolder(folderId)
{
	jQuery('#status').show();
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: "module=Documents&action=DocumentsAjax&mode=ajax&file=DeleteFolder&folderid="+folderId,
		success: function(result) {
			var item = result;
			jQuery('#status').hide();
			if(item.indexOf("FAILURE") > -1)
				alert(alert_arr.LBL_ERROR_WHILE_DELETING_FOLDER);
			else
				getObj("ListViewContents").innerHTML = item;
		}
	});
}

function MoveFile(id,foldername)
{
		fninvsh('movefolderlist');
		var select_options  =  document.getElementById('allselectedboxes').value;
		var x = select_options.split(";");
		var searchurl= document.getElementById('search_url').value;
		var count=x.length
		var viewid =getviewId();
		var idstring = "";
		if (count > 1)
		{
			document.getElementById('idlist').value=select_options;
			idstring = select_options;
		}
		else
		{
			alert(alert_arr.SELECT);
			return false;
		}

	if(idstring != '')
	{
		if(confirm(alert_arr.LBL_ARE_YOU_SURE_TO_MOVE_TO + foldername + alert_arr.LBL_FOLDER))
		{
			jQuery('#status').show();
			jQuery.ajax({
				url: 'index.php',
				method: 'POST',
				data: 'action=DocumentsAjax&file=MoveFile&from_folderid=0&module=Documents&folderid='+id+'&idlist='+idstring,
				success: function(result) {
					var item = result;
					jQuery('#status').hide();
					if(item.indexOf("NOT_PERMITTED") > -1 )
					{
						jQuery("#lblError").html("<table cellpadding=0 cellspacing=0 border=0 width=100%><tr><td class=small bgcolor=red><font color=white size=2><b>"+alert_arr.NOT_PERMITTED+"</b></font></td></tr></table>");
						setTimeout(hidelblError,3000);
					}
					else {
						getObj('ListViewContents').innerHTML = item;
					}
				}
			});
		}else{
			return false;
		}

	}else
	{
		alert(alert_arr.LBL_SELECT_ONE_FILE);
		return false;
	}
}

function dldCntIncrease(fileid) {
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: 'action=DocumentsAjax&mode=ajax&file=SaveFile&module=Documents&file_id='+fileid+"&act=updateDldCnt",
	});
}


function checkFileIntegrityDetailView(noteid)
{
	jQuery('#vtbusy_integrity_info').show();
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: 'module=Documents&action=DocumentsAjax&mode=ajax&file=SaveFile&act=checkFileIntegrityDetailView&noteid='+noteid,
		success: function(result) {
			var item = result;
			if(item.indexOf('file_available') > -1)
			{
				jQuery('#vtbusy_integrity_info').hide();
				jQuery('#integrity_result').html('<br><br>&nbsp;&nbsp;&nbsp;<font style=color:green>'+alert_arr.LBL_FILE_CAN_BE_DOWNLOAD+'</font>');
				jQuery('#integrity_result').show();
				setTimeout(hideresult,4000);
			}
			else if(item.indexOf('file_not_available') > -1)
			{
				jQuery('#vtbusy_integrity_info').hide();
				jQuery('#integrity_result').html('<br><br>&nbsp;&nbsp;&nbsp;<font style=color:red>'+alert_arr.LBL_DOCUMENT_NOT_AVAILABLE+'</font>');
				jQuery('#integrity_result').show();
				setTimeout(hideresult,6000);
			}
			else if(item.indexOf('lost_integrity') > -1)
			{
				jQuery('#vtbusy_integrity_info').hide();
				jQuery('#integrity_result').html('<br><br>&nbsp;&nbsp;&nbsp;<font style=color:red>'+alert_arr.LBL_DOCUMENT_LOST_INTEGRITY+'</font>');
				jQuery('#integrity_result').show();
				setTimeout(hideresult,6000);
			}
		}
	});
}

function hideresult() {
	jQuery('#integrity_result').hide();
}

function add_data_to_relatedlist(entity_id,recordid) {

		opener.document.location.href="index.php?module={RETURN_MODULE}&action=updateRelations&smodule={SMODULE}&destination_module=Products&entityid="+entity_id+"&parentid="+recordid;
}

// crmv@43147
function AddDocRevision(record){
	var url = 'index.php?module=Documents&action=DocumentsAjax&file=RevisionAdd&record='+record;
	openPopup(url,'title','','','40','40','','nospinner');
}
//crmv@43147e

//Added to send a file, in Documents module, as an attachment in an email
function sendfile_email() {
	filename = jQuery('#dldfilename').val();
	OpenCompose(filename,'Documents');
}

//crmv@62414
function ViewEML(documentid) {
	jQuery('#status').show();
	jQuery.ajax({
		url: 'index.php?module=Documents&action=DocumentsAjax&file=PreviewFile&mode=fetchcontent&record='+documentid,
		dataType: 'json',
		async: false,
		success: function(data){
			jQuery('#status').hide();
			if (data.success == false) {
				alert(alert_arr.NOT_PERMITTED);
			} else {
				var eml_messageid = data.messageid;
				var url = 'index.php?module=Documents&action=DocumentsAjax&file=DetailView&mode=Detach&record='+eml_messageid;
				window.open(url,'_blank');
			}
		}
	});
}

function ViewDocument(documentid) {
	jQuery('#status').show();
	jQuery.ajax({
		url: 'index.php?module=Documents&action=DocumentsAjax&file=PreviewFile&mode=fetchcontent&record='+documentid,
		dataType: 'json',
		async: false,
		success: function(data){
			jQuery('#status').hide();
			if (data.success == false) {
				alert(alert_arr.NOT_PERMITTED);
			} else {
				var savepath = data.savepath;
				var url = 'index.php?module=Messages&action=MessagesAjax&file=src/ViewerJS/index&requestedfile='+savepath;
				window.open(url,'_blank');
			}
		}
	});
}

function ViewImage(documentid) {
	jQuery('#status').show();
	jQuery.ajax({
		url: 'index.php?module=Documents&action=DocumentsAjax&file=PreviewFile&mode=fetchcontent&record='+documentid,
		dataType: 'json',
		async: false,
		success: function(data){
			jQuery('#status').hide();
			if (data.success == false) {
				alert(alert_arr.NOT_PERMITTED);
			} else {
				var savepath = data.savepath;
				var width = data.width + 20; //add some px
				var height = data.height + 20; //add some px
				var pagewidth = window.innerWidth || document.body.clientWidth;
				var pageheight = window.innerHeight || document.body.clientHeight;
				if(width > pagewidth || height > pageheight){
					width = false;
					height = false;
				}
				var url = 'index.php?module=Messages&action=MessagesAjax&file=ImageViewer&requestedfile='+savepath;
				openPopup(url,document.title,"width=800,height=600","auto",width,height,"top",'nospinner');
			}
		}
	});
}
//crmv@62414e

// crmv@95157
function showMetadata(crmid) {
	var module = gVTModule;
	
	jQuery("#status").show();
	jQuery.ajax({
		url: 'index.php?module='+module+'&action='+module+'Ajax&file=ShowMetadata&record='+crmid,
		async: true,
		success: function(data) {
			jQuery("#status").hide();
			jQuery('#metadataContainer .crmvDivContent').html(data);
			jQuery('#metadataContainer').css('visibility', 'initial').show();
			
			placeAtCenter(document.getElementById('metadataContainer'));
		}
	});
	
}

function saveMetadata(crmid) {
	var module = gVTModule;
	var props = {};
	
	jQuery.each(jQuery('#metadataForm').serializeArray(), function(k, field) {
		props[field.name] = field.value;
	});
	
	var data = {
		properties: JSON.stringify(props),
	};
	
	jQuery("#status").show();
	jQuery.ajax({
		url: 'index.php?module='+module+'&action='+module+'Ajax&file=ShowMetadata&ajxaction=save&record='+crmid,
		type: 'post',
		data: data,
		async: true,
		success: function(data) {
			jQuery("#status").hide();
			jQuery('#metadataContainer .crmvDivContent').html(data);
		}
	});
}
// crmv@95157e