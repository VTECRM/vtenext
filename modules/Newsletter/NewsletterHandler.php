<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
class NewsletterHandler extends VTEventHandler {

	function handleEvent($eventName, $data) {
		global $adb, $current_user;
		global $table_prefix;
		
		//crmv@55961 crmv@152990 crmv@181281
		$modObj = CRMEntity::getInstance('Newsletter');
		$module = $data->getModuleName();
		if (array_key_exists($module,$modObj->email_fields)) {
			global $newsletter_unsubscrpt;
			if (!$data->isNew()) {
				if($eventName == 'vte.entity.beforesave') {//crmv@207852
					$columns = $data->getData();
					$newsletter_unsubscrpt = $columns['newsletter_unsubscrpt'];
					$data->newsletter_unsubscrpt = $columns['newsletter_unsubscrpt']; //crmv@121038
					$data->set('newsletter_unsubscrpt','0');
					// get old status
					$email = $columns[$modObj->email_fields[$module]['fieldname']];
					$data->newsletter_unsubscrpt_old = $modObj->receivingNewsletter($email) ? '1' : '0';
				} elseif($eventName == 'vte.entity.aftersave') {//crmv@207852
					$record = $data->getId();
					
					$focus = CRMEntity::getInstance($module);
					$focus->retrieve_entity_info($record,$module);
					$email = $focus->column_fields[$modObj->email_fields[$module]['fieldname']];
					
					//crmv@121038
					if ($data->newsletter_unsubscrpt === 'on' || $data->newsletter_unsubscrpt === '1' || $data->newsletter_unsubscrpt === 1) {
						$mode = 'unlock';
						$changed = ($data->newsletter_unsubscrpt_old == '0');
					} else {
						$mode = 'lock';
						$changed = ($data->newsletter_unsubscrpt_old == '1');
					}
					//crmv@121038e
					
					$modObj->lockReceivingNewsletter($email,$mode);
					// save only if changed
					if ($changed) {
						$modObj->saveUnsubscribeChangelog($record, 'all', $mode == 'lock'); // crmv@151474
					}
				}
			}
		}
		//crmv@55961e crmv@152990e crmv@181281e
		
		if (!($data->focus instanceof Newsletter)) {
			return;
		}

		if($eventName == 'vte.entity.beforesave') {//crmv@207852
			
			$id = $data->getId();
			$module = $data->getModuleName();
			$focus = $data->getData();
	
			$check_refresh_scheduling = false;
			$new_date_scheduled = $focus['date_scheduled'];
			$new_time_scheduled = $focus['time_scheduled'];
			if (!$data->isNew()) {
				$res = $adb->pquery('SELECT date_scheduled,time_scheduled FROM '.$table_prefix.'_newsletter WHERE newsletterid = ?', array($id));//crmv@208173
				$current_date_scheduled = $adb->query_result($res,0,'date_scheduled');
				$current_time_scheduled = $adb->query_result($res,0,'time_scheduled');
				if ($current_date_scheduled != $new_date_scheduled || $current_time_scheduled != $new_time_scheduled)
					$check_refresh_scheduling = true;
			}
			if ($check_refresh_scheduling) {
				$date_scheduled = getValidDBInsertDateValue($new_date_scheduled).' '.$new_time_scheduled;
				$adb->pquery('update tbl_s_newsletter_queue set date_scheduled = ? where newsletterid = ? and status = ? and attempts < ?',array($adb->formatDate($date_scheduled,true),$id,'Scheduled',$data->focus->max_attempts_permitted));
			}
		}
	}
}
?>