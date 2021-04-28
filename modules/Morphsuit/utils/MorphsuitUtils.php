<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
include('modules/Morphsuit/utils/RSA/VTE_RSA.php'); // crmv@146653

function generate_key_pair_morphsuit()
{
	$key_length = 512;
	$rsa = new VTE_Crypt_RSA(); // crmv@146653
	extract($rsa->createKey(512));
	return array('public_key'=>$publickey,'private_key'=>$privatekey);
}
function encrypt_morphsuit($public_key,$plain_text)
{
	$rsa = new VTE_Crypt_RSA(); // crmv@146653
	$rsa->loadKey($public_key);
	$enc_text = $rsa->encrypt($plain_text);
	return $enc_text;
}
function decrypt_morphsuit($private_key,$enc_text)
{
	$rsa = new VTE_Crypt_RSA(); // crmv@146653
	$rsa->loadKey($private_key);
	$plain_text = $rsa->decrypt($enc_text);
	return $plain_text;
}
function getRunTimeMorphsuit()
{
	global $application_unique_key,$root_directory;
	$morphsuit = array();
	$morphsuit['application_unique_key'] = $application_unique_key;
	$morphsuit['root_directory'] = $root_directory;
	
	$mac_address = OSUtils::getMACAddress(); // crmv@167416
	$morphsuit['mac_address'] = $mac_address;
	
	return $morphsuit;
}
function getSavedMorphsuit()
{
	global $adb;
	$res = $adb->query('select morphsuit from tbl_s_morphsuit');
	if ($res && $adb->num_rows($res) > 0)
		$value = $adb->query_result_no_html($res,0,'morphsuit');
	return $value;
}
function checkDataMorphsuit()
{
	if (!itIsTimeToCheck('check')) {
		return true;
	}
	
	$alert_morphsuit = VteSession::get('alertDataMorphsuit');
	if ($alert_morphsuit == '') { $alert_morphsuit = VteSession::set('alertDataMorphsuit', date('Y-m-d H:i:s',strtotime($alert_morphsuit.' - 10 minutes'))); }
	if (date('Y-m-d H:i:s',strtotime($alert_morphsuit.' + 10 minutes')) > date('Y-m-d H:i:s')) return true;
	
	$saved_morphsuit = getSavedMorphsuit();
	$saved_morphsuit = urldecode(trim($saved_morphsuit));
	
	$private_key = substr($saved_morphsuit,0,strpos($saved_morphsuit,'-----'));
	$enc_text = substr($saved_morphsuit,strpos($saved_morphsuit,'-----')+5);
	$saved_morphsuit = @decrypt_morphsuit($private_key,$enc_text);
	if ($saved_morphsuit == '') return false;
	$saved_morphsuit = Zend_Json::decode($saved_morphsuit);
	
	$data_scadenza = $saved_morphsuit['data_scadenza'];
	$now = date('Y-m-d',strtotime('now'));
	if ($data_scadenza >= $now) {
		VteSession::set('checkDataMorphsuit', 'yes');
		return true;
	} else {
		return false;
	}
}
function checkUsersMorphsuit($userid='',$mode='',$user_status='')
{
	global $adb,$table_prefix;
	
	$saved_morphsuit = getSavedMorphsuit();
	$saved_morphsuit = urldecode(trim($saved_morphsuit));
	
	$private_key = substr($saved_morphsuit,0,strpos($saved_morphsuit,'-----'));
	$enc_text = substr($saved_morphsuit,strpos($saved_morphsuit,'-----')+5);
	$saved_morphsuit = @decrypt_morphsuit($private_key,$enc_text);
	if ($saved_morphsuit == '') return false;
	$saved_morphsuit = Zend_Json::decode($saved_morphsuit);
	
	if(in_array($saved_morphsuit['numero_utenti'],array('',0))) {
		VteSession::set('checkUsersMorphsuit', 'yes');
		setVTENumberUserImage($saved_morphsuit['numero_utenti']);
		return true;
	}
	$result = $adb->query("SELECT id FROM ".$table_prefix."_users WHERE status = 'Active' AND user_name <> 'admin'");
	if ($result) {
		$num = $adb->num_rows($result);
	}
	if ($mode == 'create' && $user_status == 'Active') {
		$num++;
	} elseif ($mode == 'edit') {
		$focus = CRMEntity::getInstance('Users');
		$focus->retrieve_entity_info($userid,"Users");
		$old_user_status = $focus->column_fields['status'];
		if ($old_user_status == 'Inactive' && $user_status == 'Active') {
			$num++;
		}
	}
	if ($num <= $saved_morphsuit['numero_utenti']) {
		VteSession::set('checkUsersMorphsuit', 'yes');
		setVTENumberUserImage($saved_morphsuit['numero_utenti']);
		return true;
	}
	return false;
}
function isFreeVersion($saved_morphsuit='') {
	if (VteSession::hasKey('isFreeVersion')) {
		return VteSession::get('isFreeVersion');
	}
	if (!vtlib_isModuleActive("Morphsuit")) {
		return false;
	}
	if ($saved_morphsuit == '') {
		$saved_morphsuit = getSavedMorphsuit();
		$saved_morphsuit = urldecode(trim($saved_morphsuit));
		$private_key = substr($saved_morphsuit,0,strpos($saved_morphsuit,'-----'));
		$enc_text = substr($saved_morphsuit,strpos($saved_morphsuit,'-----')+5);
		$saved_morphsuit = @decrypt_morphsuit($private_key,$enc_text);
		$saved_morphsuit = Zend_Json::decode($saved_morphsuit);
	}
	if ($saved_morphsuit['tipo_installazione'] == 'Free') {
		VteSession::set('isFreeVersion', true);
	} else {
		VteSession::set('isFreeVersion', false);
	}
	return VteSession::get('isFreeVersion');
}
function setVTENumberUserImage($numero_utenti) {
	//crmv@61417
	$cache = Cache::getInstance('numberUsersMorphsuit');
	$cache->set($numero_utenti);
	//crmv@61417e
}
function getVTENumberUserLabel() {
	if (isFreeVersion()) {
		$title = 'FREE';
	} else {
		//crmv@61417
		$cache = Cache::getInstance('numberUsersMorphsuit');
		$numero_utenti = $cache->get();
		//crmv@61417e
		$title = getTranslatedString('LBL_AVAILABLE_USERS','Morphsuit');
		switch ($numero_utenti) {
			case 4:
			case 9:
			case 19:
			case 49:
			case 99:
			case 199:
				$numero_utenti++;
				$title .= getTranslatedString('LBL_MORPHSUIT_USER_NUMBER_'.$numero_utenti,'Morphsuit');
				break;
			case '':
			case 0:
				$title .= getTranslatedString('LBL_MORPHSUIT_USER_NUMBER_UNLIMITED','Morphsuit');
				break;
			default:
				$title .= $numero_utenti;
				break;
		}
	}
	return $title;
}
function getVTENumberUserImage() {
	if (isFreeVersion()) {
		$image = 'VTE_header_free.png';
		$title = 'FREE';
	} else {
		//crmv@61417
		$cache = Cache::getInstance('numberUsersMorphsuit');
		$numero_utenti = $cache->get();
		//crmv@61417e
		$title = getTranslatedString('LBL_AVAILABLE_USERS','Morphsuit');
		switch ($numero_utenti) {
			case 4:
			case 9:
			case 19:
			case 49:
			case 99:
			case 199:
				$numero_utenti++;
				$image = "VTE_header_$numero_utenti.png";
				$title .= getTranslatedString('LBL_MORPHSUIT_USER_NUMBER_'.$numero_utenti,'Morphsuit');
				break;
			case '':
			case 0:
				$image = 'VTE_header_unlimited.png';
				$title .= getTranslatedString('LBL_MORPHSUIT_USER_NUMBER_UNLIMITED','Morphsuit');
				break;
			default:
				$image = '';
				$title .= $numero_utenti;
				break;
		}
	}
	if (!empty($image)) {
		return '<img src="themes/logos/'.$image.'" title="'.$title.'" border=0 />';
	}
}
function isMorphsuitActive($saved_morphsuit='')
{
	global $adb;
	$params_to_check = array('application_unique_key','root_directory');
	
	$runtime_morphsuit = getRunTimeMorphsuit();
	if ($saved_morphsuit == '') $saved_morphsuit = getSavedMorphsuit();
	if ($saved_morphsuit == '') return false;
	$saved_morphsuit = urldecode(trim($saved_morphsuit));
	
	$private_key = substr($saved_morphsuit,0,strpos($saved_morphsuit,'-----'));
	$enc_text = substr($saved_morphsuit,strpos($saved_morphsuit,'-----')+5);
	$saved_morphsuit = @decrypt_morphsuit($private_key,$enc_text);
	if ($saved_morphsuit == '') return false;
	$saved_morphsuit = Zend_Json::decode($saved_morphsuit);
	
	setCacheMorphsuitInfo($saved_morphsuit); //crmv@182677
	
	if(empty($saved_morphsuit) || $saved_morphsuit == '') return false;
	if(!in_array('data_scadenza',array_keys($saved_morphsuit))) return false;
	
	foreach ($saved_morphsuit as $key => $val) {
		if (in_array($key,$params_to_check)) {
			if ($val != $runtime_morphsuit[$key])
				return false;
		}
	}
	VteSession::remove('isFreeVersion');
	VteSession::set('checkMorphsuit', 'yes');
	return true;
}
function returnCheckFunctionMorphsuit($mode,$value,$new) {
	if ($value) {
		if (!$new) {
			VteSession::setArray(array('checkFunctionMorphsuit', $mode), 'yes');
		}
		return true;
	} else {
		if (!$new) {
			VteSession::setArray(array('checkFunctionMorphsuit', $mode), 'no');
		}
		return false;
	}
}
function getLimitFunctionMorphsuit() {
	$saved_morphsuit = getSavedMorphsuit();
	$saved_morphsuit = urldecode(trim($saved_morphsuit));
	
	$private_key = substr($saved_morphsuit,0,strpos($saved_morphsuit,'-----'));
	$enc_text = substr($saved_morphsuit,strpos($saved_morphsuit,'-----')+5);
	$saved_morphsuit = @decrypt_morphsuit($private_key,$enc_text);
	if ($saved_morphsuit == '') return false;
	$saved_morphsuit = Zend_Json::decode($saved_morphsuit);
	
	$limits = array();
	$limits['roles'] = $saved_morphsuit['roles'];
	$limits['profiles'] = $saved_morphsuit['profiles'];
	$limits['pdf'] = $saved_morphsuit['pdf'];
	$limits['adv_sharing_rules'] = $saved_morphsuit['adv_sharing_rules'];
	$limits['sharing_rules_user'] = $saved_morphsuit['sharing_rules_user'];
	return $limits;
}
function checkFunctionMorphsuit($mode,$new=false,$params=array(),$limits=array()) {
	global $adb, $table_prefix;
	if (empty($limits)) {
		$limits = getLimitFunctionMorphsuit();
	}
	switch ($mode) {
		case 'roles':
			$result = $adb->query("select * from {$table_prefix}_role");
			$num_rows = $adb->num_rows($result);
			if ($new) {
				$num_rows++;
			}
			if ($num_rows <= $limits[$mode]) {
				return returnCheckFunctionMorphsuit($mode,true,$new);
			}
			break;
		case 'profiles':
			$result = $adb->query("select * from {$table_prefix}_profile");
			$num_rows = $adb->num_rows($result);
			if ($new) {
				$num_rows++;
			}
			if ($num_rows <= $limits[$mode]) {
				return returnCheckFunctionMorphsuit($mode,true,$new);
			}
			break;
		case 'pdf':
			if ($new) {
				$result = $adb->query("SELECT COUNT(*) as count, module FROM {$table_prefix}_pdfmaker GROUP BY module ");
				if ($result && $adb->num_rows($result) > 0) {
					while($row=$adb->fetchByAssoc($result)) {
						$count = $row['count'];
						if ($params['module'] == $row['module']) {
							$count++;
						}
						if ($params['old_module'] == $row['module']) {
							$count--;
						}
						if ($count > $limits[$mode]) {
							break 2;
						}
					}
				}
				return returnCheckFunctionMorphsuit($mode,true,$new);
			} else {
				$result = $adb->query("SELECT COUNT(*) as count, module FROM {$table_prefix}_pdfmaker GROUP BY module HAVING COUNT(*) > ".$limits[$mode]);
				$num_rows = $adb->num_rows($result);
				if ($num_rows == 0) {
					return returnCheckFunctionMorphsuit($mode,true,$new);
				}
			}
			break;
		case 'adv_sharing_rules';	//Impostazioni > Accesso Condiviso Avanzato
			$othermodules = getSharingModuleList();
			if(!empty($othermodules)) {
				foreach($othermodules as $moduleresname) {
					$tmp = getAdvSharingRuleList($moduleresname);
					$count = count($tmp);
					if ($new && $params['module'] == $moduleresname) {
						$count++;
					}
					if ($count > $limits[$mode]) {
						break 2;
					}
				}
			}
			return returnCheckFunctionMorphsuit($mode,true,$new);
			break;
		case 'sharing_rules_user';	//Impostazioni > Utenti > Regole di condivisione basate sul proprietario
			$othermodules = getSharingModuleList(Array('Contacts'));
			if(!empty($othermodules)) {
				$result = $adb->query("SELECT id FROM {$table_prefix}_users WHERE status = 'Active' AND user_name <> 'admin'");
				if ($result) {
					while($row=$adb->fetchByAssoc($result)) {
						foreach($othermodules as $moduleresname) {
							$tmp = getSharingRuleListUser($moduleresname,$row['id']);
							$count = count($tmp);
							if ($new && $params['module'] == $moduleresname) {
								$count++;
							}
							if ($count > $limits[$mode]) {
								break 3;
							}
						}
					}
				}
			}
			return returnCheckFunctionMorphsuit($mode,true,$new);
			break;
	}
	return returnCheckFunctionMorphsuit($mode,false,$new);
}
function goToUpdateMorphsuit($function) {
	header('Location: index.php?module=Morphsuit&action=MorphsuitAjax&file=UpdateMorphsuit&limit_exceeded='.$function);
	die;
}
function checkMorphsuit()
{
	if ($_REQUEST['module'] == 'Morphsuit' || VteSession::get('MorphsuitZombie') === true || (VteSession::get('checkMorphsuit') == 'yes' && VteSession::get('checkUsersMorphsuit') == 'yes')) {
		if (!isFreeVersion()) {
			return true;
		} elseif (	$_REQUEST['module'] == 'Morphsuit' ||
				VteSession::get('MorphsuitZombie') === true || (
						VteSession::getArray(array('checkFunctionMorphsuit', 'roles')) == 'yes' &&
						VteSession::getArray(array('checkFunctionMorphsuit', 'profiles')) == 'yes' &&
						VteSession::getArray(array('checkFunctionMorphsuit', 'pdf')) == 'yes' &&
						VteSession::getArray(array('checkFunctionMorphsuit', 'adv_sharing_rules')) == 'yes' &&
						VteSession::getArray(array('checkFunctionMorphsuit', 'sharing_rules_user')) == 'yes'))
		{
			return true;
		}
	}
	if (itIsTimeToCheck('check')) {
		if (isFreeVersion()) {
			header('Location: index.php?module=Morphsuit&action=MorphsuitAjax&file=RequestMorphsuit');die;
		}
		if (!isMorphsuitActive()) {	//controllo validit� (se � stata manomessa, spostata su un'altra macchina o sono cambiati i settaggi della macchina)
			header('Location: index.php?module=Morphsuit&action=MorphsuitAjax&file=RequestMorphsuit');die;
		}
		if (!checkUsersMorphsuit()) {	//controllo numero utenti
			goToUpdateMorphsuit('users');
		}
		if (isFreeVersion()) {	//controlli blocchi funzionalit�
			$functions = array('roles','profiles','pdf','adv_sharing_rules','sharing_rules_user');
			$limits = getLimitFunctionMorphsuit();
			foreach($functions as $function) {
				if (!checkFunctionMorphsuit($function,false,array(),$limits)) {
					goToUpdateMorphsuit($function);
				}
			}
		}
		itIsTimeToCheck('set');
	}
}
function itIsTimeToCheck($mode) {
	$cache = Cache::getInstance('mIiTtC');
	if ($mode == 'check') {
		$val = $cache->get();
		$val = base64_decode(str_rot13($val));
		if ($cache->getType() == 'session') return true;	// check every time
		if (!empty($val) && time() < $val) {
			return false;
		} else {
			return true;
		}
	} elseif ($mode == 'set') {
		$val = time() + (10 * 24 * 60 * 60);	// check every 10 days
		
		$saved_morphsuit = getSavedMorphsuit();
		$saved_morphsuit = urldecode(trim($saved_morphsuit));
		
		$private_key = substr($saved_morphsuit,0,strpos($saved_morphsuit,'-----'));
		$enc_text = substr($saved_morphsuit,strpos($saved_morphsuit,'-----')+5);
		$saved_morphsuit = @decrypt_morphsuit($private_key,$enc_text);
		if ($saved_morphsuit == '') return false;
		$saved_morphsuit = Zend_Json::decode($saved_morphsuit);
		
		$data_scadenza = $saved_morphsuit['data_scadenza'];
		$data_scadenza = strtotime($data_scadenza);
		
		if ($data_scadenza < $val) {
			$val = $data_scadenza;
		}
		$val = str_rot13(base64_encode($val));
		$cache->set($val);
	} elseif ($mode == 'clear') {
		$cache->clear();
	}
}
//crmv@182677
function setCacheMorphsuitInfo($info) {
	global $adb, $table_prefix;
	if (!empty($info)) {
		$cache = array('id'=>$info['id'],'expiration'=>$info['data_scadenza'],'users'=>$info['numero_utenti']);
		$adb->pquery("update {$table_prefix}_version set license_info = ?",array(Zend_Json::encode($cache)));
	}
}
function getCacheMorphsuitInfo() {
	global $adb, $table_prefix;
	$result = $adb->query("select license_info from {$table_prefix}_version");
	if ($result && $adb->num_rows($result) > 0) {
		$license_info = $adb->query_result_no_html($result,0,'license_info');
		if (!empty($license_info)) {
			return Zend_Json::decode($license_info);
		}
	}
	return false;
}
function getMorphsuitInfo() {
	global $adb, $table_prefix;
	if (vtlib_isModuleActive('Morphsuit')) {
		$license_info = getCacheMorphsuitInfo();
		if (empty($license_info)) {
			$saved_morphsuit = getSavedMorphsuit();
			if ($saved_morphsuit == '') return false;
			$saved_morphsuit = urldecode(trim($saved_morphsuit));
			$private_key = substr($saved_morphsuit,0,strpos($saved_morphsuit,'-----'));
			$enc_text = substr($saved_morphsuit,strpos($saved_morphsuit,'-----')+5);
			$saved_morphsuit = @decrypt_morphsuit($private_key,$enc_text);
			if ($saved_morphsuit == '') return false;
			$license_info = Zend_Json::decode($saved_morphsuit);
			setCacheMorphsuitInfo($license_info);
			$license_info = getCacheMorphsuitInfo();
		}
		if (!empty($license_info)) {
			$result = $adb->query("SELECT id FROM ".$table_prefix."_users WHERE status = 'Active' AND id <> 1"); // crmv@202661
			if ($result) $license_info['activated_users'] = $adb->num_rows($result);
			$license_info['expiration_fulldate'] = CRMVUtils::timestamp($license_info['expiration']);
			//$license_info['url'] = 'http://'; // TODO
			if (empty($license_info['users'])) $license_info['users'] = getTranslatedString('LBL_MORPHSUIT_USER_NUMBER_UNLIMITED','Morphsuit');
			return $license_info;
		}
	}
	return false;
}
function getMorphsuitNo() {
	$info = getMorphsuitInfo();
	if (isset($info['id'])) return $info['id'];
	else return false;
}
//crmv@182677e