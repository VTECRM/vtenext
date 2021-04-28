{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@43147 *}
<style type="text/css">
{literal}
.downloadButton {
	margin-top: 10px;
	padding: 10px;
	min-height: 50px;
	vertical-align: middle;
	cursor: pointer;
}
.downloadButton:hover {
	Filter: Alpha(opacity = 70);
    -moz-opacity: 0.7;
    opacity: 0.7;
}
.downloadBtnTitle {
	font-size: 20px;
	margin-bottom:4px;
}
.downloadBtnName {
	font-size: 12px;
}
#ajaxMessage {
	padding: 5px;
	color: green;
}
#ajaxError {
	padding: 5px;
	color: red;
}
#uploadTable {
	padding: 10px;
}
{/literal}
</style>
<script type="text/javascript">
{literal}
	function downloadFile() {
		var token = jQuery('#sharetoken').val();

		location.href = "index.php?module=Utilities&action=UtilitiesAjax&file=ShareRecord&sharetoken="+encodeURIComponent(token)+"&share_action=download";
	}

	// very very very basic check
	function validateEmailAddress(email) {
		return email.match(/^[0-9a-z._-]+@[0-9a-z._-]+$/i);
	}

	function sendEditEmail() {
		var token = jQuery('#sharetoken').val(),
			email = jQuery('#visitor_email').val();

		if (!validateEmailAddress(email)) {
			alert('Invalid Email address');
			return;
		}
		jQuery('#loader').show();
		jQuery('#visitor_email').hide();
		jQuery('#ok_button').hide();
		jQuery('#ajaxError').hide();
		jQuery('#ajaxMessage').hide();
		jQuery.ajax({
			url: "index.php?module=Utilities&action=UtilitiesAjax&file=ShareRecord&sharetoken="+encodeURIComponent(token)+"&share_action=getedittoken&visitor_email="+encodeURIComponent(email),
			type: 'POST',
			complete: function() {
				jQuery('#loader').hide();
				jQuery('#visitor_email').show();
				jQuery('#ok_button').show();
			},
			success: function(data) {
				if (!data) {
					jQuery('#ajaxError').html('Server error').show();
					return;
				}
				try {
					var result = JSON.parse(data);
				} catch (e) {
					jQuery('#ajaxError').html('Server error').show();
					return;
				}
				if (result.error) {
					jQuery('#ajaxError').html(result.error).show();
				} else {
					jQuery('#ajaxMessage').html(result.message).show();
				}
			},
			error: function() {
				jQuery('#ajaxError').html('Request Error').show();
			}
		});
	}
{/literal}
</script>

<input type="hidden" name="sharetoken" id="sharetoken" value="{$SHARETOKEN}" />

<div id="spacer" style="height:10px"></div>

<table class="small" width="80%" align="center" border="0" cellspacing="0" cellpadding="0">
	{foreach item=FIELD from=$FIELDS}
		<tr>
			<td>
				<div>
					<span class="dvtCellLabel">{$FIELD.label}</span>
					<div class="dvtCellInfo">
						{$FIELD.value}
					</div>
				</div>
			</td>
		</tr>
	{/foreach}
	{if $DOWNLOAD_LINK neq '' && $DOWNLOAD_NAME neq ''}
		<tr>
			<td colspan="2" align="center">
				<div id="downloadButton" class="downloadButton save" onclick="downloadFile()">
					<div class="downloadBtnTitle">{"LBL_DOWNLOAD_FILE"|getTranslatedString:'Documents'}</div>
					<div class="downloadBtnName">{$DOWNLOAD_NAME}</div>
				</div>
			</td>
		</tr>
	{/if}
</table>

<br>
{if $DOWNLOAD_LINK neq '' && $DOWNLOAD_NAME neq '' && $ENABLE_REVISION}	{* crmv@59514 *}
	<br>
	<table id="uploadTable" cellspacing="0" cellpadding="0" align="center">
		<tr><td align="center">
		{if $EDIT_PERM}
			<div style="width:350px">{'LBL_SHARE_LOAD_FILE'|getTranslatedString}</div><br>
			<form method="POST" enctype="multipart/form-data" action="index.php?sharetoken={$SHARETOKEN}">
				<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
				<input type="hidden" name="module" value="Utilities" />
				<input type="hidden" name="action" value="UtilitiesAjax" />
				<input type="hidden" name="file" value="ShareRecord" />
				<input type="hidden" name="share_action" value="upload" />
				<input type="file" name="filename" /><br><br>
				<input type="submit" class="small crmbutton" value="{'LBL_UPLOAD'|getTranslatedString:'Settings'}" />
			</form>
			{if $UPLOAD_STATUS neq ''}
				{if $UPLOAD_STATUS}
					<div style="color:green">{'LBL_UPLOAD_SUCCESS'|getTranslatedString}</div>
				{else}
					<div style="color:red">{'LBL_UPLOAD_FAILED'|getTranslatedString}</div>
				{/if}
			{/if}
		{else}
			<div style="width:350px">{'LBL_SHARE_INSERT_EMAIL'|getTranslatedString}</div><br>
			<span class="dvtCellInfo">
				<input class="small detailedViewTextBox" type="text" name="visitor_email" id="visitor_email" style="width:150px" />
			</span>
			<input class="small crmbutton" type="button" id="ok_button" value="{'LBL_SHARE_NEXT'|getTranslatedString}" onclick="sendEditEmail()" />
			<div style="display:none" id="loader">{include file="LoadingIndicator.tpl"}</div>
			<div style="display:none" id="ajaxMessage">OK</div>
			<div style="display:none" id="ajaxError">ERR</div>
		{/if}
		</td></tr>
	</table>
{/if}