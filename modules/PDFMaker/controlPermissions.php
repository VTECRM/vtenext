<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
    $source_path=getcwd()."/modules/PDFMaker/torewrite";
    $dir_iterator = new RecursiveDirectoryIterator($source_path);
    $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);
    $i = 0;
    $p_errors = 0;
    
    $updateFilesToCheck=false;
    if((isset($controlPermissionsUpdate) && $controlPermissionsUpdate===true) || (isset($_REQUEST["controlPermissionsUpdate"]) && $_REQUEST["controlPermissionsUpdate"]=="true"))
    {
		$updateFilesToCheck=array();
		if((isset($to126) && $to126=="true") || (isset($_REQUEST["to126"]) && $_REQUEST["to126"]=="true"))      
			array_push($updateFilesToCheck, "index.php","Smarty_setup.php","EditView.php");
		if((isset($to127) && $to127=="true") || (isset($_REQUEST["to127"]) && $_REQUEST["to127"]=="true")) 
			array_push($updateFilesToCheck, "index.php","ListViewEntries.tpl","InventoryDetailView.tpl");
		if((isset($to128) && $to128=="true") || (isset($_REQUEST["to128"]) && $_REQUEST["to128"]=="true")) 
			array_push($updateFilesToCheck, "index.php");
		
		$updateFilesToCheck = array_unique($updateFilesToCheck);
    }
     
    foreach ($iterator as $file) 
    {
        $dest=substr($file, strlen($source_path)+1);        
        if($updateFilesToCheck!=false && !in_array(basename($file), $updateFilesToCheck))
			continue;        
        if($file->isFile())
        { 
			if (!vtlib_isWriteable($dest))
			{
				$permission = "<font color='red'>".$mod_strings["LBL_CHANGE_PERMISSION"]."</font>";
				$p_errors++;
			} else
				$permission = "<font color='green'>OK</font>";
			
			if (substr($dest, 0, 8) == "modules/")
				$s = $i + 10000;
			elseif (substr($dest, 0, 7) == "Smarty/")
				$s = $i + 20000;
			else
				$s = $i;
			
			$SeqLines[] = $s;
			$Lines[$s] = "- ".$dest." ".$permission."<br>";
        }
       
        $i++;         
    }
      
    
    sort($SeqLines);
    
    $list_permissions = "<b>";
    foreach ($SeqLines AS $s)
    {
        $list_permissions .= $Lines[$s];
    }
    $list_permissions .= "</b>";
    
    $list_permissions .= "<input type='hidden' id='bad_files' value='".$p_errors."'>";
    
    
    if (isset($_REQUEST["action"]) && $_REQUEST["action"] == "PDFMakerAjax") echo $list_permissions; 
?>