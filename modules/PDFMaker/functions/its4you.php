<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/**
 * This function executes if-else statement based on given parameters
 *  
 * @param $param1 first parameter of comparation
 * @param $comparator comparation sign - one of ==,!=,<,>,<=,>=
 * @param $param2 second parameter of comparation
 * @param $whatToReturn1 value returned when comparation succeded
 * @param $whatToReturn2 value returned when comparation not succeded
 **/ 
function its4you_if($param1,$comparator,$param2,$whatToReturn1,$whatToReturn2=''){
	global $adb,$table_prefix;
	$param1 = htmlentities($param1, ENT_QUOTES);
	$comparator = html_entity_decode($comparator,ENT_COMPAT,'utf-8');
	$param2 = htmlentities($param2, ENT_QUOTES);
	$whatToReturn1 = htmlentities($whatToReturn1, ENT_QUOTES);
	$whatToReturn2 = htmlentities($whatToReturn2, ENT_QUOTES);
	switch($comparator){
		case "=":
			$comparator = '==';
		break;
		case "<>":
			$comparator = '!=';
		break;
		case "=>":
			$comparator = '>=';
		break;
		case "=<":
			$comparator = '<=';
		break;
	}
	

	if(in_array($comparator, array('==','!=','>=','<=','>','<')))
		return eval("if('$param1' $comparator '$param2'){return '$whatToReturn1';} else {return '$whatToReturn2';}");
	else
		return "Error! second parameter must be one from following: ==,!=,<,>,<=,>=";
}

/**
 * This function returns id of current template 
 * 
 **/ 
function getTemplateId()
{
  global $PDFMaker_template_id;
  return $PDFMaker_template_id;
}

/**
 * This function returns image of contact 
 *  
 * @param $id - contact id
 * @param $width width of returned image (10%, 100px) 
 * @param $height height of returned image (10%, 100px)
 *
 **/ 
function its4you_getContactImage($id,$width,$height)
{
  if(isset($id) AND $id!=""){
    global $adb,$table_prefix;
    $query="SELECT ".$table_prefix."_attachments.path, ".$table_prefix."_attachments.name, ".$table_prefix."_attachments.attachmentsid
          FROM ".$table_prefix."_contactdetails
          INNER JOIN ".$table_prefix."_seattachmentsrel
            ON ".$table_prefix."_contactdetails.contactid=".$table_prefix."_seattachmentsrel.crmid
          INNER JOIN ".$table_prefix."_attachments
            ON ".$table_prefix."_attachments.attachmentsid=".$table_prefix."_seattachmentsrel.attachmentsid
          INNER JOIN ".$table_prefix."_crmentity
            ON ".$table_prefix."_attachments.attachmentsid=".$table_prefix."_crmentity.crmid
          WHERE deleted=0 AND ".$table_prefix."_contactdetails.contactid=?";
    
    $result = $adb->pquery($query, array($id));
    $num_rows = $adb->num_rows($result);
    if ($num_rows > 0)
    {
      $adb->query_result($result,0,"path");
      $image_src = $adb->query_result($result,0,"path").$adb->query_result($result,0,"attachmentsid")."_".$adb->query_result($result,0,"name");
      $image = "<img src='".$image_src."' width='".$width."' height='".$height."'/>";
      return $image;
    }
  } else {
    return "";
  }
}

/**
 * This function returns formated value 
 *  
 * @param $value - int  
 *  
 **/
function its4you_formatNumberToPDF($value) 
{
  global $PDFMaker_template_id, $adb,$table_prefix;
  
  $sql = "SELECT decimals, decimal_point, thousands_separator
          FROM ".$table_prefix."_pdfmaker_settings           
          WHERE templateid=?";
  $result = $adb->pquery($sql, array($PDFMaker_template_id));
  $data = $adb->fetch_array($result);

  $decimal_point = html_entity_decode($data["decimal_point"], ENT_QUOTES);
  $thousands_separator = html_entity_decode(($data["thousands_separator"]!="sp" ? $data["thousands_separator"] : " "), ENT_QUOTES);
  $decimals = $data["decimals"];  
  
  if(is_numeric($value)){
    $number = number_format($value,  $decimals,  $decimal_point,  $thousands_separator);
  }else {
    $number = "";
  }
  return $number;
}

/**
 * This function returns converted value into integer 
 *  
 * @param $value - int  
 *  
 **/
function its4you_formatNumberFromPDF($value) 
{
 global $PDFMaker_template_id, $adb, $table_prefix; //crmv@66623
 
 $sql = "SELECT decimals, decimal_point, thousands_separator
       FROM ".$table_prefix."_pdfmaker_settings           
       WHERE templateid=?";
 $result = $adb->pquery($sql, array($PDFMaker_template_id));
 $data = $adb->fetch_array($result);
 
 $decimal_point = html_entity_decode($data["decimal_point"], ENT_QUOTES);
 $thousands_separator = html_entity_decode(($data["thousands_separator"]!="sp" ? $data["thousands_separator"] : " "), ENT_QUOTES);
 $decimals = $data["decimals"];  
 
 $number = str_replace($decimal_point,'.',$value);
 $number = str_replace($thousands_separator,'',$number);
 return $number;
}

/**
 * This function returns multipicate value sum1*sum2 
 *  
 * @param $sum1 - int
 * @param $sum2 - int 
 *
 * using: [CUSTOMFUNCTION|its4you_multiplication|param1|param2|CUSTOMFUNCTION]
 **/
function its4you_multiplication($sum1,$sum2)
{
  $sum = its4you_formatNumberFromPDF($sum1) * its4you_formatNumberFromPDF($sum2);
  return its4you_formatNumberToPDF($sum); 
}

/**
 * This function return deducate value sum1-sum2 
 *  
 * @param $sum1 - int
 * @param $sum2 - int 
 *
 * using: [CUSTOMFUNCTION|its4you_deduct|param1|param2|CUSTOMFUNCTION]
 **/
function its4you_deduct($sum1,$sum2)
{
  $sum = its4you_formatNumberFromPDF($sum1) - its4you_formatNumberFromPDF($sum2);
  return its4you_formatNumberToPDF($sum); 
}

/**
 * This function return sum of values sum1+sum2 
 *  
 * @param $sum1 - int
 * @param $sum2 - int 
 *
 * using: [CUSTOMFUNCTION|its4you_sum|param1|param2|CUSTOMFUNCTION]
 **/
function its4you_sum($sum1,$sum2)
{
  $sum = its4you_formatNumberFromPDF($sum1) + its4you_formatNumberFromPDF($sum2);
  return $sum;
  // return its4you_formatNumberToPDF($sum); 
}

/**
 * This function return divided value sum1/sum2 
 *  
 * @param $sum1 - int
 * @param $sum2 - int 
 *
 * using: [CUSTOMFUNCTION|its4you_divide|param1|param2|CUSTOMFUNCTION]
 **/
function its4you_divide($sum1,$sum2)
{
  $sum = its4you_formatNumberFromPDF($sum1)/its4you_formatNumberFromPDF($sum2);
  return its4you_formatNumberToPDF($sum); 
}