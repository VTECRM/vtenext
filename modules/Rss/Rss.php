<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $calpath;
global $app_strings,$mod_strings;
global $theme;
global $table_prefix;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

// Require the xmlParser class
//require_once('include/feedParser/xmlParser.php');

// Require the feedParser class
//require_once('include/feedParser/feedParser.php');

require_once('include/magpierss/rss_fetch.inc');

class VteRSS extends CRMEntity
{
	var $rsscache_time = 1200;

	/** Function to get the Rss Feeds from the Given URL
	  * This Function accepts the url string as the argument
	  * and assign the value for the class variables correspondingly
	 */
	function setRSSUrl($url)
	{
		global $cache_dir;
		$this->rss = fetch_rss($url);
		// Read in our sample feed file
		//$data = @implode("",@file($url));
		// Tell feedParser to parse the data
		$info = $this->rss->items;

		if(isset($info))
		{
			$this->rss_object = $info;
		}else
		{
			return false;
		}
		if(isset($this->rss))
		{
			$this->rss_title = $this->rss->channel["title"];
			$this->rss_link = $this->rss->channel["link"];
			$this->rss_object = $info;
			return true;
		}else
		{
			return false;
		}

	}

	/** Function to get the List of Rss feeds
	  * This Function accepts no arguments and returns the listview contents on Sucess
	  * returns "Sorry: It's not possible to reach RSS URL" if fails
	 */

	function getListViewRSSHtml()
	{
		global $default_charset;
		if(isset($this->rss_object))
		{
			$i = 0;
			foreach($this->rss_object as $key=>$item)
			{
				$stringConvert = function_exists('iconv') ? @iconv("UTF-8",$default_charset,$item['title']) : $item['title']; // crmv@167702
				$rss_title= ltrim(rtrim($stringConvert));

				$i = $i + 1;
				$shtml .= "<tr class='prvPrfHoverOff' onmouseover=\"this.className='prvPrfHoverOn'\" onmouseout=\"this.className='prvPrfHoverOff'\"><td><a href=\"javascript:VTE.Rss.display('".$item['link']."','feedlist_".$i."')\"; id='feedlist_".$i."' class=\"rssNews\">".$rss_title."</a></td><td>".$this->rss_title."</td></tr>";
				if($i == 10)
				{
					return $shtml;
				}
			}

			return $shtml;

		}else
		{
			$shtml = "<strong>".$mod_strings['LBL_REGRET_MSG']."</strong>";
		}
	}

	/** Function to get the List of Rss feeds in the Customized Home page
	  * This Function accepts maximum entries as arguments and returns the listview contents on Success
	  * returns "Sorry: It's not possible to reach RSS URL" if fails
	 */
	function getListViewHomeRSSHtml($maxentries)
	{
		$return_value=Array();
		$return_more=Array();
		if(isset($this->rss_object))
		{
			$y = 0;

			foreach($this->rss_object as $key=>$item)
			{
				$title =$item['title']; // crmv@167702
				$link = $item['link']; // crmv@167702

				if($y == $maxentries)
				{
					$return_more=Array("Details"=>$return_value,"More"=>$this->rss_link);
					return $return_more;
				}
				$y = $y + 1;
		     		$return_value[]=Array($title,$link);
			}
			$return_more=Array("Details"=>$return_value,"More"=>$this->rss_link);
			return $return_more;

		}else
		{
			return $return_more;
		}
	}

	/** Function to save the Rss Feeds
	  * This Function accepts the RssURl,Starred Status as arguments and
	  * returns true on sucess
	  * returns false if fails
	 */
	function saveRSSUrl($url,$makestarred=0)
	{
		global $adb,$table_prefix;

		if ($url != "")
		{
			$rsstitle = $this->rss_title;
			if($rsstitle == "")
			{
				$rsstitle = $url;
			}
			$genRssId = $adb->getUniqueID($table_prefix."_rss");
			$sSQL = "insert into {$table_prefix}_rss (RSSID,RSSURL,RSSTITLE,RSSTYPE,STARRED) values (?,?,?,?,?)"; // crmv@37463
			$sparams = array($genRssId, $url, $rsstitle, 0, $makestarred);
			$result = $adb->pquery($sSQL, $sparams);
			if($result)
			{
				return $genRssId;
			}else
			{
				return false;
			}
		}
	}
	function getCRMRssFeeds()
	{
		global $adb,$table_prefix;
		global $image_path,$theme;

		$sSQL = "select * from ".$table_prefix."_rss where rsstype=1";
		$result = $adb->pquery($sSQL, array());
		while($allrssrow = $adb->fetch_array($result))
		{
			$shtml .= "<tr>";
			if($allrssrow["starred"] == 1)
			{
				$shtml .= "<td width=\"15\">
					<i class=\"vteicon md-sm nohover\" id=\"star-{$allrssrow['rssid']}\">star</i></td>";
			}else
			{
				$shtml .= "<td width=\"15\">
					<i class=\"vteicon md-sm md-link\" id=\"star-{$allrssrow['rssid']}\" onClick=\"VTE.Rss.makedefaultRss({$allrssrow['rssid']})\">star_border</i></td>";
			}
			$shtml .= "<td class=\"rssTitle\"><a href=\"index.php?module=Rss&action=ListView&record={$allrssrow['rssid']}
				\" class=\"rssTitle\">".$allrssrow['rsstitle']."</a></td>";
			$shtml .= "<td>&nbsp;</td></tr>";

		}
		return $shtml;

	}

	function getAllRssFeeds()
	{
		global $adb,$table_prefix;
		global $image_path;

		$sSQL = "select * from ".$table_prefix."_rss where rsstype <> 1";
		$result = $adb->pquery($sSQL, array());
		while($allrssrow = $adb->fetch_array($result))
		{
			$shtml .= "<tr>";
			if($allrssrow["starred"] == 1)
			{
				$shtml .= "<td width=\"15\">
					<i class=\"vteicon md-sm nohover\" id=\"star-{$allrssrow['rssid']}\">star</i></td>";
			}else
			{
				$shtml .= "<td width=\"15\">
					<i class=\"vteicon md-sm md-link\" id=\"star-{$allrssrow['rssid']}\" onClick=\"VTE.Rss.makedefaultRss({$allrssrow['rssid']})\">star_border</i></td>";
			}
			$shtml .= "<td class=\"rssTitle\"><a href=\"index.php?module=Rss&action=ListView&record={$allrssrow['rssid']}\" class=\"rssTitle\">{$allrssrow['rsstitle']}</a></td><td>&nbsp;</td>";
			$shtml .= "</tr>";

		}

		return $shtml;
	}

	/** Function to get the rssurl for the given id
	  * This Function accepts the rssid as argument and returns the rssurl for that id
	 */
	function getRssUrlfromId($rssid)
	{
		global $adb,$table_prefix;

		if($rssid != "")
		{
			$sSQL = "select * from ".$table_prefix."_rss where rssid=?";
			$result = $adb->pquery($sSQL, array($rssid));
			$rssrow = $adb->fetch_array($result);

			if(count($rssrow) > 0)
			{
				$rssurl = $rssrow['rssurl']; // crmv@167702
			}
		}
		return $rssurl;
	}

	function getRSSHeadings($rssid)
	{
		global $image_path;
		global $adb,$table_prefix;

		if($rssid != "")
		{
			$sSQL = "select * from ".$table_prefix."_rss where rssid=?";
			$result = $adb->pquery($sSQL, array($rssid));
			$rssrow = $adb->fetch_array($result);

			if(count($rssrow) > 0)
			{
				$shtml = "<table width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"4\">
					<tr>
					<td class=\"rssPgTitle\">";
				if($rssrow['starred'] == 1) // crmv@167702
				{
					$shtml .= "<i class=\"vteicon md-sm nohover\">star</i>";
				}else
				{
					$shtml .= "<i class=\"vteicon md-sm nohover\">star_border</i>";
				}
				// crmv@167702
				$shtml .= "<a href=\"".$this->rss_object['link']."\">  ".$rssrow['rsstitle']."</a>
					</td>
					</tr>
					</table>";

			}
		}
		return $shtml;
	}

	/** Function to get the StarredRSSFeeds lists
	  * This Function accepts no argument and returns the rss feeds of
	  * the starred Feeds as HTML strings
	 */
	function getTopStarredRSSFeeds()
	{
		global $adb,$table_prefix;
		global $image_path;

		$sSQL = "select * from ".$table_prefix."_rss where starred=1";
		$result = $adb->pquery($sSQL, array());
		$shtml = "<i class=\"vteicon md-sm nohover\">rss_feed</i> <a href=\"#\" onclick='window.open(\"index.php?module=Rss&action=Popup\",\"new\",\"width=500,height=300,resizable=1,scrollbars=1\");'>Add New Rss</a>";

		while($allrssrow = $adb->fetch_array($result))
		{
			$shtml .= "<i class=\"vteicon md-sm nohover\">rss_feed</i> ";
			$shtml .= "<a href=\"index.php?module=Rss&action=ListView&record={$allrssrow['rssid']}\" class=\"rssFavLink\">				 ".substr($allrssrow['rsstitle'],0,10)."...</a></img>";
		}
		return $shtml;
	}

	/** Function to get the rssfeed lists for the starred Rss feeds
	  * This Function accepts no argument and returns the rss feeds of
	  * the starred Feeds as HTML strings
	 */
	function getStarredRssHTML()
	{
		global $adb,$table_prefix;
		global $image_path,$mod_strings;

		$sreturnhtml = array();
		$sSQL = "select * from ".$table_prefix."_rss where starred=1";
		$result = $adb->pquery($sSQL, array());
		while($allrssrow = $adb->fetch_array($result))
		{
			if($this->setRSSUrl($allrssrow["rssurl"]))
			{
				$rss_html = $this->getListViewRSSHtml();
			}
			$shtml .= $rss_html;
			if(isset($this->rss_object))
			{
				if(count($this->rss_object) > 10)
				{
					$shtml .= "<tr><td colspan='3' align=\"right\">
						<a target=\"_BLANK\" href=\"$this->rss_link\">".$mod_strings['LBL_MORE']."</a>
						</td></tr>";
				}
			}
			$sreturnhtml[] = $shtml;
			$shtml = "";
		}

		$recordcount = round((count($sreturnhtml))/2);
		$j = $recordcount;
		for($i=0;$i<$recordcount;$i++)
		{
			$starredhtml .= $sreturnhtml[$i].$sreturnhtml[$j];
			$j = $j + 1;
		}
		$starredhtml  = "<table class='rssTable' cellspacing='0' cellpadding='0'>
						<tr>
       	                <th width='75%'>".$mod_strings['LBL_SUBJECT']."</th>
           	            <th width='25%'>".$mod_strings['LBL_SENDER']."</th>
                   	    </tr>".$starredhtml."</table>";

		return $starredhtml;

	}

	/** Function to get the rssfeed lists for the given rssid
	  * This Function accepts the rssid as argument and returns the rss feeds as HTML strings
	 */
	function getSelectedRssHTML($rssid)
	{
		global $adb,$table_prefix;
		global $image_path, $mod_strings;

		$sSQL = "select * from ".$table_prefix."_rss where rssid=?";
		$result = $adb->pquery($sSQL, array($rssid));
		while($allrssrow = $adb->fetch_array($result))
		{
			if($this->setRSSUrl($allrssrow["rssurl"]))
			{
				$rss_html = $this->getListViewRSSHtml();
			}
			$shtml .= $rss_html;
			if(isset($this->rss_object))
			{
				if(count($this->rss_object) > 10)
				{
					$shtml .= "<tr><td colspan='3' align=\"right\">
							<a target=\"_BLANK\" href=\"$this->rss_link\">".$mod_strings['LBL_MORE']."</a>
							</td></tr>";
				}
			}
			$sreturnhtml[] = $shtml;
			$shtml = "";
		}

		$recordcount = round((count($sreturnhtml))/2);
		$j = $recordcount;
		for($i=0;$i<$recordcount;$i++)
		{
			$starredhtml .= $sreturnhtml[$i].$sreturnhtml[$j];
			$j = $j + 1;
		}
		$starredhtml  = "<table class='rssTable' cellspacing='0' cellpadding='0'>
						<tr>
       	                <th width='75%'>".$mod_strings['LBL_SUBJECT']."</th>
           	            <th width='25%'>".$mod_strings['LBL_SENDER']."</th>
                   	    </tr>".$starredhtml."</table>";

		return $starredhtml;

	}

	/** Function to get the Rss Feeds by Category
	  * This Function accepts the RssCategory as argument
	  * and returns the html string for the Rss feeds lists
	 */
	function getRSSCategoryHTML()
	{
		global $adb;
		global $image_path;
			$shtml .= "<tr>
						<td colspan=\"3\">
						<table width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\" style=\"margin:5 0 0 35\">".$this->getRssFeedsbyCategory()."</table>
						</td>
					  </tr>";

		return $shtml;
	}

	/** Function to get the Rss Feeds for the Given Category
	  * This Function accepts the RssCategory as argument
	  * and returns the html string for the Rss feeds lists
	 */
	function getRssFeedsbyCategory()
	{

		global $adb,$table_prefix;
		global $image_path,$theme;

		$sSQL = "select * from ".$table_prefix."_rss";
		$result = $adb->pquery($sSQL, array());
		while($allrssrow = $adb->fetch_array($result))
		{
			$shtml .= "<tr id='feed_".$allrssrow['rssid']."'>"; // crmv@167702
			$shtml .= "<td align='left' width=\"15\">";
			if($allrssrow["starred"] == 1)
			{
				$shtml .= "<i class=\"vteicon md-sm nohover\" id=\"star-{$allrssrow['rssid']}\">star</i>"; // crmv@167702

			}else
			{
				$shtml .= "<i class=\"vteicon md-sm md-link\" id=\"star-{$allrssrow['rssid']}\" onClick=\"VTE.Rss.makedefaultRss({$allrssrow['rssid']})\">star_border</i>"; // crmv@167702
			}
			$shtml .= "</td>";
			$shtml .= "<td class=\"rssTitle\" width=\"10%\" nowrap><a href=\"javascript:VTE.Rss.GetRssFeedList('{$allrssrow['rssid']}')\" class=\"rssTitle\">".$allrssrow['rsstitle']."</a></td><td>&nbsp;</td>"; // crmv@167702
			$shtml .= "</tr>";
		}
		return $shtml;

	}

	// Function to delete an entity with given Id
	function trash($module, $id) {
		global $log, $adb,$table_prefix;

		$del_query = 'DELETE FROM '.$table_prefix.'_rss WHERE rssid=?';
		$adb->pquery($del_query, array($id));
	}

}

/** Function to get the rsstitle for the given rssid
 * This Function accepts the rssid as an optional argument and returns the title
 * if no id is passed it will return the tittle of the starred rss
 */
function gerRssTitle($id='')
{
	global $adb,$table_prefix;
	if($id == '') {
		$query = 'select * from '.$table_prefix.'_rss where starred=1';
		$params = array();
	} else {
		$query = 'select * from '.$table_prefix.'_rss where rssid =?';
		$params = array($id);
	}
	$result = $adb->pquery($query, $params);
	$title = $adb->query_result($result,0,'rsstitle');
	return $title;

}
