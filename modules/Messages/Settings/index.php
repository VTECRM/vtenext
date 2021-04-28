<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@3082m crmv@44037 crmv@46468 */

global $current_user, $adb, $table_prefix, $theme;
$small_page_title = getTranslatedString('LBL_SETTINGS');
$settings_url = 'index.php?module=Messages&action=MessagesAjax&file=Settings/index';
$small_page_title_link = "location.href='$settings_url';";
$operation = $_REQUEST['operation'];
switch($operation) {
	case '':
		include('themes/SmallHeader.php');
		
		$smarty = new VteSmarty();
		$list = array(
			array(
				'title'=>getTranslatedString('LBL_ACCOUNTS_SETTINGS','Messages'),
				'link'=>'index.php?module=Messages&action=MessagesAjax&file=Settings/index&operation=Accounts',
			),
			array(
				'title'=>getTranslatedString('Folders','Messages'),
				'link'=>'index.php?module=Messages&action=MessagesAjax&file=Settings/index&operation=Folders',
			),
			array(
				'title'=>getTranslatedString('Layout','Messages'),
				'link'=>'index.php?module=Messages&action=MessagesAjax&file=Settings/index&operation=Layout',
			),
			array(
				'title'=>getTranslatedString('Filters','Messages'),
				'link'=>'index.php?module=Messages&action=MessagesAjax&file=Settings/index&operation=Filters',
			),
			array(
				'title'=>getTranslatedString('LBL_POP3','Messages'),
				'description'=>getTranslatedString('LBL_POP3_DESCR','Messages'),
				'link'=>'index.php?module=Messages&action=MessagesAjax&file=Settings/index&operation=Pop3',
			),
			// crmv@191351
			array(
				'title'=>getTranslatedString('LBL_OUT_OF_OFFICE','Messages'),
				'link'=>'index.php?module=Messages&action=MessagesAjax&file=Settings/index&operation=OutOfOffice',
			),
			// crmv@191351e
		);
		$smarty->assign('LIST',$list);
		$smarty->assign('THEME',$theme);
		$smarty->display('modules/Messages/Settings/List.tpl');
		
		include('themes/SmallFooter.php');
		break;
	// Account start
	case 'Accounts':
		$small_page_subtitle = getTranslatedString('LBL_ACCOUNTS_SETTINGS','Messages');
		$small_page_buttons = '
		<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
		<tr>
			<td width="100%" style="padding:5px"></td>
		 	<td align="right" style="padding: 5px;" nowrap>
		 		<input type="button" class="crmbutton small create" value="'.getTranslatedString('LBL_ADD_BUTTON').'" onclick="addAccount();">
				<input class="crmbutton small cancel" onclick="'.$small_page_title_link.'" type="button" name="button" style="min-width:70px" title="'.getTranslatedString('LBL_CANCEL_BUTTON_TITLE').'" value="'.getTranslatedString('LBL_CANCEL_BUTTON_LABEL').'">
		 	</td>
		 </tr>
		 </table>';
		include('themes/SmallHeader.php');
		
		$smarty = new VteSmarty();
		$focus = CRMEntity::getInstance('Messages');
		$smarty->assign('ACCOUNTS',$focus->getUserAccounts());
		$smarty->assign('ACCOUNTS_AVAILABLE',$focus->getAvailableAccounts());
		$smarty->assign('THEME',$theme);
		$smarty->display('modules/Messages/Settings/Accounts.tpl');
		
		include('themes/SmallFooter.php');
		break;
	case 'EditAccount':
		if (!empty($_REQUEST['error'])) $error = getTranslatedString($_REQUEST['error'],'Messages');	//crmv@125629
		
		$small_page_subtitle = getTranslatedString('LBL_ACCOUNTS_SETTINGS','Messages');
		$small_page_buttons = '
		<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
		<tr>
			<td width="100%" style="padding:5px"><span class="errorString" style="padding-right:5px">'.$error.'</span></td> <!-- crmv@125629 -->
		 	<td align="right" style="padding: 5px;" nowrap>
		 		<input class="crmbutton small save" onClick="if(validateSaveAccount()) document.SaveAccount.submit();" type="button" name="button" style="min-width:70px" title="'.getTranslatedString('LBL_SAVE_BUTTON_TITLE').'" value="'.getTranslatedString('LBL_SAVE_BUTTON_LABEL').'">
				<input class="crmbutton small cancel" onclick="history.back();" type="button" name="button" style="min-width:70px" title="'.getTranslatedString('LBL_CANCEL_BUTTON_TITLE').'" value="'.getTranslatedString('LBL_CANCEL_BUTTON_LABEL').'">
		 	</td>
		 </tr>
		 </table>';
		$header_z_index = 2; //crmv@114260
		include('themes/SmallHeader.php');
		
		$smarty = new VteSmarty();
		$focus = CRMEntity::getInstance('Messages');
		if ($_REQUEST['id'] === '') {
			$smarty->assign('ACCOUNT',array('account'=>'','smtp_account'=>''));	//crmv@152167
		} else {
			$smarty->assign('KEY',$_REQUEST['id']);
			$accounts = $focus->getUserAccounts('',$_REQUEST['id']);
			$smarty->assign('ACCOUNT',$accounts[0]);
		}
		$smarty->assign('ACCOUNTS_AVAILABLE',$focus->getAvailableAccounts());
		$smarty->assign('ACCOUNTS_AVAILABLE_JSON',Zend_Json::encode($focus->getAvailableAccounts())); // crmv@206145
		$smarty->assign('SMTP_ACCOUNTS_AVAILABLE',$focus->getAvailableSmtpAccounts()); //crmv@114260
		$smarty->assign('THEME',$theme);
		$smarty->assign("FCKEDITOR_DISPLAY",$FCKEDITOR_DISPLAY);
		$smarty->display('modules/Messages/Settings/EditAccount.tpl');
		
		include('themes/SmallFooter.php');
		break;
	case 'SaveAccount':
		if (!isset($_REQUEST['main'])) {
			$main = 0;
		} else {
			$main = 1;
		}
		$focus = CRMEntity::getInstance('Messages');
		// crmv@114260
		if ($_REQUEST['account']) {
			$id = $focus->saveAccount($_REQUEST['id'],$_REQUEST['account'],$_REQUEST['username'],$_REQUEST['email'],$_REQUEST['password'],$main,$_REQUEST['description'],$_REQUEST['server'],$_REQUEST['port'],$_REQUEST['ssl_tls'],$_REQUEST['domain'],$_REQUEST['signature'],$_REQUEST['authentication'],$_REQUEST['token'],$_REQUEST['refresh_token'],$_REQUEST['expires']); // crmv@50745 crmv@206145
		
			if ($id > 0) {
				if ($_REQUEST['smtp_account']) {
					$smtpAuth = isset($_REQUEST['smtp_auth']) ? true : false;	//crmv@152167
					$focus->setAccountSmtp($id, $_REQUEST['smtp_account'], $_REQUEST['smtp_server'], $_REQUEST['smtp_port'], $_REQUEST['smtp_username'], $_REQUEST['smtp_password'], $smtpAuth);
				} else {
					$focus->clearAccountSmtp($id);
				}
			}
			//crmv@125629
			try {
				$focus->syncFolders($current_user->id,$id);
			} catch (Exception $e) {
				if ($e->getMessage() == 'ERR_IMAP_AUTENTICATION') {
					header("location: $settings_url&operation=EditAccount&id={$id}&error=".$e->getMessage());
					exit;
				}
			}
			//crmv@125629e
		}
		// crmv@114260e
		
		$result = $adb->pquery("select * from {$table_prefix}_messages_sfolders where accountid = ?",array($id));	//crmv@48159
		if ($result && $adb->num_rows($result) > 0) {
			header("location: $settings_url&operation=Accounts");
		} else {
			// try to configure automatically folders
			if ($focus->autoSetSpecialFolders($id)) {
				header("location: $settings_url&operation=Accounts");
			} else {
				header("location: $settings_url&operation=Folders&account=$id&mode=autoconfigure");
			}
		}
		break;
	case 'DeleteAccount':
		$focus = CRMEntity::getInstance('Messages');
		// crmv@107655
		if ($focus->canUserDeleteAccount($current_user->id, $_REQUEST['id'])) {
			$focus->deleteAccount($_REQUEST['id']);
		}
		// crmv@107655e
		header("location: $settings_url&operation=Accounts");
		break;
	//crmv@51862
	case 'SyncAccount':
		$focus = CRMEntity::getInstance('Messages');
		$focus->syncAll($current_user->id, $_REQUEST['id']);
		header("location: $settings_url&operation=Accounts");
		break;
	//crmv@51862e
	// Account end
	// Folders start
	case 'Folders':
		$focus = CRMEntity::getInstance('Messages');
		if (isset($_REQUEST['account'])) {
			$account = $_REQUEST['account'];
		} else {
			$account = $focus->getMainUserAccount();
			$account = $account['id'];
		}
		
		$small_page_subtitle = getTranslatedString('Folders','Messages');
		$small_page_buttons = '
		<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
		<tr>
			<td width="100%" style="padding:5px">'.$focus->getUserAccountsPicklist($account,'reloadFoldersSettings').'</td>
		 	<td align="right" style="padding: 5px;" nowrap>
		 		<input class="crmbutton small save" onClick="setFolderSettings();document.SaveFolders.submit();" type="button" name="button" style="min-width:70px" title="'.getTranslatedString('LBL_SAVE_BUTTON_TITLE').'" value="'.getTranslatedString('LBL_SAVE_BUTTON_LABEL').'">
				<input class="crmbutton small cancel" onclick="'.$small_page_title_link.'" type="button" name="button" style="min-width:70px" title="'.getTranslatedString('LBL_CANCEL_BUTTON_TITLE').'" value="'.getTranslatedString('LBL_CANCEL_BUTTON_LABEL').'">
		 	</td>
		 </tr>
		 </table>';
		include('themes/SmallHeader.php');
		
		$smarty = new VteSmarty();
		$focus->setAccount($account);
		$focus->getZendMailStorageImap();
		$folder_list = $focus->getFoldersList('');
		//crmv@49843
		if (empty($folder_list)){
			$focus->syncFolders($current_user->id,$account);
			$folder_list = $focus->getFoldersList('');
		}
		//crmv@49843 e
		$specialFolders = $focus->getSpecialFolders();
		
		// suggest folders
		if ($_REQUEST['mode'] == 'autoconfigure') {
			$account_info = $focus->getUserAccounts('',$account);
			$account_info = $account_info[0];
			$folder_list_keys = array_keys($folder_list);
			foreach($specialFolders as $special_folder => $f) {
				if (in_array($special_folder,$folder_list_keys)) {
					$specialFolders[$special_folder] = $special_folder;
				} else {
					foreach($folder_list_keys as $f) {
						if (stripos($f,$special_folder) !== false) {
							$specialFolders[$special_folder] = $f;
						}
					}
				}
			}
			// Gmail
			if ($account_info['account'] == 'Gmail') {
				if (in_array('[Gmail]/Bozze',$folder_list_keys)) {
					$specialFolders['Drafts'] = '[Gmail]/Bozze';
				}
				if (in_array('[Gmail]/Posta inviata',$folder_list_keys)) {
					$specialFolders['Sent'] = '[Gmail]/Posta inviata';
				}
				if (in_array('[Gmail]/Spam',$folder_list_keys)) {
					$specialFolders['Spam'] = '[Gmail]/Spam';
				}
				if (in_array('[Gmail]/Cestino',$folder_list_keys)) {
					$specialFolders['Trash'] = '[Gmail]/Cestino';
				}
			}
			// crmv@206145
			if ($account_info['account'] == 'Office365') {
				if (empty($specialFolders['Spam']) && in_array('Junk',$folder_list_keys))
					$specialFolders['Spam'] = 'Junk';
				if (empty($specialFolders['Trash']) && in_array('Deleted',$folder_list_keys))
					$specialFolders['Trash'] = 'Deleted';
			}
			// crmv@206145e
		}
		
		$smarty->assign('FOLDER_LIST',$folder_list);
		$smarty->assign('SPECIAL_FOLDERS',$specialFolders);
		$smarty->assign('FOLDER_IMGS',$focus->folderImgs); // crmv@192843
		$smarty->assign('THEME',$theme);
		$smarty->display('modules/Messages/Settings/Folders.tpl');
		
		include('themes/SmallFooter.php');
		break;
	case 'SaveFolders':
		$focus = CRMEntity::getInstance('Messages');
		$specialFolders = array();
		$oldSpecialFolders = $focus->getSpecialFolders();
		foreach($oldSpecialFolders as $special => $folder) {
			if (!empty($_REQUEST[$special])) {
				$specialFolders[$special] = $_REQUEST[$special];
			}
		}
		$focus->setSpecialFolders($specialFolders,$_REQUEST['account']);
		header("location: $settings_url");
		break;
	// Folders end
	// Filters start
	case 'Filters':
		$focus = CRMEntity::getInstance('Messages');
		if (isset($_REQUEST['account'])) {
			$account = $_REQUEST['account'];
		} else {
			$account = $focus->getMainUserAccount();
			$account = $account['id'];
		}
		$list = $focus->getFilters($account);
		/*
		if (empty($list)) {
			header("location: index.php?module=Messages&action=MessagesAjax&file=Settings/index&operation=EditFilter");
		}
		*/
		$small_page_subtitle = getTranslatedString('Filters','Messages');
		$small_page_buttons = '
		<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
		<tr>
			<td width="100%" style="padding:5px">'.$focus->getUserAccountsPicklist($account,'reloadFiltersSettings').'</td>
		 	<td align="right" style="padding: 5px;" nowrap>
		 		<input class="crmButton small edit" onClick="jQuery(\'#fancybox-loading\').show();document.Filters.operation.value=\'ScanNow\';document.Filters.submit();" type="button" name="button" title="'.getTranslatedString('LBL_SCAN_ALL_NOW','Messages').'" value="'.getTranslatedString('LBL_SCAN_ALL_NOW','Messages').'">
		 		<input class="crmbutton small create" onClick="location.href=\'index.php?module=Messages&action=MessagesAjax&file=Settings/index&operation=EditFilter&account=\'+jQuery(\'#accountspicklist\').val();" type="button" name="button" style="min-width:70px" title="'.getTranslatedString('LBL_NEW').'" value="'.getTranslatedString('LBL_NEW').'">
				<input class="crmbutton small cancel" onclick="'.$small_page_title_link.'" type="button" name="button" style="min-width:70px" title="'.getTranslatedString('LBL_CANCEL_BUTTON_TITLE').'" value="'.getTranslatedString('LBL_CANCEL_BUTTON_LABEL').'">
		 	</td>
		 </tr>
		 </table>';
		include('themes/SmallHeader.php');
		
		$smarty = new VteSmarty();
		$smarty->assign('ACCOUNT',$account);
		$smarty->assign('FILTER_LIST',$list);
		$smarty->assign('WHERE_LIST',$focus->filterFields);
		$smarty->assign('THEME',$theme);
		$smarty->display('modules/Messages/Settings/Filters.tpl');
		
		include('themes/SmallFooter.php');
		break;
	case 'EditFilter':
		if (isset($_REQUEST['sequence'])) {
			$small_page_subtitle = getTranslatedString('LBL_EDIT').' '.getTranslatedString('Filter','Messages');
		} else {
			$small_page_subtitle = getTranslatedString('LBL_NEW','Messages').' '.getTranslatedString('Filter','Messages');
		}
		$small_page_buttons = '
		<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
		<tr>
			<td width="100%" style="padding:5px"></td>
		 	<td align="right" style="padding: 5px;" nowrap>
		 		<input class="crmbutton small save" onClick="document.EditFilter.submit();" type="button" name="button" style="min-width:70px" title="'.getTranslatedString('LBL_SAVE_BUTTON_TITLE').'" value="'.getTranslatedString('LBL_SAVE_BUTTON_LABEL').'">
				<input class="crmbutton small cancel" onclick="history.back();" type="button" name="button" style="min-width:70px" title="'.getTranslatedString('LBL_CANCEL_BUTTON_TITLE').'" value="'.getTranslatedString('LBL_CANCEL_BUTTON_LABEL').'">
		 	</td>
		 </tr>
		 </table>';
		include('themes/SmallHeader.php');

		$smarty = new VteSmarty();
		$focus = CRMEntity::getInstance('Messages');
		$focus->setAccount($_REQUEST['account']);
		$smarty->assign('ACCOUNT',$_REQUEST['account']);
		$focus->getZendMailStorageImap();
		$specialFolders = $focus->getSpecialFolders();
		$folder_list = $focus->getFoldersList('');
		unset($folder_list[$specialFolders['INBOX']]);
		$smarty->assign('FOLDER_LIST',$folder_list);
		$smarty->assign('WHERE_LIST',$focus->filterFields);
		if (isset($_REQUEST['sequence'])) {
			$smarty->assign('SEQUENCE',$_REQUEST['sequence']);
			$result = $adb->pquery("select * from {$table_prefix}_messages_filters where userid = ? and accountid = ? and sequence = ?",array($current_user->id,$_REQUEST['account'],$_REQUEST['sequence']));
			if ($result) {
				$smarty->assign('FILTER_WHERE',$adb->query_result($result,0,'filter_where'));
				$smarty->assign('FILTER_WHAT',$adb->query_result($result,0,'filter_what'));
				$smarty->assign('FILTER_FOLDER',$adb->query_result($result,0,'filter_folder'));
			}
		}
		$smarty->assign('THEME',$theme);
		$smarty->display('modules/Messages/Settings/EditFilter.tpl');
		
		include('themes/SmallFooter.php');
		break;
	case 'SaveFilter':
		$focus = CRMEntity::getInstance('Messages');
		$focus->setFilter($_REQUEST['account'],$_REQUEST['filter_where'],$_REQUEST['filter_what'],$_REQUEST['filter_folder'],$_REQUEST['sequence']);
		header("location: index.php?module=Messages&action=MessagesAjax&file=Settings/index&operation=Filters&account=".$_REQUEST['account']);
		break;
	case 'DeleteFilter':
		$focus = CRMEntity::getInstance('Messages');
		$focus->deleteFilter($_REQUEST['account'],$_REQUEST['sequence']);
		header("location: index.php?module=Messages&action=MessagesAjax&file=Settings/index&operation=Filters&account=".$_REQUEST['account']);
		break;
	case 'MoveFilter':
		$focus = CRMEntity::getInstance('Messages');
		$focus->moveFilter($_REQUEST['account'],$_REQUEST['sequence'],$_REQUEST['to']);
		header("location: index.php?module=Messages&action=MessagesAjax&file=Settings/index&operation=Filters");
		break;
	case 'ScanNow':
		$small_page_subtitle = getTranslatedString('LBL_FILTERED_MEESSAGES','Messages');
		$small_page_subtitle_link = "location.href='{$settings_url}&operation=Filters';";
		include('themes/SmallHeader.php');
		//crmv@63113
		$smarty = new VteSmarty();
		$focus = CRMEntity::getInstance('Messages');
		$errors = array();
		$filtered = $focus->scanNowFilters($_REQUEST['account'], $errors);
		if (!empty($errors)) {
			$error = '';
			$folders_not_found = array_unique($errors['folders_not_found']);
			$smarty->assign('FOLDERS_NOT_FOUND',$folders_not_found);
		}
		//crmv@63113e
		$smarty->assign('FILTERED',$filtered);
		$smarty->assign('FILTER_LINK',$small_page_subtitle_link);
		$smarty->display('modules/Messages/Settings/ScanResults.tpl');
		
		include('themes/SmallFooter.php');
		break;
	// Filters end
	// Pop3 start
	case 'Pop3':
		$focus = CRMEntity::getInstance('Messages');
		$list = $focus->getPop3();
		if (empty($list)) {
			header("location: index.php?module=Messages&action=MessagesAjax&file=Settings/index&operation=EditPop3");
			break;
		}

		$small_page_subtitle = getTranslatedString('LBL_POP3','Messages');
		$small_page_buttons = '
		<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
		<tr>
			<td width="100%" style="padding:5px"></td>
		 	<td align="right" style="padding: 5px;" nowrap>
		 		<input class="crmbutton small create" onClick="location.href=\'index.php?module=Messages&action=MessagesAjax&file=Settings/index&operation=EditPop3\';" type="button" name="button" style="min-width:70px" title="'.getTranslatedString('LBL_NEW').'" value="'.getTranslatedString('LBL_NEW').'">
				<input class="crmbutton small cancel" onclick="'.$small_page_title_link.'" type="button" name="button" style="min-width:70px" title="'.getTranslatedString('LBL_CANCEL_BUTTON_TITLE').'" value="'.getTranslatedString('LBL_CANCEL_BUTTON_LABEL').'">
		 	</td>
		 </tr>
		 </table>';
		include('themes/SmallHeader.php');
		
		$smarty = new VteSmarty();
		$smarty->assign('LIST',$list);
		$smarty->assign('THEME',$theme);
		$smarty->display('modules/Messages/Settings/Pop3.tpl');
		
		include('themes/SmallFooter.php');
		break;
	case 'EditPop3':
		if (isset($_REQUEST['id'])) {
			$small_page_subtitle = getTranslatedString('LBL_EDIT','Messages').' '.getTranslatedString('LBL_POP3','Messages');
		} else {
			$small_page_subtitle = getTranslatedString('LBL_NEW','Messages').' '.getTranslatedString('LBL_POP3','Messages');
		}
		$small_page_buttons = '
		<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
		<tr>
			<td width="100%" style="padding:5px"></td>
		 	<td align="right" style="padding: 5px;" nowrap>
		 		<input class="crmbutton small save" onClick="document.EditPop3.submit();" type="button" name="button" style="min-width:70px" title="'.getTranslatedString('LBL_SAVE_BUTTON_TITLE').'" value="'.getTranslatedString('LBL_SAVE_BUTTON_LABEL').'">
				<input class="crmbutton small cancel" onclick="history.back();" type="button" name="button" style="min-width:70px" title="'.getTranslatedString('LBL_CANCEL_BUTTON_TITLE').'" value="'.getTranslatedString('LBL_CANCEL_BUTTON_LABEL').'">
		 	</td>
		 </tr>
		 </table>';
		include('themes/SmallHeader.php');
		
		$smarty = new VteSmarty();
		$focus = CRMEntity::getInstance('Messages');
		$smarty->assign('PORT',110);
		$smarty->assign('LMOS',1);
		$smarty->assign('ACTIVE',1);
		if (isset($_REQUEST['id'])) {
			$smarty->assign('ID',$_REQUEST['id']);
			$result = $adb->pquery("select * from {$table_prefix}_messages_pop3 where id = ?",array($_REQUEST['id']));
			if ($result) {
				$password = $adb->query_result($result,0,'password');
				require_once('include/utils/encryption.php');
				$de_crypt = new Encryption();
				if(!empty($password)) {
					$password = $de_crypt->decrypt($password);
				}
				$account = $adb->query_result($result,0,'accountid');
				$smarty->assign('SERVER',$adb->query_result($result,0,'server'));
				$smarty->assign('PORT',$adb->query_result($result,0,'port'));
				$smarty->assign('USERNAME',$adb->query_result($result,0,'username'));
				$smarty->assign('PASSWORD',$password);
				$smarty->assign('SECURE',$adb->query_result($result,0,'secure'));
				$smarty->assign('FOLDER',$adb->query_result($result,0,'folder'));
				$smarty->assign('LMOS',$adb->query_result($result,0,'lmos'));
				$smarty->assign('ACTIVE',$adb->query_result($result,0,'active'));
			}
		} else {
			$account = $focus->getMainUserAccount();
			$account = $account['id'];
		}
		$focus->setAccount($account);
		$smarty->assign('ACCOUNTS',$focus->getUserAccountsPicklist($account,'reloadPOP3FoldersSettings'));
		
		$focus->getZendMailStorageImap();
		$folder_list = $focus->getFoldersList('');
		$smarty->assign('FOLDER_LIST',$folder_list);
		
		$smarty->assign('THEME',$theme);
		$smarty->display('modules/Messages/Settings/EditPop3.tpl');
		
		include('themes/SmallFooter.php');
		break;
	case 'SavePop3':
		$lmos = 0;
		$active = 0;
		if (isset($_REQUEST['lmos'])) {
			$lmos = 1;
		}
		if (isset($_REQUEST['active'])) {
			$active = 1;
		}
		$focus = CRMEntity::getInstance('Messages');
		$focus->setPop3($_REQUEST['server'],$_REQUEST['port'],$_REQUEST['username'],$_REQUEST['password'],$_REQUEST['secure'],$_REQUEST['accountspicklist'],$_REQUEST['folder'],$lmos,$active,'',$_REQUEST['id']);
		header("location: index.php?module=Messages&action=MessagesAjax&file=Settings/index&operation=Pop3");
		break;
	case 'DeletePop3':
		$focus = CRMEntity::getInstance('Messages');
		$focus->deletePop3($_REQUEST['id']);
		header("location: index.php?module=Messages&action=MessagesAjax&file=Settings/index&operation=Pop3");
		break;
	case 'FolderPicklist':
		$focus = CRMEntity::getInstance('Messages');
		if (isset($_REQUEST['account'])) {
			$account = $_REQUEST['account'];
		} else {
			$account = $focus->getMainUserAccount();
			$account = $account['id'];
		}
		$focus->setAccount($account);
		$focus->getZendMailStorageImap();
		$folder_list = $focus->getFoldersList('');
		$smarty = new VteSmarty();
		$smarty->assign('FOLDER_LIST',$folder_list);
		//$smarty->assign('SEL_FOLDER',$_REQUEST['folder']);
		$smarty->display('modules/Messages/Settings/FolderPicklist.tpl');
		break;
	// Pop3 end
	case 'Layout':
		$small_page_buttons = '
		<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
		<tr>
			<td width="100%" style="padding:5px"></td>
		 	<td align="right" style="padding: 5px;" nowrap>
		 		<input class="crmbutton small save" onClick="document.Layout.submit();" type="button" name="button" style="min-width:70px" title="'.getTranslatedString('LBL_SAVE_BUTTON_TITLE').'" value="'.getTranslatedString('LBL_SAVE_BUTTON_LABEL').'">
				<input class="crmbutton small cancel" onclick="history.back();" type="button" name="button" style="min-width:70px" title="'.getTranslatedString('LBL_CANCEL_BUTTON_TITLE').'" value="'.getTranslatedString('LBL_CANCEL_BUTTON_LABEL').'">
		 	</td>
		 </tr>
		 </table>';
		include('themes/SmallHeader.php');
		
		$focus = CRMEntity::getInstance('Messages');
		$smarty = new VteSmarty();
		$smarty->assign('SETTINGS',$focus->getLayoutSettings());
		$smarty->display('modules/Messages/Settings/Layout.tpl');
		
		include('themes/SmallFooter.php');
		break;
	case 'SaveLayout':
		$focus = CRMEntity::getInstance('Messages');
		$focus->saveLayoutSettings($_REQUEST);
		header("location: index.php?module=Messages&action=MessagesAjax&file=Settings/index");
		break;
	// crmv@191351
	case 'OutOfOffice':
		$small_page_buttons = '
		<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
			<tr>
				<td width="100%" style="padding:5px;"></td>
				<td align="right" style="padding:5px;" nowrap>
					<button class="crmbutton save" onclick="return VTE.Messages.Settings.FormOutOfOffice.validateAndSave();" type="submit" name="button">'.getTranslatedString('LBL_SAVE_BUTTON_LABEL').'</button>
					<button class="crmbutton cancel" onclick="history.back();" type="button" name="button">'.getTranslatedString('LBL_CANCEL_BUTTON_LABEL').'</button>
				</td>
			</tr>
		</table>';
		$header_z_index = 20;
		include('themes/SmallHeader.php');
		
		$focus = CRMEntity::getInstance('Messages');
		$smarty = new VteSmarty();
		
		$accounts = array();
		$userAccounts = $focus->getUserAccounts();
		if (!empty($userAccounts)) {
			foreach($userAccounts as $account) {
				$accounts[] = array('id'=>$account['id'],'name'=>$account['description']);
			}
		}
		$smarty->assign('ACCOUNTS', $accounts);
		
		$settings = $focus->getOutOfOfficeSettings();
		$smarty->assign('SETTINGS', $settings);
		
		require_once('modules/SDK/src/73/73Utils.php');
		$uitypeTimeUtils = UitypeTimeUtils::getInstance();
		$startDateHtml = getOutputHtml(5, 'start_date', 'Start date', 100, array('start_date'=>$settings['start_date']), 1, 'Messages');
		$smarty->assign('START_DATE_HTML', $startDateHtml);
		$endDateHtml = getOutputHtml(5, 'end_date', 'End date', 100, array('end_date'=>$settings['end_date']), 1, 'Messages');
		$smarty->assign('END_DATE_HTML', $endDateHtml);
		$startTimeHtml = getOutputHtml(73, 'start_time', 'Start time', 100, array('start_time'=>$uitypeTimeUtils->time2Seconds($settings['start_time'])), 1, 'Messages');
		$smarty->assign('START_TIME_HTML', $startTimeHtml);
		$endTimeHtml = getOutputHtml(73, 'end_time', 'End time', 100, array('end_time'=>$uitypeTimeUtils->time2Seconds($settings['end_time'])), 1, 'Messages');
		$smarty->assign('END_TIME_HTML', $endTimeHtml);
		
		$smarty->display('modules/Messages/Settings/OutOfOffice.tpl');
		
		include('themes/SmallFooter.php');
		break;
	case 'SaveOutOfOffice':
		global $default_charset;
		$focus = CRMEntity::getInstance('Messages');
		$focus->saveOutOfOfficeSettings(array(
			'active'=>($_REQUEST['active']=='1')?1:0,
			'message_subject'=>$_REQUEST['message_subject'],
			'message_body'=>html_entity_decode($_REQUEST['message_body'],ENT_QUOTES,$default_charset),
			'start_date_allday'=>($_REQUEST['start_date_allday']=='on')?1:0,
			'start_date'=>getValidDBInsertDateValue($_REQUEST['start_date']),
			'start_time'=>$_REQUEST['start_time'],
			'end_date_active'=>($_REQUEST['end_date_active']=='on')?1:0,
			'end_date'=>getValidDBInsertDateValue($_REQUEST['end_date']),
			'end_time'=>$_REQUEST['end_time'],
			'only_known_addresses_active'=>($_REQUEST['only_known_addresses_active']=='on')?1:0,
			'accounts'=>Zend_Json::encode($_REQUEST['accounts']),
		));
		header("location: index.php?module=Messages&action=MessagesAjax&file=Settings/index");
		break;
	// crmv@191351e
	// crmv@206145
	case 'GetOAuthToken':
		$focus = CRMEntity::getInstance('Messages');
		
		if (!isset($_GET['code'])) {
			// If we don't have an authorization code then get one
			
			$providerName = $focus->getOuathProviderName([
				'account' => $_REQUEST['account'],
				'server' => $_REQUEST['server'],
			]);
			VteSession::set('provider',$providerName);
			$provider = $focus->getOuathProvider($providerName);
			
			global $site_URL, $application_unique_key;
			$license_id = getMorphsuitNo();
			$state = substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil(20/strlen($x)) )),1,20);
			
			// prevent cross-site request forgery attacks
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, 'https://oauthms.vtecrm.net/savestate.php');
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, [
				'state' => $state,
				'vteurl' => $site_URL,
				'application_unique_key' => $application_unique_key,
				'license_id' => $license_id,
			]);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_exec($ch);
			curl_close($ch);
			
			$options = $focus->getOuathProviderOptions($providerName, $state, $_REQUEST['username'], true);
			$authUrl = $provider->getAuthorizationUrl($options);
			VteSession::set('oauth2state',$provider->getState());
			header('Location: ' . $authUrl);
			exit;
		// Check given state against previously stored one to mitigate CSRF attack
		} elseif (empty($_GET['state']) || ($_GET['state'] !== VteSession::get('oauth2state'))) {
			VteSession::remove('oauth2state');
			VteSession::remove('provider');
			exit('Invalid state');
		} else {
			$providerName = VteSession::get('provider');
			VteSession::remove('provider');
			
			$provider = $focus->getOuathProvider($providerName);
			
			// Try to get an access token (using the authorization code grant)
			$token = $provider->getAccessToken(
				'authorization_code',
				[
					'code' => $_GET['code']
				]
			);
			//echo '<pre>'; print_r($token); echo '</pre>';
			
			$user = $provider->getResourceOwner($token);
			//echo '<pre>'; print_r($user); echo '</pre>';
			
			echo '<script type="text/javascript">
				window.opener.jQuery("#username").val("'.$user->getEmail().'");
				window.opener.jQuery("#token").val("'.$token->getToken().'");
				window.opener.jQuery("#refresh_token").val("'.$token->getRefreshToken().'");
				window.opener.jQuery("#expires").val("'.$token->getExpires().'");
				window.opener.jQuery("#get_token_link").hide();
				window.close();
			</script>';
			exit;
		}
		break;
	// crmv@206145e
}
$otherSmarty = new VteSmarty();
$otherSmarty->display('modules/Messages/Settings/BottomSettings.tpl');
?>