{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{assign var="BROWSER_TITLE" value=$MOD.TITLE_COMPOSE_MAIL}
{include file="HTMLHeader.tpl" head_include="icons,jquery,fancybox,prototype"}

<body class="small">

{include file='CachedValues.tpl'}	{* crmv@26316 *}

{* additional scripts *}
<script type="text/javascript" src="modules/Fax/multifile.js"></script>

{* crmv@21048m *}
<div id="popupContainer" style="display:none;"></div>
{* crmv@21048m e*}
{literal}
<form name="EditView" method="POST" ENCTYPE="multipart/form-data" action="index.php" onSubmit="if(fax_validate(this.form,'')) { VteJS_DialogBox.block();} else { return false; }">
{/literal}
<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
<input type="hidden" name="send_fax" >
<input type="hidden" name="parent_module" value="{$select_module}"> {* crmv@152701 *}
<input type="hidden" name="contact_id" value="{$CONTACT_ID}">
<input type="hidden" name="user_id" value="{$USER_ID}">
<input type="hidden" name="filename" value="{$FILENAME}">
<input type="hidden" name="old_id" value="{$OLD_ID}">
<input type="hidden" name="module" value="{$MODULE}">
<input type="hidden" name="record" value="{$ID}">
<input type="hidden" name="mode" value="{$MODE}">
<input type="hidden" name="action">
<input type="hidden" name="popupaction" value="create">
<input type="hidden" name="hidden_toid" id="hidden_toid">
<table class="small mailClient" border="0" cellpadding="0" cellspacing="0" width="100%">
<tbody>
   
   <tr>
	<td colspan=3 >
	<!-- Fax Header -->
	<table border=0 cellspacing=0 cellpadding=0 width=100% class="mailClientWriteEmailHeader level2Bg menuSeparation">
	<tr>
		<td>{$MOD.LBL_COMPOSE_FAX}</td>
	</tr>
	</table>
	
	
	</td>
</tr>
	{foreach item=row from=$BLOCKS.fields}
	{foreach item=elements from=$row}
	{if $elements.2.0 eq 'parent_id'}
   <tr>
	<td class="mailSubHeader" align="right">{$MOD.LBL_TO}</td>
	<td class="cellText" style="padding: 5px;">
 		<input name="{$elements.2.0}" id="{$elements.2.0}" type="hidden" value="{$IDLISTS}">
		<input type="hidden" name="saved_toid" value="{$TO_FAX}">
		<input id="parent_name" name="parent_name" readonly class="txtBox" type="text" value="{$TO_FAX}" style="width:99%">&nbsp;
	</td>
	<td class="cellText" style="padding: 5px;" align="left" nowrap>
		<select name="parent_type">
			{foreach key=labelval item=selectval from=$elements.1.0}
				{if $select_module eq $APP[$labelval]}
					{assign var=selectval value="selected"}
				{else}
					{assign var=selectval value=""}
				{/if}
				<option value="{$labelval}" {$selectval}>{$APP[$labelval]}</option>
			{/foreach}
		</select>
		&nbsp;
		<span  class="mailClientCSSButton">
		  <i class="vteicon md-link" title="{$APP.LBL_SELECT}" onclick='openPopup("index.php?module="+ document.EditView.parent_type.value +"&action=Popup&html=Popup_picker&form=HelpDeskEditView&return_module=Fax&popuptype=set_return_fax","test","width=640,height=602,resizable=0,scrollbars=0,top=150,left=200");'>view_list</i>&nbsp;{* crmv@21048m *}
    </span>
    <span class="mailClientCSSButton" >
      <i class="vteicon md-link" title="{$APP.LBL_CLEAR}" onClick="jQuery('#parent_id').val(''); jQuery('#hidden_toid').val('');jQuery('#parent_name').val(''); return false;">highlight_off</i> {* crmv@192033 *}
    </span>
	</td>
   </tr>
   <tr>
   <!-- ds@6 send a fax to external receiver -->
    <tr>
      <td class="mailSubHeader" style="padding: 5px;" align="right">{$MOD.LBL_TO}</td>
      <td class="cellText" style="padding: 5px;">
  		  <input name="to_fax" id ="to_fax" class="txtBox" type="text" value="{$TO_FAX}" style="width:99%">&nbsp;
      </td>
      <td class="cellText">
        <input name="check_to_fax" id="check_to_fax" type="checkbox" {if $CHECK_TO_FAX} checked {/if}>&nbsp;{$MOD.EXTERNAL_RECEIVER}       
      </td>
    </tr>
    <!-- ds@6e -->   
	{elseif $elements.2.0 eq 'subject'}
   <tr>
	<td class="mailSubHeader" style="padding: 5px;" align="right" nowrap><font color="red">*</font>{$elements.1.0}</td>
                <td class="cellText" style="padding: 5px;"><input type="text" class="txtBox" name="{$elements.2.0}" value="{$elements.3.0}" id="{$elements.2.0}" style="width:99%"></td>
                
                     <td valign="top" class="cellLabel" rowspan="4"><div id="attach_cont_fax" class="addEventInnerBox" style="overflow:auto;height:110px;width:100%;position:relative;left:0px;top:0px;"></div>
     </td>  
   </tr>
	{elseif $elements.2.0 eq 'filename'}

   <tr>
	<td class="mailSubHeader" style="padding: 5px;" align="right" nowrap>{$elements.1.0}</td>
	<td class="cellText" style="padding: 5px;">
		<!--<input name="{$elements.2.0}"  type="file" class="small txtBox" value="" size="78"/>-->
		<input name="del_file_list" type="hidden" value="">
					<div id="files_list" style="border: 1px solid grey; width: 500px; padding: 5px; background: rgb(255, 255, 255) none repeat scroll 0%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; font-size: x-small">
						{'Files_Maximum_6'|getTranslatedString}
						<input id="my_file_element" type="file" name="{$elements.2.0}" tabindex="7" onchange="validateFilename(this);">
						<input type="hidden" name="{$elements.2.0}_hidden" value="" />
																	</div>
					<script>
						var multi_selector = new MultiSelector( document.getElementById( 'files_list' ), 6 );
						multi_selector.count = 0
						multi_selector.addElement( document.getElementById( 'my_file_element' ) );
					</script>
		<div id="attach_temp_cont_fax" style="display:none;">
		<table class="small" width="100% ">
	{if $smarty.request.attachment != ''}
                <tr><td width="100%" colspan="2">{$smarty.request.attachment}<input type="hidden" value="{$smarty.request.attachment}" name="pdf_attachment"></td></tr>                                                                                                                                                                                      {else}   

		{foreach item="attach_files" key="attach_id" from=$elements.3}	
			<tr id="row_{$attach_id}"><td width="90%">{$attach_files}</td><td><i class="vteicon checkno md-link" onClick="delAttachments({$attach_id})" title="{$APP.LBL_DELETE_BUTTON}">cancel</i></td></tr>	
		{/foreach}
		<input type='hidden' name='att_id_list' value='{$ATT_ID_LIST}' />
	{/if}
		</table>	
		</div>	
		{$elements.3.0}
	</td>
   </tr>
   <tr>
	<td colspan="3" class="faxSubHeader" style="padding: 5px;" align="center">
		<input title="{$APP.LBL_SAVE_BUTTON_TITLE}" accessKey="{$APP.LBL_SAVE_BUTTON_KEY}" class="crmbutton small save" onclick="return fax_validate(this.form,'save');" type="button" name="button" value="  {$APP.LBL_SAVE_BUTTON_LABEL} " >&nbsp;
		<input name="{$MOD.LBL_SEND}" value=" {$APP.LBL_SEND} " class="crmbutton small save" type="button" onclick="return fax_validate(this.form,'send');">&nbsp;
		<input name="{$APP.LBL_CANCEL_BUTTON_TITLE}" accessKey="{$APP.LBL_CANCEL_BUTTON_KEY}" value=" {$APP.LBL_CANCEL_BUTTON_LABEL} " class="crmbutton small cancel" type="button" onClick="closePopup();">	{* crmv@48269 *}
	</td>
    </tr>
	{elseif $elements.2.0 eq 'description'}
   <tr>	{* crmv@24834 *}
	<td colspan="3" align="center" valign="top" height="320">
        {if $RET_ERROR eq 1}
		<input type="hidden" name="from_add" value="{$from_add}">
		<input type="hidden" name="faxid" value="{$faxid}">
		<!--  KoKr fix for konquer engine = safari,chrome -->
                <textarea  style="height:100%"  class="detailedViewTextBox" id="description" name="description" cols="90" rows="16">{$DESCRIPTION}</textarea>
        {else}
                <textarea   style="height:100%" class="detailedViewTextBox" id="description" name="description" cols="90" rows="16">{$elements.3.0}</textarea>        {/if}
	</td>
   </tr>   
	{/if}
	{/foreach}
	{/foreach}
</tbody>
</table>
</form>
</body>
<script>
var cc_err_msg = '{$MOD.LBL_CC_EMAIL_ERROR}';
var no_rcpts_err_msg = '{$MOD.LBL_NO_RCPTS_EMAIL_ERROR}';
var conf_fax_srvr_err_msg = '{$MOD.LBL_CONF_MAILSERVER_ERROR}';
var no_subject = '{$MOD.MESSAGE_NO_SUBJECT}';
var no_subject_label = '{$MOD.LBL_NO_SUBJECT}';
{literal}
function get_estensione(path) {
    posizione_punto=path.lastIndexOf(".");
	lunghezza_stringa=path.length;
	estensione=path.substring(posizione_punto+1,lunghezza_stringa);
	return estensione;
}

function controlla_estensione(path){
 	var fileext= get_estensione(path);
	if ((fileext != 'pdf') && (fileext != 'PDF') && (fileext != 'ps') && (fileext != 'PS') && (fileext != 'TIFF') && (fileext != 'tiff')){
		return true;
	}
}
function fax_validate(oform,mode)
{
	if(trim(mode) == '')
	{
		return false;
	}
	if(oform.parent_name && oform.parent_name.value.replace(/^\s+/g, '').replace(/\s+$/g, '').length==0) // crmv@152701
	{
		//alert('No recipients were specified');
    //ds@6 send a fax to external receiver
    if(oform.check_to_fax && !oform.check_to_fax.checked || oform.to_fax.value.replace(/^\s+/g, '').replace(/\s+$/g, '').length==0) // crmv@152701
		{
			alert(no_rcpts_err_msg);
  		return false;
    }
    //ds@6e
	}
	if(oform.subject.value.replace(/^\s+/g, '').replace(/\s+$/g, '').length==0)
	{
		if(fax_sub = prompt(no_subject,no_subject_label))
		{
			oform.subject.value = fax_sub;
		}else
		{
			return false;
		}
	}
	if(mode == 'send')
	{
		server_check()	
	}else if(mode == 'save')
	{
		oform.action.value='Save';
		oform.submit();
	}else
	{
		return false;
	}
}
//function to extract the faxaddress inside < > symbols.......for the bug fix #3752
function findAngleBracket(faxadd)
{
        var strlen = faxadd.length;
        var success = 0;
        var gt = 0;
        var lt = 0;
        var ret = '';
        for(i=0;i<strlen;i++){
                if(faxadd[i] == '<' && gt == 0){
                        lt = 1;
                }
                if(faxadd[i] == '>' && lt == 1){
                        gt = 1;
                }
                if(faxadd[i] != '<' && lt == 1 && gt == 0)
                        ret = ret + faxadd[i];

        }
        if(/^[a-z0-9]([a-z0-9_\-\.]*)@([a-z0-9_\-\.]*)(\.[a-z]{2,3}(\.[a-z]{2}){0,2})$/.test(ret)){
                return true;
        }
        else
                return false;

}

//ds@31 bugfix spaces
function trim(s) 
{
	while (s.substring(0,1) == " ")
	{
		s = s.substring(1, s.length);
	}
	while (s.substring(s.length-1, s.length) == ' ')
	{
		s = s.substring(0,s.length-1);
	}
	return s;
}
//ds@31e

// crmv@192033
function server_check()
{
	var oform = window.document.EditView;
	jQuery.ajax({
		url:'index.php',
		method: 'post',
		data: "module=Fax&action=FaxAjax&file=Save&ajax=true&server_check=true",
		success: function(result)
		{
			//ds@31 bugfix spaces
			var trimed_response = trim(result);
			if(trimed_response == 'SUCESS')
			//ds@31e
			{
				oform.send_fax.value='true';
				oform.action.value='Save';
				oform.submit();
			}
			else
			{
				alert(conf_fax_srvr_err_msg);
				return false;
			}
		}
	});
}

jQuery('#attach_cont_fax').html(jQuery('#attach_temp_cont_fax').html());	//crmv@22139

function delAttachments(id) {
	jQuery.ajax({
		url:'index.php',
		method: 'post',
		data: 'module=Contacts&action=ContactsAjax&file=DelImage&attachmodule=Emails&recordid='+id,
		success: function(result) {
			jQuery('#row_'+id).fadeOut(); // crmv@168103
		}
	});
}
// crmv@192033e
{/literal}
</script>
<!--crmv@10621-->
<script type="text/javascript" src="include/ckeditor/ckeditor.js"></script>
<script type="text/javascript" defer="1">
var current_language_arr = "{$AUTHENTICATED_USER_LANGUAGE}".split("_"); // crmv@181170
var curr_lang = current_language_arr[0];
{literal}
CKEDITOR.replace('description', {
	filebrowserBrowseUrl: 'include/ckeditor/filemanager/index.html',
	toolbar : 'Basic',	//crmv@31210
	language : curr_lang
});	
//crmv@22566
jQuery(document).ready(function() {
	loadedPopup();
});
//crmv@22566 e
{/literal}	
</script>
<!--crmv@10621 e-->
</html>