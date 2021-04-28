<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

function showEditPDFForm($PDFContents)
{
global $table_prefix;
global $c;
global $adb;
global $mod_strings;
global $app_strings;

$commontemplateids = trim($_REQUEST["commontemplateid"],";");  
$Templateids = explode(";",$commontemplateids);

if(isset($_REQUEST["idslist"]) && $_REQUEST["idslist"]!="")   //generating from listview 
{     
    $Records = explode(";", rtrim($_REQUEST["idslist"],";"));
}
elseif(isset($_REQUEST['record'])) 
{     
    $Records = array($_REQUEST["record"]);  
}

echo '<html>
      <head>
    	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
      <title>PDF Maker - '.$app_strings['LBL_BROWSER_TITLE'].'</title>
      <link rel="stylesheet" href="themes/'.VteSession::get("authenticated_user_theme").'/style.css">
      </head>
      <body leftmargin=0 topmargin=0 marginheight=0 marginwidth=0>';	//crmv@25443 //crmv@207841
      
      if(VteSession::hasKey("VTE_DB_VERSION") AND VteSession::get("VTE_DB_VERSION") == "5.1.0"){
        echo '<script type="text/javascript" src="include/fckeditor/fckeditor.js"></script>';
      } else {
        echo '<script type="text/javascript" src="include/ckeditor/ckeditor.js"></script>';
      }      
      //crmv@21048m
      echo "<script type=\"text/javascript\" src=\"include/js/jquery.js\"></script>
	  		<script type=\"text/javascript\" src=\"include/js/general.js\"></script>";
      //crmv@21048m e
      // crmv@171581
      echo '<form action="index.php" method="POST">
      <input type="hidden" name="__csrf_token" value="'.RequestHandler::getCSRFToken().'">
      <input type="hidden" id="action" name="action" value="CreatePDFFromTemplate">
      <input type="hidden" name="module" value="PDFMaker">
      <input type="hidden" name="commontemplateid" value="'.$_REQUEST["commontemplateid"].'">
      <input type="hidden" name="template_ids" value="'.$_REQUEST["commontemplateid"].'">
      <input type="hidden" name="idslist" value="'.implode(";",$Records).'">
      <input type="hidden" name="relmodule" value="'.$_REQUEST["relmodule"].'">
      <input type="hidden" name="language" value="'.$_REQUEST["language"].'">
      <input type="hidden" name="pmodule" value="'.$_REQUEST["relmodule"].'" />
      <input type="hidden" name="pid" value="'.$_REQUEST["record"].'">
      <input type="hidden" name="mode" value="edit">';
      //crmv@25443
      echo '<input type="hidden" name="return_action" value="CreatePDFFromTemplate">';
      //crmv@25443e
 
$templates = implode(",",$Templateids); 
$sql = "SELECT * FROM ".$table_prefix."_pdfmaker WHERE templateid IN (?)";//crmv@208173
$result = $adb->pquery($sql, array($templates));
$num_rows = $adb->num_rows($result);  

echo "<div id='editTemplate'>";
//crmv@25443
echo "<table id='emailHeader' border=0 cellspacing=0 cellpadding=0 width=100% class='mailClientWriteEmailHeader' style='position:fixed;'>
		<tr>
			<td>".$mod_strings["LBL_TEMPLATE"].":&nbsp;";
//crmv@25443e

if ($num_rows > 1)
{
    echo "<select onChange='changeTemplate(this.value);'>";
    while($row = $adb->fetchByAssoc($result))
    {
    	 if ($st == "") $st = $row['templateid'];
       echo "<option value='".$row['templateid']."'>".$row['filename']."</option>";
    }
    echo "</select>";
}   
else
{
    $st = $adb->query_result($result,0,"templateid");
    echo $adb->query_result($result,0,"filename");
}
//crmv@25443
$export_buttons = "<input type='submit' value='".$app_strings["LBL_EXPORT_TO_PDF"]."' class='crmbutton small edit'>&nbsp;&nbsp;<input type='button' value='".$mod_strings["LBL_SAVEASDOC"]."' onClick='showDocSettings();' class='crmbutton small edit'>"; 
echo '</td></tr></table>
	<div id="vte_menu_white_small"></div>
	<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" class="level3Bg" id="Buttons_List_4" style="position:fixed;z-index:19;">
	<tr>
		<td width="100%" style="padding:5px"></td>
		<td style="padding:5px" nowrap>'.$export_buttons.'</td>
	</tr>
	</table>
	<div id="vte_menu_white_1"></div>
	<script>jQuery(\'#vte_menu_white_1\').height(jQuery(\'#Buttons_List_4\').height());</script>';
//crmv@25443e
   
echo '<table class="small" width="100%" border="0" cellpadding="3" cellspacing="0" style="padding-top: 5px;"><tr>
            <td style="width: 10px;" nowrap="nowrap">&nbsp;</td>
            <td style="width: 15%;" class="dvtSelectedCell" id="body_tab" onclick="showHideTab(\'body\');" width="75" align="center" nowrap="nowrap"><b>'.$mod_strings["LBL_BODY"].'</b></td>
		        <td class="dvtUnSelectedCell" id="header_tab" onclick="showHideTab(\'header\');" align="center" nowrap="nowrap"><b>'.$mod_strings["LBL_HEADER_TAB"].'</b></td>
		        <td class="dvtUnSelectedCell" id="footer_tab" onclick="showHideTab(\'footer\');" align="center" nowrap="nowrap"><b>'.$mod_strings["LBL_FOOTER_TAB"].'</b></td>
            <td style="width: 50%;" nowrap="nowrap">&nbsp;</td> 
      </tr></table>';

echo '<div style="padding-left:5px; padding-right:5px">';

foreach ($PDFContents AS $templateid => $templatedata)
{           
  echo '<div style="display:none;" id="body_div'.$templateid.'" class="cellInfo"> 
         <textarea name="body'.$templateid.'" id="body'.$templateid.'" style="width:90%;height:500px" class=small tabindex="5">'.$templatedata["body"].'</textarea>
       </div>

       <div style="display:none;" id="header_div'.$templateid.'" class="cellInfo"> 
         <textarea name="header'.$templateid.'" id="header'.$templateid.'" style="width:90%;height:500px" class=small tabindex="5">'.$templatedata["header"].'</textarea>
       </div>
 
       <div style="display:none;" id="footer_div'.$templateid.'" class="cellInfo"> 
         <textarea name="footer'.$templateid.'" id="footer'.$templateid.'" style="width:90%;height:500px" class=small tabindex="5">'.$templatedata["footer"].'</textarea>
       </div>';

  if(VteSession::hasKey("VTE_DB_VERSION") AND VteSession::get("VTE_DB_VERSION") == "5.1.0"){
      echo '<script type="text/javascript" defer="1">
        var oFCKeditor = new FCKeditor(\'body'.$templateid.'\', "860", "510");
        oFCKeditor.BasePath="include/fckeditor/";
        oFCKeditor.Config["CustomConfigurationsPath"] = "../../../modules/PDFMaker/fck_config.js"  ;
        oFCKeditor.ToolbarSet="BodyToolbar";
        oFCKeditor.ReplaceTextarea();  
        
        var headerFCK = new FCKeditor(\'header'.$templateid.'\', "860", "510");
        headerFCK.BasePath="include/fckeditor/";
        headerFCK.Config["CustomConfigurationsPath"] = "../../../modules/PDFMaker/fck_config_fh.js"  ;
        headerFCK.ToolbarSet="HeaderToolbar";       
        headerFCK.ReplaceTextarea();
        
        var footerFCK = new FCKeditor(\'footer'.$templateid.'\', "860", "510");
        footerFCK.BasePath="include/fckeditor/";
        footerFCK.Config["CustomConfigurationsPath"] = "../../../modules/PDFMaker/fck_config_fh.js"  ;
        footerFCK.ToolbarSet="HeaderToolbar";       
        footerFCK.ReplaceTextarea();
      </script>';
  } else {
   echo '<script type="text/javascript">
          	CKEDITOR.replace( \'body'.$templateid.'\',{customConfig:\'../../modules/PDFMaker/fck_popup_config.js\'} );
            CKEDITOR.replace( \'header'.$templateid.'\',{customConfig:\'../../modules/PDFMaker/fck_popup_config.js\'} );
            CKEDITOR.replace( \'footer'.$templateid.'\',{customConfig:\'../../modules/PDFMaker/fck_popup_config.js\'} );
         </script>';
  
  }
}

echo '</div>';

//echo "<br /><center>$export_buttons</center>";	//crmv@25443
echo "</div>";

$image_path = 'themes/'.$theme.'/images/';
$language = VteSession::get('authenticated_user_language');
$mod_strings = return_module_language($language, "Documents");
$pdf_strings = return_module_language($language, "PDFMaker");

// crmv@30967
$sql="select foldername,folderid from ".$table_prefix."_crmentityfolder where tabid = ? order by foldername";
$res=$adb->pquery($sql,array(getTabId('Documents')));
// crmv@30967e

$options="";
for($i=0;$i<$adb->num_rows($res);$i++)
{
	$fid=$adb->query_result($res,$i,"folderid");
	$fldr_name=$adb->query_result($res,$i,"foldername");
  $options.='<option value="'.$fid.'">'.$fldr_name.'</option>';
}

echo '<div id="docSettings" style="display:none;">
<table border=0 cellspacing=0 cellpadding=5 width=100% class=layerHeadingULine>
<tr>
	<td width="90%" align="left" class="genHeaderSmall" id="PDFDocDivHandle" style="cursor:move;">'.$pdf_strings["LBL_SAVEASDOC"].'                 			
	</td>
</tr>
</table>
<table border=0 cellspacing=0 cellpadding=5 width=100% align=center>
    <tr><td class="small">
        <table border=0 cellspacing=0 cellpadding=5 width=100% align=center bgcolor=white>
            <tr><td colspan="2" class="detailedViewHeader" style="padding-top:5px;padding-bottom:5px;"><b>'.$app_strings["Documents"].'</b></td></tr>
            <tr>
                <td class="dvtCellLabel" width="20%" align="right"><font color="red">*</font>'.$mod_strings["Title"].'</td>
                <td class="dvtCellInfo" width="80%" align="left"><input name="notes_title" type="text" class="detailedViewTextBox"></td>
            </tr>
            <tr>
                <td class="dvtCellLabel" width="20%" align="right">'.$mod_strings["Folder Name"].'</td>
                <td class="dvtCellInfo" width="80%" align="left">
                  <select name="folderid" class="small">
                  '.$options.'
                  </select>
                </td>
            </tr>
            <tr>
                <td class="dvtCellLabel" width="20%" align="right">'.$mod_strings["Note"].'</td>
                <td class="dvtCellInfo" width="80%" align="left"><textarea name="notecontent" class="detailedViewTextBox"></textarea></td>
            </tr>
        </table>
    </td></tr>
</table>
<table border=0 cellspacing=0 cellpadding=5 width=100% class="layerPopupTransport">
<tr><td align=center class="small">
	<input type="submit" value="'.$app_strings["LBL_SAVE_BUTTON_LABEL"].'" class="crmbutton small create"/>&nbsp;&nbsp;
	<input type="button" name="'.$app_strings["LBL_CANCEL_BUTTON_LABEL"].'" value="'.$app_strings["LBL_CANCEL_BUTTON_LABEL"].'" class="crmbutton small cancel" onclick="hideDocSettings();" />
</td></tr>
</table>
</div>';

echo "</form>";
echo "<script type=\"text/javascript\" src=\"modules/PDFMaker/fck_popup_config.js\"></script>
      <script type=\"text/javascript\">
      
      document.getElementById('body_div$st').style.display='block';
      
      var selectedTab='body';
      var selectedTemplate='$st';
      
      function changeTemplate(newtemplate)
      {
          document.getElementById(selectedTab+'_div'+selectedTemplate).style.display='none';
          document.getElementById(selectedTab+'_div'+newtemplate).style.display='block';
          
          selectedTemplate = newtemplate;
      }
      
      function showDocSettings()
      {
          document.getElementById('editTemplate').style.display='none';
          document.getElementById('docSettings').style.display='block';
          document.getElementById('action').value='SavePDFDoc';
      }
      
      function hideDocSettings()
      {
          document.getElementById('editTemplate').style.display='block';
          document.getElementById('docSettings').style.display='none';
          document.getElementById('action').value='CreatePDFFromTemplate';
      }
      
      function showHideTab(tabname)
      {
          document.getElementById(selectedTab+'_tab').className='dvtUnSelectedCell';    
          document.getElementById(tabname+'_tab').className='dvtSelectedCell';
          
          document.getElementById(selectedTab+'_div'+selectedTemplate).style.display='none';
          document.getElementById(tabname+'_div'+selectedTemplate).style.display='block';

          var formerTab=selectedTab;
          selectedTab=tabname;
      }
      
		//crmv@22227
		jQuery(document).ready(function() {
			loadedPopup();
			jQuery('#emailHeader').css('z-index',findZMax());
			if (!browser_ie) {
				var addHeight = 21;
			}
			else {
				var addHeight = 0;
			}
			jQuery('#vte_menu_white_small').height(jQuery('#emailHeader').height() + addHeight);
		});
		//crmv@22227e
      </script>";
}      
?>