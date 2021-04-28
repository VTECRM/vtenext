<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class Squirrelmail {

	private $message;
	private $crmid;
	private $folder;
	private $view_unsafe_images;
	private $content_ids = array();
	
	function __construct($message, $view_unsafe_images=false) {
		$this->message = $message;
		$this->crmid = $message->id;
		$this->view_unsafe_images = $view_unsafe_images;
	}
	
	function getPref($data_dir, $username, $string, $default='') {
		$filename = "$data_dir/$username.pref";
		if(!$file = @fopen($filename, 'r')) {
			return false;
		}
		/* Read in the preferences. */
		while (!feof($file)) {
			$pref = '';
			/* keep reading a pref until we reach an eol (\n (or \r for macs)) */
			while($read = fgets($file, 1024))
			{
				$pref .= $read;
				if(strpos($read,"\n") || strpos($read,"\r"))
				break;
			}
			$pref = trim($pref);
			$equalsAt = strpos($pref, '=');
			if ($equalsAt > 0) {
				$key = substr($pref, 0, $equalsAt);
				$value = substr($pref, $equalsAt + 1);
				if ($value != '') {
					$prefs_cache[$key] = $value;
				}
			}
		}
		fclose($file);
		if (isset($prefs_cache[$string])) {
			$result = $prefs_cache[$string];
		} elseif (!empty($default)) {
			$result = $default;
		} else {
			$result = false;
		}
		return $result;
	}
	
	function decrypt( $txt ) {
		$txt = mf_keyED( hex2bin( $txt ) );
		$tmp = '';
		for ( $i=0; $i < strlen( $txt ); $i++ ) {
			$md5 = substr( $txt, $i, 1 );
			$i++;
			$tmp.= ( substr( $txt, $i, 1 ) ^ $md5 );
		}
		return $tmp;
	}
	
	function setContentId($contentid) {
		if (!in_array($contentid,$this->content_ids)) {
			$this->content_ids[] = $contentid;
		}			
	}
	
	function getContentIds() {
		return $this->content_ids;
	}
	
	/**
	 * This is a wrapper function to call html sanitizing routines.
	 *
	 * @param  $body  the body of the message
	 * @param  $id    the id of the message
	 * @return        a string with html safe to display in the browser.
	 */
	function magicHTML($body, $id, $message, $mailbox='INBOX') {
	
		$this->folder = $mailbox;
		
	//    require_once(SM_PATH . 'functions/url_parser.php');  // for $MailTo_PReg_Match
	
	    global $attachment_common_show_images, $has_unsafe_images;
	    /**
	     * Don't display attached images in HTML mode.
	     */
	    $attachment_common_show_images = false;
	    $tag_list = Array(
	            false,
	            "object",
	            "meta",
	            "html",
	            "head",
	            "base",
	            "link",
	            "frame",
	            "iframe",
	            "plaintext",
	            "marquee"
	            );
	
	    $rm_tags_with_content = Array(
	            "style",
	            "script",
	            "applet",
	            "embed",
	            "title",
	            "frameset",
	            "xmp",
	            "xml"
	            );
	
	    $self_closing_tags =  Array(
	            "img",
	            "br",
	            "hr",
	            "input",
	            "outbind"
	            );
	
	    $force_tag_closing = true;
	
	    $rm_attnames = Array(
	            "/.*/" =>
	            Array(
	                "/target/i",
	                "/^on.*/i",
	                "/^dynsrc/i",
	                "/^data.*/i",
	                "/^lowsrc.*/i",
            		"/^class.*/i"	//crmv@151901
	                )
	            );
	
	    $secremoveimg = 'modules/Messages/src/img/sec_remove.png';
	    $bad_attvals = Array(
	            "/.*/" =>
	            Array(
	                "/^src|background/i" =>
	                Array(
	                    Array(
	                        "/^([\'\"])\s*\S+script\s*:.*([\'\"])/si",
	                        "/^([\'\"])\s*mocha\s*:*.*([\'\"])/si",
	                        "/^([\'\"])\s*about\s*:.*([\'\"])/si"
	                        ),
	                    Array(
	                        "\\1$secremoveimg\\2",
	                        "\\1$secremoveimg\\2",
	                        "\\1$secremoveimg\\2"
	                        )
	                    ),
	                "/^href|action/i" =>
	                Array(
	                    Array(
	                        "/^([\'\"])\s*\S+script\s*:.*([\'\"])/si",
	                        "/^([\'\"])\s*mocha\s*:*.*([\'\"])/si",
	                        "/^([\'\"])\s*about\s*:.*([\'\"])/si"
	                        ),
	                    Array(
	                        "\\1#\\1",
	                        "\\1#\\1",
	                        "\\1#\\1"
	                        )
	                    ),
	        "/^style/i" =>
	            Array(
	                Array(
	                    "/\/\*.*\*\//",
	                    "/expression/i",
	                    "/binding/i",
	                    "/behaviou*r/i",
	                    "/include-source/i",
	
	                    // position:relative can also be exploited
	                    // to put content outside of email body area
	                    // and position:fixed is similarly exploitable
	                    // as position:absolute, so we'll remove it
	                    // altogether....
	                    //
	                    // Does this screw up legitimate HTML messages?
	                    // If so, the only fix I see is to allow position
	                    // attributes (any values?  I think we still have
	                    // to block static and fixed) only if $use_iframe
	                    // is enabled (1.5.0+)
	                    //
	                    // was:   "/position\s*:\s*absolute/i",
	                    //
	                    "/position\s*:/i",
	
	                    "/(\\\\)?u(\\\\)?r(\\\\)?l(\\\\)?/i",
	                    "/url\s*\(\s*([\'\"])\s*\S+script\s*:.*([\'\"])\s*\)/si",
	                    "/url\s*\(\s*([\'\"])\s*mocha\s*:.*([\'\"])\s*\)/si",
	                    "/url\s*\(\s*([\'\"])\s*about\s*:.*([\'\"])\s*\)/si",
	                    "/(.*)\s*:\s*url\s*\(\s*([\'\"]*)\s*\S+script\s*:.*([\'\"]*)\s*\)/si",
	                    ),
	                Array(
	                    "",
	                    "idiocy",
	                    "idiocy",
	                    "idiocy",
	                    "idiocy",
	                    "idiocy",
	                    "url",
	                    "url(\\1#\\1)",
	                    "url(\\1#\\1)",
	                    "url(\\1#\\1)",
	                    "\\1:url(\\2#\\3)"
	                    )
	                )
	            )
	        );
	
	    // If there's no "view_unsafe_images" variable in the URL, turn unsafe
	    // images off by default.

		if (!$this->view_unsafe_images){
	        /**
	         * Remove any references to http/https if view_unsafe_images set
	         * to false.
	         */
	        array_push($bad_attvals{'/.*/'}{'/^src|background/i'}[0],
	                '/^([\'\"])\s*https*:.*([\'\"])/si');
	        array_push($bad_attvals{'/.*/'}{'/^src|background/i'}[1],
	                "\\1$secremoveimg\\1");
	        array_push($bad_attvals{'/.*/'}{'/^style/i'}[0],
	                '/url\([\'\"]?https?:[^\)]*[\'\"]?\)/si');
	        array_push($bad_attvals{'/.*/'}{'/^style/i'}[1],
	                "url(\\1$secremoveimg\\1)");
	    }
	
	    $add_attr_to_tag = Array(
			"/^a$/i" => Array('target'=>'"_blank"','title'=>'""')	//crmv@78555
		);
	    $trusted = $this->sq_sanitize($body,
	                           $tag_list,
	                           $rm_tags_with_content,
	                           $self_closing_tags,
	                           $force_tag_closing,
	                           $rm_attnames,
	                           $bad_attvals,
	                           $add_attr_to_tag,
	                           $message,
	                           $id,
	                           $mailbox
	                           );
	                           
		$trusted = preg_replace('@<pre[^>]*?>@i', '<p class="MessagePre">', $trusted);
		$trusted = str_replace('</pre>','</p>',$trusted);
		
	    if (strpos($trusted,$secremoveimg)){
	        $has_unsafe_images = true;
	    }
	
	    return $trusted;
	}
	
	/**
	 * This is the main function and the one you should actually be calling.
	 * There are several variables you should be aware of an which need
	 * special description.
	 *
	 * Since the description is quite lengthy, see it here:
	 * http://linux.duke.edu/projects/mini/htmlfilter/
	 *
	 * @param $body                 the string with HTML you wish to filter
	 * @param $tag_list             see description above
	 * @param $rm_tags_with_content see description above
	 * @param $self_closing_tags    see description above
	 * @param $force_tag_closing    see description above
	 * @param $rm_attnames          see description above
	 * @param $bad_attvals          see description above
	 * @param $add_attr_to_tag      see description above
	 * @param $message              message object
	 * @param $id                   message id
	 * @return                      sanitized html safe to show on your pages.
	 */
	function sq_sanitize($body,
	                     $tag_list,
	                     $rm_tags_with_content,
	                     $self_closing_tags,
	                     $force_tag_closing,
	                     $rm_attnames,
	                     $bad_attvals,
	                     $add_attr_to_tag,
	                     $message,
	                     $id,
	                     $mailbox
	                     ){
	    $me = 'sq_sanitize';
	    $rm_tags = array_shift($tag_list);
	    /**
	     * Normalize rm_tags and rm_tags_with_content.
	     */
	    @array_walk($tag_list, 'self::sq_casenormalize');
	    @array_walk($rm_tags_with_content, 'self::sq_casenormalize');
	    @array_walk($self_closing_tags, 'self::sq_casenormalize');
	    /**
	     * See if tag_list is of tags to remove or tags to allow.
	     * false  means remove these tags
	     * true   means allow these tags
	     */
	    $curpos = 0;
	    $open_tags = Array();
	    $trusted = "\n<!-- begin sanitized html -->\n";
	    $skip_content = false;
	    /**
	     * Take care of netscape's stupid javascript entities like
	     * &{alert('boo')};
	     */
	    $body = preg_replace("/&(\{.*?\};)/si", "&amp;\\1", $body);
	
	    while (($curtag = $this->sq_getnxtag($body, $curpos)) != FALSE){
	        list($tagname, $attary, $tagtype, $lt, $gt) = $curtag;
	        $free_content = substr($body, $curpos, $lt-$curpos);
	        /**
	         * Take care of <style>
	         */
	       	/*
	        if ($tagname == "style" && $tagtype == 1){
	            list($free_content, $curpos) =
	                sq_fixstyle($body, $gt+1, $message, $id, $mailbox);
	            if ($free_content != FALSE){
	                $trusted .= sq_tagprint($tagname, $attary, $tagtype);
	                $trusted .= $free_content;
	                $trusted .= sq_tagprint($tagname, false, 2);
	            }
	            continue;
	        }
	        */
	        if ($skip_content == false){
            	//crmv@56453
				$free_content = preg_replace('!(\s|^)((https?://)+[a-z0-9_./?=&-/%]+)!i', '<a href="$2" target="_blank">$2</a>', $free_content);
				$free_content = preg_replace('!(\s|^)((www\.)+[a-z0-9_./?=&-/%]+)!i', '<a href="http://$2" target="_blank">$2</a>', $free_content);
				//crmv@56453e
				$trusted .= $free_content;
	        }
	        if ($tagname != FALSE){
	            if ($tagtype == 2){
	                if ($skip_content == $tagname){
	                    /**
	                     * Got to the end of tag we needed to remove.
	                     */
	                    $tagname = false;
	                    $skip_content = false;
	                } else {
	                    if ($skip_content == false){
	                        if ($tagname == "body"){
	                            $tagname = "div";
	                        }
	                        if (isset($open_tags{$tagname}) &&
	                                $open_tags{$tagname} > 0){
	                            $open_tags{$tagname}--;
	                        } else {
	                            $tagname = false;
	                        }
	                    }
	                }
	            } else {
	                /**
	                 * $rm_tags_with_content
	                 */
	                if ($skip_content == false){
	                    /**
	                     * See if this is a self-closing type and change
	                     * tagtype appropriately.
	                     */
	                    if ($tagtype == 1
	                            && in_array($tagname, $self_closing_tags)){
	                        $tagtype = 3;
	                    }
	                    /**
	                     * See if we should skip this tag and any content
	                     * inside it.
	                     */
	                    if ($tagtype == 1 &&
	                            in_array($tagname, $rm_tags_with_content)){
	                        $skip_content = $tagname;
	                    } else {
	                        if (($rm_tags == false
	                                    && in_array($tagname, $tag_list)) ||
	                                ($rm_tags == true &&
	                                 !in_array($tagname, $tag_list))){
	                            $tagname = false;
	                        } else {
	                            /**
	                             * Convert body into div.
	                             */
	                            if ($tagname == "body"){
	                                $tagname = "div";
	                                $attary = $this->sq_body2div($attary, $mailbox,
	                                        $message, $id);
	                            }
	                            if ($tagtype == 1){
	                                if (isset($open_tags{$tagname})){
	                                    $open_tags{$tagname}++;
	                                } else {
	                                    $open_tags{$tagname}=1;
	                                }
	                            }
	                            /**
	                             * This is where we run other checks.
	                             */
	                            if (is_array($attary) && sizeof($attary) > 0){
	                                $attary = $this->sq_fixatts($tagname,
	                                                     $attary,
	                                                     $rm_attnames,
	                                                     $bad_attvals,
	                                                     $add_attr_to_tag,
	                                                     $message,
	                                                     $id,
	                                                     $mailbox
	                                                     );
	                            }
	                        }
	                    }
	                }
	            }
	            if ($tagname != false && $skip_content == false){
	                $trusted .= $this->sq_tagprint($tagname, $attary, $tagtype);
	            }
	        }
	        $curpos = $gt+1;
	    }
	    $trusted .= substr($body, $curpos, strlen($body)-$curpos);
	    if ($force_tag_closing == true){
	        foreach ($open_tags as $tagname=>$opentimes){
	            while ($opentimes > 0){
	                $trusted .= '</' . $tagname . '>';
	                $opentimes--;
	            }
	        }
	        $trusted .= "\n";
	    }
	    $trusted .= "<!-- end sanitized html -->\n";
	    return $trusted;
	}
	
	/**
	 * This function looks for the next tag.
	 *
	 * @param  $body   String where to look for the next tag.
	 * @param  $offset Start looking from here.
	 * @return         false if no more tags exist in the body, or
	 *                 an array with the following members:
	 *                 - string with the name of the tag
	 *                 - array with attributes and their values
	 *                 - integer with tag type (1, 2, or 3)
	 *                 - integer where the tag starts (starting "<")
	 *                 - integer where the tag ends (ending ">")
	 *                 first three members will be false, if the tag is invalid.
	 */
	function sq_getnxtag($body, $offset){
	    $me = 'sq_getnxtag';
	    if ($offset > strlen($body)){
	        return false;
	    }
	    $lt = $this->sq_findnxstr($body, $offset, "<");
	    if ($lt == strlen($body)){
	        return false;
	    }
	    /**
	     * We are here:
	     * blah blah <tag attribute="value">
	     * \---------^
	     */
	    $pos = $this->sq_skipspace($body, $lt+1);
	    if ($pos >= strlen($body)){
	        return Array(false, false, false, $lt, strlen($body));
	    }
	    /**
	     * There are 3 kinds of tags:
	     * 1. Opening tag, e.g.:
	     *    <a href="blah">
	     * 2. Closing tag, e.g.:
	     *    </a>
	     * 3. XHTML-style content-less tag, e.g.:
	     *    <img src="blah" />
	     */
	    $tagtype = false;
	    switch (substr($body, $pos, 1)){
	        case '/':
	            $tagtype = 2;
	            $pos++;
	            break;
	        case '!':
	            /**
	             * A comment or an SGML declaration.
	             */
	            if (substr($body, $pos+1, 2) == "--"){
	                $gt = strpos($body, "-->", $pos);
	                if ($gt === false){
	                    $gt = strlen($body);
	                } else {
	                    $gt += 2;
	                }
	                return Array(false, false, false, $lt, $gt);
	            } else {
	                $gt = $this->sq_findnxstr($body, $pos, ">");
	                return Array(false, false, false, $lt, $gt);
	            }
	            break;
	        default:
	            /**
	             * Assume tagtype 1 for now. If it's type 3, we'll switch values
	             * later.
	             */
	            $tagtype = 1;
	            break;
	    }
	
	    $tag_start = $pos;
	    $tagname = '';
	    /**
	     * Look for next [\W-_], which will indicate the end of the tag name.
	     */
	    $regary = $this->sq_findnxreg($body, $pos, "[^\w\-_]");
	    if ($regary == false){
	        return Array(false, false, false, $lt, strlen($body));
	    }
	    list($pos, $tagname, $match) = $regary;
	    $tagname = strtolower($tagname);
	
	    /**
	     * $match can be either of these:
	     * '>'  indicating the end of the tag entirely.
	     * '\s' indicating the end of the tag name.
	     * '/'  indicating that this is type-3 xhtml tag.
	     *
	     * Whatever else we find there indicates an invalid tag.
	     */
	    switch ($match){
	        case '/':
	            /**
	             * This is an xhtml-style tag with a closing / at the
	             * end, like so: <img src="blah" />. Check if it's followed
	             * by the closing bracket. If not, then this tag is invalid
	             */
	            if (substr($body, $pos, 2) == "/>"){
	                $pos++;
	                $tagtype = 3;
	            } else {
	                $gt = $this->sq_findnxstr($body, $pos, ">");
	                $retary = Array(false, false, false, $lt, $gt);
	                return $retary;
	            }
	        case '>':
	            return Array($tagname, false, $tagtype, $lt, $pos);
	            break;
	        default:
	            /**
	             * Check if it's whitespace
	             */
	            if (!preg_match('/\s/', $match)){
	                /**
	                 * This is an invalid tag! Look for the next closing ">".
	                 */
	                $gt = $this->sq_findnxstr($body, $lt, ">");
	                return Array(false, false, false, $lt, $gt);
	            }
	            break;
	    }
	
	    /**
	     * At this point we're here:
	     * <tagname  attribute='blah'>
	     * \-------^
	     *
	     * At this point we loop in order to find all attributes.
	     */
	    $attname = '';
	    $atttype = false;
	    $attary = Array();
	
	    while ($pos <= strlen($body)){
	        $pos = $this->sq_skipspace($body, $pos);
	        if ($pos == strlen($body)){
	            /**
	             * Non-closed tag.
	             */
	            return Array(false, false, false, $lt, $pos);
	        }
	        /**
	         * See if we arrived at a ">" or "/>", which means that we reached
	         * the end of the tag.
	         */
	        $matches = Array();
	        if (preg_match("%^(\s*)(>|/>)%s", substr($body, $pos), $matches)) {
	            /**
	             * Yep. So we did.
	             */
	            $pos += strlen($matches{1});
	            if ($matches{2} == "/>"){
	                $tagtype = 3;
	                $pos++;
	            }
	            return Array($tagname, $attary, $tagtype, $lt, $pos);
	        }
	
	        /**
	         * There are several types of attributes, with optional
	         * [:space:] between members.
	         * Type 1:
	         *   attrname[:space:]=[:space:]'CDATA'
	         * Type 2:
	         *   attrname[:space:]=[:space:]"CDATA"
	         * Type 3:
	         *   attr[:space:]=[:space:]CDATA
	         * Type 4:
	         *   attrname
	         *
	         * We leave types 1 and 2 the same, type 3 we check for
	         * '"' and convert to "&quot" if needed, then wrap in
	         * double quotes. Type 4 we convert into:
	         * attrname="yes".
	         */
	        $regary = $this->sq_findnxreg($body, $pos, "[^:\w\-_]");
	        if ($regary == false){
	            /**
	             * Looks like body ended before the end of tag.
	             */
	            return Array(false, false, false, $lt, strlen($body));
	        }
	        list($pos, $attname, $match) = $regary;
	        $attname = strtolower($attname);
	        /**
	         * We arrived at the end of attribute name. Several things possible
	         * here:
	         * '>'  means the end of the tag and this is attribute type 4
	         * '/'  if followed by '>' means the same thing as above
	         * '\s' means a lot of things -- look what it's followed by.
	         *      anything else means the attribute is invalid.
	         */
	        switch($match){
	            case '/':
	                /**
	                 * This is an xhtml-style tag with a closing / at the
	                 * end, like so: <img src="blah" />. Check if it's followed
	                 * by the closing bracket. If not, then this tag is invalid
	                 */
	                if (substr($body, $pos, 2) == "/>"){
	                    $pos++;
	                    $tagtype = 3;
	                } else {
	                    $gt = $this->sq_findnxstr($body, $pos, ">");
	                    $retary = Array(false, false, false, $lt, $gt);
	                    return $retary;
	                }
	            case '>':
	                $attary{$attname} = '"yes"';
	                return Array($tagname, $attary, $tagtype, $lt, $pos);
	                break;
	            default:
	                /**
	                 * Skip whitespace and see what we arrive at.
	                 */
	                $pos = $this->sq_skipspace($body, $pos);
	                $char = substr($body, $pos, 1);
	                /**
	                 * Two things are valid here:
	                 * '=' means this is attribute type 1 2 or 3.
	                 * \w means this was attribute type 4.
	                 * anything else we ignore and re-loop. End of tag and
	                 * invalid stuff will be caught by our checks at the beginning
	                 * of the loop.
	                 */
	                if ($char == "="){
	                    $pos++;
	                    $pos = $this->sq_skipspace($body, $pos);
	                    /**
	                     * Here are 3 possibilities:
	                     * "'"  attribute type 1
	                     * '"'  attribute type 2
	                     * everything else is the content of tag type 3
	                     */
	                    $quot = substr($body, $pos, 1);
	                    if ($quot == "'"){
	                        $regary = $this->sq_findnxreg($body, $pos+1, "\'");
	                        if ($regary == false){
	                            return Array(false, false, false, $lt, strlen($body));
	                        }
	                        list($pos, $attval, $match) = $regary;
	                        $pos++;
	                        $attary{$attname} = "'" . $attval . "'";
	                    } else if ($quot == '"'){
	                        $regary = $this->sq_findnxreg($body, $pos+1, '\"');
	                        if ($regary == false){
	                            return Array(false, false, false, $lt, strlen($body));
	                        }
	                        list($pos, $attval, $match) = $regary;
	                        $pos++;
	                        $attary{$attname} = '"' . $attval . '"';
	                    } else {
	                        /**
	                         * These are hateful. Look for \s, or >.
	                         */
	                        $regary = $this->sq_findnxreg($body, $pos, "[\s>]");
	                        if ($regary == false){
	                            return Array(false, false, false, $lt, strlen($body));
	                        }
	                        list($pos, $attval, $match) = $regary;
	                        /**
	                         * If it's ">" it will be caught at the top.
	                         */
	                        $attval = preg_replace("/\"/s", "&quot;", $attval);
	                        $attary{$attname} = '"' . $attval . '"';
	                    }
	                } else if (preg_match("|[\w/>]|", $char)) {
	                    /**
	                     * That was attribute type 4.
	                     */
	                    $attary{$attname} = '"yes"';
	                } else {
	                    /**
	                     * An illegal character. Find next '>' and return.
	                     */
	                    $gt = $this->sq_findnxstr($body, $pos, ">");
	                    return Array(false, false, false, $lt, $gt);
	                }
	                break;
	        }
	    }
	    /**
	     * The fact that we got here indicates that the tag end was never
	     * found. Return invalid tag indication so it gets stripped.
	     */
	    return Array(false, false, false, $lt, strlen($body));
	}
	
	/**
	 * This function returns the final tag out of the tag name, an array
	 * of attributes, and the type of the tag. This function is called by
	 * sq_sanitize internally.
	 *
	 * @param  $tagname  the name of the tag.
	 * @param  $attary   the array of attributes and their values
	 * @param  $tagtype  The type of the tag (see in comments).
	 * @return           a string with the final tag representation.
	 */
	function sq_tagprint($tagname, $attary, $tagtype){
	    $me = 'sq_tagprint';
	
	    if ($tagtype == 2){
	        $fulltag = '</' . $tagname . '>';
	    } else {
	        $fulltag = '<' . $tagname;
	        if (is_array($attary) && sizeof($attary)){
	            $atts = Array();
	            while (list($attname, $attvalue) = each($attary)){
	                array_push($atts, "$attname=$attvalue");
	            }
	            $fulltag .= ' ' . join(" ", $atts);
	        }
	        if ($tagtype == 3){
	            $fulltag .= ' /';
	        }
	        $fulltag .= '>';
	    }
	    return $fulltag;
	}
	
	/**
	 * A small helper function to use with array_walk. Modifies a by-ref
	 * value and makes it lowercase.
	 *
	 * @param  $val a value passed by-ref.
	 * @return      void since it modifies a by-ref value.
	 */
	function sq_casenormalize(&$val){
	    $val = strtolower($val);
	}
	
	/**
	 * This function skips any whitespace from the current position within
	 * a string and to the next non-whitespace value.
	 *
	 * @param  $body   the string
	 * @param  $offset the offset within the string where we should start
	 *                 looking for the next non-whitespace character.
	 * @return         the location within the $body where the next
	 *                 non-whitespace char is located.
	 */
	function sq_skipspace($body, $offset){
	    $me = 'sq_skipspace';
	    preg_match('/^(\s*)/s', substr($body, $offset), $matches);
	    if (is_array($matches) && isset($matches[1]) && !empty($matches[1])){ // crmv@167234
	        $count = strlen($matches[1]); // crmv@167234
	        $offset += $count;
	    }
	    return $offset;
	}
	
	/**
	 * This function takes a PCRE-style regexp and tries to match it
	 * within the string.
	 *
	 * @param  $body   The string to look for needle in.
	 * @param  $offset Start looking from here.
	 * @param  $reg    A PCRE-style regex to match.
	 * @return         Returns a false if no matches found, or an array
	 *                 with the following members:
	 *                 - integer with the location of the match within $body
	 *                 - string with whatever content between offset and the match
	 *                 - string with whatever it is we matched
	 */
	function sq_findnxreg($body, $offset, $reg){
	    $me = 'sq_findnxreg';
	    $matches = Array();
	    $retarr = Array();
	    preg_match("%^(.*?)($reg)%si", substr($body, $offset), $matches);
	    if (!isset($matches[0]) || !$matches[0]){ // crmv@167234 - replace {} with []
	        $retarr = false;
	    } else {
			// crmv@167234 - replace {} with []
	        $retarr[0] = $offset + strlen($matches[1]);
	        $retarr[1] = $matches[1];
	        $retarr[2] = $matches[2];
			// crmv@167234
	    }
	    return $retarr;
	}
	
	/**
	 * This function looks for the next character within a string.  It's
	 * really just a glorified "strpos", except it catches if failures
	 * nicely.
	 *
	 * @param  $body   The string to look for needle in.
	 * @param  $offset Start looking from this position.
	 * @param  $needle The character/string to look for.
	 * @return         location of the next occurance of the needle, or
	 *                 strlen($body) if needle wasn't found.
	 */
	function sq_findnxstr($body, $offset, $needle){
	    $me  = 'sq_findnxstr';
	    $pos = strpos($body, $needle, $offset);
	    if ($pos === FALSE){
	        $pos = strlen($body);
	    }
	    return $pos;
	}
	
	/**
	 * This function runs various checks against the attributes.
	 *
	 * @param  $tagname         String with the name of the tag.
	 * @param  $attary          Array with all tag attributes.
	 * @param  $rm_attnames     See description for sq_sanitize
	 * @param  $bad_attvals     See description for sq_sanitize
	 * @param  $add_attr_to_tag See description for sq_sanitize
	 * @param  $message         message object
	 * @param  $id              message id
	 * @param  $mailbox         mailbox
	 * @return                  Array with modified attributes.
	 */
	function sq_fixatts($tagname,
	                    $attary,
	                    $rm_attnames,
	                    $bad_attvals,
	                    $add_attr_to_tag,
	                    $message,
	                    $id,
	                    $mailbox
	                    ){
	    $me = 'sq_fixatts';
	    while (list($attname, $attvalue) = each($attary)){
	        /**
	         * See if this attribute should be removed.
	         */
	        foreach ($rm_attnames as $matchtag=>$matchattrs){
	            if (preg_match($matchtag, $tagname)){
	                foreach ($matchattrs as $matchattr){
	                    if (preg_match($matchattr, $attname)){
	                        unset($attary{$attname});
	                        continue;
	                    }
	                }
	            }
	        }
	
	        /**
	         * Workaround for IE quirks
	         */
	        //sq_fixIE_idiocy($attvalue);
	
	        /**
	         * Remove any backslashes, entities, and extraneous whitespace.
	         */
	        $oldattvalue = $attvalue;
	        $this->sq_defang($attvalue);
	        if ($attname == 'style' && $attvalue !== $oldattvalue) {
	            // entities are used in the attribute value. In 99% of the cases it's there as XSS
	            // i.e.<div style="{ left:exp&#x0280;essio&#x0274;( alert('XSS') ) }">
	            $attvalue = "idiocy";
	            $attary{$attname} = $attvalue;
	        }
	        $this->sq_unspace($attvalue);
	
	        /**
	         * Now let's run checks on the attvalues.
	         * I don't expect anyone to comprehend this. If you do,
	         * get in touch with me so I can drive to where you live and
	         * shake your hand personally. :)
	         */
	        foreach ($bad_attvals as $matchtag=>$matchattrs){
	            if (preg_match($matchtag, $tagname)){
	                foreach ($matchattrs as $matchattr=>$valary){
	                    if (preg_match($matchattr, $attname)){
	                        /**
	                         * There are two arrays in valary.
	                         * First is matches.
	                         * Second one is replacements
	                         */
	                        list($valmatch, $valrepl) = $valary;
	                        $newvalue =
	                            preg_replace($valmatch, $valrepl, $attvalue);
	                        if ($newvalue != $attvalue){
	                            $attary{$attname} = $newvalue;
	                            $attvalue = $newvalue;
	                        }
	                    }
	                }
	            }
	        }
	        if ($attname == 'style') {
	            if (preg_match('/[\0-\37\200-\377]+/',$attvalue)) {
	                // 8bit and control characters in style attribute values can be used for XSS, remove them
	                $attary{$attname} = '"disallowed character"';
	            }
	            preg_match_all("/url\s*\((.+)\)/si",$attvalue,$aMatch);
	            if (count($aMatch)) {
	                foreach($aMatch[1] as $sMatch) {
	                    // url value
	                    $urlvalue = $sMatch;
	                    $this->sq_fix_url($attname, $urlvalue, $message, $id, $mailbox,"'");
	                    $attary{$attname} = str_replace($sMatch,$urlvalue,$attvalue);
	                }
	            }
	        }
	        /**
	         * Use white list based filtering on attributes which can contain url's
	         */
	        else if ($attname == 'href' || $attname == 'src' || $attname == 'background') {
	            $this->sq_fix_url($attname, $attvalue, $message, $id, $mailbox);
	            $attary{$attname} = $attvalue;
	        }
	    }
	    /**
	     * See if we need to append any attributes to this tag.
	     */
	    foreach ($add_attr_to_tag as $matchtag=>$addattary){
	        if (preg_match($matchtag, $tagname)){
	            $attary = array_merge($attary, $addattary);
	        }
	    }
	    return $attary;
	}
	
	/**
	 * This function checks attribute values for entity-encoded values
	 * and returns them translated into 8-bit strings so we can run
	 * checks on them.
	 *
	 * @param  $attvalue A string to run entity check against.
	 * @return           Nothing, modifies a reference value.
	 */
	function sq_defang(&$attvalue){
	    $me = 'sq_defang';
	    /**
	     * Skip this if there aren't ampersands or backslashes.
	     */
	    if (strpos($attvalue, '&') === false
	        && strpos($attvalue, '\\') === false){
	        return;
	    }
	    $m = false;
	    do {
	        $m = false;
	        $m = $m || $this->sq_deent($attvalue, '/\&#0*(\d+);*/s');
	        $m = $m || $this->sq_deent($attvalue, '/\&#x0*((\d|[a-f])+);*/si', true);
	        $m = $m || $this->sq_deent($attvalue, '/\\\\(\d+)/s', true);
	    } while ($m == true);
	    $attvalue = stripslashes($attvalue);
	}
	
	/**
	 * Kill any tabs, newlines, or carriage returns. Our friends the
	 * makers of the browser with 95% market value decided that it'd
	 * be funny to make "java[tab]script" be just as good as "javascript".
	 *
	 * @param  attvalue  The attribute value before extraneous spaces removed.
	 * @return attvalue  Nothing, modifies a reference value.
	 */
	function sq_unspace(&$attvalue){
	    $me = 'sq_unspace';
	    if (strcspn($attvalue, "\t\r\n\0 ") != strlen($attvalue)){
	        $attvalue = str_replace(Array("\t", "\r", "\n", "\0", " "),
	                                Array('',   '',   '',   '',   ''), $attvalue);
	    }
	}
	
	/**
	 * This function filters url's
	 *
	 * @param  $attvalue        String with attribute value to filter
	 * @param  $message         message object
	 * @param  $id               message id
	 * @param  $mailbox         mailbox
	 * @param  $sQuote          quoting characters around url's
	 */
	function sq_fix_url($attname, &$attvalue, $message, $id, $mailbox,$sQuote = '"') {
	    $attvalue = trim($attvalue);
	    if ($attvalue && ($attvalue[0] =='"'|| $attvalue[0] == "'")) {
	        // remove the double quotes
	        $sQuote = $attvalue[0];
	        $attvalue = trim(substr($attvalue,1,-1));
	    }
	
	    // If there's no "view_unsafe_images" variable in the URL, turn unsafe
	    // images off by default.
	    $secremoveimg = 'modules/Messages/src/img/sec_remove.png';
	
	    /**
	     * Replace empty src tags with the blank image.  src is only used
	     * for frames, images, and image inputs.  Doing a replace should
	     * not affect them working as should be, however it will stop
	     * IE from being kicked off when src for img tags are not set
	     */
	    if ($attvalue == '') {
	        $attvalue = 'modules/Messages/src/img/blank.png';
	    } else {
	        // first, disallow 8 bit characters and control characters
	        if (preg_match('/[\0-\37\200-\377]+/',$attvalue)) {
	            switch ($attname) {
	                case 'href':
	                    $attvalue = $sQuote . 'http://invalid-stuff-detected.example.com' . $sQuote;
	                    break;
	                default:
	                    $attvalue = $sQuote . 'modules/Messages/src/img/blank.png'. $sQuote;
	                    break;
	            }
	        } else {
				$aUrl = parse_url($attvalue);
				if (isset($aUrl['host']) && $aUrl['host'] === 'cid') { // crmv@205899
					$attvalue = $sQuote . $this->sq_cid2http($message, $id, $attvalue, $mailbox) . $sQuote;
				} else {
					if (isset($aUrl['scheme'])) {
						switch(strtolower($aUrl['scheme'])) {
							case 'mailto':
							// crmv@81136
							case 'tel':
							case 'sms':
							// crmv@81136e
							case 'http':
							case 'https':
							case 'ftp':
								if ($attname != 'href') {
									if ($this->view_unsafe_images == false) {
										$attvalue = $sQuote . $secremoveimg . $sQuote;
									} else {
										if (isset($aUrl['path'])) {
		
											// No one has been able to show that image URIs
											// can be exploited, so for now, no restrictions
											// are made at all.  If this proves to be a problem,
											// the commented-out code below can be of help.
											// (One consideration is that I see nothing in this
											// function that specifically says that we will
											// only ever arrive here when inspecting an image
											// tag, although that does seem to be the end
											// result - e.g., <script src="..."> where malicious
											// image URIs are in fact a problem are already
											// filtered out elsewhere.
											/* ---------------------------------
											// validate image extension.
											$ext = strtolower(substr($aUrl['path'],strrpos($aUrl['path'],'.')));
											if (!in_array($ext,array('.jpeg','.jpg','xjpeg','.gif','.bmp','.jpe','.png','.xbm'))) {
												// If URI is to something other than
												// a regular image file, get the contents
												// and try to see if it is an image.
												// Don't use Fileinfo (finfo_file()) because
												// we'd need to make the admin configure the
												// location of the magic.mime file (FIXME: add finfo_file() support later?)
												//
												$mime_type = '';
												if (function_exists('mime_content_type')
												&& ($FILE = @fopen($attvalue, 'rb', FALSE))) {
		
													// fetch file
													//
													$file_contents = '';
													while (!feof($FILE)) {
														$file_contents .= fread($FILE, 8192);
													}
													fclose($FILE);
		
													// store file locally
													//
													global $attachment_dir, $username;
													$hashed_attachment_dir = getHashedDir($username, $attachment_dir);
													$localfilename = GenerateRandomString(32, '', 7);
													$full_localfilename = "$hashed_attachment_dir/$localfilename";
													while (file_exists($full_localfilename)) {
														$localfilename = GenerateRandomString(32, '', 7);
														$full_localfilename = "$hashed_attachment_dir/$localfilename";
													}
													$FILE = fopen("$hashed_attachment_dir/$localfilename", 'wb');
													fwrite($FILE, $file_contents);
													fclose($FILE);
		
													// get mime type and remove file
													//
													$mime_type = mime_content_type("$hashed_attachment_dir/$localfilename");
													unlink("$hashed_attachment_dir/$localfilename");
												}
												// debug: echo "$attvalue FILE TYPE IS $mime_type<HR>";
												if (substr(strtolower($mime_type), 0, 5) != 'image') {
													$attvalue = $sQuote . SM_PATH . 'images/blank.png'. $sQuote;
												}
											}
											--------------------------------- */
										} else {
											$attvalue = $sQuote . 'modules/Messages/src/img/blank.png'. $sQuote;
										}
									}
								} else {
									$attvalue = $sQuote . $attvalue . $sQuote;
								}
								break;
							case 'outbind':
								//"Hack" fix for Outlook using propriatary outbind:// protocol in img tags.
								//One day MS might actually make it match something useful, for now, falling back to using cid2http, so we can grab the blank.png.
								$attvalue = $sQuote . $this->sq_cid2http($message, $id, $attvalue, $mailbox) . $sQuote;
								break;
							case 'cid':
								//Turn cid: urls into http-friendly ones.
								$attvalue = $sQuote . $this->sq_cid2http($message, $id, $attvalue, $mailbox) . $sQuote;
								break;
							//crmv@93993
							case 'data':
								$attvalue = $sQuote . $attvalue . $sQuote;
								break;
							//crmv@93993e
							default:
								$attvalue = $sQuote . 'modules/Messages/src/img/blank.png' . $sQuote;
								break;
						}
					} else {
						if (!(isset($aUrl['path']) && $aUrl['path'] == $secremoveimg)) {
							// parse_url did not lead to satisfying result
							$attvalue = $sQuote . 'modules/Messages/src/img/blank.png' . $sQuote;
						}
					}
				}
			}
	    }
	}
	
	/**
	 * This function changes the <body> tag into a <div> tag since we
	 * can't really have a body-within-body.
	 *
	 * @param  $attary   an array of attributes and values of <body>
	 * @param  $mailbox  mailbox we're currently reading (for cid2http)
	 * @param  $message  current message (for cid2http)
	 * @param  $id       current message id (for cid2http)
	 * @return           a modified array of attributes to be set for <div>
	 */
	function sq_body2div($attary, $mailbox, $message, $id){
	    $me = 'sq_body2div';
	    $divattary = Array('class' => "'bodyclass'");
	    $bgcolor = '#ffffff';
	    $text = '#000000';
	    $styledef = '';
	    if (is_array($attary) && sizeof($attary) > 0){
	        foreach ($attary as $attname=>$attvalue){
	            $quotchar = substr($attvalue, 0, 1);
	            $attvalue = str_replace($quotchar, "", $attvalue);
	            switch ($attname){
	                case 'background':
	                    $attvalue = $this->sq_cid2http($message, $id, $attvalue, $mailbox);
	                    $styledef .= "background-image: url('$attvalue'); ";
	                    break;
	                case 'bgcolor':
	                    $styledef .= "background-color: $attvalue; ";
	                    break;
	                case 'text':
	                    $styledef .= "color: $attvalue; ";
	                    break;
	            }
	        }
	        if (strlen($styledef) > 0){
	            $divattary{"style"} = "\"$styledef\"";
	        }
	    }
	    return $divattary;
	}
	
	/**
	 * Translates entities into literal values so they can be checked.
	 *
	 * @param $attvalue the by-ref value to check.
	 * @param $regex    the regular expression to check against.
	 * @param $hex      whether the entites are hexadecimal.
	 * @return          True or False depending on whether there were matches.
	 */
	function sq_deent(&$attvalue, $regex, $hex=false){
	    $me = 'sq_deent';
	    $ret_match = false;
	    preg_match_all($regex, $attvalue, $matches);
	    if (is_array($matches) && sizeof($matches[0]) > 0){
	        $repl = Array();
	        for ($i = 0; $i < sizeof($matches[0]); $i++){
	            $numval = $matches[1][$i];
	            if ($hex){
	                $numval = hexdec($numval);
	            }
	            $repl{$matches[0][$i]} = chr($numval);
	        }
	        $attvalue = strtr($attvalue, $repl);
	        return true;
	    } else {
	        return false;
	    }
	}
	
	/**
	 * This function converts cid: url's into the ones that can be viewed in
	 * the browser.
	 *
	 * @param  $message  the message object
	 * @param  $id       the message id
	 * @param  $cidurl   the cid: url.
	 * @param  $mailbox  the message mailbox
	 * @return           a string with a http-friendly url
	 */
	function sq_cid2http($message, $id, $cidurl, $mailbox){
	    /**
	     * Get rid of quotes.
	     */
	    $quotchar = substr($cidurl, 0, 1);
	    if ($quotchar == '"' || $quotchar == "'"){
	        $cidurl = str_replace($quotchar, "", $cidurl);
	    } else {
	        $quotchar = '';
	    }
	    $cidurl = substr(trim($cidurl), 4);

	    $match_str = '/\{.*?\}\//';
	    $str_rep = '';
	    $cidurl = preg_replace($match_str, $str_rep, $cidurl);

	    $linkurl = $this->find_ent_id($cidurl, $message);
	    /* in case of non-safe cid links $httpurl should be replaced by a sort of
	       unsafe link image */
	    $httpurl = '';
	
	    /**
	     * This is part of a fix for Outlook Express 6.x generating
	     * cid URLs without creating content-id headers. These images are
	     * not part of the multipart/related html mail. The html contains
	     * <img src="cid:{some_id}/image_filename.ext"> references to
	     * attached images with as goal to render them inline although
	     * the attachment disposition property is not inline.
	     */
	
	    if ($linkurl === '') {
	        if (preg_match('/{.*}\//', $cidurl)) {
	            $cidurl = preg_replace('/{.*}\//','', $cidurl);
	            if (!empty($cidurl)) {
	                $linkurl = $this->find_ent_id($cidurl, $message);
				}
	        }
	    }
	    
	    if ($linkurl !== '') {
	    	if (!empty($this->crmid)) {
	    		$record = $this->crmid;
	    	}
			$httpurl = $quotchar . "index.php?module=Messages&action=MessagesAjax&file=Download&record={$record}&contentid={$linkurl}&mode=inline" . $quotchar;	//crmv@80250
			$this->setContentId($linkurl);
	    } else {
	        /**
	         * If we couldn't generate a proper img url, drop in a blank image
	         * instead of sending back empty, otherwise it causes unusual behaviour
	         */
	        $httpurl = $quotchar . 'modules/Messages/src/img/blank.png' . $quotchar;
	    }
	    
	    return $httpurl;
	}
	
	/* This function trys to locate the entity_id of a specific mime element */
	function find_ent_id($id, $message) {
		foreach ($message['other'] as $contentid => $attach) {
			if (!empty($attach['parameters']['content_id']) && strcasecmp($attach['parameters']['content_id'], $id) == 0) {
				return $contentid;
			} elseif (!empty($attach['parameters']['name']) && strcasecmp($attach['parameters']['name'], $id) == 0) {
				return $contentid;
            }
		}
		return '';
	}

	/**
	 * Decodes headers
	 *
	 * This functions decode strings that is encoded according to
	 * RFC1522 (MIME Part Two: Message Header Extensions for Non-ASCII Text).
	 * Patched by Christian Schmidt <christian@ostenfeld.dk>  23/03/2002
	 */
	function decodeHeader($string, $utfencode=true, $htmlsave=true) {
	    global $languages, $default_charset;
	    if (is_array($string)) {
	        $string = implode("\n", $string);
	    }
		
	    $i = 0;
	    $iLastMatch = -2;
	    $encoded = false;
	
	    $aString = explode(' ',$string);
	    $ret = '';
	    foreach ($aString as $chunk) {
	        if ($encoded && $chunk === '') {
	            continue;
	        } elseif ($chunk === '') {
	            $ret .= ' ';
	            continue;
	        }
	        $encoded = false;
	        /* if encoded words are not separated by a linear-space-white we still catch them */
	        $j = $i-1;
	
	        while ($match = preg_match('/^(.*)=\?([^?]*)\?(Q|B)\?([^?]*)\?=(.*)$/Ui',$chunk,$res)) {
	            /* if the last chunk isn't an encoded string then put back the space, otherwise don't */
	            if ($iLastMatch !== $j) {
	                if ($htmlsave) {
	                    $ret .= '&#32;';
	                } else {
	                    $ret .= ' ';
	                }
	            }
	            $iLastMatch = $i;
	            $j = $i;
	            if ($htmlsave) {
	                $ret .= htmlspecialchars($res[1]);
	            } else {
	                $ret .= $res[1];
	            }
	            $encoding = ucfirst($res[3]);
				/*
	        	switch ($encoding)
	            {
	                case 'B':
	                    $replace = base64_decode($res[4]);
						if ($utfencode) {
	                        // string is converted to htmlentities and sanitized
	                        //crmv@27759
	                        if ($res[2] == 'utf-8' || $res[2] == 'UTF-8') {
	                        	$replace = correctEncoding($replace,'ISO-8859-1','UTF-8');
	                        } else {
	                        	$replace = charset_decode($res[2],$replace).' 2';
	                        }
	                        //crmv@27759e
	                    } elseif ($htmlsave) {
	                        // string is not converted, but still sanitized
	                        $replace = htmlspecialchars($replace);
	                    }
	                    $ret.= $replace;
	                    break;
	                case 'Q':
	                    $replace = str_replace('_', ' ', $res[4]);
	                    $replace = preg_replace('/=([0-9a-f]{2})/ie', 'chr(hexdec("\1"))',$replace);
						if ($utfencode) {
	                        // string is converted to html entities and sanitized
							//crmv@27759
	                        if ($res[2] == 'utf-8' || $res[2] == 'UTF-8') {
	                        	$replace = correctEncoding($replace,'ISO-8859-1','UTF-8');
	                        } else {
	                        	$replace = charset_decode($res[2],$replace);
	                        }
	                        //crmv@27759e
	                    } elseif ($htmlsave) {
	                        // string is not converted, but still sanizited
	                        $replace = htmlspecialchars($replace);
	                    }
	                    $ret .= $replace;
	                    break;
	                default:
	                    break;
	            }
				*/
	            switch ($encoding) {
	            	 case 'B':
	            	 	$encoding = 'base64';
	            	 	break;
	            	 case 'Q':
	            	 	$res[4] = str_replace('_', ' ', $res[4]);
	            	 	$encoding = 'quoted-printable';
	            	 	break;
	            	 default:
	                    break;
	            }
				$replace = $this->message->decodePart($res[4],$encoding,$res[2]);
				$ret .= $replace;
	            $chunk = $res[5];
	            $encoded = true;
	        }
	        if (!$encoded) {
	            if ($htmlsave) {
	                $ret .= '&#32;';
	            } else {
	                $ret .= ' ';
	            }
	        }
	        if (!$encoded && $htmlsave) {
	            $ret .= htmlspecialchars($chunk);
	        } else {
	            $ret .= $chunk;
	        }
	        ++$i;
	    }
	    /* remove the first added space */
	    if ($ret) {
	        if ($htmlsave) {
	            $ret = substr($ret,5);
	        } else {
	            $ret = substr($ret,1);
	        }
	    }
	    return $ret;
	}
}
?>