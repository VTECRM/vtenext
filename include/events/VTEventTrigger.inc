<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

	require_once("include/events/SqlResultIterator.inc");
	class VTEventTrigger{
		
		/* EventTrigger cache management */
		private static $cache = array();
		private static $cacheLookupType = '';
		
		const CACHE_LOOKUP_TYPE_ALL = 'ALL';
				
		static function initCache($name = false, $force = false) {
			global $adb;
			
			if($name) {
				$names = $name;				
				if(!is_array($names)) $names = array($name);

				$evtinfos = self::getActiveEventInfos($adb, $names);
				
				foreach($evtinfos as $k=>$v) {
					if(!self::isCached($k) || $force) {						
						self::$cache[$forname] = $evtinfos;
					}
				}								
			} else {
				if(!self::isCached() || $force) {
					self::$cache = self::getActiveEventInfos($adb, $name);
					self::$cacheLookupType = self::CACHE_LOOKUP_TYPE_ALL;	
				}
			}			
		}
		static function isCached($name = false) {
			if($name === false) {
				if(self::$cacheLookupType == self::CACHE_LOOKUP_TYPE_ALL) {
					// Was init cache done for ALL earlier?
					return true;
				}
			} else {
				return isset(self::$cache[$name]);
			}
			return false;
		}
		static function clearCache($name = false) {
			if($name === false) {
				self::$cache = array();
				self::$cacheLookupType = '';				
			} else if(self::isCached($name)) {
				unset(self::$cache[$name]);
			}			
		}
		static function lookupCache($name) {
			if(self::isCached($name)) {
				return self::$cache[$name];
			} else if(self::$cacheLookupType == self::CACHE_LOOKUP_TYPE_ALL) {
				return array();
			}			
			return false;
		}
		
		static function getActiveEventInfos($adb, $name = false) {	
			global $table_prefix;		
			$params = array();
			//crmv@fix boolean
			$query = "SELECT * FROM ".$table_prefix."_eventhandlers WHERE is_active=1";
			//crmv@fix boolean end
			if($name !== false) {
				if(is_array($name)) {
					$query .= " AND event_name IN (" . generateQuestionMarks($name) . ")";
				} else {
					$query .= " AND event_name = ?";
				}
				$params[] = $name;
			}
			
			$evtinfosbyname = array();
			
			$result= $adb->pquery($query, $params);			
			$it = new SqlResultIterator($adb, $result);
			foreach($it as $row) {
				$evtinfosbyname[$row->event_name][] = array(
					'condition'     => trim($row->cond),
					'handler_class' => $row->handler_class,
					'handler_path'  => $row->handler_path,
					'dependent_on'	=> $row->dependent_on, //crmv@392267
				);
			}
			
			if($name) return $evtinfosbyname[$name];
			else return $evtinfosbyname;
		}
		/** END **/
		
		function __construct($adb, $name){
			$this->name=$name;
			$this->adb = $adb;
		}
		
		function trigger($data){
			$adb = $this->adb;
			
			$eventInfos = self::lookupCache($this->name);
			if($eventInfos === false) {
				$eventInfos = self::getActiveEventInfos($this->adb, $this->name);
			}

			$completedEvents = array();
			if($eventInfos) {
				while(count($eventInfos) > count($completedEvents)) {
					$handlerCounter = 0; // Tracks the number of handlers triggered for the current iteration.
					foreach($eventInfos as $eventInfo){
						$condition = new VTEventCondition($eventInfo['condition']);
						if($condition->test($data)){
							$handler_class = $eventInfo['handler_class'];
							if(in_array($handler_class, $completedEvents)) {
								continue;
							}

							$dependentEventsNotCompleted = false;
							$dependentOn = $eventInfo['dependent_on'];
							$dependentEvents = Zend_Json::decode($dependentOn);
							if (is_array($dependentEvents)) {
								foreach($dependentEvents as $eventHandlerClass) {
									if(!in_array($eventHandlerClass, $completedEvents)) {
										$dependentEventsNotCompleted = true;
									}
								}
							}
							if($dependentEventsNotCompleted) continue;

							require_once($eventInfo['handler_path']);
							$handler = new $handler_class();

							$handler->handleEvent($this->name, $data);
							$completedEvents[] = $handler_class;
							$handlerCounter++;
						}
					}
					if($handlerCounter == 0 && count($eventInfos) > count($completedEvents)) {
						$uncompletedEvents = array();
						foreach($eventInfos as $eventInfo){
							if(!in_array($eventInfo['handler_class'], $completedEvents)) {
								$uncompletedEvents[] = $eventInfo['handler_class'];
							}
						}
						throw new Exception("Deadlock occured for events: ". implode(' , ', $uncompletedEvents));
					}
				}
			}
		}
	}
?>