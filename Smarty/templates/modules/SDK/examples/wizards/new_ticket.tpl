{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@OPER6317 crmv@96233 *}

{include file="WizardHeader.tpl"}

<style type="text/css">
{literal}
.click_target {
	cursor:pointer;
}
.border_change{
	border-width:2px;
	border-style:dashed;
	border-color:lightblue;		
}
.drag_zone{
	display: table;
	text-align:center;
}
.drag_zone span.message{
	display: table-cell;
	vertical-align: middle;
	text-align:middle;
	opacity:0.5;
}
{/literal}
</style>
<table id="nlWizMainTab" border="0" height="100%">
	<tr>
		<td id="nlWizLeftPane">
			<div>
				<table id="nlWizStepTable">
					<tr><td class="nlWizStepCell nlWizStepCellSelected">1. {$APP.WZ_ChooseAccount}</td></tr>
					<tr><td class="nlWizStepCell">2. {$APP.WZ_ChooseProduct}</td></tr>
					<tr><td class="nlWizStepCell">3. {$APP.WZ_UploadDocuments}</td></tr>
					<tr><td class="nlWizStepCell">4. {$APP.WZ_TicketData}</td></tr>
				</table>
			</div>
		</td>
		<td id="nlWizRightPane">
			
			<table id="nlwTopButtons" border="0" cellspacing="0" cellpadding="0" width="100%">
				<tr>
					<td align="left"><input type="button" class="crmbutton cancel" onclick="Wizard.gotoPrevStep()" id="nlw_backButton" style="display:none" value="&lt; {$APP.LBL_BACK}"></td>
					<td align="right">
						<input type="button" class="crmbutton save" onclick="Wizard.gotoNextStep()" id="nlw_nextButton" value="{$APP.LBL_FORWARD} &gt;">
						<input type="button" class="crmbutton save" onclick="Wizard.save()" id="nlw_endButton" style="display:none" value="{$APP.LNK_LIST_END}">
					</td>
				</tr>
			</table>
			
			<div id="nlWizStep1" style="">
				<div class="nlWizTargetList" id="nlw_targetList_Accounts">
					{$STEP1LIST.list}
				</div>
				<div id="nlw_targetsBoxCont">
					<p><b>{$APP.WZ_ChooseAccount}</b></p>
					<div id="selectList{$STEP1LIST.listid}"></div>
				</div>
			</div>
			
			<div id="nlWizStep2" style="display:none;">
				<div class="nlWizTargetList" id="nlw_targetList_Products">
					{$STEP2LIST.list}
				</div>
				<div id="nlw_targetsBoxCont">
					<p><b>{$APP.WZ_ChooseProduct}</b></p>
					<div id="selectList{$STEP2LIST.listid}"></div>
				</div>
			</div>
			
			<div id="nlWizStep3" style="display:none">
				<table width="100%" cellspacing="1" cellpadding="3" border="0">
					<tr height="150" valign="middle">
						<td class="dropzone_target click_target border_change" align="center">
							<div class="drag_zone">
								<span class="message"><b>{'LBL_CLICK_DRAG_UPLOAD'|@getTranslatedString:'Myfiles'}</b></span>
							</div>
						</td>
					<tr>
						<td width="80%" id="files_uploaded"></td>
					</tr>
				</table>
			</div>
			
			<div id="nlWizStep4" style="display:none">
				<p>{$APP.WZ_TicketData}:</p>
				<form name="nlw_RecordFields" id="nlw_RecordFields" onsubmit="return false;">
					{foreach item=FLD from=$STEP3FIELDS}
						{if $FLD.mandatory}
							{assign var="divclass" value="dvtCellInfoM"}
						{else}
							{assign var="divclass" value="dvtCellInfo"}
						{/if}
						{include file="EditViewUI.tpl" NOLABEL=false MODULE="Potentials" DIVCLASS=$divclass uitype=$FLD.uitype keymandatory=$FLD.mandatory fldlabel=$FLD.label fldname=$FLD.name fldvalue=$FLD.value secondvalue=$FLD.secondvalue}
						<br>
					{/foreach}
				</form>
			</div>
		</td>
	</tr>
</table>
<script type="text/javascript">
//initialize drag & drop area to upload files
var filename = '';
var image_data = '';
var upload_url = 'index.php?module={$MODULE}&action={$MODULE}Ajax&file=WizardAjax&ajaxaction=uploadfile';
jQuery.event.props.push('dataTransfer');
var dataArray = [];
{literal}
jQuery('.dropzone_target').on({
	dragover: function(){dragenter_css(this);return false;},
	dragleave: function(){dragleave_css(this)},
	drop:function(e){
		e.preventDefault();
		var files = e.dataTransfer.files;
		var data = new FormData();
		{/literal}
		var max_upload_size = '{$MAX_FILE_SIZE}';
		{literal}
		var total_size = 0;
		jQuery.each(files, function(index, file) {
			data.append('file_'+index, file);
			total_size+=file.size;
		});
		if (total_size > max_upload_size){
			{/literal}
			alert('{'LBL_EXCEED_MAX'|@getTranslatedString:'Emails'}');
			{literal}
			dragleave_css(this);
			return false;
		}
		block_wait(true);					
		jQuery.ajax({
		    url: upload_url+"&uniqueid="+getUniqueid(),
		    data: data,
		    cache: false,
		    contentType: false,
		    processData: false,
		    type: 'POST',
		    dataType:'json',
		    success: crmv_upload_ok,
		    error: crmv_upload_ko
		});
		dragleave_css(this);					
		return false;
	}
});
//initialize click area to upload files
jQuery('.click_target').on({
	click:function(e){
		e.preventDefault();
		jQuery('#fileupload').detach();
		jQuery('#fileupload_form').detach();
		jQuery('#fileupload_div').detach();
		if (jQuery('form#homeblockform input[name=view_mode]').val() == 'folder'){
			return;
		}
		jQuery('body').append(
			'<div id="fileupload_div" style="display:none;"><form id="fileupload_form" action="" method="post" enctype="multipart/form-data"><input folderid="'+jQuery(this).attr('folderid')+'" type="file" id="fileupload" name="upload[]" multiple /></form></div>'
		);
		jQuery('#fileupload').bind({
			change:function(e){
				e.preventDefault();
				{/literal}
				var max_upload_size = '{$MAX_FILE_SIZE}';
				{literal}					
				block_wait(true);
				var data = new FormData();
				var total_size = 0;
				jQuery.each(jQuery("#fileupload_form input[name^='upload']")[0].files, function(i, file) {
			        data.append('file_'+i, file);
			        total_size+=file.size;
		      	});
				if (total_size > max_upload_size){
					{/literal}
					alert('{'LBL_EXCEED_MAX'|@getTranslatedString:'Emails'}');
					{literal}
					return false;
				}				      						
				jQuery.ajax({
					type: 'POST',
					url: upload_url+"&uniqueid="+getUniqueid(),
				    data: data,
				    cache: false,
				    contentType: false,
				    processData: false,
				    dataType:'json',
					success: crmv_upload_ok,
		    		error: crmv_upload_ko					
				});					
			}
		});
		jQuery('#fileupload').click();
	},
	mouseenter:function(){
		dragenter_css(jQuery(this));
	},
	mouseleave:function(){
		dragleave_css(jQuery(this));
	}
});
function dragenter_css(obj){
	jQuery(obj).find('span.message').css('opacity','1');
	jQuery(obj).css('border-width', '2px');
	jQuery(obj).css('border-style', 'dashed');
	jQuery(obj).css('border-color', 'blue');
}
function dragleave_css(obj){
	jQuery(obj).find('span.message').css('opacity','0.5');
	jQuery(obj).css('border-width', '');
	jQuery(obj).css('border-style', '');
	jQuery(obj).css('border-color', '');
}
function block_wait(flag){
	if (flag === true){
		jQuery('#status').show();
	} else {
		jQuery('#status').hide();
	}		
}
function crmv_upload_ok(data){
	block_wait(false);
	if (data['success'] == true){
		{/literal}
		var url = 'index.php?module={$MODULE}&action={$MODULE}Ajax&file=WizardAjax&ajaxaction=documentlist&uniqueid='+data['uniqueid'];
		{literal}
		jQuery.ajax({
			type: 'POST',
			url: url,
		    success: function(data) {
		    	jQuery('#files_uploaded').html(data);
		    }				
		});
	}
	else{
		alert(data['message']);
	}
}
function crmv_upload_ko(data){
	{/literal}
	alert('{'LBL_UPLOAD_ERROR'|@getTranslatedString:'Contacts'}');
	{literal}
	block_wait(false);			
}
function getUniqueid(){
	if (jQuery('#documentlist_uniqueid').length > 0)
		return jQuery('#documentlist_uniqueid').val();
	else
		return Math.random().toString(36).slice(2);
}
function removeTmpFile(uniqueid,filename) {
	{/literal}
	var url = 'index.php?module={$MODULE}&action={$MODULE}Ajax&file=WizardAjax&ajaxaction=removetmpfile&uniqueid='+uniqueid+'&filename='+filename;
	{literal}
	jQuery.ajax({
		type: 'POST',
		url: url,
	    success: function(data) {
	    	jQuery('#files_uploaded').html(data);
	    }				
	});
}
{/literal}
</script>