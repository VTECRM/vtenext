<?php // $Id: iCalendar_components.php,v 1.8 2005/07/21 22:31:44 defacer Exp $
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('include/utils/utils.php');
require_once('modules/Emails/mail.php');

class iCalendar_component {
    var $name             = NULL;
    var $properties       = NULL;
    var $components       = NULL;
    var $valid_properties = NULL;
    var $valid_components = NULL;

    function __construct() {
        $this->construct();
    }

    function construct() {
        // Initialize the components array
        if(empty($this->components)) {
            $this->components = array();
            foreach($this->valid_components as $name) {
                $this->components[$name] = array();
            }
        }
    }

    function get_name() {
        return $this->name;
    }

    function add_property($name, $value = NULL, $parameters = NULL) {

        // Uppercase first of all
        $name = strtoupper($name);
        // Are we trying to add a valid property?
        $xname = false;
        if(!isset($this->valid_properties[$name])) {
            // If not, is it an x-name as per RFC 2445?
            if(!rfc2445_is_xname($name)) {
                return false;
            }
            // Since this is an xname, all components are supposed to allow this property
            $xname = true;
        }

        // Create a property object of the correct class
        if($xname) {
            $property = new iCalendar_property_x;
            $property->set_name($name);
        }
        else {
            $classname = 'iCalendar_property_'.strtolower(str_replace('-', '_', $name));
            $property = new $classname;
        }
        // If $value is NULL, then this property must define a default value.
        if($value === NULL) {
            $value = $property->default_value();
            if($value === NULL) {
                return false;
            }
        }

        // Set this property's parent component to ourselves, because some
        // properties behave differently according to what component they apply to.
        $property->set_parent_component($this->name);

        // Set parameters before value; this helps with some properties which
        // accept a VALUE parameter, and thus change their default value type.

        // The parameters must be valid according to property specifications
        if(!empty($parameters)) {
            foreach($parameters as $paramname => $paramvalue) {
                if(!$property->set_parameter($paramname, $paramvalue)) {
                    return false;
                }
            }

            // Some parameters interact among themselves (e.g. ENCODING and VALUE)
            // so make sure that after the dust settles, these invariants hold true
            if(!$property->invariant_holds()) {
                return false;
            }
        }

        // $value MUST be valid according to the property data type
        if(!$property->set_value($value)) {
            return false;
        }

        // If this property is restricted to only once, blindly overwrite value
        if(!$xname && $this->valid_properties[$name] & RFC2445_ONCE) {
            $this->properties[$name] = array($property);
        }

        // Otherwise add it to the instance array for this property
        else {
            $this->properties[$name][] = $property;
        }

        // Finally: after all these, does the component invariant hold?
        if(!$this->invariant_holds()) {
            // If not, completely undo the property addition
            array_pop($this->properties[$name]);
            if(empty($this->properties[$name])) {
                unset($this->properties[$name]);
            }
            return false;
        }

        return true;        
        
    }

    function add_component($component) {

        // With the detailed interface, you can add only components with this function
        if(!is_object($component) || !is_subclass_of($component, 'iCalendar_component')) {
            return false;
        }

        $name = $component->get_name();

        // Only valid components as specified by this component are allowed
        if(!in_array($name, $this->valid_components)) {
            return false;
        }

        // Add it
        $this->components[$name][] = $component;

        return true;
    }

    function get_property_list($name) {
    }

    function invariant_holds() {
        return true;
    }

    function is_valid() {
        // If we have any child components, check that they are all valid
        if(!empty($this->components)) {
            foreach($this->components as $component => $instances) {
                foreach($instances as $number => $instance) {
                    if(!$instance->is_valid()) {
                        return false;
                    }
                }
            }
        }
        // Finally, check the valid property list for any mandatory properties
        // that have not been set and do not have a default value
        foreach($this->valid_properties as $property => $propdata) {
            if(($propdata & RFC2445_REQUIRED) && empty($this->properties[$property])) {
                $classname = 'iCalendar_property_'.strtolower(str_replace('-', '_', $property));
                $object    = new $classname;
                if($object->default_value() === NULL) {
                    return false;
                }
                unset($object);
            }
        }

        return true;
    }
    
    function serialize() {
        // Check for validity of the object
        if(!$this->is_valid()) {
            return false;
        }

        // Maybe the object is valid, but there are some required properties that
        // have not been given explicit values. In that case, set them to defaults.
        foreach($this->valid_properties as $property => $propdata) {
            if(($propdata & RFC2445_REQUIRED) && empty($this->properties[$property])) {
                $this->add_property($property);
            }
        }

        // Start tag
        $string = rfc2445_fold('BEGIN:'.$this->name) . RFC2445_CRLF;
        // List of properties
        if(!empty($this->properties)) {
            foreach($this->properties as $name => $properties) {
                foreach($properties as $property) {
                    $string .= $property->serialize();
                }
            }
        }
        // List of components
        if(!empty($this->components)) {
            foreach($this->components as $name => $components) {
                foreach($components as $component) {
                    $string .= $component->serialize();
                }
            }
        }

        // End tag
        $string .= rfc2445_fold('END:'.$this->name) . RFC2445_CRLF;

        return $string;
    }

    function assign_values($activity) {
    	foreach($this->mapping_arr as $key=>$components){
    		if(!is_array($components['component']) && empty($components['function'])){
    			$this->add_property($key,$activity[$components['component']]);
    		} else if(is_array($components['component']) && empty($components['function'])){
    			$component = '';
    			foreach($components['component'] as $comp){
    				if(!empty($component)) $component .= ',';
    				$component .= $activity[$comp];
    			}
    			$this->add_property($key,$component);
    		} else if(!empty($components['function'])){
    			$this->{$components['function']}($activity); //crmv@162303
    		}
    	}
        return true;
    }

	// crmv@68357 - removed useless code
	
}

class iCalendar extends iCalendar_component {
    var $name = 'VCALENDAR';

    function construct() {
        $this->valid_properties = array(
            'CALSCALE'    => RFC2445_OPTIONAL | RFC2445_ONCE,
            'METHOD'      => RFC2445_OPTIONAL | RFC2445_ONCE,
            'PRODID'      => RFC2445_REQUIRED | RFC2445_ONCE,
            'VERSION'     => RFC2445_REQUIRED | RFC2445_ONCE,
            RFC2445_XNAME => RFC2445_OPTIONAL 
        );

        $this->valid_components = array(
            'VEVENT', 'VTODO', 'VTIMEZONE'
            // TODO: add support for the other component types
            //, 'VJOURNAL', 'VFREEBUSY', 'VALARM'
        );
        parent::construct();
    }

}

class iCalendar_event extends iCalendar_component {

    var $name       = 'VEVENT';
    var $properties;
    var $mapping_arr = array(
    	'CLASS'			=>	array('component'=>'visibility','type'=>'string'),
    	'DESCRIPTION'	=>	array('component'=>'description','type'=>'string'),
    	'DTSTART'		=>	array('component'=>array('date_start','time_start'),'function'=>'iCalendar_event_dtstart','type'=>'datetime'),
    	'DTEND'			=>	array('component'=>array('due_date','time_end'),'function'=>'iCalendar_event_dtend','type'=>'datetime'),
    	'DTSTAMP'		=>	array('component'=>array('date_start','time_start'),'function'=>'iCalendar_event_dtstamp','type'=>'datetime'),
    	'LOCATION'		=>	array('component'=>'location','type'=>'string'),
    	'STATUS'		=>	array('component'=>'eventstatus','type'=>'string'),
    	'SUMMARY'		=>	array('component'=>'subject','type'=>'string'),
    	'PRIORITY'		=>	array('component'=>'priority','function'=>'iCalendar_event_priority', 'type'=>'integer'), // crmv@68357
    	'ATTENDEE'		=>	array('component'=>'activityid','function'=>'iCalendar_event_attendee','type'=>'user'),
    	'RESOURCES'		=>	array('component'=>array('location','eventstatus'),'type'=>'string'),
		// crmv@68357
    	'UID'			=>	array('component'=>'ical_uuid','type'=>'string'),
    	'LAST-MODIFIED' =>	array('component'=>'modifiedtime','function'=>'iCalendar_event_modifiedtime', 'type'=>'datetime'),
    	'CREATED'		=>	array('component'=>'createdtime','function'=>'iCalendar_event_createdtime', 'type'=>'datetime'),
    	'ORGANIZER'		=>	array('component'=>'smownerid','function'=>'iCalendar_event_organizer','type'=>'user'),
    	// crmv@68357e
    );
    var $field_mapping_arr = array(
    	'priority'=>'taskpriority'
    );
    
    function construct() {
        
        $this->valid_components = array('VALARM');

        $this->valid_properties = array(
            'CLASS'          => RFC2445_OPTIONAL | RFC2445_ONCE,
            'CREATED'        => RFC2445_OPTIONAL | RFC2445_ONCE,
            'DESCRIPTION'    => RFC2445_OPTIONAL | RFC2445_ONCE,
            // Standard ambiguous here: in 4.6.1 it says that DTSTAMP in optional,
            // while in 4.8.7.2 it says it's REQUIRED. Go with REQUIRED.
            'DTSTAMP'        => RFC2445_REQUIRED | RFC2445_ONCE,
            // Standard ambiguous here: in 4.6.1 it says that DTSTART in optional,
            // while in 4.8.2.4 it says it's REQUIRED. Go with REQUIRED.
            'DTSTART'        => RFC2445_REQUIRED | RFC2445_ONCE,
            'GEO'            => RFC2445_OPTIONAL | RFC2445_ONCE,
            'LAST-MODIFIED'  => RFC2445_OPTIONAL | RFC2445_ONCE,
            'LOCATION'       => RFC2445_OPTIONAL | RFC2445_ONCE,
            'ORGANIZER'      => RFC2445_OPTIONAL | RFC2445_ONCE,
            'PRIORITY'       => RFC2445_OPTIONAL | RFC2445_ONCE,
            'SEQUENCE'       => RFC2445_OPTIONAL | RFC2445_ONCE,
            'STATUS'         => RFC2445_OPTIONAL | RFC2445_ONCE,
            'SUMMARY'        => RFC2445_OPTIONAL | RFC2445_ONCE,
            'TRANSP'         => RFC2445_OPTIONAL | RFC2445_ONCE,
            // Standard ambiguous here: in 4.6.1 it says that UID in optional,
            // while in 4.8.4.7 it says it's REQUIRED. Go with REQUIRED.
            'UID'            => RFC2445_REQUIRED | RFC2445_ONCE,
            'URL'            => RFC2445_OPTIONAL | RFC2445_ONCE,
            'RECURRENCE-ID'  => RFC2445_OPTIONAL | RFC2445_ONCE,
            'DTEND'          => RFC2445_OPTIONAL | RFC2445_ONCE,
            'DURATION'       => RFC2445_OPTIONAL | RFC2445_ONCE,
            'ATTACH'         => RFC2445_OPTIONAL,
            'ATTENDEE'       => RFC2445_OPTIONAL,
            'CATEGORIES'     => RFC2445_OPTIONAL,
            'COMMENT'        => RFC2445_OPTIONAL,
            'CONTACT'        => RFC2445_OPTIONAL,
            'EXDATE'         => RFC2445_OPTIONAL,
            'EXRULE'         => RFC2445_OPTIONAL,
            'REQUEST-STATUS' => RFC2445_OPTIONAL,
            'RELATED-TO'     => RFC2445_OPTIONAL,
            'RESOURCES'      => RFC2445_OPTIONAL,
            'RDATE'          => RFC2445_OPTIONAL,
            'RRULE'          => RFC2445_OPTIONAL,
            RFC2445_XNAME    => RFC2445_OPTIONAL
        );

        parent::construct();
    }
    
    function invariant_holds() {
        // DTEND and DURATION must not appear together
        if(isset($this->properties['DTEND']) && isset($this->properties['DURATION'])) {
            return false;
        }

        
        if(isset($this->properties['DTEND']) && isset($this->properties['DTSTART'])) {
            // DTEND must be later than DTSTART
            // The standard is not clear on how to hande different value types though
            // TODO: handle this correctly even if the value types are different
            if($this->properties['DTEND'][0]->value <= $this->properties['DTSTART'][0]->value) {
                return false;
            }

            // DTEND and DTSTART must have the same value type
            if($this->properties['DTEND'][0]->val_type != $this->properties['DTSTART'][0]->val_type) {
                return false;
            }

        }
        return true;
    }
    
	// crmv@69568
    function iCalendar_event_dtstamp($activity){
    	$components = gmdate('Ymd\THis\Z');
		$this->add_property("DTSTAMP",$components);
    	return true;
    }

   function iCalendar_event_dtstart($activity){
   		list($h,$m) = explode(':',$activity['time_start']);
   		$time = sprintf("%02d:%02d:00", intval($h), intval($m));
   		$components = gmdate('Ymd\THis\Z', strtotime($activity['date_start'].' '.$time));
		$this->add_property("DTSTART",$components);   	
    	return true;
    }

   function iCalendar_event_dtend($activity){
		list($h,$m) = explode(':',$activity['time_end']);
   		$time = sprintf("%02d:%02d:00", intval($h), intval($m));
   		$components = gmdate('Ymd\THis\Z', strtotime($activity['due_date'].' '.$time));
		$this->add_property("DTEND",$components);   	
    	return true;
    }
    // crmv@69568e
    
    // crmv@68357
    function iCalendar_event_priority($activity){
		$map = array(
			'High' => 1,
			'Medium' => 5,
			'Low' => 9
		);
		$value = $map[$activity['priority']];
		if ($value > 0) {
			$this->add_property("PRIORITY",$value);
		}
    	return true;
    }
    
    function iCalendar_event_modifiedtime($activity){
		if (!empty($activity['modifiedtime'])) {
			$components = gmdate('Ymd\THis\Z', strtotime($activity['modifiedtime']));
			$this->add_property("LAST-MODIFIED",$components);
		}
    	return true;
    }
    
    function iCalendar_event_createdtime($activity){
		if (!empty($activity['createdtime'])) {
			$components = gmdate('Ymd\THis\Z', strtotime($activity['createdtime']));
			$this->add_property("CREATED",$components);
		}
    	return true;
    }
    
    function iCalendar_event_organizer($activity) {
		$owner = intval($activity['smownerid']);
		if ($owner > 0) {
			$user_email = getUserEmail($owner);
			if (!empty($user_email)) {
				$organizer = array(
					'CN' => getUserFullName($owner),
				);
				$this->add_property('ORGANIZER','mailto:'.$user_email, $organizer);
			}
		}
		return true;
    }

	function iCalendar_event_attendee($activity){
		global $adb,$table_prefix;
		$partStatus = array(
			0 => 'NEEDS-ACTION',
			1 => 'DECLINED',
			2 => 'ACCEPTED',
		);
		$users_res = $adb->pquery("SELECT inviteeid, partecipation FROM ".$table_prefix."_invitees WHERE activityid=?", array($activity['id']));
		if($adb->num_rows($users_res)>0){
			for($i=0;$i<$adb->num_rows($users_res);$i++){
				$inviteeid = $adb->query_result_no_html($users_res,$i,'inviteeid');
				$part = $adb->query_result_no_html($users_res,$i,'partecipation');
				$user_email = getUserEmail($inviteeid);
				if (!empty($user_email)) {
					$attendee = array(
						'CUTYPE' => 'INDIVIDUAL',
						'ROLE' => 'REQ-PARTICIPANT',
						'CN' => getUserFullName($inviteeid),
						'PARTSTAT' => $partStatus[$part],
						'RSVP' => 'TRUE',
					);
					$this->add_property('ATTENDEE','mailto:'.$user_email, $attendee);
				}
			}
		}
		// now add the contacts
		$users_res = $adb->pquery("SELECT inviteeid, partecipation FROM ".$table_prefix."_invitees_con INNER JOIN {$table_prefix}_crmentity c ON c.crmid = inviteeid WHERE c.deleted = 0 AND activityid=?", array($activity['id']));
		if($adb->num_rows($users_res)>0){
			for($i=0;$i<$adb->num_rows($users_res);$i++){
				$inviteeid = $adb->query_result_no_html($users_res,$i,'inviteeid');
				$part = $adb->query_result_no_html($users_res,$i,'partecipation');
				$contact_email = getContactsEmailId($inviteeid);
				if (!empty($contact_email)) {
					$attendee = array(
						'CUTYPE' => 'INDIVIDUAL',
						'ROLE' => 'REQ-PARTICIPANT',
						'CN' => getContactName($inviteeid), 
						'PARTSTAT' => $partStatus[$part],
						'RSVP' => 'TRUE',
					);
					$this->add_property('ATTENDEE', 'mailto:'.$contact_email, $attendee);
				}
			}
		}
		
    	return true;
	}
	// crmv@68357e
	
}

class iCalendar_todo extends iCalendar_component {
    var $name       = 'VTODO';
    var $properties;
    var $mapping_arr = array(
    	'DESCRIPTION'	=>	array('component'=>'description','type'=>'string'),
    	'DTSTAMP'		=>	array('component'=>array('date_start','time_start'),'function'=>'iCalendar_event_dtstamp','type'=>'datetime'),
    	'DTSTART'		=>	array('component'=>array('date_start','time_start'),'function'=>'iCalendar_event_dtstart','type'=>'datetime'),
    	'DUE'			=>	array('component'=>array('due_date'),'function'=>'iCalendar_event_dtend','type'=>'datetime'),
    	'STATUS'		=>	array('component'=>'status','type'=>'string'),
    	'SUMMARY'		=>	array('component'=>'subject','type'=>'string'),
    	'PRIORITY'		=>	array('component'=>'priority','function'=>'iCalendar_event_priority', 'type'=>'integer'), // crmv@68357
    	'RESOURCES'		=>	array('component'=>array('status'),'type'=>'string'),
		// crmv@68357
    	'UID'			=>	array('component'=>'ical_uuid','type'=>'string'),
    	'LAST-MODIFIED' =>	array('component'=>'modifiedtime','function'=>'iCalendar_event_modifiedtime', 'type'=>'datetime'),
    	'CREATED'		=>	array('component'=>'createdtime','function'=>'iCalendar_event_createdtime', 'type'=>'datetime'),
    	// crmv@68357e
    );
    var $field_mapping_arr = array(
    	'status'=>'taskstatus',
    	'priority'=>'taskpriority'
    );

    function construct() {

        $this->valid_components = array();
        $this->valid_properties = array(
            'CLASS'       => RFC2445_OPTIONAL | RFC2445_ONCE,
            'COMPLETED'   => RFC2445_OPTIONAL | RFC2445_ONCE,
            'CREATED'     => RFC2445_OPTIONAL | RFC2445_ONCE,
            'DESCRIPTION' => RFC2445_OPTIONAL | RFC2445_ONCE,
            'DTSTAMP'     => RFC2445_OPTIONAL | RFC2445_ONCE,
            'DTSTART'     => RFC2445_OPTIONAL | RFC2445_ONCE,
            'GEO'         => RFC2445_OPTIONAL | RFC2445_ONCE,
            'LAST-MODIFIED'    => RFC2445_OPTIONAL | RFC2445_ONCE,
            'LOCATION'    => RFC2445_OPTIONAL | RFC2445_ONCE,
            'ORGANIZER'   => RFC2445_OPTIONAL | RFC2445_ONCE,
            'PERCENT'     => RFC2445_OPTIONAL | RFC2445_ONCE,
            'PRIORITY'    => RFC2445_OPTIONAL | RFC2445_ONCE,
            'RECURID'     => RFC2445_OPTIONAL | RFC2445_ONCE,
            'SEQUENCE'    => RFC2445_OPTIONAL | RFC2445_ONCE,
            'STATUS'      => RFC2445_OPTIONAL | RFC2445_ONCE,
            'SUMMARY'     => RFC2445_OPTIONAL | RFC2445_ONCE,
            'UID'         => RFC2445_OPTIONAL | RFC2445_ONCE,
            'URL'         => RFC2445_OPTIONAL | RFC2445_ONCE,
            'DUE'         => RFC2445_OPTIONAL | RFC2445_ONCE,
            'DURATION'    => RFC2445_OPTIONAL | RFC2445_ONCE,
            'ATTACH'      => RFC2445_OPTIONAL,
            'ATTENDEE'    => RFC2445_OPTIONAL,
            'CATEGORIES'  => RFC2445_OPTIONAL,
            'COMMENT'     => RFC2445_OPTIONAL,
            'CONTACT'     => RFC2445_OPTIONAL,
            'EXDATE'      => RFC2445_OPTIONAL,
            'EXRULE'      => RFC2445_OPTIONAL,
            'RSTATUS'     => RFC2445_OPTIONAL,
            'RELATED'     => RFC2445_OPTIONAL,
            'RESOURCES'   => RFC2445_OPTIONAL,
            'RDATE'       => RFC2445_OPTIONAL,
            'RRULE'       => RFC2445_OPTIONAL,
            'XPROP'       => RFC2445_OPTIONAL
        );

        parent::construct();
        // TODO:
        // either 'due' or 'duration' may appear in  a 'eventprop', but 'due'
        // and 'duration' MUST NOT occur in the same 'eventprop'
    }

    // crmv@69568
    function iCalendar_event_dtstamp($activity){
    	$components = gmdate('Ymd\THis\Z');
		$this->add_property("DTSTAMP",$components);
    	return true;
    }

   function iCalendar_event_dtstart($activity){
   		list($h,$m) = explode(':',$activity['time_start']);
   		$time = sprintf("%02d:%02d:00", intval($h), intval($m));
   		$components = gmdate('Ymd\THis\Z', strtotime($activity['date_start'].' '.$time));
		$this->add_property("DTSTART",$components);   	
    	return true;
    }

   function iCalendar_event_dtend($activity){
   		$time = "00:00:00";
   		$components = gmdate('Ymd\THis\Z', strtotime($activity['due_date'].' '.$time));
		$this->add_property("DUE",$components);   	
    	return true;
    }
    // crmv@69568e
    
    // crmv@68357
    function iCalendar_event_priority($activity){
		$map = array(
			'High' => 1,
			'Medium' => 5,
			'Low' => 9
		);
		$value = $map[$activity['priority']];
		if ($value > 0) {
			$this->add_property("PRIORITY",$value);
		}
    	return true;
    }
    
    function iCalendar_event_modifiedtime($activity){
		if (!empty($activity['modifiedtime'])) {
			$components = gmdate('Ymd\THis\Z', strtotime($activity['modifiedtime']));
			$this->add_property("LAST-MODIFIED",$components);
		}
    	return true;
    }
    
    function iCalendar_event_createdtime($activity){
		if (!empty($activity['createdtime'])) {
			$components = gmdate('Ymd\THis\Z', strtotime($activity['createdtime']));
			$this->add_property("CREATED",$components);
		}
    	return true;
    }
    // crmv@68357
    
}

class iCalendar_journal extends iCalendar_component {
    // TODO: implement
}

class iCalendar_freebusy extends iCalendar_component {
    // TODO: implement
}

class iCalendar_alarm extends iCalendar_component {
    var $name='VALARM';
    var $properties;
    var $mapping_arr = array(
    	'TRIGGER'	=>	array('component'=>'reminder_time', 'function'=>'iCalendar_event_trigger'),
    );
    
    function construct() {

        $this->valid_components = array();
        $this->valid_properties = array(
            'TRIGGER'         => RFC2445_OPTIONAL | RFC2445_ONCE,
            'DESCRIPTION'     => RFC2445_OPTIONAL | RFC2445_ONCE,
            'ACTION'          => RFC2445_OPTIONAL | RFC2445_ONCE,
            'X-WR-ALARMUID'   => RFC2445_OPTIONAL | RFC2445_ONCE,
             RFC2445_XNAME    => RFC2445_OPTIONAL
        );
        
        parent::construct();
    }

   function iCalendar_event_trigger($activity){
    	$reminder_time = $activity['reminder_time'];
    	if($reminder_time>60){ 
    		$reminder_time = round($reminder_time/60);
    		$reminder = $reminder_time.'H';
    	}else { 
    		$reminder = $reminder_time.'M';
    	} 
	    $this->add_property('ACTION', 'DISPLAY');
	    $this->add_property('TRIGGER', 'PT'.$reminder);
	    $this->add_property('DESCRIPTION', 'Reminder');
    	return true;
    }
}

// crmv@68357 - add support for standard and daylight components in timezone
class iCalendar_timezone extends iCalendar_component {
    var $name       = 'VTIMEZONE';
    var $properties;

    function construct() {
        $this->valid_components = array(
			'STANDARD',
			'DAYLIGHT'
        );
        $this->valid_properties = array(
            'TZID'        => RFC2445_REQUIRED | RFC2445_ONCE,
            'LAST-MODIFIED'    => RFC2445_OPTIONAL | RFC2445_ONCE,
            'TZURL'       => RFC2445_OPTIONAL | RFC2445_ONCE,
            'TZOFFSETFROM'   => RFC2445_OPTIONAL | RFC2445_ONCE,
            'TZOFFSETTO'   => RFC2445_OPTIONAL | RFC2445_ONCE,
            'X-PROP'      => RFC2445_OPTIONAL
        );
        
        parent::construct();
    }

}

class iCalendar_timezone_standard extends iCalendar_component {
    var $name       = 'STANDARD';
    var $properties;

    function construct() {
        $this->valid_components = array();
        $this->valid_properties = array(
			'TZOFFSETFROM'   => RFC2445_REQUIRED | RFC2445_ONCE,
            'TZOFFSETTO'   => RFC2445_REQUIRED | RFC2445_ONCE,
            'DTSTART'        => RFC2445_REQUIRED | RFC2445_ONCE,
            'COMMENT'    => RFC2445_OPTIONAL,
            'RDATE'    => RFC2445_OPTIONAL,
            'RRULE'    => RFC2445_OPTIONAL,
            'TZNAME'    => RFC2445_OPTIONAL,
            'X-PROP'      => RFC2445_OPTIONAL
        );
        
        parent::construct();
    }
}

class iCalendar_timezone_daylight extends iCalendar_timezone_standard {
    var $name       = 'DAYLIGHT';

}

// crmv@68357e

// REMINDER: DTEND must be later than DTSTART for all components which support both
// REMINDER: DUE must be later than DTSTART for all components which support both
