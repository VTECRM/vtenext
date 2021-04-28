<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $adb,$current_user,$table_prefix,$currentModule;
$ajax_mode = false;
if ($_REQUEST['action'] == $currentModule.'Ajax'){
	$ajax_mode = true;
}
$allowed_actions = Array(
	'index','edit','list','get_picklists',
);
$page_action = 'index';
if ($_REQUEST['page_action'] != ''){
	$page_action = $_REQUEST['page_action'];
}
if (!in_array($page_action,$allowed_actions) || !is_admin($current_user)){
	notallowed_display($ajax_mode);
}
$allowed_options = Array(
	'add'=>'true',
	'del'=>'false',
	'edit'=>'true',
	'multiselect'=>'false',
);
switch($page_action){
	case 'index':
		if ($ajax_mode){
			notallowed_display($ajax_mode);
		}
		$alllanguages = lang_initialize('languages');
		construct_table($alllanguages);
		initialize_table($alllanguages);
		$allmodules = lang_initialize('modules');
		$allpicklists = lang_initialize('picklists');
		if (!isset($current_user->default_language)){
			$curr_lang = $current_user->default_language;
		}
		elseif (!isset($current_user->column_fields['default_language'])){
			$curr_lang = $current_user->column_fields['default_language'];
		}
		else{
			global $default_language;
			$curr_lang = $default_language;
		}
		$lang = explode("_",$curr_lang);
		$lang = $lang[0];
		$ALL = getTranslatedString('LBL_TRANS_ALL',$currentModule);
		$TITLE = getTranslatedString('LBL_TRANS_LANGUAGE',$currentModule);
		$HTML_PICKLISTS = get_options($allpicklists);
		$MANDATORY = getTranslatedString('LBL_TRANS_MANDATORY',$currentModule);
		$MODULE = getTranslatedString('LBL_TRANS_MODULE',$currentModule);
		$LABEL = getTranslatedString('LBL_TRANS_LABEL',$currentModule);
		$SEARCH = getTranslatedString('LBL_TRANS_SEARCH',$currentModule);
		$ACTIONS = getTranslatedString('LBL_TRANS_ACTIONS',$currentModule);
		//js stuff
echo <<<EOT
		<!-- crmv@193430 - jquery ui is already included -->
		<link rel="stylesheet" type="text/css" media="screen" href="include/js/jquery_plugins/jqgrid/css/ui.jqgrid.css" />
		<script language="JavaScript" type="text/javascript" src="include/js/jquery_plugins/jqgrid/i18n/grid.locale-{$lang}.js"></script>
		<script language="JavaScript" type="text/javascript" src="include/js/jquery_plugins/jqgrid/jqgrid.js"></script>
		<!-- crmv@193430e -->
		<script>
		function init_language_table(mode){
			var module = jQuery('#module_select').val();
			var language = jQuery('#language_select').val();
			var picklist = jQuery('#picklist_select').val();
			var filter = jQuery('#filter_select').val();
			var untranslated = jQuery('#untrans_check').is(':checked');
			var onlyuntranslated = 0;
			if (untranslated == true){
				onlyuntranslated = 1;
			}
			var colNames = new Array();
			var colModel = new Array();
			var moduleoptions = {}
			var prop = '';
			var propval = '';
			jQuery('#module_select option').each(function(){
				prop = jQuery(this).val();
				propval = jQuery(this).text();
				if (prop != ''){
					moduleoptions[prop] = propval;
				}
			});
			var grid = jQuery("#trans_table"),
                myDelOptions = {
                	beforeShowForm:before_show_del,beforeSubmit:before,afterSubmit:after
                },
                lastSel,
                myEditOptions = {
                	beforeShowForm:before_show_edit,beforeSubmit:before,afterSubmit:after,bottominfo:'"$MANDATORY"'
				},		
                myAddOptions = {
                	beforeShowForm:before_show_add,beforeSubmit:before,afterSubmit:after,bottominfo:'"$MANDATORY"'
				};			
                myDelOptions = {
                	jqModal:true,afterSubmit:after,closeAfterDelete:true
				};			
			colNames.push('$ACTIONS');
            colModel.push({name:'act',index:'act',width:25,align:'center',sortable:false,search:false,formatter:'actions',
                     formatoptions:{
                     	keys: true,
                     	delbutton: {$allowed_options['del']},
                     	editbutton: {$allowed_options['edit']},
                     	editformbutton: true,             	
                     	editOptions:myEditOptions,
                     	delOptions:myDelOptions
                     }});			
			colNames.push('$MODULE *');
			colModel.push({name:'modulename',index:'modulename',editable:true,editrules:{required:true},edittype:'select',editoptions:{value:moduleoptions},search:false});
			colNames.push('$LABEL *');
			colModel.push({name:'label',index:'label',editable:true,editrules:{required:true}});
			var val = '';
			var txt = '';
			if (jQuery('#language_select').val() == ''){
				jQuery('#language_select option').each(function(){
					if (jQuery(this).val() != ''){
						colNames.push(jQuery(this).text());
						val = jQuery(this).val();
						colModel.push({name:val,index:val,editable:true,edittype:'textarea',editoptions:{size:"20"},search:true});
					}
				});
			}
			else{
				val = jQuery('#language_select :selected').val();
				colNames.push(jQuery('#language_select :selected').text());
				colModel.push({name:val,index:val,editable:true,edittype:'textarea',editoptions:{size:"20"}});
			}
			var list_url = 'index.php?module=$currentModule&action={$currentModule}Ajax&page_action=list&file=LanguageEditor&module_select='+module+'&language_select='+language+'&picklist_select='+picklist+'&filter_select='+filter+'&onlyuntranslated='+onlyuntranslated;
			if (typeof mode != 'undefined' && mode == 'reload'){
				jQuery("#trans_table").jqGrid("setGridParam",{'url':list_url}).trigger("reloadGrid");
				return;
			}
			var edit_url = 'index.php?module=$currentModule&action={$currentModule}Ajax&file=LanguageEditor&page_action=edit';
			grid.jqGrid({
				url:list_url, 
				datatype: "json",
			   	colNames:colNames,
			   	colModel:colModel,
				autowidth: true,
				pager: '#trans_table_nav',
				gridview:true,
                rownumbers:true,
                viewrecords: true,			
				rowNum:30, 
				rowList:[20,40,60,'{$ALL}'],
			   	editurl:edit_url, 
			   	caption: '',
			   	multiselect: {$allowed_options['multiselect']},
			   	toppager:true,
			   	drag:true,
			   	height:jQuery(document).height() - jQuery('#trans_table').offset().top - jQuery('#vte_footer').height()-85,
			   	jqModal:true,
			});
			//grid.jqGrid('filterToolbar',{}); //TODO: ricerca veloce
			grid.navGrid('#trans_table_nav',
				{refresh:true,search:true,edit:false,del:{$allowed_options['del']},add:{$allowed_options['add']},cloneToTop:true}, //options
				myEditOptions,	// edit options 
				myAddOptions,	// add options
				myDelOptions,	//del options
				{sopt:['cn','eq','bw','ew'],find:"$SEARCH"}	//search options
			);
		}
		function before_show_del(formid){
		
		}
		function before_show_edit(formid){
			jQuery('#modulename',formid).attr('disabled',true);
			jQuery('#label',formid).attr('readonly',true);
		}
		function before_show_add(formid){
			if (jQuery('#module_select').val() != ''){
				jQuery('#modulename',formid).val(jQuery('#module_select').val());
			}
			jQuery('#modulename',formid).attr('disabled',false);
			jQuery('#label',formid).attr('readonly',false);
		}
		function before(postdata, formid){
			return [true,"",""];
		}
		function after(response, postdata){
			var response = response.responseText;
			response = eval('('+response+')');
			if (response['confirm']){
				if (confirm(response['msg'])){
					response['success'] = true;
				}
				else{
					response['success'] = false;
					response['msg'] = '';
				}
			}
			return [response['success'],response['msg'],""];
		}
		function update_picklists(value){
			var res = getFile('index.php?module=$currentModule&action={$currentModule}Ajax&file=LanguageEditor&page_action=get_picklists&module_select='+value);
			res = eval("("+res+")");
			jQuery('#picklist_select option').remove();
			jQuery('#picklist_select').append("$HTML_PICKLISTS");
			if (res['success']){
				var numres = res['picklists'].length;
				for (var i=0;i<numres;i++){
					jQuery('#picklist_select').append('<option value="'+res['picklists'][i][0]+'">'+res['picklists'][i][1]+'</option>');
				}
			}
		}
		jQuery(document).ready(function() {
			init_language_table();
		});
		</script>
EOT;
		
		//costruisco la picklist dei linguaggi
		echo "<table id='trans_search' name='trans_search' border='0' cellpadding='5' cellspacing='0' width='100%'>
		<tr>
			<td align='center'>
			".getTranslatedString('LBL_TRANS_LANGUAGE',$currentModule)."&nbsp;
			<select id='language_select' name='language_select' onchange='jQuery(\"#trans_table\").jqGrid(\"GridUnload\");init_language_table();'>
				".get_options($alllanguages)."
			</select>	
			</td>
			<td align='center'>
			".getTranslatedString('LBL_TRANS_MODULE',$currentModule)."&nbsp;
			<select id='module_select' name='module_select' onchange='update_picklists(this.value);init_language_table(\"reload\");'>
				".get_options($allmodules)."
			</select>				
			</td>
			<td align='center'>
			".getTranslatedString('LBL_TRANS_FILTER_NAME',$currentModule)."&nbsp;
			<select id='filter_select' name='filter_select' onchange='init_language_table(\"reload\");'>
				<option value=''>".getTranslatedString('LBL_TRANS_FILTER_ALL',$currentModule)."</option>
				<option value='fields'>".getTranslatedString('LBL_TRANS_FILTER_FIELDS',$currentModule)."</option>
				<option value='fieldvalues'>".getTranslatedString('LBL_TRANS_FILTER_FIELDVALUES',$currentModule)."</option>
				<option value='other'>".getTranslatedString('LBL_TRANS_FILTER_OTHER',$currentModule)."</option>
			</select>
			</td>
			<td align='center'>
			".getTranslatedString('LBL_TRANS_SHOW_ONLY_NOT_TRANSLATED',$currentModule)."&nbsp;
			<input type='checkbox' id='untrans_check' name='untrans_check' onclick='init_language_table(\"reload\");'>
			</td>
			<td align='center'>
			".getTranslatedString('LBL_TRANS_PICKLIST_FIELDS',$currentModule)."&nbsp;
			<select id='picklist_select' name='picklist_select' onchange='jQuery(\"#filter_select\").val(\"\");init_language_table(\"reload\");'>
				".$HTML_PICKLISTS."
			</select>				
			</td>
		</tr>
		<tr>
			<table id='trans_table'></table> 
			<div id='trans_table_nav'></div>
		</tr>
		</table>
		";
		break;
	case 'list':
		if (!$ajax_mode){
			notallowed_display($ajax_mode);
		}
		$search = ($_GET['_search'] == 'true')?true:false;
		$searchfield = $_GET['searchField'];
		$searchoper = $_GET['searchOper'];
		$searchstring = $_GET['searchString'];
		$orderfield = $_GET['sidx'];
		$ordermeth = $_GET['sord'];
		$page = $_GET['page'];
		$limit = $_GET['rows'];
		$sidx = $_GET['sidx'];
		$sord = $_GET['sord'];
		$module_select = $_GET['module_select'];
		$language_select = $_GET['language_select'];
		$picklist_select = $_GET['picklist_select'];
		$filter_select = $_GET['filter_select'];
		$onlyuntranslated = $_GET['onlyuntranslated'];
		$start = $limit*$page - $limit;
		if(!$sidx) $sidx =1;
		$languages = lang_initialize('languages');
		//genero la query
		$selects = Array(
			'module',		
			'label',		
		);
		$sql = "from sdk_language_translate";
		$languages = array_filter(array_keys($languages));
		$params = Array();
		$wheres = Array();
		$orderby = Array();
		if ($module_select!= ''){
			$wheres[] = "module = ?";
			$params[] = $module_select;
		}
		if ($language_select != ''){
			$selects[] = $language_select;
		}
		else{
			foreach ($languages as $language_prefix){
				$selects[] = $language_prefix;
			}
		}
		if ($filter_select != ''){
			switch ($filter_select){
				case 'fields':
					$wheres[] = "(coalesce(fieldname,'') <> '' and fieldtitle = 1)";
					break;
				case 'fieldvalues':
					$wheres[] = "(coalesce(fieldname,'') <> '' and fieldtitle = 0)";
					break;
				case 'other':
					$wheres[] = "coalesce(fieldname,'') = ''";
					break;
			}
		}
		if ($onlyuntranslated == 1){
			if ($language_select != ''){
				$wheres[] = "coalesce($language_select,'') = ''";
			}
			else{
				$where_untrans = Array();
				foreach ($languages as $language_prefix){
					$where_untrans[] = "coalesce($language_prefix,'') = ''";
				}
				if (!empty($where_untrans)){
					$wheres[] = "(".implode(" or ",$where_untrans).")";
				}
			}
		}
		if ($picklist_select != ''){
			$wheres[] = "fieldname = ?";
			$params[] = $picklist_select;
			$orderby[] = 'fieldtitle desc';
		}
		//ricerca di jqgrid
		if ($search){
			switch($searchoper){
				case 'eq':
					$operator = '=';
					break;
				case 'ne':
					$operator = '<>';
					break;
				case 'ew':
					$operator = 'like';
					$searchstring = '%'.$searchstring;
					break;
				case 'bw':
					$operator = 'like';
					$searchstring = $searchstring.'%';
					break;
				case 'cn':
					$operator = 'like';
					$searchstring = '%'.$searchstring.'%';
					break;				
			}
			$wheres[] = "{$searchfield} {$operator} ?";
			$params[] = $searchstring;
		}
		if ($orderfield != '' && $ordermeth != ''){
			$orderby[] = "$orderfield $ordermeth";
		}
		if (!empty($wheres)){
			$query = "select ".implode(",",$selects)." from sdk_language_translate where ".implode(" AND ",$wheres);
		}
		else{
			$query = "select ".implode(",",$selects)." from sdk_language_translate";
		}
		if (!empty($orderby)){
			$query.=" order by ".implode(",",$orderby);
		}
		$query = $adb->convert2Sql($query,$adb->flatten_array($params));
		//conto cmq tutte
		$cnt_query = replaceSelectQuery($query);
		$res_cnt = $adb->query($cnt_query);
		$total = 0;
		if ($res_cnt){
			$total = $adb->query_result($res_cnt,0,'count');
		}
		if (!is_numeric($limit)){
			$res = $adb->query($query);
			if ($res){
				$limit = $cnt = $adb->num_rows($res);
			}
			$page = 1;
		}	
		else{
			$res = $adb->limitquery($query,$start,$limit);
			if ($res){
				$cnt = $adb->num_rows($res);
			}
		}
		if ($res && $cnt>0){
			$i = 0;
			while($row = $adb->fetchByAssoc($res)){
				array_unshift($row,''); //aggiungo la colonna vuota per le azioni
				$response->rows[$i]['id']=bin2hex(base64_encode(Zend_Json::encode(Array('module = ?'=>$row['module'],'label = ?'=>$row['label']))));
				$response->rows[$i]['cell']=array_values($row);
				$i++;
			}
		}
		else{
			$response->success = false;
			$response->message = getTranslatedString('LBL_EMPTY');			
		}
		if ($total > 0){
			$total_pages = ceil($total/$limit);
		}	
		else{
			$total_pages = 0;
		}	 	 
		if ($page > $total_pages){ 
			$page=$total_pages; 
		}	
		$response->page = $page;	
		$response->total = $total_pages;	
		$response->records = $total;
		echo Zend_Json::encode($response);	
		break;
	case 'get_picklists':
		$fieldtype_allowed = get_fieldtype_allowed();
		$sql = "select f.fieldname,f.fieldlabel,t.name as module from {$table_prefix}_field f
		inner join {$table_prefix}_tab t on t.tabid = f.tabid
		inner join {$table_prefix}_picklist p on p.name = f.fieldname
		inner join {$table_prefix}_ws_fieldtype e on e.uitype = f.uitype
		where t.name = ? and e.fieldtype in (".generateQuestionMarks($fieldtype_allowed).") and f.presence in (0,2)";
		$params = Array($_GET['module_select'],$fieldtype_allowed);
		$res = $adb->pquery($sql,$params);
		$response = Array('success'=>false);
		if ($res && $adb->num_rows($res)>0){
			$response['success']=true;
			while($row = $adb->fetchByAssoc($res)){
				$response['picklists'][] = Array($row['fieldname'],getTranslatedString($row['fieldlabel'],$row['module']));
			}
		}
		echo Zend_Json::encode($response);
		break;
	case 'edit':
		$response = Array('success'=>false,'msg'=>false,'confirm'=>false);
		$languages = $languages_backup = lang_initialize('languages');
		$languages = array_filter(array_keys($languages));
		//operazioni di modifica,aggiunta demandata al modulo SDK
		$update_languages = Array();
		foreach ($languages as $language_prefix){
			if (isset($_POST[$language_prefix])){
				$update_languages[$language_prefix] = $_POST[$language_prefix];
			}
		}
		switch($_POST['oper']){
			case 'add':
				//controllo se esiste gi�
				$params_check = Array($_POST['modulename'],$_POST['label']);
				$sql_check = "select count(*) as count from sdk_language_translate where module = ? and label = ?";
				$res_check = $adb->pquery($sql_check,$params_check);
				if ($res_check && $adb->query_result_no_html($res_check,0,'count') > 0){
					$response['confirm'] = true;
					$response['msg'] = getTranslatedString('LBL_TRANS_DUPLICATE_MESSAGE_BEFORE',$currentModule);
					foreach ($update_languages as $lang=>$val_trans){
						$response['msg'].="\n $languages_backup[$lang]: ".getTranslatedString($_POST['label'],$_POST['modulename']);
					}
					$response['msg'].="\n";
					$response['msg'] .= getTranslatedString('LBL_TRANS_DUPLICATE_MESSAGE_AFTER',$currentModule);
				}
				else{
					SDK::setLanguageEntries($_POST['modulename'],$_POST['label'],$update_languages);
					$params_add = Array(
						'module'=>$_POST['modulename'],
						'label'=>$_POST['label']
					);
					foreach ($update_languages as $pref=>$upd){
						$params_add[$pref] = $upd;
					}
					$sql_add = 'insert into sdk_language_translate('.implode(",",array_keys($params_add)).') values ('.generateQuestionMarks($params_add).')';
					$res_add = $adb->pquery($sql_add,$params_add);
					if ($res_add){
						$response['success'] = true;
					}
				}
				break;
			case 'edit':
				SDK::setLanguageEntries($_POST['modulename'],$_POST['label'],$update_languages);
				//controllo se � legato ad un campo o un valore di picklist
				$params_check = Array($_POST['modulename'],$_POST['label']);
				$sql_check = "select fieldname,fieldtitle from sdk_language_translate where module = ? and label = ?";
				$res_check = $adb->pquery($sql_check,$params_check);
				$params_where = Array(
					'module = ?'=>$_POST['modulename'],
					'label = ?'=>$_POST['label'],
				);
				$params_edit = Array();
				if ($res_check){
					$params_edit['fieldname = ?'] = $adb->query_result($res_check,0,'fieldname');
					$params_edit['fieldtitle = ?'] = $adb->query_result($res_check,0,'fieldtitle');
				}
				foreach ($update_languages as $pref=>$upd){
					$params_edit["{$pref} = ?"] = $upd;
				}				
				$sql_edit = "update sdk_language_translate set ".implode(",",array_keys($params_edit))." where ".implode(" and ",array_keys($params_where));
				$res_edit = $adb->pquery($sql_edit,Array($params_edit,$params_where));
				if ($res_edit){
					$response['success'] = true;
				}
				break;
			case 'del':
				foreach ($update_languages as $pref=>$upd){
					SDK::deleteLanguageEntry($POST['modulename'],$pref,$POST['label']);
				}
				if (strpos($_POST['id'],",")!==false){
					//cancellazione multipla
					$ids = explode(",",$_POST['id']);
				}
				else{
					$ids = Array($_POST['id']);
				}
				foreach ($ids as $id){
					$where_del = Zend_Json::decode(base64_decode(pack("H*",$id)));
					$sql_del = "delete from sdk_language_translate where ".implode(" and ",array_keys($where_del));
					$res_del = $adb->pquery($sql_del,$where_del);
					if (!$res_del){
						break;
					}
				}
				if ($res_del){
					$response['success'] = true;
				}
				break;		
		}
		if ($response['success'] == false && $response['msg'] ===false){
			$response['msg'] = getTranslatedString('LBL_TRANS_ERR',$currentModule);
		}
		echo Zend_Json::encode($response);
		break;		
}
//funzioni----------------------------------------------------------------
function notallowed_display($ajax_mode){
	global $theme;
	if ($ajax_mode){
		echo Zend_Json::encode(Array('success'=>false,'message'=>GetTranslatedString('LBL_PERMISSION')));
		exit;
	}
	echo "<link rel='stylesheet' type='text/css' href='themes/$theme/style.css'>";	
	echo "<table border='0' cellpadding='5' cellspacing='0' width='100%' height='450px'><tr><td align='center'>";
	echo "<div style='border: 3px solid rgb(153, 153, 153); background-color: rgb(255, 255, 255); width: 55%; position: relative; z-index: 10000000;'>

		<table border='0' cellpadding='5' cellspacing='0' width='98%'>
		<tbody><tr>
		<td rowspan='2' width='11%'><img src='". resourcever('denied.gif') . "' ></td>
		<td style='border-bottom: 1px solid rgb(204, 204, 204);' nowrap='nowrap' width='70%'><span class='genHeaderSmall'>".getTranslatedString('LBL_PERMISSION')."</span></td>
		</tr>
		<tr>
		<td class='small' align='right' nowrap='nowrap'>			   	
		<a href='javascript:window.history.back();'>".getTranslatedString('LBL_GO_BACK')."</a><br>
		</td>
		</tr>
		</tbody></table> 
		</div>";
	echo "</td></tr></table>";
	die;
}
function lang_initialize($type){
	global $currentModule;
	$arr = Array();
	switch ($type){
		case 'languages':
			$all_languages = vtlib_getToggleLanguageInfo();
			$arr[''] = getTranslatedString('LBL_TRANS_ALL',$currentModule);
			foreach ($all_languages as $lang=>$lang_vals){
				$arr[$lang] = getTranslatedString($lang_vals['label']);
			}			
			break;
		case 'modules':
			$all_modules = SDK::getModuleLanguageList();
			$arr[''] = getTranslatedString('LBL_TRANS_ALL',$currentModule);
			$arr['APP_STRINGS'] = getTranslatedString('LBL_TRANS_APP_STRINGS',$currentModule);
//			$arr['APP_LIST_STRINGS'] = getTranslatedString('LBL_TRANS_APP_LIST_STRINGS',$currentModule); //tolto perch� immodificabile, sono array in JSON
//			$arr['APP_CURRENCY_STRINGS'] = getTranslatedString('LBL_TRANS_APP_CURRENCY_STRINGS',$currentModule); //tolto perch� sempre vuoto...
			foreach ($all_modules as $mod){
				$arr[$mod] = getTranslatedString($mod,$mod);
			}		
			break;
		case 'picklists':
			$arr[''] = getTranslatedString('LBL_TRANS_NONE',$currentModule);
			break;
	}
	return $arr;
}
//retrieve languages
function get_options($arr){
	foreach ($arr as $prefix=>$value){
		$html.="<option value='{$prefix}'>{$value}</option>";
	}
	return $html;
}
//retrieve sql
function get_lang_query($module_select,$language_select,$picklist_select,$languages){
	global $adb;
	$alias = 1;
	$selects = Array(
		"l.module",
		"l.label",
		"l.fieldname",
	);
	$first = true;
	foreach ($languages as $lang_prefix=>$lang_label){
		if ($lang_prefix == ''){
			continue;
		}
		if ($language_select != '' && $language_select != $lang_prefix){
			continue;
		}
		if ($first){
			$sql = "FROM (
			SELECT l.module,tf.fieldname,l.trans_label,l.language,l.label FROM sdk_language l
				LEFT JOIN (
				SELECT f.fieldlabel,t.name,f.fieldname
				FROM vte_field f 
				INNER JOIN vte_tab t ON t.tabid = f.tabid
				) tf ON (tf.fieldlabel = l.label AND tf.name = l.module)
			) l ";
			$where ="where l.language = ?";
			if ($module_select != ''){
				$where.=" and l.module = ?";
				$where_module = $module_select;
			}
			$where_language = $lang_prefix;
			$selects[] = "l.trans_label as {$lang_prefix}";
			$first = false;
			continue;	
		}
		$sql .= "left join sdk_language l{$alias} on (l{$alias}.module = l.module and l{$alias}.label = l.label and l{$alias}.language = ?) ";
		$params[] = $lang_prefix;
		$selects[] = "l{$alias}.trans_label as {$lang_prefix}";
		$alias++;
	}
	if ($where_language != ''){
		$params[] = $where_language;
	}		
	if ($where_module != ''){
		$params[] = $where_module;
	}	
	$sql = "select ".implode(",",$selects)." $sql $where";
	return $adb->convert2Sql($sql,$adb->flatten_array($params));
}
function get_fieldtype_allowed(){
	return Array('picklist','multipicklist');
}
function construct_table($languages){
	global $adb;
	if ($adb->table_exist('sdk_language_translate')){
		$adb->query('drop table sdk_language_translate');
	}
	$languages = array_filter(array_keys($languages));
	//crmv@100548
	if($adb->isMssql()){
		$fields = Array(
			'module C(50)',
			'fieldname C(50)',
			'label VARCHAR(4000)',
			'fieldtitle I(1) default 0'
		);
		foreach ($languages as $language){
			$fields[] = "$language VARCHAR(4000)";
		}
	}
	else{
		$fields = Array(
			'module C(50)',
			'fieldname C(50)',
			'label XL',
			'fieldtitle I(1) default 0'
		);
		foreach ($languages as $language){
			$fields[] = "$language XL";
		}
	}
	//crmv@100548e
	Vtecrm_Utils::CreateTable('sdk_language_translate',implode(",",$fields));
}
function initialize_table($languages){
	global $adb,$table_prefix;
	//rimpiazzo l'indice con uno non univoco
	//crmv@100548
	if($adb->isMssql()){
		@$adb->datadict->ExecuteSQLArray((Array)$adb->datadict->CreateIndexSQL('idx_module_label', 'sdk_language_translate', 'module,label'));
	}
	else{
		@$adb->datadict->ExecuteSQLArray((Array)$adb->datadict->CreateIndexSQL('idx_module_label', 'sdk_language_translate', 'module,label(255)'));
	}
	//crmv@100548e
	$languages = array_filter(array_keys($languages));
	$alias = 1;
	$selects = Array(
		"l.module",
		"l.label",
	);
	//inserisco tutte le traduzioni presenti in sdk_language
	//crmv@100548
	if($adb->isMssql()){
		$sql = "from (SELECT module,cast(label as varchar(max)) as label FROM sdk_language GROUP BY module,cast(label as varchar(max))) l ";
	}
	else{
		$sql = "from (SELECT module,label FROM sdk_language GROUP BY module,label) l ";
	}
	//crmv@100548e
	foreach ($languages as $lang_prefix){
		//crmv@100548
		if($adb->isMssql()){
			$sql .= "left join sdk_language l{$alias} on (l{$alias}.module = l.module and cast(l{$alias}.label as varchar(max)) = l.label and l{$alias}.language = ?) ";
		}
		else{
			$sql .= "left join sdk_language l{$alias} on (l{$alias}.module = l.module and l{$alias}.label = l.label and l{$alias}.language = ?) ";
		}
		//crmv@100548e
		$params[] = $lang_prefix;
		$selects[] = "l{$alias}.trans_label as {$lang_prefix}";
		$alias++;
	}
	$sql = "select ".implode(",",$selects)." $sql";
	$sql = "insert into sdk_language_translate (module,label,".implode(",",$languages).") $sql";
	$res=$adb->pquery($sql,$params);
	
	//rimuovo i doppioni
	$sql = "SELECT module,label,COUNT(*) FROM sdk_language_translate GROUP BY module,label HAVING COUNT(*) > 1";
	$res = $adb->query($sql);
	if ($res){
		while($row = $adb->fetchByAssoc($res,-1,false)){
			$params_search = Array($row['module'],$row['label']);
			$sql_search = "select * from sdk_language_translate where module = ? and label = ?";
			$res_search = $adb->limitpQuery($sql_search,0,1,$params_search);
			if ($res_search){
				$row_search = $adb->fetchByAssoc($res_search,-1,false);
				$sql_delete = "delete from sdk_language_translate where module = ? and label = ?";
				$res_delete = $adb->pquery($sql_delete,$params_search);
				$sql_insert = "insert into sdk_language_translate (".implode(",",array_keys($row_search)).") values (".generateQuestionMarks($row_search).")";
				$res_insert = $adb->pquery($sql_insert,$row_search);
			}
		}
	}
	//ora rimpiazzo l'indice con uno univoco
	//crmv@100548
	if($adb->isMssql()){
		$adb->datadict->ExecuteSQLArray((Array)$adb->datadict->CreateIndexSQL('idx_module_label', 'sdk_language_translate', 'module,label',Array('REPLACE','UNIQUE')));
	}
	else{
		$adb->datadict->ExecuteSQLArray((Array)$adb->datadict->CreateIndexSQL('idx_module_label', 'sdk_language_translate', 'module,label(255)',Array('REPLACE','UNIQUE')));
	}
	//crmv@100548e
	//inserisco tutti i campi di tutti i moduli
	$sql = "SELECT f.fieldname,f.fieldlabel,t.name as module
		FROM {$table_prefix}_field f
		INNER JOIN {$table_prefix}_tab t ON t.tabid = f.tabid where f.presence in (0,2)";
	$res=$adb->query($sql);
	if ($res){
		while($row = $adb->fetchByAssoc($res,-1,false)){
			$check_query="select count(*) as count from sdk_language_translate where module = ? and label = ?";
			$check_res = $adb->pquery($check_query,Array($row['module'],$row['fieldlabel']));
			if ($check_res){
				if ($adb->query_result_no_html($check_res,0,'count') == 0){
					$params_insert = Array($row['module'],$row['fieldname'],$row['fieldlabel'],1);
					$sql_insert = "insert into sdk_language_translate (module,fieldname,label,fieldtitle) values (".generateQuestionMarks($params_insert).")";
					$res_insert = $adb->pquery($sql_insert,$params_insert);
				}
				else{
					$params_update = Array($row['fieldname'],1,$row['module'],$row['fieldlabel']);
					$sql_update = "update sdk_language_translate set fieldname = ?,fieldtitle = ? where module = ? and label = ?";
					$res_update = $adb->pquery($sql_update,$params_update);
				}
			}
		}
	}
	//inserisco tutte le picklist di tutti i moduli
	$fieldtype_allowed = get_fieldtype_allowed();
	$sql = "SELECT f.fieldname,t.name as module FROM 
		{$table_prefix}_picklist p
		INNER JOIN {$table_prefix}_field f ON f.fieldname = p.name
		inner join {$table_prefix}_tab t on t.tabid = f.tabid
		inner join {$table_prefix}_ws_fieldtype e on e.uitype = f.uitype
		where e.fieldtype in (".generateQuestionMarks($fieldtype_allowed).") and f.presence in (0,2)";
	$res = $adb->pquery($sql,$fieldtype_allowed);
	if ($res){
		while ($row = $adb->fetchByAssoc($res,-1,false)){
			$sql_search = "select {$row['fieldname']} from {$table_prefix}_{$row['fieldname']}";
			$res_search = $adb->query($sql_search);
			if ($res_search){
				while($row2 = $adb->fetchByAssoc($res_search,-1,false)){
					$check_query="select count(*) as count from sdk_language_translate where module = ? and label = ?";
					$check_res = $adb->pquery($check_query,Array($row['module'],$row2[$row['fieldname']]));
					if ($check_res){
						if ($adb->query_result_no_html($check_res,0,'count') == 0){
							$params_insert = Array($row['module'],$row['fieldname'],$row2[$row['fieldname']]);
							$sql_insert = "insert into sdk_language_translate (module,fieldname,label) values (".generateQuestionMarks($params_insert).")";
							$res_insert = $adb->pquery($sql_insert,$params_insert);
						}
						else{
							$params_update = Array($row['module'],$row['fieldname'],$row2[$row['fieldname']],$row['module'],$row2[$row['fieldname']]);
							$sql_update = "update sdk_language_translate set module=?,fieldname=?,label=? where module=? and label = ?";
							$res_update = $adb->pquery($sql_update,$params_update);
							//TODO:gestione conflitti!
							//non dovrebbe MAI arrivare qui, se arriva qui ci sono 2 campi con la stessa label o ancora peggio la label di un campo corrisponde ad una label di un valore di picklist di un altro campo
						}
					}
				}
			}
		}
	}
	//crmv@147094
	//inserisco i blocchi di tutti i moduli
	$sql = "SELECT b.blocklabel, t.name as module 
		FROM {$table_prefix}_blocks b
		INNER JOIN {$table_prefix}_tab t ON t.tabid = b.tabid";
	$res = $adb->pquery($sql,$fieldtype_allowed);
	if ($res) {
		while ($row = $adb->fetchByAssoc($res,-1,false)){
			$check_query="SELECT count(*) AS count FROM sdk_language_translate WHERE module = ? AND label = ?";
			$check_res = $adb->pquery($check_query,Array($row['module'],$row['blocklabel']));
			if ($check_res){
				if ($adb->query_result_no_html($check_res,0,'count') == 0){
					$params_insert = Array($row['module'],$row['blocklabel']);
					$sql_insert = "INSERT INTO sdk_language_translate (module,label) VALUES (".generateQuestionMarks($params_insert).")";
					$res_insert = $adb->pquery($sql_insert,$params_insert);
				}
			}
		}	
	}
	//crmv@147094e
	//cancello le righe vuote
	$sql_delete = "delete from sdk_language_translate where coalesce(label,'') = ''";
	$adb->query($sql_delete);
}
?>