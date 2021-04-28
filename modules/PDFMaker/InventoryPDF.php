<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
$memory_limit = substr(ini_get("memory_limit"), 0,-1);
if($memory_limit<256){
  ini_set("memory_limit","256M");
}

class PDFContent extends SDKExtendableClass { // crmv@42024
    protected $templateid;
    protected $module;
    protected $language;
    protected $focus;
    protected $db;
    protected $mod_strings;

    protected $site_url;
    var $decimal_point;
    var $thousands_separator;
    var $decimals;
    public $pagebreak;

    protected $ignored_picklist_values=array();

    protected $header;
    protected $footer;
    protected $body;
    protected $content;
    protected $filename;
    protected $templatename;
    protected $type;

    protected $section_sep="&#%ITS%%%@@@%%%ITS%#&";

    protected $replacements;

    protected $inventory_table_array = Array();

    protected $inventory_id_array = Array(); //crmv@48404

    protected $org_cols = array("organizationname" => "COMPANY_NAME",
                              "address" => "COMPANY_ADDRESS",
                              "city" => "COMPANY_CITY",
                              "state" => "COMPANY_STATE",
                              "code" => "COMPANY_ZIP",
                              "country" => "COMPANY_COUNTRY",
                              "phone" => "COMPANY_PHONE",
                              "fax" => "COMPANY_FAX",
                              "website" => "COMPANY_WEBSITE",
                              "logo" => "COMPANY_LOGO",
							  //crmv@25443
								'crmv_banking'=>'COMPANY_BANKING',
								'crmv_vat_registration_number'=>'COMPANY_VAT_REGISTRATION_NUMBER',
								'crmv_rea'=>'COMPANY_REA',
								'crmv_issued_capital'=>'COMPANY_ISSUED_CAPITAL',
							  //crmv@25443e
                       );


    function  __construct($templateid, $module, $focus, $language) {
    	global $table_prefix;
      $db = "adb";
      $mod = "mod_strings";
      $salt="site_URL";

      global $$db, $$mod, $$salt, $PDFMaker_template_id;

      $this->db = &$$db;
      $this->mod_strings = $$mod;
      //crmv@48404
      $mods = getInventoryModules();
      foreach ($mods as $imod) {
		$inst = CRMEntity::getInstance($imod);
		$this->inventory_table_array[$imod] = $inst->table_name;
		$this->inventory_id_array[$imod] = $inst->table_index;
      }
      //crmv@48404 e
      $this->templateid = $templateid;
      $PDFMaker_template_id = $this->templateid;
      $this->module = $module;
      $this->focus = $focus;
      $this->language = $language;

      $this->type = "professional";

      $this->getTemplateData();
      $this->getIgnoredPicklistValues();
    }

    public function getContent() {
    	global $table_prefix;
      require_once("classes/simple_html_dom.php");
      $img_root = "img_root_directory";
      global $$img_root;

      if ($this->type == "professional" || $this->type == "basic")
      {
          $this->content = $this->body;

          $this->content = $this->header.$this->section_sep;
          $this->content .= $this->body.$this->section_sep;
          $this->content .= $this->footer;

          $this->replacements["&nbsp;"]=" ";
          $this->replacements["##PAGE##"]="{PAGENO}";
          $this->replacements["##PAGES##"]="{nb}";
          $this->replacements["##DD-MM-YYYY##"]=date("d-m-Y");
          $this->replacements["##DD.MM.YYYY##"]=date("d.m.Y");
          $this->replacements["##MM-DD-YYYY##"]=date("m-d-Y");
          $this->replacements["##YYYY-MM-DD##"]=date("Y-m-d");
          $this->replacements["src='"]="src='".$$img_root;
          $this->replacements["$".strtoupper($this->module)."_CRMID$"]=$this->focus->id;
          $this->replacements["\$CRMID$"]=$this->focus->id;	//crmv@2539m

          if($this->module == "Contacts"){
            $this->replacements['$CONTACTS_IMAGENAME$']=$this->getContactImage($this->focus->id);
          }
		  //crmv@32962
          if($this->module == "Products"){
            $this->replacements['$PRODUCTS_IMAGENAME$']=$this->getProductImage($this->focus->id);
          }
          //crmv@32962e

          // MaSo - 19. 5. 2011
          $this->replaceContent();

          $this->content = html_entity_decode($this->content,ENT_QUOTES,"utf-8");  //because of encrypting it is here

          $html = str_get_html($this->content);
          // CKEditor
          foreach($html->find("div[style^=page-break-after]") as $div_page_break)
          {
            $div_page_break->outertext = $this->pagebreak;
            $this->content = $html->save();
          }
          // FCKEditor
          foreach($html->find("div[style^=PAGE-BREAK-AFTER]") as $div_page_break)
          {
            $div_page_break->outertext = $this->pagebreak;
            $this->content = $html->save();
          }
    	  $html->clear(); ////crmv@58635
    	  unset($html); ////crmv@58635

          $this->convertRelatedModule();
          $this->convertRelatedBlocks();

          $this->replaceFieldsToContent($this->module, $this->focus);
          $this->convertInventoryModules();
          
          $this->convertProdAttr($this->module, $this->focus); // crmv@198024

          if($this->focus->column_fields["assigned_user_id"]==""){
            $this->focus->column_fields["assigned_user_id"]=$this->db->query_result($this->db->query("SELECT smownerid FROM ".$table_prefix."_crmentity WHERE crmid=".$this->focus->id),0,"smownerid");
          }

          $this->convertListViewBlock();  //VlMe 1.31

          $this->replaceUserCompanyFields();
          $this->replaceLabels();
          $this->replaceBarcode();
          if ($this->type == "professional") $this->replaceCustomFunctions();

          $PDF_content = array();
          list($PDF_content["header"],$PDF_content["body"],$PDF_content["footer"]) = explode($this->section_sep,  $this->content);
      }
      else
      {
          $error_text = "Invalid license key! Please contact the vendor of PDF Maker.";

          $PDF_content = array("header" => "<center>ERROR</center>", "body" => $error_text, "footer" => "");
      }
      return $PDF_content;
    }

    public function getSettings() {
    	global $table_prefix;
    	// crmv@172422
      $sql = "SELECT (margin_top * 10) AS margin_top,
                     (margin_bottom * 10) AS margin_bottom,
                     (margin_left * 10) AS margin_left,
                     (margin_right*10) AS margin_right,
                     format,
                     orientation,
                     encoding,
                     compliance
              FROM ".$table_prefix."_pdfmaker_settings WHERE templateid = '".$this->templateid."'";
      $result = $this->db->query($sql);
      $Settings = $this->db->fetchByAssoc($result, 1);
      return $Settings;
    }

    protected function convertRelatedModule() {
    	global $table_prefix;
		// crmv@190493
    	$sql = "SELECT f.fieldid, fieldname, uitype, r.relmodule, (SELECT COUNT(*) FROM {$table_prefix}_fieldmodulerel WHERE fieldid = f.fieldid) AS count_links
              FROM {$table_prefix}_field f
              LEFT JOIN {$table_prefix}_fieldmodulerel r ON f.fieldid = r.fieldid 
              WHERE f.tabid=".getTabId($this->module)." AND (displaytype != 3) AND uitype = 10 ";
    	// crmv@190493e
      $result = $this->db->query($sql);
      $num_rows = $this->db->num_rows($result);

      if ($num_rows > 0)
      {
        while($row = $this->db->fetch_array($result))
        {
          $related_module = "";
          $fk_record = $this->focus->column_fields[$row["fieldname"]];
          // crmv@190493 crmv@191809
    		if($row['count_links'] > 1){
    			$related_module = getSalesEntityType($fk_record);
    		}else{
    			$related_module = $row['relmodule'];
    		}
			// crmv@190493e

          if ($related_module != "") {
            $tabid = getTabId($related_module);
            $field_inf = "_fieldinfo_cache";
            $temp = &VTCacheUtils::$$field_inf;
            unset($temp[$tabid]);
            $focus2 = CRMEntity::getInstance($related_module);
            if ($fk_record != "" && $fk_record != "0") {
              $result_delete = $this->db->query("SELECT deleted FROM ".$table_prefix."_crmentity WHERE crmid='".$fk_record."' AND deleted=0");
              if($this->db->num_rows($result_delete)>0){
              $focus2->retrieve_entity_info($fk_record,$related_module);
               $focus2->id = $fk_record;
              }
            }
            $this->replacements["$"."R_".strtoupper($related_module)."_CRMID$"]=$focus2->id;
            // add contact image to related contact modul
            if(isset($related_module) AND $related_module == "Contacts"){
              $this->replacements['$R_CONTACTS_IMAGENAME$']=$this->getContactImage($focus2->id);
              // print_r($this->replacements);
            }
            // preco to tu nebolo ?
            $this->replaceContent();
            $this->replaceFieldsToContent($related_module, $focus2, true);
            unset($focus2);
          }
		/*	crmv@24347
          if ($row["uitype"] == "10" || $row["uitype"] == "68") {
            if ($related_module == "Accounts")
              $related_module2 = "Contacts";
            else
              $related_module2 = "Accounts";

            $tabid2 = getTabId($related_module2);
            $field_inf = "_fieldinfo_cache";
            $temp = &VTCacheUtils::$$field_inf;
            unset($temp[$tabid2]);
            $focus3 = CRMEntity::getInstance($related_module2);
            $this->replacements["$"."R_".strtoupper($related_module2)."_CRMID$"]=$focus3->id;
            $this->replaceFieldsToContent($related_module2, $focus3, true);
            unset($focus3);
          }
		crmv@24347 - e */
        }
      }
    }

    protected function convertProductBlock() {
      require_once("classes/simple_html_dom.php");
      $html = str_get_html($this->content);
      $tableDOM=false;
      foreach($html->find("td") as $td)
      {
        if(trim($td->plaintext) == "#PRODUCTBLOC_START#"){
          $td->parent->outertext = "#PRODUCTBLOC_START#";
          list($tag)=explode(">",$td->parent->parent->parent->outertext,2);

          $header = $td->parent->prev_sibling()->outertext;
		  if ($td->parent->prev_sibling()->children[0])	//crmv@25443
          $header_style = $td->parent->prev_sibling()->children[0]->getAttribute("style");
          $footer_tag="<tr>";

          if(isset($header_style)){
           $StyleHeader = explode(";", $header_style);
           // print_r($StyleHeader);
           if(isset($StyleHeader)){
             foreach($StyleHeader as $style_header_tag)
             {
                if(strpos($style_header_tag, "border-top") == TRUE){
                  $footer_tag.="<td colspan='".$td->getAttribute("colspan")."' style='".$style_header_tag."'>&nbsp;</td>";
                }
             }
            }
          } else {
            $footer_tag.="<td colspan='".$td->getAttribute("colspan")."' style=\"border-top:1px solid #000000;\">&nbsp;</td>";
          }
          $footer_tag.="</tr>";

           // LAST VALUE IN TEMPLATE, MUST BE SUM
           $var = $td->parent->next_sibling()->last_child()->plaintext;

           $subtotal_tr="";
           // SUBTOTAL
           if(strpos($var, "TOTAL")!==false){
              // check for #PRODUCTBLOC_START# style

              if(is_object($td)){
                $style_subtotal=$td->getAttribute("style");
              }
              if(isset($td->innertext)){
                list($style_subtotal_tag,$style_subtotal_endtag)=explode("#PRODUCTBLOC_START#",$td->innertext);
              } else {
                $style_subtotal_tag = "";
                $style_subtotal_endtag = "";
              }
              // search for border-top style in PRODUCTBLOC_START td
             if(isset($style_subtotal)){
               $StyleSubtotal = explode(";", $style_subtotal);
               if(isset($StyleSubtotal)){
                 foreach($StyleSubtotal as $style_tag)
                 {
                    if(strpos($style_tag, "border-top") == TRUE){
                      $tag.=" style=\"".$style_tag."\"";
                      break;
                    }
                 }
               }
             } else {
              $style_subtotal = "";
             }

             $tag.=">";

             $subtotal_tr="<tr>";
                $subtotal_tr.="<td colspan='".($td->getAttribute("colspan")-1)."' style='".$style_subtotal.";border-right:none'>".$style_subtotal_tag."%G_Subtotal%".$style_subtotal_endtag."</td>";
                $subtotal_tr.="<td align='right' nowrap=\"nowrap\" style='".$style_subtotal."'>".$style_subtotal_tag."".rtrim($var,"$")."_SUBTOTAL$".$style_subtotal_endtag."</td>";
             $subtotal_tr.="</tr>";
           }

           $tableDOM["tag"] = $tag;
           $tableDOM["header"] = $header;
           $tableDOM["footer"] = $footer_tag;
           $tableDOM["subtotal"] = $subtotal_tr;
        }
        if(trim($td->plaintext) == "#PRODUCTBLOC_END#")
           $td->parent->outertext = "#PRODUCTBLOC_END#";
      }
      $this->content=$html->save();
      $html->clear(); ////crmv@58635
      unset($html); ////crmv@58635
      return $tableDOM;
    }

    protected function convertInventoryModules() {
      global $table_prefix;
	  $query = "select * from ".$table_prefix."_inventoryproductrel where id=?";
      $result = $this->db->pquery($query, array($this->focus->id));
      $num_rows=$this->db->num_rows($result);

	  if ($num_rows > 0)
      {

        if (!isset($this->inventory_table_array[$this->module]) || $this->inventory_table_array[$this->module] == "")
	    {
		    $query2 = "select tablename, entityidfield from ".$table_prefix."_entityname where modulename=?";
			$result2 = $this->db->pquery($query2, array($this->module));
			$this->inventory_table_array[$this->module] = $this->db->query_result($result2,0,"tablename");
			$this->inventory_id_array[$this->module] = $this->db->query_result($result2,0,"entityidfield");
		}

		$this->replacements["$"."SUBTOTAL$"] = $this->formatNumberToPDF($this->focus->column_fields["hdnSubTotal"]);
        $this->replacements["$"."TOTAL$"] = $this->formatNumberToPDF($this->focus->column_fields["hdnGrandTotal"]);

        $currencytype = $this->getInventoryCurrencyInfoCustom();
		$currencytype["currency_symbol"] = str_replace("â‚¬","&euro;",$currencytype["currency_symbol"]);
        $currencytype["currency_symbol"] = str_replace("Â£","&pound;",$currencytype["currency_symbol"]);

        $this->replacements["$"."CURRENCYNAME$"] = getTranslatedCurrencyString($currencytype["currency_name"]);
        $this->replacements["$"."CURRENCYSYMBOL$"] = $currencytype["currency_symbol"];
        $this->replacements["$"."CURRENCYCODE$"] = $currencytype["currency_code"];
        $this->replacements["$"."ADJUSTMENT$"] = $this->formatNumberToPDF($this->focus->column_fields["txtAdjustment"]);

        $Products = $this->getInventoryProducts();

        $this->replacements["$"."TOTALWITHOUTVAT$"] = $Products["TOTAL"]["TOTALWITHOUTVAT"];
        $this->replacements["$"."VAT$"] = $Products["TOTAL"]["TAXTOTAL"];
        $this->replacements["$"."VATPERCENT$"] = $Products["TOTAL"]["TAXTOTALPERCENT"];

        if (is_array($Products["TOTAL"]["VATBLOCK"]) && count($Products["TOTAL"]["VATBLOCK"]) > 0) // crmv@187957
		{
			//crmv@34067
            $vattable = "<table border='1' style=\"border-collapse:collapse;width:100%;\" cellpadding=\"3\">";
            $vattable .= "<tr>
                            <td nowrap align='center'><b>".$this->app_strings["Name"]."</b></td>
							<td nowrap align='center'><b>".$this->mod_strings["LBL_VATBLOCK_VAT_PERCENT"]."</b></td>
                            <td nowrap align='center'><b>".$this->mod_strings["LBL_VATBLOCK_SUM"]." (".$currencytype["currency_symbol"].")"."</b></td>
                            <td nowrap align='center'><b>".$this->mod_strings["LBL_VATBLOCK_VAT_VALUE"]." (".$currencytype["currency_symbol"].")"."</b></td>
                          </tr>";
            foreach ($Products["TOTAL"]["VATBLOCK"] as $keyW=>$valueW)
            {
                if($valueW["netto"]!=0) {
                    $vattable .= "<tr>
                                    <td nowrap align='left' width='20%'>".$valueW["label"]."</td>
									<td nowrap align='right' width='25%'>".$this->formatNumberToPDF($valueW["value"])." %</td>
                                    <td nowrap align='right' width='30%'>".$this->formatNumberToPDF($valueW["netto"])."</td>
                                    <td nowrap align='right' width='25%'>".$this->formatNumberToPDF($valueW["vat"])."</td>
                                  </tr>";
                }
			}
            $vattable .= "</table>";
            //crmv@34067e
        }
		else
		{
			$vattable = "";
		}
        $this->replacements["$"."VATBLOCK$"] = $vattable;
        $this->replacements["$"."TOTALWITHVAT$"] = $Products["TOTAL"]["TOTALWITHVAT"];
        $this->replacements["$"."SHTAXAMOUNT$"] = $Products["TOTAL"]["SHTAXAMOUNT"];
        $this->replacements["$"."SHTAXTOTAL$"] = $Products["TOTAL"]["SHTAXTOTAL"];
        $this->replacements["$"."TOTALDISCOUNT$"] = $Products["TOTAL"]["FINALDISCOUNT"];
        $this->replacements["$"."TOTALDISCOUNTPERCENT$"] = $Products["TOTAL"]["FINALDISCOUNTPERCENT"];
        $this->replacements["$"."TOTALAFTERDISCOUNT$"] = $Products["TOTAL"]["TOTALAFTERDISCOUNT"];
        $this->replaceContent();

        $var_array = array();
        if(strpos($this->content,"#PRODUCTBLOC_START#") !== false && strpos($this->content,"#PRODUCTBLOC_END#") !== false)
        {
          $tableTag = $this->convertProductBlock();
          $breaklines_array = $this->getInventoryBreaklines();
          $breaklines = $breaklines_array["products"];
          $show_header = $breaklines_array["show_header"];
          $show_subtotal = $breaklines_array["show_subtotal"];

          $breakline_type="";
          if(count($breaklines)>0)
          {
            if($tableTag!==false) {
              $breakline_type="</table>".$this->pagebreak.$tableTag["tag"];
              if($show_header==1)
                $breakline_type.=$tableTag["header"];
              if($show_subtotal==1){
                $breakline_type=$tableTag["subtotal"].$breakline_type;
              } else {
                $breakline_type=$tableTag["footer"].$breakline_type;
              }

            }
            else
              $breakline_type=$this->pagebreak;
          }

          $ExplodedPdf = array();
          $Exploded = explode("#PRODUCTBLOC_START#",$this->content);
          $ExplodedPdf[] = $Exploded[0];
          for($iterator=1;$iterator<count($Exploded);$iterator++){
            $SubExploded = explode("#PRODUCTBLOC_END#",$Exploded[$iterator]);
            foreach($SubExploded as $part)
              $ExplodedPdf[] = $part;
            $highestpartid = $iterator*2-1;
            $ProductParts[$highestpartid]=$ExplodedPdf[$highestpartid];
            $ExplodedPdf[$highestpartid]='';
          }
        if ($Products["P"]) {
			foreach ( $Products["P"] as $Product_i => $Product_Details ) {
				foreach ( $ProductParts as $productpartid => $productparttext ) {
					$breakline = "";
					$tot += floatval ( str_replace ( ',', '.', str_replace ( '.', '', $Product_Details['PRODUCTSTOTALAFTERDISCOUNT'] ) ) ); //crmv@62673
					if ($breakline_type != "" && isset( $breaklines[$Product_Details["SERVICES_RECORD_ID"] . "_" . $Product_Details["PRODUCTSEQUENCE"]] ) || isset ( $breaklines[$Product_Details["PRODUCTS_RECORD_ID"] . "_" . $Product_Details["PRODUCTSEQUENCE"]] )) {
						$breakln = true; //crmv@62673
						$breakline = $breakline_type;
					}
					$productparttext .= $breakline;
					//crmv@62673
					if ($breakln) {
						$breakln = false;
						$productparttext = str_replace("\$PRODUCTSTOTALAFTERDISCOUNT_SUBTOTAL\$", $this->formatNumberToPDF( $tot ) . " " . $currencytype["currency_symbol"], $productparttext );
						$tot = 0;
					}
					//crmv@62673e
					foreach ( $Product_Details as $coll => $value ) {
						$productparttext = str_replace("$" . strtoupper( $coll ) . "$", $value, $productparttext );
					}
					$ExplodedPdf[$productpartid] .= $productparttext;
				}
			}
		}
        $this->content = implode('',$ExplodedPdf);
        }
      }
    }

    // crmv@190493
    protected function replaceFieldsToContent($emodule, $efocus, $is_related = false, $is_inventory = false) {
    	global $table_prefix;
    	$related_fieldnames = array("related_to",
    			"relatedto",
    			"parent_id",
    			"parentid",
    			"product_id",
    			"productid",
    			"service_id",
    			"serviceid",
    			"vendor_id",
    			"product",
    			"account",
    			"invoiceid",
    			"linktoaccountscontacts",
    			"projectid",
    			"sc_related_to"
    	);
    	
    	if($is_inventory){
    		$inventory_content=array();
    	}
    	
    	//crmv@32899
    	$moduleInstance = Vtecrm_Module::getInstance($emodule);
    	$tabid = $moduleInstance->id;
    	//crmv@32899e
    	if ($is_related){
    		$related = "R_";
    	}else{
    		$related = "";
    	}
    	
    	$Checkboxes = array();
    	$Picklists = array();
    	$Picklists_ml = array(); // multilanguage picklist   //crmv@27014
    	$Textareas = array();
    	$Datefields = array();
    	$DateTimefields = array(); //crmv@28638
    	$Multipicklists = array();
    	$NumbersField = array();
    	$UserField = array(); //crmv@97136
    	$sdk_uitypes = array(); //crmv@69568
    	$TimerField = array();
    	
    	$sql = "SELECT fieldname, uitype FROM ".$table_prefix."_field WHERE tabid = '".$tabid."'";
    	$result = $this->db->query($sql);
    	
    	//crmv@25443
    	if (empty($this->reference_fields[$emodule])) {
    		$referenceUitype = array();
    		$res = $this->db->query("SELECT uitype FROM ".$table_prefix."_ws_fieldtype WHERE fieldtype = 'reference'");
    		if ($res) {
    			while($row=$this->db->fetchByAssoc($res)) {
    				$referenceUitype[] = $row['uitype'];
    			}
    		}
    		$res = $this->db->query("SELECT fieldname from ".$table_prefix."_field WHERE uitype in (".implode(',',$referenceUitype).") and tabid = ".$tabid);
    		if ($res) {
    			while($row=$this->db->fetchByAssoc($res)) {
    				$this->reference_fields[$emodule][] = $row['fieldname'];
    			}
    		}
    	}
    	//crmv@25443e
    	
    	while($row = $this->db->fetchByAssoc($result)) {
    		if ($row["uitype"] == "19" || $row["uitype"] == "20" || $row["uitype"] == "21" || $row["uitype"] == "24"){
    			$Textareas[] = $row["fieldname"];
    		}elseif ($row["uitype"] == "5" || $row["uitype"] == "23" || $row["uitype"] == "70") {
    			//crmv@26202
    			if ($row["uitype"] == "5") {
    				$Datefields[] = $row["fieldname"];
    			} else {
    				$DateTimefields[] = $row["fieldname"];
    			}
    			//crmv@26202e
    		} elseif ($row["uitype"] == "15" || $row["fieldname"] == "salutationtype"){
    			$Picklists[] = $row["fieldname"];
    			//crmv@27014
    		}elseif ($row["uitype"] == "1015"){
    			$Picklists_ml[] = $row["fieldname"];
    			//crmv@27014e
    		}elseif ($row["uitype"] == "56"){
    			$Checkboxes[] = $row["fieldname"];
    		}elseif ($row["uitype"] == "33"){
    			$Multipicklists[] = $row["fieldname"];
    		}elseif ($row["uitype"] == "71" || $row["uitype"] == "7"){ //crmv@51366
    			$NumbersField[] = $row["fieldname"];
    			//crmv@97136
    		}elseif (in_array($row["uitype"],array('50','51','52','53','77'))){	//crmv@126096
    			$UserField[] = $row["fieldname"];
    			//crmv@97136e
    			//crmv@65492 - 28
    		}elseif(SDK::isUitype($row["uitype"])){
    			$sdk_uitypes[$row["fieldname"]]=$row["uitype"];
    			}
    			//crmv@65492e - 28
    			elseif ($row["uitype"] == "1020"){
    				$TimerField[] = $row["fieldname"];
    			}
    	}
    	
    	foreach ($efocus->column_fields as $fieldname => $value)
    	{
    		
    		if (in_array($fieldname,$UserField) && is_numeric($value)){	//crmv@60349 crmv@97136 crmv@126096
    			$value = $this->getOwnerNameCustom($value);
    		}elseif($fieldname == "terms_conditions"){
    			$value = $this->getTermsAndConditionsCustom($value);
    		}elseif($fieldname == "comments"){
    			$value = $this->getTicketCommentsCustom($efocus);
    		}elseif($fieldname == "folderid"){
    			$value = $this->getFolderName($value);
    		// crmv@190493
    		}elseif(in_array($fieldname, $related_fieldnames) || (!empty($this->reference_fields[$emodule]) && in_array($fieldname, $this->reference_fields[$emodule]))) {	//crmv@25443
    			if (!empty($value)) {
    				$parent_module = getSalesEntityType($value);
    				$displayValueArray = getEntityName($parent_module, $value);
    				
    				if(!empty($displayValueArray)) {
    					foreach($displayValueArray as $key=>$p_value) {
    						$value = $p_value;
    					}
    				}
    			}
    			if(empty($value)){
    				$value="";
    			}
    		}
    		// crmv@190493e
    		
    		//crmv@26202
    		if(in_array($fieldname, $Datefields)) {
    			$value = substr(getValidDisplayDate($value),0,10);
    		} elseif(in_array($fieldname, $DateTimefields)) {
    			$value = getValidDisplayDate($value);
    		}
    		//crmv@26202e
    		elseif(in_array($fieldname, $Picklists)) {
    			if(!in_array(trim($value), $this->ignored_picklist_values)){
    				$value = $this->getTranslatedStringCustom($value,$emodule);
    			}else{
    				$value = "";
    			}
    		}
    		//crmv@27014
    		elseif(in_array($fieldname, $Picklists_ml)) {
    			if(!in_array(trim($value), $this->ignored_picklist_values)){
    				$value = Picklistmulti::getTranslatedPicklist($value, $fieldname, $this->language);
    			}else{
    				$value = "";
    			}
    		}
    		//crmv@27014e
    		elseif(in_array($fieldname, $Checkboxes)) {
    			$pdf_app_strings = return_application_language($this->language);
    			if($value == 1){
    				$value = $pdf_app_strings["yes"];
    			}else{
    				$value = $pdf_app_strings["no"];
    			}
    		}
    		elseif(in_array($fieldname, $Textareas)){
    			$value = nl2br($value);
    		}elseif(in_array($fieldname, $Multipicklists)){
    			$value = str_ireplace(' |##| ',', ',$value);
    		}elseif(in_array($fieldname, $NumbersField)){
    			$value = $this->formatNumberToPDF($value);
			//crmv@126096
    		}elseif(in_array($fieldname, $TimerField)){
    			$value = time_duration(abs($value));
 			//crmv@126096e
			//crmv@65492 - 28
    		}elseif(array_key_exists($fieldname,$sdk_uitypes)){
    			$sdk_file = SDK::getUitypeFile('php','pdfmaker',$sdk_uitypes[$fieldname]);
    			$sdk_value = $value;
    			if ($sdk_file != '') {
    				include($sdk_file);
    			}
			//crmv@65492e - 28
			// crmv@205306
			} elseif ($fieldname === 'taxclass') {
				$IU = InventoryUtils::getInstance();
				$taxValues = Zend_Json::decode($value) ?: [];
	
				$taxInfo = [];
				foreach ($taxValues as $taxName => $taxPerc) {
					$taxLabel = $IU->getTaxLabel($taxName);
					$taxInfo[] = $taxLabel . ": " . $this->formatNumberToPDF($taxPerc) . " %";
				}
	
				$value = implode(', ', $taxInfo);
			// crmv@205306e
			// crmv@171512
			} elseif ($this->convertFieldsHtml) {
				global $default_charset;
				$value = htmlentities($value, ENT_NOQUOTES, $default_charset);
				if ($this->convertFieldsForCKEditor) {
					$value = str_ireplace(array('&amp;', '&lt;', '&gt;'), array('&amp;amp;', '&amp;lt;', '&amp;gt;'), $value); // crmv@166458 - ckeditor bug!!
				}
			}
			// crmv@171512e
	    			
			if ($is_inventory) {
				$inventory_content[strtoupper($emodule."_".$fieldname)] = $value;
			} else {
				$this->replacements["$".$related.strtoupper($emodule."_".$fieldname)."$"]=$value;
			}
    	}
		if ($is_inventory) {
			return $inventory_content;
		} else {
			$this->replaceContent();
		}
    }
    // crmv@190493e

    protected function replaceUserCompanyFields() {
    	global $table_prefix;
      $cur_usr = "current_user";
      $root_dir = "root_directory";
      global $$cur_usr,$$root_dir;

      //organization details
      //crmv@25963
	  global $adb;
	  $res = $adb->query("select * from ".$table_prefix."_field where fieldname = 'bu_mc'");
	  if ($res && $adb->num_rows($res) > 0) {
		  if ($_REQUEST['organizationid'] != '') {
			  $sql = "SELECT * FROM ".$table_prefix."_organizationdetails where organizationid = ?";
			  $result = $this->db->pquery($sql,array($_REQUEST['organizationid']));
		  }
	  } else {
		  $sql = "SELECT * FROM ".$table_prefix."_organizationdetails";
		  $result = $this->db->pquery($sql,array());
	  }
	  if ($result && $adb->num_rows($result) > 0) {
		  $Org_Details = $this->db->fetchByAssoc($result, 1);
		  foreach ($Org_Details AS $coll => $value) {
			if ($coll == "logo")
			  $value = "<img src=\"".$$root_dir."storage/logo/".$Org_Details["logoname"]."\">";

			$this->replacements["$".$this->org_cols[$coll]."$"] = $value;
		  }
	  }
	  //crmv@25963e

      //terms and conditions
	  //crmv
      $result = $this->db->query("SELECT tandc FROM ".$table_prefix."_inventory_tandc WHERE type = 'Inventory'");
      $tandc = $this->db->query_result($result,0,"tandc");
	  //crmv e
      $this->replacements["$"."TERMS_AND_CONDITIONS$"] = nl2br($tandc);

      //assigned user fields
      $user_sql = "SELECT *
                   FROM ".$table_prefix."_users WHERE id=".$this->focus->column_fields["assigned_user_id"];
      $user_res = $this->db->query($user_sql);
      $user_row = $this->db->fetchByAssoc($user_res);

      $this->replacements["$"."USER_USERNAME$"] = $user_row["user_name"];
      $this->replacements["$"."USER_FIRSTNAME$"] = $user_row["first_name"];
      $this->replacements["$"."USER_LASTNAME$"] = $user_row["last_name"];
      $this->replacements["$"."USER_EMAIL$"] = $user_row["email1"];

      $this->replacements["$"."USER_TITLE$"] = $user_row["title"];
      $this->replacements["$"."USER_FAX$"] = $user_row["phone_fax"];
      $this->replacements["$"."USER_DEPARTMENT$"] = $user_row["department"];
      $this->replacements["$"."USER_OTHER_EMAIL$"] = $user_row["email2"];
      $this->replacements["$"."USER_PHONE$"] = $user_row["phone_work"];
      $this->replacements["$"."USER_YAHOOID$"] = $user_row["yahoo_id"];
      $this->replacements["$"."USER_MOBILE$"] = $user_row["phone_mobile"];
      $this->replacements["$"."USER_HOME_PHONE$"] = $user_row["phone_home"];
      $this->replacements["$"."USER_OTHER_PHONE$"] = $user_row["phone_other"];
      $this->replacements["$"."USER_NOTES$"] = $user_row["description"];

      $this->replacements["$"."USER_ADDRESS$"] = $user_row["address_street"];
      $this->replacements["$"."USER_COUNTRY$"] = $user_row["address_country"];
      $this->replacements["$"."USER_CITY$"] = $user_row["address_city"];
      $this->replacements["$"."USER_ZIP$"] = $user_row["address_postalcode"];
      $this->replacements["$"."USER_STATE$"] = $user_row["address_state"];

      //current logged in fields
      $this->replacements["$"."L_USER_USERNAME$"] = $$cur_usr->column_fields["user_name"];
      $this->replacements["$"."L_USER_FIRSTNAME$"] = $$cur_usr->column_fields["first_name"];
      $this->replacements["$"."L_USER_LASTNAME$"] = $$cur_usr->column_fields["last_name"];
      $this->replacements["$"."L_USER_EMAIL$"] = $$cur_usr->column_fields["email1"];

      $this->replacements["$"."L_USER_TITLE$"] = $$cur_usr->column_fields["title"];
      $this->replacements["$"."L_USER_FAX$"] = $$cur_usr->column_fields["phone_fax"];
      $this->replacements["$"."L_USER_DEPARTMENT$"] = $$cur_usr->column_fields["department"];
      $this->replacements["$"."L_USER_OTHER_EMAIL$"] = $$cur_usr->column_fields["email2"];
      $this->replacements["$"."L_USER_PHONE$"] = $$cur_usr->column_fields["phone_work"];
      $this->replacements["$"."L_USER_YAHOOID$"] = $$cur_usr->column_fields["yahoo_id"];
      $this->replacements["$"."L_USER_MOBILE$"] = $$cur_usr->column_fields["phone_mobile"];
      $this->replacements["$"."L_USER_HOME_PHONE$"] = $$cur_usr->column_fields["phone_home"];
      $this->replacements["$"."L_USER_OTHER_PHONE$"] = $$cur_usr->column_fields["phone_other"];
      $this->replacements["$"."L_USER_NOTES$"] = $$cur_usr->column_fields["description"];

      $this->replacements["$"."L_USER_ADDRESS$"] = $$cur_usr->column_fields["address_street"];
      $this->replacements["$"."L_USER_COUNTRY$"] = $$cur_usr->column_fields["address_country"];
      $this->replacements["$"."L_USER_CITY$"] = $$cur_usr->column_fields["address_city"];
      $this->replacements["$"."L_USER_ZIP$"] = $$cur_usr->column_fields["address_postalcode"];
      $this->replacements["$"."L_USER_STATE$"] = $$cur_usr->column_fields["address_state"];

      $this->replaceContent();
    }


    protected function replaceLabels()
    {
       $app_lang = return_application_language($this->language);
       $mod_lang = return_specified_module_language($this->language,  $this->module);

       if(strpos($this->content, "%G_")!==false){
         foreach($app_lang as $key=>$value){
           $this->replacements["%G_".$key."%"] = $value;
         }
       }

       if(strpos($this->content, "%M_")!==false){
         foreach($mod_lang as $key=>$value){
           $this->replacements["%M_".$key."%"] = $value;
         }
       }
       $this->replaceContent();
    }

    protected function replaceBarcode()
    {
      require_once("classes/simple_html_dom.php");
      $this->replacements["[BARCODE|"]="<barcode>";
      $this->replacements["|BARCODE]"]="</barcode>";
      $this->replaceContent();
      $html = str_get_html($this->content);

      foreach($html->find("barcode") as $barcode) {
        list($type,$code) = explode("=",$barcode->plaintext,2);
        $barcode->outertext = '<barcode code="'.$code.'" type="'.$type.'" />';
      }

      $this->content = $html->save();
      $html->clear(); ////crmv@58635
      unset($html); ////crmv@58635      
    }

    protected function replaceContent() {
      if(!empty($this->replacements)){
        $this->content = str_replace(array_keys($this->replacements), $this->replacements, $this->content);
        $this->replacements = array();
      }
    }

    //utils to get template data
    protected function getTemplateData() {
      global $table_prefix;
      $salt="site_URL";
      global $$salt;
      $this->site_url = trim($$salt,"/");

      $sql = "SELECT ".$table_prefix."_pdfmaker.*, ".$table_prefix."_pdfmaker_settings.*
              FROM ".$table_prefix."_pdfmaker
              LEFT JOIN ".$table_prefix."_pdfmaker_settings
                ON ".$table_prefix."_pdfmaker_settings.templateid = ".$table_prefix."_pdfmaker.templateid
              WHERE ".$table_prefix."_pdfmaker.templateid=?";
      $result = $this->db->pquery($sql, array($this->templateid));
      $data = $this->db->fetch_array($result);

      $this->decimal_point = html_entity_decode($data["decimal_point"], ENT_QUOTES);
      $this->thousands_separator = html_entity_decode(($data["thousands_separator"]!="sp" ? $data["thousands_separator"] : " "), ENT_QUOTES);
      $this->decimals = $data["decimals"];
      $this->header = $data["header"];
      $this->footer = $data["footer"];
      $this->body = $data["body"];
      $this->filename = $data["file_name"];
      $this->templatename = $data["filename"];
      $this->pagebreak = '<pagebreak orientation="'.$data["orientation"].'" margin-left="'.($data["margin_left"] * 10).'mm" margin-right="'.($data["margin_right"] * 10).'mm" margin-top="0mm" margin-bottom="0mm" margin-header="'.($data["margin_top"] * 10).'mm" margin-footer="'.($data["margin_bottom"] * 10).'mm" />';
    }

    protected function getIgnoredPicklistValues() {
    	global  $table_prefix;
      $sql = "SELECT value FROM ".$table_prefix."_pdfmaker_ignpickvals";	//crmv@19166
      $result = $this->db->query($sql);
      while($row = $this->db->fetchByAssoc($result)) {
        $this->ignored_picklist_values[] = $row["value"];
      }
    }

    function getInventoryProducts() {
    	global $table_prefix;
      $taxtype = $this->getInventoryTaxTypeCustom();
      $currencytype = $this->getInventoryCurrencyInfoCustom();

      $InventoryUtils = InventoryUtils::getInstance(); // crmv@42024

	//crmv@19166 crmv@150773
      $query="select case when ".$table_prefix."_products.productid != '' then ".$table_prefix."_products.productname else ".$table_prefix."_service.servicename end as productname," .
            " case when ".$table_prefix."_products.productid != '' then ".$table_prefix."_products.productid else ".$table_prefix."_service.serviceid end as psid," .
            " case when ".$table_prefix."_products.productid != '' then 'Products' else 'Services' end as entitytype," .
            " case when ".$table_prefix."_products.productid != '' then ".$table_prefix."_products.unit_price else ".$table_prefix."_service.unit_price end as unit_price," .
            " case when ".$table_prefix."_products.productid != '' then ".$table_prefix."_products.usageunit else ".$table_prefix."_service.service_usageunit end as usageunit," .
            " case when ".$table_prefix."_products.productid != '' then ".$table_prefix."_products.qty_per_unit else ".$table_prefix."_service.qty_per_unit end as qty_per_unit," .
            " case when ".$table_prefix."_products.productid != '' then ".$table_prefix."_products.qtyinstock else NULL end as qtyinstock," .
            " case when ".$table_prefix."_products.productid != '' then ".$table_prefix."_products.description else ".$table_prefix."_service.description end as psdescription, ".$table_prefix."_inventoryproductrel.* " .
            " from ".$table_prefix."_inventoryproductrel" .
            " left join ".$table_prefix."_products on ".$table_prefix."_products.productid=".$table_prefix."_inventoryproductrel.productid " .
            " left join ".$table_prefix."_crmentity c1 on c1.crmid = ".$table_prefix."_products.productid " .
            " left join ".$table_prefix."_service on ".$table_prefix."_service.serviceid=".$table_prefix."_inventoryproductrel.productid " .
            " left join ".$table_prefix."_crmentity c2 on c2.crmid = ".$table_prefix."_service.serviceid " .
            " where id=? ORDER BY sequence_no";
	//crmv@19166e crmv@150773e

      $result = $this->db->pquery($query, array($this->focus->id));
      $num_rows=$this->db->num_rows($result);
      $netTotal = 0.0;
      $sumwithoutvat = 0.0;

      //product images
      $images = $this->getInventoryImages();
      //end

      for($i=1; $i<=$num_rows; $i++)
      {
        $sub_prod_query = $this->db->pquery("SELECT productid from ".$table_prefix."_inventorysubproductrel WHERE id=? AND sequence_no=?",array($this->focus->id,$i));
        $subprodname_str='';
        if($this->db->num_rows($sub_prod_query)>0){
          for($j=0;$j<$this->db->num_rows($sub_prod_query);$j++){
            $sprod_id = $this->db->query_result($sub_prod_query,$j,"productid");
            $sprod_name = getProductName($sprod_id);
            $str_sep = "";
            if($j>0) $str_sep = ":";
            $subprodname_str .= $str_sep." - ".$sprod_name;
          }
        }
        $subprodname_str = str_replace(":","<br>",$subprodname_str);

        $psid = $this->db->query_result($result,$i-1,"psid");
        $psno = $this->db->query_result($result,$i-1,"psno");
        $productid=$this->db->query_result($result,$i-1,"productid");
        $entitytype=$this->db->query_result($result,$i-1,"entitytype");
        $producttitle = $productname= $this->db->query_result($result,$i-1,"productname");
        if($subprodname_str!="") $productname .= "<br/><span style='color:#C0C0C0;font-style:italic;'>".$subprodname_str."</span>";
        $comment=$this->db->query_result($result,$i-1,"comment");
        $psdescription =$this->db->query_result($result,$i-1,"psdescription");
        $inventory_prodrel_desc = $this->db->query_result($result,$i-1,"description");

        $qtyinstock=$this->db->query_result($result,$i-1,"qtyinstock");
        $qty=$this->db->query_result($result,$i-1,"quantity");
        $qty_per_unit=$this->db->query_result($result,$i-1,"qty_per_unit");
        $usageunit=$this->db->query_result($result,$i-1,"usageunit");
        $unitprice=$this->db->query_result($result,$i-1,"unit_price");
        $listprice=$this->db->query_result($result,$i-1,"listprice");
        $discount_percent = $this->db->query_result($result,$i-1,"discount_percent");
        $discount_amount = $this->db->query_result($result,$i-1,"discount_amount");
		$total = $qty*$listprice; //crmv@65801


        $prodinfo = array(
        	'listprice' => floatval($listprice),
        	'quantity' => floatval($qty),
        	'discount_percent' => $discount_percent,
        	'discount_amount' => $discount_amount,
        	'taxes' => array(),
        );

        if ($taxtype == "individual") {
        	$tax_details = $InventoryUtils->getTaxDetailsForProduct($productid,"all");
        	for ($tax_count=0; $tax_count<count($tax_details); ++$tax_count) {
        		$tax_name = $tax_details[$tax_count]["taxname"];
        		$tax_value = $InventoryUtils->getInventoryProductTaxValue($this->focus->id, $productid, $tax_name);
        		$prodinfo['taxes'][$tax_name] = $tax_value;
        	}
        }

        // calculate prices
        $prodPrices = $InventoryUtils->calcProductTotals($prodinfo);

        // retrieve discounts
        $productDiscountPercent = "";
        if ($discount_percent != "NULL" && $discount_percent != "") {
        	$productDiscountPercent_tmp = array();
			if (!empty($prodPrices['discounts'])) {
				foreach ($prodPrices['discounts'] as $discountInfo) {
					$productDiscountPercent_tmp[] = $this->formatNumberToPDF($discountInfo['percentage']);
				}
			}
			$productDiscountPercent = implode('+',$productDiscountPercent_tmp);
        }
        $productDiscount = $prodPrices['total_discount'];
        $totalAfterDiscount = $prodPrices['price_discount'];

		// taxes

        //Calculate the individual tax if taxtype is individual
        $taxtotal = $prodPrices['total_taxes'];
        if ($taxtype == "individual") {


          for ($tax_count=0; $tax_count<count($tax_details); ++$tax_count) {
            $tax_name = $tax_details[$tax_count]["taxname"];
            $tax_label = $tax_details[$tax_count]["taxlabel"];
            $tax_value = $prodPrices['taxes'][$tax_count]['percentage'];

            // vatblock - sum all the taxes for every product
            if ($tax_name != "" || $tax_value > 0) {
			  $vatblock[$tax_name."-".$tax_value]["label"] = $tax_label;
              $vatblock[$tax_name."-".$tax_value]["netto"] += $totalAfterDiscount;
	          $vatblock[$tax_name."-".$tax_value]["vat"] += $prodPrices['taxes'][$tax_count]['amount'];
	          $vatblock[$tax_name."-".$tax_value]["value"] += $prodPrices['taxes'][$tax_count]['percentage'];
            }

          }

          $Details["P"][$i]["PRODUCTVATPERCENT"] = $this->formatNumberToPDF($prodPrices['total_taxes_perc']);
          $Details["P"][$i]["PRODUCTVATSUM"] = $this->formatNumberToPDF($taxtotal);
        }

        $netprice = $prodPrices['price_taxes'];

        if ($entitytype == "Products") {
	        $Details["P"][$i]["PRODUCTS_CRMID"] = $psid;
	        $Details["P"][$i]["SERVICES_CRMID"] = "";
        } else {
			$Details["P"][$i]["PRODUCTS_CRMID"] = "";
	        $Details["P"][$i]["SERVICES_CRMID"] = $psid;
		}

		$Details["P"][$i]["PS_CRMID"] = $psid;
		$Details["P"][$i]["PS_NO"] = $psno;

        //crmv@57957
        if ($comment != "") {
            $comment = str_replace("\\n", "<br>", nl2br($comment));
            $productname .= "<br /><small>".$comment."</small>";
        }

        $Details["P"][$i]["PRODUCTNAME"] = $productname;
        $Details["P"][$i]["PRODUCTTITLE"] = $producttitle;

        $psdescription = str_replace("\\n", "<br>", nl2br($psdescription));
        $Details["P"][$i]["PRODUCTDESCRIPTION"] = $psdescription;

        $Details["P"][$i]["PRODUCTEDITDESCRIPTION"] = $comment;

        $inventory_prodrel_desc = str_replace("\\n", "<br>", nl2br($inventory_prodrel_desc));
		$Details["P"][$i]["CRMNOWPRODUCTDESCRIPTION"] = $inventory_prodrel_desc;
		$Details["P"][$i]["PRODUCTINVDESCRIPTION"] = $inventory_prodrel_desc;	//crmv@23798
		//crmv@57957e

        $Details["P"][$i]["PRODUCTLISTPRICE"] = $this->formatNumberToPDF($listprice);
        $Details["P"][$i]["PRODUCTTOTAL"] = $this->formatNumberToPDF($total);
        $Details["P"][$i]["PRODUCTQUANTITY"] = $this->formatNumberToPDF($qty);
        $Details["P"][$i]["PRODUCTQINSTOCK"] = $this->formatNumberToPDF($qtyinstock);//missing
        $Details["P"][$i]["PRODUCTPRICE"] = $this->formatNumberToPDF($unitprice);//missing
        $Details["P"][$i]["PRODUCTPOSITION"] = $i;
        $Details["P"][$i]["PRODUCTQTYPERUNIT"] = $this->formatNumberToPDF($qty_per_unit);//missing
        $Details["P"][$i]["PRODUCTUSAGEUNIT"] = $this->getTranslatedStringCustom($usageunit,"Products/Services");
        $Details["P"][$i]["PRODUCTDISCOUNT"] = $this->formatNumberToPDF($productDiscount);
        $Details["P"][$i]["PRODUCTDISCOUNTPERCENT"] = $productDiscountPercent;	//crmv@2539m
        $Details["P"][$i]["PRODUCTSTOTALAFTERDISCOUNTSUM"] = $totalAfterDiscount;//missing
        $Details["P"][$i]["PRODUCTSTOTALAFTERDISCOUNT"] = $this->formatNumberToPDF($totalAfterDiscount);
        $Details["P"][$i]["PRODUCTTOTALSUM"] = $this->formatNumberToPDF($netprice);

        //product images
        $sequence = $this->db->query_result($result,$i-1,"sequence_no");
        $Details["P"][$i]["PRODUCTSEQUENCE"] = $sequence;
        if(isset($images[$productid."_".$sequence])) {
          $width = $height = "";

          if ($images[$productid."_".$sequence]["width"]>0)
            $width=" width='".$images[$productid."_".$sequence]["width"]."' ";
          if ($images[$productid."_".$sequence]["height"]>0)
            $height=" height='".$images[$productid."_".$sequence]["height"]."' ";
          $Details["P"][$i]["PRODUCTS_IMAGENAME"] = "<img src='".$images[$productid."_".$sequence]["src"]."' ".$width.$height."/>"; // crmv@140808
        }
        //end

        $focus_p = CRMEntity::getInstance("Products");
        if ($entitytype == "Products" && $psid != "") {
          $focus_p->id = $psid;
          $this->retrieve_entity_infoCustom($focus_p,$psid,"Products");
          //$focus_p->retrieve_entity_info($psid,"Products");
        }
        $Array_P = $this->replaceFieldsToContent("Products", $focus_p, false, true);
        $Details["P"][$i] = array_merge($Array_P,$Details["P"][$i]);
        unset($focus_p);

        $focus_s = CRMEntity::getInstance("Services");
        if ($entitytype == "Services" && $psid != "") {
          $focus_s->id = $psid;
          $this->retrieve_entity_infoCustom($focus_s,$psid,"Services");
          //$focus_s->retrieve_entity_info($psid,"Services");
        }
        $Array_S = $this->replaceFieldsToContent("Services", $focus_s, false, true);
        $Details["P"][$i] = array_merge($Array_S,$Details["P"][$i]);
        unset($focus_s);

        // totalwithoutvat fot totals
        $sumwithoutvat += $totalAfterDiscount;
        $netTotal += $netprice;
      }

      // TOTALS

      // populate array for calculations
      $totalinfo = array(
      	'nettotal' => floatval($netTotal),
      	's_h_amount' => floatval($this->focus->column_fields["hdnS_H_Amount"]),
      	'discount_percent' => $this->focus->column_fields["hdnDiscountPercent"],
      	'discount_amount' => $this->focus->column_fields["hdnDiscountAmount"],
      	'adjustment' => floatval($this->focus->column_fields['txtAdjustment']),
      	'taxes' => array(),
      	'shtaxes' => array(),
      );

      // populate taxes for calculations
      if ($taxtype == "group") {
      	$tax_details = $InventoryUtils->getAllTaxes("available","","edit",$this->focus->id);
   		foreach ($tax_details as $taxinfo) {
  			$tax_name = $taxinfo['taxname'];
   			$tax_percent = $this->db->query_result($result,0,$tax_name);
   			$totalinfo['taxes'][$tax_name] = $tax_percent;
   		}
      }

      // populate sh taxes for calculateions
      $shtax_details = $InventoryUtils->getAllTaxes("available","sh","edit",$this->focus->id);
      foreach ($shtax_details as $taxinfo) {
      	$shtax_name = $taxinfo['taxname'];
      	$shtax_percent = $InventoryUtils->getInventorySHTaxPercent($this->focus->id,$shtax_name);
      	$totalinfo['shtaxes'][$shtax_name] = $shtax_percent;
      }

      // calculate totals
      $totalPrices = $InventoryUtils->calcInventoryTotals($totalinfo);

      // discounts
      $finalDiscountPercent = "";
      //if($focus->column_fields['hdnDiscountPercent'] != '') - previously (before changing to prepared statement) the selected option (either percent or amount) will have value and the other remains empty. So we can find the non selected item by empty check. But now with prepared statement, the non selected option stored as 0
      if ($this->focus->column_fields["hdnDiscountPercent"] != "0") {
      	$productDiscountPercent_tmp = array();
		foreach ($totalPrices['discounts'] as $discountInfo) {
			$productDiscountPercent_tmp[] = $this->formatNumberToPDF($discountInfo['percentage']);
		}
		$finalDiscountPercent = implode('+',$productDiscountPercent_tmp);

      }

      $finalDiscount = $totalPrices['total_discount'];

      // taxes
      $vat_value = 0.0;
      $taxtotal = $totalPrices['total_taxes'];
      if ($taxtype == "group") {

        for ($tax_count=0; $tax_count<count($tax_details); ++$tax_count) {
          $tax_name = $tax_details[$tax_count]["taxname"];
          $tax_value = $totalPrices['taxes'][$tax_count]['percentage'];

          if ($tax_name != "" || $tax_value > 0) {
              $vatblock[$tax_name]["label"] = $tax_details[$tax_count]["taxlabel"];
              $vatblock[$tax_name]["netto"] = $totalPrices['price_discount'];
	          $vatblock[$tax_name]["vat"] = $totalPrices['taxes'][$tax_count]['amount'];
			  $vatblock[$tax_name]["value"] = $tax_value;
          }
        }
        $total_vat_percent = $totalPrices['total_taxes_perc'];
        $vat_value = $taxtotal;

        // info tax value and total with dph for every product
        foreach ($Details["P"] as $keyP=>$valueP) {
        	// this is the only calculation left here
          $productvatsum = ($Details["P"][$keyP]["PRODUCTSTOTALAFTERDISCOUNTSUM"] * $total_vat_percent) / 100;
          $producttotalsum = $Details["P"][$keyP]["PRODUCTSTOTALAFTERDISCOUNTSUM"] + $productvatsum;

          $Details["P"][$keyP]["PRODUCTVATPERCENT"] = $this->formatNumberToPDF($total_vat_percent);
          $Details["P"][$keyP]["PRODUCTVATSUM"] = $this->formatNumberToPDF($productvatsum);
          $Details["P"][$keyP]["PRODUCTTOTALSUM"] = $this->formatNumberToPDF($producttotalsum);
        }
        // info tax end

      } else {
      	// individual taxes, with vatblock - sum everything
        if (is_array($vatblock) && count($vatblock) > 0) {
			foreach ($vatblock as $keyM => $valueM) $vat_value += $valueM["vat"];
		}
      }

      // SH amount and taxes
      $shAmount = ($this->focus->column_fields["hdnS_H_Amount"] != "") ? $this->focus->column_fields["hdnS_H_Amount"] : "0.00";
      $shtaxtotal = $totalPrices['total_shtaxes'];

      $totalafterdiscount = $sumwithoutvat - $finalDiscount;
      $totalwithvat = $totalafterdiscount + $vat_value;

      $Details["TOTAL"]["NETTOTAL"] = $this->formatNumberToPDF($netTotal);
      $Details["TOTAL"]["TOTALWITHOUTVAT"] = $this->formatNumberToPDF($sumwithoutvat);
      $Details["TOTAL"]["FINALDISCOUNT"] = $this->formatNumberToPDF($finalDiscount);
      $Details["TOTAL"]["FINALDISCOUNTPERCENT"] = $finalDiscountPercent;//crmv@2539m
      $Details["TOTAL"]["TOTALAFTERDISCOUNT"] = $this->formatNumberToPDF($totalafterdiscount);
      $Details["TOTAL"]["TAXTOTAL"] = $this->formatNumberToPDF($vat_value);

      $Details["TOTAL"]["TAXTOTALPERCENT"] = $this->formatNumberToPDF($total_vat_percent);//to get total vat percent
      $Details["TOTAL"]["TOTALWITHVAT"] = $this->formatNumberToPDF($totalwithvat);
      $Details["TOTAL"]["SHTAXAMOUNT"] = $this->formatNumberToPDF($shAmount);
      $Details["TOTAL"]["SHTAXTOTAL"] = $this->formatNumberToPDF($shtaxtotal);
      $Details["TOTAL"]["VATBLOCK"] = $vatblock;
      return $Details;
    }

    protected function getInventoryBreaklines()
    {
    	global $table_prefix;
      $sql="SELECT productid, sequence, show_header, show_subtotal FROM ".$table_prefix."_pdfmaker_breakline WHERE crmid=?";
      $res = $this->db->pquery($sql,array($this->focus->id));
      $products=array();
      $show_header=0;
      $show_subtotal=0;
      while($row=$this->db->fetchByAssoc($res)){
        $products[$row["productid"]."_".$row["sequence"]] = $row["sequence"];
        $show_header=$row["show_header"];
        $show_subtotal=$row["show_subtotal"];
      }
      $output["products"] = $products;
      $output["show_header"] = $show_header;
      $output["show_subtotal"] = $show_subtotal;
      return $output;
    }

    protected function getInventoryImages()
    {
    	global $table_prefix;
      $sql="SELECT ".$table_prefix."_attachments.path, ".$table_prefix."_attachments.name, ".$table_prefix."_attachments.attachmentsid, productid, sequence, width, height
            FROM ".$table_prefix."_pdfmaker_images
            INNER JOIN ".$table_prefix."_attachments
              ON ".$table_prefix."_attachments.attachmentsid=".$table_prefix."_pdfmaker_images.attachmentid
            INNER JOIN ".$table_prefix."_crmentity
              ON ".$table_prefix."_attachments.attachmentsid=".$table_prefix."_crmentity.crmid
            WHERE deleted=0 AND ".$table_prefix."_pdfmaker_images.crmid=?";
      $res = $this->db->pquery($sql,array($this->focus->id));
      $products=array();
      while($row=$this->db->fetchByAssoc($res)){
        $products[$row["productid"]."_".$row["sequence"]]["src"] = $row["path"].$row["attachmentsid"]."_".$row["name"];
        $products[$row["productid"]."_".$row["sequence"]]["width"] = $row["width"];
        $products[$row["productid"]."_".$row["sequence"]]["height"] = $row["height"];
      }

      return $products;
    }

    protected function getContactImage($id)
    {
      global $table_prefix;
      if(isset($id) AND $id!=""){
        $query="SELECT ".$table_prefix."_attachments.path, ".$table_prefix."_attachments.name, ".$table_prefix."_attachments.attachmentsid
              FROM ".$table_prefix."_contactdetails
              INNER JOIN ".$table_prefix."_seattachmentsrel
                ON ".$table_prefix."_contactdetails.contactid=".$table_prefix."_seattachmentsrel.crmid
              INNER JOIN ".$table_prefix."_attachments
                ON ".$table_prefix."_attachments.attachmentsid=".$table_prefix."_seattachmentsrel.attachmentsid
              INNER JOIN ".$table_prefix."_crmentity
                ON ".$table_prefix."_attachments.attachmentsid=".$table_prefix."_crmentity.crmid
              WHERE deleted=0 AND ".$table_prefix."_contactdetails.contactid=?";

        $result = $this->db->pquery($query, array($id));
        $num_rows = $this->db->num_rows($result);
        if ($num_rows > 0)
        {
          $image_src = $this->db->query_result($result,0,"path").$this->db->query_result($result,0,"attachmentsid")."_".$this->db->query_result($result,0,"name");
          $image = "<img src='".$this->site_url."/".$image_src."'/>";
          return $image;
        }
      } else {
        return "";
      }
    }

	//crmv@32962
    protected function getProductImage($id)
    {
      global $table_prefix;
      if(isset($id) AND $id!=""){
        $query="SELECT ".$table_prefix."_attachments.path, ".$table_prefix."_attachments.name, ".$table_prefix."_attachments.attachmentsid
              FROM ".$table_prefix."_products
              INNER JOIN ".$table_prefix."_seattachmentsrel
                ON ".$table_prefix."_products.productid=".$table_prefix."_seattachmentsrel.crmid
              INNER JOIN ".$table_prefix."_attachments
                ON ".$table_prefix."_attachments.attachmentsid=".$table_prefix."_seattachmentsrel.attachmentsid
              INNER JOIN ".$table_prefix."_crmentity
                ON ".$table_prefix."_attachments.attachmentsid=".$table_prefix."_crmentity.crmid
              WHERE deleted=0 AND ".$table_prefix."_products.productid=?";

        $result = $this->db->pquery($query, array($id));
        $num_rows = $this->db->num_rows($result);
        if ($num_rows > 0)
        {
          $image_src = $this->db->query_result($result,0,"path").$this->db->query_result($result,0,"attachmentsid")."_".$this->db->query_result($result,0,"name");
          $image = "<img src='".$this->site_url."/".$image_src."'/>";
          return $image;
        }
      } else {
        return "";
      }
    }
    //crmv@32962e

    //utils functions which needed to be customized
    protected function getOwnerNameCustom($id) {
    	global $table_prefix;
      if($id != "") {
        $sql = "SELECT user_name FROM ".$table_prefix."_users WHERE id=?";
        $result = $this->db->pquery($sql, array($id));
        $ownername = $this->db->query_result($result,0,"user_name");
      }
      if($ownername == "") {
          $sql = "SELECT groupname FROM ".$table_prefix."_groups WHERE groupid=?";
          $result = $this->db->pquery($sql, array($id));
          $ownername = $this->db->query_result($result,0,"groupname");
      }else {
      	//crmv@126096
      	global $showfullusername;
      	$ownername = getUserName($id,$showfullusername);
      	//crmv@126096e
      }
      return $ownername;
    }

    protected function getAccountNo($account_id) {
    	global $table_prefix;
      $accountno = "";
      if($account_id != '') {
        $sql = "SELECT account_no FROM ".$table_prefix."_account WHERE accountid=?";
        $result = $this->db->pquery($sql, array($account_id));
        $accountno = $this->db->query_result($result,0,"account_no");
      }
      return $accountno;
    }

    protected function getTranslatedStringCustom($str,$emodule) {
      if($emodule!="Products/Services") {
        $app_lang = return_application_language($this->language);
        $mod_lang = return_specified_module_language($this->language,$emodule);
      }
      else {
        $app_lang = return_specified_module_language($this->language,"Services");
        $mod_lang = return_specified_module_language($this->language,"Products");
      }

      $trans_str = ($mod_lang[$str] != '') ? $mod_lang[$str] : (($app_lang[$str] != '') ? $app_lang[$str] : $str);
      return $trans_str;
    }

    protected function getInventoryCurrencyInfoCustom() {
      global $table_prefix;
	  $inventory_table = $this->inventory_table_array[$this->module];
      $inventory_id = $this->inventory_id_array[$this->module];
      $res = $this->db->pquery("SELECT currency_id, ".$inventory_table.".conversion_rate AS conv_rate, ".$table_prefix."_currency_info.*
                           FROM ".$inventory_table."
                           INNER JOIN ".$table_prefix."_currency_info ON ".$inventory_table.".currency_id = ".$table_prefix."_currency_info.id
                           WHERE ".$inventory_id."=?", array($this->focus->id));

      $currency_info = array();
      $currency_info["currency_id"] = $this->db->query_result($res,0,"currency_id");
      $currency_info["conversion_rate"] = $this->db->query_result($res,0,"conv_rate");
      $currency_info["currency_name"] = $this->db->query_result($res,0,"currency_name");
      $currency_info["currency_code"] = $this->db->query_result($res,0,"currency_code");
      $currency_info["currency_symbol"] = $this->db->query_result($res,0,"currency_symbol");

	  return $currency_info;
    }

    protected function getInventoryTaxTypeCustom() {
      $res = $this->db->pquery("SELECT taxtype FROM ".$this->inventory_table_array[$this->module]." WHERE ".$this->inventory_id_array[$this->module]."=?", array($this->focus->id));
      $taxtype = $this->db->query_result($res,0,'taxtype');
      return $taxtype;
    }

    protected function formatNumberToPDF($value) {
      if(is_numeric($value)){
        $number = number_format($value,  $this->decimals,  $this->decimal_point,  $this->thousands_separator);
      } else {
        $number = "";
      }

      return $number;
    }

	// crmv@205306
	protected function retrieve_entity_infoCustom(&$focus, $record, $module) {
		$focus->id = $record;
		$focus->retrieve_entity_info_no_html($record, $module);
	}
	// crmv@205306e

    protected function getTermsAndConditionsCustom($value) {
    	global $table_prefix;
      if(file_exists("modules/Settings/EditTermDetails.php")) {
        $sql = "SELECT tandc FROM ".$table_prefix."_inventory_tandc WHERE id='".$value."'";
        $res = $this->db->query($sql);
        $num = $this->db->num_rows($res);
        if($num>0)
          $tandc = $this->db->query_result($res,0,"tandc");
        else
          $tandc = $value;
      }
      else
        $tandc = $value;

      return $tandc;
    }

    protected function getTicketCommentsCustom($efocus) {
		global $table_prefix;
      $commentlist="";
      $prefix="";
      if($efocus->column_fields["record_module"] == "HelpDesk"){
        $prefix="ticket";
      }elseif($efocus->column_fields["record_module"] == "Faq") {
        $prefix="faq";
      }

      if($prefix!="") {
        $mod_lang = return_specified_module_language($this->language,  $efocus->column_fields["record_module"]);
        $sql="SELECT * FROM ".$table_prefix."_".$prefix."comments WHERE ".$prefix."id=".$efocus->id;
        $result=$this->db->query($sql);
        while($row = $this->db->fetchByAssoc($result)) {
          $comment = $row["comments"];
          $crtime = getValidDisplayDate($row["createdtime"]);
          $body="";
          if($prefix=="ticket"){
            //crmv@101102
			if($row["ownertype"] == 'customer'){
				$author = $this->getCustomerNameCustom($row["ownerid"]);
			} else {
				$author = $this->getOwnerNameCustom($row["ownerid"]);
			}
			//crmv@101102e
            $body=$comment."<br />".$mod_lang["LBL_AUTHOR"].": ".$author."<br />".$mod_lang["Created Time"].": ".$crtime."<br /><br />";
          }
          else {
            $body=$comment."<br />".$mod_lang["Created Time"].": ".$crtime."<br /><br />";
          }
          $commentlist.=$body;
        }
      }

      return $commentlist;
    }

	//crmv@101102
	protected function getCustomerNameCustom($id){
		global $table_prefix;
		
		$customername = '';
		
		$sql="SELECT lastname,firstname FROM ".$table_prefix."_contactdetails WHERE contactid=".$id;
		$res = $this->db->query($sql);
		if($res){
			$customername = $this->db->query_result($res,0,'lastname').' '.$this->db->query_result($res,0,'firstname');
		}
		return $customername;
	}
	//crmv@101102e

    protected function getFolderName($folderid) {
		global $table_prefix;
		$sql="SELECT foldername FROM ".$table_prefix."_crmentityfolder WHERE folderid=".$folderid; // crmv@30967
		return $foldername = $this->db->query_result($this->db->query($sql),0,"foldername");
    }

    protected function replaceCustomFunctions(){
		if(is_numeric(strpos($this->content, '[CUSTOMFUNCTION|'))){
			require_once("classes/simple_html_dom.php");
		    foreach (glob('modules/PDFMaker/functions/*.php') as $file) {
				include_once $file;
		    }
			$this->replacements["[CUSTOMFUNCTION|"]="<customfunction>";
			$this->replacements["|CUSTOMFUNCTION]"]="</customfunction>";
			$this->replaceContent();
			$html = str_get_html($this->content);
			foreach($html->find("customfunction") as $customfunction) {
				//$Params = explode("|", trim($customfunction->plaintext));
				$Params = $this->getCustomfunctionParams(trim($customfunction->plaintext));
				$func = $Params[0];
				unset($Params[0]);
				$replacement = call_user_func_array($func,$Params);
				$customfunction->outertext = html_entity_decode($replacement); //crmv@27854
			}
			$this->content = $html->save();
    	  	$html->clear(); ////crmv@58635
    	  	unset($html); ////crmv@58635			
    	}
	}

    protected function getCustomfunctionParams($val)
    {
      $Params = array();
      $end = false;

      do {
          if (strstr($val, '|'))
          {
             if ($val[0] == '"')
             {
                $delimiter = '"|';
                $val = substr($val, 1);
             }
             elseif (substr($val,0,6) == '&quot;')
             {
                $delimiter = '&quot;|';
                $val = substr($val, 6);
             }
             else
                 $delimiter = '|';

             list($Params[],$val) = explode($delimiter,$val,2);
          }
          else
          {
             $Params[] = $val;
             $end = true;
          }
      } while (!$end);

      return $Params;
    }

    public function getFilename() {
        $this->content = $this->filename;
        $this->replacements["$#TEMPLATE_NAME#$"]=$this->templatename;
        $this->replacements["$#DD-MM-YYYY#$"]=date("d-m-Y");
        $this->replacements["$#MM-DD-YYYY#$"]=date("m-d-Y");
        $this->replacements["$#YYYY-MM-DD#$"]=date("Y-m-d");
        $this->replacements["$".strtoupper($this->module)."_CRMID$"]=$this->focus->id;
        $this->replaceFieldsToContent($this->module, $this->focus);

        $this->replacements=array();
        $this->replacements["\r\n"]="";
        $this->replacements["\n\r"]="";
        $this->replacements["\n"]="";
        $this->replacements["\r"]="";
        $this->replaceContent();

        return substr(generate_cool_uri(strip_tags(html_entity_decode($this->content,ENT_QUOTES,"utf-8"))),0,255);
    }

    protected function itsmd($val) {
      return md5($val);
    }

    protected function convertRelatedBlocks()
    {
    	global $table_prefix;
      include_once("modules/PDFMaker/RelBlockRun.php");

	  if(!isset($this->picklist_fields)) $this->picklist_fields = array(); //crmv@97759

      if(strpos($this->content,"#RELBLOCK") !== false)
      {
          preg_match_all("|#RELBLOCK([0-9]+)_START#|U", $this->content, $RelatedBlocks, PREG_PATTERN_ORDER);

          if (count($RelatedBlocks[1]) > 0)
          {
              $ConvertRelBlock = array();
              foreach($RelatedBlocks[1] AS $r => $relblockid)
              {
                  if (!in_array($relblockid,$ConvertRelBlock))
                  {
                      $sql_rb = "SELECT secmodule FROM ".$table_prefix."_pdfmaker_relblocks WHERE relblockid = '".$relblockid."'";
                      $secmodule = $this->db->getOne($sql_rb,0,"secmodule");

						//crmv@25443
						global $adb;
						$secmoduleInstance = Vtecrm_Module::getInstance($secmodule);
						if (empty($this->reference_fields[$secmodule])) {
							$referenceUitype = array();
							$res = $adb->query("SELECT uitype FROM ".$table_prefix."_ws_fieldtype WHERE fieldtype = 'reference'");
							if ($res) {
								while($row=$adb->fetchByAssoc($res)) {
									$referenceUitype[] = $row['uitype'];
								}
							}
							$res = $adb->query("SELECT fieldname from ".$table_prefix."_field WHERE uitype in (".implode(',',$referenceUitype).") and tabid = ".$secmoduleInstance->id);
							if ($res) {
								while($row=$adb->fetchByAssoc($res)) {
									$this->reference_fields[$secmodule][] = $row['fieldname'];
								}
							}
						}
						//TODO: multipicklist_fields
						if (empty($this->picklist_fields[$secmodule])) {
							$picklistUitype = array();
							$res = $adb->query("SELECT uitype FROM ".$table_prefix."_ws_fieldtype WHERE fieldtype IN ('picklist')");
							if ($res) {
								while($row=$adb->fetchByAssoc($res)) {
									$picklistUitype[] = $row['uitype'];
								}
							}
							$picklistres = $adb->query("SELECT fieldname from ".$table_prefix."_field WHERE uitype in (".implode(',',$picklistUitype).") and tabid = ".$secmoduleInstance->id);
							if($picklistres) {
								while($row=$adb->fetchByAssoc($picklistres)) {
									$this->picklist_fields[$secmodule][] = $row['fieldname'];
								}
							}
						}
						//crmv@25443e
						
						//crmv@126096 : TODO use _ws_fieldtype
						$Checkboxes = array();
						$Picklists = array();
						$Picklists_ml = array();
						$Textareas = array();
						$Datefields = array();
						$DateTimefields = array();
						$Multipicklists = array();
						$NumbersField = array();
						$UserField = array();
						$sdk_uitypes = array();
						$TimerField = array();
						
						$sql = "SELECT fieldname, uitype FROM ".$table_prefix."_field WHERE tabid = '".$secmoduleInstance->id."'";
						$result = $this->db->query($sql);
						while($row = $this->db->fetchByAssoc($result))
						{
							if ($row["uitype"] == "19" || $row["uitype"] == "20" || $row["uitype"] == "21" || $row["uitype"] == "24")
								$Textareas[] = $row["fieldname"];
							elseif ($row["uitype"] == "5" || $row["uitype"] == "23" || $row["uitype"] == "70") {
								if ($row["uitype"] == "5") {
									$Datefields[] = $row["fieldname"];
								} else {
									$DateTimefields[] = $row["fieldname"];
								}
							} elseif ($row["uitype"] == "15" || $row["fieldname"] == "salutationtype")
								$Picklists[] = $row["fieldname"];
							elseif ($row["uitype"] == "1015")
								$Picklists_ml[] = $row["fieldname"];
							elseif ($row["uitype"] == "56")
								$Checkboxes[] = $row["fieldname"];
							elseif ($row["uitype"] == "33")
								$Multipicklists[] = $row["fieldname"];
							elseif ($row["uitype"] == "71" || $row["uitype"] == "7")
								$NumbersField[] = $row["fieldname"];
							elseif (in_array($row["uitype"],array('50','51','52','53','77')))
								$UserField[] = $row["fieldname"];
							elseif(SDK::isUitype($row["uitype"]))
								$sdk_uitypes[$row["fieldname"]]=$row["uitype"];
							elseif ($row["uitype"] == "1020")
								$TimerField[] = $row["fieldname"];
						}
						//crmv@126096e

                      if(strpos($this->content,"#RELBLOCK".$relblockid."_START#") !== false)
                      {
                          if (strpos($this->content,"#RELBLOCK".$relblockid."_END#") !== false)
                          {
                              $tableDOM = $this->convertRelatedBlock($relblockid);

                              $oRelBlockRun = new RelBlockRun($this->focus->id, $relblockid, $this->module, $secmodule);

                              $RelBlock_Data = $oRelBlockRun->GenerateReport();

                              $ExplodedPdf = array();
                              $Exploded = explode("#RELBLOCK".$relblockid."_START#",$this->content);
                              $ExplodedPdf[] = $Exploded[0];
                              for($iterator=1;$iterator<count($Exploded);$iterator++){
                                $SubExploded = explode("#RELBLOCK".$relblockid."_END#",$Exploded[$iterator]);
                                foreach($SubExploded as $part)
                                  $ExplodedPdf[] = $part;
                                $highestpartid = $iterator*2-1;
                                $ProductParts[$highestpartid]=$ExplodedPdf[$highestpartid];
                                $ExplodedPdf[$highestpartid]='';
                              }

                              if (count($RelBlock_Data) > 0)
                              {
                                  foreach ($RelBlock_Data AS $RelBlock_i => $RelBlock_Details)
                                  {
                                      foreach($ProductParts as $productpartid=>$productparttext)
                                      {
                                          $show_line = false;
                                          //crmv@126096
                                          foreach ($RelBlock_Details AS $fieldname => $value)
                                          {
                                          		//crmv@60349 crmv@97136 crmv@25443 crmv@106527 crmv@26202 crmv@27014 crmv@65492 - 28
                                          		if (in_array($fieldname,$UserField) && is_numeric($value)) {
													$value = $this->getOwnerNameCustom($value);
												} elseif($fieldname == "terms_conditions")
													$value = $this->getTermsAndConditionsCustom($value);
												elseif($fieldname == "comments")
													$value = $this->getTicketCommentsCustom($efocus);
												elseif($fieldname == "folderid")
													$value = $this->getFolderName($value);
												elseif (in_array($fieldname,(array)$this->reference_fields[$secmodule]) && $value != '' && $value != '-') {
													$type = getSalesEntityType($value);
													$tmp = getEntityName($type,$value);
													if (!empty($tmp)) {
														foreach($tmp as $key=>$val){
															$value = $val;
															break;
														}
													}
												}
												elseif(in_array($fieldname, $Datefields)) {
													$value = substr(getValidDisplayDate($value),0,10);
												} elseif(in_array($fieldname, $DateTimefields)) {
													$value = getValidDisplayDate($value);
												}
												elseif(in_array($fieldname, $Picklists)) {
													if(!in_array(trim($value), $this->ignored_picklist_values))
														$value = $this->getTranslatedStringCustom($value,$secmodule); //crmv@140504
													else
														$value = "";
												}
												elseif(in_array($fieldname, $Picklists_ml)) {
													if(!in_array(trim($value), $this->ignored_picklist_values))
														$value = Picklistmulti::getTranslatedPicklist($value, $fieldname, $this->language);
													else
														$value = "";
												}
												elseif(in_array($fieldname, $Checkboxes)) {
													$pdf_app_strings = return_application_language($this->language);
													if($value == 1 || $value == getTranslatedString("yes")) // crmv@159471
														$value = $pdf_app_strings["yes"];
													else
														$value = $pdf_app_strings["no"];
												}
												elseif(in_array($fieldname, $Textareas))
													$value = nl2br($value);
												elseif(in_array($fieldname, $Multipicklists))
													$value = str_ireplace(' |##| ',', ',$value);
												elseif(in_array($fieldname, $NumbersField))
													$value = $this->formatNumberToPDF($value);
												elseif(in_array($fieldname, $TimerField))
													$value = time_duration(abs($value));
												elseif(array_key_exists($fieldname,$sdk_uitypes)){
													$sdk_file = SDK::getUitypeFile('php','pdfmaker',$sdk_uitypes[$fieldname]);
													$sdk_value = $value;
													if ($sdk_file != '') {
														include($sdk_file);
													}
												}
												
                                              if (trim($value) != "-") $show_line = true;
                                              $productparttext = str_replace("$".$fieldname."$",$value,$productparttext);
                                          }
                                          //crmv@126096e
                                          if ($show_line) $ExplodedPdf[$productpartid].=$productparttext;
                                      }
                                  }
                              }

                              $this->content = implode('',$ExplodedPdf);

                          }
                      }

                      $ConvertRelBlock[] = $relblockid;
                  }
              }
          }
      }
    }

    protected function convertRelatedBlock($relblockid)
    {
      require_once("classes/simple_html_dom.php");
      $html = str_get_html($this->content);
      $tableDOM=false;
      foreach($html->find("td") as $td)
      {
        if(trim($td->plaintext) == "#RELBLOCK".$relblockid."_START#"){
          $td->parent->outertext = "#RELBLOCK".$relblockid."_START#";
          list($tag)=explode(">",$td->parent->parent->parent->outertext,2);

          $header = $td->parent->prev_sibling()->outertext;
          if ($td->parent->prev_sibling()->children[0]) {
			$header_style = $td->parent->prev_sibling()->children[0]->getAttribute("style");
		  }
          $footer_tag="<tr>";

          if(isset($header_style)){
           $StyleHeader = explode(";", $header_style);
           // print_r($StyleHeader);
           if(isset($StyleHeader)){
             foreach($StyleHeader as $style_header_tag)
             {
                if(strpos($style_header_tag, "border-top") == TRUE){
                  $footer_tag.="<td colspan='".$td->getAttribute("colspan")."' style='".$style_header_tag."'>&nbsp;</td>";
                }
             }
            }
          } else {
            $footer_tag.="<td colspan='".$td->getAttribute("colspan")."' style=\"border-top:1px solid #000000;\">&nbsp;</td>";
          }
          $footer_tag.="</tr>";

           // LAST VALUE IN TEMPLATE, MUST BE SUM
           $var = $td->parent->next_sibling()->last_child()->plaintext;

           $subtotal_tr="";
           // SUBTOTAL
           if(strpos($var, "TOTAL")!==false){
              // check for #PRODUCTBLOC_START# style

              if(is_object($td)){
                $style_subtotal=$td->getAttribute("style");
              }
              if(isset($td->innertext)){
                list($style_subtotal_tag,$style_subtotal_endtag)=explode("#PRODUCTBLOC_START#",$td->innertext);
              } else {
                $style_subtotal_tag = "";
                $style_subtotal_endtag = "";
              }
              // search for border-top style in PRODUCTBLOC_START td
             if(isset($style_subtotal)){
               $StyleSubtotal = explode(";", $style_subtotal);
               if(isset($StyleSubtotal)){
                 foreach($StyleSubtotal as $style_tag)
                 {
                    if(strpos($style_tag, "border-top") == TRUE){
                      $tag.=" style=\"".$style_tag."\"";
                      break;
                    }
                 }
               }
             } else {
              $style_subtotal = "";
             }

             $tag.=">";

             $subtotal_tr="<tr>";
                $subtotal_tr.="<td colspan='".($td->getAttribute("colspan")-1)."' style='".$style_subtotal.";border-right:none'>".$style_subtotal_tag."%G_Subtotal%".$style_subtotal_endtag."</td>";
                $subtotal_tr.="<td align='right' nowrap=\"nowrap\" style='".$style_subtotal."'>".$style_subtotal_tag."".rtrim($var,"$")."_SUBTOTAL$".$style_subtotal_endtag."</td>";
             $subtotal_tr.="</tr>";
           }

           $tableDOM["tag"] = $tag;
           $tableDOM["header"] = $header;
           $tableDOM["footer"] = $footer_tag;
           $tableDOM["subtotal"] = $subtotal_tr;
        }
        if(trim($td->plaintext) == "#RELBLOCK".$relblockid."_END#")
           $td->parent->outertext = "#RELBLOCK".$relblockid."_END#";
      }
      $this->content=$html->save();
      $html->clear(); ////crmv@58635
      unset($html); ////crmv@58635
      return $tableDOM;
    }

    //VlMe 1.31 S
    protected function convertListViewBlock() {
      require_once("classes/simple_html_dom.php");
      $html = str_get_html($this->content);

      foreach($html->find("td") as $td)
      {
        if(trim($td->plaintext) == "#LISTVIEWBLOCK_START#")
           $td->parent->outertext = "#LISTVIEWBLOCK_START#";

        if(trim($td->plaintext) == "#LISTVIEWBLOCK_END#")
           $td->parent->outertext = "#LISTVIEWBLOCK_END#";
      }
      $this->content=$html->save();
      $html->clear(); ////crmv@58635
      unset($html); ////crmv@58635      
    }
    //VlMe 1.31 E
    
    // crmv@198024
    protected function convertProdAttr($module, $focus) {
		
		// get all tags in the body
		$mathes = array();
		$bigmod = strtoupper($module);
		if (preg_match_all("/\\\${$bigmod}_PRODATTR_\d+_\d+\\\$/", $this->content, $matches)) {
			// now get all values for current product
			$values = $focus->getAttributes();
			// for each match, prepare the replacement
			foreach ($matches[0] as $match) {
				$key = 	strtolower(str_replace(array("\${$bigmod}_", '$'), '', $match));
				if (array_key_exists($key, $values)) {
					$rval = $values[$key];
				} else {
					$rval = '';
				}
				$this->replacements[$match] = $rval;
			}
			// and replace them!
			$this->replaceContent();
		}
		
    }
    // crmv@198024e
}

function generate_cool_uri($name)
{
    $nazov=trim(strtolower(stripslashes($name)));
    $Search = array("$","â‚¬","&","%",")","(","."," - ","/"," ",",","Ä¾","Å¡","Ä�","Å¥","Å¾","Ã½","Ã¡","Ã­","Ã©","Ã³","Ã¶","Å¯","Ãº","Ã¼","Ã¤","Åˆ","Ä�","Ã´","Å•","Ä½","Å ","ÄŒ","Å¤","Å½","Ã�","Ã�","Ã�","Ã‰","Ã“","Ãš","ÄŽ","\"","Â°","ÃŸ");
    $Replace = array("","","","","","","-","-","-","-","-","l","s","c","t","z","y","a","i","e","o","o","u","u","u","a","n","d","o","r","l","s","c","t","z","y","a","i","e","o","u","d","","","ss");
    $return=str_replace($Search, $Replace, $name);
    // echo $return;
    return $return;
}

function createPDFAndSaveFile($templates,$focus,$modFocus,$file_name,$moduleName,$language)
{
	$db="adb";
  $cu="current_user";
  $dl="default_language";
  global $$db, $$cu, $$dl;
  global $table_prefix;

	$date_var = date("Y-m-d H:i:s");
	//to get the owner id
	$ownerid = $focus->column_fields["assigned_user_id"];
	if(!isset($ownerid) || $ownerid=="")
		$ownerid = $$cu->id;

	$current_id = $$db->getUniqueID($table_prefix."_crmentity");

  $templates = rtrim($templates,";");
  $Templateids = explode(";",$templates);

  $name="";

  if (!$language || $language == "") $language = $$dl;

  foreach ($Templateids AS $templateid)
  {
	  $PDFContent = PDFContent::getInstance($templateid, $moduleName, $modFocus, $language); // crmv@34738
      $pdf_content = $PDFContent->getContent();
      $Settings = $PDFContent->getSettings();
      if($name=="")
        $name = $PDFContent->getFilename();

      if (isset($_REQUEST["mode"]) && $_REQUEST["mode"] == "edit" && isset($_REQUEST["header".$templateid]) && isset($_REQUEST["body".$templateid]) && isset($_REQUEST["footer".$templateid]))
      {
          $header_html = $_REQUEST["header".$templateid];
          $body_html = $_REQUEST["body".$templateid];
          $footer_html = $_REQUEST["footer".$templateid];
      }
      else
      {
          $header_html = $pdf_content["header"];
          $body_html = $pdf_content["body"];
          $footer_html = $pdf_content["footer"];
      }

      $body_html = str_replace("#LISTVIEWBLOCK_START#","",$body_html);
      $body_html = str_replace("#LISTVIEWBLOCK_END#","",$body_html);

      $encoding = $Settings["encoding"];

      if ($Settings["orientation"] == "landscape")
         $format = $Settings["format"]."-L";
      else
         $format = $Settings["format"];

      if (!isset($mpdf))
      {
          /*
          if($encoding=="auto"){
            $mpdf=new mPDF("utf-8",$format,'','',$Settings["margin_left"],$Settings["margin_right"],$Settings["margin_top"],$Settings["margin_bottom"],10,10);
            $mpdf->SetAutoFont();
          }
          else
            $mpdf=new mPDF($encoding,$format,'','',$Settings["margin_left"],$Settings["margin_right"],$Settings["margin_top"],$Settings["margin_bottom"],10,10);
          */
          $mpdf=new mPDF('',$format,'','Arial',$Settings["margin_left"],$Settings["margin_right"],0,0,$Settings["margin_top"],$Settings["margin_bottom"]);
          $mpdf->SetAutoFont();
          @$mpdf->SetHTMLHeader($header_html);
      }
      else
      {
          @$mpdf->SetHTMLHeader($header_html);
          @$mpdf->WriteHTML('<pagebreak sheet-size="'.$format.'" margin-left="'.$Settings["margin_left"].'mm" margin-right="'.$Settings["margin_right"].'mm" margin-top="0mm" margin-bottom="0mm" margin-header="'.$Settings["margin_top"].'mm" margin-footer="'.$Settings["margin_bottom"].'mm" />');
      }

      @$mpdf->SetHTMLFooter($footer_html);
      @$mpdf->WriteHTML($body_html);
  }

  $upload_file_path = decideFilePath();

  if($name!="")
    $file_name = $name.".pdf";

  $mpdf->Output($upload_file_path.$current_id."_".$file_name);

  $filesize = filesize($upload_file_path.$current_id."_".$file_name);
  $filetype = "application/pdf";

	// crmv@150773
	$sql1 = "insert into ".$table_prefix."_crmentity (crmid,smcreatorid,smownerid,setype,createdtime,modifiedtime) values(?, ?, ?, ?, ?, ?)";
	$params1 = array($current_id, $$cu->id, $ownerid, "Documents Attachment", $$db->formatDate($date_var, true), $$db->formatDate($date_var, true));
	// crmv@150773e
	
	$$db->pquery($sql1, $params1);

	$sql2="insert into ".$table_prefix."_attachments(attachmentsid, name, description, type, path) values(?, ?, ?, ?, ?)";
	$params2 = array($current_id, $file_name, $focus->column_fields["description"], $filetype, $upload_file_path);
	$result=$$db->pquery($sql2, $params2);

	$sql3='insert into '.$table_prefix.'_seattachmentsrel values(?,?)';
	$$db->pquery($sql3, array($focus->id, $current_id));

  $sql4="UPDATE ".$table_prefix."_notes SET filesize=?, filename=? WHERE notesid=?";
  $$db->pquery($sql4,array($filesize,$file_name,$focus->id));

	return true;
}
?>