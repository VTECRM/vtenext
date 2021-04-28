{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@192033 *}
<!--crmv@8056-->
<script language="JAVASCRIPT" type="text/javascript" src="include/js/smoothscroll.js"></script>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
<tbody><tr>
        <td valign="top"></td>
        <td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->
<form action="index.php" method="post" id="form">
<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
<input type='hidden' name='module' value='Users'>
<input type='hidden' name='action' value='DefModuleView'>
<input type='hidden' name='return_action' value='ListView'>
<input type='hidden' name='return_module' value='Users'>
<input type='hidden' name='parenttab' value='Settings'>

<input type='hidden' name='backup_invoice_no' id='backup_invoice_no' value='{$inv_no}'>
<input type='hidden' name='backup_quote_no' id='backup_quote_no' value='{$quote_no}'>
<input type='hidden' name='backup_sorder_no' id='backup_sorder_no' value='{$sorder_no}'>
<input type='hidden' name='backup_porder_no' id='backup_porder_no' value='{$porder_no}'>

<!-- vtc -->
<input type='hidden' name='backup_note_no' id='backup_note_no' value='{$note_no}'>
<!-- vtc e -->

	<div align=center>
			{include file='SetMenu.tpl'}
				<!-- DISPLAY -->
				<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
				<tr>
					<td width=50 rowspan=2 valign=top><img src="{'settingsInvNumber.gif'|resourcever}" alt="{$MOD.LBL_CUSTOMIZE_INVOICE_NUMBER}" width="48" height="48" border=0 title="{$MOD.LBL_CUSTOMIZE_INVOICE_NUMBER}"></td>
					<td class=heading2 valign=bottom><b> {$MOD.LBL_SETTINGS} > {$MOD.LBL_CUSTOMIZE_INVOICE_NUMBER}</b></td> <!-- crmv@30683 -->
				</tr>
				<tr>
					<td valign=top class="small">{$MOD.LBL_CUSTOMIZE_INVOICE_NUMBER_DESCRIPTION}</td>
				</tr>
				</table>
				
				<br>
				<table border=0 cellspacing=0 cellpadding=10 width=100% >
				<tr>
				<td>
				
				<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
				<tr>
				<td class="big" height="40px;" width="70%"><strong>{$MOD.LBL_CUSTOM_INVOICE_NUMBER_VIEW}</strong></td>
				<td class="small" align="center" width="30%">&nbsp;
					<span id="view_info" class="crmButton small cancel" style="display:none;">{$MOD.LBL_SUCCESSFULLY_UPDATED}</span>
				</td>
				</tr>
				</table>
			
							<table border=0 cellspacing=0 cellpadding=0 width=100% class="listRow">
			<tr>
	         	    <td class="small" valign=top ><table width="100%"  border="0" cellspacing="0" cellpadding="5">
                        <tr>
                            <td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_CUSTOMINVOICE_STRING}</strong></td>
                            <td width="80%" class="small cellText">


<input type="text" id="invoicestring" name="invoicestring" class="small" style="width:30%" value="{$inv_str}" onkeyup="preview();" onchange="preview();" />
			</td>
                        </tr>


                        <tr>

		<td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_CUSTOMINVOICE_NUMBER}</strong>
		</td>
                <td width="80%" class="small cellText">
<input type="text" id="invoicenumber" name="invoicenumber" class="small" style="width:30%" value="{$inv_no}"  onkeyup="preview();" onchange="preview();" />
</td>
			</tr>

<tr>

                <td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_INVOICE_NUMBER_PREVIEW}</strong>
                </td>
                <td width="80%" class="small cellText" id="invoicepreview" style="font-weight:bold">{$inv_str}{$inv_no}</td>
                        </tr>

<tr>

                <td width="20%" nowrap colspan="2" align ="center">


<input type="button" name="Button" class="crmbutton small create" value="{$MOD.LBL_INVOICE_NUMBER_BUTTON}" onclick="validatefn1();" />

        </td>
                        </tr>

                       
                        </table>
	    </td>
                        </tr>


                        </table>

				{include file="Settings/ScrollTop.tpl"}
<!-- quotes -->
		<tr>
		<td>
				
			<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
			<tr>
			<td class="big" height="40px;" width="70%"><strong>{$MOD.LBL_CUSTOM_QUOTE_NUMBER_VIEW}</strong></td>
			<td class="small" align="center" width="30%">&nbsp;
				<span id="view_info_quote" class="crmButton small cancel" style="display:none;">{$MOD.LBL_SUCCESSFULLY_UPDATED}</span>
			</td>
			</tr>
			</table>
			
			<table border=0 cellspacing=0 cellpadding=0 width=100% class="listRow">
			<tr>
			<td class="small" valign=top >
	
					<table width="100%"  border="0" cellspacing="0" cellpadding="5">
					<tr>
					<td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_CUSTOMQUOTE_STRING}</strong>
					</td>
					<td width="80%" class="small cellText">
					<input type="text" id="quotestring" name="quotestring" class="small" style="width:30%" value="{$quote_str}" onkeyup="preview_quote();"/>
					</td>
					</tr>
			
			
					<tr>
					<td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_CUSTOMQUOTE_NUMBER}</strong>
					</td>
					<td width="80%" class="small cellText">
					<input type="text" id="quotenumber" name="quotenumber" class="small" style="width:30%" value="{$quote_no}"  onkeyup="preview_quote();"/>
					</td>
					</tr>
					
					<tr>
					<td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_QUOTE_NUMBER_PREVIEW}</strong>
					</td>
					<td width="80%" class="small cellText" id="quotepreview" style="font-weight:bold">{$quote_str}{$quote_no}</td>
					</tr>
					
					<tr>
					<td width="20%" nowrap colspan="2" align ="center">
					<input type="button" name="Button2" class="crmbutton small create" value="{$MOD.LBL_QUOTE_NUMBER_BUTTON}" onclick="validatefn1_quote();" />
					</td>
					</tr>
					</table>
			</td>
			</tr>
			</table>	
				
			{include file="Settings/ScrollTop.tpl"}
			<!-- end : quotes -->
	
		<!-- porders -->
		<tr>
		<td>
				
			<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
			<tr>
			<td class="big" height="40px;" width="70%"><strong>{$MOD.LBL_CUSTOM_PORDER_NUMBER_VIEW}</strong></td>
			<td class="small" align="center" width="30%">&nbsp;
				<span id="view_info_porder" class="crmButton small cancel" style="display:none;">{$MOD.LBL_SUCCESSFULLY_UPDATED}</span>
			</td>
			</tr>
			</table>
			
			<table border=0 cellspacing=0 cellpadding=0 width=100% class="listRow">
			<tr>
			<td class="small" valign=top >
	
					<table width="100%"  border="0" cellspacing="0" cellpadding="5">
					<tr>
					<td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_CUSTOMPORDER_STRING}</strong>
					</td>
					<td width="80%" class="small cellText">
					<input type="text" id="porderstring" name="porderstring" class="small" style="width:30%" value="{$porder_str}" onkeyup="preview_porder();"/>
					</td>
					</tr>
			
			
					<tr>
					<td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_CUSTOMPORDER_NUMBER}</strong>
					</td>
					<td width="80%" class="small cellText">
					<input type="text" id="pordernumber" name="pordernumber" class="small" style="width:30%" value="{$porder_no}"  onkeyup="preview_porder();"/>
					</td>
					</tr>
					
					<tr>
					<td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_PORDER_NUMBER_PREVIEW}</strong>
					</td>
					<td width="80%" class="small cellText" id="porderpreview" style="font-weight:bold">{$porder_str}{$porder_no}</td>
					</tr>
					
					<tr>
					<td width="20%" nowrap colspan="2" align ="center">
					<input type="button" name="Button2" class="crmbutton small create" value="{$MOD.LBL_PORDER_NUMBER_BUTTON}" onclick="validatefn1_porder();" />
					</td>
					</tr>
					</table>
			</td>
			</tr>
			</table>	
				
			{include file="Settings/ScrollTop.tpl"}
			<!-- end : porders -->
	
		<!-- sorders -->
		<tr>
		<td>
				
			<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
			<tr>
			<td class="big" height="40px;" width="70%"><strong>{$MOD.LBL_CUSTOM_SORDER_NUMBER_VIEW}</strong></td>
			<td class="small" align="center" width="30%">&nbsp;
				<span id="view_info_sorder" class="crmButton small cancel" style="display:none;">{$MOD.LBL_SUCCESSFULLY_UPDATED}</span>
			</td>
			</tr>
			</table>
			
			<table border=0 cellspacing=0 cellpadding=0 width=100% class="listRow">
			<tr>
			<td class="small" valign=top >
	
					<table width="100%"  border="0" cellspacing="0" cellpadding="5">
					<tr>
					<td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_CUSTOMSORDER_STRING}</strong>
					</td>
					<td width="80%" class="small cellText">
					<input type="text" id="sorderstring" name="sorderstring" class="small" style="width:30%" value="{$sorder_str}" onkeyup="preview_sorder();"/>
					</td>
					</tr>
			
			
					<tr>
					<td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_CUSTOMSORDER_NUMBER}</strong>
					</td>
					<td width="80%" class="small cellText">
					<input type="text" id="sordernumber" name="sordernumber" class="small" style="width:30%" value="{$sorder_no}"  onkeyup="preview_sorder();"/>
					</td>
					</tr>
					
					<tr>
					<td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_SORDER_NUMBER_PREVIEW}</strong>
					</td>
					<td width="80%" class="small cellText" id="sorderpreview" style="font-weight:bold">{$sorder_str}{$sorder_no}</td>
					</tr>
					
					<tr>
					<td width="20%" nowrap colspan="2" align ="center">
					<input type="button" name="Button2" class="crmbutton small create" value="{$MOD.LBL_SORDER_NUMBER_BUTTON}" onclick="validatefn1_sorder();" />
					</td>
					</tr>
					</table>
			</td>
			</tr>
			</table>	
				
			{include file="Settings/ScrollTop.tpl"}
			<!-- end : sorders -->

		<!-- vtc : note -->
		<tr>
		<td>
				
			<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
			<tr>
			<td class="big" height="40px;" width="70%"><strong>{$MOD.LBL_CUSTOM_NOTE_NUMBER_VIEW}</strong></td>
			<td class="small" align="center" width="30%"> 
				<span id="view_info_note" class="crmButton small cancel" style="display:none;">{$MOD.LBL_SUCCESSFULLY_UPDATED}</span>
			</td>
			</tr>
			</table>
			
			<table border=0 cellspacing=0 cellpadding=0 width=100% class="listRow">
			<tr>
			<td class="small" valign=top >
	
					<table width="100%"  border="0" cellspacing="0" cellpadding="5">
					<tr>
					<td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_CUSTOMNOTE_STRING}</strong>
					</td>
					<td width="80%" class="small cellText">
					<input type="text" id="notestring" name="notestring" class="small" style="width:30%" value="{$note_str}" onkeyup="preview_note();"/>
					</td>
					</tr>
			
			
					<tr>
					<td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_CUSTOMNOTE_NUMBER}</strong>
					</td>
					<td width="80%" class="small cellText">
					<input type="text" id="notenumber" name="notenumber" class="small" style="width:30%" value="{$note_no}"  onkeyup="preview_note();"/>
					</td>
					</tr>
					
					<tr>
					<td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_NOTE_NUMBER_PREVIEW}</strong>
					</td>
					<td width="80%" class="small cellText" id="notepreview" style="font-weight:bold">{$note_str}{$note_no}</td>
					</tr>
					
					<tr>
					<td width="20%" nowrap colspan="2" align ="center">
					<input type="button" name="Button2" class="crmbutton small create" value="{$MOD.LBL_NOTE_NUMBER_BUTTON}" onclick="validatefn1_note();" />
					</td>
					</tr>
					</table>
			</td>
			</tr>
			</table>	
				
			{include file="Settings/ScrollTop.tpl"}
			<!-- vtc end : note -->

	
				</td>
				</tr>
				</table>
			
			
			
			</td>
			</tr>
			</table>
		</td>
	</tr>
	</table>
		
	</div>

</td>
        <td valign="top"></td>
   </tr>
</tbody>
</form>
</table>

{literal}
<script>

function setinvoiceid()
{
	var inv_no=document.getElementById("invoicenumber").value;
	var inv_str=document.getElementById("invoicestring").value;

	var inv_no_backup=document.getElementById("backup_invoice_no").value;

	jQuery("#status").show();
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: 'module=Users&action=UsersAjax&file=UpdateCustomInvoiceNo&ajax=true&no='+inv_no+'&str='+inv_str+'&mode=configure_invoiceno&status='+status,
		success: function(result) {
			if (result != '') {
				document.getElementById("invoicenumber").value=inv_no_backup;
				alert(result);
			} else {
				jQuery('#view_info').show();
			}
			jQuery("#status").hide();
		}
	});

	setTimeout("hide('view_info')",3000);
}

function preview() {
	document.getElementById("invoicepreview").innerHTML=(document.getElementById("invoicestring").value + document.getElementById("invoicenumber").value);
}

function preview_quote() {
	document.getElementById("quotepreview").innerHTML=(document.getElementById("quotestring").value + document.getElementById("quotenumber").value);
}

function preview_sorder() {
	document.getElementById("sorderpreview").innerHTML=(document.getElementById("sorderstring").value + document.getElementById("sordernumber").value);
}


function preview_porder() {
	document.getElementById("porderpreview").innerHTML=(document.getElementById("porderstring").value + document.getElementById("pordernumber").value);
}


function validatefn1() {
	preview();
	var invnumber=document.getElementById("invoicenumber").value;
	var invstring=document.getElementById("invoicestring").value;

	var iChars = "!@#$%^&*()+=[]\\\';,.{}|\":<>?";

	for (var i = 0; i < invstring.length; i++) {
		if (iChars.indexOf(invstring.charAt(i)) != -1) {
			alert (alert_arr.NO_SPECIAL_CHARS);
			return false;
		}
	}

	if (!emptyCheck("invoicenumber","Invoice Number","any")) return false
	if (!emptyCheck("invoicestring","Invoice String","text")) return false
	if (!numValidate("invoicenumber","Invoice Number","any")) return false 

	setinvoiceid();
}

function setquoteid()
{
	var quote_no=document.getElementById("quotenumber").value;
	var quote_str=document.getElementById("quotestring").value;
	var quote_no_backup=document.getElementById("backup_quote_no").value;
	
	jQuery("#status").show();
	
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: 'module=Users&action=UsersAjax&file=UpdateCustomQuoteNo&ajax=true&no='+quote_no+'&str='+quote_str+'&mode=configure_quoteno&status='+status,
		success: function(result) {
			if((result != '')){
				document.getElementById("quotenumber").value=quote_no_backup;
				alert(result);
			} else {
				jQuery('#view_info_quote').show();
			}
			jQuery("#status").hide();
		}
	});

	setTimeout("hide('view_info_quote')",3000);
}


function validatefn1_quote()
{
	var quotenumber=document.getElementById("quotenumber").value;
	var quotestring=document.getElementById("quotestring").value;


	var iChars = "!@#$%^&*()+=[]\\\';,.{}|\":<>?";

	for (var i = 0; i < quotestring.length; i++)
	{
		if (iChars.indexOf(quotestring.charAt(i)) != -1)
		{
			alert (alert_arr.NO_SPECIAL_CHARS);
			return false;
		}
	}

	if (!emptyCheck("quotenumber","Quote Number","any")) return false
	if (!emptyCheck("quotestring","Quote String","text")) return false
	if (!numValidate("quotenumber","Quote Number","any")) return false 

	setquoteid();
}


function setporderid()
{
	var porder_no=document.getElementById("pordernumber").value;
	var porder_str=document.getElementById("porderstring").value;
	var porder_no_backup=document.getElementById("backup_porder_no").value;
	
	jQuery("#status").show();
	
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: 'module=Users&action=UsersAjax&file=UpdateCustomPorderNo&ajax=true&no='+porder_no+'&str='+porder_str+'&mode=configure_porderno&status='+status,
		success: function(result) {
			if((result != '')){
				document.getElementById("pordernumber").value=porder_no_backup;
				alert(result);
			} else {
				jQuery('#view_info_porder').show();
			}
			jQuery("#status").hide();
		}
	});

	setTimeout("hide('view_info_porder')",3000);

}


function validatefn1_porder()
{
	var pordernumber=document.getElementById("pordernumber").value;
	var porderstring=document.getElementById("porderstring").value;

	var iChars = "!@#$%^&*()+=[]\\\';,.{}|\":<>?";

	for (var i = 0; i < porderstring.length; i++)
	{
		if (iChars.indexOf(porderstring.charAt(i)) != -1)
		{
			alert (alert_arr.NO_SPECIAL_CHARS);
			return false;
		}
	}

	if (!emptyCheck("pordernumber","Porder Number","any")) return false
	if (!emptyCheck("porderstring","Porder String","text")) return false
	if (!numValidate("pordernumber","Porder Number","any")) return false 

	setporderid();
}


function setsorderid()
{
	var sorder_no=document.getElementById("sordernumber").value;
	var sorder_str=document.getElementById("sorderstring").value;
	var sorder_no_backup=document.getElementById("backup_sorder_no").value;
	jQuery("#status").show();
	
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: 'module=Users&action=UsersAjax&file=UpdateCustomSorderNo&ajax=true&no='+sorder_no+'&str='+sorder_str+'&mode=configure_sorderno&status='+status,
		success: function(response) {
			if((response != '')){
				document.getElementById("sordernumber").value=sorder_no_backup;
				alert(response);
			} else {
				jQuery('#view_info_sorder').show();
			}
			jQuery("#status").hide();
		}
	});

	setTimeout("hide('view_info_sorder')",3000);
}

function validatefn1_sorder()
{
	var sordernumber=document.getElementById("sordernumber").value;
	var sorderstring=document.getElementById("sorderstring").value;

	var iChars = "!@#$%^&*()+=[]\\\';,.{}|\":<>?";

	for (var i = 0; i < sorderstring.length; i++)
	{
		if (iChars.indexOf(sorderstring.charAt(i)) != -1)
		{
			alert (alert_arr.NO_SPECIAL_CHARS);
			return false;
		}
	}

	if (!emptyCheck("sordernumber","Sorder Number","any")) return false
	if (!emptyCheck("sorderstring","Sorder String","text")) return false
	if (!numValidate("sordernumber","Sorder Number","any")) return false 

	setsorderid();
}

//vtc
function preview_note()
{
	document.getElementById("notepreview").innerHTML=(document.getElementById("notestring").value + document.getElementById("notenumber").value);
}

function setnoteid()
{
	var note_no=document.getElementById("notenumber").value;
	var note_str=document.getElementById("notestring").value;
	var note_no_backup=document.getElementById("backup_note_no").value;
	jQuery("#status").show();

	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: 'module=Users&action=UsersAjax&file=UpdateCustomNoteNo&ajax=true&no='+note_no+'&str='+note_str+'&mode=configure_noteno&status='+status,
		success: function(result) {
			if((result != '')){
				document.getElementById("notenumber").value=note_no_backup;
				alert(result);
			} else {
				jQuery('#view_info_note').show();
			}
			jQuery("#status").hide();
		}
	});

	setTimeout("hide('view_info_note')",3000);
}

function validatefn1_note()
{
	var notenumber=document.getElementById("notenumber").value;
	var notestring=document.getElementById("notestring").value;

	var iChars = "!@#$%^&*()+=[]\\\';,.{}|\":<>?";

	for (var i = 0; i < notestring.length; i++)
	{
		if (iChars.indexOf(notestring.charAt(i)) != -1)
		{
			alert (alert_arr.NO_SPECIAL_CHARS);
			return false;
		}
	}

	if (!emptyCheck("notenumber","Note Number","any")) return false
	if (!emptyCheck("notestring","Note String","text")) return false
	if (!numValidate("notenumber","Note Number","any")) return false 

	setnoteid();

}
//vtc e

</script>
{/literal}
<!--crmv@8056e-->