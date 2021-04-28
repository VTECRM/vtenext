{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@80155 crmv@197575 *}
<script language="JAVASCRIPT" type="text/javascript" src="include/js/smoothscroll.js"></script>
<script language="JavaScript" type="text/javascript" src="{"include/js/menu.js"|resourcever}"></script>
<script language="JavaScript" type="text/javascript" src="modules/com_workflow/resources/webservices.js"></script>

<script language="JavaScript" type="text/javascript">
	
	function submitEmailTemplate(form){ldelim}

		//crmv@91082
		if(!SessionValidator.check()) {ldelim}
			SessionValidator.showLogin();
			return false;
		{rdelim}
		//crmv@91082e

		form.action.value='saveemailtemplate'; 
		form.parenttab.value='Settings'; 

		var template_editor = '{$TEMPLATE_EDITOR}';
		if(template_editor == 'grapesjs'){ldelim}
			jQuery('#body').val(window.frames[1].VTE.GrapesEditor.editor.runCommand('gjs-get-inlined-html'));
		{rdelim}

	{rdelim}

	{if $TEMPLATE_EDITOR neq 'grapesjs'}

    var allOptions = null;

    function setAllOptions(inputOptions) 
    {ldelim}
        allOptions = inputOptions;
    {rdelim}

    function modifyMergeFieldSelect(cause, effect) 
    {ldelim}
        var selected = cause.options[cause.selectedIndex].value;
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

		{assign var="all_variables_count" value=$ALL_VARIABLES|@count}
		{assign var="all_variables_count" value=$all_variables_count+1}
		var allOpts = new Object({$all_variables_count});

		{assign var="alloptioncount" value="0"}
		{foreach key=index item=module from=$ALL_VARIABLES}
	    	{assign var="module_count" value=$module|@count}
			{assign var="module_count" value=$module_count+1}
	    	options = new Object({$module_count});
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

	{/if}	

	function cancelForm(frm)
	{ldelim}
		frm.action.value='detailviewemailtemplate';
		frm.parenttab.value='Settings';
		frm.submit();
	{rdelim}

{* crmv@22700 *}
function InsertIntoTemplate(element)
{ldelim}
    selectField =  document.getElementById(element).value;
    var oEditor = CKEDITOR.instances.body;
	if (selectField != '')
	{ldelim}
        oEditor.insertHtml(selectField);
	{rdelim}
{rdelim}
{* crmv@22700e *}
</script>


<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
<tbody><tr>
        <td valign="top"></td>
        <td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->
	<div align=center>
	
			{include file='SetMenu.tpl'}
			{include file='Buttons_List.tpl'} {* crmv@30683 *} 
				<!-- DISPLAY -->
				<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
				{literal}
				<form action="index.php" method="post" name="templatecreate" onsubmit="if(check4null(templatecreate)) { VteJS_DialogBox.block(); } else { return false; }">
				{/literal}
				<input type="hidden" name="action">
				<input type="hidden" name="mode" value="{$EMODE}">
				<input type="hidden" name="module" value="Settings">
				<input type="hidden" name="templateid" value="{$TEMPLATEID}">
				<input type="hidden" name="parenttab" value="{$PARENTTAB}">
				<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}">
				<tr>
					<td width=50 rowspan=2 valign=top><img src="{'ViewTemplate.gif'|resourcever}" alt="{$MOD.LBL_MODULE_NAME}" width="45" height="60" border=0 title="{$MOD.LBL_MODULE_NAME}"></td>
				{if $EMODE eq 'edit'}
					<td class=heading2 valign=bottom><b>{$MOD.LBL_SETTINGS} > <a href="index.php?module=Settings&action=listemailtemplates&parenttab=Settings">{$UMOD.LBL_EMAIL_TEMPLATES}</a> &gt; {$MOD.LBL_EDIT} &quot;{$TEMPLATENAME}&quot; </b></td> <!-- crmv@30683 -->
				{*//crmv@36773*}	
				{elseif $EMODE eq 'duplicate'}
					<td class=heading2 valign=bottom><b>{$MOD.LBL_SETTINGS} > <a href="index.php?module=Settings&action=listemailtemplates&parenttab=Settings">{$UMOD.LBL_EMAIL_TEMPLATES}</a> &gt; {$APP.LBL_DUPLICATING} &quot;{$TEMPLATENAME}&quot; </b></td> <!-- crmv@30683 -->
				{*//crmv@36773 e*}						
				{else}
					<td class=heading2 valign=bottom><b>{$MOD.LBL_SETTINGS} > <a href="index.php?module=Settings&action=listemailtemplates&parenttab=Settings">{$UMOD.LBL_EMAIL_TEMPLATES}</a> &gt; {$MOD.LBL_CREATE_EMAIL_TEMPLATES} </b></td> <!-- crmv@30683 -->
				{/if}
					
				</tr>
				<tr>
					<td valign=top class="small">{$UMOD.LBL_EMAIL_TEMPLATE_DESC}</td>
				</tr>
				</table>
				
				<br>
				<table border=0 cellspacing=0 cellpadding=10 width=100% >
				<tr>
				<td>
				
					<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
					<tr>
						{if $EMODE eq 'edit'}
						<td class="big"><strong>{$UMOD.LBL_PROPERTIES} &quot;{$TEMPLATENAME}&quot; </strong></td>
						{else}
						<td class="big"><strong>{$MOD.LBL_CREATE_EMAIL_TEMPLATES}</strong></td>
						{/if}
						<td class="small" align=right>
							<input type="submit" value="{$APP.LBL_SAVE_BUTTON_LABEL}" class="crmButton small save" onclick="submitEmailTemplate(this.form);" >&nbsp;&nbsp;
			{if $EMODE eq 'edit'}
				<input type="submit" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" class="crmButton small cancel" onclick="cancelForm(this.form)" />
			{else}
				<input type="button" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" class="crmButton small cancel" onclick="window.history.back()" >
			{/if}
						</td>
					</tr>
					</table>
					
					<table border=0 cellspacing=0 cellpadding=5 width=100% >
					<tr>
						<td width=20% class="dvtCellLabel">{$UMOD.LBL_NAME}</td>
						<td width=80% class="small cellText">
							<div class="dvtCellInfoM">
								<input name="templatename" type="text" value="{$TEMPLATENAME}" class="detailedViewTextBox" tabindex="1">
							</div>
						</td>
					  </tr>
					<tr>
						<td class="dvtCellLabel">{$UMOD.LBL_DESCRIPTION}</td>
						<td class="cellText small" class="small cellText">
							<div class="dvtCellInfo">
								<input name="description" type="text" value="{$DESCRIPTION}" class="detailedViewTextBox" tabindex="2">
							</div>
						</td>
					</tr>
					<tr>
						<td class="dvtCellLabel">{$UMOD.LBL_FOLDER}</td>
						<td class="cellText small" valign=top>
						{if $EMODE eq 'edit'}
							<div class="dvtCellInfo">
								<select name="foldername" class="detailedViewTextBox" tabindex="3">
									{foreach item=arr from=$FOLDERNAME}
										<option value="{$FOLDERNAME}" {$arr}>{$FOLDERNAME}</option>
										{if $FOLDERNAME == 'Public'}
											<option value="Personal">{$UMOD.LBL_PERSONAL}</option>
										{else}
											<option value="Public">{$UMOD.LBL_PUBLIC}</option>
										{/if}
									{/foreach}
								</select>
							</div>
						{else}
							<div class="dvtCellInfo">
								<select name="foldername" class="detailedViewTextBox" value="{$FOLDERNAME}" tabindex="3">
									<option value="Personal">{$UMOD.LBL_PERSONAL}</option>
									<option value="Public" selected>{$UMOD.LBL_PUBLIC}</option>
								</select>
							</div>
						{/if}
						</td>
					  </tr>
					{* crmv@22700 *}
					<tr>
						<td width=20% class="dvtCellLabel">{'LBL_TYPE'|getTranslatedString}</td>
						<td width=80% class="small cellText">
							<div class="dvtCellInfo">
								<select name="templatetype" id="templatetype" class="detailedViewTextBox" tabindex="3" onChange="toggleEmailSettings(this.value);">
								{foreach item=arr from=$TEMPLATETYPE}
									<option value="{$arr.value}" {$arr.selected}>{$arr.label}</option>
								{/foreach}
								</select>
							</div>
						</td>
			  		</tr>
			  		{* crmv@22700e *}
					{if $TEMPLATE_EDITOR eq 'grapesjs'}
					<tr>
						<td class="dvtCellLabel">{$UMOD.LBL_SUBJECT}</td>
						<td class="cellText small" class="small cellText">
							<div class="dvtCellInfo">
								<input name="subject" type="text" value="{$SUBJECT}" class="detailedViewTextBox" tabindex="4">
							</div>
						</td>
					</tr>
					{/if}
					<tr id="row_use_signature">
						<td width=20% class="dvtCellLabel"><label for="use_signature">{'LBL_USE_SIGNATURE'|getTranslatedString:'Settings'}</label></td>
						<td width=80% class="small cellText">
							<div class="dvtCellInfo"><input id="use_signature" name="use_signature" type="checkbox" {if $USE_SIGNATURE eq 1}checked{/if}></div>
						</td>
			  		</tr>
					<tr id="row_overwrite_message">
						<td width=20% class="dvtCellLabel"><label for="overwrite_message">{'LBL_OVERWRITE_MESSAGE'|getTranslatedString:'Settings'}</label></td>
						<td width=80% class="small cellText">
							<div class="dvtCellInfo"><input id="overwrite_message" name="overwrite_message" type="checkbox" {if $OVERWRITE_MESSAGE eq 1}checked{/if}></div>
						</td>
			  		</tr>
			  		{if $BU_MC_ENABLED}
						<tr valign="top">
							<td width=20% class="dvtCellLabel">Business Unit</td>
							<td width=80% class="small cellText">
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

					{if $TEMPLATE_EDITOR eq 'grapesjs'}
						<tr>
					  	<td colspan="2" valign=top class="cellText small">
							<textarea style="display:none;" name="body" id="body"></textarea>
							<iframe allowfullscreen id="grapes_editor" id="grapes_editor" style="width: 100%; height: 950px; border:none;" src=""></iframe>
					  </td>
                    </tr>
					{else}
					<tr>
					  <td colspan="2" valign=top class="cellText small"><table width="100%"  border="0" cellspacing="0" cellpadding="0" class="thickBorder">
                        <tr>
                          <td valign=top><table width="100%"  border="0" cellspacing="0" cellpadding="5" >
                              <tr>
                                <td colspan="3" valign="top" class="small" style="background-color:#cccccc"><strong>{$UMOD.LBL_EMAIL_TEMPLATE}</strong></td>
                                </tr>
                              <tr>
                                <td width="15%" valign="top" class="cellLabel small"><font color='red'>*</font>{$UMOD.LBL_SUBJECT}</td>
                                <td width="85%" colspan="2" class="cellText small">
									<div class="dvtCellInfo">
                                  		<input name="subject" type="text" value="{$SUBJECT}" class="detailedViewTextBox" tabindex="4">
                                  	</div>
								</td>
                              </tr> 




                             <tr>
                              
                                <td width="15%"  class="cellLabel small" valign="center">{$UMOD.LBL_SELECT_FIELD_TYPE}</td>
                                <td width="85%" colspan="2" class="cellText small">

		<table>
			<tr>
				<td>{$UMOD.LBL_STEP}1
				<td>
			
				<td style="border-left:2px dotted #cccccc; padding-left:5px;">{$UMOD.LBL_STEP}2
				<td>

				<td style="border-left:2px dotted #cccccc; padding-left:5px;">{$UMOD.LBL_STEP}3
				<td>
			</tr>
			
			<tr>
				<td>
<!-- crmv@15309 -->
					<select class="dvtCellInfo" style="font-family: Arial, Helvetica, sans-serif;font-size: 11px;color: #000000;border:1px solid #bababa;padding-left:5px;background-color:#ffffff;" id="entityType" ONCHANGE="modifyMergeFieldSelect(this, document.getElementById('mergeFieldSelect'));" tabindex="6">
						<OPTION VALUE="0" selected>{$APP.LBL_NONE}
						{foreach key=module item=arr from=$ALL_VARIABLES name=modules}
							<OPTION VALUE="$smarty.foreach.modules.iteration">{$module|@getTranslatedString}
                    	{/foreach}                    
					</select>
				<td>
			
				<td style="border-left:2px dotted #cccccc; padding-left:5px;">
					<select class="dvtCellInfo" style="font-family: Arial, Helvetica, sans-serif;font-size: 11px;color: #000000;border:1px solid #bababa;padding-left:5px;background-color:#ffffff;" id="mergeFieldSelect" onchange="document.getElementById('mergeFieldValue').value=this.options[this.selectedIndex].value;" tabindex="7"><option value="0" selected>{$APP.LBL_NONE}</select>	
				<td>

				<td style="border-left:2px dotted #cccccc; padding-left:5px;">	
					<input class="dvtCellInfo" type="text" id="mergeFieldValue" name="variable" value="variable" style="width:200px;font-family: Arial, Helvetica, sans-serif;font-size: 11px;color: #000000;border:1px solid #bababa;padding-left:5px;background-color:#ffffdd;" tabindex="8"/>
				<td>
				{* crmv@22700 *}
				<td>
					<input class="crmButton small create" type="button" onclick="InsertIntoTemplate('mergeFieldValue');" value="{'LBL_INSERT_INTO_TEMPLATE'|getTranslatedString}">
				</td>
				{* crmv@22700e *}
			</tr>
<!-- crmv@15309 end-->
		</table>
			

				</td>
                              </tr>





					<tr>
					<td valign="top" width=10% class="cellLabel small">{$UMOD.LBL_MESSAGE}</td>
						<td valign="top" colspan="2" width=60% class="cellText small">
						<div class="cellInfo">
						<textarea name="body" style="width:90%;height:200px" class=small tabindex="5">{$BODY}</textarea>
					</div>
					</tr>
				{/if}


                      </table></td>
					  </tr>
					</table>
					<br>
					<br><br>
					{include file="Settings/ScrollTop.tpl"}
				</td>
				</tr>
				</table>
			
			
			
			</td>
			</tr>
			</table>
		</td>
	</tr>
	</form>
	</table>
		
	</div>

</td>
        <td valign="top"></td>
   </tr>
</tbody>
</table>

{if $TEMPLATE_EDITOR eq 'grapesjs'}
<script>
	var templateid = '{$TEMPLATEID}';
	var emode = '{$EMODE}';
	if(emode == 'duplicate'){ldelim} 
		templateid = '{$DUPLICATE_FROM}';
	{rdelim}
	jQuery('#grapes_editor').attr('src', 'index.php?module=SDK&action=SDKAjax&file=src/Grapes/Grapes&mode=load_body&is_wizard=1&templateid='+templateid);
</script>

{else}

<script type="text/javascript" src="include/ckeditor/ckeditor.js"></script>
<script type="text/javascript" defer="1">
var current_language_arr = "{$AUTHENTICATED_USER_LANGUAGE}".split("_"); // crmv@181170
var curr_lang = current_language_arr[0];
{literal}
CKEDITOR.replace('body', {
	filebrowserBrowseUrl: 'include/ckeditor/filemanager/index.html',
	language : curr_lang
});	

{/literal}
init();

</script>
{/if}

<script type="text/javascript" defer="1">
function toggleEmailSettings(templatetype) {ldelim}
	if (templatetype == 'Email') {ldelim}
		jQuery('#row_use_signature').show();
		jQuery('#row_overwrite_message').show();
	{rdelim} else {ldelim}
		jQuery('#row_use_signature').hide();
		jQuery('#row_overwrite_message').hide();
	{rdelim}
{rdelim}

toggleEmailSettings(jQuery('#templatetype').val());

function check4null(form)
{ldelim}
	var isError = false;
	var errorMessage = "";
	// Here we decide whether to submit the form.
	if (trim(form.templatename.value) =='') {ldelim}
		isError = true;
		errorMessage += "\n{$UMOD.LBL_NAME}";
		form.templatename.focus();
	{rdelim}
	if (trim(form.foldername.value) =='') {ldelim}
		isError = true;
		errorMessage += "\n{$UMOD.LBL_FOLDER}";
		form.foldername.focus();
	{rdelim}
	if (trim(form.subject.value) =='') {ldelim}
		isError = true;
		errorMessage += "\n{$UMOD.LBL_SUBJECT}";
		form.subject.focus();
	{rdelim}
	// Here we decide whether to submit the form.
	if (isError == true) {ldelim}
		alert("{$APP.MISSING_FIELDS}" + errorMessage);
		return false;
	{rdelim}
	//crmv@55961
	
	var template_editor = '{$TEMPLATE_EDITOR}';	
	var body;
	if(template_editor == 'grapesjs'){ldelim}
		jQuery('#body').html(window.frames[1].VTE.GrapesEditor.editor.runCommand('gjs-get-inlined-html') );
		body = jQuery('#body').html();
	{rdelim}
	else{ldelim}
		body = CKEDITOR.instances.body.getData();
	{rdelim}

	if (form.templatetype.value == 'Newsletter') {ldelim}
		if (body.indexOf('$Newsletter||tracklink#unsubscription$') == -1)
			if (confirm(alert_arr.LBL_TEMPLATE_MUST_HAVE_UNSUBSCRIPTION_LINK) == false)
				return false;
		if (body.indexOf('$Newsletter||tracklink#preview$') == -1)
			if (confirm(alert_arr.LBL_TEMPLATE_MUST_HAVE_PREVIEW_LINK) == false)
				return false;
	{rdelim}
	//crmv@55961e
	return true;
{rdelim}

</script>