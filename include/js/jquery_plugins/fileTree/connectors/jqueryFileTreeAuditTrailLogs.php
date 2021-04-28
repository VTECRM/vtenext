<?php
//
// jQuery File Tree PHP Connector
//
// Version 1.01
//
// Cory S.N. LaViska
// A Beautiful Site (http://abeautifulsite.net/)
// 24 March 2008
//
// History:
//
// 1.01 - updated to work with foreign characters in directory/file names (12 April 2008)
// 1.00 - released (24 March 2008)
//
// Output a list of files for jQuery File Tree
//
/* crmv@203590 */

$ftc = new FileTreeConnectorAuditTrailLogs();
$ftc->process($_REQUEST);


class FileTreeConnectorAuditTrailLogs {

	public $config = array(
		'root' => '../../../../../logs/AuditTrail/',
		'show_hidden' => false,
		'show_subdirs' => true,
		'allow_types' => array('gz'),
		'fs_encoding' => 'UTF-8',
	);
	
	protected $root;
	
	public function __construct($config = array()) {
		if (is_array($config) && !empty($config)) {
			$this->config = array_merge($this->config, $config);
		}
		$this->root = $this->config['root'];
	}
	
	public function process(&$request) {
		
		$root = $this->root;
		$dir = str_replace('..', '', urldecode($request['dir']));
		$dir = $this->correctEncoding($dir, $this->config['fs_encoding']);

		if( file_exists($root . $dir) ) {
			$files = scandir($root . $dir);
			natcasesort($files);
			if( count($files) > 2 ) { /* The 2 accounts for . and .. */
				$this->output("<ul class=\"jqueryFileTree\" style=\"display: none;\">");
				// All dirs (first)
				if ($this->config['show_subdirs']) {
					foreach( $files as $file ) {
						if( file_exists($root . $dir . $file) && $file != '.' && $file != '..' && is_dir($root . $dir . $file) ) {
							if (!$this->config['show_hidden'] && $this->isHidden($root . $dir, $file, urldecode($request['userId']))) continue;
							$this->outputDir($dir, $file);
						}
					}
				}
				// All files
				foreach( $files as $file ) {
					if( file_exists($root . $dir . $file) && $file != '.' && $file != '..' && is_file($root . $dir . $file) ) {
						if (!$this->config['show_hidden'] && $this->isHidden($root . $dir, $file, urldecode($request['userId']))) continue;
						if (strpos($file, '.') !== false) {
							$ext = preg_replace('/^.*\./', '', $file);
						} else {
							$ext = '';
						}
						if (is_array($this->config['allow_types']) && count($this->config['allow_types']) > 0 && !in_array(strtolower($ext), $this->config['allow_types'])) continue;
						$this->outputFile($dir, $file, $ext);
					}
				}
				$this->output("</ul>");
			}
		}
		
	}
	
	protected function decodeFilename($name) {
		$name = htmlentities($name, ENT_COMPAT, $this->config['fs_encoding']);
		return $name;
	}
	
	protected function outputDir($dir, $file) {
		$o = "<li class=\"directory collapsed\"><a href=\"#\" rel=\"" . $this->decodeFilename($dir . $file) . "/\">" . $this->decodeFilename($file) . "</a></li>";
		$this->output($o);;
	}

	protected function outputFile($dir, $file, $ext = '') {
		$o = "<li class=\"file ext_$ext\"><a href=\"#\" rel=\"" . $this->decodeFilename($dir . $file) . "\">" . $this->decodeFilename($file) . "</a></li>";
		$this->output($o);;
	}
	
	protected function output($str) {
		echo $str;
	}
	
	protected function isHidden($path, $name, $userId) {
		$extractedId = explode("_", $name)[2][0];
		if ($extractedId != $userId) return true;
		if ($name && $name[0] == '.') return true;
		return false;
	}
	
	protected function correctEncoding($text,$dest_encoding='UTF-8',$current_encoding='') {
		$text .= ' ';
		if ($current_encoding == '') {
			// detect input encoding
			if (function_exists('mb_detect_encoding')) {
				// add here new encodings to check, pay attention to the order!
				$encorder = 'ASCII,UTF-8,ISO-8859-15';
				$current_encoding = mb_detect_encoding($text, $encorder);
			} else {
				// default fallback
				$current_encoding = 'ISO-8859-15';
			}
		}
		// check if we need conversion
		if ($current_encoding != $dest_encoding) {
			// convert to new encoding
			if (function_exists('iconv')) {
				$text = iconv($current_encoding, $dest_encoding.'//IGNORE', $text);
			} elseif ($current_encoding == 'ISO-8859-1' && $dest_encoding == 'UTF-8') {
				$text = utf8_encode($text);
			}
		}
		$text = substr($text,0,-1);
		return $text;
	}
	
}

