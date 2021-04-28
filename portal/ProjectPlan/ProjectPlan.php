<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@173271 */


class ProjectPlanModule extends PortalModule {

	protected function prepareDetail($id) {
		$smarty = parent::prepareDetail($id);
		
		// add extra blocks
		$other_blocks = $this->getExtraBlocks($id);
		$smarty->assign('OTHERBLOCKS',$other_blocks);
		
		return $smarty;
	}
	
	protected function getExtraBlocks($id) {
		global $client, $Server_Path;
		
		$customerid = $_SESSION['customer_id'];
		$sessionid = $_SESSION['customer_sessionid'];
		
		$projectid = $id;
		
		$other_blocks = array();
	
		$projecttaskblock = 'ProjectTask';
		$params = array('id' => "$projectid", 'block'=>"$projecttaskblock",'contactid'=>$customerid,'sessionid'=>"$sessionid");
		$result = $client->call('get_project_components', $params, $Server_Path, $Server_Path);

		$other_blocks[getTranslatedString('LBL_PROJECT_TASKS')] = getblock_fieldlistview($result,"$projecttaskblock");
		
		$other_blocks_entries = array();
		$other_blocks_link = array();
		foreach($other_blocks as $other_blocks_laber=>$other_blocks_list){
			$element = count($other_blocks_list['HEADER']);
		}
		$other_blocks[getTranslatedString('LBL_PROJECT_TASKS')]['LAYOUT'] = $element;
		
		foreach($other_blocks as $other_blocks_laber=>$other_blocks_list){
			$other_blocks_header = $other_blocks_list['HEADER'];
			$other_blocks_entries[] = $other_blocks_list['ENTRIES'][0];
			$other_blocks_link[] = $other_blocks_list['LINK'];
		}
		
		$projectmilestoneblock = 'ProjectMilestone';
		$params = array('id' => "$projectid", 'block'=>"$projectmilestoneblock",'contactid'=>$customerid,'sessionid'=>"$sessionid");
		$result = $client->call('get_project_components', $params, $Server_Path, $Server_Path);	
		$other_blocks[getTranslatedString('LBL_PROJECT_MILESTONES')] = getblock_fieldlistview($result,"$projectmilestoneblock");
		
		foreach($other_blocks as $other_blocks_laber=>$other_blocks_list){
			$element = count($other_blocks_list['HEADER']);
		}
		$other_blocks[getTranslatedString('LBL_PROJECT_MILESTONES')]['LAYOUT'] = $element;
		
		$projectticketsblock = 'HelpDesk';
		$params = array('id' => "$projectid", 'block'=>"$projectticketsblock",'contactid'=>$customerid,'sessionid'=>"$sessionid");
		$result = $client->call('get_project_tickets', $params, $Server_Path, $Server_Path);
		
		$other_blocks[getTranslatedString('LBL_PROJECT_TICKETS')] = getblock_fieldlistview($result,"$projectticketsblock");
		
		$projectdocumentsblock = 'Documents';
		$params = array('id' => "$projectid", 'block'=>"$projectdocumentsblock",'contactid'=>$customerid,'sessionid'=>"$sessionid");
		$result = $client->call('get_documents', $params, $Server_Path, $Server_Path);
		$other_blocks[getTranslatedString('LBL_PROJECT_DOCUMENTS')] = getblock_fieldlistview($result,"$projectdocumentsblock");
		
		
		$other_blocks[getTranslatedString('LBL_PROJECT_DOCUMENTS')] = getblock_fieldlistview($result,"$projectdocumentsblock");

		return $other_blocks;
	
	}
 
}