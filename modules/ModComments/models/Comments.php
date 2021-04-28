<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@59626 */

require_once('modules/ModComments/models/Replies.php');

class ModComments_CommentsModel {
	
	protected $data;
	public $replies;
	public $repliesIds;
	protected $searchkey; //crmv@31301
	static $ownerNamesCache = array();
	static $ownerPhotoCache = array();
	public $previewCommentLength = 150;
	public $previewReplyLength = 450;
	
	function __construct($datarow, $searchkey='', $replyRows='') { //crmv@31301
		$this->data = $datarow;
		$this->searchkey = $searchkey;	//crmv@31301
		$replies = new ModComments_RepliesModel($this->id(), $searchkey, $replyRows); //crmv@31301
		$this->replies = $replies->getReplies();
		$this->repliesIds = $replies->getRepliesIds();
	}
	
	function hasUnreadReplies($unseen_ids) {
		if (!empty($this->repliesIds)) {
			$intersect = array_intersect($this->repliesIds, $unseen_ids);
			if (!empty($intersect)) return true;
		}
		return false;
	}
	
	function author() {
		$authorid = $this->data['smcreatorid'];
		if(!isset(self::$ownerNamesCache[$authorid])) {
			self::$ownerNamesCache[$authorid] = getUserFullName($authorid);
		}
		//crmv@31301
		if($this->searchkey && stristr(self::$ownerNamesCache[$authorid], $this->searchkey))
			return	'<mark>'.self::$ownerNamesCache[$authorid].'</mark>';
		else
		//crmv@31301e
			return self::$ownerNamesCache[$authorid];
	}
	
	// crmv@31780
	function recipients($nohtml = false) {
		$return = '';
		if ($this->data['visibility_comm'] == 'Users') {
			global $adb,$table_prefix;
			$result = $adb->pquery('SELECT * FROM '.$table_prefix.'_modcomments_users WHERE id = ?',array($this->id()));
			if ($result && $adb->num_rows($result) > 0) {
				$recipients = array();
				while($row=$adb->fetchByAssoc($result)) {
					if(!isset(self::$ownerNamesCache[$row['user']])) {
						self::$ownerNamesCache[$row['user']] = getUserFullName($row['user']);
					}
					//crmv@31301
					if($this->searchkey && stristr(self::$ownerNamesCache[$row['user']], $this->searchkey))
						$recipients[$row['user']] = '<mark>'.self::$ownerNamesCache[$row['user']].'</mark>';
					else
					//crmv@31301e
						$recipients[$row['user']] = self::$ownerNamesCache[$row['user']];
				}
				if (!empty($recipients)) {
					if (!$nohtml) $return .= ' > ';
					$info = array();
					foreach($recipients as $id => $name) {
						if(!isset(self::$ownerPhotoCache[$id])) {
							self::$ownerPhotoCache[$id] = getUserAvatar($id);
						}
						$info[] = array('value'=>$id,'name'=>$name,'img'=>self::$ownerPhotoCache[$id]);
					}
					if ($nohtml) {
						$return = $info;
					} else {
						$info = rawurlencode(Zend_Json::encode($info));
						if (count($recipients) > 1) {
							$return .= '<b><a href="javascript:;" style="text-decoration:none;" onmouseover="displayRecipientsInfo(this,\''.$info.'\');" title="'.implode(', ',$recipients).'">'.count($recipients).' '.getTranslatedString('LBL_USERS','Users').'</a></b>';
						} else {
							$return .= '<b><a href="javascript:;" style="text-decoration:none;" onmouseover="displayRecipientsInfo(this,\''.$info.'\');">'.implode('',$recipients).'</a></b>';
						}
					}
				}
			}
		}
		return $return;
	}
	// crmv@31780e
	
	function authorPhoto() {
		global $theme;
		$authorid = $this->data['smcreatorid'];
		if(!isset(self::$ownerPhotoCache[$authorid])) {
			self::$ownerPhotoCache[$authorid] = getUserAvatar($authorid);
		}
		return self::$ownerPhotoCache[$authorid];
	}
	
	function timestamp(){
		return CRMVUtils::timestamp($this->data['createdtime']); // crmv@164654
	}
	
	function timestampAgo(){
		return CRMVUtils::timestampAgo($this->data['createdtime']); // crmv@164654
	}
	
	function previewContent($content='') {
		
		global $current_user;
		if ($current_user->id == 20526) {
			
			if (empty($content)) $content = $this->content();
		
			$content = strip_tags($content);
			$string = substr($content,$content->previewCommentLength-5,10);
			echo "---$string---";
	//		$content = substr($content,0,$content->previewCommentLength);

		}
	}
	
	function content() {
		//crmv@31301	crmv@34684
		global $adb, $default_charset;

		$comments = decode_html($this->data['commentcontent']);
		// crmv@80705 - fix for xss
		if ($_REQUEST['action'] != 'DetailView' || $_REQUEST['module'] == 'Messages') {
			$comments = str_replace(array('<', '>'), array('&lt;', '&gt;'), $comments);
		}
		// crmv@80705e

		preg_match_all("/([\w]+?:\/\/.*?[^ \"\n\r\t<]*)/",$comments,$links1);
		preg_match_all("/((www|ftp)\.[\w\-]+\.[\w\-.\~]+(?:\/[^ \"\t\n\r<]*)?)/",$comments,$links2);
		$links = array_merge($links1,$links2);
		if (is_array($links)) {
			$links = $adb->flatten_array(array_filter($links));
			if (is_array($links)) {
				$links = array_filter($links,function($var) {
					if ($var == "" || $var == "www") return false; else return true;
				});
				if (is_array($links)) {
					$links = array_unique($links);
				}
			}
		}
		
		$comments = preg_replace("/(^|[\n ])([\w]+?:\/\/.*?[^ \"\n\r\t<]*)/","\\1<a href=\"\\2\" target=\"_blank\">\\2</a>",$comments);
		$comments = preg_replace("/(^|[\n ])((www|ftp)\.[\w\-]+\.[\w\-.\~]+(?:\/[^ \"\t\n\r<]*)?)/","\\1<a href=\"http://\\2\" target=\"_blank\">\\2</a>",$comments);
		$comments = preg_replace("/(^|[\n ])([a-z0-9&\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)*[\w]+)/i","\\1<a href=\"javascript:parent.jQuery('#ModCommentsNews').hide();parent.InternalMailer('\\2@\\3','','','','email_addy');\">\\2@\\3</a>",$comments);
		$comments = preg_replace("/,\"|\.\"|\)\"|\)\.\"|\.\)\"/","\"",$comments);
		$comments = nl2br($comments);
		
		if (!empty($this->searchkey)) $comments = str_ireplace($this->searchkey, '<mark>'.$this->searchkey.'</mark>', $comments);

		if (!empty($links)) {
			// clean links
			foreach ($links as $url) {
				$dirty_url = str_ireplace($this->searchkey, '<mark>'.$this->searchkey.'</mark>', $url);
				$comments = str_ireplace($dirty_url, $url, $comments);
			}
			// replace marks
			foreach ($links as $url) {
				if (strlen($url) > 60) {
					$first_part = str_ireplace($this->searchkey, '<mark>'.$this->searchkey.'</mark>', substr($url,0,45));
					$last_part = str_ireplace($this->searchkey, '<mark>'.$this->searchkey.'</mark>', substr($url,-12));
					$link = $first_part.'...'.$last_part;
				} else {
					$link = str_ireplace($this->searchkey, '<mark>'.$this->searchkey.'</mark>', $url);
				}
				$comments = str_replace(">$url<",'>'.$link.'<',$comments);
			}
		}
		
		return $comments;
		//crmv@31301e	crmv@34684e
	}

	// crmv@31780 - restituisce un array, no html
	function content_no_html() {

		// tolgo chiavi numeriche
		$cleandata = array();
		if (is_array($this->data) && count($this->data) > 0) {
			foreach ($this->data as $k=>$v) {
				if (!is_numeric($k)) $cleandata[$k] = $v;
			}
		}

		// author and recipients
		$cleandata['author'] = $this->author();
		$cleandata['authorPhoto'] = $this->authorPhoto();
		$cleandata['recipients'] = $this->recipients(true);

		// timestamp
		$cleandata['timestamp'] = $this->timestamp();
		$cleandata['timestampago'] = $this->timestampAgo();

		$cleandata['unseen'] = $this->isUnseen();
		$cleandata['forced'] = $this->isForced();

		// crmv@33311
		$relatedid = $this->relatedTo();
		if ($relatedid > 0) {
			$cleandata['related_to_module'] = getSalesEntityType($relatedid);
			$ename = getEntityName($cleandata['related_to_module'], array($relatedid));
			$cleandata['related_to_name'] = $ename[$relatedid];
		}
		// crmv@33311e

		// prendo le risposte
		if (is_array($this->replies) && count($this->replies) > 0) {
			$cleandata['replies'] = array();
			foreach ($this->replies as $rep) {
				$cleandata['replies'][] = $rep->content_no_html();
			}
		}
		return $cleandata;
	}
	// crmv@31780e
	
	function id() {
		return $this->data['crmid'];
	}

	// crmv@80503
	function lastchildid() {
		return $this->data['lastchildid'];
	}
	// crmv@80503e
	
	function relatedTo() {
		return $this->data['related_to'];
	}
	
	function relatedToString() {
		$parent_id = $this->relatedTo();
		if (!in_array($parent_id,array('',0))) {
			$parent_module = getSalesEntityType($parent_id);			
			$entityType = getSingleModuleName($parent_module,$parent_id);
			$displayValueArray = getEntityName($parent_module,$parent_id);
			$displayValue = $displayValueArray[$parent_id];
			if (empty($displayValue)) {
				$displayValue = $entityType;
			}
			//return ' '.getTranslatedString('LBL_ABOUT','ModComments')." <a href='index.php?module=$parent_module&action=DetailView&record=$parent_id' title='$entityType' target='_parent'>$displayValue</a> ($entityType)";
			return array(
				'url'=>"index.php?module=$parent_module&action=DetailView&record=$parent_id",
				'displayValue'=>$displayValue,
				'entityType'=>$entityType,
				//crmv@179773
				'module'=>$parent_module,
				'crmid'=>$parent_id,
				//crmv@179773e
			);
		}
	}
	
	function isUnseen() {
		global $adb, $current_user;
		$result = $adb->pquery('SELECT * FROM vte_notifications where id = ? and userid = ? and type = ?',array($this->id(),$current_user->id,'ModComments'));
		if ($result && $adb->num_rows($result) > 0) {
			return true;
		}
		return false;
	}

	function isForced() {
		global $adb, $current_user;
		$result = $adb->pquery('SELECT forced FROM vte_notifications where id = ? and userid = ? and type = ? and forced = ?',array($this->id(),$current_user->id,'ModComments', 1));
		return ($result && $adb->num_rows($result) > 0);
	}
	
	function authorId() {
		return $this->data['smcreatorid'];
	}
	
	function getCurrentUser() {
		global $current_user;
		return $current_user->id;
	}

	//crmv@43050

	// returns a list of all recipients of the message, including author and replies
	// useful only in case of users talk
	function getAllRecipients() {
		$rec = array($this->authorId());

		$replies = $this->recipients(true);
		if (is_array($replies)) {
			foreach ($replies as $r) $rec[] = $r['value'];
		}

		return array_unique($rec);
	}

	function isPublic() {
		return ($this->data['visibility_comm'] == 'All');
	}
	//crmv@43050e

	// crmv@101967
	public function canDeletePost() {
		return (ModComments::isDeletionEnabled() && ($this->getCurrentUser() == $this->authorId()));
	}
	// crmv@101967e

}