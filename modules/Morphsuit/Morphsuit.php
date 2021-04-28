<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
class Morphsuit {
	
	var $vteFreeServer;
	var $vteUpdateServer;
	var $vteActivationMail = 'activate@vtecrm.com';
	
	function __construct() {
		eval(Users::m_de_cryption());
		eval($hash_version[19]);
	}
	
	function vtlib_handler($modulename, $event_type) {
		if($event_type == 'module.postinstall') {
			global $adb,$table_prefix;
			// Mark the module as Standard module
			$adb->pquery('UPDATE '.$table_prefix.'_tab SET customized=0 WHERE name=?', array($modulename));
			
			$module = Vtecrm_Module::getInstance('Morphsuit');
			$module->addLink('HEADERSCRIPT', 'MorphsuitCommonScript', 'modules/Morphsuit/MorphsuitCommon.js');
			$module->hide(array('hide_module_manager'=>1,'hide_profile'=>1,'hide_report'=>1));

		} else if($event_type == 'module.disabled') {
			// TODO Handle actions when this module is disabled.
		} else if($event_type == 'module.enabled') {
			// TODO Handle actions when this module is enabled.
		} else if($event_type == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
		} else if($event_type == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
		} else if($event_type == 'module.postupdate') {
			// TODO Handle actions after this module is updated.
		}
	}
	
	function morph_par($value) {
		$sResult = '';
		$sData = $value;
	    for($i=0;$i<strlen($sData);$i++){
	        $sChar    = substr($sData, $i, 1);
	        $sKeyChar = substr('sKey', ($i % strlen('sKey')) - 1, 1);
	        $sChar    = chr(ord($sChar) + ord($sKeyChar));
	        $sResult .= $sChar;
	    }
	    $sResult = base64_encode($sResult);
    	$sResult = strtr($sResult, '+/', '-_'); 
	    $sResult = str_rot13($sResult);
	    return $sResult;
	}
}
?>