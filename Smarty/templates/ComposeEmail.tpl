{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@21048m	crmv@22123	crmv@10621	crmv@25356	crmv@2963m	crmv@59091 *}
{assign var="BROWSER_TITLE" value='LBL_COMPOSE'|getTranslatedString:'Messages'}
{include file="HTMLHeader.tpl" head_include="icons,jquery,jquery_plugins,jquery_ui,fancybox,prototype,file_upload,sdk_headers"}

<body class="small">

{include file="Theme.tpl" THEME_MODE="body"}

<div id="popupContainer" style="display:none;"></div> {* crmv@97214 *}

{* Some variables *}
<script type="text/javascript">
	var cc_err_msg = '{$MOD.LBL_CC_EMAIL_ERROR}';
	var no_rcpts_err_msg = '{$MOD.LBL_NO_RCPTS_EMAIL_ERROR}';
	var bcc_err_msg = '{$MOD.LBL_BCC_EMAIL_ERROR}';
	var conf_mail_srvr_err_msg = '{$MOD.LBL_CONF_MAILSERVER_ERROR}';
	//crmv@7216
	var no_subject = '{$MOD.MESSAGE_NO_SUBJECT}';
	var no_subject_label = '{$MOD.LBL_NO_SUBJECT}';
	//crmv@7216e
	var saving_draft = false;
</script>

{* Extra scripts *}
<script type="text/javascript" src="{"modules/Popup/Popup.js"|resourcever}"></script> {* crmv@43864 *}
<script type="text/javascript" src="{"modules/Emails/Emails.js"|resourcever}"></script>
<script type="text/javascript" src="{"modules/Messages/Messages.js"|resourcever}"></script>

{include file='CachedValues.tpl'}	{* crmv@26316 *}

{foreach item=row from=$BLOCKS.fields} {* crmv@104568 *}
	{foreach item=elements from=$row}
		{if $elements.2.0 eq 'from_email'}
			{assign var=element_from_email value=$elements}
		{elseif $elements.2.0 eq 'parent_id'}
			{assign var=element_parent_id value=$elements}
		{elseif $elements.2.0 eq 'subject'}
			{assign var=element_subject value=$elements}
		{elseif $elements.2.0 eq 'filename'}
			{assign var=element_filename value=$elements}
		{elseif $elements.2.0 eq 'description'}
			{assign var=element_description value=$elements}
		{/if}
	{/foreach}
{/foreach}
<div style="display:none" id="signature_box">{$SIGNATURE}</div> {* crmv@48228 *}
<form name="EditView" method="POST" ENCTYPE="multipart/form-data" action="index.php" onkeypress="return event.keyCode != 13;">
<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
<input type="hidden" name="add2queue" value="true">	{* crmv@48501 *}
<input type="hidden" name="send_mail">
<input type="hidden" name="contact_id" value="{$CONTACT_ID}">
<input type="hidden" name="user_id" value="{$USER_ID}">
<input type="hidden" name="old_id" value="{$OLD_ID}">
<input type="hidden" name="module" value="{$MODULE}">
<input type="hidden" name="record" value="{$ID}">
<input type="hidden" name="mode" value="{$MODE}">
<input type="hidden" name="action" value="Save">
<input type="hidden" name="hidden_toid" id="hidden_toid">
<input type="hidden" name="draft_id" id="draft_id" value="{$DRAFTID}">
{if !empty($smarty.request.message)}
	<input type="hidden" name="message" value="{$smarty.request.message|@vtlib_purify}">{* crmv@211287 *}
	<input type="hidden" name="message_mode" value="{$smarty.request.message_mode|@vtlib_purify}">{* crmv@211287 *}
{/if}
<input type="hidden" name="uploaddir" value="{$UPLOADIR}">
{* crmv@2043m *}
{if $smarty.request.reply_mail_converter neq ''}
	<input type="hidden" name="reply_mail_converter" value="{$smarty.request.reply_mail_converter|@vtlib_purify}">{* crmv@211287 *}
	<input type="hidden" name="reply_mail_converter_record" value="{$smarty.request.reply_mail_converter_record|@vtlib_purify}">{* crmv@211287 *}
	<input type="hidden" name="reply_mail_user" value="{$smarty.request.reply_mail_user|@vtlib_purify}">{* crmv@211287 *}
{/if}
{* crmv@2043me *}
{* crmv@62394 - activity tracking inputs *}
<input type="hidden" name="tracking_compose_track" id="tracking_compose_track" value="0" >
<input type="hidden" name="tracking_compose_start_ts" id="tracking_compose_start_ts" value="0" >
<input type="hidden" name="tracking_compose_stop_ts" id="tracking_compose_stop_ts" value="0" >
{* crmv@62394e *}
{* crmv@80155 *}
<input type="hidden" name="signature_id" id="signature_id" value="{$SIGNATUREID}">
<input type="hidden" name="use_signature" id="use_signature" value="{$USE_SIGNATURE}">
{* crmv@80155e *}
{* crmv@121575*}
<input type="hidden" name="attachments_mode" id="attachments_mode" value="">
<input type="hidden" name="attach_contentids" id="attach_contentids" value="">
{* crmv@121575e*}
<input type="hidden" name="scheduled_date" id="scheduled_date" value=""> {* crmv@187622 *}

{* crmv@25356 *}

<table class="small mailClient" border="0" cellpadding="0" cellspacing="0" width="100%">
<tbody>
	<tr id="emailHeader" height="24px" style="margin-bottom:4px;">
		<td colspan="3">
			<table cellpadding="0" cellspacing="0" width="100%" class="mailClientWriteEmailHeader level2Bg menuSeparation">
				<tr>
					{* crmv@133415 *}
					<td width="100"><button type="button" class="crmbutton small cancel" onclick="window.close()">{$APP.LBL_CLOSE}</button></td>
					<td style="padding-left: 20px;">{'LBL_COMPOSE'|getTranslatedString:'Messages'}</td>
					<td align="right">
						<span id="composeEmailDraftUpdate" style="font-style:italic;font-weight:normal;font-size:12px;"></span>&nbsp;	{* crmv@31263 *}
						<input class="crmbutton small edit" value="{$APP.LBL_SELECTEMAILTEMPLATE_BUTTON_LABEL}" type="button" onclick="openPopup('index.php?module=Users&action=lookupemailtemplates','emailtemplate','top=100,left=200,height=400,width=500,resizable=yes,scrollbars=yes,menubar=no,addressbar=no,status=yes','auto');">
						<input class="crmbutton small save" value="{'Save Draft'|getTranslatedString:'Emails'}" type="button" onclick="email_validate(document.EditView,'save');">
						{* crmv@187622 *}
						<div class="vte-btn-group">
							<input class="crmbutton save success" value="{$APP.LBL_SEND}" type="submit">
							<a href="bootstrap-elements.html" data-target="#" class="crmbutton save success dropdown-toggle" data-toggle="dropdown">
								<span class="caret"></span>
							</a>
							<ul class="dropdown-menu" style="left:-50px">
								<li><a href="javascript:ScheduleSending.showOptions(true)">{$MOD.LBL_SCHEDULE_SENDING}</a></li>
							</ul>
						</div>
						{* crmv@187622e *}
					</td>
					{* crmv@133415e *}
				</tr>
			</table>
		</td>
	</tr>
	<tr height="4"><td colspan="3"></td></tr>
	<tr valign="top" id="pageContents">
		<td width="30%">
			<table cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td width="15%"></td>
					<td class="sendingMethod">
						<div>
							<span>{'Send Mode'|getTranslatedString:'Emails'}</span>
						</div>
						{* crmv@82419 *}
						<div class="sendingMethodOptions">
							<div class="radio radio-primary">
								<label for="send_mode_single" title="{'LBL_SINGLE_HELPINFO'|getTranslatedString:'Emails'}">
									<input type="radio" name="send_mode" id="send_mode_single" value="single" {if $SEND_MODE eq 'single'}checked="checked"{/if} title="{'LBL_SINGLE_HELPINFO'|getTranslatedString:'Emails'}"/>
									{'LBL_SINGLE_MODE'|getTranslatedString:'Emails'}
								</label>
							</div>
							<div class="radio radio-primary">
								<label for="send_mode_multiple" title="{'LBL_MULTIPLE_HELPINFO'|getTranslatedString:'Emails'}">
									<input type="radio" name="send_mode" id="send_mode_multiple" value="multiple" {if $SEND_MODE eq 'multiple'}checked="checked"{/if} title="{'LBL_MULTIPLE_HELPINFO'|getTranslatedString:'Emails'}"/>
									{'LBL_MULTIPLE_MODE'|getTranslatedString:'Emails'}
								</label>
							</div>
						</div>
						{* crmv@82419e *}
					</td>
					<td></td>
				</tr>
				<tr height="4"><td colspan="3"></td></tr>
				<tr>
					<td class="mailSubHeader edit" align="center">{$MOD.LBL_FROM}</td>
					<td>
						<div class="dvtCellInfo">
							<select id="from_email" name="from_email" class="detailedViewTextBox" onChange="changeSignature('{$SIGNATUREID}',this.value);">	{* crmv@44037 *}
								{foreach item="from_email_entity" from=$FROM_EMAIL_LIST}
									<option value="{$from_email_entity.email}" {if $from_email_entity.selected eq 'selected'}selected{/if} data-accountid="{$from_email_entity.account}">{if $from_email_entity.name neq ''}"{$from_email_entity.name}"{/if}&lt;{$from_email_entity.email}&gt;</option> {* crmv@114260 *}
								{/foreach}
							</select>
						</div>
					</td>
					<td></td>
				</tr>
				<tr height="10"><td colspan="3"></td></tr>
				<tr valign="top">
					<td align="center">
						<input class="crmbutton small edit" style="width:90%;height:32px;" type="button" value="{$MOD.LBL_TO}" onclick='openPopup("index.php?return_module={$MODULE}&module=Emails&action=EmailsAjax&file=PopupDest&fromEmail=1","","","auto",1050,505);'>
					</td>
					<td>
				 		<input name="{$element_parent_id.2.0}" id="{$element_parent_id.2.0}" type="hidden" value="{$IDLISTS}">
						<input type="hidden" name="saved_toid" id="saved_toid" value="{$TO_MAIL}">
						<input id="parent_name" name="parent_name" readonly class="txtBox1" type="hidden" value="{$TO_MAIL}">
						<div class="dvtCellInfo" id="autosuggest_to" onClick="jQuery('#to_mail').focus();">
							{$AUTOSUGGEST}
							{* <input id="to_mail" name="to_mail" class="detailedViewTextBox" value="{$OTHER_TO_MAIL}"> *}
							<textarea id="to_mail" name="to_mail" class="detailedViewTextBox" style="height:50px;">{$OTHER_TO_MAIL}</textarea>
						</div>
					</td>
					<td style="padding:5px;">
						<i class="vteicon md-link" title="{$APP.LBL_CLEAR}" onClick="jQuery('#parent_id').val(''); jQuery('#hidden_toid').val('');jQuery('#parent_name').val('');jQuery('#saved_toid').val('');jQuery('#to_mail').val('');jQuery('#autosuggest_to span').remove();return false;">highlight_remove</i>
					</td>
		   		</tr>
				<tr height="10"><td colspan="3"></td></tr>
				<tr valign="top">
					<td align="center">
						<input class="crmbutton small edit" style="width:90%;height:32px;" type="button" value="{$MOD.LBL_CC}" onclick='openPopup("index.php?return_module={$MODULE}&module=Emails&action=EmailsAjax&file=PopupDest&fromEmail=1","","","auto",1050,505);'>
					</td>
					<td>
						<div class="dvtCellInfo">
							<textarea name="ccmail" id="cc_name" class="detailedViewTextBox" style="height:50px;">{$CC_MAIL}</textarea>
						</div>
					</td>
					<td style="padding-left:5px;">
						<i class="vteicon md-link" title="{$APP.LBL_CLEAR}" onClick="jQuery('#cc_name').val('');return false;">highlight_remove</i>
					</td>
			   	</tr>
			   	<tr height="10"><td colspan="3"></td></tr>
			   	<tr valign="top" id="ccn_add">
			   		<td></td>
			   		<td colspan="2">
			   			<a href="javascript:;" onclick="jQuery('#ccn_row').show();jQuery('#ccn_add').hide();">{$MOD.LBL_ADD_BCC}</a>
			   		</td>
			   	</tr>
				<tr valign="top" id="ccn_row" style="display:none;">
					<td align="center">
						<input class="crmbutton small edit" style="width:90%;height:32px;" type="button" value="{$MOD.LBL_BCC}" onclick='openPopup("index.php?return_module={$MODULE}&module=Emails&action=EmailsAjax&file=PopupDest&fromEmail=1","","","auto",1050,505);'>
					</td>
					<td>
						<div class="dvtCellInfo">
							<textarea name="bccmail" id="bcc_name" class="detailedViewTextBox" style="height:50px;">{$BCC_MAIL}</textarea>
						</div>
					</td>
					<td style="padding-left:5px;">
						<i class="vteicon md-link" title="{$APP.LBL_CLEAR}" LANGUAGE=javascript onClick="jQuery('#bcc_name').val('');return false;">highlight_remove</i>
					</td>
				</tr>
			</table>
		</td>
		<td width="60%" rowspan="2">
			<table cellpadding="0" cellspacing="0" width="100%">
				<tr>
				    <td width="100%">
						<label class="dvtCellLabel">{$element_subject.1.0}</label>
						<div class="dvtCellInfo">
					        {if $RET_ERROR eq 1}
								<input type="text" class="detailedViewTextBox" name="{$element_subject.2.0}" value="{$SUBJECT}" id="{$element_subject.2.0}">
					        {else}
								<input type="text" class="detailedViewTextBox" name="{$element_subject.2.0}" value="{$element_subject.3.0}" id="{$element_subject.2.0}">
					        {/if}
						</div>
				    </td>
			   	</tr>
			   	<tr>
			   		<td colspan="2" class="mailSubHeader">
			   			<div class="message-compose-border">
							{* crmv@56409 - textareas require the text to be escaped, but due to several previous encoding/decoding, it's safer just to escape the < > *}
							<textarea class="detailedViewTextBox" id="description" name="description" cols="90" rows="16">{$element_description.3.0|replace:'&lt;':'&amp;lt;'|replace:'&gt;':'&amp;gt;'}</textarea>
						</div>
					</td>
			   	</tr>
			</table>
		</td>
		<td width="10%" rowspan="3" style="padding:0px 5px;" id="ComposeLinks">
			<input type="hidden" id="relation" name="relation" value="{$LINKS_STR}">
			{include file="TurboliftButtons.tpl"} {* crmv@42752 crmv@43864 *}
		</td>
	</tr>
	<tr valign="bottom">
		<td style="padding:4px;">
			{* crmv@22123 *}	{* crmv@30356 *}
			{if isMobile() neq true}
				<div class="mailSubHeader" style="border:none;height:35px;">
					<i class="vteicon" style="vertical-align:middle;padding-right:7px;">attachment</i>{$element_filename.1.0}
				</div>
				<div id="attach_cont" style="border:none;">
					<table cellspacing="0" cellpadding="0" width="100%" class="attachmentsEmail">
						<tr>
							<td valign="middle">
								{if ($element_filename.3|@count gt 0) OR ($smarty.request.attachment != '') OR ($COMMON_TEMPLATE_NAME neq '') OR ($webmail_attachments neq '')}{* crmv@22139 *} {* crmv@23060 *} {* crmv@25554 *}
									<div style="width:100%;height:60px;overflow:auto;margin:10px 0;"> {* crmv@121575 *}
										<table cellpaddin="0" cellspacing="0" class="small" width="100%">
										{if $smarty.request.attachment != ''}
											<tr><td width="100%" colspan="2">{$smarty.request.attachment|@vtlib_purify}<input type="hidden" value="{$smarty.request.attachment|@vtlib_purify}" name="pdf_attachment"></td></tr>
										{else} {* crmv@23060 *}
											{foreach item="attach_files" key="attach_id" from=$element_filename.3}
												<tr id="row_{$attach_id}"><td width="90%">{$attach_files}</td><td align="right"><i class="vteicon checkko md-link md-sm" onClick="delAttachments({$attach_id})" title="{$APP.LBL_DELETE_BUTTON}">clear</i></td></tr>
											{/foreach}
											<input type='hidden' name='att_id_list' value='{$ATT_ID_LIST}' />
										{/if}
										{foreach item="attach_files" from=$webmail_attachments}
											{* crmv@121575 *}
											<tr class="deletable_attach" id="{$attach_files.contentid}">{* crmv@204525 *}
												<td width="90%">{$attach_files.atag}</td> {* crmv@204525 *}
												<td align="right">
													<i class="vteicon checkko md-link md-sm" onclick="remove_attach(this);" title="{$APP.LBL_DELETE_BUTTON}">clear</i>
												</td>
											</tr>
											<script type="text/javascript">checkAttachment('{$attach_files.url}', '{$attach_files.name}', '{$attach_files.contentid}', 'compose')</script> {* crmv@204525 *}
											{* crmv@121575e *}
								        {/foreach}
										</table>
									</div>
								{/if}
								<div id="uploader" style="width:100%;height:90px;">You browser doesn't support upload.</div>
							</td>
						</tr>
					</table>
				</div>
			{/if}
			{* crmv@22123e *}	{* crmv@30356e *}
		</td>
	</tr>
	<tr id="DETAILVIEWWIDGETBLOCK">
   		<td colspan="2">
   			{* crmv@181170 *}
			{* vtlib Customization: Embed DetailViewWidget block:// type if any *}
			{if $CUSTOM_LINKS && !empty($CUSTOM_LINKS.DETAILVIEWWIDGET)}
				<table border=0 cellspacing=0 cellpadding=5 width=100% id="DetailViewWidgets">
					{foreach item=CUSTOM_LINK_DETAILVIEWWIDGET from=$CUSTOM_LINKS.DETAILVIEWWIDGET}
						{if !$CUSTOM_LINK_DETAILVIEWWIDGET->validateDisplayWidget($ID)}
							{continue}
						{/if}
						<tr>
							<td style="padding:5px;">
								{$CUSTOM_LINK_DETAILVIEWWIDGET->displayWidgetContent($ID)}
							</td>
						</tr>
					{/foreach}
				</table>
			{/if}
			{* END *}
			{* crmv@181170e *}
		</td>
	</tr>
</tbody>
</table>
</form>
<div id="hideBottom" style="display:none;"></div>

<div id="droparea" class="droparea" style="opacity:0;visibility:hidden;"><div class="droparea-text">{'LBL_DROP_FILES_HERE'|getTranslatedString}</div></div>

</body>

{if $FCKEDITOR_DISPLAY eq 'true'}
	<script type="text/javascript" src="include/ckeditor/ckeditor.js"></script>
{/if}
<script type="text/javascript">
jQuery(document).ready(function() {ldelim}
	// crmv@140887
	Blockage.addCheck(function() {ldelim}
		return "";
	{rdelim});
	// crmv@140887e
	
	{if $FCKEDITOR_DISPLAY eq 'true'}
		{literal}
		CKEDITOR.replace('description', {
			filebrowserBrowseUrl: 'include/ckeditor/filemanager/index.html',
			toolbar : 'Basic',	//crmv@31210
			{/literal}
			language : "{$SHORT_LANGUAGE}", // crmv@181170
			imageUploadUrl : 'index.php?module=Emails&action=EmailsAjax&file=plupload/upload&ckeditor=true&dir={$UPLOADIR}', //crmv@81704
			{literal}
			customConfig : 'message_config.js'
		});
		{/literal}
	{/if}
	
	if (jQuery('#use_signature').val() == 1) setSignature('{$SIGNATUREID}');	{* crmv@44037 crmv@48228 crmv@80155 *}

{literal}	

	// crmv@82419
	setInterval(function() {
		email_validate(document.EditView,'auto_save');
	}, 90000);
	// crmv@82419e


	jQuery("#uploader").pluploadQueue({
		// General settings
		runtimes: 'html5,flash,silverlight', //crmv@25883
		url: 'index.php?module=Emails&action=EmailsAjax&file=plupload/upload&dir={/literal}{$UPLOADIR}{literal}',
		// crmv@198545
		multipart_params: {
			"__csrf_token" :'{/literal}{$CSRF_TOKEN}{literal}'
		},
		// crmv@198545e
		max_file_size: '{/literal}{$FOCUS->max_attachment_size}{literal}mb', //crmv@58893
		chunk_size: '1mb',
		unique_names: true,
		prevent_duplicates: true,
		runtime_visible: false, // show current runtime in statusbar
		// Resize images on clientside if we can
		//resize: {width: 320, height: 240, quality: 90},
		// Specify what files to browse for
		// Flash/Silverlight paths
		flash_swf_url: 'modules/Emails/plupload/plupload.flash.swf',
		silverlight_xap_url: 'modules/Emails/plupload/plupload.silverlight.xap',
		// PreInit events, bound before any internal events
		preinit: {
			Init: function(up, info) {
				
				// crmv@140887
				if (up.features.dragdrop) {
					
					var lastTarget = null;
					var target = jQuery('#droparea').get(0);
					up.settings.drop_element = 'droparea';
		        	  
					window.addEventListener('dragenter', function(e) {
						lastTarget = e.target;
						jQuery(target).css('visibility', '');
						jQuery(target).css('opacity', 1);
						jQuery(target).addClass('dragover');
					});

					window.addEventListener('dragleave', function(e) {
						e.preventDefault();
						if (e.target === lastTarget) {
							jQuery(target).css('visibility', 'hidden');
							jQuery(target).css('opacity', 0);
							jQuery(target).removeClass('dragover');
						}
					});

					window.addEventListener('dragover', function(e) {
						e.preventDefault();
					});
		        	  
					window.addEventListener('drop', function(e) {
						jQuery(target).css('visibility', 'hidden');
						jQuery(target).css('opacity', 0);
					});
					
					jQuery('.plupload_filelist').css('height', '150px');
					jQuery('.plupload_filelist').css('min-height', '150px');
					
				}
				// crmv@140887e
				
			},
			UploadFile: function(up, file) {
				// You can override settings before the file is uploaded
				// up.settings.url = 'upload.php?id=' + file.id;
				// up.settings.multipart_params = {param1: 'value1', param2: 'value2'};
			}
		},
		// Post init events, bound after the internal events
		init: {
			Refresh: function(up) {
				// Called when upload shim is moved
			},
			StateChanged: function(up) {
				// Called when the state of the queue is changed
				//crmv@155537
				if (up['state'] == 2) { // start uploading
					jQuery('.crmbutton.small.save').attr("savebutton","1").prop('disabled',true).removeClass('save').addClass('edit');
					jQuery('.crmbutton.save.success').attr("savebutton","1").prop('disabled',true).removeClass('save').addClass('edit'); // crmv@201673
				}
				if (up['state'] == 1) { // upload completed
					jQuery('[savebutton="1"]').removeAttr("savebutton").prop('disabled',false).removeClass('edit').addClass('save');
				}
				//crmv@155537e
			},
			QueueChanged: function(up) {
				// Called when the files in queue are changed by adding/removing files
			},
			UploadProgress: function(up, file) {
				// Called while a file is being uploaded
			},
			FilesAdded: function(up, files) {
				// Called when files are added to queue
				//crmv@58893
				var total_size_before_upload = up.total.size;
				var queue_size = 0;
				plupload.each(files, function(file) {
					queue_size+=file.size;
				});
				if (total_size_before_upload+queue_size > up.settings.max_file_size){
					var filenames='';
					plupload.each(files, function(file) {
						filenames+=","+file.name;
						up.removeFile(file);
					});					
					//show error
					up.trigger("Error",{code:plupload.FILE_SIZE_ERROR,message:plupload.translate("File size error."),file:{'name':filenames.slice(1)}});
				}
				else{
					up.start();	//crmv@24568
				}
				//crmv@58893 e
			},
			FilesRemoved: function(up, files) {
				// Called when files where removed from queue
				plupload.each(files, function(file) {
				});
			},
			FileUploaded: function(up, file, info) {
				// crmv@228766
				var response = JSON.parse(info.response);
				if(response.hasOwnProperty('error')){
					vtealert(response.error.message);
					up.removeFile(file);
				}
				// crmv@228766e

				// Called when a file has finished uploading
				jQuery('.plupload_buttons').show();
				jQuery('.plupload_upload_status').hide();
			},
			ChunkUploaded: function(up, file, info) {
				// Called when a file chunk has finished uploading
			},
			Error: function(up, args) {
				// Called when a error has occured
				// Handle file specific error and general error
				if (args.file) {
				} else {
				}
			}
		}
	});
	//crmv@24568
	jQuery(".plupload_start").detach();
	jQuery(".plupload_header").detach();
	jQuery(".plupload_filelist_header").hide();
	//crmv@24568e

	var options = {
		beforeSerialize: beforeSendEmail,	//crmv@104438
	    success: successSendEmail,
	    error: errorSendEmail
	};
	jQuery('form[name="EditView"]').ajaxForm(options);
});

//crmv@22139	//crmv@31691
{/literal}
{if $smarty.request.attachment != '' && $smarty.request.rec != ''}
{literal}
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: "module=Documents&action=DocumentsAjax&file=EmailFile&record={/literal}{$smarty.request.rec|@vtlib_purify|escape:'quotes'}{literal}",//crmv@211287
		success: function(result) {
		}
	});
{/literal}{/if}{literal}
//crmv@22139e	//crmv@31691e

jQuery(function() {
	//crmv@32091
	function split( val ) {
		var arr = val.split( /,\s*/ );
		arr = cleanArray(arr);
		return arr;
	}
	//crmv@32091e
	function extractLast( term ) {
		return split( term ).pop();
	}
	jQuery("#to_mail")
		// don't navigate away from the field on tab when selecting an item
		.bind( "keydown", function( event ) {
			if ( event.keyCode === jQuery.ui.keyCode.TAB &&
					jQuery( this ).data( "autocomplete" ).menu.active ) {
				event.preventDefault();
			}
		})
		.autocomplete({
			source: function( request, response ) {
				jQuery.getJSON( "index.php?module=Emails&action=EmailsAjax&file=Autocomplete", {
					term: extractLast( request.term )
				}, response );
			},
			search: function() {
				// custom minLength
				var term = extractLast( this.value );
				if ( term.length < 3 ) {
					return false;
				}
			},
			focus: function() {
				// prevent value inserted on focus
				return false;
			},
			select: function( event, ui ) {
				var terms = split( this.value );
				// remove the current input
				terms.pop();
				// add placeholder to get the comma-and-space at the end
				terms.push('');
				this.value = terms.join(', ');

				// add the selected item
				var span = '<span id="to_'+ui.item.id+'" class="addrBubble">'+ui.item.value
						+'<div id="to_'+ui.item.id+'_parent_id" style="display:none;">'+ui.item.parent_id+'</div>'
						+'<div id="to_'+ui.item.id+'_parent_name" style="display:none;">'+ui.item.parent_name+'</div>'
						+'<div id="to_'+ui.item.id+'_hidden_toid" style="display:none;">'+ui.item.hidden_toid+'</div>'
						+' <div id="to_'+ui.item.id+'_remove" class="ImgBubbleDelete" onClick="removeAddress(\'to\',\''+ui.item.id+'\');"><i class="vteicon small">clear</i></div>'
						+'</span>';
				jQuery("#autosuggest_to").prepend(span);

				document.EditView.parent_id.value = document.EditView.parent_id.value+ui.item.parent_id+'|';
				document.EditView.parent_name.value = document.EditView.parent_name.value+ui.item.parent_name+' <'+ui.item.hidden_toid+'>,';
				document.EditView.hidden_toid.value = ui.item.hidden_toid+','+document.EditView.hidden_toid.value;

				return false;
			}
		}
	);
	jQuery("#cc_name")
		// don't navigate away from the field on tab when selecting an item
		.bind( "keydown", function( event ) {
			if ( event.keyCode === jQuery.ui.keyCode.TAB &&
					jQuery( this ).data( "autocomplete" ).menu.active ) {
				event.preventDefault();
			}
		})
		.autocomplete({
			source: function( request, response ) {
				jQuery.getJSON( "index.php?module=Emails&action=EmailsAjax&file=Autocomplete&field=cc_name", {
					term: extractLast( request.term )
				}, response );
			},
			search: function() {
				// custom minLength
				var term = extractLast( this.value );
				if ( term.length < 3 ) {
					return false;
				}
			},
			focus: function() {
				// prevent value inserted on focus
				return false;
			},
			select: function( event, ui ) {
				var terms = split( this.value );
				// remove the current input
				terms.pop();
				// add the selected item
				terms.push( ui.item.value );
				// add placeholder to get the comma-and-space at the end
				terms.push( "" );
				this.value = terms.join( ", " );
				return false;
			}
		}
	);
	jQuery("#bcc_name")
		// don't navigate away from the field on tab when selecting an item
		.bind( "keydown", function( event ) {
			if ( event.keyCode === jQuery.ui.keyCode.TAB &&
					jQuery( this ).data( "autocomplete" ).menu.active ) {
				event.preventDefault();
			}
		})
		.autocomplete({
			source: function( request, response ) {
				jQuery.getJSON( "index.php?module=Emails&action=EmailsAjax&file=Autocomplete&field=bcc_name", {
					term: extractLast( request.term )
				}, response );
			},
			search: function() {
				// custom minLength
				var term = extractLast( this.value );
				if ( term.length < 3 ) {
					return false;
				}
			},
			focus: function() {
				// prevent value inserted on focus
				return false;
			},
			select: function( event, ui ) {
				var terms = split( this.value );
				// remove the current input
				terms.pop();
				// add the selected item
				terms.push( ui.item.value );
				// add placeholder to get the comma-and-space at the end
				terms.push( "" );
				this.value = terms.join( ", " );
				return false;
			}
		}
	);
});

// crmv@121575
function remove_attach(self) {
	
	// remove the line
	jQuery(self).closest('tr').remove();
	
	// generate the list of ids
	var cids = [];
	var rows = jQuery('#attach_cont').find('tr.deletable_attach');
	rows.each(function(index, item) {
		var cid = jQuery(item).find('a').data('contentid');
		if (cid !== undefined && cid !== null) {
			cids.push(cid);
		}
	});
	
	// set the inputs
	jQuery('#attachments_mode').val('some');
	jQuery("#attach_contentids").val(cids.join(','));
}
// crmv@121575e

{/literal}
</script>
</html>
