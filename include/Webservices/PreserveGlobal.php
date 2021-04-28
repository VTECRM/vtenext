<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class VTWS_PreserveGlobal{
	
	private static $globalData = array();
	
	static function preserveGlobal($name,$value){
		//$name store the name of the global.
		global $$name;
		
		if(!is_array(VTWS_PreserveGlobal::$globalData[$name])){
			VTWS_PreserveGlobal::$globalData[$name] = array();
			VTWS_PreserveGlobal::$globalData[$name][] = $$name;
		}
		$$name = $value;
		return $$name;
	}
	
	static function restore($name){
		//$name store the name of the global.
		global $$name;
		
		if(is_array(VTWS_PreserveGlobal::$globalData[$name]) && count(VTWS_PreserveGlobal::$globalData[$name]) > 0){
			$$name = array_pop(VTWS_PreserveGlobal::$globalData[$name]);
		}
		$$name;
	}
	
	static function getGlobal($name){
		global $$name;
		return VTWS_PreserveGlobal::preserveGlobal($name,$$name);
	}
	
	static function flush(){
		foreach (VTWS_PreserveGlobal::$globalData as $name => $detail) {
			//$name store the name of the global.
			global $$name;
			if(is_array(VTWS_PreserveGlobal::$globalData[$name]) && count(VTWS_PreserveGlobal::$globalData[$name]) > 0) {
				$$name = array_pop(VTWS_PreserveGlobal::$globalData[$name]);
			}
		}
		VTWS_PreserveGlobal::$globalData = array(); //crmv@31461
	}
	
}

?>