<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

include_once('config.php');
require_once('include/logging.php');
require_once('include/utils/utils.php');

	global $empty_string;
// Faq is used to store vte_faq information.
class Faq extends CRMEntity {
	var $log;
	var $db;
	var $table_name;
	var $table_index= 'id';
	var $tab_name = Array();
	var $tab_name_index = Array();
	var $customFieldTable = Array(); // crmv@81217

	var $entity_table;

	var $column_fields = Array();

	var $sortby_fields = Array('question','category','id');

	// This is the list of vte_fields that are in the lists.
	var $list_fields = Array(
				'FAQ Id'=>Array('faq'=>'id'),
				'Question'=>Array('faq'=>'question'),
				'Category'=>Array('faq'=>'category'),
				'Product Name'=>Array('faq'=>'product_id'),
				'Created Time'=>Array('crmentity'=>'createdtime'),
				'Modified Time'=>Array('crmentity'=>'modifiedtime')
				);

	var $list_fields_name = Array(
				        'FAQ Id'=>'',
				        'Question'=>'question',
				        'Category'=>'faqcategories',
				        'Product Name'=>'product_id',
						'Created Time'=>'createdtime',
						'Modified Time'=>'modifiedtime'
				      );
	var $list_link_field= 'question';

	var $search_fields = Array(
				'Account Name'=>Array('account'=>'accountname'),
				'City'=>Array('accountbillads'=>'bill_city'),
				);

	var $search_fields_name = Array(
				        'Account Name'=>'accountname',
				        'City'=>'bill_city',
				      );

	//Added these variables which are used as default order by and sortorder in ListView
	var $default_order_by = 'id';
	var $default_sort_order = 'DESC';

	var $mandatory_fields = Array('question','faq_answer','createdtime' ,'modifiedtime');
	//crmv@10759
	var $search_base_field = 'question';
	//crmv@10759 e
	/**	Constructor which will set the column_fields in this object
	 */
	function __construct() {
		global $table_prefix;
		parent::__construct(); // crmv@37004
		$this->table_name = $table_prefix."_faq";
		// crmv@81217
		$this->tab_name = Array($table_prefix.'_crmentity',$table_prefix.'_faq', $table_prefix.'_faqcf');
		$this->tab_name_index = Array($table_prefix.'_crmentity'=>'crmid',$table_prefix.'_faq'=>'id',$table_prefix.'_faqcf'=>'faqid', $table_prefix.'_faqcomments'=>'faqid');
		$this->customFieldTable = Array($table_prefix.'_faqcf', 'faqid');
		// crmv@81217e
		$this->entity_table = $table_prefix."_crmentity";
		$this->log =LoggerManager::getLogger('faq');
		$this->log->debug("Entering Faq() method ...");
		$this->db = PearDatabase::getInstance();
		$this->column_fields = getColumnFields('Faq');
		$this->log->debug("Exiting Faq method ...");
	}

	function save_module($module)
	{
		global $table_prefix;
		//Inserting into Faq comment table
		$this->insertIntoFAQCommentTable($table_prefix.'_faqcomments', $module);

	}


	/** Function to insert values in vte_faqcomments table for the specified module,
  	  * @param $table_name -- table name:: Type varchar
  	  * @param $module -- module:: Type varchar
 	 */
	function insertIntoFAQCommentTable($table_name, $module)
	{
		global $log;
		$log->info("in insertIntoFAQCommentTable  ".$table_name."    module is  ".$module);
        	global $adb;
			global $table_prefix;
        	$current_time = $adb->formatDate(date('Y-m-d H:i:s'), true);

		if($this->column_fields['comments'] != '')
			$comment = $this->column_fields['comments'];
		else
			$comment = $_REQUEST['comments'];

		if($comment != '')
		{
			$faqid = $adb->getUniqueID($table_prefix.'_faqcomments');
			$params = array($faqid, $this->id, from_html($comment), $current_time);
			$sql = "insert into ".$table_prefix."_faqcomments values(?, ?, ?, ?)";
			$adb->pquery($sql, $params);
		}
	}


	/**     Function to get the list of comments for the given FAQ id
         *      @param  int  $faqid - FAQ id
	 *      @return list $list - return the list of comments and comment informations as a html output where as these comments and comments informations will be formed in div tag.
        **/
	function getFAQComments($faqid)
	{
		global $log, $default_charset;
		global $table_prefix;
		$log->debug("Entering getFAQComments(".$faqid.") method ...");
		global $mod_strings;
		$sql = "select * from ".$table_prefix."_faqcomments where faqid=?";
		$result = $this->db->pquery($sql, array($faqid));
		$noofrows = $this->db->num_rows($result);

		//In ajax save we should not add this div
		if($_REQUEST['fldName'] != 'comments')
		{
			$list .= '<div id="comments_div" style="overflow:auto;max-height:200px;width:100%;">';
			$enddiv = '</div>';
		}

		for($i=0;$i<$noofrows;$i++)
		{
			$comment = $this->db->query_result($result,$i,'comments');
			$createdtime = $this->db->query_result($result,$i,'createdtime');
			if($comment != '')
			{
				//this div is to display the comment
				$list .= '<div style="margin-bottom: 5px;">';
				
				if($_REQUEST['action'] == 'FaqAjax') {
					$comment = htmlentities($comment, ENT_QUOTES, $default_charset);
				}
				$list .= '<div style="padding-top:5px;" class="dataField">'.make_clickable(nl2br($comment)).'</div>';

				//this div is to display the created time
				$list .= '<div valign="top" style="padding-bottom:5px;text-align:'.$textalign.';" class="dataLabel">';
				$list .=" <a href=\"javascript:;\" title=\"".CRMVUtils::timestamp($createdtime)."\" style=\"color: gray; text-decoration: none;\">".CRMVUtils::timestampAgo($createdtime)."</a>"; // crmv@164654
				$list .= '</div>';
				$list .= '</div>';
			}
		}
		$list .= $enddiv;

		$log->debug("Exiting getFAQComments method ...");
		return $list;
	}

	// crmv@97237 - removed report function

	/*
	 * Function to get the relation tables for related modules
	 * @param - $secmodule secondary module name
	 * returns the array with table names and fieldnames storing relations between module and this module
	 */
	function setRelationTables($secmodule){
		global $table_prefix;
		$rel_tables = array (
			"Documents" => array($table_prefix."_senotesrel"=>array("crmid","notesid"),$table_prefix."_faq"=>"id"),
		);
		return $rel_tables[$secmodule];
	}
}

?>