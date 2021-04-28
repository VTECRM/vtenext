<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('include/utils/CommonUtils.php');

global $mod_strings;
global $app_strings;
global $app_list_strings;
global $current_user,$default_charset;
global $table_prefix;
global $import_mod_strings;

$focus = 0;

global $theme;

global $import_dir;

$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";


if (!is_uploaded_file($_FILES['userfile']['tmp_name']) )
{
	show_error_import($mod_strings['LBL_IMPORT_MODULE_ERROR_NO_UPLOAD']);
	exit;
}
else if ($_FILES['userfile']['size'] > $upload_maxsize)
{
	show_error_import( $mod_strings['LBL_IMPORT_MODULE_ERROR_LARGE_FILE'] . " ". $upload_maxsize. " ". $mod_strings['LBL_IMPORT_MODULE_ERROR_LARGE_FILE_END']);
	exit;
}
if( !is_writable( $import_dir ))
{
	show_error_import($mod_strings['LBL_IMPORT_MODULE_NO_DIRECTORY'].$import_dir.$mod_strings['LBL_IMPORT_MODULE_NO_DIRECTORY_END']);
	exit;
}

require_once('modules/PDFMaker/PDFMaker.php');
$pdfmaker = new PDFMaker();

$tmp_file_name = $import_dir. "IMPORT_".$current_user->id;

move_uploaded_file($_FILES['userfile']['tmp_name'], $tmp_file_name);

$fh = fopen($tmp_file_name,"r");
$xml_content = fread($fh, filesize($tmp_file_name));
fclose($fh);
	
$type = "professional";
	
$xml = new SimpleXMLElement($xml_content);

foreach ($xml->template AS $data)
{
    //print_r($data);
    $filename = $pdfmaker->cdataDecode($data->templatename);
    $nameOfFile = $pdfmaker->cdataDecode($data->filename);
    $description = $pdfmaker->cdataDecode($data->description);
    $modulename = $pdfmaker->cdataDecode($data->module);
    $pdf_format = $pdfmaker->cdataDecode($data->settings->format);
    $pdf_orientation = $pdfmaker->cdataDecode($data->settings->orientation);
    
    $tabid = getTabId($modulename); 

    if ($type == "professional" || $tabid == "20" || $tabid == "21" || $tabid == "22" || $tabid == "23")
    {
        if ($data->settings->margins->top > 0) $margin_top = $data->settings->margins->top; else $margin_top = 0;
        if ($data->settings->margins->bottom > 0) $margin_bottom = $data->settings->margins->bottom; else $margin_bottom = 0;
        if ($data->settings->margins->left > 0) $margin_left = $data->settings->margins->left; else $margin_left = 0;
        if ($data->settings->margins->right > 0) $margin_right = $data->settings->margins->right; else $margin_right = 0;
    
        $dec_point = $pdfmaker->cdataDecode($data->settings->decimals->point);
        $dec_decimals = $pdfmaker->cdataDecode($data->settings->decimals->decimals);
        $dec_thousands = $pdfmaker->cdataDecode($data->settings->decimals->thousands);
    
        $header = $pdfmaker->cdataDecode($data->header);
        $body = $pdfmaker->cdataDecode($data->body);
        $footer = $pdfmaker->cdataDecode($data->footer);
        
        $templateid = $adb->getUniqueID($table_prefix.'_pdfmaker');
      	$sql1 = "insert into ".$table_prefix."_pdfmaker (filename,module,description,body,deleted,templateid) values (?,?,?,?,?,?)";
     	  $params1 = array($filename, $modulename, $description, $body, 0, $templateid);
    	  $adb->pquery($sql1, $params1);
    	  
    	  $sql2 = "INSERT INTO ".$table_prefix."_pdfmaker_settings (templateid, margin_top, margin_bottom, margin_left, margin_right, format, orientation, decimals, decimal_point, thousands_separator, header, footer, encoding, file_name) 
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $params2 = array($templateid, $margin_top, $margin_bottom, $margin_left, $margin_right, $pdf_format, $pdf_orientation, $dec_decimals, $dec_point, $dec_thousands, $header, $footer, "auto", $nameOfFile);
        $adb->pquery($sql2, $params2);
        
        
        $sql3 = "SELECT * FROM ".$table_prefix."_links WHERE tabid = ? AND linktype = 'LISTVIEWBASIC' AND linklabel = 'PDF Export'";
        $result3 = $adb->pquery($sql3, array($tabid));
        $num_rows3 = $adb->num_rows($result3);
        
        if ($num_rows3 == 0)
        {
            $linkid = $adb->getUniqueID($table_prefix.'_links');
            
            $sql4 = "INSERT INTO ".$table_prefix."_links (linkid, tabid, linktype, linklabel, linkurl, sequence) VALUES(?,?,?,?,?,?)";
            $adb->pquery($sql4, array($linkid, $tabid, "LISTVIEWBASIC", "PDF Export", "VTE.PDFMakerActions.getPDFListViewPopup2(this,'$"."MODULE$');",1));
        }
        
        $sql5 = "SELECT * FROM ".$table_prefix."_links WHERE tabid = ? AND linktype = 'DETAILVIEWWIDGET' AND linklabel = 'PDFMaker'";
        $result5 = $adb->pquery($sql5, array($tabid));
        $num_rows5 = $adb->num_rows($result5);
        
        if ($num_rows5 == 0)
        {
            $linkid = $adb->getUniqueID($table_prefix.'_links');
            
            $sql6 = "INSERT INTO ".$table_prefix."_links (linkid, tabid, linktype, linklabel, linkurl, sequence) VALUES(?,?,?,?,?,?)";
            $adb->pquery($sql6, array($linkid, $tabid, "DETAILVIEWWIDGET", "PDFMaker", "module=PDFMaker&action=PDFMakerAjax&file=getPDFActions&record=$"."RECORD$",1));
        }  
    }
}	

header("Location:index.php?module=PDFMaker&action=ListPDFTemplates&parenttab=Tools");
exit;
?>