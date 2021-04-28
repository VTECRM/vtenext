{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@186975 *}
{assign var="BROWSER_TITLE" value=$PAGE_TITLE}
{include file="HTMLHeader.tpl" head_include="icons,jquery,jquery_plugins"}
{* crmv@186975e *}

<body class="small">

	<table id="vte_menu" style="position:fixed;z-index:10;width:100%;">
		<tr>
			<td width="100%" class="mailClientWriteEmailHeader level2Bg menuSeparation header-breadcrumbs">
				<h4><a href="javascript:;">{$PAGE_TITLE}</a></h4>
			</td>
		</tr>
	</table>
	<div id="vte_menu_white"></div>

	<table class="level3Bg" id="Buttons_List_4" style="position:fixed;z-index:19;width:100%;">
		<tr>
			<td width="100%" style="padding:5px"></td>
			<td style="padding:5px" nowrap>
				<button type="button" class="crmbutton save" onclick="jQuery('#templatecreate').submit();">{$APP.LBL_SAVE_BUTTON_LABEL}</button>
				<button type="button" class="crmbutton cancel" onclick="closePopup()">{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
			</td>
		</tr>
	</table>
	<div id="vte_menu_white_1"></div>
	
	<div class="container-fluid" style="margin-top:15px;">
		<div class="row">
			<div class="col-sm-12">
				<form action="index.php" method="post" id="templatecreate" name="templatecreate">
					<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
					<input type="hidden" name="mode" value="{$EMODE}">
					<input type="hidden" name="file" value="widgets/TemplateEmailSSave">
					<input type="hidden" name="action" value="NewsletterAjax">
					<input type="hidden" name="module" value="Newsletter">
					<input type="hidden" name="templateid" value="{$TEMPLATEID}">
					<input type="hidden" name="parenttab" value="{$PARENTTAB}">
					<input type="hidden" name="quick_create" value="true">

					<table class="vtetable vtetable-props">
						<tbody>
							<tr>
								<td class="cellLabel" style="width: 10%;">{'LBL_NAME'|getTranslatedString:'Settings'}</td>
								<td class="cellText" style="width: 90%;">
									<div class="dvtCellInfoM">
										<input name="templatename" type="text" value="{$TEMPLATENAME}" class="detailedViewTextBox">
									</div>
								</td>
							</tr>
							<tr>
								<td class="cellLabel" style="width: 10%;">{'LBL_DESCRIPTION'|getTranslatedString:'Settings'}</td>
								<td class="cellText" style="width: 90%;">
									<div class="dvtCellInfo">
										<input name="description" type="text" value="{$DESCRIPTION}" class="detailedViewTextBox">
									</div>
								</td>
							</tr>
							<tr>
								<td class="cellLabel" style="width: 10%;">{'LBL_FOLDER'|getTranslatedString:'Settings'}</td>
								<td class="cellText" style="width: 90%;">
									<div class="dvtCellInfo">
										{if $EMODE eq 'edit'}
											<select	name="foldername" class="detailedViewTextBox">
												{foreach item=arr from=$FOLDERNAME}
													<option value="{$FOLDERNAME}"{$arr}>{$FOLDERNAME}</option>
													{if $FOLDERNAME == 'Public'}
														<option value="Personal">{'LBL_PERSONAL'|getTranslatedString:'Settings'}</option>
													{else}
														<option value="Public">{'LBL_PUBLIC'|getTranslatedString:'Settings'}</option>
													{/if}
												{/foreach}
											</select>
										{else}
											<select name="foldername" class="detailedViewTextBox" value="{$FOLDERNAME}">
												<option value="Personal">{'LBL_PERSONAL'|getTranslatedString:'Settings'}</option>
												<option value="Public" selected>{'LBL_PUBLIC'|getTranslatedString:'Settings'}</option>
											</select>
										{/if}
									</div>
								</td>
							</tr>
							<tr>
								<td class="cellLabel" style="width: 10%;">{'LBL_TYPE'|getTranslatedString}</td>
								<td class="cellText" style="width: 90%;">
									<div class="dvtCellInfo">
										<select name="templatetype" class="detailedViewTextBox">
											{foreach item=arr from=$TEMPLATETYPE}
												{if $arr.value eq 'Newsletter'}	{* solo Newsletter *}
													<option value="{$arr.value}" {$arr.selected}>{$arr.label}</option>
												{/if}
											{/foreach}
										</select>
									</div>
								</td>
							</tr>
							{* crmv@80155 *}
							{if $BU_MC_ENABLED}
								<tr>
									<td class="cellLabel" style="width: 10%;">Business Unit</td>
									<td class="cellText" style="width: 90%;">
										<div class="dvtCellInfo">
											<select name="bu_mc[]" class="detailedViewTextBox" multiple>
												{foreach item=arr from=$BU_MC}
													<option value="{$arr.value}" {$arr.selected}>{$arr.label}</option>
												{/foreach}
											</select>
										</div>
									</td>
								</tr>
							{/if}
							{* crmv@80155e *}
							<tr>
								<td class="cellLabel" style="width: 10%;">{'LBL_SUBJECT'|getTranslatedString:'Settings'}</td>
								<td class="cellText" style="width: 90%;">
									<div class="dvtCellInfoM">
										<input name="subject" type="text" value="{$SUBJECT}" class="detailedViewTextBox">
									</div>
								</td>
							</tr>
							{if $TEMPLATE_EDITOR eq 'ckeditor'}	{* crmv@197575 *}
							<tr>
								<td class="cellLabel">{'LBL_SELECT_FIELD_TYPE'|getTranslatedString:'Settings'}</td>
								<td class="cellText">
									<table>
										<tr>
											<td>
												<select class="detailedViewTextBox" id="entityType" onchange="modifyMergeFieldSelect(this, document.getElementById('mergeFieldSelect'));">
													<option value="0" selected>{$APP.LBL_NONE}</option>
													{foreach key=module item=arr from=$ALL_VARIABLES name=modules}
														<option value="$smarty.foreach.modules.iteration">{$module|@getTranslatedString}</option>
													{/foreach}
												</select>
											</td>
											<td>
												<select class="detailedViewTextBox" id="mergeFieldSelect" onchange="document.getElementById('mergeFieldValue').value=this.options[this.selectedIndex].value;">
													<option value="0" selected>{$APP.LBL_NONE}
												</select>
											</td>
											<td>
												<input type="text" class="detailedViewTextBox" id="mergeFieldValue" name="variable" value="" />
											</td>
											<td>
												<button class="crmbutton create" type="button" onclick="InsertIntoTemplate('mergeFieldValue');">{'LBL_INSERT_INTO_TEMPLATE'|getTranslatedString:'Newsletter'}</button>
											</td>
										</tr>
									</table>
								</td>
							</tr>
							{/if}
							<tr>
								<td class="cellLabel" style="width: 10%;">{'LBL_MESSAGE'|getTranslatedString:'Settings'}</td>
								<td class="cellText" style="width: 90%;">
									{* crmv@197575 *}
									{if $TEMPLATE_EDITOR eq 'grapesjs'}
										<textarea style="display:none;" name="body" id="body"></textarea>
										<iframe allowfullscreen id="grapes_editor" id="grapes_editor" style="width: 100%; height: 950px; border:none;" src=""></iframe>
									{else}
										<textarea name="body" style="height:600px;">{$BODY}</textarea>
									{/if}
									{* crmv@197575e *}
								</td>
							</tr>
							
						</tbody>
					</table>
				</form>
			</div>
		</div>
	</div>

	{if $TEMPLATE_EDITOR eq 'ckeditor'}	{* crmv@197575 *}
	<script type="text/javascript">
		var allOptions = null;
		
		function setAllOptions(inputOptions) 
		{ldelim}
			allOptions = inputOptions;
		{rdelim}

		function modifyMergeFieldSelect(cause, effect) 
		{ldelim}
			var selected = cause.options[cause.selectedIndex].value;  id="mergeFieldValue"
			var s = allOptions[cause.selectedIndex];
			effect.length = s;
			for (var i = 0; i < s; i++) 
			{ldelim}
	           effect.options[i] = s[i];
			{rdelim}
			document.getElementById('mergeFieldValue').value = '';
		{rdelim}

		function init() 
		{ldelim}
			var blankOption = new Option('--None--', '--None--');
			var options = null;
			var allOpts = new Object({$ALL_VARIABLES|@count}+1);
			{assign var="alloptioncount" value="0"}
			{foreach key=index item=module from=$ALL_VARIABLES}
				options = new Object({$module|@count}+1);
				{assign var="optioncount" value="0"}
				options[{$optioncount}] = blankOption;
				{foreach key=header item=detail from=$module}
					{assign var="optioncount" value=$optioncount+1}
					options[{$optioncount}] = new Option('{$detail.0|escape}', '{$detail.1|escape}');
				{/foreach}      
				{assign var="alloptioncount" value=$alloptioncount+1}     
				allOpts[{$alloptioncount}] = options;
			{/foreach}
			setAllOptions(allOpts);	    
		{rdelim}
    
		function InsertIntoTemplate(element)
		{ldelim}
			selectField =  document.getElementById(element).value;
			var oEditor = CKEDITOR.instances.body;
			if (selectField != '')
			{ldelim}
				oEditor.insertHtml(selectField);
			{rdelim}
		{rdelim}

		init();
	</script>

	<script type="text/javascript" src="include/ckeditor/ckeditor.js"></script>
	<script type="text/javascript">
		var current_language_arr = "{$AUTHENTICATED_USER_LANGUAGE}".split("_"); // crmv@181170
		var curr_lang = current_language_arr[0];
		{literal}
			CKEDITOR.replace('body', {
				filebrowserBrowseUrl: 'include/ckeditor/filemanager/index.html',
				language : curr_lang,
				height: '600px',
			});	
		{/literal}
	</script>
	{/if}

	<script type="text/javascript">
		function check4null()
		{ldelim}
			var form = document.templatecreate;
			var isError = false;
			var errorMessage = "";
			// Here we decide whether to submit the form.
			if (trim(form.templatename.value) =='') {ldelim}
				isError = true;
				errorMessage += "\n{'LBL_NAME'|getTranslatedString:'Settings'}";
				form.templatename.focus();
			{rdelim}
			if (trim(form.foldername.value) =='') {ldelim}
				isError = true;
				errorMessage += "\n{'LBL_FOLDER'|getTranslatedString:'Settings'}";
				form.foldername.focus();
			{rdelim}
			if (trim(form.subject.value) =='') {ldelim}
				isError = true;
				errorMessage += "\n{'LBL_SUBJECT'|getTranslatedString:'Settings'}";
				form.subject.focus();
			{rdelim}
			// Here we decide whether to submit the form.
			if (isError == true) {ldelim}
				alert("{$APP.MISSING_FIELDS}" + errorMessage);
				return false;
			{rdelim}
			//crmv@55961
			//crmv@197575
			var template_editor = '{$TEMPLATE_EDITOR}';	
			var body;
			if(template_editor == 'grapesjs'){ldelim}
				jQuery('#body').val(window.frames[0].VTE.GrapesEditor.editor.runCommand('gjs-get-inlined-html') );	//crmv@197575
				body = jQuery('#body').val();
			{rdelim}
			else{ldelim}
				body = CKEDITOR.instances.body.getData();
			{rdelim}
			//crmv@197575e
			if (form.templatetype.value == 'Newsletter') {ldelim}
				if (body.indexOf('$Newsletter||tracklink#unsubscription$') == -1)
					if (confirm(alert_arr.LBL_TEMPLATE_MUST_HAVE_UNSUBSCRIPTION_LINK) == false)
						return false;
				if (body.indexOf('$Newsletter||tracklink#preview$') == -1)
					if (confirm(alert_arr.LBL_TEMPLATE_MUST_HAVE_PREVIEW_LINK) == false)
						return false;
			{rdelim}
			//crmv@55961e
			
			//crmv@197575
			if(template_editor == 'ckeditor'){ldelim}
				for (instance in CKEDITOR.instances) {ldelim}
					CKEDITOR.instances[instance].updateElement();
				{rdelim}
			{rdelim}
			//crmv@197575e
			
			VteJS_DialogBox.block();
			return true;
		{rdelim}

		function returnValue(response) {ldelim}
			submittemplate({$RECORD},response['templateid'],response['templatename']);
		{rdelim}

		{literal}
		function submittemplate(record,templateid,templatename)
		{
			res = getFile("index.php?module=Newsletter&action=NewsletterAjax&file=widgets/TemplateEmailSave&record="+record+"&templateid="+templateid);
			//crmv@104558
			if (typeof parent.getObj('templateemail_name')  !== "undefined"){
				parent.getObj('templateemail_name').value = templatename;
			}
			//crmv@104558e
			closePopup();
			parent.location.reload();  //crmv@104558
		}
		jQuery(document).ready(function() {
			jQuery('#vte_menu_white').height(jQuery('#vte_menu').outerHeight());
			jQuery('#vte_menu_white_1').height(jQuery('#Buttons_List_4').height());
			loadedPopup();
			
			var options = {
				beforeSerialize: check4null,	// pre-submit callback 
			    success: returnValue,			// post-submit callback 
			    dataType: 'json'				// 'xml', 'script', or 'json' (expected server response type) 
			};
			jQuery('#templatecreate').ajaxForm(options);
		});
		{/literal}
	</script>

	{if $TEMPLATE_EDITOR eq 'grapesjs'}	{* crmv@197575 *}
	<script>
		var templateid = '{$TEMPLATEID}';
		jQuery('#grapes_editor').attr('src', 'index.php?module=SDK&action=SDKAjax&file=src/Grapes/Grapes&mode=load_body&is_wizard=1&templateid='+templateid);
	</script>
	{/if}
	{* crmv@197575e *}
	
</body>
</html>