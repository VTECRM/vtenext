<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/
// ITS4YOU TT0093 VlMe N

require_once('include/database/PearDatabase.php');

global $adb, $current_user;

$smarty = new VteSmarty();

require_once('include/utils/UserInfoUtil.php');
global $current_language;
global $app_strings;
global $mod_strings;
global $table_prefix;
global $theme,$default_charset;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

global $current_language;

if (isset($_REQUEST["mode"]) && ($_REQUEST["mode"] == "columns" || $_REQUEST["mode"] == "stdcriteria"))
{
    $sec_module = $_REQUEST["secmodule"];

    $module_list = getModuleList($sec_module);

    if ($_REQUEST["mode"] == "stdcriteria")
    {
        $options = getStdCriteriaByModule($sec_module, $module_list);
        if (is_array($options) && count($options) > 0) // crmv@167234
        {
            foreach ($options AS $value => $label)
            {
                echo "<option value='".$value."'>".$label."</option>";
            }
        }
    }
    else
    {
        foreach ($module_list AS $blockid => $optgroup)
        {
            $options = getColumnsListbyBlock($sec_module,$blockid);

            if (is_array($options) && count($options) > 0) // crmv@167234
            {
                echo "<optgroup label='".$optgroup."'>";

                foreach ($options AS $value => $label)
                {
                    echo "<option value='".$value."'>".$label."</option>";
                }
                echo "</optgroup>";
            }
        }
    }

    exit;
}

if (isset($_REQUEST["mode"]) && $_REQUEST["mode"] == "Delete")
{
    $record = addslashes($_REQUEST["record"]);

    $sql1 = "SELECT module FROM ".$table_prefix."_pdfmaker_relblocks WHERE relblockid = '".$record."'";
    $rel_module = $result = $adb->getOne($sql1,0,"module");

    $sql2 = "DELETE FROM ".$table_prefix."_pdfmaker_relblocks WHERE relblockid = '".$record."'";
    $result = $adb->Query($sql2);

    header("Location:index.php?module=PDFMaker&action=PDFMakerAjax&file=ListRelatedBlocks&parenttab=Tools&pdfmodule=".$rel_module);

    exit;
}

if (isset($_REQUEST["mode"]) && $_REQUEST["mode"] == "add")
{
    $record = addslashes($_REQUEST["record"]);
    //crmv@208173
    $record = intval($record);
    if($record < 0){
        exit();
    }
    //crmv@208173

    $sql = "SELECT * FROM ".$table_prefix."_pdfmaker_relblocks WHERE relblockid = '".$record."'";
    $result = $adb->query($sql);
    $Blockdata = $adb->fetchByAssoc($result, 0);

    $body = $Blockdata["block"];

    $body = str_replace("RELBLOCK_START","RELBLOCK".$record."_START",$body);
    $body = str_replace("RELBLOCK_END","RELBLOCK".$record."_END",$body);

    //echo $body; exit;

    echo "<div id='block' style='display:none;'>".$body."</div>";

    echo "<script>
        var oEditor = parent.CKEDITOR.instances.body;	//crmv@25443

        content = document.getElementById('block').innerHTML;

        oEditor.insertHtml(content);
        parent.closePopup();	//crmv@25443
        </script>";

    exit;
}

$rep_mod_strings = return_module_language($current_language,"Reports");

$smarty->assign("REP", $rep_mod_strings);
$smarty->assign("MOD", $mod_strings);
$smarty->assign("APP", $app_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("PARENTTAB", getParentTab());
$smarty->assign("THEME_PATH",$theme_path);
$smarty->assign("IMAGE_PATH",$image_path);

$smarty->assign("DATEFORMAT",$current_user->date_format);
$smarty->assign("JS_DATEFORMAT",parse_calendardate($app_strings['NTC_DATE_FORMAT']));

$record = $_REQUEST["record"];

$columns1 = "";
if ($record)
{
    $sql = "SELECT * FROM ".$table_prefix."_pdfmaker_relblocks WHERE relblockid = '".$record."'";
    $result = $adb->query($sql);
    $Blockdata = $adb->fetchByAssoc($result, 0);

    $rel_module = $Blockdata["module"];
    $sec_module = $Blockdata["secmodule"];
    $blockname = $Blockdata["name"];
    $block = $Blockdata["block"];

    $sql2 = "SELECT * FROM ".$table_prefix."_pdfmaker_relblckdfilt WHERE datefilterid = '".$record."'";
    $result2 = $adb->query($sql2);
    $num_rows2 = $adb->num_rows($result2);

    if ($num_rows2 > 0)
    {
        $datecolumnname = $adb->query_result($result2,0,"datecolumnname");
        $stdselectedfilter = $adb->query_result($result2,0,"datefilter");
        $startdate = $adb->query_result($result2,0,"startdate");
        $enddate = $adb->query_result($result2,0,"enddate");

        if($startdate != "" && $startdate != "0000-00-00" && $startdate != "1900-01-01 00:00:00") // crmv@77567
            $smarty->assign("STARTDATE_STD",getValidDisplayDate($startdate));

        if($enddate != "" && $startdate != "0000-00-00"  && $startdate != "1900-01-01 00:00:00") // crmv@77567
            $smarty->assign("ENDDATE_STD",getValidDisplayDate($enddate));
    }


    $module_list = getModuleList($sec_module);

    $options1 = getStdCriteriaByModule($sec_module, $module_list);
    if (is_array($options1) && count($options1) > 0) // crmv@183676
    {
        foreach ($options1 AS $value => $label)
        {
            if ($value == $datecolumnname) $sel = "selected"; else $sel = "";

            $columns1 .= "<option value='".$value."' ".$sel.">".$label."</option>";
        }
    }


    $columns2 = "";
    foreach ($module_list AS $blockid => $optgroup)
    {
        $options2 = getColumnsListbyBlock($sec_module,$blockid);
        if (is_array($options2) && count($options2) > 0) // crmv@206710
        {
            $columns2 .= "<optgroup label='".$optgroup."'>";
            foreach ($options2 AS $value => $label)
            {
                $columns2 .= "<option value='".$value."'>".$label."</option>";
            }
            $columns2 .= "</optgroup>";
        }
    }

    $selected_columns = getSelectedColumnsList($rel_module, $sec_module, $record);

    $advft_criteria = getAdvancedFilterList($record);
}
else
{
    $rel_module = $_REQUEST["pdfmodule"];
    $block = "";
    $record = "";
    $blockname = "";
    $columns = "";
    $columns2 = "";
    $selected_columns = "";
    $advft_criteria = array();
    $stdselectedfilter = "";
}

$smarty->assign("CRITERIA_GROUPS",$advft_criteria);

$FILTER_OPTION = getAdvCriteriaHTML();
$smarty->assign("FOPTION",$FILTER_OPTION);


$smarty->assign("STDDATEFILTERFIELDS",$columns1);
$smarty->assign("SECCOLUMNS",$columns2);
$smarty->assign("SELECTEDCOLUMNS",$selected_columns);


$smarty->assign("RECORD",$record);
$smarty->assign("BLOCKNAME",$blockname);


$rel_module_id = getTabid($rel_module);

$restricted_modules = array('Emails','Events','Messages'); // crmv@164120 crmv@180240
$Related_Modules = array();

$rsql = "SELECT ".$table_prefix."_tab.name FROM ".$table_prefix."_tab
				INNER JOIN ".$table_prefix."_relatedlists on ".$table_prefix."_tab.tabid=".$table_prefix."_relatedlists.related_tabid
				WHERE ".$table_prefix."_tab.isentitytype=1
				AND ".$table_prefix."_tab.name NOT IN(".generateQuestionMarks($restricted_modules).")
				AND ".$table_prefix."_tab.presence=0 AND ".$table_prefix."_relatedlists.label!='Activity History'
        AND ".$table_prefix."_relatedlists.tabid = '".$rel_module_id."' AND ".$table_prefix."_tab.tabid != '".$rel_module_id."'";
$relatedmodules = $adb->pquery($rsql,array($restricted_modules));

if($adb->num_rows($relatedmodules))
{
    while($resultrow = $adb->fetch_array($relatedmodules))
    {
        $Related_Modules[] = $resultrow['name'];
    }
}

// crmv@106527 - add MyNotes
if (isModuleInstalled('MyNotes') && vtlib_isModuleActive('MyNotes') && isPermitted('MyNotes', 'DetailView')) {
    $notesFocus = CRMEntity::getInstance('MyNotes');
    if ($notesFocus->moduleHasNotes($rel_module)) {
        $Related_Modules[] = 'MyNotes';
    }
}
// crmv@106527e

//crmv@121616
if (SDK::isUitype(220)) {
    $result = $adb->pquery("select fieldname from {$table_prefix}_field where tabid = ? and uitype = ?", array($rel_module_id,220));
    if ($result && $adb->num_rows($result) > 0) {
        while($row=$adb->fetchByAssoc($result)) {
            $Related_Modules[] = 'ModLight'.str_replace('ml','',$row['fieldname']);
        }
    }
}
//crmv@121616e

if ($record == "") $sec_module = $Related_Modules[0];

$smarty->assign("SEC_MODULE",$sec_module);

$smarty->assign("RELATED_MODULES",$Related_Modules);

include_once("version.php");
$smarty->assign("VERSION",$version);


$smarty->assign("REL_MODULE",$rel_module);



//Standard criteria S
$BLOCKJS = getRBlockCriteriaJS();
$smarty->assign("BLOCKJS_STD",$BLOCKJS);

if(isset($_REQUEST["record"]) == false || $_REQUEST["record"]=='')
    $BLOCKCRITERIA = getRBlockSelectedStdFilterCriteria();
else
    $BLOCKCRITERIA = getRBlockSelectedStdFilterCriteria($stdselectedfilter);

$smarty->assign("BLOCKCRITERIA_STD",$BLOCKCRITERIA);
//Standard criteria E

$smarty->assign("RELATEDBLOCK",$block);

if(isset($_REQUEST["record"]) == false || $_REQUEST["record"]=='')
    $smarty->display("modules/PDFMaker/CreateRelatedBlock.tpl");
else
    $smarty->display("modules/PDFMaker/EditRelatedBlock.tpl");

function getColumnsListbyBlock($module,$block)
{
    global $adb, $table_prefix;
    global $log;
    global $current_user;

    if(is_string($block)) $block = explode(",", $block);

    $tabid = getTabid($module);
    if ($module == 'Calendar') {
        $tabid = array('9','16');
    }
    $params = array($tabid, $block);

    require('user_privileges/requireUserPrivileges.php'); // crmv@39110
    //Security Check
    if($is_admin == true || $profileGlobalPermission[1] == 0 || $profileGlobalPermission[2] ==0)
    {
        $sql = "select * from ".$table_prefix."_field where ".$table_prefix."_field.tabid in (". generateQuestionMarks($tabid) .") and ".$table_prefix."_field.block in (". generateQuestionMarks($block) .") and ".$table_prefix."_field.displaytype in (1,2,3) and ".$table_prefix."_field.presence in (0,2) ";

        //fix for Ticket #4016
        if($module == "Calendar")
            $sql.=" group by ".$table_prefix."_field.fieldlabel order by ".$table_prefix."_field.sequence";
        else
            $sql.=" order by ".$table_prefix."_field.sequence";
    }
    else
    {

        $profileList = getCurrentUserProfileList();
        $sql = "select * from ".$table_prefix."_field inner join ".$table_prefix."_profile2field on ".$table_prefix."_profile2field.fieldid=".$table_prefix."_field.fieldid inner join ".$table_prefix."_def_org_field on ".$table_prefix."_def_org_field.fieldid=".$table_prefix."_field.fieldid where ".$table_prefix."_field.tabid in (". generateQuestionMarks($tabid) .")  and ".$table_prefix."_field.block in (". generateQuestionMarks($block) .") and ".$table_prefix."_field.displaytype in (1,2,3) and ".$table_prefix."_profile2field.visible=0 and ".$table_prefix."_def_org_field.visible=0 and ".$table_prefix."_field.presence in (0,2)";
        if (count($profileList) > 0) {
            $sql .= " and ".$table_prefix."_profile2field.profileid in (". generateQuestionMarks($profileList) .")";
            array_push($params, $profileList);
        }

        //fix for Ticket #4016
        if($module == "Calendar")
            $sql.=" group by ".$table_prefix."_field.fieldid,".$table_prefix."_field.fieldlabel order by ".$table_prefix."_field.sequence";
        else
            $sql.=" group by ".$table_prefix."_field.fieldid order by ".$table_prefix."_field.sequence";
    }

    $result = $adb->pquery($sql, $params);
    $noofrows = $adb->num_rows($result);
    for($i=0; $i<$noofrows; $i++)
    {
        $fieldtablename = $adb->query_result($result,$i,"tablename");
        $fieldcolname = $adb->query_result($result,$i,"columnname");
        $fieldname = $adb->query_result($result,$i,"fieldname");
        $fieldtype = $adb->query_result($result,$i,"typeofdata");
        $uitype = $adb->query_result($result,$i,"uitype");
        $fieldtype = explode("~",$fieldtype);
        $fieldtypeofdata = $fieldtype[0];

        if (stripos($module,'ModLight') !== false && $fieldname == 'parent_id') continue;	//crmv@121616

        //Here we Changing the displaytype of the field. So that its criteria will be displayed correctly in Reports Advance Filter.
        $fieldtypeofdata=ChangeTypeOfData_Filter($fieldtablename,$fieldcolname,$fieldtypeofdata);

        if($fieldtablename == $table_prefix."_crmentity")
        {
            $fieldtablename = $fieldtablename.$module;
        }
        if($fieldname == "assigned_user_id")
        {
            $fieldtablename = $table_prefix."_users".$module;
            $fieldcolname = "user_name";
        }
        if($fieldname == "account_id")
        {
            $fieldtablename = $table_prefix."_account".$module;
            $fieldcolname = "accountname";
        }
        if($fieldname == "contact_id")
        {
            $fieldtablename = $table_prefix."_contactdetails".$module;
            $fieldcolname = "lastname";
        }
        if($fieldname == "parent_id")
        {
            $fieldtablename = $table_prefix."_crmentityRel".$module;
            $fieldcolname = "setype";
        }
        if($fieldname == "vendor_id")
        {
            $fieldtablename = $table_prefix."_vendorRel".$module;
            $fieldcolname = "vendorname";
        }
        if($fieldname == "potential_id")
        {
            $fieldtablename = $table_prefix."_potentialRel".$module;
            $fieldcolname = "potentialname";
        }
        if($fieldname == "assigned_user_id1")
        {
            $fieldtablename = $table_prefix."_usersRel1";
            $fieldcolname = "user_name";
        }
        if($fieldname == 'quote_id')
        {
            $fieldtablename = $table_prefix."_quotes".$module;
            $fieldcolname = "subject";
        }

        $product_id_tables = array(
            $table_prefix."_troubletickets"=>$table_prefix."_productsRel",
            $table_prefix."_campaign"=>$table_prefix."_productsCampaigns",
            $table_prefix."_faq"=>$table_prefix."_productsFaq",
        );
        if($fieldname == 'product_id' && isset($product_id_tables[$fieldtablename]))
        {
            $fieldtablename = $product_id_tables[$fieldtablename];
            $fieldcolname = "productname";
        }
        if($fieldname == 'campaignid' && $module=='Potentials')
        {
            $fieldtablename = $table_prefix."_campaign".$module;
            $fieldcolname = "campaignname";
        }
        if($fieldname == 'currency_id' && $fieldtablename==$table_prefix.'_pricebook')
        {
            $fieldtablename = $table_prefix."_currency_info".$module;
            $fieldcolname = "currency_name";
        }

        $fieldlabel = $adb->query_result($result,$i,"fieldlabel");
        $fieldlabel1 = str_replace(" ","_",$fieldlabel);
        $optionvalue = $fieldtablename.":".$fieldcolname.":".$module."_".$fieldlabel1.":".$fieldname.":".$fieldtypeofdata;
        //$this->adv_rel_fields[$fieldtypeofdata][] = '$'.$module.'#'.$fieldname.'$'."::".getTranslatedString($module,$module)." ".$fieldlabel;
        //added to escape attachments fields in Reports as we have multiple attachments
        if($module != 'HelpDesk' || $fieldname !='filename')
            $module_columnlist[$optionvalue] = getTranslatedString($fieldlabel,$module);
    }
    $blockname = getBlockName($block);
    if($blockname == 'LBL_RELATED_PRODUCTS' && ($module=='PurchaseOrder' || $module=='SalesOrder' || $module=='Quotes' || $module=='Invoice')){
        $fieldtablename = $table_prefix.'_inventoryproductrel';
        $fields = array('productid'=>getTranslatedString('Product Name',$module),
            'serviceid'=>getTranslatedString('Service Name',$module),
            'listprice'=>getTranslatedString('List Price',$module),
            'discount'=>getTranslatedString('Discount',$module),
            'quantity'=>getTranslatedString('Quantity',$module),
            'comment'=>getTranslatedString('Comments',$module),
        );
        $fields_datatype = array('productid'=>'V',
            'serviceid'=>'V',
            'listprice'=>'I',
            'discount'=>'I',
            'quantity'=>'I',
            'comment'=>'V',
        );
        foreach($fields as $fieldcolname=>$label){
            $fieldtypeofdata = $fields_datatype[$fieldcolname];
            $optionvalue =  $fieldtablename.":".$fieldcolname.":".$module."_".$label.":".$fieldcolname.":".$fieldtypeofdata;
            $module_columnlist[$optionvalue] = $label;
        }
    }
    $log->info("Reports :: FieldColumns->Successfully returned ColumnslistbyBlock".$module.$block);
    return $module_columnlist;
}

function getStdCriteriaByModule($sec_module, $module_list)
{
    global $adb, $table_prefix;
    global $log;
    global $current_user;
    require('user_privileges/requireUserPrivileges.php'); // crmv@39110

    $tabid = getTabid($sec_module);
    foreach($module_list as $blockid =>$key)
    {
        $blockids[] = $blockid;
    }
    //$blockids = implode("','",$blockids);

    $params = array($tabid, $blockids);
    if($is_admin == true || $profileGlobalPermission[1] == 0 || $profileGlobalPermission[2] == 0)
    {
        //uitype 6 and 23 added for start_date,EndDate,Expected Close Date
        $sql = "select * from ".$table_prefix."_field where ".$table_prefix."_field.tabid=? and (".$table_prefix."_field.uitype =5 or ".$table_prefix."_field.uitype = 6 or ".$table_prefix."_field.uitype = 23 or ".$table_prefix."_field.displaytype=2) and ".$table_prefix."_field.block in (". generateQuestionMarks($blockids) .") and ".$table_prefix."_field.presence in (0,2) order by ".$table_prefix."_field.sequence";
    }
    else
    {
        $profileList = getCurrentUserProfileList();
        $sql = "select * from ".$table_prefix."_field inner join ".$table_prefix."_tab on ".$table_prefix."_tab.tabid = ".$table_prefix."_field.tabid inner join ".$table_prefix."_profile2field on ".$table_prefix."_profile2field.fieldid=".$table_prefix."_field.fieldid inner join ".$table_prefix."_def_org_field on ".$table_prefix."_def_org_field.fieldid=".$table_prefix."_field.fieldid  where ".$table_prefix."_field.tabid=? and (".$table_prefix."_field.uitype =5 or ".$table_prefix."_field.displaytype=2) and ".$table_prefix."_profile2field.visible=0 and ".$table_prefix."_def_org_field.visible=0 and ".$table_prefix."_field.block in (". generateQuestionMarks($blockids) .") and ".$table_prefix."_field.presence in (0,2)";
        if (count($profileList) > 0) {
            $sql .= " and ".$table_prefix."_profile2field.profileid in (". generateQuestionMarks($profileList) .")";
            array_push($params, $profileList);
        }
        $sql .= " order by ".$table_prefix."_field.sequence";
    }

    $result = $adb->pquery($sql, $params);

    while($criteriatyperow = $adb->fetch_array($result))
    {
        $fieldtablename = $criteriatyperow["tablename"];
        $fieldcolname = $criteriatyperow["columnname"];
        $fieldlabel = $criteriatyperow["fieldlabel"];

        if($fieldtablename == $table_prefix."_crmentity")
        {
            $fieldtablename = $fieldtablename.$module;
        }
        $fieldlabel1 = str_replace(" ","_",$fieldlabel);
        $optionvalue = $fieldtablename.":".$fieldcolname.":".$module."_".$fieldlabel1;
        $stdcriteria_list[$optionvalue] = getTranslatedString($fieldlabel,$module);
    }

    $log->info("Reports :: StdfilterColumns->Successfully returned Stdfilter for".$module);
    return $stdcriteria_list;
}

function getRBlockCriteriaJS()
{
    $today = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d"), date("Y")));
    $tomorrow  = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")));
    $yesterday  = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")-1, date("Y")));

    $currentmonth0 = date("Y-m-d",mktime(0, 0, 0, date("m"), "01",   date("Y")));
    $currentmonth1 = date("Y-m-t");
    $lastmonth0 = date("Y-m-d",mktime(0, 0, 0, date("m")-1, "01",   date("Y")));
    $lastmonth1 = date("Y-m-t", strtotime("-1 Month"));
    $nextmonth0 = date("Y-m-d",mktime(0, 0, 0, date("m")+1, "01",   date("Y")));
    $nextmonth1 = date("Y-m-t", strtotime("+1 Month"));

    $lastweek0 = date("Y-m-d",strtotime("-2 week Sunday"));
    $lastweek1 = date("Y-m-d",strtotime("-1 week Saturday"));

    $thisweek0 = date("Y-m-d",strtotime("-1 week Sunday"));
    $thisweek1 = date("Y-m-d",strtotime("this Saturday"));

    $nextweek0 = date("Y-m-d",strtotime("this Sunday"));
    $nextweek1 = date("Y-m-d",strtotime("+1 week Saturday"));

    $next7days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")+6, date("Y")));
    $next30days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")+29, date("Y")));
    $next60days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")+59, date("Y")));
    $next90days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")+89, date("Y")));
    $next120days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")+119, date("Y")));

    $last7days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")-6, date("Y")));
    $last30days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")-29, date("Y")));
    $last60days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")-59, date("Y")));
    $last90days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")-89, date("Y")));
    $last120days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")-119, date("Y")));

    $currentFY0 = date("Y-m-d",mktime(0, 0, 0, "01", "01",   date("Y")));
    $currentFY1 = date("Y-m-t",mktime(0, 0, 0, "12", date("d"),   date("Y")));
    $lastFY0 = date("Y-m-d",mktime(0, 0, 0, "01", "01",   date("Y")-1));
    $lastFY1 = date("Y-m-t", mktime(0, 0, 0, "12", date("d"), date("Y")-1));
    $nextFY0 = date("Y-m-d",mktime(0, 0, 0, "01", "01",   date("Y")+1));
    $nextFY1 = date("Y-m-t", mktime(0, 0, 0, "12", date("d"), date("Y")+1));

    if(date("m") <= 3)
    {
        $cFq = date("Y-m-d",mktime(0, 0, 0, "01","01",date("Y")));
        $cFq1 = date("Y-m-d",mktime(0, 0, 0, "03","31",date("Y")));
        $nFq = date("Y-m-d",mktime(0, 0, 0, "04","01",date("Y")));
        $nFq1 = date("Y-m-d",mktime(0, 0, 0, "06","30",date("Y")));
        $pFq = date("Y-m-d",mktime(0, 0, 0, "10","01",date("Y")-1));
        $pFq1 = date("Y-m-d",mktime(0, 0, 0, "12","31",date("Y")-1));
    }else if(date("m") > 3 and date("m") <= 6)
    {
        $pFq = date("Y-m-d",mktime(0, 0, 0, "01","01",date("Y")));
        $pFq1 = date("Y-m-d",mktime(0, 0, 0, "03","31",date("Y")));
        $cFq = date("Y-m-d",mktime(0, 0, 0, "04","01",date("Y")));
        $cFq1 = date("Y-m-d",mktime(0, 0, 0, "06","30",date("Y")));
        $nFq = date("Y-m-d",mktime(0, 0, 0, "07","01",date("Y")));
        $nFq1 = date("Y-m-d",mktime(0, 0, 0, "09","30",date("Y")));

    }else if(date("m") > 6 and date("m") <= 9)
    {
        $nFq = date("Y-m-d",mktime(0, 0, 0, "10","01",date("Y")));
        $nFq1 = date("Y-m-d",mktime(0, 0, 0, "12","31",date("Y")));
        $pFq = date("Y-m-d",mktime(0, 0, 0, "04","01",date("Y")));
        $pFq1 = date("Y-m-d",mktime(0, 0, 0, "06","30",date("Y")));
        $cFq = date("Y-m-d",mktime(0, 0, 0, "07","01",date("Y")));
        $cFq1 = date("Y-m-d",mktime(0, 0, 0, "09","30",date("Y")));
    }
    else if(date("m") > 9 and date("m") <= 12)
    {
        $nFq = date("Y-m-d",mktime(0, 0, 0, "01","01",date("Y")+1));
        $nFq1 = date("Y-m-d",mktime(0, 0, 0, "03","31",date("Y")+1));
        $pFq = date("Y-m-d",mktime(0, 0, 0, "07","01",date("Y")));
        $pFq1 = date("Y-m-d",mktime(0, 0, 0, "09","30",date("Y")));
        $cFq = date("Y-m-d",mktime(0, 0, 0, "10","01",date("Y")));
        $cFq1 = date("Y-m-d",mktime(0, 0, 0, "12","31",date("Y")));

    }

    $sjsStr = '<script language="JavaScript" type="text/javaScript">
			function showDateRange( type )
			{
				if (type!="custom")
				{
					document.NewBlock.startdate.readOnly=true
					document.NewBlock.enddate.readOnly=true
					getObj("jscal_trigger_date_start").style.visibility="hidden"
					getObj("jscal_trigger_date_end").style.visibility="hidden"
				}
				else
				{
					document.NewBlock.startdate.readOnly=false
					document.NewBlock.enddate.readOnly=false
					getObj("jscal_trigger_date_start").style.visibility="visible"
					getObj("jscal_trigger_date_end").style.visibility="visible"
				}
				if( type == "today" )
				{

					document.NewBlock.startdate.value = "'.getValidDisplayDate($today).'";
					document.NewBlock.enddate.value = "'.getValidDisplayDate($today).'";
				}
				else if( type == "yesterday" )
				{

					document.NewBlock.startdate.value = "'.getValidDisplayDate($yesterday).'";
					document.NewBlock.enddate.value = "'.getValidDisplayDate($yesterday).'";
				}
				else if( type == "tomorrow" )
				{

					document.NewBlock.startdate.value = "'.getValidDisplayDate($tomorrow).'";
					document.NewBlock.enddate.value = "'.getValidDisplayDate($tomorrow).'";
				}
				else if( type == "thisweek" )
				{

					document.NewBlock.startdate.value = "'.getValidDisplayDate($thisweek0).'";
					document.NewBlock.enddate.value = "'.getValidDisplayDate($thisweek1).'";
				}
				else if( type == "lastweek" )
				{

					document.NewBlock.startdate.value = "'.getValidDisplayDate($lastweek0).'";
					document.NewBlock.enddate.value = "'.getValidDisplayDate($lastweek1).'";
				}
				else if( type == "nextweek" )
				{

					document.NewBlock.startdate.value = "'.getValidDisplayDate($nextweek0).'";
					document.NewBlock.enddate.value = "'.getValidDisplayDate($nextweek1).'";
				}

				else if( type == "thismonth" )
				{

					document.NewBlock.startdate.value = "'.getValidDisplayDate($currentmonth0).'";
					document.NewBlock.enddate.value = "'.getValidDisplayDate($currentmonth1).'";
				}

				else if( type == "lastmonth" )
				{

					document.NewBlock.startdate.value = "'.getValidDisplayDate($lastmonth0).'";
					document.NewBlock.enddate.value = "'.getValidDisplayDate($lastmonth1).'";
				}
				else if( type == "nextmonth" )
				{

					document.NewBlock.startdate.value = "'.getValidDisplayDate($nextmonth0).'";
					document.NewBlock.enddate.value = "'.getValidDisplayDate($nextmonth1).'";
				}
				else if( type == "next7days" )
				{

					document.NewBlock.startdate.value = "'.getValidDisplayDate($today).'";
					document.NewBlock.enddate.value = "'.getValidDisplayDate($next7days).'";
				}
				else if( type == "next30days" )
				{

					document.NewBlock.startdate.value = "'.getValidDisplayDate($today).'";
					document.NewBlock.enddate.value = "'.getValidDisplayDate($next30days).'";
				}
				else if( type == "next60days" )
				{

					document.NewBlock.startdate.value = "'.getValidDisplayDate($today).'";
					document.NewBlock.enddate.value = "'.getValidDisplayDate($next60days).'";
				}
				else if( type == "next90days" )
				{

					document.NewBlock.startdate.value = "'.getValidDisplayDate($today).'";
					document.NewBlock.enddate.value = "'.getValidDisplayDate($next90days).'";
				}
				else if( type == "next120days" )
				{

					document.NewBlock.startdate.value = "'.getValidDisplayDate($today).'";
					document.NewBlock.enddate.value = "'.getValidDisplayDate($next120days).'";
				}
				else if( type == "last7days" )
				{

					document.NewBlock.startdate.value = "'.getValidDisplayDate($last7days).'";
					document.NewBlock.enddate.value =  "'.getValidDisplayDate($today).'";
				}
				else if( type == "last30days" )
				{

					document.NewBlock.startdate.value = "'.getValidDisplayDate($last30days).'";
					document.NewBlock.enddate.value = "'.getValidDisplayDate($today).'";
				}
				else if( type == "last60days" )
				{

					document.NewBlock.startdate.value = "'.getValidDisplayDate($last60days).'";
					document.NewBlock.enddate.value = "'.getValidDisplayDate($today).'";
				}
				else if( type == "last90days" )
				{

					document.NewBlock.startdate.value = "'.getValidDisplayDate($last90days).'";
					document.NewBlock.enddate.value = "'.getValidDisplayDate($today).'";
				}
				else if( type == "last120days" )
				{

					document.NewBlock.startdate.value = "'.getValidDisplayDate($last120days).'";
					document.NewBlock.enddate.value = "'.getValidDisplayDate($today).'";
				}
				else if( type == "thisfy" )
				{

					document.NewBlock.startdate.value = "'.getValidDisplayDate($currentFY0).'";
					document.NewBlock.enddate.value = "'.getValidDisplayDate($currentFY1).'";
				}
				else if( type == "prevfy" )
				{

					document.NewBlock.startdate.value = "'.getValidDisplayDate($lastFY0).'";
					document.NewBlock.enddate.value = "'.getValidDisplayDate($lastFY1).'";
				}
				else if( type == "nextfy" )
				{

					document.NewBlock.startdate.value = "'.getValidDisplayDate($nextFY0).'";
					document.NewBlock.enddate.value = "'.getValidDisplayDate($nextFY1).'";
				}
				else if( type == "nextfq" )
				{

					document.NewBlock.startdate.value = "'.getValidDisplayDate($nFq).'";
					document.NewBlock.enddate.value = "'.getValidDisplayDate($nFq1).'";
				}
				else if( type == "prevfq" )
				{

					document.NewBlock.startdate.value = "'.getValidDisplayDate($pFq).'";
					document.NewBlock.enddate.value = "'.getValidDisplayDate($pFq1).'";
				}
				else if( type == "thisfq" )
				{
					document.NewBlock.startdate.value = "'.getValidDisplayDate($cFq).'";
					document.NewBlock.enddate.value = "'.getValidDisplayDate($cFq1).'";
				}
				else
				{
					document.NewBlock.startdate.value = "";
					document.NewBlock.enddate.value = "";
				}
			}
		</script>';

    return $sjsStr;
}

function getRBlockSelectedStdFilterCriteria($selecteddatefilter = "")
{
    global $rep_mod_strings;

    $datefiltervalue = Array("custom","prevfy","thisfy","nextfy","prevfq","thisfq","nextfq",
        "yesterday","today","tomorrow","lastweek","thisweek","nextweek","lastmonth","thismonth",
        "nextmonth","last7days","last30days", "last60days","last90days","last120days",
        "next30days","next60days","next90days","next120days"
    );

    $datefilterdisplay = Array("Custom","Previous FY", "Current FY","Next FY","Previous FQ","Current FQ","Next FQ","Yesterday",
        "Today","Tomorrow","Last Week","Current Week","Next Week","Last Month","Current Month",
        "Next Month","Last 7 Days","Last 30 Days","Last 60 Days","Last 90 Days","Last 120 Days",
        "Next 7 Days","Next 30 Days","Next 60 Days","Next 90 Days","Next 120 Days"
    );

    $sshtml = "";

    for($i=0;$i<count($datefiltervalue);$i++)
    {
        if($selecteddatefilter == $datefiltervalue[$i])
        {
            $sshtml .= "<option selected value='".$datefiltervalue[$i]."'>".$rep_mod_strings[$datefilterdisplay[$i]]."</option>";
        }else
        {
            $sshtml .= "<option value='".$datefiltervalue[$i]."'>".$rep_mod_strings[$datefilterdisplay[$i]]."</option>";
        }
    }

    return $sshtml;
}


function getModuleList($sec_module)
{
    global $adb, $table_prefix;

    // crmv@137727
    $sec_module_array = array();
    $sec_module_array[] = getTabid($sec_module);
    if ($sec_module == 'Calendar') {
        $sec_module_array[] = getTabid('Events');
    }
    $reportblocks =	$adb->pquery("SELECT blockid, blocklabel, tabid FROM ".$table_prefix."_blocks WHERE tabid IN (".generateQuestionMarks($sec_module_array).")", $sec_module_array);
    // crmv@137727e

    $prev_block_label = '';
    if($adb->num_rows($reportblocks)) {
        while($resultrow = $adb->fetch_array($reportblocks)) {
            $blockid = $resultrow['blockid'];
            $blocklabel = $resultrow['blocklabel'];
            if(!empty($blocklabel)){
                if($sec_module == 'Calendar' && $blocklabel == 'LBL_CUSTOM_INFORMATION')
                    $module_list[$blockid] = getTranslatedString($blocklabel,$sec_module);
                else
                    $module_list[$blockid] = getTranslatedString($blocklabel,$sec_module);
                $prev_block_label = $blocklabel;
            } else {
                $module_list[$blockid] = getTranslatedString($prev_block_label,$sec_module);
            }
        }
    }

    return $module_list;
}

function getSelectedColumnsList($primodule, $secmodule, $relblockid)
{
    global $adb, $table_prefix;
    global $modules;
    global $log,$current_user;

    $ssql = "select ".$table_prefix."_pdfmaker_relblockcol.* from ".$table_prefix."_pdfmaker_relblocks";
    $ssql .= " left join ".$table_prefix."_pdfmaker_relblockcol on ".$table_prefix."_pdfmaker_relblockcol.relblockid = ".$table_prefix."_pdfmaker_relblocks.relblockid";
    $ssql .= " where ".$table_prefix."_pdfmaker_relblocks.relblockid = ?";
    $ssql .= " order by ".$table_prefix."_pdfmaker_relblockcol.colid";
    $result = $adb->pquery($ssql, array($relblockid));
    $permitted_fields = Array();

    $selected_mod = explode(":",$secmodule);
    array_push($selected_mod,$primodule);

    while($columnslistrow = $adb->fetch_array($result))
    {
        $fieldname ="";
        $fieldcolname = $columnslistrow["columnname"];

        $selmod_field_disabled = true;
        foreach($selected_mod as $smod)
        {
            if((stripos($fieldcolname,":".$smod."_")>-1) && vtlib_isModuleActive($smod)){
                $selmod_field_disabled = false;
                break;
            }
        }
        if($selmod_field_disabled==false){
            list($tablename,$colname,$module_field,$fieldname,$single) = explode(":",$fieldcolname);
            require('user_privileges/requireUserPrivileges.php'); // crmv@39110
            list($module,$field) = explode("_",$module_field);
            if(sizeof($permitted_fields) == 0 && $is_admin == false && $profileGlobalPermission[1] == 1 && $profileGlobalPermission[2] == 1)
            {
                $permitted_fields = getaccesfield($module, $primodule, $secmodule);
            }
            $querycolumns = getEscapedColumns($selectedfields, $primodule, $secmodule);
            $fieldlabel = trim(str_replace($module," ",$module_field));
            $mod_arr=explode('_',$fieldlabel);
            $mod = ($mod_arr[0] == '')?$module:$mod_arr[0];
            $fieldlabel = trim(str_replace("_"," ",$fieldlabel));
            //modified code to support i18n issue
            //$mod_lbl = getTranslatedString($mod,$module); //module
            //$fld_lbl = getTranslatedString($fieldlabel,$module); //fieldlabel
            //$fieldlabel = $mod_lbl." ".$fld_lbl;
            $fieldlabel = getTranslatedString($fieldlabel,$module); //fieldlabel

            if(CheckFieldPermission($fieldname,$mod) != 'true' && $colname!="crmid")
            {
                $shtml .= "<option permission='no' value=\"".$fieldcolname."\" disabled = 'true'>".$fieldlabel."</option>";
            }
            else
            {
                $shtml .= "<option permission='yes' value=\"".$fieldcolname."\">".$fieldlabel."</option>";
            }
        }
        //end
    }
    return $shtml;
}

function getaccesfield($module, $primodule, $secmodule)
{
    global $current_user;
    global $adb, $table_prefix;
    $access_fields = Array();

    $profileList = getCurrentUserProfileList();
    $query = "select {$table_prefix}_field.fieldname from {$table_prefix}_field inner join {$table_prefix}_profile2field on {$table_prefix}_profile2field.fieldid={$table_prefix}_field.fieldid inner join {$table_prefix}_def_org_field on {$table_prefix}_def_org_field.fieldid={$table_prefix}_field.fieldid where";
    $params = array();
    if($module == "Calendar")
    {
        $query .= " {$table_prefix}_field.tabid in (9,16) and {$table_prefix}_field.displaytype in (1,2,3) and {$table_prefix}_profile2field.visible=0 and {$table_prefix}_def_org_field.visible=0 and {$table_prefix}_field.presence in (0,2)";
        if (count($profileList) > 0) {
            $query .= " and {$table_prefix}_profile2field.profileid in (". generateQuestionMarks($profileList) .")";
            array_push($params, $profileList);
        }
        $query .= " group by {$table_prefix}_field.fieldid order by {$table_prefix}_field.block, {$table_prefix}_field.sequence";
    }
    else
    {
        array_push($params, $primodule, $secmodule);
        $query .= " {$table_prefix}_field.tabid in (select tabid from {$table_prefix}_tab where {$table_prefix}_tab.name in (?,?)) and {$table_prefix}_field.displaytype in (1,2,3) and {$table_prefix}_profile2field.visible=0 and {$table_prefix}_def_org_field.visible=0 and {$table_prefix}_field.presence in (0,2)";
        if (count($profileList) > 0) {
            $query .= " and {$table_prefix}_profile2field.profileid in (". generateQuestionMarks($profileList) .")";
            array_push($params, $profileList);
        }
        $query .= " group by {$table_prefix}_field.fieldid order by {$table_prefix}_field.block, {$table_prefix}_field.sequence";
    }
    $result = $adb->pquery($query, $params);


    while($collistrow = $adb->fetch_array($result))
    {
        $access_fields[] = $collistrow["fieldname"];
    }
    return $access_fields;
}

function getEscapedColumns($selectedfields, $primarymodule, $secondarymodule)
{
    global $table_prefix;
    $fieldname = $selectedfields[3];
    if($fieldname == "parent_id")
    {
        if($primarymodule == "HelpDesk" && $selectedfields[0] == $table_prefix."_crmentityRelHelpDesk")
        {
            $querycolumn = "case ".$table_prefix."_crmentityRelHelpDesk.setype when 'Accounts' then ".$table_prefix."_accountRelHelpDesk.accountname when 'Contacts' then ".$table_prefix."_contactdetailsRelHelpDesk.lastname End"." '".$selectedfields[2]."', ".$table_prefix."_crmentityRelHelpDesk.setype 'Entity_type'";
            return $querycolumn;
        }
        if($primarymodule == "Products" || $secondarymodule == "Products")
        {
            $querycolumn = "case ".$table_prefix."_crmentityRelProducts.setype when 'Accounts' then ".$table_prefix."_accountRelProducts.accountname when 'Leads' then ".$table_prefix."_leaddetailsRelProducts.lastname when 'Potentials' then ".$table_prefix."_potentialRelProducts.potentialname End"." '".$selectedfields[2]."', ".$table_prefix."_crmentityRelProducts.setype 'Entity_type'";
        }
        if($primarymodule == "Calendar" || $secondarymodule == "Calendar")
        {
            $querycolumn = "case ".$table_prefix."_crmentityRelCalendar.setype when 'Accounts' then ".$table_prefix."_accountRelCalendar.accountname when 'Leads' then ".$table_prefix."_leaddetailsRelCalendar.lastname when 'Potentials' then ".$table_prefix."_potentialRelCalendar.potentialname when 'Quotes' then ".$table_prefix."_quotesRelCalendar.subject when 'PurchaseOrder' then ".$table_prefix."_purchaseorderRelCalendar.subject when 'Invoice' then ".$table_prefix."_invoiceRelCalendar.subject End"." '".$selectedfields[2]."', ".$table_prefix."_crmentityRelCalendar.setype 'Entity_type'";
        }
    }
    return $querycolumn;
}

function getAdvancedFilterList($relblockid)
{
    global $adb, $table_prefix;
    global $modules;
    global $log;

    $advft_criteria = array();

    $sql = 'SELECT * FROM '.$table_prefix.'_pdfmaker_relblckcri_g WHERE relblockid = ? ORDER BY relblockid';
    $groupsresult = $adb->pquery($sql, array($relblockid));

    $i = 1;
    $j = 0;
    while($relcriteriagroup = $adb->fetch_array($groupsresult)) {
        $groupId = $relcriteriagroup["groupid"];
        $groupCondition = $relcriteriagroup["group_condition"];

        $ssql = 'select '.$table_prefix.'_pdfmaker_relblckcri.* from '.$table_prefix.'_pdfmaker_relblocks
						inner join '.$table_prefix.'_pdfmaker_relblckcri on '.$table_prefix.'_pdfmaker_relblckcri.relblockid = '.$table_prefix.'_pdfmaker_relblocks.relblockid
						left join '.$table_prefix.'_pdfmaker_relblckcri_g on '.$table_prefix.'_pdfmaker_relblckcri.relblockid = '.$table_prefix.'_pdfmaker_relblckcri_g.relblockid
								and '.$table_prefix.'_pdfmaker_relblckcri.relblockid = '.$table_prefix.'_pdfmaker_relblckcri_g.relblockid';
        $ssql .= ' where '.$table_prefix.'_pdfmaker_relblocks.relblockid = ? AND '.$table_prefix.'_pdfmaker_relblckcri.groupid = ? order by '.$table_prefix.'_pdfmaker_relblckcri.colid';

        $result = $adb->pquery($ssql, array($relblockid, $groupId));

        //echo $ssql." = ".$relblockid." - ".$groupId;

        $noOfColumns = $adb->num_rows($result);
        if($noOfColumns <= 0) continue;

        while($relcriteriarow = $adb->fetch_array($result)) {
            $columnIndex = $relcriteriarow["colid"];
            $criteria = array();
            $criteria['columnname'] = html_entity_decode($relcriteriarow["columnname"]);
            $criteria['comparator'] = $relcriteriarow["comparator"];
            $advfilterval = $relcriteriarow["value"];
            $col = explode(":",$relcriteriarow["columnname"]);
            $temp_val = explode(",",$relcriteriarow["value"]);
            if($col[4] == 'D' || ($col[4] == 'T' && $col[1] != 'time_start' && $col[1] != 'time_end') || ($col[4] == 'DT')) {
                $val = Array();
                for($x=0;$x<count($temp_val);$x++) {
                    list($temp_date,$temp_time) = explode(" ",$temp_val[$x]);
                    $temp_date = getValidDisplayDate(trim($temp_date));
                    if(trim($temp_time) != '')
                        $temp_date .= ' '.$temp_time;
                    $val[$x]=$temp_date;
                }
                $advfilterval = implode(",",$val);
            }
            $criteria['value'] = decode_html($advfilterval);
            $criteria['column_condition'] = $relcriteriarow["column_condition"];

            $advft_criteria[$i]['columns'][$j] = $criteria;
            $advft_criteria[$i]['condition'] = $groupCondition;
            $j++;
        }
        $i++;
    }

    $log->info("Reports :: Successfully returned getAdvancedFilterList");
    return $advft_criteria;
}

function getAdvCriteriaHTML($selected="")
{
    $customView = CRMEntity::getInstance('CustomView');
    $adv_filter_options = $customView->getAdvFilterOptions();	//crmv@26161
    $shtml = "";
    foreach($adv_filter_options as $key=>$value)
    {
        if($selected == $key)
        {
            $shtml .= "<option selected value=\"".$key."\">".$value."</option>";
        }else
        {
            $shtml .= "<option value=\"".$key."\">".$value."</option>";
        }
    }
    return $shtml;
}
?>