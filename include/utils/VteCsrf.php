<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@171581 */

class VteCsrf {

	public $config  = array(

		/**
		* By default, when you include this file csrf-magic will automatically check
		* and exit if the CSRF token is invalid. This will defer executing
		* csrf_check() until you're ready.  You can also pass false as a parameter to
		* that function, in which case the function will not exit but instead return
		* a boolean false if the CSRF check failed. This allows for tighter integration
		* with your system.
		*/
		'defer' => true,
		
		/**
		* This is the amount of seconds you wish to allow before any token becomes
		* invalid; the default is two hours, which should be more than enough for
		* most websites.
		*/
		'expires' => 0, // disable time-based expiration, expire with session
		
		/**
		* Callback function to execute when there's the CSRF check fails and
		* $fatal == true (see csrf_check). This will usually output an error message
		* about the failure.
		*/
		'callback' => 'csrf_callback',
		
		/**
		* Whether or not to include our JavaScript library which also rewrites
		* AJAX requests on this domain. Set this to the web path. This setting only works
		* with supported JavaScript libraries in Internet Explorer; see README.txt for
		* a list of supported libraries.
		*/
		//option not working  because we no support buffering and we use custom js file
		'rewrite-js' => false,
		
		/**
		* A secret key used when hashing items. Please generate a random string and
		* place it here. If you change this value, all previously generated tokens
		* will become invalid.
		*/
		'secret' => '',
		
		// nota bene: library code should use csrf_get_secret() and not access
		// this global directly
		/**
		* Set this to false to disable csrf-magic's output handler, and therefore,
		* its rewriting capabilities. If you're serving non HTML content, you should
		* definitely set this false.
		*/
		//option not working  because we no support buffering and we use custom js file
		'rewrite' => false,
		
		/**
		* Whether or not to use IP addresses when binding a user to a token. This is
		* less reliable and less secure than sessions, but is useful when you need
		* to give facilities to anonymous users and do not wish to maintain a database
		* of valid keys.
		*/
		'allow-ip' => false,
		
		/**
		* If this information is available, use the cookie by this name to determine
		* whether or not to allow the request. This is a shortcut implementation
		* very similar to 'key', but we randomly set the cookie ourselves.
		*/
		'cookie' => false,
		
		/**
		* If this information is available, set this to a unique identifier (it
		* can be an integer or a unique username) for the current "user" of this
		* application. The token will then be globally valid for all of that user's
		* operations, but no one else. This requires that 'secret' be set.
		*/
		'user' => false,
		
		/**
		* This is an arbitrary secret value associated with the user's session. This
		* will most probably be the contents of a cookie, as an attacker cannot easily
		* determine this information. Warning: If the attacker knows this value, they
		* can easily spoof a token. This is a generic implementation; sessions should
		* work in most cases.
		*
		* Why would you want to use this? Lets suppose you have a squid cache for your
		* website, and the presence of a session cookie bypasses it. Let's also say
		* you allow anonymous users to interact with the website; submitting forms
		* and AJAX. Previously, you didn't have any CSRF protection for anonymous users
		* and so they never got sessions; you don't want to start using sessions either,
		* otherwise you'll bypass the Squid cache. Setup a different cookie for CSRF
		* tokens, and have Squid ignore that cookie for get requests, for anonymous
		* users. (If you haven't guessed, this scheme was(?) used for MediaWiki).
		*/
		'key' => true,
		
		/**
		* The name of the magic CSRF token that will be placed in all forms, i.e.
		* the contents of <input type="hidden" name="$name" value="CSRF-TOKEN" />
		*/
		'input-name' => '__csrf_token',
		
		/**
		* Set this to false if your site must work inside of frame/iframe elements,
		* but do so at your own risk: this configuration protects you against CSS
		* overlay attacks that defeat tokens.
		*/
		'frame-breaker' => true,
		
		/**
		* Whether or not CSRF Magic should be allowed to start a new session in order
		* to determine the key.
		*/
		'auto-session' => false,
		
		/**
		 * Whether or not csrf-magic should produce XHTML style tags.
		 */
		'xhtml' => true,
		
		// Don't edit this!
		'version' => '1.0.4'
	);
	
	public function __construct()
	{                
		if (!$this->config['defer'])       $this->csrf_check();    
	}
	
	/**
	* Checks if this is a post request, and if it is, checks if the nonce is valid.
	* @param bool $fatal Whether or not to fatally error out if there is a problem.
	* @return True if check passes or is not necessary, false if failure.
	*/
	public function csrf_check($fatal = true) {
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') return true;
		
		//  csrf_start();
		$name = $this->config['input-name'];
		$ok = false;
		$tokens = '';
		do {
			if (!isset($_POST[$name])) break;
			// we don't regenerate a token and check it because some token creation
			// schemes are volatile.
			$tokens = $_POST[$name];                       
			if (!$this->csrf_check_tokens($tokens)) break;
			$ok = true;
		} while (false);
		if ($fatal && !$ok) {
			if (trim($tokens, 'A..Za..z0..9:;,') !== '') $tokens = 'hidden';
			$callback = $this->csrf_callback($tokens);
			exit;
		}
		return $ok;
	}

/**
* Retrieves a valid token(s) for a particular context. Tokens are separated
* by semicolons.
*/
	public function csrf_get_tokens() {
		$has_cookies = !empty($_COOKIE);
		// $ip implements a composite key, which is sent if the user hasn't sent
		// any cookies. It may or may not be used, depending on whether or not
		// the cookies "stick"
		$secret = $this->csrf_get_secret();
		if (!$has_cookies && $secret) {
			// :TODO: Harden this against proxy-spoofing attacks
			$ip = ';ip:' . $this->csrf_hash($_SERVER['IP_ADDRESS']);
		} else {
			$ip = '';
		}
		// csrf_start();
		// These are "strong" algorithms that don't require per se a secret
		if (VteSession::getId()) return 'sid:' . $this->csrf_hash(VteSession::getId()) . $ip;
		if ($this->config['cookie']) {
			$val = $this->csrf_generate_secret();
			setcookie($this->config['cookie'], $val);
			return 'cookie:' . $this->csrf_hash($val) . $ip;
		}
		if ($this->config['key']) return 'key:' . $this->csrf_hash($this->config['key']) . $ip;
		// These further algorithms require a server-side secret
		if (!$secret) return 'invalid';
		if ($this->config['user'] !== false) {
			return 'user:' . $this->csrf_hash($this->config['user']);
		}
		if ($this->config['allow-ip']) {
			return ltrim($ip, ';');
		}
		return 'invalid';
	}

	public function csrf_flattenpost($data) {
		$ret = array();
		foreach($data as $n => $v) {
			$ret = array_merge($ret, $this->csrf_flattenpost2(1, $n, $v));
		}
		return $ret;
	}
	
	public function csrf_flattenpost2($level, $key, $data) {
		if(!is_array($data)) return array($key => $data);
		$ret = array();
		foreach($data as $n => $v) {
			$nk = $level >= 1 ? $key."[$n]" : "[$n]";
			$ret = array_merge($ret, $this->csrf_flattenpost2($level+1, $nk, $v));
		}
		return $ret;
	}

	/**
	* @param $tokens is safe for HTML consumption
	*/
	public function csrf_callback($tokens) {
	// (yes, $tokens is safe to echo without escaping)
	header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
	$data = '';
	foreach ($this->csrf_flattenpost($_POST) as $key => $value) {
		if ($key == $this->config['input-name']) continue;
			$data .= '<input type="hidden" name="'.htmlspecialchars($key).'" value="'.htmlspecialchars($value).'" />';
		}
		echo "<html><head><title>CSRF check failed</title></head>
				<body>
					<p>CSRF check failed. Your form session may have expired, or you may not have
								cookies enabled.</p>
								<form method='post' action=''>$data<input type='submit' value='Try again' /></form>
								<p>Debug: $tokens</p></body></html>";
	}
	
	/**
	 * Checks if a composite token is valid. Outward facing code should use this
	 * instead of csrf_check_token()
	 */
	public function csrf_check_tokens($tokens) {
		if (is_string($tokens)) $tokens = explode(';', $tokens);
		foreach ($tokens as $token) {
			if ($this->csrf_check_token($token)) return true;
		}
		return false;
	}
	
	/**
	 * Checks if a token is valid.
	 */
	public function csrf_check_token($token) {
		if (strpos($token, ':') === false) return false;
		list($type, $value) = explode(':', $token, 2);
		if (strpos($value, ',') === false) return false;
		list($x, $time) = explode(',', $token, 2);
		if ($this->config['expires']) {
			if (time() > $time + $this->config['expires']) return false;
		}
		switch ($type) {
			case 'sid':
				return $value === $this->csrf_hash(VteSession::getId(), $time);
			case 'cookie':
				$n = $this->config['cookie'];
				if (!$n) return false;
				if (!isset($_COOKIE[$n])) return false;
				return $value === $this->csrf_hash($_COOKIE[$n], $time);
			case 'key':
				if (!$this->config['key']) return false;
				return $value === $this->csrf_hash($this->config['key'], $time);
			// We could disable these 'weaker' checks if 'key' was set, but
			// that doesn't make me feel good then about the cookie-based
			// implementation.
			case 'user':
				if (!$this->csrf_get_secret()) return false;
				if ($this->config['user'] === false) return false;
				return $value === $this->csrf_hash($this->config['user'], $time);
			case 'ip':
				if (!$this->csrf_get_secret()) return false;
				// do not allow IP-based checks if the username is set, or if
				// the browser sent cookies
				if ($this->config['user'] !== false) return false;
				if (!empty($_COOKIE)) return false;
				if (!$this->config['allow-ip']) return false;
				return $value === $this->csrf_hash($_SERVER['IP_ADDRESS'], $time);
		}
	return false;
	}

	/**
	* Sets a configuration value.
	*/
	public function csrf_conf($key, $val) {
		if (!isset($this->config[$key])) {
			trigger_error('No such configuration ' . $key, E_USER_WARNING);
			return;
		}
		$this->config[$key] = $val;
	}
	
	/**
	* Starts a session if we're allowed to.
	*/
	function csrf_start() {
		if ($GLOBALS['auto-session'] && !VteSession::getId()) {
			VteSession::reopen();
		}
	}
	   
	/**
	 * Retrieves the secret
	 */
	public function csrf_get_secret() {
		if ($this->config['secret']) return $this->config['secret'];
		
		global $csrf_secret;
		if (!isset($csrf_secret)) {
			require('config.inc.php');
		}
		return $csrf_secret;
	}
	
	/**
	 * Generates a random string as the hash of time, microtime, and mt_rand.
	 */
	public function csrf_generate_secret($len = 32) {
		$r = '';
		for ($i = 0; $i < $len; $i++) {
			$r .= chr(mt_rand(0, 255));
		}
		$r .= time() . microtime();
		return sha1($r);
	}
	
	/**
	 * Generates a hash/expiry double. If time isn't set it will be calculated
	 * from the current time.
	 */
	public function csrf_hash($value, $time = null) {
		if (!$time) $time = time();
		return sha1($this->csrf_get_secret() . $value . $time) . ',' . $time;
	}

}
