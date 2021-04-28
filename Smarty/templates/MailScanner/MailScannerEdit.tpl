{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@56233 *}

<script language="JAVASCRIPT" type="text/javascript" src="include/js/smoothscroll.js"></script>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 --> 
<tbody>
<tr>
	<td valign="top"></td>
    <td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->

	<form action="index.php" method="post" id="form" onsubmit="VteJS_DialogBox.block();return MailScannerBox.validateAndSave();"> {* crmv@178441 *}
		<input type='hidden' name='module' value='Settings'>
		<input type='hidden' name='action' value='MailScanner'>
		<input type='hidden' name='mode' value='save'>
		<input type='hidden' name='return_action' value='MailScanner'>
		<input type='hidden' name='return_module' value='Settings'>
		<input type='hidden' name='parenttab' value='Settings'>
		<input type='hidden' name='savemode' value='{$SAVEMODE}'>
		<input type='hidden' id='imap_params' name='imap_params' value=''> {* crmv@178441 *}
		
		<div align=center>
			{include file='SetMenu.tpl'}
			{include file='Buttons_List.tpl'} {* crmv@30683 *}
				<!-- DISPLAY -->
				<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
				<tr>
					<td width=50 rowspan=2 valign=top><img src="{'mailScanner.gif'|resourcever}" alt="{$MOD.LBL_MAIL_SCANNER}" width="48" height="48" border=0 title="{$MOD.LBL_MAIL_SCANNER}"></td>
					<td class=heading2 valign=bottom><b>{$MOD.LBL_SETTINGS} > {$MOD.LBL_MAIL_SCANNER}</b></td> <!-- crmv@30683 -->
				</tr>
				<tr>
					<td valign=top class="small">{$MOD.LBL_MAIL_SCANNER_DESCRIPTION}</td>
				</tr>
				</table>
				
				<br>
				<table border=0 cellspacing=0 cellpadding=10 width=100% >
				<tr>
				<td>

				{if $CONNECTFAIL neq ''}
					<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
					<tr>
						<td align="center" width="100%"><font color='red'><b>{$CONNECTFAIL}</b></font></td>
					</tr>
					</table>
				{/if}

				<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
				<tr>
				<td class="big" width="70%"><strong>{$MOD.LBL_MAILBOX} {$MOD.LBL_INFORMATION}</strong></td>
				</tr>
				</table>
				
				<table border=0 cellspacing=0 cellpadding=0 width=100% class="listRow">
				<tr>
	         	    <td class="small" valign=top ><table width="100%"  border="0" cellspacing="0" cellpadding="5">
						<tr>
                            <td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_SCANNER} {$MOD.LBL_NAME}</strong> <font color="red">*</font></td>
                            <td width="80%">
                            	<input type="hidden" name="hidden_scannername" class="small" value="{$SCANNERINFO.scannername}" readonly>
                            	<input type="text" name="mailboxinfo_scannername" class="small" value="{$SCANNERINFO.scannername}" size=50 {if $SAVEMODE eq 'edit'}style="background-color: #E8E8E8;" readonly{/if}>
                            </td>
                        </tr>
                        <tr>
                            <td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_SERVER} {$MOD.LBL_NAME}</strong> <font color="red">*</font></td>
                            <td width="80%"><input type="text" name="mailboxinfo_server" class="small" value="{$SCANNERINFO.server}" size=50></td>
                        </tr>
                        <tr>
							<td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_PROTOCOL}</strong> <font color="red">*</font></td>
                            <td width="80%">
								{assign var="imapused" value=""}
								{assign var="imap4used" value=""}

								{if $SCANNERINFO.protocol eq 'imap4'}
									{assign var="imap4used" value="checked='true'"}
								{else}
									{assign var="imapused" value="checked='true'"}
								{/if}
							
								<input type="radio" name="mailboxinfo_protocol" class="small" value="imap" {$imapused}> {$MOD.LBL_IMAP2}
								<input type="radio" name="mailboxinfo_protocol" class="small" value="imap4" {$imap4used}> {$MOD.LBL_IMAP4}
							</td>
						</tr>
						<tr>
			                <td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_USERNAME}</strong> <font color="red">*</font></td>
                            <td width="80%"><input type="text" name="mailboxinfo_username" class="small" value="{$SCANNERINFO.username}" size=50></td>
                        </tr>
						<tr>
			                <td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_PASSWORD}</strong> <font color="red">*</font></td>
                            <td width="80%">
                            	{* crmv@43764 *}
								<input type="password" value="{if !empty($SCANNERINFO.password)}********{/if}" class="small" onFocus="this.value='';" onChange="document.getElementById('mailboxinfo_password').value=this.value;" size=50 />
								<input type="hidden" id="mailboxinfo_password" name="mailboxinfo_password" value="">
								{* crmv@43764e *}
                            </td>
                        </tr>
						<tr>
			                <td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_SSL} {$MOD.LBL_TYPE}</strong></td>
               				<td width="80%" class="small cellText">
								{assign var="notls_type" value=""}
								{assign var="tls_type" value=""}
								{assign var="ssl_type" value=""}

								{if $SCANNERINFO.ssltype eq 'notls'}
									{assign var="notls_type" value="checked='true'"}
								{elseif $SCANNERINFO.ssltype eq 'tls'}
									{assign var="tls_type" value="checked='true'"}
								{elseif $SCANNERINFO.ssltype eq 'ssl'}
									{assign var="ssl_type" value="checked='true'"}
								{/if}

								<input type="radio" name="mailboxinfo_ssltype" class="small" value="notls" {$notls_type}> {$MOD.LBL_NO} {$MOD.LBL_TLS}
								<input type="radio" name="mailboxinfo_ssltype" class="small" value="tls" {$tls_type}> {$MOD.LBL_TLS}
								<input type="radio" name="mailboxinfo_ssltype" class="small" value="ssl" {$ssl_type}> {$MOD.LBL_SSL}
							</td>
                        </tr>
						<tr>
			                <td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_SSL} {$MOD.LBL_METHOD}</strong></td>
							<td width="80%" class="small cellText">
								{assign var="novalidatecert_type" value=""}
								{assign var="validatecert_type" value=""}

								{if $SCANNERINFO.sslmethod eq 'validate-cert'}
									{assign var="validatecert_type" value="checked='true'"}
								{else}
									{assign var="novalidatecert_type" value="checked='true'"}
								{/if}

								<input type="radio" name="mailboxinfo_sslmethod" class="small" value="validate-cert" {$validatecert_type}> {$MOD.LBL_VAL_SSL_CERT}
								<input type="radio" name="mailboxinfo_sslmethod" class="small" value="novalidate-cert" {$novalidatecert_type}> {$MOD.LBL_DONOT_VAL_SSL_CERT}
							</td>
                        </tr>
                        {* crmv@178441 *}
                        <tr>
                        	<td width="20%" nowrap class="small cellLabel" valign="top"><strong>{$MOD.LBL_PARAMETERS}</strong></td>
							<td width="80%" class="small cellText">
								<table id="params_table" {if !is_array($SCANNERINFO.imap_params) || count($SCANNERINFO.imap_params) == 0}style="display:none;"{/if} width="50%" cellspacing="5"> 
									<thead>
										<tr>
											<th class="extws_subfield_header" width="40%">{$MOD.LBL_PARAMETER_NAME}</th>
											<th class="extws_subfield_header">{$MOD.LBL_PARAMETER_VALUE}</th>
											<th></th>
										</tr>
									</thead>
									<tbody>
										<tr id="param_row_tpl" style="display:none;">
											<td><input class="detailedViewTextBox paramname" type="text" maxlength="60"></td>
											<td><input class="detailedViewTextBox paramvalue" type="text"></td>
											<td>
												<i class="vteicon md-link md-sm" onclick="MailScannerBox.delParam(this);">delete</i>
											</td>
										</tr>
										{foreach from=$SCANNERINFO.imap_params item=param}
											<tr>
												<td><input class="detailedViewTextBox paramname" type="text" maxlength="60" value="{$param.name}"></td>
												<td><input class="detailedViewTextBox paramvalue" type="text" value="{$param.value}"></td>
												<td>
													<i class="vteicon md-link md-sm" onclick="MailScannerBox.delParam(this);">delete</i>
												</td>
											</tr>
										{/foreach}
									</tbody>
								</table>
								<button class="crmbutton small edit" id="add_param" type="button" onclick="MailScannerBox.addParam();">{$MOD.LBL_ADD_PARAMETER}</button>
							</td>
                        </tr>
                        <tr>
			                <td width="20%" nowrap class="small cellLabel"><strong>PEC</strong></td>
							<td width="80%" class="small cellText">
								<input type="checkbox" name="is_pec" {if $SCANNERINFO.is_pec eq true}checked{/if} />
							</td>
                        </tr>
                        {* crmv@178441e *}
						<tr>
			                <td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_STATUS}</strong></td>
							<td width="80%" class="small cellText">
								{assign var="mailbox_enable" value=""}
								{assign var="mailbox_disable" value=""}

								{if $SCANNERINFO.isvalid eq false}
									{assign var="mailbox_disable" value="checked='true'"}
								{else}
									{assign var="mailbox_enable" value="checked='true'"}
								{/if}

								<input type="radio" name="mailboxinfo_enable" class="small" value="true" {$mailbox_enable}> {$MOD.LBL_ENABLE}
								<input type="radio" name="mailboxinfo_enable" class="small" value="false" {$mailbox_disable}> {$MOD.LBL_DISABLE}
							</td>
                        </tr>
				    </td>
            	</tr>

				<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
				<tr>
				<td class="big" width="70%"><strong>{$MOD.LBL_SCANNING} {$MOD.LBL_INFORMATION}</strong></td>
				<td width="30%" nowrap align="right">&nbsp;</td>
				</tr>
				</table>

				<table border=0 cellspacing=0 cellpadding=0 width=100% class="listRow">
				<tr>
	         	    <td class="small" valign=top ><table width="100%"  border="0" cellspacing="0" cellpadding="5">
						<tr>
                            <td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_LOOKFOR}</strong></td>
                            <td width="80%" class="small cellText"> 
                            {* crmv@111580 *}
							<select name="mailboxinfo_searchfor" class="small" onchange="mailscanneredit_changeSearchfor(this);">
								<option value="ALL" {if $SCANNERINFO.searchfor eq 'ALL'}selected="true"{/if}>{$MOD.LBL_ALL}</option>
								<option value="UNSEEN" {if $SCANNERINFO.searchfor eq 'UNSEEN'}selected="true"{/if}>{$MOD.LBL_UNREAD}</option> 
							</select> {$MOD.LBL_MESSAGES_FROM_LASTSCAN}
								{* crmv@36562 *}
								<span id="mailboxinfo_rescan_folders_span" {if $SCANNERINFO.searchfor neq 'ALL'}style="display:none;"{/if} >
									<input type="checkbox" id="mailboxinfo_rescan_folders" name="mailboxinfo_rescan_folders" value="true" class="small" {if $SCANNERINFO.requireRescan}checked=true{/if} /> {$MOD.LBL_RESCAN_FOLDERS}
								</span>
								{*crmv@36562e crmv@111580e *}
							</td>
                        </tr>
						<tr>
                            <td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_AFTER_SCAN}</strong></td>
                            <td width="80%" class="small cellText">{$MOD.LBL_MARK_MESSAGE_AS}
							<select name="mailboxinfo_markas" class="small">
								<option value=""></option>
								<option value="SEEN" {if $SCANNERINFO.markas eq 'SEEN'}selected=true{/if} >{$MOD.LBL_READ}</option> 
							</select>	
							</td>
                        </tr>
                        {* crmv@2043m *}
                        {if $SCANNERINFO.scannername neq ''}
							<tr>
	                            <td width="20%" nowrap class="small cellLabel"></td>
	                            <td width="80%" class="small cellText">{$MOD.LBL_MOVE_MESSAGE}
	                            <select name="mailboxinfo_succ_moveto" class="small">
	                            <option value=""></option>
	                            {foreach item=FOLDER key=FOLDERNAME from=$FOLDERINFO}
									<option value="{$FOLDERNAME}" {if $SCANNERINFO.succ_moveto eq $FOLDERNAME}selected=true{/if} >{$FOLDERNAME}</option> 
								{/foreach}
								</select>
								<br />{$MOD.LBL_MOVE_MESSAGE_ELSE}
								<select name="mailboxinfo_no_succ_moveto" class="small">
	                            <option value=""></option>
	                            {foreach item=FOLDER key=FOLDERNAME from=$FOLDERINFO}
									<option value="{$FOLDERNAME}" {if $SCANNERINFO.no_succ_moveto eq $FOLDERNAME}selected=true{/if} >{$FOLDERNAME}</option> 
								{/foreach}
								</select>
								</td>
	                        </tr>
						{/if}
                        {* crmv@2043me *}
					</td>
				</tr>
				</table>
				<tr height="25px"><td colspan=2></td></tr>
				<tr>
					<td colspan=2 nowrap align="center">
						<input type="submit" class="crmbutton small save" value="{$APP.LBL_SAVE_LABEL}" onclick="return mailscaneredit_validateform(this.form);" />
						<input type="button" class="crmbutton small cancel" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" onclick="window.location.href='index.php?module=Settings&action=MailScanner&parenttab=Settings'"/>
					</td>
				</tr>
				</table>	
				
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

</tr>
</table>

</tr>
</table>
{literal}
<script type="text/javascript">
function mailscaneredit_validateform(form) {
	var scannername = form.mailboxinfo_scannername;
	if(scannername.value == '') {
		scannername.focus();
		return false;
	}
	return true;		
}

// crmv@111580
function mailscanneredit_changeSearchfor(self) {
	var val = jQuery(self).val();
	jQuery('#mailboxinfo_rescan_folders_span')[val == 'ALL' ? 'show' : 'hide']();
	if (val == 'UNSEEN') {
		jQuery('#mailboxinfo_rescan_folders').prop('checked', false);
	}
}
// crmv@111580e

// crmv@178441
var MailScannerBox = MailScannerBox || {
	
	addParam: function() {
		var table = jQuery('#params_table');
		var tpl = jQuery('#param_row_tpl');
		
		var newrow = tpl.clone().show();
		table.append(newrow);
		table.show();
	},
	
	delParam: function(self) {
		var table = jQuery('#params_table');
		
		// remove row
		jQuery(self).closest('tr').remove();
		
		// if only 2 rows are left (header and tpl), hide it
		if (table.find('tr').length <= 2) table.hide();
	},
	
	validateAndSave: function() {
		var me = this;
		me.prepareFieldForSave();
		if (!me.validate()) return false;
		return true;
	},
	
	prepareFieldForSave: function() {
		var params = [];
			
		var ptable = jQuery('#params_table');
		ptable.find('input.paramname').each(function(idx, hval) {
			var hname = jQuery(hval).val();
			if (hname !== '') {
				var hval = jQuery(hval).closest('td').next().find('input.paramvalue').val();
				params.push({
					name: hname,
					value: hval,
				});
			}
		});
		
		jQuery('#imap_params').val(JSON.stringify(params));
	},
	
	validate: function() {
		return true;	
	},
	
};
//crmv@178441e
</script>
{/literal}