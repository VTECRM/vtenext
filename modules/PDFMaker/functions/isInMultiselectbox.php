<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/


function isInMultiselectbox($value,$search){
	global $language;
	
	$app_strings = return_application_language($language);
	
  $Values = explode(" |##| ",$value);

  if (in_array($search,$Values))
	   $s = $app_strings["LBL_YES"];
	else  
     $s = $app_strings["LBL_NO"];

	return $s;
}