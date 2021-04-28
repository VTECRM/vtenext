<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('include/utils/utils.php');

global $app_strings,$current_user,$theme,$adb,$current_language,$table_prefix;

$image_path = 'themes/'.$theme.'/images/';
$language = VteSession::get('authenticated_user_language');
$pdf_strings = return_module_language($language, "PDFMaker");

$smarty=new VteSmarty();

$smarty->assign("MOD",$mod_strings);
$smarty->assign("APP",$app_strings);
$smarty->assign("PDF",$pdf_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH",$image_path);


//crmv@26396
$status_sql="SELECT ".$table_prefix."_pdfmaker.templateid FROM ".$table_prefix."_pdfmaker_userstatus  
             INNER JOIN ".$table_prefix."_pdfmaker ON ".$table_prefix."_pdfmaker.templateid = ".$table_prefix."_pdfmaker_userstatus.templateid
             WHERE userid=? AND is_active=0"; 
$status_res=$adb->pquery($status_sql,array($current_user->id));
while($status_row = $adb->fetchByAssoc($status_res))
{
  $inactive_arr[$status_row["templateid"]] = $status_row["templateid"];
}

$default_sql="SELECT ".$table_prefix."_pdfmaker.templateid FROM ".$table_prefix."_pdfmaker_userstatus  
			 INNER JOIN ".$table_prefix."_pdfmaker ON ".$table_prefix."_pdfmaker.templateid = ".$table_prefix."_pdfmaker_userstatus.templateid
             WHERE userid=? AND is_default=1"; 
//crmv@26396e
$default_res=$adb->pquery($default_sql,array($current_user->id));
while($default_row = $adb->fetchByAssoc($default_res))
{
  $default_template = $default_row["templateid"];
}

$temp_sql = "SELECT templateid, filename AS templatename
             FROM ".$table_prefix."_pdfmaker
             WHERE module = '".$_REQUEST['return_module']."'";
if(isset($inactive_arr)){
  $temp_sql.=" AND templateid NOT IN (".implode($inactive_arr,",").")";
}      
$temp_result = $adb->query($temp_sql);

//TEMPLATES BLOCK
$options="";
while($temp_row = $adb->fetchByAssoc($temp_result)){
  if(isset($default_template) AND $default_template == $temp_row['templateid']){
    $selected=' selected="selected" ';
  } else {
    $selected="";
  }
  $options.='<option value="'.$temp_row['templateid'].'"'.$selected.'>'.$temp_row['templatename'].'</option>';
    
}


$language_output="";
$generate_pdf="";
if($adb->num_rows($temp_result)>0)
{
  $template_output='
    <tr>
  		<td class="dvtCellInfo" style="width:100%;border-top:1px solid #DEDEDE;">
  			<select name="use_common_template" id="use_common_template" class="detailedViewTextBox" multiple style="width:90%;" size="5">
        '.$options.'
        </select>        
  		</td>
		</tr>
  ';    

    $temp_res = $adb->query("SELECT label, prefix FROM ".$table_prefix."_language WHERE active=1");
    while($temp_row = $adb->fetchByAssoc($temp_res)) {
      $template_languages[$temp_row["prefix"]]=$temp_row["label"];
    }

  //LANGUAGES BLOCK  
  if(count($template_languages) > 1)
  {
      $options="";
      foreach($template_languages as $prefix=>$label)
      {
        if($current_language!=$prefix)
          $options.='<option value="'.$prefix.'">'.$label.'</option>';
        else
          $options.='<option value="'.$prefix.'" selected="selected">'.$label.'</option>';
      }
      
      $language_output='
      <tr>
  		<td class="dvtCellInfo" style="width:100%;">    	
          <select name="template_language" id="template_language" class="detailedViewTextBox" style="width:90%;" size="1">
  		    '.$options.'
          </select>
  		</td>
      </tr>';
  }
  else
  {   
    foreach($template_languages as $prefix=>$label)       
      $language_output.='<input type="hidden" name="template_language" id="template_language" value="'.$prefix.'"/>';
  }
  
  //GENERATE PDF ACTION BLOCK
  //crmv@27096
  $generate_pdf='
      <tr>
    		<td class="dvtCellInfo" style="width:100%;" align="center">   		    
          <input type="button" class="crmbutton small save" value="'.$app_strings["LBL_EXPORT_TO_PDF"].'" onclick="if(VTE.PDFMakerActions.getSelectedTemplates()==\'\') alert(\''.$pdf_strings["SELECT_TEMPLATE"].'\'); else document.location.href=\'index.php?module=PDFMaker&relmodule='.$_REQUEST["return_module"].'&action=CreatePDFFromTemplate&idslist=true&commontemplateid=\'+VTE.PDFMakerActions.getSelectedTemplates()+\'&language=\'+document.getElementById(\'template_language\').value; hideFloatingDiv(\'PDFListViewDivCont\');" />            
          <input type="button" class="crmbutton small cancel" value="'.$app_strings["LBL_CANCEL_BUTTON_LABEL"].'" onclick="hideFloatingDiv(\'PDFListViewDivCont\');" />      
        </td>
  		</tr>';
  //crmv@27096e
}
else 
{
  $template_output='<tr>
                		<td class="dvtCellInfo" style="width:100%;border-top:1px solid #DEDEDE;">
                		  '.$pdf_strings["CRM_TEMPLATES_DONT_EXIST"];
  
  if(isPermitted("PDFMaker","EditView") == 'yes')
  {
    $template_output.='<br />'.$pdf_strings["CRM_TEMPLATES_ADMIN"].'
                      <a href="index.php?module=PDFMaker&action=EditPDFTemplate&return_module='.$_REQUEST["return_module"].'&parenttab=Tools" class="webMnu">'.$pdf_strings["TEMPLATE_CREATE_HERE"].'</a>'; 
  }                		            
  
  $template_output.='</td></tr>';
  
  //GENERATE PDF ACTION BLOCK IN CASE NO TEMPLATES EXIST
  $generate_pdf='
      <tr>
    		<td class="dvtCellInfo" style="width:100%;" align="center">
          <input type="button" class="crmbutton small cancel" value="'.$app_strings["LBL_CANCEL_BUTTON_LABEL"].'" onclick="hideFloatingDiv(\'PDFListViewDivCont\');" />      
        </td>
  		</tr>';                      
}

// TODO: move all the html in the tpl!
$smarty->assign('POPUP_HTML', $template_output.$language_output.$generate_pdf);

$smarty->display('modules/PDFMaker/ListViewSelect.tpl');