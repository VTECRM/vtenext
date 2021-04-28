<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@34559 */

class TouchGetOverrideFile extends TouchWSClass {

	protected $overrideDir = 'modules/Touch/overrides';

	public function process(&$request) {
	
		$getContent = ($request['get_content'] == '1');

		$files = glob($this->overrideDir.'/*.js');
		if ($files && count($files) > 0) {
			foreach ($files as $k=>$f) {
				if (!is_readable($f) || filesize($f) <= 0) {
					unset($files[$k]);
				}
			}
		} else {
			$files = array();
		}
		
		// crmv@78141
		$cssFiles = glob($this->overrideDir.'/css_overrides/*.css');
		if ($cssFiles && count($cssFiles) > 0) {
			foreach ($cssFiles as $k=>$f) {
				if (is_readable($f) && filesize($f) > 0) {
					$files[] = $f;
				}
			}
		}
	
		$cssFiles = glob($this->overrideDir.'/css_extra/*.css');
		if ($cssFiles && count($cssFiles) > 0) {
			foreach ($cssFiles as $k=>$f) {
				if (is_readable($f) && filesize($f) > 0) {
					$files[] = $f;
				}
			}
		}
		// crmv@78141e
		
		$return = array('overrides' => array());
		foreach ($files as $f) {
			$content = array('name' => $f);
			if ($getContent) {
				$content['content'] = @file_get_contents($f);
				$content['modifiedtime'] = @filemtime($f) ?: time();
			}
			$return['overrides'][] = $content;
		}
		
		return $this->success($return);
	}
}
