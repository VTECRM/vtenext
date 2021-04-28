<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@167416 crmv@183041 */

/**
 * Class to handle OS-related functions
 */
class OSUtils {
		
	/**
	 * Used to get the MAC address of the host server. It works with Linux,
	 * Darwin (Mac OS X), and Win XP. It may work with others as some other
	 * os's have similar ifconfigs to Darwin but they haven't been tested
	 *
  	 * @return string Mac address if found
  	 * @return string ERROR_OPEN means config can't be found and thus not opened
  	 * @return string MAC_404 means mac adress doesn't exist in the config file
  	 * @return string SAFE_MODE means server is in safe mode so config can't be read
	 */
	public static function getMACAddress()
	{
		if(ini_get('safe_mode')) {
			// returns invalid because server is in safe mode thus not allowing 
			// sbin reads but will still allow it to open. a bit weird that one.
			return 'SAFE_MODE';
		}
		
		$os = strtolower(PHP_OS);
		if(substr($os, 0, 3)=='win') {
			$lines = array();
			@exec('ipconfig/all', $lines);
			if(count($lines) == 0) return 'ERROR_OPEN';
			// seperate the lines for analysis
			foreach ($lines as $key=>$line) {
				if(preg_match("/([0-9a-f][0-9a-f][-:]){5}([0-9a-f][0-9a-f])/i", $line)) {
					$trimmed_line = trim($line);
					return trim(substr($trimmed_line, strrpos($trimmed_line, " ")));
				}
			}
		} else {
			$mac_delim_list = array('HWaddr','ether','IndirizzoHW');
			
			// open the ipconfig
			$fp = @popen('/sbin/ifconfig', "rb");
			// returns invalid, cannot open ifconfig
			if (!$fp) return 'ERROR_OPEN';
			// read the config
			$conf = @fread($fp, 4096);
			@pclose($fp);
			
			// get the pos of the os_var to look for
			foreach($mac_delim_list as $mac_delim) {
				$pos = strpos($conf, $mac_delim);
				if ($pos !== false) break;
			}			
			if($pos) {
				$str1 = trim(substr($conf, ($pos+strlen($mac_delim))));
				$str1 = trim(substr($str1, 0, strpos($str1, "\n")));
				list($str1) = explode(' ',$str1);
				return $str1;
			}
		}
		
		// failed to find the mac address
		return 'MAC_404'; 
	}
	
	// crmv@187020
	/**
	 * Return a unique identifier for this host
	 */
	public static function getHostId() {
		static $hostid = null;
		if (is_null($hostid)) {
			$hostid = md5(gethostname().'-'.self::getMACAddress());
		}
		return $hostid;
	}
	// crmv@187020e
		
}