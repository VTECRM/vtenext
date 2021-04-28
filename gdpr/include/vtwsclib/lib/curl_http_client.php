<?php
/**
 * @version 1.2
 * @package dinke.net
 * @copyright &copy; 2008 Dinke.net
 * @author Dragan Dinic <dragan@dinke.net>
 */

/* crmv@80883 */
 
/**
 * Curl based HTTP Client 
 * Simple but effective OOP wrapper around Curl php lib.
 * Contains common methods needed 
 * for getting data from url, setting referrer, credentials, 
 * sending post data, managing cookies, etc.
 * 
 * Samle usage:
 * $curl = &new Curl_HTTP_Client();
 * $useragent = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)";
 * $curl->set_user_agent($useragent);
 * $curl->store_cookies("/tmp/cookies.txt");
 * $post_data = array('login' => 'pera', 'password' => 'joe');
 * $html_data = $curl->send_post_data(http://www.foo.com/login.php, $post_data);
 */
class Curl_HTTP_Client
{
	/**
	 * Curl handler
	 * @access protected
	 * @var resource
	 */
	protected $ch ;

	/**
	 * set debug to true in order to get usefull output
	 * @access public
	 * @var string
	 */
	public $debug = false;

	/**
	 * Contain last error message if error occured
	 * @access public
	 * @var string
	 */
	public $error_msg;


	/**
	 * Curl_HTTP_Client constructor
	 * @param boolean debug
	 * @access public
	 */
	public function __construct($debug = false)
	{
		$this->debug = $debug;
		$this->init();
	}
	
	/**
	 * Init Curl session	 
	 * @access public
	 */
	public function init()
	{
		// initialize curl handle
		$this->ch = curl_init();

		//set various options

		//set error in case http return code bigger than 300
		curl_setopt($this->ch, CURLOPT_FAILONERROR, true);

		// allow redirects
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
		
		// use gzip if possible
		curl_setopt($this->ch,CURLOPT_ENCODING , 'gzip, deflate');

		// do not veryfy ssl
		// this is important for windows
		// as well for being able to access pages with non valid cert
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);	
	}

	/**
	 * Set username/pass for basic http auth
	 * @param string user
	 * @param string pass
	 * @access public
	 */
	function set_credentials($username,$password)
	{
		curl_setopt($this->ch, CURLOPT_USERPWD, "$username:$password");
	}

	/**
	 * Set referrer
	 * @param string referrer url 
	 * @access public
	 */
	function set_referrer($referrer_url)
	{
		curl_setopt($this->ch, CURLOPT_REFERER, $referrer_url);
	}

	/**
	 * Set client's useragent
	 * @param string user agent
	 * @access public
	 */
	function set_user_agent($useragent)
	{
		curl_setopt($this->ch, CURLOPT_USERAGENT, $useragent);
	}

	/**
	 * Set to receive output headers in all output functions
	 * @param boolean true to include all response headers with output, false otherwise
	 * @access public
	 */
	function include_response_headers($value)
	{
		curl_setopt($this->ch, CURLOPT_HEADER, $value);
	}


	/**
	 * Set proxy to use for each curl request
	 * @param string proxy
	 * @access public
	 */
	function set_proxy($proxy)
	{
		curl_setopt($this->ch, CURLOPT_PROXY, $proxy);
	}



	/**
	 * Send post data to target URL	 
	 * return data returned from url or false if error occured
	 * @param string url
	 * @param mixed post data (assoc array ie. $foo['post_var_name'] = $value or as string like var=val1&var2=val2)
	 * @param string ip address to bind (default null)
	 * @param int timeout in sec for complete curl operation (default 10)
	 * @return string data
	 * @access public
	 */
	function send_post_data($url, $postdata, $ip=null, $timeout=10)
	{
		//set various curl options first

		// set url to post to
		curl_setopt($this->ch, CURLOPT_URL,$url);

		// return into a variable rather than displaying it
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER,true);

		//bind to specific ip address if it is sent trough arguments
		if($ip)
		{
			if($this->debug)
			{
				echo "Binding to ip $ip\n";
			}
			curl_setopt($this->ch,CURLOPT_INTERFACE,$ip);
		}

		//set curl function timeout to $timeout
		curl_setopt($this->ch, CURLOPT_TIMEOUT, $timeout);

		//set method to post
		curl_setopt($this->ch, CURLOPT_POST, true);
		
		// crmv@163342
		$remoteAddr = null;
		if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
			$remoteAddr = $_SERVER["HTTP_CLIENT_IP"];
		} elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
			$remoteAddr = $_SERVER["HTTP_X_FORWARDED_FOR"];
		} else {
			$remoteAddr = $_SERVER["REMOTE_ADDR"];
		}
		
		// disable Expect header
		// hack to make it working
		$headers = array("Expect: ", "CLIENT-IP: {$remoteAddr}", "X-FORWARDED-FOR: {$remoteAddr}");
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
		// crmv@163342e

		if($this->debug) {
			echo "Url: $url\nPost Data: ".print_r($postdata, true)."\n";
		}
		
		// check for file uploads
		if (is_array($postdata)) {
			$fileUploads = array();
			foreach ($postdata as $key=>$val) {
				if (substr($val, 0, 1) == '@') {
					$fileUploads[$key] = $val;
				}
			}
			if (count($fileUploads) > 0) {
				// ok, we have uploads, prefer curlfile class, otherwise leave it as it is
				foreach ($fileUploads as $key=>$filename) {
					$basename = basename($filename);
					$mime = null; // autodetect
					if (strpos(PHP_OS, "WIN") !== false) $filename = str_replace("/", "\\", $filename); // win hack
					if (class_exists('CURLFile')) {
						$postdata[$key] = new CURLFile($filename, $mime, $basename);
					} else {
						if ($mime && strpos($filename, ';') === false) {
							$filename .= ';'.$mime;
						}
						$postdata[$key] = $filename;
					}
				}
			}
		}

		// set post string or array
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $postdata);

		//and finally send curl request
		$result = curl_exec($this->ch);
		if(curl_errno($this->ch))
		{
			if($this->debug)
			{
				echo "Error Occured in Curl\n";
				echo "Error number: " .curl_errno($this->ch) ."\n";
				echo "Error message: " .curl_error($this->ch)."\n";
			}

			return false;
		}
		else
		{
			return $result;
		}
	}

	/**
	 * fetch data from target URL	 
	 * return data returned from url or false if error occured
	 * @param string url	 
	 * @param string ip address to bind (default null)
	 * @param int timeout in sec for complete curl operation (default 5)
	 * @return string data
	 * @access public
	 */
	function fetch_url($url, $ip=null, $timeout=5)
	{
		// set url to post to
		curl_setopt($this->ch, CURLOPT_URL,$url);

		//set method to get
		curl_setopt($this->ch, CURLOPT_HTTPGET,true);

		// return into a variable rather than displaying it
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER,true);

		//bind to specific ip address if it is sent trough arguments
		if($ip)
		{
			if($this->debug)
			{
				echo "Binding to ip $ip\n";
			}
			curl_setopt($this->ch,CURLOPT_INTERFACE,$ip);
		}
		
		// crmv@163342
		$remoteAddr = null;
		if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
			$remoteAddr = $_SERVER["HTTP_CLIENT_IP"];
		} elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
			$remoteAddr = $_SERVER["HTTP_X_FORWARDED_FOR"];
		} else {
			$remoteAddr = $_SERVER["REMOTE_ADDR"];
		}
		
		$headers = array("CLIENT-IP: {$remoteAddr}", "X-FORWARDED-FOR: {$remoteAddr}");
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
		// crmv@163342e

		//set curl function timeout to $timeout
		curl_setopt($this->ch, CURLOPT_TIMEOUT, $timeout);

		//and finally send curl request
		$result = curl_exec($this->ch);
		if(curl_errno($this->ch))
		{
			if($this->debug)
			{
				echo "Error Occured in Curl\n";
				echo "Error number: " .curl_errno($this->ch) ."\n";
				echo "Error message: " .curl_error($this->ch)."\n";
			}

			return false;
		}
		else
		{
			return $result;
		}
	}

	/**
	 * Set file location where cookie data will be stored and send on each new request
	 * @param string absolute path to cookie file (must be in writable dir)
	 * @access public
	 */
	function store_cookies($cookie_file)
	{
		// use cookies on each request (cookies stored in $cookie_file)
		curl_setopt ($this->ch, CURLOPT_COOKIEJAR, $cookie_file);
		curl_setopt ($this->ch, CURLOPT_COOKIEFILE, $cookie_file);
	}
	
	/**
	 * Set custom cookie
	 * @param string cookie
	 * @access public
	 */
	function set_cookie($cookie)
	{		
		curl_setopt ($this->ch, CURLOPT_COOKIE, $cookie);
	}

	/**
	 * Get last URL info 
	 * usefull when original url was redirected to other location	
	 * @access public
	 * @return string url
	 */
	function get_effective_url()
	{
		return curl_getinfo($this->ch, CURLINFO_EFFECTIVE_URL);
	}

	/**
	 * Get http response code	 
	 * @access public
	 * @return int
	 */
	function get_http_response_code()
	{
		return curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
	}

	/**
	 * Return last error message and error number
	 * @return string error msg
	 * @access public
	 */
	function get_error_msg()
	{
		$err = "Error number: " .curl_errno($this->ch) ."\n";
		$err .="Error message: " .curl_error($this->ch)."\n";

		return $err;
	}
	
	/**
	 * Close curl session and free resource
	 * Usually no need to call this function directly
	 * in case you do you have to call init() to recreate curl
	 * @access public
	 */
	function close()
	{
		//close curl session and free up resources
		curl_close($this->ch);
	}
}
