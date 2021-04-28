<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@208173 */

      /** to get the details of a KeyMetrics on Home page 
        * @returns  $customviewlist Array in the following format
	* $values = Array('Title'=>Array(0=>'image name',
	*				 1=>'Key Metrics',
	*			 	 2=>'home_metrics'
	*			 	),
	*		  'Header'=>Array(0=>'Metrics',
	*	  			  1=>'Count'
	*			  	),
	*		  'Entries'=>Array($cvid=>Array(
	*			  			0=>$customview name,
	*						1=>$no of records for the view
	*					       ),
	*				   $cvid=>Array(
        *                                               0=>$customview name,
        *                                               1=>$no of records for the view
        *                                              ),
	*					|
	*					|
        *				   $cvid=>Array(
        *                                               0=>$customview name,
        *                                               1=>$no of records for the view
        *                                              )	
	*				  )
	*
       */
function getKeyMetrics($calCnt)
{
	require_once('modules/CustomView/CustomView.php');

	global $app_strings;
	global $adb;
	global $log;
	global $display_empty_home_blocks;

	$log = LoggerManager::getLogger();

	$metriclists = getMetricList();
	
	// Determine if the KeyMetrics widget should appear or not?
	if($calCnt == 'calculateCnt') {
		return count($metriclists);
	}
	
	$log->info("Metrics :: Successfully got MetricList to be displayed");
	if(isset($metriclists))
	{
		global $current_user;
		foreach ($metriclists as $key => $metriclist) {
			$queryGenerator = QueryGenerator::getInstance($metriclist['module'], $current_user);
			$queryGenerator->initForCustomViewById($metriclist['id']);
			$metricsql = $queryGenerator->getQuery();
			$metricsql = mkCountQuery($metricsql);
			$metricresult = $adb->query($metricsql);
			if($metricresult)
			{
				//crmv@16312
				$rowcount = $adb->fetch_array($metricresult);	
				if(isset($rowcount))
				{
					$metriclists[$key]['count'] = $rowcount['count'];
				}
				//crmv@16312 end
			}
		}
		$log->info("Metrics :: Successfully build the Metrics");
	}
	$title= [];
	$title[] = 'keyMetrics.gif';
	$title[] = $app_strings['LBL_HOME_KEY_METRICS'];
	$title[] = 'home_metrics';

	$header = [];
	$header[] = $app_strings['LBL_HOME_METRICS'];
	$header[] = $app_strings['LBL_MODULE'];
	$header[] = $app_strings['LBL_HOME_COUNT'];
	$entries = [];
	if(isset($metriclists))
	{
		foreach($metriclists as $metriclist)
		{
			$value = [];
			$CVname = (strlen($metriclist['name']) > 20) ? (substr($metriclist['name'],0,20).'...') : $metriclist['name'];
			$value[]='<a href="index.php?action=ListView&module='.$metriclist['module'].'&viewname='.$metriclist['id'].'">'.$CVname . '</a> <font style="color:#6E6E6E;">('. $metriclist['user'] .')</font>';
			$value[]='<a href="index.php?action=ListView&module='.$metriclist['module'].'&viewname='.$metriclist['id'].'">'.getTranslatedString($metriclist['module']). '</a>';
			$value[]='<a href="index.php?action=ListView&module='.$metriclist['module'].'&viewname='.$metriclist['id'].'">'.$metriclist['count'].'</a>';
			$entries[$metriclist['id']]=$value;
		}

	}
	$values=[
	    'Title'=>$title,
        'Header'=>$header,
        'Entries'=>$entries
    ];
	if ( ($display_empty_home_blocks ) || (count($value)!= 0) )
		return $values;
}
	
	/** to get the details of a customview Entries
	  * @returns  $metriclists Array in the following format
	  * $customviewlist []= Array('id'=>custom view id,
	  *                         'name'=>custom view name,
	  *                         'module'=>modulename,
	  			    'count'=>''
			           )	
	 */
function getMetricList()
{
	global $adb, $current_user,$table_prefix;
	require('user_privileges/user_privileges_'.$current_user->id.'.php');
	
	$sql = "select {$table_prefix}_customview.* from {$table_prefix}_customview inner join {$table_prefix}_tab on {$table_prefix}_tab.name = {$table_prefix}_customview.entitytype";
    $sql .= " where ".$table_prefix."_customview.setmetrics = 1 ";
	$sparams = [];
	
	if(!is_admin($current_user)){
        $sql .= " and ({$table_prefix}_customview.status=0
         or {$table_prefix}_customview.userid = ?
         or {$table_prefix}_customview.status =3
         or {$table_prefix}_customview.userid in(select {$table_prefix}_user2role.userid from {$table_prefix}_user2role
         inner join {$table_prefix}_users on {$table_prefix}_users.id={$table_prefix}_user2role.userid 
         inner join {$table_prefix}_role on {$table_prefix}_role.roleid={$table_prefix}_user2role.roleid
          where {$table_prefix}_role.parentrole like '{$current_user_parent_role_seq}::%'))";
	      array_push($sparams, $current_user->id);
	}
    $sql .= " order by ".$table_prefix."_customview.entitytype, {$table_prefix}_customview.viewname"; // crmv@118341
	$result = $adb->pquery($sql, $sparams);
	while($cvrow=$adb->fetch_array($result))
	{
		$metricslist = [];
		
		if(vtlib_isModuleActive($cvrow['entitytype'])){
			$metricslist['id'] = $cvrow['cvid'];
			$metricslist['name'] = $cvrow['viewname'];
			$metricslist['module'] = $cvrow['entitytype'];
			$metricslist['user'] = getUserName($cvrow['userid']);
			$metricslist['count'] = '';
			if(isPermitted($cvrow['entitytype'],"index") == "yes"){
				$metriclists[] = $metricslist;
			}
		}
	}

	return $metriclists;
}