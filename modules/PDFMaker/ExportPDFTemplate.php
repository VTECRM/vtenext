<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
// ITS4YOU TT0093 VlMe N

require_once('data/Tracker.php');
require_once('include/utils/UserInfoUtil.php');
require_once('include/database/PearDatabase.php');
global $adb;
global $log;
global $mod_strings;
global $app_strings;
global $current_language;
global $theme;
global $table_prefix;

$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$Templates = explode(";",$_REQUEST['templates']);

sort($Templates);
$c = '';

if(count($Templates) > 0)
{
  	foreach ($Templates AS $templateid)
  	{
      	$sql = "SELECT ".$table_prefix."_pdfmaker.*, ".$table_prefix."_pdfmaker_settings.*
                  FROM ".$table_prefix."_pdfmaker 
                  LEFT JOIN ".$table_prefix."_pdfmaker_settings
                    ON ".$table_prefix."_pdfmaker_settings.templateid = ".$table_prefix."_pdfmaker.templateid
                 WHERE ".$table_prefix."_pdfmaker.templateid=?";
            
      	$result = $adb->pquery($sql, array($templateid));
      	$num_rows = $adb->num_rows($result); 
      	
      	if ($num_rows > 0)
      	{
          	$pdftemplateResult = $adb->fetch_array($result);
        
            $Margins = array("top" => $pdftemplateResult["margin_top"], 
                             "bottom" => $pdftemplateResult["margin_bottom"], 
                             "left" => $pdftemplateResult["margin_left"], 
                             "right" => $pdftemplateResult["margin_right"]);
        
            $Decimals = array("point"=>$pdftemplateResult["decimal_point"],
                              "decimals"=>$pdftemplateResult["decimals"],
                              "thousands"=>$pdftemplateResult["thousands_separator"]);
                      
            $templatename = $pdftemplateResult["filename"];
            $nameOfFile = $pdftemplateResult["file_name"]; 
            $description = $pdftemplateResult["description"];
            $module = $pdftemplateResult["module"];
            
            $body = decode_html($pdftemplateResult["body"]);
            $header = decode_html($pdftemplateResult["header"]);
            $footer = decode_html($pdftemplateResult["footer"]);
            
            $format = $pdftemplateResult["format"];
            $orientation = $pdftemplateResult["orientation"];
            
            $c .= "<template>";
               $c .= "<type>PDFMaker</type>";
               $c .= "<templatename>".cdataEncode($templatename)."</templatename>";
               $c .= "<filename>".cdataEncode($nameOfFile)."</filename>";
               $c .= "<description>".cdataEncode($description)."</description>";
               $c .= "<module>".cdataEncode($module)."</module>";
               $c .= "<settings>";
                   $c .= "<format>".cdataEncode($format)."</format>";
                   $c .= "<orientation>".cdataEncode($orientation)."</orientation>";
                   $c .= "<margins>";
                       $c .= "<top>".cdataEncode($Margins["top"])."</top>";
                       $c .= "<bottom>".cdataEncode($Margins["bottom"])."</bottom>";
                       $c .= "<left>".cdataEncode($Margins["left"])."</left>";
                       $c .= "<right>".cdataEncode($Margins["right"])."</right>";
                   $c .= "</margins>";
                   $c .= "<decimals>";
                       $c .= "<point>".cdataEncode($Decimals["point"])."</point>";
                       $c .= "<decimals>".cdataEncode($Decimals["decimals"])."</decimals>";
                       $c .= "<thousands>".cdataEncode($Decimals["thousands"])."</thousands>";
                   $c .= "</decimals>";
               $c .= "</settings>";
               
               $c .= "<header>";
                  $c .= cdataEncode($header,true);
               $c .= "</header>";
               
               $c .= "<body>";
                  $c .= cdataEncode($body,true);
               $c .= "</body>";
               
               $c .= "<footer>";
                  $c .= cdataEncode($footer,true);
               $c .= "</footer>";
               
            $c .= "</template>";

            
        }
    }

}

header('Content-Type: application/xhtml+xml');
header("Content-Disposition: attachment; filename=export.xml");

echo "<?xml version='1.0'?".">";
echo "<export>";
echo $c;
echo "</export>";

exit;

function cdataEncode($text, $encode = false)
{
    $From = array("<![CDATA[", "]]>");
    $To   = array("<|!|[%|CDATA|[%|", "|%]|]|>");
    
    if ($text != "")
    {
        $pos1 = strpos("<![CDATA[", $text);
        $pos2 = strpos("]]>", $text);
        
        if ($pos1 === false && $pos2 === false && $encode == false)
        {
            $content = $text;
        }
        else
        {
            $encode_text = str_replace($From, $To, $text);
        
            $content = "<![CDATA[".$encode_text."]]>";
        }
    }
    else
    {
        $content = "";
    }
    
    return $content;
}

?>