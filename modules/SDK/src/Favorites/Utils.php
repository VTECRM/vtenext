<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@26986
function getFavoritesList($userid,$mode='') {
	global $adb, $history_max_viewed,$table_prefix;
	//crmv@33465
	if (!$adb->table_exist('vte_favorites')){
		return Array();
	}
	//crmv@33465e	
	$sql = 'SELECT vte_favorites.* FROM vte_favorites
			INNER JOIN '.$table_prefix.'_crmentity ON '.$table_prefix.'_crmentity.crmid = vte_favorites.crmid
			LEFT JOIN '.$table_prefix.'_tracker ON '.$table_prefix.'_tracker.user_id = vte_favorites.userid AND '.$table_prefix.'_tracker.item_id = vte_favorites.crmid
			WHERE '.$table_prefix.'_crmentity.deleted = 0 AND vte_favorites.userid = '.$userid.'
			ORDER BY COALESCE('.$table_prefix.'_tracker.id,0) DESC';
	if ($mode == 'all') {
		$result = $adb->query($sql);
	} else {
		$result = $adb->limitQuery($sql,0,$history_max_viewed);
	}
	$list = array();
	if ($result && $adb->num_rows($result) > 0) {
		while($row=$adb->fetchByAssoc($result)) {
			$entity_name = getEntityName($row['module'],array($row['crmid']));
			$list[] = array('module'=>$row['module'],'crmid'=>$row['crmid'],'name'=>$entity_name[$row['crmid']]);
		}
	}
	return $list;
}
function getHtmlFavoritesList($userid,$mode='') {
	$list = getFavoritesList($userid,$mode);
	
	// crmv@140887
	$mode = intval($_REQUEST['fastmode']);
	if ($mode) {
		$smarty = new VteSmarty();
		$smarty->assign('FAV_LIST', $list);
		
		$tpl = 'modules/SDK/src/Favorites/FavoriteList.tpl';
		return $smarty->fetch($tpl);
	}
	// crmv@140887e
	
	$list_favorites = '';
	$i = 1;
	foreach ($list as $info) {
		$entityType = getTranslatedString('SINGLE_'.$info['module'],$info['module']);
		if (in_array($entityType,array('','SINGLE_'.$info['module']))) {
			$entityType = getTranslatedString($info['module'],$info['module']);
		}
		$list_favorites .= '<tr>
								<td class="trackerListBullet small" align="center" width="12">'.$i++.'</td>
								<td class="trackerList small">'.$entityType.'</a></td>
								<td class="trackerList small" width="100%"><a href="index.php?module='.$info['module'].'&action=DetailView&record='.$info['crmid'].'">'.$info['name'].'</a></td>
							</tr>';
	}
	return $list_favorites;
}
function setFavorite($userid,$record) {
	global $adb;
	$result = $adb->pquery('SELECT * FROM vte_favorites WHERE userid = ? AND crmid = ?',array($userid,$record));
	if ($result && $adb->num_rows($result) > 0) {
		$adb->pquery('delete from vte_favorites where userid = ? and crmid = ?',array($userid,$record));
		return 'not_favorite';
	} else {
		$adb->pquery('insert into vte_favorites (userid,crmid,module) values (?,?,?)',array($userid,$record,getSalesEntityType($record)));
		return 'favorite';
	}
}
function getFavorite($record) {
	global $adb, $theme, $current_user;
	$result = $adb->pquery('SELECT * FROM vte_favorites WHERE userid = ? AND crmid = ?',array($current_user->id,$record));
	if ($result && $adb->num_rows($result) > 0) {
		return resourcever('favorites_on.png');
	}
	return resourcever('favorites_off.png');
}
function getFavoriteCls($record) {
	global $adb, $theme, $current_user;
	$result = $adb->pquery('SELECT * FROM vte_favorites WHERE userid = ? AND crmid = ?',array($current_user->id,$record));
	if ($result && $adb->num_rows($result) > 0) {
		return 'star';
	}
	return 'star_border';
}
//crmv@26986e
?>