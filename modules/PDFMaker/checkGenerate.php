<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
$app="app_strings";
$mod="mod_strings";
$db="adb";
$salt="site_URL";
global $table_prefix;

include("modules/PDFMaker/InventoryPDF.php");
include("include/mpdf/mpdf.php"); //crmv@30066

$focus = CRMEntity::getInstance($_REQUEST["relmodule"]);

//crmv@33361
if (isset($_REQUEST["pid"])) {
	$Records = array($_REQUEST["pid"]);  
//crmv@33361e
}
elseif(isset($_REQUEST["idslist"]) && $_REQUEST["idslist"]!="")   //generating from listview 
{
	//crmv@27096	//crmv@28059
	$Records = getListViewCheck($_REQUEST["relmodule"]);
	if (empty($Records)) {
		$Records = explode(";", rtrim($_REQUEST["idslist"],";"));
	}
	global $php_max_execution_time;
	set_time_limit($php_max_execution_time);
	//crmv@27096e	//crmv@28059e
}
elseif(isset($_REQUEST['record'])) 
{     
	$Records = array($_REQUEST["record"]);  
}

sort($Records);

$commontemplateids = trim($_REQUEST["commontemplateid"],";");  
$Templateids = explode(";",$commontemplateids);
$name="";

if($_REQUEST["mode"] == "content")
{
	$PDFContents = array();
	
	foreach($Records as $record)
	{   
		$focus->retrieve_entity_info($record,$_REQUEST["relmodule"]);
		$focus->id = $record;
		
		foreach ($Templateids AS $templateid)
		{ 
			$PDFContent = PDFContent::getInstance($templateid, $_REQUEST["relmodule"], $focus, $_REQUEST["language"]); //crmv@34738
			// crmv@171512
			$PDFContent->convertFieldsHtml = true;
			$PDFContent->convertFieldsForCKEditor = true;
			// crmv@171512e
			$pdf_content = $PDFContent->getContent();    
			
			$body_html = $pdf_content["body"];
			$body_html = str_replace("#LISTVIEWBLOCK_START#","",$body_html);
			$body_html = str_replace("#LISTVIEWBLOCK_END#","",$body_html);
			
			$PDFContents[$templateid]["header"] = $pdf_content["header"];
			$PDFContents[$templateid]["body"] = $body_html;
			$PDFContents[$templateid]["footer"] = $pdf_content["footer"];       
		}
	}    
	
	include_once("modules/PDFMaker/EditPDF.php");
	
	showEditPDFForm($PDFContents);
	
}
else
{
	if (isset($_REQUEST["type"]) && ($_REQUEST["type"] == "doc" OR $_REQUEST["type"] == "rtf"))
	{
		$Section = array();
		$i = 1;
		
		foreach($Records as $record)
		{
			$focus->retrieve_entity_info($record,$_REQUEST["relmodule"]);
			$focus->id = $record;
			
			foreach ($Templateids AS $templateid)
			{
				$PDFContent = PDFContent::getInstance($templateid, $_REQUEST["relmodule"], $focus, $_REQUEST["language"]); // crmv@34738
				
				$PDFContent->pagebreak = "<br clear=all style='mso-special-character:line-break;page-break-before:always'>";
				
				$Settings = $PDFContent->getSettings();
				if($name=="")
					$name = $PDFContent->getFilename();
				
				
				
				if (isset($_REQUEST["mode"]) && $_REQUEST["mode"] == "edit")
				{
					$header_html = $_REQUEST["header".$templateid];
					$body_html = $_REQUEST["body".$templateid];
					$footer_html = $_REQUEST["footer".$templateid];
				}
				else
				{
					$pdf_content = $PDFContent->getContent();
					$header_html = $pdf_content["header"];
					$body_html = $pdf_content["body"];
					$footer_html = $pdf_content["footer"];
				}
				
				if ($header_html != "" OR $footer_html != "")
				{
					$headerfooterurl = "cache/pdfmaker/".$record."_headerfooter_".$templateid."_".$i.".html";
					
					$header_html = str_replace("{PAGENO}","<!--[if supportFields]><span class=MsoPageNumber><span style='mso-element:field-begin'></span><span style='mso-spacerun:yes'> </span>PAGE <span style='mso-element:field-separator'></span></span><![endif]--><span class=MsoPageNumber><span style='mso-no-proof:yes'>1</span></span><!--[if supportFields]><span class=MsoPageNumber><span style='mso-element:field-end'></span></span><![endif]-->",$header_html);
					$footer_html = str_replace("{PAGENO}","<!--[if supportFields]><span class=MsoPageNumber><span style='mso-element:field-begin'></span><span style='mso-spacerun:yes'> </span>PAGE <span style='mso-element:field-separator'></span></span><![endif]--><span class=MsoPageNumber><span style='mso-no-proof:yes'>1</span></span><!--[if supportFields]><span class=MsoPageNumber><span style='mso-element:field-end'></span></span><![endif]-->",$footer_html);
					//$footer_html = str_replace("{PAGENO}","<!--[if supportFields]><span class=MsoPageNumber><span style='mso-element:field-begin'></span><span style='mso-spacerun:yes'> </span>PAGE <span style='mso-element:field-separator'></span></span><![endif]-->",$footer_html);
					
					$header_html = str_replace("{nb}","<!--[if supportFields]><span class=MsoPageNumber><span style='mso-element:field-begin'></span><span style='mso-spacerun:yes'> </span>NUMPAGES <span style='mso-element:field-separator'></span></span><![endif]--><span class=MsoPageNumber><span style='mso-no-proof:yes'>1</span></span><!--[if supportFields]><span class=MsoPageNumber><span style='mso-element:field-end'></span></span><![endif]-->",$header_html);
					$footer_html = str_replace("{nb}","<!--[if supportFields]><span class=MsoPageNumber><span style='mso-element:field-begin'></span><span style='mso-spacerun:yes'> </span>NUMPAGES <span style='mso-element:field-separator'></span></span><![endif]--><span class=MsoPageNumber><span style='mso-no-proof:yes'>1</span></span><!--[if supportFields]><span class=MsoPageNumber><span style='mso-element:field-end'></span></span><![endif]-->",$footer_html);
					
					$headerfooter = '<!--[if supportFields]>';
					$headerfooter .= '<div style="mso-element:header;" id=h'.$i.'><p class=MsoHeader>'.$header_html.'</p></div>';
					$headerfooter .= '<div style="mso-element:footer;" id=f'.$i.'><p class=MsoFooter>'.$footer_html.'</p></div>';
					$headerfooter .= '<![endif]-->';
				} else {
					$headerfooterurl = "";
					$headerfooter = "";
				}
				
				$body_html = str_replace("#LISTVIEWBLOCK_START#","",$body_html);
				$body_html = str_replace("#LISTVIEWBLOCK_END#","",$body_html);
				
				$content = "<div class=\"Section".$i."\">"; 
				$content .= $body_html;
				$content .= "</div>"; 
				
				$Templates[$templateid][] = $i;
				
				$Section[$i] = array("settings" => $Settings, "content" => $content, "headerfooterurl" => $headerfooterurl , "headerfooter" => $headerfooter);
				$i++;
			}
		}
		
		if($name=="")
		{
			if(count($Records)>1)
			{
				$name="BatchPDF";
			}
			else
			{
				$result=$$db->query("SELECT fieldname FROM ".$table_prefix."_field WHERE uitype=4 AND tabid=".getTabId($_REQUEST["relmodule"]));
				$fieldname=$$db->query_result($result,0,"fieldname");
				if(isset($focus->column_fields[$fieldname]) && $focus->column_fields[$fieldname]!="")
				{
					$name = generate_cool_uri($focus->column_fields[$fieldname]);
				}
				else
				{
			    	$name = $_REQUEST["commontemplateid"].$_REQUEST["record"].date("ymdHi");
			    	$name = str_replace(";","_",$name);
				}
			}  
		}
		
		@header("Cache-Control: ");
			@header("Pragma: ");
		
		if ($_REQUEST["type"] == "doc")
		{
			@header("Content-type: application/vnd.ms-word");
			@header("Content-Disposition: attachment;Filename=".$name.".doc");
		} 
		elseif ($_REQUEST["type"] == "rtf")
		{
			@header("Content-type: application/rtf");
			@header("Content-Disposition: attachment;Filename=".$name.".rtf");
		}
		
		
		echo "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>";
		echo "<head>";
		echo "<meta http-equiv=\"Content-Type\" content=\"text/html;\" charset=\"UTF-8\">"; //crmv@128499
		echo "<link rel=File-List href=\"cache/filelist.xml\">";
		//crmv@35765
//		echo "<title></title>
//					<!--[if gte mso 9]><xml>
//					 <w:WordDocument>
//					  <w:View>Print</w:View>
//					  <w:DoNotHyphenateCaps/>
//					  <w:PunctuationKerning/>
//					  <w:DrawingGridHorizontalSpacing>9.35 pt</w:DrawingGridHorizontalSpacing>
//					  <w:DrawingGridVerticalSpacing>9.35 pt</w:DrawingGridVerticalSpacing>
//					 </w:WordDocument>
//					</xml><![endif]-->
//					<style>
//					<!--
//					 /* Font Definitions */
//					@font-face
//						{font-family:Verdana;
//						panose-1:2 11 6 4 3 5 4 4 2 4;
//						mso-font-charset:0;
//						mso-generic-font-family:swiss;
//						mso-font-pitch:variable;
//						mso-font-signature:536871559 0 0 0 415 0;}
//					 /* Style Definitions */
//					p.MsoNormal, li.MsoNormal, div.MsoNormal
//						{mso-style-parent:\"\";
//						margin:0in;
//						padding:0in;
//						margin-bottom:.0001pt;
//						mso-pagination:widow-orphan;
//						font-size:7.5pt;
//					        mso-bidi-font-size:8.0pt;
//						font-family:\"Verdana\";
//						mso-fareast-font-family:\"Verdana\";}
//					p.small
//						{mso-style-parent:\"\";
//						margin:0in;
//						margin-bottom:.0001pt;
//						mso-pagination:widow-orphan;
//						font-size:1.0pt;
//					  mso-bidi-font-size:1.0pt;
//						font-family:\"Verdana\";
//						mso-fareast-font-family:\"Verdana\";}";
		echo "<title></title>
					<style>
					<!--
					 /* Font Definitions */
					@font-face
						{font-family:Verdana;
						panose-1:2 11 6 4 3 5 4 4 2 4;
						mso-font-charset:0;
						mso-generic-font-family:swiss;
						mso-font-pitch:variable;
						mso-font-signature:536871559 0 0 0 415 0;}
					 /* Style Definitions */
					p.MsoNormal, li.MsoNormal, div.MsoNormal
						{mso-style-parent:\"\";
						margin:0in;
						padding:0in;
						margin-bottom:.0001pt;
						mso-pagination:widow-orphan;
						font-size:7.5pt;
					        mso-bidi-font-size:8.0pt;
						font-family:\"Verdana\";
						mso-fareast-font-family:\"Verdana\";}
					p.small
						{mso-style-parent:\"\";
						margin:0in;
						margin-bottom:.0001pt;
						mso-pagination:widow-orphan;
						font-size:1.0pt;
					  mso-bidi-font-size:1.0pt;
						font-family:\"Verdana\";
						mso-fareast-font-family:\"Verdana\";}";
		//crmv@35765 e		
		$Fomats['A3'] = array(29.7, 42, "cm");
		$Fomats['A4'] = array(21, 29.7, "cm");
		$Fomats['A5'] = array(14.8, 21, "cm");
		$Fomats['A6'] = array(10.5, 14.8, "cm");
		$Fomats['Letter'] = array(21.59, 27.94, "cm");
		$Fomats['Legal'] = array(21.59, 35.56, "cm");
		     
		$data = $Section[1];
		$n = "1";
		
		$format = $data["settings"]["format"];
		$orientation = $data["settings"]["orientation"];
		
		if ($orientation == "portrait")
			$size = $Fomats[$format][0].$Fomats[$format][2]." ".$Fomats[$format][1].$Fomats[$format][2]."; ";
		else
			$size = $Fomats[$format][1].$Fomats[$format][2]." ".$Fomats[$format][0].$Fomats[$format][2]."; ";
		  
		$margin_left = $data["settings"]["margin_left"];
		$margin_right = $data["settings"]["margin_right"];
		$margin_top = $data["settings"]["margin_top"];
		$margin_bottom = $data["settings"]["margin_bottom"];
		
		echo "@page Section".$n." 
		        {
		          size: ".$size.";
		          margin: ".$margin_top."mm ".$margin_right."mm ".$margin_bottom."mm ".$margin_left."mm;
		          mso-page-orientation: ".$orientation.";
		          padding: 0cm 0cm 0cm 0cm; ";
		 
		if ($data["headerfooterurl"] !="")
		{
		    echo "mso-footer: url(\"".$$salt."/".$data["headerfooterurl"]."\") f".$n." ; ";
		    echo "mso-header: url(\"".$$salt."/".$data["headerfooterurl"]."\") h".$n."; ";
		          
		    if(!is_dir('cache/pdfmaker'))
				mkdir('cache/pdfmaker');
		      
		    $fp = fopen($data["headerfooterurl"], 'w');
		    $c = '<html xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns:m="http://schemas.microsoft.com/office/2004/12/omml"= xmlns="http://www.w3.org/TR/REC-html40">';
		    $c .= '<body>'; 
		    $c .= $data["headerfooter"];    
		    $c .= '</body>'; 
		    $c .= '</html>'; 
		         
		    fwrite($fp, $c);
		    fclose($fp);
		}     
		   
		echo "}
		        div.Section".$n."
		        {page:Section".$n.";}";
		//crmv@35765		    
//		echo "p.MsoHeader, li.MsoHeader, div.MsoHeader
//		      	{margin:0in;
//		      	margin-bottom:.0001pt;
//		      	mso-pagination:widow-orphan;
//		      	tab-stops:center 3.0in right 6.0in;}
//		      p.MsoFooter, li.MsoFooter, div.MsoFooter
//		      { mso-pagination:widow-orphan;
//		        tab-stops:center 216.0pt right 432.0pt;
//		        font-family:\"Arial\";
//		        font-size:1.0pt;
//		      } 
//					-->
//					</style>
//					<!--[if gte mso 9]><xml>
//					 <o:shapedefaults v:ext=\"edit\" spidmax=\"1032\">
//					  <o:colormenu v:ext=\"edit\" strokecolor=\"none\"/>
//					 </o:shapedefaults></xml><![endif]--><!--[if gte mso 9]><xml>
//					 <o:shapelayout v:ext=\"edit\">
//					  <o:idmap v:ext=\"edit\" data=\"1\"/>
//					 </o:shapelayout></xml><![endif]-->";
		echo "p.MsoHeader, li.MsoHeader, div.MsoHeader
		      	{margin:0in;
		      	margin-bottom:.0001pt;
		      	mso-pagination:widow-orphan;
		      	tab-stops:center 3.0in right 6.0in;}
		      p.MsoFooter, li.MsoFooter, div.MsoFooter
		      { mso-pagination:widow-orphan;
		        tab-stops:center 216.0pt right 432.0pt;
		        font-family:\"Arial\";
		        font-size:1.0pt;
		      } 
					-->
					</style>";
		//crmv@35765 e		
		echo "</head>";
		echo "<body>";
		
		foreach ($Section AS $n => $data)
		{   
			if ($n > 1)
				echo "<br clear=all style=\"mso-special-character:line-break;page-break-before:always\">";              
		
			echo $data["content"];
		}
		
		echo "</body>";
		echo "</html>";
	}
	else
	{
		$TemplateContent = array();
		
		foreach($Records as $record)
		{
			$focus->retrieve_entity_info($record,$_REQUEST["relmodule"]);
			$focus->id = $record;
			
			foreach ($Templateids AS $templateid)
			{
				$PDFContent = PDFContent::getInstance($templateid, $_REQUEST["relmodule"], $focus, $_REQUEST["language"]); //crmv@34738
				$PDFContent->convertFieldsHtml = true; // crmv@171512
				
				$Settings = $PDFContent->getSettings();
				if($name=="")      
					$name = $PDFContent->getFilename();
				
				if (isset($_REQUEST["mode"]) && $_REQUEST["mode"] == "edit")
				{
					$header_html = $_REQUEST["header".$templateid];
					$body_html = $_REQUEST["body".$templateid];
					$footer_html = $_REQUEST["footer".$templateid];
				}
				else
				{
					$pdf_content = $PDFContent->getContent();
					$header_html = $pdf_content["header"];
					$body_html = $pdf_content["body"];
					$footer_html = $pdf_content["footer"];
				}
				//$encoding = $Settings["encoding"];
				
				if ($Settings["orientation"] == "landscape")
					$format = $Settings["format"]."-L";
				else
					$format = $Settings["format"];
				
				
				$ListViewBlocks = array();
				if(strpos($body_html,"#LISTVIEWBLOCK_START#") !== false && strpos($body_html,"#LISTVIEWBLOCK_END#") !== false)
					preg_match_all("|#LISTVIEWBLOCK_START#(.*)#LISTVIEWBLOCK_END#|sU", $body_html, $ListViewBlocks, PREG_PATTERN_ORDER);
				
				if (count($ListViewBlocks) > 0)
				{
					//if (!isset($TemplateContent[$templateid]))
					//{
					$TemplateContent[$templateid] = $pdf_content;
					$TemplateSettings[$templateid] = $Settings;
					//}
					
					$num_listview_blocks = count($ListViewBlocks[0]);
					for($i=0; $i<$num_listview_blocks; $i++)
					{
						//if (!isset($ListViewBlock[$templateid][$i])) $ListViewBlock[$templateid][$i] = $ListViewBlocks[0][$i];
						$ListViewBlock[$templateid][$i] = $ListViewBlocks[0][$i];
						$ListViewBlockContent[$templateid][$i][$record][] = $ListViewBlocks[1][$i];
					}   
				}
				else
				{
					if (!isset($mpdf))
					{           
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
			}
		}
		
		if (count($TemplateContent)> 0)
		{
			foreach($TemplateContent AS $templateid => $TContent)
			{
				$header_html = $TContent["header"];
				$body_html = $TContent["body"];
				$footer_html = $TContent["footer"];
				
				$Settings = $TemplateSettings[$templateid];
				
				foreach($ListViewBlock[$templateid] AS $id => $text)
				{
					$replace = "";
					foreach($Records as $record)
					{  
						$replace .= implode("",$ListViewBlockContent[$templateid][$id][$record]);   
					}
					
					$body_html = str_replace($text,$replace,$body_html);
				}
				
				if ($Settings["orientation"] == "landscape")
					$format = $Settings["format"]."-L";
				else
					$format = $Settings["format"];
				
				
				if (!isset($mpdf))
				{           
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
		}
		
		if($name=="")
		{
			if(count($Records)>1)
			{
				$name="BatchPDF";
			}
			else
			{
				$result=$$db->query("SELECT fieldname FROM ".$table_prefix."_field WHERE uitype=4 AND tabid=".getTabId($_REQUEST["relmodule"]));
				$fieldname=$$db->query_result($result,0,"fieldname");
				if(isset($focus->column_fields[$fieldname]) && $focus->column_fields[$fieldname]!="")
				{
					$name = generate_cool_uri($focus->column_fields[$fieldname]);
				}
				else
				{
					$name = $_REQUEST["commontemplateid"].$_REQUEST["record"].date("ymdHi");
					$name = str_replace(";","_",$name);
				}
			}  
		}
		
		// crmv@172422
		if ($Settings['compliance'] === 'PDFA') {
			$mpdf->PDFA = true;
			$mpdf->PDFAauto = true;
		} elseif ($Settings['compliance'] === 'PDFX') {
			$mpdf->PDFX = true;
			$mpdf->PDFXauto = true;
		}
		// crmv@172422e
		
		$mpdf->Output('cache/'.$name.'.pdf');
		
		@ob_clean();
		header('Content-Type: application/pdf');
		header("Content-length: ".filesize("./cache/$name.pdf"));
		header("Cache-Control: private");
		header("Content-Disposition: attachment; filename=$name.pdf");
		header("Content-Description: PHP Generated Data");
		echo fread(fopen("./cache/$name.pdf", "r"),filesize("./cache/$name.pdf"));
		
		@unlink("cache/$name.pdf");
	}
}
exit;

?>