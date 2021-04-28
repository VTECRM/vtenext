<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@91979 */

/**
 * This class displays the maintenance page or the maintenance message for other API.
 * This file is completely independent from the rest of VTE, so any change in other
 * files or database won't affect the maintenance message.
 */
class MaintenanceMode {

	static $strings = array(
		'it_it' => array(
			'PAGE_TITLE' => 'Manutenzione',
			'MAIN_TEXT' => 'Il sistema &egrave; in manutenzione',
		),
		'en_us' => array(
			'PAGE_TITLE' => 'Maintenance',
			'MAIN_TEXT' => 'The system is in maintenance mode',
		),
		'de_de' => array(
			'PAGE_TITLE' => 'Wartungsmodus',
			'MAIN_TEXT' => 'Das System ist im Wartungsmodus',
		),
		'pt_br' => array(
			'PAGE_TITLE' => 'Manuten&ccedil;&atilde;o',
			'MAIN_TEXT' => 'O sistema est&aacute; em modo de manuten&ccedil;&atilde;o',
		),
	);
	
	/**
	 * Check if the system should enter the maintenance state
	 */
	public static function check() {
		if (is_readable('maintenance.php')) {
			include('maintenance.php');
			return ($vte_maintenance === true || $vte_maintenance === 1);
		}
		return false;
	}
	
	protected static function getLangStrings($language = null) {
		
		// set the language
		if (empty($language) && is_readable('config.inc.php')) {
			@include('config.inc.php');
			if (array_key_exists($default_language, self::$strings)) {
				$language = $default_language;
			}
		}
		if (empty($language)) $language = 'en_us';
		
		$strings = self::$strings[$language];
		return $strings;
	}
	
	/**
	 * Display the maintenance mode page.
	 */
	public static function display($language = null) {
	
		$strings = self::getLangStrings($language);
		
		// check for ajax page
		$ajaxPage = preg_match("/Ajax$/", $_REQUEST['action']);
		
		if ($ajaxPage) {
			echo '<script type="text/javascript" language="Javascript">window.location.reload();</script>';
			return;
		}
	
		$str = <<<EOM
<html>
	<head>
		<title>{$strings['PAGE_TITLE']}</title>
		<meta http-equiv="X-UA-Compatible" content="IE=9" />
		<style type="text/css">
			body {
				padding: 0px;
				margin: 0px;
				width: 100%;
				font-family: sans-serif;
				overflow: hidden;
			}
			.largeContainer {
				width: 960px;
				position: relative;
				text-align: center;
				padding: 20px;
				margin: auto;
			}
			.responseDiv {
				border-radius: 8px;
				-moz-border-radius: 8px;
			-webkit-border-radius: 8px;
				width: 90%;
				margin: auto;
				font-size: 16pt;
				padding: 32px;

				border: 1px solid #203050;
				color: #e0e0ff;
				background: rgb(96,108,136); /* Old browsers */
				background: -moz-linear-gradient(top,  rgba(96,108,136,1) 0%, rgba(63,76,107,1) 100%); /* FF3.6+ */
				background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,rgba(96,108,136,1)), color-stop(100%,rgba(63,76,107,1))); /* Chrome,Safari4+ */
				background: -webkit-linear-gradient(top,  rgba(96,108,136,1) 0%,rgba(63,76,107,1) 100%); /* Chrome10+,Safari5.1+ */
				background: -o-linear-gradient(top,  rgba(96,108,136,1) 0%,rgba(63,76,107,1) 100%); /* Opera 11.10+ */
				background: -ms-linear-gradient(top,  rgba(96,108,136,1) 0%,rgba(63,76,107,1) 100%); /* IE10+ */
				background: linear-gradient(to bottom,  rgba(96,108,136,1) 0%,rgba(63,76,107,1) 100%); /* W3C */
				filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#606c88', endColorstr='#3f4c6b',GradientType=0 ); /* IE6-9 */
			}
		</style>
	</head>
	<body>
		<div class="largeContainer">
			<div class="responseDiv">
				{$strings['MAIN_TEXT']}
			</div>
		</div>
		<script type="text/javascript" language="Javascript">
			// auto reload page
			setInterval(function() {
				location.reload();
			}, 30000);
		</script>
	</body>
</html>
EOM;
		echo $str;
	}
	
	/**
	 * Display the maintenance message for standard webservices
	 */
	public static function displayWS($language = null) {
		
		$strings = self::getLangStrings($language);
		$response = array('success' => false, 'error' => $strings['MAIN_TEXT']);
		
		echo json_encode($response);
	}
	
	/**
	 * Display the maintenance message for Touch (app) webservices
	 */
	public static function displayTouchWS($language = null) {
		
		$strings = self::getLangStrings($language);
		$response = array('success' => false, 'error' => $strings['MAIN_TEXT']);
		
		echo json_encode($response);
	}
	
	/**
	 * Display the maintenance message for the cron process
	 */
	public static function displayCron($language = null) {
		$strings = self::getLangStrings($language);
		// do nothing by default, if you want, you can print a message
		//error_log($strings['MAIN_TEXT']);
	}
	
}