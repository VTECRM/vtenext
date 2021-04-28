<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('include/utils/utils.php');

global $app_strings;
global $mod_strings;
global $app_list_strings;
global $adb;
global $upload_maxsize;
global $theme,$default_charset;
global $current_language, $default_language, $current_user;
global $table_prefix;  
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$smarty = new VteSmarty();
  
if(isset($_REQUEST['templateid']) && $_REQUEST['templateid']!='')
{
  	$templateid = $_REQUEST['templateid'];
  	
  	$sql = "SELECT ".$table_prefix."_pdfmaker.*, ".$table_prefix."_pdfmaker_settings.*
              FROM ".$table_prefix."_pdfmaker 
              LEFT JOIN ".$table_prefix."_pdfmaker_settings
                ON ".$table_prefix."_pdfmaker_settings.templateid = ".$table_prefix."_pdfmaker.templateid
             WHERE ".$table_prefix."_pdfmaker.templateid=?";
             
  	$result = $adb->pquery($sql, array($templateid));
  	$pdftemplateResult = $adb->fetch_array($result);

  	$select_module = $pdftemplateResult["module"];
  	// $pdf_language = $pdftemplateResult["pdf_language"];
  	$select_format = $pdftemplateResult["format"];
  	$select_orientation = $pdftemplateResult["orientation"];
  	//$select_encoding = $pdftemplateResult["encoding"];
    $nameOfFile = $pdftemplateResult["file_name"];
        
    $sql="SELECT is_active, is_default FROM ".$table_prefix."_pdfmaker_userstatus WHERE templateid=? AND userid=?";
    $result=$adb->pquery($sql,array($templateid,$current_user->id));
    if($adb->num_rows($result)>0)
    {
      $statusrow = $adb->fetch_array($result);
      $is_active = $statusrow["is_active"];
      $is_default = $statusrow["is_default"];
    }
    else
    {
      $is_active = "1";
      $is_default = "0";  
    }     
}
else
{
    $templateid = "";
    
    if (isset($_REQUEST["return_module"]) && $_REQUEST["return_module"] != "") 
       $select_module = $_REQUEST["return_module"]; 
    else 
       $select_module = "";
       
    $pdf_language = $current_language;
    $select_format = "A4";
    $select_orientation = "portrait";
    //$select_encoding = "utf-8";
    $nameOfFile = "";
    $is_active = "1";
    $is_default = "0";
}

$type = "professional";

$smarty->assign("TYPE",$type);

if(isset($_REQUEST["isDuplicate"]) && $_REQUEST["isDuplicate"]=="true")
{
  $smarty->assign("FILENAME", "");
  $smarty->assign("DUPLICATE_FILENAME", $pdftemplateResult["filename"]);
}
else
  $smarty->assign("FILENAME", $pdftemplateResult["filename"]);
  
$smarty->assign("DESCRIPTION", $pdftemplateResult["description"]);

if (!isset($_REQUEST["isDuplicate"]) OR (isset($_REQUEST["isDuplicate"]) && $_REQUEST["isDuplicate"] != "true")) $smarty->assign("SAVETEMPLATEID", $templateid);
if ($templateid!="")
  $smarty->assign("EMODE", "edit");  

$smarty->assign("TEMPLATEID", $templateid);
$smarty->assign("MODULENAME", getTranslatedString($select_module,$select_module));	//crmv@25443
$smarty->assign("SELECTMODULE", $select_module);

$smarty->assign("BODY", $pdftemplateResult["body"]);
  
$smarty->assign("MOD",$mod_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH",$image_path);
$smarty->assign("APP", $app_strings);
$smarty->assign("PARENTTAB", getParentTab());


$Modulenames = Array(''=>$mod_strings["LBL_PLS_SELECT"]);
$sql = "SELECT tabid, name FROM ".$table_prefix."_tab WHERE isentitytype=1 AND presence=0 AND tabid NOT IN (10, 9, 16, 28)";
//TODO: aggiungere in vte_hide_tab anche la voce hide_pdf
$ChartsInstance = Vtecrm_Module::getInstance('Charts');
$sql .= " AND tabid NOT IN (SELECT tabid FROM vte_hide_tab WHERE hide_report = 1) AND tabid <> ".$ChartsInstance->id;
//END
$sql .= " ORDER BY name ASC";
$result = $adb->query($sql);
while($row = $adb->fetchByAssoc($result)){
  if(file_exists("modules/".$row['name'])){
    if ($row['tabid'] == "20" || $row['tabid'] == "21" || $row['tabid'] == "22" || $row['tabid'] == "23" || $type == "professional")
		$Modulenames[$row['name']] = getTranslatedString($row['name'],$row['name']);	//crmv@25443
    $ModuleIDS[$row['name']] = $row['tabid'];
  }
} 
 
$smarty->assign("MODULENAMES",$Modulenames);

$sql="SELECT * FROM ".$table_prefix."_organizationdetails";
$result = $adb->pquery($sql, array());

$organization_logoname = decode_html($adb->query_result($result,0,'logoname'));
$organization_header = decode_html($adb->query_result($result,0,'headername'));
$organization_stamp_signature = $adb->query_result($result,0,'stamp_signature');

global $site_URL, $db_prefix_name;	
$path = $site_URL."/test/".$db_prefix_name."/logo/";

if (isset($organization_logoname))
{
	$organization_logo_img = "<img src=\"".$path.$organization_logoname."\">";
  $smarty->assign("COMPANYLOGO",$organization_logo_img);
}
if (isset($organization_stamp_signature))
{
  $organization_stamp_signature_img = "<img src=\"".$path.$organization_stamp_signature."\">";
	$smarty->assign("COMPANY_STAMP_SIGNATURE",$organization_stamp_signature_img);
}	
if (isset($organization_header))
{
	$organization_header_img = "<img src=\"".$path.$organization_header."\">";
  $smarty->assign("COMPANY_HEADER_SIGNATURE",$organization_header_img);
}

$Acc_Info = array(''=>$mod_strings["LBL_PLS_SELECT"],
                  "COMPANY_NAME"=>$mod_strings["LBL_COMPANY_NAME"],
                  "COMPANY_LOGO"=>$mod_strings["LBL_COMPANY_LOGO"],
                  "COMPANY_ADDRESS"=>$mod_strings["LBL_COMPANY_ADDRESS"],
                  "COMPANY_CITY"=>$mod_strings["LBL_COMPANY_CITY"],
                  "COMPANY_STATE"=>$mod_strings["LBL_COMPANY_STATE"],
                  "COMPANY_ZIP"=>$mod_strings["LBL_COMPANY_ZIP"],
                  "COMPANY_COUNTRY"=>$mod_strings["LBL_COMPANY_COUNTRY"],
                  "COMPANY_PHONE"=>$mod_strings["LBL_COMPANY_PHONE"],
                  "COMPANY_FAX"=>$mod_strings["LBL_COMPANY_FAX"],
                  "COMPANY_WEBSITE"=>$mod_strings["LBL_COMPANY_WEBSITE"],
				  //crmv@25443
				'COMPANY_BANKING'=>getTranslatedString('LBL_ORGANIZATION_BANKING','Settings'),
				'COMPANY_VAT_REGISTRATION_NUMBER'=>getTranslatedString('LBL_ORGANIZATION_VAT','Settings'),
				'COMPANY_REA'=>getTranslatedString('LBL_ORGANIZATION_REA','Settings'),
				'COMPANY_ISSUED_CAPITAL'=>getTranslatedString('LBL_ORGANIZATION_CAPITAL','Settings'),
				  //crmv@25443e
                 );
                 
$smarty->assign("ACCOUNTINFORMATIONS",$Acc_Info);

if(file_exists("modules/Users/language/$default_language.lang.php")){ 
  $user_mod_strings = return_specified_module_language($current_language, "Users");
} else {
  $user_mod_strings = return_specified_module_language("en_us", "Users");
}

$User_Info = array("USER_USERNAME"=>$user_mod_strings["User Name"],
                   "USER_FIRSTNAME"=>$user_mod_strings["First Name"],
                   "USER_LASTNAME"=>$user_mod_strings["Last Name"],
                   "USER_EMAIL"=>$user_mod_strings["Email"],
                   
                   "USER_TITLE"=>$user_mod_strings["Title"],
                   "USER_FAX"=>$user_mod_strings["Fax"],
                   "USER_DEPARTMENT"=>$user_mod_strings["Department"],
                   "USER_OTHER_EMAIL"=>$user_mod_strings["Other Email"],
                   "USER_PHONE"=>$user_mod_strings["Office Phone"],
                   "USER_YAHOOID"=>$user_mod_strings["Yahoo id"],
                   "USER_MOBILE"=>$user_mod_strings["Mobile"], 
                   "USER_HOME_PHONE"=>$user_mod_strings["Home Phone"], 
                   "USER_OTHER_PHONE"=>$user_mod_strings["Other Phone"],
                   "USER_NOTES"=>$user_mod_strings["Documents"],      
      
                   "USER_ADDRESS"=>$user_mod_strings["Street Address"],
                   "USER_CITY"=>$user_mod_strings["City"],
                   "USER_STATE"=>$user_mod_strings["State"],
                   "USER_ZIP"=>$user_mod_strings["Postal Code"],
                   "USER_COUNTRY"=>$user_mod_strings["Country"]
                 );                             

$smarty->assign("USERINFORMATIONS",$User_Info);
    
$Logged_User_Info = array("L_USER_USERNAME"=>$user_mod_strings["User Name"],
                   "L_USER_FIRSTNAME"=>$user_mod_strings["First Name"],
                   "L_USER_LASTNAME"=>$user_mod_strings["Last Name"],
                   "L_USER_EMAIL"=>$user_mod_strings["Email"],
                   
                   "L_USER_TITLE"=>$user_mod_strings["Title"],
                   "L_USER_FAX"=>$user_mod_strings["Fax"],
                   "L_USER_DEPARTMENT"=>$user_mod_strings["Department"],
                   "L_USER_OTHER_EMAIL"=>$user_mod_strings["Other Email"],
                   "L_USER_PHONE"=>$user_mod_strings["Office Phone"],
                   "L_USER_YAHOOID"=>$user_mod_strings["Yahoo id"],
                   "L_USER_MOBILE"=>$user_mod_strings["Mobile"], 
                   "L_USER_HOME_PHONE"=>$user_mod_strings["Home Phone"], 
                   "L_USER_OTHER_PHONE"=>$user_mod_strings["Other Phone"],
                   "L_USER_NOTES"=>$user_mod_strings["Documents"],      
      
                   "L_USER_ADDRESS"=>$user_mod_strings["Street Address"],
                   "L_USER_CITY"=>$user_mod_strings["City"],
                   "L_USER_STATE"=>$user_mod_strings["State"],
                   "L_USER_ZIP"=>$user_mod_strings["Postal Code"],
                   "L_USER_COUNTRY"=>$user_mod_strings["Country"]
                );

$smarty->assign("LOGGEDUSERINFORMATION",$Logged_User_Info);


$Invterandcon = array(""=>$mod_strings["LBL_PLS_SELECT"],
                      "TERMS_AND_CONDITIONS"=>$mod_strings["LBL_TERMS_AND_CONDITIONS"]);

$smarty->assign("INVENTORYTERMSANDCONDITIONS",$Invterandcon); 

//labels
$global_lang_labels = @array_flip($app_strings);
$global_lang_labels = array_flip($global_lang_labels);
asort($global_lang_labels);
$smarty->assign("GLOBAL_LANG_LABELS",$global_lang_labels);
    
$module_lang_labels=array();
if($select_module!="")
{ 
  if(file_exists("modules/$select_module/language/$default_language.lang.php"))  //kontrola na $default_language pretoze vo funkcii return_specified_module_language sa kontroluje $current_language a ak neexistuje tak sa pouzije $default_language  
    $mod_lang=return_specified_module_language($current_language,$select_module);    
  else 
    $mod_lang=return_specified_module_language("en_us",$select_module); 
  
  $module_lang_labels = array_flip($mod_lang);
  $module_lang_labels = array_flip($module_lang_labels);
  asort($module_lang_labels);  
}
else
  $module_lang_labels[""]=$mod_strings["LBL_SELECT_MODULE_FIELD"];

$smarty->assign("MODULE_LANG_LABELS",$module_lang_labels);

$Header_Footer_Strings = array(""=>$mod_strings["LBL_PLS_SELECT"],
                               "PAGE"=>$app_strings["Page"],
                               "PAGES"=>$app_strings["Pages"],
                              );

$smarty->assign("HEADER_FOOTER_STRINGS",$Header_Footer_Strings);

$Article_Strings = array(""=>$mod_strings["LBL_PLS_SELECT"],
                         "PRODUCTBLOC_START"=>$mod_strings["LBL_ARTICLE_START"],
                         "PRODUCTBLOC_END"=>$mod_strings["LBL_ARTICLE_END"]
                        );

$smarty->assign("ARTICLE_STRINGS",$Article_Strings);
       

//PDF FORMAT SETTINGS

$Formats = array("A3"=>"A3",
                 "A4"=>"A4",
                 "A5"=>"A5",
                 "A6"=>"A6",
                 "Letter"=>"Letter",
                 "Legal"=>"Legal");

$smarty->assign("FORMATS",$Formats);

$smarty->assign("SELECT_FORMAT",$select_format);


//PDF ORIENTATION SETTINGS

$Orientations = array("portrait"=>$mod_strings["portrait"],
                      "landscape"=>$mod_strings["landscape"]);

$smarty->assign("ORIENTATIONS",$Orientations);

$smarty->assign("SELECT_ORIENTATION",$select_orientation);

//PDF ENCODING SETTINGS
/*
$Encodings = array("auto"=>"Auto",
                   "utf-8"=>"utf-8",
                   "win-1252"=>"win-1252",                   
                   "win-1251"=>"win-1251",
                   "iso-8859-2"=>"iso-8859-2",
                   "iso-8859-4"=>"iso-8859-4",
                   "iso-8859-7"=>"iso-8859-7",
                   "iso-8859-9"=>"iso-8859-9",
                   "SJIS"=>"Japanese",
                   "GBK"=>"Chinese - Simplified",
                   "BIG5"=>"Chinese - Traditional",
                   "UHC"=>"Korean");

$smarty->assign("ENCODINGS",$Encodings);
$smarty->assign("SELECT_ENCODING",$select_encoding);
*/

//PDF STATUS SETTINGS
$Status = array("1"=>$app_strings["Active"],
                "0"=>$app_strings["Inactive"]);
$smarty->assign("STATUS",$Status);
$smarty->assign("IS_ACTIVE",$is_active);
if($is_active=="0")
  $smarty->assign("IS_DEFAULT_CHECKED",'disabled="disabled"');  
elseif($is_default=="1")
  $smarty->assign("IS_DEFAULT_CHECKED",'checked="checked"');                

//PDF MARGIN SETTINGS
 
if(isset($_REQUEST['templateid']) && $_REQUEST['templateid']!='')
{
    $Margins = array("top" => $pdftemplateResult["margin_top"], 
                     "bottom" => $pdftemplateResult["margin_bottom"], 
                     "left" => $pdftemplateResult["margin_left"], 
                     "right" => $pdftemplateResult["margin_right"]);
                    
    $Decimals = array("point"=>$pdftemplateResult["decimal_point"],
                      "decimals"=>$pdftemplateResult["decimals"],
                      "thousands"=>($pdftemplateResult["thousands_separator"]!="sp" ? $pdftemplateResult["thousands_separator"] : " ")    
                      );
    
    $compliance = $pdftemplateResult['compliance']; // crmv@172422
}
else
{
    $Margins = array("top"=>"2", "bottom" => "2", "left" => "2", "right" => "2");
    // crmv@83877
    $Decimals = array(
		"point" => (isset($current_user->column_fields['decimal_separator']) ? $current_user->column_fields['decimal_separator'] : ","),
		"decimals" => (isset($current_user->column_fields['decimals_num']) ? $current_user->column_fields['decimals_num'] : "2"),
		"thousands" => (isset($current_user->column_fields['thousands_separator']) ? $current_user->column_fields['thousands_separator'] : " ")
	);
	// crmv@83877e
	
    $compliance = ''; // crmv@172422
}
$smarty->assign("MARGINS",$Margins);
$smarty->assign("DECIMALS",$Decimals);

$smarty->assign("COMPLIANCE", $compliance); // crmv@172422

//PDF HEADER / FOOTER
$header="";
$footer="";
if(isset($_REQUEST['templateid']) && $_REQUEST['templateid']!="") {
  $header=$pdftemplateResult["header"];
  $footer=$pdftemplateResult["footer"];
}
$smarty->assign("HEADER",$header);
$smarty->assign("FOOTER",$footer);

$hfVariables = array("##PAGE##"=>$mod_strings["LBL_CURRENT_PAGE"],
                     "##PAGES##"=>$mod_strings["LBL_ALL_PAGES"],
                     "##PAGE##/##PAGES##"=>$mod_strings["LBL_PAGE_PAGES"]);
                     //"##FILENAME##"=>$mod_strings["LBL_FILENAME"],
                     //"##FILESIZE##"=>$mod_strings["LBL_FILESIZE"]);
                     
$smarty->assign("HEAD_FOOT_VARS",$hfVariables);

$dateVariables = array("##DD.MM.YYYY##"=>$mod_strings["LBL_DATE_DD.MM.YYYY"],
                       "##DD-MM-YYYY##"=>$mod_strings["LBL_DATE_DD-MM-YYYY"],
                       "##MM-DD-YYYY##"=>$mod_strings["LBL_DATE_MM-DD-YYYY"],
                       "##YYYY-MM-DD##"=>$mod_strings["LBL_DATE_YYYY-MM-DD"]);
                       
$smarty->assign("DATE_VARS",$dateVariables);
                       
//PDF FILENAME FIELDS
$filenameFields = array("#TEMPLATE_NAME#"=>$mod_strings["LBL_PDF_NAME"],                        
                        "#DD-MM-YYYY#"=>$mod_strings["LBL_CURDATE_DD-MM-YYYY"],
                        "#MM-DD-YYYY#"=>$mod_strings["LBL_CURDATE_MM-DD-YYYY"],
                        "#YYYY-MM-DD#"=>$mod_strings["LBL_CURDATE_YYYY-MM-DD"]                        
                        );
$smarty->assign("FILENAME_FIELDS",$filenameFields);
$smarty->assign("NAME_OF_FILE",$nameOfFile);
 
//Ignored picklist values
$pvsql="SELECT value FROM ".$table_prefix."_pdfmaker_ignpickvals";	//crmv@19166
$pvresult = $adb->query($pvsql);
$pvvalues="";
while($pvrow=$adb->fetchByAssoc($pvresult))
  $pvvalues.=$pvrow["value"].", ";
$smarty->assign("IGNORE_PICKLIST_VALUES",rtrim($pvvalues, ", "));

$Product_Fields = array("PS_CRMID"=>$mod_strings["LBL_RECORD_ID"],
						"PS_NO"=>$mod_strings["LBL_PS_NO"],
                        "PRODUCTPOSITION"=>$mod_strings["LBL_PRODUCT_POSITION"],
                        "CURRENCYNAME"=>$mod_strings["LBL_CURRENCY_NAME"],
                        "CURRENCYCODE"=>$mod_strings["LBL_CURRENCY_CODE"],
                        "CURRENCYSYMBOL"=>$mod_strings["LBL_CURRENCY_SYMBOL"],
                        "PRODUCTNAME"=>$mod_strings["LBL_VARIABLE_PRODUCTNAME"],
                        "PRODUCTTITLE"=>$mod_strings["LBL_VARIABLE_PRODUCTTITLE"],
                        "PRODUCTDESCRIPTION"=>$mod_strings["LBL_VARIABLE_PRODUCTDESCRIPTION"],
                        "PRODUCTEDITDESCRIPTION"=>$mod_strings["LBL_VARIABLE_PRODUCTEDITDESCRIPTION"]);
						
$Product_Fields["PRODUCTINVDESCRIPTION"]=$mod_strings["LBL_VARIABLE_PRODUCTINVDESCRIPTION"];	//crmv@23798

if($adb->num_rows($adb->query("SELECT tabid FROM ".$table_prefix."_tab WHERE name='Pdfsettings'"))>0)
  $Product_Fields["CRMNOWPRODUCTDESCRIPTION"]=$mod_strings["LBL_CRMNOW_DESCRIPTION"];
                                                                                   
$Product_Fields["PRODUCTQUANTITY"]=$mod_strings["LBL_VARIABLE_QUANTITY"];
$Product_Fields["PRODUCTUSAGEUNIT"]=$mod_strings["LBL_VARIABLE_USAGEUNIT"];
$Product_Fields["PRODUCTLISTPRICE"]=$mod_strings["LBL_VARIABLE_LISTPRICE"];
$Product_Fields["PRODUCTTOTAL"]=$mod_strings["LBL_PRODUCT_TOTAL"];
$Product_Fields["PRODUCTDISCOUNT"]=$mod_strings["LBL_VARIABLE_DISCOUNT"];
$Product_Fields["PRODUCTDISCOUNTPERCENT"]=$mod_strings["LBL_VARIABLE_DISCOUNT_PERCENT"];
$Product_Fields["PRODUCTSTOTALAFTERDISCOUNT"]=$mod_strings["LBL_VARIABLE_PRODUCTTOTALAFTERDISCOUNT"];
$Product_Fields["PRODUCTVATPERCENT"]=$mod_strings["LBL_PROCUCT_VAT_PERCENT"];
$Product_Fields["PRODUCTVATSUM"]=$mod_strings["LBL_PRODUCT_VAT_SUM"];
$Product_Fields["PRODUCTTOTALSUM"]=$mod_strings["LBL_PRODUCT_TOTAL_VAT"];
                        
$smarty->assign("SELECT_PRODUCT_FIELD",$Product_Fields);

$More_Fields = array("SUBTOTAL"=>$mod_strings["LBL_VARIABLE_SUM"],	//crmv@25443
                     "CURRENCYNAME"=>$mod_strings["LBL_CURRENCY_NAME"],
                     "CURRENCYSYMBOL"=>$mod_strings["LBL_CURRENCY_SYMBOL"],
                     "CURRENCYCODE"=>$mod_strings["LBL_CURRENCY_CODE"],
                     "TOTALWITHOUTVAT"=>$mod_strings["LBL_VARIABLE_SUMWITHOUTVAT"],
                     "TOTALDISCOUNT"=>$mod_strings["LBL_VARIABLE_TOTALDISCOUNT"],
                     "TOTALDISCOUNTPERCENT"=>$mod_strings["LBL_VARIABLE_TOTALDISCOUNT_PERCENT"],
                     "TOTALAFTERDISCOUNT"=>$mod_strings["LBL_VARIABLE_TOTALAFTERDISCOUNT"],
                     "VAT"=>$mod_strings["LBL_VARIABLE_VAT"],
                     "VATPERCENT"=>$mod_strings["LBL_VARIABLE_VAT_PERCENT"],
                     "VATBLOCK"=>$mod_strings["LBL_VARIABLE_VAT_BLOCK"],
                     "TOTALWITHVAT"=>$mod_strings["LBL_VARIABLE_SUMWITHVAT"],
                     "SHTAXTOTAL"=>$mod_strings["LBL_SHTAXTOTAL"],
                     "SHTAXAMOUNT"=>$mod_strings["LBL_SHTAXAMOUNT"],
                     "ADJUSTMENT"=>$mod_strings["LBL_ADJUSTMENT"],
                     "TOTAL"=>$mod_strings["LBL_VARIABLE_TOTALSUM"]                     
                     );
     
if (isInventoryModule($select_module))	//crmv@48404
   $display_product_div = "block";
else
   $display_product_div = "none";

$smarty->assign("DISPLAY_PRODUCT_DIV",$display_product_div);
                               
foreach ($ModuleIDS as $module => $IDS) {
	$sql1 = "SELECT blockid, blocklabel FROM ".$table_prefix."_blocks WHERE tabid=".$IDS." ORDER BY sequence ASC";
	$res1 = $adb->query($sql1);
  $block_info_arr = array();
  while($row = $adb->fetch_array($res1))
  {
 	    $sql2 = "SELECT fieldid, uitype FROM ".$table_prefix."_field WHERE block=".$row['blockid']." and (displaytype != 3 OR uitype = 55) ORDER BY sequence ASC";
 	    $res2 = $adb->query($sql2);
 	    $num_rows2 = $adb->num_rows($res2);  
 	    
 	    if ($num_rows2 > 0)
 	    {
    	    $field_id_array = array();
          
          while($row2 = $adb->fetch_array($res2))
          {
             $field_id_array[] = $row2['fieldid'];
             
             switch ($row2['uitype'])
             {
                 // crmv@182167 - removed old uitypes
                 case "10": $fmrs=$adb->query('select relmodule from '.$table_prefix.'_fieldmodulerel where fieldid='.$row2['fieldid']);
                            while ($rm=$adb->fetch_array($fmrs)) { 
                              $All_Related_Modules[$module][] = $rm['relmodule'];
                            }                  
                 break;
             }
          }
          
          $block_info_arr[$row['blocklabel']] = $field_id_array;
      }
  }
  
  if (isInventoryModule($module))	//crmv@48404
      $block_info_arr["LBL_DETAILS_BLOCK"] = array();
  
  $ModuleFields[$module] = $block_info_arr;
}
 
// ITS4YOU-CR VlZa
//Oprava prazdneho selectboxu v pripade ze zvoleny modul nemal ziadne related moduly
foreach($Modulenames as $key=>$value)
{
  if(!isset($All_Related_Modules[$key]))
    $All_Related_Modules[$key]=array();
}
// ITS4YOU-END
$smarty->assign("ALL_RELATED_MODULES",$All_Related_Modules);
 
if ($select_module != "")
{			                  
    foreach ($All_Related_Modules[$select_module] AS $rel_module)
    {		                  
    	 $Related_Modules[$rel_module] = getTranslatedString($rel_module,$rel_module);	//crmv@25443
    }
}

$smarty->assign("RELATED_MODULES",$Related_Modules);

                        
foreach ($ModuleFields AS $module => $Blocks)
{
    $Optgroupts = array();
    
    if(file_exists("modules/$module/language/$default_language.lang.php"))  //kontrola na $default_language pretoze vo funkcii return_specified_module_language sa kontroluje $current_language a ak neexistuje tak sa pouzije $default_language  
      $current_mod_strings = return_specified_module_language($current_language, $module);    
    else 
      $current_mod_strings = return_specified_module_language("en_us", $module);
    
		$b = 0;
		foreach ($Blocks AS $block_label => $block_fields)
		{
        $b++;
        
        $Options = array();
        

        if (isset($current_mod_strings[$block_label]) AND $current_mod_strings[$block_label] != "")
             $optgroup_value = $current_mod_strings[$block_label];
        elseif (isset($app_strings[$block_label]) AND $app_strings[$block_label] != "")
             $optgroup_value = $app_strings[$block_label];  
        elseif(isset($mod_strings[$block_label]) AND $mod_strings[$block_label]!="")
             $optgroup_value = $mod_strings[$block_label];
        else
             $optgroup_value = $block_label;  
            
        $Optgroupts[] = '"'.$optgroup_value.'","'.$b.'"';
        
        if (count($block_fields) > 0)
        {
             $field_ids = implode(",",$block_fields);
            
             $sql1 = "SELECT * FROM ".$table_prefix."_field WHERE fieldid IN (".$field_ids.")";
             $result1 = $adb->query($sql1); 
            
             while($row1 = $adb->fetchByAssoc($result1))
             {
            	   $fieldname = $row1['fieldname'];
            	   $fieldlabel = $row1['fieldlabel'];
            	   
         	       $option_key = strtoupper($module."_".$fieldname);
            	   
                 if (isset($current_mod_strings[$fieldlabel]) AND $current_mod_strings[$fieldlabel] != "")
                     $option_value = $current_mod_strings[$fieldlabel];
                 elseif (isset($app_strings[$fieldlabel]) AND $app_strings[$fieldlabel] != "")
                     $option_value = $app_strings[$fieldlabel];  
                 else
                     $option_value = $fieldlabel;  
                     
            	   $Options[] = '"'.$option_value.'","'.$option_key.'"';
            	   $SelectModuleFields[$module][$optgroup_value][$option_key] = $option_value;
             }
             
			// crmv@198024 - add variant fields
			if ($module == 'Products' && $block_label == 'LBL_VARIANT_INFORMATION' && vtlib_isModuleActive('ConfProducts')) {
				$confProd = CRMEntity::getInstance('ConfProducts');
				$struct = $confProd->getAllAttributes();
				foreach ($struct as $prodattr) {
					$fieldname = $prodattr['fieldname'];
					$option_key = strtoupper($module."_".$fieldname);
					$option_value = $prodattr['productname'].': '.$prodattr['fieldlabel'];
					$Options[] = '"'.$option_value.'","'.$option_key.'"';
					$SelectModuleFields[$module][$optgroup_value][$option_key] = $option_value;
				}
			}
			// crmv@198024e
        }
        
        //variable RECORD ID added
        if($b==1)
        {
          $option_value = "Record ID";
          $option_key = strtoupper($module."_CRMID");
          $Options[] = '"'.$option_value.'","'.$option_key.'"';
          $SelectModuleFields[$module][$optgroup_value][$option_key] = $option_value;
        }        
       //end
        
        $Convert_RelatedModuleFields[$module."|".$b] = implode(",",$Options);
        
        
        if ($block_label == "LBL_DETAILS_BLOCK" && isInventoryModule($module))	//crmv@48404
        {
             foreach ($More_Fields AS $variable => $variable_name)
             {
                 $variable_key = strtoupper($variable);
                 
                 $Options[] = '"'.$variable_name.'","'.$variable_key.'"';
                 $SelectModuleFields[$module][$optgroup_value][$variable_key] = $variable_name;
             }
        }
        $Convert_ModuleFields[$module."|".$b] = implode(",",$Options);
    }
    
    $Convert_ModuleBlocks[$module] = implode(",",$Optgroupts);
    
}

if(isset($ModuleSorces))
  $smarty->assign("MODULESORCES",$ModuleSorces);
  
$smarty->assign("MODULE_BLOCKS",$Convert_ModuleBlocks);

$smarty->assign("RELATED_MODULE_FIELDS",$Convert_RelatedModuleFields);

$smarty->assign("MODULE_FIELDS",$Convert_ModuleFields);

// ITS4YOU-CR VlZa
// Product bloc templates
$sql="SELECT * FROM ".$table_prefix."_pdfmaker_prodbloc_tpl";	//crmv@19166
$result=$adb->query($sql);
$Productbloc_tpl[""]=$mod_strings["LBL_PLS_SELECT"];
while($row=$adb->fetchByAssoc($result))
{
  $Productbloc_tpl[$row["body"]]=$row["name"];  
}                 
$smarty->assign("PRODUCT_BLOC_TPL",$Productbloc_tpl);

$smarty->assign("PRODUCTS_FIELDS",$SelectModuleFields["Products"]);
$smarty->assign("SERVICES_FIELDS",$SelectModuleFields["Services"]);
// ITS4YOU-END

if ($templateid != "" || $select_module!="")
{
  $smarty->assign("SELECT_MODULE_FIELD",$SelectModuleFields[$select_module]);
  $smf_filename = $SelectModuleFields[$select_module];
  if(isInventoryModule($select_module))	//crmv@48404
    unset($smf_filename["Details"]);
  $smarty->assign("SELECT_MODULE_FIELD_FILENAME",$smf_filename);    
}

include_once("version.php");

//$sql = "SELECT version_type FROM vte_pdfmaker_license";
//$version_type = ucfirst($adb->getOne($sql,0,"version_type"));
$smarty->assign("MODULE",$currentModule); //crmv

$smarty->assign("VERSION",$version_type." ".$version);

$smarty->assign("CUSTOM_FUNCTIONS",SDK::getPDFCustomFunctions());	//crmv@2539m

$category = getParentTab();
$smarty->assign("CATEGORY",$category);
$smarty->assign("INVENTORY_MODULES",getInventoryModules()); //crmv@48404
$smarty->display('modules/PDFMaker/EditPDFTemplate.tpl');
?>