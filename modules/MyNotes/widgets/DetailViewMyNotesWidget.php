<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@168573

class MyNotes_DetailViewMyNotesWidget {
	
	private $_name;
	
	private $title;
	
	protected $context = false;
	
	function __construct() {
		$this->_name = 'DetailViewMyNotesWidget';
		$this->title = getTranslatedString('MyNotes', 'MyNotes');
	}
	
	function name() {
		return $this->_name;
	}
	
	function title() {
		return $this->title;
	}
	
	function getFromContext($key, $purify = false) {
		if ($this->context) {
			$value = $this->context[$key];
			if ($purify && !empty($value)) {
				$value = vtlib_purify($value);
			}
			return $value;
		}
		return false;
	}
	
	function getData($parentId) {
		global $current_user;
		
		$focus = CRMEntity::getInstance('MyNotes');
		$notes = $focus->getRelNotes($parentId);
		
		$data = array();
		$data['permissions'] = array();
		
		$permWrite = isPermitted('MyNotes', 'EditView') === 'yes';
		$data['permissions']['write'] = $permWrite;
		
		$noteList = array();
		
		if (is_array($notes)) {
			foreach ($notes as $note) {
				$noteFocus = CRMEntity::getInstance('MyNotes');
				$noteFocus->id = $note;
				$noteFocus->retrieve_entity_info_no_html($note, 'MyNotes');
				
				$timestampAgo = CRMVUtils::timestampAgo($noteFocus->column_fields['createdtime']);
				$noteFocus->column_fields['created_timestamp_ago'] = $timestampAgo;
				
				$timestamp = CRMVUtils::timestamp($noteFocus->column_fields['createdtime']);
				$noteFocus->column_fields['created_timestamp'] = $timestamp;
				
				$author = getOwnerId($note);
				$authorFocus = CRMEntity::getInstance('Users');
				$authorFocus->id = $author;
				$authorFocus->retrieve_entity_info($author, 'Users');
				
				$noteFocus->column_fields['assigned_user_avatar'] = getUserAvatar($author);
				$noteFocus->column_fields['assigned_user_formatted'] = $current_user->formatUserName($author, $authorFocus->column_fields);
				
				$editable = isPermitted('MyNotes', 'EditView', $note) === 'yes';
				$noteFocus->column_fields['editable'] = $editable;
				
				$deletable = isPermitted('MyNotes', 'Delete', $note) === 'yes';
				$noteFocus->column_fields['deletable'] = $deletable;
				
				// crmv@203919
				$cleanHtml = htmlspecialchars($noteFocus->column_fields['description'], ENT_COMPAT, 'UTF-8');
				$noteFocus->column_fields['html_description'] = make_clickable(nl2br($cleanHtml));
				// crmv@203919e
				
				$noteList[] = $noteFocus->column_fields;
			}
		}
		
		$data['list'] = $noteList;
		
		return $data;
	}
	
	function process($context = false) {
		global $theme, $app_strings, $current_user, $listview_max_textlength;
		
		$this->context = $context;
		$sourceRecordId = $this->getFromContext('ID', true);
		
		$smarty = new VteSmarty();
		$smarty->assign('APP', $app_strings);
		$smarty->assign('THEME', $theme);
		$smarty->assign('NAME', $this->name());
		
		$data = $this->getData($sourceRecordId);
		$smarty->assign('DATA', Zend_Json::encode($data));
		
		$smarty->assign('PARENT_RECORD', $sourceRecordId);
		$smarty->assign('CURRENT_USER_ID', $current_user->id);
		$smarty->assign('MAX_TEXTLENGTH', $listview_max_textlength);
		
		$smarty->display('modules/MyNotes/widgets/DetailViewMyNotesWidget.tpl');
	}
	
}