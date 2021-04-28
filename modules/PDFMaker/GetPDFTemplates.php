<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
    global $table_prefix;
    $fieldvalue = array();
    
    $workflowid = addslashes($_REQUEST["workflowid"]);
    
    if (isset($_REQUEST["none"]) && $_REQUEST["none"] == "yes")
    {
        $fieldvalue[]= "0@none";
    }
   
    $sql1="SELECT module_name FROM com_".$table_prefix."_workflows WHERE workflow_id = '".$workflowid."'";
		$module_name = $adb->getOne($sql1,0,'module_name');
   
    $sql2 = "SELECT templateid, filename FROM ".$table_prefix."_pdfmaker WHERE module = ? ORDER BY filename ASC";
    $res = $adb->pquery($sql2, array($module_name));
    
    $num_rows = $adb->num_rows($res);
    
    if ($num_rows > 0)
    {
    		for($i=0;$i<$adb->num_rows($res);$i++)
    		{
    				$tid=$adb->query_result($res,$i,"templateid");
    				$tname=$adb->query_result($res,$i,"filename");	
            $fieldvalue[]=$tid."@".$tname;
    		}
    } 
    
    echo implode("###",$fieldvalue);
?>