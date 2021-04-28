<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('config.php');
require_once('include/logging.php');
require_once('modules/Potentials/Potentials.php');
require_once('Image/Graph.php');
require_once('include/utils/utils.php');
require_once('include/utils/GraphUtils.php');




class jpgraph {
	/**
	 * Creates opportunity pipeline image as a horizontal accumlated bar graph for multiple vte_users.
	 * param $datax- the month data to display in the x-axis
	 */
	function outcome_by_month($date_start=false, $date_end=false, $user_id=array('1'), $cache_file_name='a_file', $refresh=false,$width=900,$height=500){
		global $log;
		global $table_prefix;
		$log->debug("Entering outcome_by_month(".$date_start.",". $date_end.",". $user_id.") method ...");
		global $app_strings,$lang_crm, $app_list_strings, $current_module_strings,$current_user, $log, $charset, $tmp_dir,$adb;
		global $theme;
		include_once ('Image/Graph.php');
		include_once ('Image/Canvas.php');

		$log =& LoggerManager::getLogger('outcome_by_month chart');
		// Set the basic parameters of the graph
		$canvas =& Image_Canvas::factory('png', array('width' => $width, 'height' => $height, 'usemap' => true));
		$imagemap = $canvas->getImageMap();
		$graph =& Image_Graph::factory('graph', $canvas);
		$log->debug("graph object created");

		// add a TrueType font
		$font =& $graph->addNew('font', calculate_font_name($lang_crm));
		// set the font size to 11 pixels
		$font->setSize(8);
		
		$graph->setFont($font);
		// create the plotarea layout
        $title =& Image_Graph::factory('title', array('Title',10));
    	$plotarea =& Image_Graph::factory('plotarea',array(
                    'axis',
                    'axis'
                ));
        $footer =& Image_Graph::factory('title', array('Footer',8));
		$graph->add(
		    Image_Graph::vertical($title,
	        Image_Graph::vertical(
				$plotarea,
        	    $footer,
            	90
		        ),
        	5
	    	)
		);   

		//$graph->SetScale("textlin");

		if (!file_exists($cache_file_name) || !file_exists($cache_file_name.'.map') || $refresh == true) {
			//$font = calculate_font_family($lang_crm);

			$log->debug("date_start is: $date_start");
			$log->debug("date_end is: $date_end");
			$log->debug("user_id is: ");
			$log->debug($user_id);
			$log->debug("cache_file_name is: $cache_file_name");

			//build the where clause for the query that matches $user
			$where = "";
			$first = true;
			$current = 0;

			//crmv@fix dash
			$params_list = Array();
			//build the where clause for the query that matches $datax
			$count = count($datax);
			if ($count>0) {
				$params_list[] = array_keys($datax);
				$where_list = " and sales_stage in (".generateQuestionMarks($datax).")";
			}

			//build the where clause for the query that matches $date_start and $date_end
			if ($date_start){
				$where_list .= "and ".$table_prefix."_potential.closingdate >= ?";
				$params_list[] = $date_start;
			}	
			if ($date_end){
				$where_list .= "and ".$table_prefix."_potential.closingdate <= ?";
				$params_list[] = $date_end;
			}
			if ($date_start && $date_end)
				$subtitle = $current_module_strings['LBL_DATE_RANGE']." ".getDisplayDate($date_start)." ".$current_module_strings['LBL_DATE_RANGE_TO']." ".getDisplayDate($date_end)."\n";
			elseif ($date_start && !$date_end){
				$subtitle = $current_module_strings['LBL_DATE_RANGE']." >=".getDisplayDate($date_start)."\n";
			}
			elseif ($date_end && !$date_start){
				$subtitle = $current_module_strings['LBL_DATE_RANGE']." <=".getDisplayDate($date_end)."\n";
			}
			else{
				$subtitle = $current_module_strings['LBL_DATE_RANGE_NOT_SET']."\n";
			}

			//Now do the db queries
			//query for opportunity data that matches $datax and $user
			$select_fields = Array(
			'sales_stage','closingdate','assigned_user_id','amount',
			);
			$q = "select tablename,columnname,fieldname from ".$table_prefix."_field where fieldname in (".generateQuestionMarks($select_fields).") and tabid = ?";
			$params = Array($select_fields,getTabId('Potentials'));
			$res = $adb->pquery($q,$params);
			$columns = Array();
			if ($res && $adb->num_rows($res)>0){
				while($row = $adb->fetchByAssoc($res,-1,false)){
					if (getFieldVisibilityPermission('Potentials',$current_user->id,$row['fieldname']) == '0')
						$columns[] = $row['tablename'].".".$row['columnname'];
					else{
//						$columns[] = $row['tablename'].".".$row['columnname'];
						return false;
					}
							
				}	
			}
			$list_query = getListQuery('Potentials',$where);
			$list_query = replaceSelectQuery(getListQuery('Potentials',$where_list),implode(",",$columns));
			$list_query.=" order by ".$table_prefix."_potential.amount DESC, ".$table_prefix."_potential.closingdate DESC";
			$res_list = $adb->pquery($list_query,$params_list);
//			echo $adb->convert2Sql($list_query,$adb->flatten_array($params_list));
			$opp_list = Array();
			if ($res_list && $adb->num_rows($res_list)>0){
				while($row = $adb->fetchByAssoc($res_list,-1,false)){
					$opp_list[] = $row;
				}
			}			
			//build pipeline by sales stage data
			$total = 0;
			$count = array();
			$sum = array();
			$months = array();
			$other = $current_module_strings['LBL_LEAD_SOURCE_OTHER'];
			if (isset($opp_list)) {
				foreach ($opp_list as $record) {
					$month = substr_replace(substr($record['closingdate'],0,10),'',-3);
					if (!in_array($month, $months)) { array_push($months, $month); }
					if ($record['sales_stage'] == 'Closed Won' || $record['sales_stage'] == 'Closed Lost') {
						$sales_stage=$record['sales_stage'];
					}
					else {
						$sales_stage=$other;
					}

					if (!isset($sum[$month][$sales_stage][$record['smownerid']]))
				       	{
						$sum[$month][$sales_stage][$record['smownerid']] = 0;
					}
					if (isset($record['amount']) && in_array($record['smownerid'],$user_id)) {
						// Strip all non numbers from this string.
						$amount = convertFromMasterCurrency(preg_replace('/[^0-9]/', '', floor($record['amount'])),$current_user->conv_rate);
						$sum[$month][$sales_stage][$record['smownerid']] = $sum[$month][$sales_stage][$record['smownerid']] + $amount;
						if (isset($count[$month][$sales_stage][$record['smownerid']])) {
						
							$count[$month][$sales_stage][$record['smownerid']]++;
						} else {

							$count[$month][$sales_stage][$record['smownerid']] = 1;
						}
						$total = $total + ($amount/1000);
					}
				}
			}
			$legend = array();
			$datax = array();
			$aTargets = array();
			$aAlts = array();
			$stages = array($other, 'Closed Lost', 'Closed Won');
			//sort the months or push a bogus month on the array so that an empty chart is drawn
			if (empty($months)) {
				array_push($months, date('Y-m',time()));
			}
			else{
				sort($months);
			}
			$cvid = getCvIdOfAll("Potentials");
			foreach ($months as $month){
				array_push($legend,$month);
			}
			foreach ($user_id as $the_id) {
				$the_user= getUserName($the_id);
				foreach($stages as $stage) {
					foreach($months as $month) {
						if (!isset($datax[$stage][$the_id])) {
							$datax[$stage][$the_id] = array();
						}
						if (!isset($aAlts[$stage][$the_id])) {
							$aAlts[$stage][$the_id] = array();
						}
						if (!isset($aTargets[$stage][$the_id])) {
							$aTargets[$stage][$the_id] = array();
						}

						if (isset($sum[$month][$stage][$the_id])) {
							array_push($datax[$stage][$the_id], $sum[$month][$stage][$the_id]/1000);
							array_push($aAlts[$stage][$the_id], $the_user.' - '.$count[$month][$stage][$the_id]." ".$current_module_strings['LBL_OPPS_OUTCOME']." $stage");
						}
						else {
							array_push($datax[$stage][$the_id], 0);
							array_push($aAlts[$stage][$the_id], "");
						}
						array_push($aTargets[$stage][$the_id], "index.php?module=Potentials&action=ListView&date_closed=$month&sales_stage=".urlencode($stage)."&query=true&type=dbrd&owner=".$the_user."&viewname=".$cvid);						
					}
				}
			}
			$log->debug("datax is:");
			$log->debug($datax);
			$log->debug("aAlts is:");
			$log->debug($aAlts);
			$log->debug("aTargets is:");
			$log->debug($aTargets);
			$log->debug("sum is:");
			$log->debug($sum);
			$log->debug("count is:");
			$log->debug($count);
			//now build the bar plots for each user across the sales stages
			$index = 0;
			$datasets = array();
			$xlabels = array();
			$fills =& Image_Graph::factory('Image_Graph_Fill_Array');
			$color = array('Closed Lost'=>'#FF9900','Closed Won'=>'#009933', $other=>'#0066CC');
			foreach($stages as $stage) {
				$datasets[$index] = & Image_Graph::factory('dataset');
				foreach($datax[$stage] as $ownerid=>$owner_amt) {
					foreach($owner_amt as $i=>$y) {
					$x = 1+2*$i;
					$datasets[$index]->addPoint(
						$x,
						$y,
						array(
							'url' => $aTargets[$stage][$ownerid][$i],
							'alt' => $aAlts[$stage][$ownerid][$i]
						)
					);
					}
				}
				// Set fill colors for bars
				$fills->addColor($color[$stage]);
				$index++;
			}

			for($i=0;$i<count($months); $i++)
			{
			  $x = 1+2*$i;
			  $xlabels[$x] = $months[$i];
			  $xlabels[$x+1] = '';
			}
			
			// compute maximum value because of grace jpGraph parameter not supported
			$maximum = 0;
			foreach($months as $num=>$m) {
			  	$monthSum = 0;
				foreach($user_id as $the_id) {
					foreach($stages as $stage) $monthSum += $datax[$stage][$the_id][$num];
				}	
				if($monthSum > $maximum) $maximum = $monthSum;
				$log->debug('maximum = '.$maximum.' month = '.$m.' sum = '.$monthSum);
			}

			if($theme == "blue")
			{
				$font_color = "#212473";
			}
			else
			{
				$font_color = "#000000";
			}
			$font->setColor($font_color);

			// Create the grouped bar plot
			$gbplot = & $plotarea->addNew('bar', array($datasets, 'stacked'));
			$gbplot->setFillStyle($fills);

			//You can change the width of the bars if you like
			$gbplot->setBarWidth(50/count($months),"%");

			// set margin
			$plotarea->setPadding(array('top'=>0,'bottom'=>0,'left'=>10,'right'=>30));

			// Set white margin color
			$graph->setBackgroundColor('#F5F5F5');

			// Use a box around the plot area
			$gbplot->setBorderColor('black');

			// Use a gradient to fill the plot area
			$gbplot->setBackground(Image_Graph::factory('gradient', array(IMAGE_GRAPH_GRAD_VERTICAL, 'white', '#E5E5E5')));

			// Setup title
			$titlestr = $current_module_strings['LBL_TOTAL_PIPELINE'].$current_user->currency_symbol.$total.$app_strings['LBL_THOUSANDS_SYMBOL'];
				//$titlestr = $current_module_strings['LBL_TOTAL_PIPELINE'].$current_user->currency_symbol.$total;
			
			$title->setText($titlestr);

			// Create the xaxis labels
			$array_data =& Image_Graph::factory('Image_Graph_DataPreprocessor_Array', 
			    array($xlabels) 
			); 

			// Setup X-axis
			$xaxis =& $plotarea->getAxis(IMAGE_GRAPH_AXIS_X);
			$xaxis->setDataPreprocessor($array_data);
			$xaxis->forceMinimum(0);
			$xaxis->forceMaximum(2*count($months));
			$xaxis->setLabelInterval(1);
			$xaxis->setTickOptions(0,0);
			$xaxis->setLabelInterval(2,2);
			$xaxis->setTickOptions(5,0,2);

			// set grid
			$gridY =& $plotarea->addNew('line_grid', IMAGE_GRAPH_AXIS_Y);
			$gridY->setLineColor('#E5E5E5@0.5');


			// Add some grace to y-axis so the bars doesn't go
			// all the way to the end of the plot area
			$yaxis =& $plotarea->getAxis(IMAGE_GRAPH_AXIS_Y);
			$yaxis->forceMaximum($maximum * 1.1);
			$ticks = get_tickspacing($maximum);

			// Setup the Y-axis to be displayed in the bottom of the
			// graph. We also finetune the exact layout of the title,
			// ticks and labels to make them look nice.
			$yaxis->setAxisIntersection('max');

			// Then fix the tick marks
			$valueproc =& Image_Graph::factory('Image_Graph_DataPreprocessor_Formatted', $current_user->currency_symbol."%d");
			$yaxis->setFontSize(8);
			$yaxis->setDataPreprocessor($valueproc);
			// Arrange Y-Axis tick marks inside
			$yaxis->setLabelInterval($ticks[0]);
			$yaxis->setTickOptions(-5,0);
			$yaxis->setLabelInterval($ticks[1],2);
			$yaxis->setTickOptions(-2,0,2);
			$yaxis->setLabelOption('position','inside');

			// Finally setup the title
			$yaxis->setLabelOption('position','inside');
			
			// eliminate zero values
			$gbplot->setDataSelector(Image_Graph::factory('Image_Graph_DataSelector_NoZeros'));
			
			// set markers
			$marker =& $graph->addNew('value_marker', IMAGE_GRAPH_VALUE_Y);
			$marker->setDataPreprocessor($valueproc);
			$marker->setFillColor('000000@0.0');
			$marker->setBorderColor('000000@0.0');
			$marker->setFontColor('white');
			$marker->setFontSize(8);
			$gbplot->setMarker($marker);

			$subtitle .= $current_module_strings['LBL_OPP_SIZE'].$current_user->currency_symbol.$current_module_strings['LBL_OPP_SIZE_VALUE'];
			$footer->setText($subtitle);
			$footer->setAlignment(IMAGE_GRAPH_ALIGN_TOP_RIGHT);

			// .. and stroke the graph
			$imgMap = $graph->done(
								    array(
									        'tohtml' => true,
									        'border' => 0,
									        'filename' => $cache_file_name,
									        'filepath' => './',
									        'urlpath' => ''
									    ));
			//$imgMap = htmlspecialchars($output);
			save_image_map($cache_file_name.'.map', $imgMap);
		}
		else {
			$imgMap_fp = fopen($cache_file_name.'.map', "rb");
			$imgMap = fread($imgMap_fp, filesize($cache_file_name.'.map'));
			fclose($imgMap_fp);
		}
		$fileModTime = filemtime($cache_file_name.'.map');
		$return = "\n$imgMap";
		$log->debug("Exiting outcome_by_month method ...");
		return $return;
	}

	/**
	 * Creates lead_source_by_outcome pipeline image as a horizontal accumlated bar graph for multiple vte_users.
	 * param $datay- the lead source data to display in the x-axis
	 * param $date_start- the begin date of opps to find
	 * param $date_end- the end date of opps to find
	 * param $ids - list of assigned vte_users of opps to find
	 * param $cache_file_name - file name to write image to
	 * param $refresh - boolean whether to rebuild image if exists
	 */
	function lead_source_by_outcome($datay=array('foo','bar'), $user_id=array('1'), $cache_file_name='a_file', $refresh=false,$width=900,$height=500){
		global $log,$current_user;
		global $table_prefix;
		$log->debug("Entering lead_source_by_outcome(".$datay.",".$user_id.",".$cache_file_name.",".$refresh.") method ...");
		global $app_strings,$lang_crm, $current_module_strings,$charset, $tmp_dir,$adb;
		global $theme;

		include_once ('Image/Graph.php');
		include_once ('Image/Canvas.php');

		$log =& LoggerManager::getLogger('lead_source_by_outcome chart');
		// Set the basic parameters of the graph
		$canvas =& Image_Canvas::factory('png', array('width' => $width, 'height' => $height, 'usemap' => true));
		$imagemap = $canvas->getImageMap();
		$graph =& Image_Graph::factory('graph', $canvas);
		$log->debug("graph object created");
		// add a TrueType font
		$font =& $graph->addNew('font', calculate_font_name($lang_crm));
		// set the font size to 11 pixels
		$font->setSize(8);
		
		$graph->setFont($font);
		// create the plotarea layout
        $title =& Image_Graph::factory('title', array('Test',10));
    	$plotarea =& Image_Graph::factory('plotarea',array(
                    'axis',
                    'axis',
                    'horizontal'
                ));
        $footer =& Image_Graph::factory('title', array('Footer',8));
		$graph->add(
		    Image_Graph::vertical($title,
	        Image_Graph::vertical(
				$plotarea,
        	    $footer,
            	90
		        ),
        	5
	    	)
		);   

		if (!file_exists($cache_file_name) || !file_exists($cache_file_name.'.map') || $refresh == true) {

			$log->debug("datay is:");
			$log->debug($datay);
			$log->debug("user_id is: ");
			$log->debug($user_id);
			$log->debug("cache_file_name is: $cache_file_name");

			$params_list = Array();
			//build the where clause for the query that matches $datax
			$count = count($datax);
			if ($count>0) {
				$params_list[] = array_keys($datax);
				$where_list = " and sales_stage in (".generateQuestionMarks($datax).")";
			}

			//Now do the db queries
			//query for opportunity data that matches $datax and $user
			$select_fields = Array(
			'sales_stage','closingdate','assigned_user_id','amount',
			);
			$q = "select tablename,columnname,fieldname from ".$table_prefix."_field where fieldname in (".generateQuestionMarks($select_fields).") and tabid = ?";
			$params = Array($select_fields,getTabId('Potentials'));
			$res = $adb->pquery($q,$params);
			$columns = Array();
			if ($res && $adb->num_rows($res)>0){
				while($row = $adb->fetchByAssoc($res,-1,false)){
					if (getFieldVisibilityPermission('Potentials',$current_user->id,$row['fieldname']) == '0')
						$columns[] = $row['tablename'].".".$row['columnname'];
					else{
//						$columns[] = $row['tablename'].".".$row['columnname'];
						return false;
					}
							
				}	
			}
			$list_query = replaceSelectQuery(getListQuery('Potentials',$where_list),implode(",",$columns));
			$list_query.=" order by ".$table_prefix."_potential.amount DESC, ".$table_prefix."_potential.closingdate DESC";
//			echo $adb->convert2Sql($list_query,$adb->flatten_array($params_list));
			$res_list = $adb->pquery($list_query,$params_list);
			$opp_list = Array();
			if ($res_list && $adb->num_rows($res_list)>0){
				while($row = $adb->fetchByAssoc($res_list,-1,false)){
					$opp_list[] = $row;
				}
			}
			//build pipeline by sales stage data
			$total = 0;
			$count = array();
			$sum = array();
			$other = $current_module_strings['LBL_LEAD_SOURCE_OTHER'];
			if (isset($opp_list)) {
				foreach ($opp_list as $record) {
					//if lead source is blank, set it to the language's "none" value
					if (isset($record['leadsource']) && $record['leadsource'] != '') {
						$lead_source = $record['leadsource'];
					}
					else {
						$lead_source = $current_module_strings['NTC_NO_LEGENDS'];
					}

					if ($record['sales_stage'] == 'Closed Won' || $record['sales_stage'] == 'Closed Lost') {
						$sales_stage=$record['sales_stage'];
					}
					else {
						$sales_stage=$other;
					}

					if (!isset($sum[$lead_source][$sales_stage][$record['smownerid']])) {
						$sum[$lead_source][$sales_stage][$record['smownerid']] = 0;
					}
					if (isset($record['amount']) && in_array($record['smownerid'],$user_id))	{
						// Strip all non numbers from this string.
						$amount = convertFromMasterCurrency(preg_replace('/[^0-9]/', '', floor($record['amount'])),$current_user->conv_rate);
						$sum[$lead_source][$sales_stage][$record['smownerid']] = $sum[$lead_source][$sales_stage][$record['smownerid']] + $amount;
						if (isset($count[$lead_source][$sales_stage][$record['smownerid']])) {
							$count[$lead_source][$sales_stage][$record['smownerid']]++;
						}
						else {
							$count[$lead_source][$sales_stage][$record['smownerid']] = 1;
						}
						$total = $total + ($amount/1000);
					}
				}
			}
			$legend = array();
			$datax = array();
			$aTargets = array();
			$aAlts = array();
			$stages = array($other,'Closed Lost', 'Closed Won');
			$cvid = getCvIdOfAll("Potentials");
			$datay[''] = '';
			foreach ($datay as $lead=>$translation){
				if ($lead == '') {
					$translation = $current_module_strings['NTC_NO_LEGENDS'];
		  		}
		  		$legend[] = $translation;
			}
			foreach ($user_id as $the_id) {
				$the_user= getUserName($the_id);
				foreach ($stages as $stage) {
					foreach ($datay as $lead=>$translation){
						if ($lead == '') {
							$flag_lead = true;
							$lead = $current_module_strings['NTC_NO_LEGENDS'];
				  		}
				  		else
				  			$flag_lead = false;
						if (!isset($datax[$stage][$the_id])) {
							$datax[$stage][$the_id] = array();
						}
						if (!isset($aAlts[$stage][$the_id])) {
							$aAlts[$stage][$the_id] = array();
						}
						if (!isset($aTargets[$stage][$the_id])) {
							$aTargets[$stage][$the_id] = array();
						}
						if (isset($sum[$lead][$stage][$the_id])) {
							array_push($datax[$stage][$the_id], $sum[$lead][$stage][$the_id]/1000);
							array_push($aAlts[$stage][$the_id], $the_user.' - '.$count[$lead][$stage][$the_id]." ".$current_module_strings['LBL_OPPS_OUTCOME']." $stage");
						}
						else {
							array_push($datax[$stage][$the_id], 0);
							array_push($aAlts[$stage][$the_id], "");
						}
						$lead_string = (!$flag_lead)?urlencode($lead):'';
						array_push($aTargets[$stage][$the_id], "index.php?module=Potentials&action=ListView&leadsource=".$lead_string."&sales_stage=".urlencode($stage)."&query=true&type=dbrd&owner=".$the_user."&viewname=".$cvid);				  		
					}
				}
			}
			$log->debug("datax is:");
			$log->debug($datax);
			$log->debug("aAlts is:");
			$log->debug($aAlts);
			$log->debug("aTargets is:");
			$log->debug($aTargets);
			$log->debug("sum is:");
			$log->debug($sum);
			$log->debug("count is:");
			$log->debug($count);

			//now build the bar plots for each user across the sales stages
			$color = array('Closed Lost'=>'FF9900','Closed Won'=>'009933', $other=>'0066CC');
			$index = 0;
			$xlabels = array();
			$datasets = array();
			$fills =& Image_Graph::factory('Image_Graph_Fill_Array');
			foreach($stages as $stage) {
				// Now create a bar pot
				$datasets[$index] = & Image_Graph::factory('dataset');
				foreach($datax[$stage] as $ownerid=>$owner_amt) {
					foreach($owner_amt as $i=>$y) {
				  		$x = 1+2*$i;
				    		$datasets[$index]->addPoint(
				        		//$datay[$legend[$x]],
				        		$x,
					        	$y,
				        		array(
				            			'url' => $aTargets[$stage][$ownerid][$i],
				            			'alt' => $aAlts[$stage][$ownerid][$i],
				      		      		'target' => ''
				        		)
				    		);
					}
				}	
				// Set fill colors for bars
				$fills->addColor("#".$color[$stage]);
				$log->debug("datax[$stage] is: ");
				$log->debug($datax[$stage]);
				$index++;
			}
			
			for($i=0;$i<count($legend); $i++)
			{
				$x = 1+2*$i;
				$xlabels[$x] = $legend[$i];
				$xlabels[$x+1] = '';
			}

			// compute maximum value because of grace jpGraph parameter not supported
			$maximum = 0;
			foreach($legend as $legendidx=>$legend_text) {
			  	$dataxSum = 0;
				foreach($user_id as $the_id) {
					foreach($stages as $stage) $dataxSum += $datax[$stage][$the_id][$legendidx];
                                }
				if($dataxSum > $maximum) $maximum = $dataxSum;
			}

			if($theme == "blue")
			{
				$font_color = "#212473";
			}
			else
			{
				$font_color = "#000000";
			}
			$font->setColor($font_color);

			// Create the grouped bar plot
			$gbplot = & $plotarea->addNew('bar', array($datasets, 'stacked'));
			$gbplot->setFillStyle($fills);

			//You can change the width of the bars if you like
			$gbplot->setBarWidth(50/count($legend),"%");

			// Set white margin color
			$graph->setBackgroundColor('#F5F5F5');

			// Use a box around the plot area
			$gbplot->setBorderColor('black');

			// Use a gradient to fill the plot area
			$gbplot->setBackground(Image_Graph::factory('gradient', array(IMAGE_GRAPH_GRAD_HORIZONTAL, 'white', '#E5E5E5')));

			// Setup title
			$titlestr = $current_module_strings['LBL_ALL_OPPORTUNITIES'].$current_user->currency_symbol.$total.$app_strings['LBL_THOUSANDS_SYMBOL'];
			//$titlestr = $current_module_strings['LBL_ALL_OPPORTUNITIES'].$current_user->currency_symbol.$total;
			$title->setText($titlestr);

			// Create the xaxis labels
			$array_data =& Image_Graph::factory('Image_Graph_DataPreprocessor_Array', 
			    array($xlabels) 
			); 

			// Setup X-axis
			$xaxis =& $plotarea->getAxis(IMAGE_GRAPH_AXIS_X);
			$xaxis->setDataPreprocessor($array_data);
			$xaxis->forceMinimum(0);
			$xaxis->forceMaximum(2*count($legend));
			$xaxis->setLabelInterval(1);
			$xaxis->setTickOptions(0,0);
			$xaxis->setLabelInterval(2,2);
			$xaxis->setTickOptions(5,0,2);
			$xaxis->setInverted(true);
			
			// set grid
			$gridY =& $plotarea->addNew('line_grid', IMAGE_GRAPH_AXIS_Y);
			$gridY->setLineColor('#E5E5E5@0.5');

			// Add some grace to y-axis so the bars doesn't go
			// all the way to the end of the plot area
			$yaxis =& $plotarea->getAxis(IMAGE_GRAPH_AXIS_Y);
			$yaxis->forceMaximum($maximum * 1.1);
			$ticks = get_tickspacing($maximum);

			// Then fix the tick marks
			$yaxis->setFontSize(8);
			$yaxis->setAxisIntersection('max');
			$valueproc =& Image_Graph::factory('Image_Graph_DataPreprocessor_Formatted', $current_user->currency_symbol."%d");
			$yaxis->setDataPreprocessor($valueproc);
			$yaxis->setLabelInterval($ticks[0]);
			$yaxis->setTickOptions(-5,0);
			$yaxis->setLabelInterval($ticks[1],2);
			$yaxis->setTickOptions(-2,0,2);
			
			// eliminate zero values
			$gbplot->setDataSelector(Image_Graph::factory('Image_Graph_DataSelector_NoZeros'));
			
			// set markers
			$marker =& $graph->addNew('value_marker', IMAGE_GRAPH_VALUE_Y);
			$marker->setDataPreprocessor($valueproc);
			$marker->setFillColor('#000000@0.0');
			$marker->setBorderColor('#000000@0.0');
			$marker->setFontColor('white');
			$marker->setFontSize(8);
			$gbplot->setMarker($marker);

			// Finally setup the title
			$subtitle = $current_module_strings['LBL_OPP_SIZE'].$current_user->currency_symbol.$current_module_strings['LBL_OPP_SIZE_VALUE']; 
			$footer->setText($subtitle);
			$footer->setAlignment(IMAGE_GRAPH_ALIGN_TOP_RIGHT);

			// .. and stroke the graph
			$imgMap = $graph->done(
								    array(
									        'tohtml' => true,
									        'border' => 0,
									        'filename' => $cache_file_name,
									        'filepath' => './',
									        'urlpath' => ''
									    ));
			//$imgMap = htmlspecialchars($output);
			save_image_map($cache_file_name.'.map', $imgMap);
		}
		else {
			$imgMap_fp = fopen($cache_file_name.'.map', "rb");
			$imgMap = fread($imgMap_fp, filesize($cache_file_name.'.map'));
			fclose($imgMap_fp);
		}
		$fileModTime = filemtime($cache_file_name.'.map');
		$return = "\n$imgMap";
		$log->debug("Exiting lead_source_by_outcome method ...");
		return $return;
	}

	/**
	 * Creates opportunity pipeline image as a horizontal accumlated bar graph for multiple vte_users.
	 * param $datax- the sales stage data to display in the x-axis
	 */
	function pipeline_by_sales_stage($datax=array('foo','bar'), $date_start=false, $date_end=false, $user_id=array('1'), $cache_file_name='a_file', $refresh=false,$width=900,$height=500){
		global $log,$current_user,$adb;
		$log->debug("Entering pipeline_by_sales_stage(".$datax.",".$date_start.",".$date_end.",".$user_id.",".$cache_file_name.",".$refresh.") method ...");
		global $app_strings,$lang_crm, $current_module_strings, $charset, $tmp_dir;
		global $theme;
		global $table_prefix;
		include_once ('Image/Graph.php');
		include_once ('Image/Canvas.php');

		$log =& LoggerManager::getLogger('opportunity charts');
		// Set the basic parameters of the graph

		
		$canvas =& Image_Canvas::factory('png', array('width' => $width, 'height' => $height, 'usemap' => true));
		$imagemap = $canvas->getImageMap();
		$graph =& Image_Graph::factory('graph', $canvas);
		//$log->debug("graph object created");
		// add a TrueType font
		//$font =& $graph->addNew('font', calculate_font_name($lang_crm));
		$font =& $graph->addNew('font', calculate_font_name($lang_crm));
		// set the font size to 11 pixels
		$font->setSize(8);
		
		$graph->setFont($font);
        $title =& Image_Graph::factory('title', array('Test',10));
    	$plotarea =& Image_Graph::factory('plotarea',array(
                    'axis',
                    'axis',
                    'horizontal'
                ));
        $footer =& Image_Graph::factory('title', array('Footer',8));
		$graph->add(
		    Image_Graph::vertical($title,
	        Image_Graph::vertical(
				$plotarea,
        	    $footer,
            	90
		        ),
        	5
	    	)
		);   
		$log->debug("graph object created");
		

		if (!file_exists($cache_file_name) || !file_exists($cache_file_name.'.map') || $refresh == true) {

			$log->debug("starting pipeline chart");
			$log->debug("datax is:");
			$log->debug($datax);
			$log->debug("user_id is: ");
			$log->debug($user_id);
			$log->debug("cache_file_name is: $cache_file_name");
			
			$params_list = Array();
			//build the where clause for the query that matches $datax
			$count = count($datax);
			if ($count>0) {
				$params_list[] = array_keys($datax);
				$where_list = " and sales_stage in (".generateQuestionMarks($datax).")";
			}

			//build the where clause for the query that matches $date_start and $date_end
			if ($date_start){
				$where_list .= "and ".$table_prefix."_potential.closingdate >= ?";
				$params_list[] = $date_start;
			}	
			if ($date_end){
				$where_list .= "and ".$table_prefix."_potential.closingdate <= ?";
				$params_list[] = $date_end;
			}
			if ($date_start && $date_end)
				$subtitle = $current_module_strings['LBL_DATE_RANGE']." ".getDisplayDate($date_start)." ".$current_module_strings['LBL_DATE_RANGE_TO']." ".getDisplayDate($date_end)."\n";
			elseif ($date_start && !$date_end){
				$subtitle = $current_module_strings['LBL_DATE_RANGE']." >=".getDisplayDate($date_start)."\n";
			}
			elseif ($date_end && !$date_start){
				$subtitle = $current_module_strings['LBL_DATE_RANGE']." <=".getDisplayDate($date_end)."\n";
			}
			else{
				$subtitle = $current_module_strings['LBL_DATE_RANGE_NOT_SET']."\n";
			}

			//Now do the db queries
			//query for opportunity data that matches $datax and $user
			$select_fields = Array(
			'sales_stage','closingdate','assigned_user_id','amount',
			);
			$q = "select tablename,columnname,fieldname from ".$table_prefix."_field where fieldname in (".generateQuestionMarks($select_fields).") and tabid = ?";
			$params = Array($select_fields,getTabId('Potentials'));
			$res = $adb->pquery($q,$params);
			$columns = Array();
			if ($res && $adb->num_rows($res)>0){
				while($row = $adb->fetchByAssoc($res,-1,false)){
					if (getFieldVisibilityPermission('Potentials',$current_user->id,$row['fieldname']) == '0')
						$columns[] = $row['tablename'].".".$row['columnname'];
					else{
//						$columns[] = $row['tablename'].".".$row['columnname'];
						return false;
					}
							
				}	
			}
			$list_query = getListQuery('Potentials',$where);
			$list_query = replaceSelectQuery(getListQuery('Potentials',$where_list),implode(",",$columns));
			$list_query.=" order by ".$table_prefix."_potential.amount DESC, ".$table_prefix."_potential.closingdate DESC";
			$res_list = $adb->pquery($list_query,$params_list);
			$opp_list = Array();
			if ($res_list && $adb->num_rows($res_list)>0){
				while($row = $adb->fetchByAssoc($res_list,-1,false)){
					$opp_list[] = $row;
				}
			}
			//build pipeline by sales stage data
			$total = 0;
			$count = array();
			$sum = array();
			if (isset($opp_list)) {
				foreach ($opp_list as $record) {
					if (!isset($sum[$record['sales_stage']][$record['smownerid']])) {
						$sum[$record['sales_stage']][$record['smownerid']] = 0;
					}
					if (isset($record['amount']) && in_array($record['smownerid'],$user_id))	{
						// Strip all non numbers from this string.
						$amount = convertFromMasterCurrency(preg_replace('/[^0-9]/', '', floor($record['amount'])),$current_user->conv_rate);
						$sum[$record['sales_stage']][$record['smownerid']] = $sum[$record['sales_stage']][$record['smownerid']] + $amount;
						if (isset($count[$record['sales_stage']][$record['smownerid']])) {
							$count[$record['sales_stage']][$record['smownerid']]++;
						}
						else {
							$count[$record['sales_stage']][$record['smownerid']] = 1;
						}
						$total = $total + ($amount/1000);
					}
				}
			}
			$legend = array();
			$datay = array();
			$aTargets = array();
			$aAlts = array();
			$cvid = getCvIdOfAll("Potentials");
			foreach ($user_id as $the_id) {
				$the_user= getUserName($the_id);
				foreach ($datax as $stage_key=>$stage_translation) {
					if (!isset($datay[$the_id])) {
						$datay[$the_id] = array();
					}
					if (!isset($aAlts[$the_id])) {
						$aAlts[$the_id] = array();
					}
					if (!isset($aTargets[$the_id])) {
						$aTargets[$the_id] = array();
					}
	
					if (isset($sum[$stage_key][$the_id])) {
						array_push($datay[$the_id], $sum[$stage_key][$the_id]/1000);
						array_push($aAlts[$the_id], $the_user.' - '.$count[$stage_key][$the_id]." ".$current_module_strings['LBL_OPPS_IN_STAGE']." $stage_translation");
					}
					else {
						array_push($datay[$the_id], 0);
						array_push($aAlts[$the_id], "");
					}
					array_push($aTargets[$the_id], "index.php?module=Potentials&action=ListView&sales_stage=".urlencode($stage_key)."&closingdate_start=".urlencode($date_start)."&closingdate_end=".urlencode($date_end)."&query=true&type=dbrd&owner=".$the_user."&viewname=".$cvid);
				}
			}
			$legend = array_values($datax);
			$log->debug("datay is:");
			$log->debug($datay);
			$log->debug("aAlts is:");
			$log->debug($aAlts);
			$log->debug("aTargets is:");
			$log->debug($aTargets);
			$log->debug("sum is:");
			$log->debug($sum);
			$log->debug("count is:");
			$log->debug($count);

			//now build the bar plots for each user across the sales stages
			$colors = color_generator(count($user_id),'#D50100','#002222');
			$index = 0;
			$datasets = array();
			$xlabels = array();
			$fills =& Image_Graph::factory('Image_Graph_Fill_Array');
			foreach($user_id as $the_id) {
				// Now create a bar pot
				$datasets[$index] = & Image_Graph::factory('dataset');
				foreach($datay[$the_id] as $i => $y) {
				    $x = 1+2*$i;
				    $datasets[$index]->addPoint(
				        $x,
				        $y,
				        array(
				            'url' => $aTargets[$the_id][$i],
				            'alt' => $aAlts[$the_id][$i]
				        )
				    );
				}

				// Set fill colors for bars
				$fills->addColor($colors[$index]);

				$index++;
			}
			for($i=0;$i<count($legend); $i++)
			{
			  $x = 1+2*$i;
			  $xlabels[$x] = $legend[$i];
			  $xlabels[$x+1] = '';
			}
			
			// compute maximum value because of grace jpGraph parameter not supported
			$maximum = 0;
			foreach($legend as $legendidx=>$legend_text) {
			  	$legendsum = 0;
				foreach($user_id as $the_id) $legendsum += $datay[$the_id][$legendidx];
				if($legendsum > $maximum) $maximum = $legendsum;
			}
			// Create the grouped bar plot
			$gbplot = & $plotarea->addNew('bar', array($datasets, 'stacked'));
			$gbplot->setFillStyle($fills);

			//You can change the width of the bars if you like
			$gbplot->setBarWidth(50/count($legend),"%");


			// Set white margin color
			$graph->setBackgroundColor('#F5F5F5');

			// Use a box around the plot area
			$gbplot->setBorderColor('black');

			// Use a gradient to fill the plot area
			$gbplot->setBackground(Image_Graph::factory('gradient', array(IMAGE_GRAPH_GRAD_HORIZONTAL, 'white', '#E5E5E5')));

			if($theme == "blue")
			{
				$font_color = "#212473";
			}
			else
			{
				$font_color = "#000000";
			}
			$font->setColor($font_color);

			// Setup title
			$titlestr = $current_module_strings['LBL_TOTAL_PIPELINE'].$current_user->currency_symbol.$total.$app_strings['LBL_THOUSANDS_SYMBOL'];
			//$titlestr = $current_module_strings['LBL_TOTAL_PIPELINE'].$current_user->currency_symbol.$total;
			$title->setText($titlestr);

			// Create the xaxis labels
			$array_data =& Image_Graph::factory('Image_Graph_DataPreprocessor_Array', 
			    array($xlabels) 
			); 

		
			// Setup X-axis
			$xaxis =& $plotarea->getAxis(IMAGE_GRAPH_AXIS_X);
			$xaxis->setDataPreprocessor($array_data);
			$xaxis->forceMinimum(0);
			$xaxis->forceMaximum(2*count($legend));
			$xaxis->setLabelInterval(1);
			$xaxis->setTickOptions(0,0);
			$xaxis->setLabelInterval(2,2);
			$xaxis->setTickOptions(5,0,2);
			$xaxis->setInverted(true);

			// Setup Y-axis
			$yaxis =& $plotarea->getAxis(IMAGE_GRAPH_AXIS_Y);
			$yaxis->setFontSize(8);
			$yaxis->setAxisIntersection('max');

			// Add some grace to y-axis so the bars doesn't go
			// all the way to the end of the plot area
			$yaxis->forceMaximum($maximum * 1.1);
			$ticks = get_tickspacing($maximum);
			
			// set grid
			$gridY =& $plotarea->addNew('line_grid', IMAGE_GRAPH_AXIS_Y);
			$gridY->setLineColor('#E5E5E5@0.5');

			// First make the labels look right
			$valueproc =& Image_Graph::factory('Image_Graph_DataPreprocessor_Formatted', $current_user->currency_symbol."%d");
			$yaxis->setDataPreprocessor($valueproc);
			$yaxis->setLabelInterval($ticks[0]);
			$yaxis->setTickOptions(-5,0);
			$yaxis->setLabelInterval($ticks[1],2);
			$yaxis->setTickOptions(-2,0,2);
			
			// eliminate zero values
			$gbplot->setDataSelector(Image_Graph::factory('Image_Graph_DataSelector_NoZeros'));
			
			// set markers
			$marker =& $graph->addNew('value_marker', IMAGE_GRAPH_VALUE_Y);
			$marker->setDataPreprocessor($valueproc);
			$marker->setFillColor('000000@0.0');
			$marker->setBorderColor('000000@0.0');
			$marker->setFontColor('white');
			$marker->setFontSize(8);
			$gbplot->setMarker($marker);

			// Finally setup the title

			$subtitle .= $current_module_strings['LBL_OPP_SIZE'].$current_user->currency_symbol.$current_module_strings['LBL_OPP_SIZE_VALUE']; 
			$footer->setText($subtitle);
			$footer->setAlignment(IMAGE_GRAPH_ALIGN_TOP_RIGHT);

			// .. and stroke the graph
			$imgMap = $graph->done(
								    array(
									        'tohtml' => true,
									        'border' => 0,
									        'filename' => $cache_file_name,
									        'filepath' => './',
									        'urlpath' => ''
									    ));
			//$imgMap = $graph->GetHTMLImageMap('pipeline');
			save_image_map($cache_file_name.'.map', $imgMap);
		}
		else {
			$imgMap_fp = fopen($cache_file_name.'.map', "rb");
			$imgMap = fread($imgMap_fp, filesize($cache_file_name.'.map'));
			fclose($imgMap_fp);
		}
		$fileModTime = filemtime($cache_file_name.'.map');
		$return = "\n$imgMap";
		$log->debug("Exiting pipeline_by_sales_stage method ...");
		return $return;
	}

	/**
	 * Creates pie chart image of opportunities by lead_source.
	 * param $datax- the sales stage data to display in the x-axis
	 * param $datay- the sum of opportunity amounts for each opportunity in each sales stage
	 * to display in the y-axis
	 */
	function pipeline_by_lead_source($legends=array('foo','bar'), $user_id=array('1'), $cache_file_name='a_file', $refresh=true,$width=900,$height=500){
		global $log,$current_user;
		$log->debug("Entering pipeline_by_lead_source(".$legends.") method ...");
		global $app_strings,$lang_crm, $current_module_strings, $log, $charset, $tmp_dir,$adb;
		global $theme;
		global $table_prefix;
		include_once ('Image/Graph.php');
		include_once ('Image/Canvas.php');

		$font = calculate_font_name($lang_crm);
		if (!file_exists($cache_file_name) || !file_exists($cache_file_name.'.map') || $refresh == true) {
			$log =& LoggerManager::getLogger('opportunity charts');
			$log->debug("starting pipeline chart");
			$log->debug("legends is:");
			$log->debug($legends);
			$log->debug("user_id is: ");
			$log->debug($user_id);
			$log->debug("cache_file_name is: $cache_file_name");

			//Now do the db queries
			//query for opportunity data that matches $legends and $user
			$count = count($legends);
			if ($count>0) {
				$params_list[] = array_keys($legends);
				$where_list = " and leadsource in (".generateQuestionMarks($legends).")";
			}
			$select_fields = Array(
			'leadsource','amount',
			);
			$q = "select tablename,columnname,fieldname from ".$table_prefix."_field where fieldname in (".generateQuestionMarks($select_fields).") and tabid = ?";
			$params = Array($select_fields,getTabId('Potentials'));
			$res = $adb->pquery($q,$params);
			$columns = Array();
			if ($res && $adb->num_rows($res)>0){
				while($row = $adb->fetchByAssoc($res,-1,false)){
					if (getFieldVisibilityPermission('Potentials',$current_user->id,$row['fieldname']) == '0')
						$columns[] = $row['tablename'].".".$row['columnname'];
					else{
//						$columns[] = $row['tablename'].".".$row['columnname'];
						return false;
					}
							
				}	
			}
			$list_query = getListQuery('Potentials',$where);
			$list_query = replaceSelectQuery(getListQuery('Potentials',$where_list),implode(",",$columns));
			$list_query.=" order by ".$table_prefix."_potential.amount DESC, ".$table_prefix."_potential.closingdate DESC";
//			echo $adb->convert2Sql($list_query,$adb->flatten_array($params_list));
			$res_list = $adb->pquery($list_query,$params_list);
			$opp_list = Array();
			if ($res_list && $adb->num_rows($res_list)>0){
				while($row = $adb->fetchByAssoc($res_list,-1,false)){
					$opp_list[] = $row;
				}
			}

			//build pipeline by lead source data
			$total = 0;
			$count = array();
			$sum = array();
			if (isset($opp_list)) {
				foreach ($opp_list as $record) {
					if (!isset($sum[$record['leadsource']])) $sum[$record['leadsource']] = 0;
						// Strip all non numbers from this string.
						$amount = convertFromMasterCurrency(preg_replace('/[^0-9]/', '', floor($record['amount'])),$current_user->conv_rate);
						$sum[$record['leadsource']] = $sum[$record['leadsource']] + ($amount/1000);
						if (isset($count[$record['leadsource']])) $count[$record['leadsource']]++;
						else $count[$record['leadsource']] = 1;
						$total = $total + ($amount/1000);
				}
			}

			$visible_legends = array();
			$data= array();
			$aTargets = array();
			$aAlts = array();
			$cvid = getCvIdOfAll("Potentials");
			foreach ($legends as $lead_source_key=>$lead_source_translation) {
				if (isset($sum[$lead_source_key]))
				{
					array_push($data, $sum[$lead_source_key]);
					if($lead_source_key != '')
					{
						array_push($visible_legends, $lead_source_translation);
					}
					else
					{
						// put none in if the vte_field is blank.
						array_push($visible_legends, $current_module_strings['NTC_NO_LEGENDS']);
					}
					array_push($aTargets, "index.php?module=Potentials&action=ListView&leadsource=".urlencode($lead_source_key)."&query=true&type=dbrd&viewname=".$cvid);
					array_push($aAlts, $count[$lead_source_key]." ".$current_module_strings['LBL_OPPS_IN_LEAD_SOURCE']." $lead_source_translation	");
				}
			}
			$log->debug("sum is:");
			$log->debug($sum);
			$log->debug("count is:");
			$log->debug($count);
			$log->debug("total is: $total");
			if ($total == 0) {
$log->debug("Exiting pipeline_by_lead_source method ...");
				return ($current_module_strings['ERR_NO_OPPS']);
			}

			if($theme == "blue")
			{
				$font_color = "#212473";
			}
			else
			{
				$font_color = "#000000";
			}

	
			$canvas =& Image_Canvas::factory('png', array('width' => $width, 'height' => $height, 'usemap' => true));
			$imagemap = $canvas->getImageMap();
			$graph =& Image_Graph::factory('graph', $canvas);
	
			$font =& $graph->addNew('font', calculate_font_name($lang_crm));
			// set the font size to 11 pixels
			$font->setSize(8);
			$font->setColor($font_color);
			
			$graph->setFont($font);
			// create the plotarea layout
	        $title =& Image_Graph::factory('title', array('Test',10));
	    	$plotarea =& Image_Graph::factory('plotarea',array(
                    'category',
                    'axis'
                ));
	        $footer =& Image_Graph::factory('title', array('Footer',8));
			$graph->add(
			    Image_Graph::vertical($title,
		        Image_Graph::vertical(
					$plotarea,
	        	    $footer,
	            	90
			        ),
	        	5
		    	)
			);   

			// Generate colours
			$colors = color_generator(count($visible_legends),'#33CCFF','#3322FF');
			$index = 0;
			$dataset = & Image_Graph::factory('dataset');
			$fills =& Image_Graph::factory('Image_Graph_Fill_Array');
			foreach($visible_legends as $legend) {
			    $dataset->addPoint(
			        $legend,
			        $data[$index],
			        array(
			            'url' => $aTargets[$index],
			            'alt' => $aAlts[$index]
			        )
			    );
				$fills->addColor($colors[$index]);
			    $log->debug('point ='.$legend.','.$data[$index]);

				$index++;
			}

			// create the pie chart and associate the filling colours			
			$gbplot = & $plotarea->addNew('pie', $dataset);
			$plotarea->hideAxis();
			$gbplot->setFillStyle($fills);

			// Setup title
			$titlestr = $current_module_strings['LBL_TOTAL_PIPELINE'].$current_user->currency_symbol.$total.$app_strings['LBL_THOUSANDS_SYMBOL'];
			//$titlestr = $current_module_strings['LBL_TOTAL_PIPELINE'].$current_user->currency_symbol.$total;

			$title->setText($titlestr);

			// format the data values
			$valueproc =& Image_Graph::factory('Image_Graph_DataPreprocessor_Formatted', $current_user->currency_symbol."%d");

			// set markers
			$marker =& $graph->addNew('value_marker', IMAGE_GRAPH_VALUE_Y);
			$marker->setDataPreprocessor($valueproc);
			$marker->setFillColor('#FFFFFF');
			$marker->setBorderColor($font_color);
			$marker->setFontColor($font_color);
			$marker->setFontSize(8);
			$pointingMarker =& $graph->addNew('Image_Graph_Marker_Pointing_Angular', array(20, &$marker));
			$gbplot->setMarker($pointingMarker);
			
			// set legend
			$legend_box =& $plotarea->addNew('legend');
			$legend_box->setPadding(array('top'=>20,'bottom'=>0,'left'=>0,'right'=>0));
			$legend_box->setFillColor('#F5F5F5');
			$legend_box->showShadow();

			$subtitle = $current_module_strings['LBL_OPP_SIZE'].$current_user->currency_symbol.$current_module_strings['LBL_OPP_SIZE_VALUE'];
			$footer->setText($subtitle);
			$footer->setAlignment(IMAGE_GRAPH_ALIGN_TOP_LEFT);

			$imgMap = $graph->done(
								    array(
									        'tohtml' => true,
									        'border' => 0,
									        'filename' => $cache_file_name,
									        'filepath' => './',
									        'urlpath' => ''
									    ));
			//$imgMap = htmlspecialchars($output);
			save_image_map($cache_file_name.'.map', $imgMap);
		}
		else {
			$imgMap_fp = fopen($cache_file_name.'.map', "rb");
			$imgMap = fread($imgMap_fp, filesize($cache_file_name.'.map'));
			fclose($imgMap_fp);
		}
		$fileModTime = filemtime($cache_file_name.'.map');
		$return = "\n$imgMap";
		$log->debug("Exiting pipeline_by_lead_source method ...");
		return $return;

	}

}


/**
 * Creates a file with the image map
 * param $filename - file name to save to
 * param $image_map - image map string to save
 */
function save_image_map($filename,$image_map)
{
	global $log;
	$log->debug("Entering save_image_map(".$filename.",".$image_map.") method ...");
	// save the image map to file
	$log =& LoggerManager::getLogger('save_image_file');

	if (!$handle = fopen($filename, 'w')) {
		$log->debug("Cannot open file ($filename)");
		$log->debug("Exiting save_image_map method ...");
		return;
	}

	// Write $somecontent to our opened file.
	if (fwrite($handle, $image_map) === FALSE) {
	   $log->debug("Cannot write to file ($filename)");
	   $log->debug("Exiting save_image_map method ...");
	   return false;
	}

	$log->debug("Success, wrote ($image_map) to file ($filename)");

	fclose($handle);
	$log->debug("Exiting save_image_map method ...");
	return true;

}

// retrieve the translated strings.
$app_strings = return_application_language($current_language);

if(isset($app_strings['LBL_CHARSET']))
{
	$charset = $app_strings['LBL_CHARSET'];
}
else
{
	$charset = $default_charset;
}


?>
