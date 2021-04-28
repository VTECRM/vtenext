{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}

{include file="HTMLHeader.tpl" head_include="jquery,jquery_ui"} {* crmv@192014 *}

{include file="Theme.tpl" THEME_MODE="body"} {* crmv@153770 *}

<style type="text/css">
	{literal}
	.lview_folder_td {
		width: 30%;
		height: 30%;
		margin: 2px;
		float: left;
		clear: none;
		text-align: center;
	}
	.show_link {
		cursor:pointer;
	}
	.delete_link {
		cursor:pointer;
	}
	.convert_link {
		cursor:pointer;
	}
	.click_target {
		cursor:pointer;
	}
	div.action-menu {
	    position: absolute;
	    margin: 0px;
	    padding: 2px;
	    text-align:center;
	    border:1px solid #D0D0D0;
	    border-radius:2px;
	    display:none;
	    background-color:#E0E0E0;
	}
	.add_folder {
		position:absolute;
		width:99%;
		height:82%;
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
	#layerbg{
	position: absolute;
        position: absolute;
        left: 0;
        top: 0;
        background: #000;
	}
	{/literal}
</style>

<script language="JavaScript" type="text/javascript" src="modules/{$MODULE}/{$MODULE}.js"></script>
<script language="JavaScript" type="text/javascript" src="{"include/js/dtlviewajax.js"|resourcever}"></script>
<script language="JavaScript" type="text/javascript" src="modules/SDK/SDK.js"></script>

{SDK::checkJsLanguage()}	{* crmv@sdk-18430 *} {* crmv@181170 *}
{include file='CachedValues.tpl'}	{* crmv@26316 *}
{include file='modules/SDK/src/Reference/Autocomplete.tpl'}	{* crmv@29190 *}

<script type="text/javascript">

{literal}
	function block_wait(flag){
		if (flag === true){
			{/literal}
			parent.jQuery('#refresh_{$STUFFID}').html(parent.jQuery('#vtbusy_homeinfo').html());
			{literal}	
			return;		
		}
		{/literal}
		parent.jQuery('#refresh_{$STUFFID}').html('');
		{literal}		
	}
	function lviewfold_add() {
		var baseurl = 'index.php?module=Utilities&action=UtilitiesAjax&file=FolderHandler';
		var formdata = jQuery('#lview_folder_addform').serialize();
		block_wait(true);		
		jQuery.ajax({
			type: 'POST',
			url: baseurl,
			data: formdata,
			success: function(data, tstatus) {
				if (data.substr(0, 7) == 'ERROR::') {
					window.alert(data.substr(7));
					block_wait(false);
				} else {
					jQuery('form#homeblockform').submit();
					block_wait(false);				
				}
			}
		});
	}
	function lviewfold_del() {
		var checklist = jQuery('#lview_table_cont span[id^=lview_folder_checkspan]');
		if (checklist.length == 0) return window.alert(alert_arr.LBL_NO_EMPTY_FOLDERS);
		jQuery('#lviewfolder_button_del').hide();
		jQuery('#lviewfolder_button_add').hide();
		jQuery('#lviewfolder_button_list').hide();
		jQuery('#lviewfolder_button_del_cancel').show();
		jQuery('#lviewfolder_button_del_save').show();
		checklist.show();
		// crmv@30976 - ingrigisce le altre cartelle
		lviewFolder.disabled = true;
		jQuery('#lview_table_cont div[class=lview_folder_td]:not(:has(span[id^=lview_folder_checkspan]))').css({opacity: 0.5});
		// crmv@30976e
	}
	function lviewfold_del_save(module) {
		var delids = [];
		jQuery('#lview_table_cont input[type=checkbox]:checked').each(function (idx, el) {
			delids.push(parseInt(el.id.replace('lvidefold_check_', '')));
		});
	
		if (delids.length == 0) return window.alert(alert_arr.LBL_SELECT_DEL_FOLDER);
	
		var baseurl = 'index.php?module=Utilities&action=UtilitiesAjax&file=FolderHandler&subaction=del';
		var formdata = 'folderids='+delids.join(',')+'&formodule='+module;
		block_wait(true);	
		jQuery.ajax({
			type: 'POST',
			url: baseurl,
			data: formdata,
			success: function(data, tstatus) {
				if (data.substr(0, 7) == 'ERROR::') {
					window.alert(data.substr(7));
					block_wait(false);				
				} else {
					jQuery('form#homeblockform').submit();
					block_wait(false);					
				}
			}
		});
	}
	function lviewfold_del_cancel() {
		jQuery('#lviewfolder_button_del').show();
		jQuery('#lviewfolder_button_add').show();
		jQuery('#lviewfolder_button_list').show();
		jQuery('#lviewfolder_button_del_cancel').hide();
		jQuery('#lviewfolder_button_del_save').hide();
		jQuery('#lview_table_cont span[id^=lview_folder_checkspan]').hide();
		// crmv@30976
		lviewFolder.disabled = false;
		jQuery('#lview_table_cont div[class=lview_folder_td]').css({opacity: 1});
		// crmv@30976e
	}	
	function lviewfold_showTooltip(folderid) {
		if (lviewFolder.disabled == true) return; // crmv@30976
		jQuery('#lviewfold_tooltip_'+folderid).show();
		lviewFolder.hidden = false;
	}
	
	function lviewfold_hideTooltip(folderid) {
		if (lviewFolder.disabled == true) return; // crmv@30976
		jQuery('#lviewfold_tooltip_'+folderid).hide();
		lviewFolder.hidden = true;
	}
	
	function lviewfold_moveTooltip(folderid) {
		if (!lviewFolder.hidden) {
			var newx, newy;
			var ttip = jQuery('#lviewfold_tooltip_'+folderid);
			tw = ttip.width();
			th = ttip.height();
			dw = jQuery(document).width();
			dh = jQuery(document).height();
			dx = dy = 10;
			if (lviewFolder.x + dx + tw > dw) {
				newx = dw - tw;
			} else {
				newx = lviewFolder.x+dx;
			}
			if (lviewFolder.y + dy + th > dh) {
				newy = dh - th;
			} else {
				newy = lviewFolder.y+dy;
			}
			ttip.css({'left':newx, 'top':newy});
		}
	}
	
	function lviewfold_add() {
		var baseurl = 'index.php?module=Utilities&action=UtilitiesAjax&file=FolderHandler';
		var formdata = jQuery('#lview_folder_addform').serialize();
		block_wait(true);	
		jQuery.ajax({
			type: 'POST',
			url: baseurl,
			data: formdata,
			success: function(data, tstatus) {
				if (data.substr(0, 7) == 'ERROR::') {
					window.alert(data.substr(7));
					block_wait(false);				
				} else {
					jQuery('form#homeblockform').submit();
					block_wait(false);					
				}
			}
		});
	}
	function lview_convert_file(){
		if (jQuery('#lview_file_convertform input[name=title]').val() == ''){
			{/literal}
				alert('{'LBL_TITLE_NOT_EMPTY'|@getTranslatedString:$MODULE}');
			{literal}
			jQuery('#lview_file_convertform input[name=title]').focus();
			return false;
		}
		{/literal}
		var baseurl = 'index.php?module={$MODULE}&action={$MODULE}Ajax&file=ConvertDocument';
		{literal}
		var formdata = jQuery('#lview_file_convertform').serialize();
		block_wait(true);
		jQuery.ajax({
			type: 'POST',
			url: baseurl,
			data: formdata,
			dataType:'json',
			success: function(data) {
				if (data['success'] == true) {
					jQuery('#Convertfile_Handle #lview_folder_save').hide();
					jQuery('#lview_file_details_content').html(data['content']);
					jQuery('#lview_file_convert').show('fast',function e(){
						jQuery(this).css({top:'50%',left:'50%',margin:'-'+(jQuery(this).height() / 2)+'px 0 0 -'+(jQuery(this).width() / 2)+'px'});
					});
					jQuery('#lview_file_convert .closebutton').click(function(e){
						jQuery('form#homeblockform').submit();
						block_wait(false);
					});
				} else {
					window.alert(data['message']);
					block_wait(false);					
				}
			}
		});		
	}
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
	jQuery(document).ready(function(){
		lviewFolder = {x:0, y:0, hidden: true};
		// crmv@192014
		jQuery('#lview_folder_add').draggable({
			handle: '#AddFolder_Handle',
		});
		// crmv@192014e
		//change title of widget
		if (jQuery('form#homeblockform input[name=title]').val() == ''){
			var title = '';
			{/literal}
			if (parent.jQuery('#stuff_{$STUFFID} table tr:first td.homePageMatrixHdr:first').length > 0){ldelim}
				title = parent.jQuery('#stuff_{$STUFFID} table tr:first td.homePageMatrixHdr:first').html();
			{rdelim}
			{literal}
			//correct title in case of reload
			if (title.indexOf('&gt;') > 0){
				title = title.slice(0,title.indexOf('&gt;'));
				{/literal}
				parent.jQuery('#stuff_{$STUFFID} table tr:first td.homePageMatrixHdr:first').html(title);
				{literal}
			}
			jQuery('form#homeblockform input[name=title]').val(title);
		}
		{/literal}
		//initialize block
		var mode=jQuery('form#homeblockform input[name=view_mode]').val();
		var trans_list = "{'LBL_VIEW_LIST'|@getTranslatedString:$MODULE}";
		var trans_icons = "{'LBL_VIEW_ICON'|@getTranslatedString:$MODULE}";
		{literal}
		if (mode == 'folder' || mode == 'icon'){
			jQuery('#lviewfolder_button_list').val(trans_list);
			jQuery('#lviewfolder_button_list').text(trans_list);
		}
		else if (mode != 'detailview'){
			jQuery('#lviewfolder_button_list').val(trans_icons);
			jQuery('#lviewfolder_button_list').text(trans_icons);			
		}		
		var folder = jQuery('form#homeblockform input[name=folder_selected]').val();
		var file = jQuery('form#homeblockform input[name=myfilesid_selected]').val();
		if (mode == 'icon' || mode == 'list' || mode == 'detailview'){
			jQuery('#lviewfolder_button_add').hide();
			jQuery('#lviewfolder_button_del').hide();
		}
		//case folder
		if (folder != '' && file == ''){
			jQuery('#button_back').show();
			//build links to turn back
			var menu_html = '';
			menu_html+=jQuery('form#homeblockform input[name=title]').val()+' > '; 
			menu_html+=jQuery('form#homeblockform input[name=folder_name]').val();
			{/literal}
			parent.jQuery('#stuff_{$STUFFID} table tr:first td.homePageMatrixHdr:first').html(menu_html);
			{literal}			
			jQuery('#button_back').click(function(e){
				e.preventDefault();
				jQuery('form#homeblockform input[name=view_mode]').val('folder');
				jQuery('form#homeblockform input[name=folder_selected]').val('');
				jQuery('form#homeblockform input[name=folder_name]').val('');
				jQuery('form#homeblockform').submit();
			});			
		}
		//case file
		if (file != ''){
			jQuery('#button_back').show();
			jQuery('#lviewfolder_button_list').hide();
			//build links to turn back
			var menu_html = '';
			menu_html+=jQuery('form#homeblockform input[name=title]').val()+' > '; 
			menu_html+=jQuery('form#homeblockform input[name=folder_name]').val();
			menu_html+=" > ";
			menu_html+=jQuery('form#homeblockform input[name=file_name]').val();
			{/literal}
			parent.jQuery('#stuff_{$STUFFID} table tr:first td.homePageMatrixHdr:first').html(menu_html);
			{literal}			
			jQuery('#button_back').click(function(e){
				e.preventDefault();
				jQuery('form#homeblockform input[name=view_mode]').val(jQuery('form#homeblockform input[name=last_view_mode]').val());
				jQuery('form#homeblockform input[name=folder_selected]').val(jQuery('form#homeblockform input[name=last_folder_selected]').val());
				jQuery('form#homeblockform input[name=folder_name]').val(jQuery('form#homeblockform input[name=last_folder_name]').val());
				jQuery('form#homeblockform input[name=myfilesid_selected]').val('');
				jQuery('form#homeblockform').submit();
			});
		}		
		//click folder view
		jQuery('div#lview_table_cont div#content_folder a.folder').click(function(e){
			{/literal} 
			parent.jQuery('#stuff_{$STUFFID} table tr:first td.homePageMatrixHdr:first').html(jQuery('form#homeblockform input[name=title]').val());
			{literal}
			e.preventDefault();
			jQuery('form#homeblockform input[name=folder_selected]').val(jQuery(this).attr('folderid'));
			jQuery('form#homeblockform input[name=folder_name]').val(jQuery(this).attr('foldername'));
			jQuery('form#homeblockform input[name=view_mode]').val('icon');
			jQuery('form#homeblockform').submit();
			return;

			//build links to turn back
			var menu_html = '';
			menu_html+=jQuery('form#homeblockform input[name=title]').val()+' > '; 
			menu_html+=jQuery('form#homeblockform input[name=folder_name]').val();
			{/literal} 
			parent.jQuery('#stuff_{$STUFFID} table tr:first td.homePageMatrixHdr:first').html(menu_html);
			{literal}
		});
		//click icon view
		jQuery('.icon').on({
			mouseenter:function(e){
				jQuery(this).children('.action-menu').addClass('hover');
				var pos = jQuery(this).position();
				jQuery(this).children('.action-menu').css('top',pos.top);
				jQuery(this).children('.action-menu').css('left',pos.left+jQuery(this).width()-20);
            	jQuery(this).children('.action-menu').show();
			},
			mouseleave:function(e){
				jQuery(this).children('.action-menu').removeClass('hover');
            	jQuery(this).children('.action-menu').hide();
			}
		});
		//click delete file
		jQuery('.delete_link').on({
			click:function(e){
				block_wait(true);
				jQuery.ajax({
					type: 'GET',
					url: 'index.php?module=Myfiles&action=MyfilesAjax&file=DeleteRecord&myfilesid='+jQuery(this).attr('fileid'),
					success: function(data, tstatus) {
						jQuery('form#homeblockform').submit();
						block_wait(false);								
					},
				    error: function(){
						{/literal}
						alert('{'LBL_UPLOAD_ERROR'|@getTranslatedString:'Contacts'}');
						block_wait(false);
						{literal}	
				    }						
				});	
			}
		});
		//click detailview
		jQuery('.show_link').on({
			click:function(e){
				e.preventDefault();
				if (jQuery('form#homeblockform input[name=folder_selected]').val() == ''){
					jQuery('form#homeblockform input[name=folder_selected]').val(jQuery(this).attr('folderid'));
				}
				jQuery('form#homeblockform input[name=myfilesid_selected]').val(jQuery(this).attr('fileid'));
				jQuery('form#homeblockform input[name=last_folder_selected]').val(jQuery('form#homeblockform input[name=folder_selected]').val());
				jQuery('form#homeblockform input[name=last_folder_name]').val(jQuery('form#homeblockform input[name=folder_name]').val());
				jQuery('form#homeblockform input[name=last_view_mode]').val(jQuery('form#homeblockform input[name=view_mode]').val());
				jQuery('form#homeblockform input[name=view_mode]').val('detailview');
				jQuery('form#homeblockform input[name=file_name]').val(jQuery(this).attr('file_name'));
				jQuery('form#homeblockform').submit();
			}
		});
		//click convert
		jQuery('.convert_link').on({
			click:function(e){
				e.preventDefault();
				jQuery('#layerbg').css({"zIndex":findZMax()+1, "display": "block", "opacity": 0.4, "width":jQuery(document).width(),"height":jQuery(document).height()});
				jQuery('#lview_file_convert').show('fast',function e(){
					jQuery(this).css({"zIndex":findZMax()+1,top:'50%',left:'50%',margin:'-'+(jQuery(this).height() / 2)+'px 0 0 -'+(jQuery(this).width() / 2)+'px'});
				});	
				jQuery('form#lview_file_convertform input[name=title]').val(jQuery(this).attr('file_title'));
				jQuery('form#lview_file_convertform input[name=fileid]').val(jQuery(this).attr('fileid'));
			}
		});
		//switch list/icon view
		jQuery('#lviewfolder_button_list').click(function(e){
			e.preventDefault();
			var oldmode=jQuery('form#homeblockform input[name=view_mode]').val();
			var newmode = '';
			{/literal}
			parent.jQuery('#stuff_{$STUFFID} table tr:first td.homePageMatrixHdr:first').html(jQuery('form#homeblockform input[name=title]').val());
			{literal}			
			if (oldmode == 'folder'){
				newmode = 'global';
			}
			else if (oldmode == 'global'){
				newmode = 'folder';
			}
			if (oldmode == 'icon'){
				newmode = 'list';
			}
			else if (oldmode == 'list'){
				newmode = 'icon';
			}
			jQuery('form#homeblockform input[name=view_mode]').val(newmode);
			jQuery('form#homeblockform').submit();
		});
		//initialize drag & drop area to upload files
		var filename = '';
		var image_data = '';
		var upload_url = 'index.php?module=Myfiles&action=MyfilesAjax&file=UploadFile'
		jQuery.event.props.push('dataTransfer');
		var dataArray = [];
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
				    url: upload_url+'&folderid='+jQuery(this).attr('folderid')+"&uniqueid="+Math.random().toString(36).slice(2),
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
		function crmv_upload_ok(data){
			block_wait(false);
			if (data['rename'] == true){
				jQuery('body').append(data['rename_panel']);
				jQuery('#layerbg').css({"zIndex":findZMax()+1, "display": "block", opacity: 0.4, "width":jQuery(document).width(),"height":jQuery(document).height()});
				jQuery('#lview_file_rename').show('fast',function e(){
					if (jQuery('#lview_file_rename_content').height() > (jQuery(document).height() -120)){
						jQuery('#lview_file_rename_content').height(jQuery(document).height()-120);
					}
					jQuery('#lview_file_rename_content').css('overflow-y','auto');		
					jQuery(this).css({"zIndex":findZMax()+1,top:'50%',left:'50%',margin:'-'+(jQuery(this).height() / 2)+'px 0 0 -'+(jQuery(this).width() / 2)+'px'});
				});
				jQuery('#lview_file_rename form[name=lview_file_renameform] select[id^=action_]').change(function(e){
					if (jQuery(this).val() == 'replace' || jQuery(this).val() == 'jump'){
						jQuery('#lview_file_rename form[name=lview_file_renameform] input[name=file_'+jQuery(this).attr('fileid')+']').val(jQuery('#lview_file_rename form[name=lview_file_renameform] input[name=filebackup_'+jQuery(this).attr('fileid')+']').val())
						jQuery('#lview_file_rename form[name=lview_file_renameform] input[name=file_'+jQuery(this).attr('fileid')+']').parent('div').toggleClass('dvtCellInfoOff',true).toggleClass('dvtCellInfo',false);
						jQuery('#lview_file_rename form[name=lview_file_renameform] input[name=desc_'+jQuery(this).attr('fileid')+']').val(jQuery('#lview_file_rename form[name=lview_file_renameform] input[name=descbackup_'+jQuery(this).attr('fileid')+']').val())
						jQuery('#lview_file_rename form[name=lview_file_renameform] input[name=desc_'+jQuery(this).attr('fileid')+']').parent('div').toggleClass('dvtCellInfoOff',true).toggleClass('dvtCellInfo',false);
					}
					else{
						jQuery('#lview_file_rename form[name=lview_file_renameform] input[name=file_'+jQuery(this).attr('fileid')+']')
						jQuery('#lview_file_rename form[name=lview_file_renameform] input[name=file_'+jQuery(this).attr('fileid')+']').parent('div').toggleClass('dvtCellInfo',true).toggleClass('dvtCellInfoOff',false);
						jQuery('#lview_file_rename form[name=lview_file_renameform] input[name=desc_'+jQuery(this).attr('fileid')+']')
						jQuery('#lview_file_rename form[name=lview_file_renameform] input[name=desc_'+jQuery(this).attr('fileid')+']').parent('div').toggleClass('dvtCellInfo',true).toggleClass('dvtCellInfoOff',false);
					}
				});
				jQuery('div#lview_file_rename input#lview_rename_file').on({
					click:function(e){
						e.preventDefault();
						{/literal}
						var baseurl = 'index.php?module={$MODULE}&action={$MODULE}Ajax&file=UploadFile';
						{literal}
						var disabled = jQuery('div#lview_file_rename form[name=lview_file_renameform]').find(':input:disabled').removeAttr('disabled');						
						var formdata = jQuery('div#lview_file_rename form[name=lview_file_renameform]').serialize();
						disabled.attr('disabled','disabled');
						block_wait(true);	
						jQuery.ajax({
							type: 'POST',
							url: baseurl,
							dataType:'json',
							data: formdata,
							success: function(data, tstatus) {
								if (!data['success']){
									alert(data['message']);
									block_wait(false);
								}
								else{
									jQuery('form#homeblockform').submit();
									block_wait(false);								
								}
							}
						});
					}
				});
			}
			else if (data['success'] == true){
				jQuery('form#homeblockform').submit();
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
							url: upload_url+'&folderid='+jQuery(this).attr('folderid')+"&uniqueid="+Math.random().toString(36).slice(2),
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
		if (jQuery('.add_folder').length>0){
			jQuery('#lviewfolder_button_list').hide();
		}
		jQuery('.add_folder').on({
			mouseenter:function(){
				dragenter_css(jQuery(this));
			},
			mouseleave:function(){
				dragleave_css(jQuery(this));
			},
			click:function(e){
				jQuery('#lviewfolder_button_add').click();
			}
		});
	});
{/literal}
</script>
</head>
<body class="small">
<div id="layerbg"></div>
<div id="Buttons_List_3_Container" style="display:block;">
<table class="level3Bg" border=0 cellspacing=0 cellpadding=2 width=100% class="small">
	<tr>
		<td style="padding:5px">
			<table border="0" cellspacing="0" cellpadding="0" width="100%">
				<tr>
					<td align="left">
						<form id="homeblockform" method="POST" action="index.php">
							<input type="hidden" name="module" value ="{$MODULE}" />
							<input type="hidden" name="action" value ="{$MODULE}Ajax" />
							<input type="hidden" name="file" value="HomeBlock" />
							<input type="hidden" name="view_mode" value="{$MODE}" />
							<input type="hidden" name="last_view_mode" value="{$LAST_MODE}" />
							<input type="hidden" name="folder_selected" value="{$FOLDERID}" />
							<input type="hidden" name="last_folder_selected" value="{$LAST_FOLDERID}" />
							<input type="hidden" name="folder_name" value="{$FOLDERNAME}" />
							<input type="hidden" name="last_folder_name" value="{$LAST_FOLDERNAME}" />
							<input type="hidden" name="title" value="" />
							<input type="hidden" name="stuffid" value="{$STUFFID}" />
							<input type="hidden" name="myfilesid_selected" value="{$MYFILESID}" />
							<input type="hidden" name="file_name" value="{$FILE_NAME}" />
							<input id="button_back" type="button" class="crmbutton small edit" value="<-" text="<-" style="display:none;"/>
							<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
							<input id="lviewfolder_button_list" type="button" class="crmbutton small edit" />
						    <input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
						</form>
					</td>
					<td align="right">
						<input id="lviewfolder_button_add" type="button" name="add" value="{$APP.LBL_ADD_NEW_FOLDER}" class="crmbutton small edit" onClick="fnvshobj(this,'lview_folder_add');" title="{$APP.LBL_ADD_NEW_FOLDER}">&nbsp;
						<input id="lviewfolder_button_del" type="button" name="delete" value="{$APP.LBL_DELETE_FOLDERS}" class="crmbutton small delete" onClick="lviewfold_del();" title="{$APP.LBL_DELETE_FOLDERS}">
						<input id="lviewfolder_button_del_save" style="display:none" type="button" name="delete_save" value="{$APP.LBL_DELETE_BUTTON}" class="crmbutton small delete" onClick="lviewfold_del_save('{$MODULE}');" title="{$APP.LBL_DELETE_BUTTON}">
						<input id="lviewfolder_button_del_cancel" style="display:none" type="button" name="delete_cancel" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" class="crmbutton small cancel" onClick="lviewfold_del_cancel();" title="{$APP.LBL_CANCEL_BUTTON_LABEL}">
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</div>
<div style="display:none;">
	<span id="vtbusy_info" style="display:none;" valign="bottom">{include file="LoadingIndicator.tpl"}</span>
</div>
<div id="lview_folder_add" style="display:none; position:fixed;" class="crmvDiv">
	<table border="0" cellpadding="5" cellspacing="0" width="100%">
		<tr style="cursor:move;" height="34">
			<td id="AddFolder_Handle" style="padding:5px" class="level3Bg">
				<table cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td width="80%"><b>
						<span>{$APP.LBL_ADD_NEW_FOLDER}</span>
					</b></td>
					<td width="20%" align="right">
						<input id="lview_folder_save" type="button" value="{$APP.LBL_SAVE_LABEL}" name="button" class="crmbutton small save" title="{$APP.LBL_SAVE_LABEL}" onclick="lviewfold_add()" />
					</td>
				</tr>
				</table>
			</td>
		</tr>
	</table>
	<div id="lview_folder_addcont">
		<form name="lview_folder_addform" id="lview_folder_addform">
			<input type="hidden" name="formodule" value="{$MODULE}" />
			<input type="hidden" name="subaction" value="add" />
		<table cellpadding="5" cellspacing="0" class="hdrNameBg" >
			<tr>
				<td>{$APP.LBL_FOLDER_NAME}</td>
				<td><input type="text" class="detailedViewTextBox" maxlength="20" name="foldername" value="" /></td> {* crmv@198701 *}
			</tr>
			<tr>
				<td>{$APP.LBL_DESCRIPTION}</td>
				<td><input type="text" class="detailedViewTextBox" maxlength="50" name="folderdesc" value="" /></td> {* crmv@198701 *}
			</tr>
		</table>
		</form>
	</div>
	<br />
	<div class="closebutton" onClick="fninvsh('lview_folder_add');"></div>
</div>

<div id="lview_file_convert" style="display:none; position:fixed;" class="crmvDiv">
	<table border="0" cellpadding="5" cellspacing="0" width="100%">
		<tr height="34">
			<td id="Convertfile_Handle" style="padding:5px" class="level3Bg">
				<table cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td width="80%"><b>
						<span>{'LBL_CONVERT_TO_DOCUMENT'|@getTranslatedString:$MODULE}</span>
					</b></td>
					<td width="20%" align="right">
						<input id="lview_folder_save" type="button" value="{$APP.LBL_SAVE_LABEL}" name="button" class="crmbutton small save" title="{$APP.LBL_SAVE_LABEL}" onclick="lview_convert_file()" />
					</td>
				</tr>
				</table>
			</td>
		</tr>
	</table>
	<div id="lview_file_details_content">
		<form name="lview_file_convertform" id="lview_file_convertform">
			<input type="hidden" name="fileid" value="" />
		<table cellpadding="5" cellspacing="0" class="hdrNameBg" >
			<tr>
				<td>{$APP.LBL_FOLDER_NAME}</td>
				<td>
					<select name="folderid">
						{foreach key=folderid item=foldername from=$FOLDERLISTSELECT}
							<option name="foldername" value="{$folderid}" />{$foldername}</option>
						{/foreach}
					</select>
				</td>
			</tr>
			<tr>
				<td>{$APP.LBL_TITLE}</td>
				<td><input type="text" maxlength="50" name="title" value="" /></td>
			</tr>
		</table>
		</form>
	</div>
	<br />
	{literal}
	<div class="closebutton" onClick="jQuery('#lview_file_convert').hide(function(){jQuery('#layerbg').css({'display': 'none'})});"></div>
	{/literal}
</div>

<div id="lview_table_cont" class="small lview_folder_table">
	{if $MODE eq 'folder'}
	<div id="content_folder">
	{foreach item=folder from=$FOLDERLIST}
		<div class="lview_folder_td dropzone_target" folderid="{$folder.folderid}">
			<div>
				<a class="folder" folderid="{$folder.folderid}" foldername="{$folder.foldername}" href="javascript:"><img src="modules/Myfiles/src/img/folder_icon.png" border="0" /></a><br />
			</div>
			<div>
				{if $folder.count eq 0}
				<span id="lview_folder_checkspan_{$folder.folderid}" style="display:none"><input type="checkbox" name="lvidefold_check_{$folder.folderid}" id="lvidefold_check_{$folder.folderid}" value="" /></span>
				{/if}
				<span class="lview_folder_span">{$folder.foldername} ({$folder.count})</span><br />
				<div class="lview_folder_desc">{$folder.description}&nbsp;</div>
			</div>
		 </div>
	{/foreach}
	</div>
	{elseif $MODE eq 'global'}
	<div id="content_global">
	{foreach item=folder from=$FOLDERLIST}
		{assign var=foldercontent value=$folder.content}
		<table class="lvt small" width="100%" cellspacing="1" cellpadding="3" border="0">
			<tr class="lvtColData" folderid="{$folder.folderid}">
				<td class="lvtColData" width="10%" valign="center" align="center" colspan="{$foldercontent.count}">
					<img class="lview_folder_img" src="modules/Myfiles/src/img/folder_icon.png" border="0" /><br />
					{$folder.foldername} ({$foldercontent.count})<div class="lview_folder_desc">{$folder.description}&nbsp;</div>					
				</td>
				<td width="60%" valign="top">
					<table width="100%" cellspacing="1" cellpadding="3" border="0">
						{foreach item=files_arr from=$foldercontent.files}
							<tr onmouseout="this.className='lvtColData'" onmouseover="this.className='lvtColDataHover'" class="lvtColData">
								<td>
									{foreach item=icon from=$files_arr.fastmenu}
										{$icon}&nbsp;
									{/foreach}								
									{$files_arr.title|truncate:30} {$files_arr.link}
								</td>
							</tr>
						{/foreach}
					</table>		
				</td>
				<td width="20%" class="dropzone_target click_target border_change" folderid="{$folder.folderid}">
					<div class="drag_zone">
						<span class="message"><b>{'LBL_CLICK_DRAG_UPLOAD'|@getTranslatedString:$MODULE}</b></span>
					</div>
				</td>
			</tr>
		</table>	
	{/foreach}
	</div>
	{elseif $MODE eq 'icon'}
	<div id="content_icon">
	{foreach item=folder key=folderid from=$FOLDERLIST}
		{assign var=foldercontent value=$folder.content}
		<div folderid="{$folder.folderid}">
			<table width="100%" cellspacing="1" cellpadding="3" border="0">
				<tr>
					<td width="80%">
					{foreach item=files_arr from=$foldercontent.files}
					<div class="lview_folder_td" fileid="{$files_arr.id}">
						<div class="icon" fileid="{$files_arr.id}">
							<a href="javascript:"><img class="lview_file_img" src="modules/Myfiles/src/img/file_icon.png" border="0" /></a><br />
							<div class="action-menu" fileid="{$files_arr.id}">
								<div>
									{foreach item=icon from=$files_arr.fastmenu}
										<div>{$icon}</div>
									{/foreach}						    
						    	</div>
					    	</div>
							<div>
								<span class="lview_file_span">{$files_arr.link}</span><br />
								<div class="lview_file_desc" style="height:15px">{$files_arr.title|truncate:30}</div>
							</div>
						</div>
					</div>	
					{/foreach}
					<td height="100%" width="20%" class="dropzone_target click_target border_change" folderid="{$folderid}">
						<div class="drag_zone">
							<span class="message"><b>{'LBL_CLICK_DRAG_UPLOAD'|@getTranslatedString:$MODULE}</b></span>
						</div>
					</td>
				</tr>
			</table>	
		</div>
	{/foreach}
	</div>
	{elseif $MODE eq 'list'}
	<div id="content_list">
		<table class="lvt small" width="100%" cellspacing="1" cellpadding="3" border="0">
			{foreach item=folder from=$FOLDERLIST}
				{assign var=foldercontent value=$folder.content}
					<tr class="lvtColData" ondragover="return false" folderid="{$folder.folderid}">
						<td>
							<table width="100%" cellspacing="1" cellpadding="3" border="0">
								{foreach item=files_arr from=$foldercontent.files}
									<tr onmouseout="this.className='lvtColData'" onmouseover="this.className='lvtColDataHover'" class="lvtColData">
										<td>
											{foreach item=icon from=$files_arr.fastmenu}
												{$icon}&nbsp;
											{/foreach}
											{$files_arr.title|truncate:30} {$files_arr.link}
										</td>
									</tr>
								{/foreach}
							</table>		
						</td>
						<td width="20%" ondragover="return false" class="dropzone_target click_target border_change" folderid="{$folder.folderid}">
							<div class="drag_zone">
								<span class="message"><b>{'LBL_CLICK_DRAG_UPLOAD'|@getTranslatedString:$MODULE}</b></span>
							</div>
						</td>						
					</tr>
			{/foreach}
		</table>
	</div>
	{elseif $MODE eq 'detailview'}
	<div id="content_detail">
		{$FOLDERLIST}
	</div>
	{/if}
	{if $EMPTY_FOLDERS eq 'true'}
		<div class="add_folder drag_zone border_change">
			<span><b>{'LBL_CLICK_CREATE_FOLDER'|@getTranslatedString:$MODULE}</b></span>
		</div>
	{/if}
</div>
</body>
</html>