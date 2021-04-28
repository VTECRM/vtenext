<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* Calls multiple webservices at once. Useful to make only one request to several
 * quick webservices */

class TouchMultiCall extends TouchWSClass {

	public function process(&$request) {
		// array in the form [wsname=>'', 'params'=>array())
		$wslist = Zend_Json::decode($request['wslist']);
		
		if (!is_array($wslist)) return $this->error('Wrong data supplied');
		
		$globalWsOutput = array();
		foreach ($wslist as $multiwsinfo) {
			$wsname = $multiwsinfo['wsname'];
			$wsparams = $multiwsinfo['wsparams'];
			
			$wsOut = $this->subcall($wsname, $wsparams);
			$globalWsOutput[$wsname] = $wsOut;
		}

		return $this->success($globalWsOutput);
		
	}
}
