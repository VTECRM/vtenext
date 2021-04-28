<?php
/**
 * tnef_mailinfo.php
 *
 * Copyright (c) 2003 Bernd Wiegmann <bernd@wib-software.de>
 * Copyright (c) 2008 The NaSMail Project
 * This file is part of NaSMail attachment_tnef plugin.
 *
 * This plugin is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This plugin is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * plugin; if not, write to the Free Software Foundation, Inc., 59 Temple
 * Place, Suite 330, Boston, MA  02111-1307  USA
 * @package plugins
 * @subpackage attachment_tnef
 * @version $Id: tnef_mailinfo.php 1166 2008-02-23 07:18:12Z tokul $
 */

/**
 * @package plugins
 * @subpackage attachment_tnef
 */
class TnefMailinfo {
    var $subject;
    var $topic;
    var $from;
    var $from_name;
    var $date_sent;

    function TnefMailinfo() {
    }

    function getTopic() {
        return $this->topic;
    }

    function getSubject() {
        return $this->subject;
    }

    function getFrom() {
        return $this->from;
    }

    function getFromName() {
        return $this->from_name;
    }

    function &getDateSent() {
        return $this->date_sent;
    }

    function receiveTnefAttribute($attribute, $value, $length) {
        switch($attribute) {
        case TNEF_ASUBJECT:
            $this->subject = substr($value, 0, $length - 1);
            break;

        case TNEF_ADATERECEIVED:
            if (!$this->date_sent) {
                $this->date_sent = new TnefDate;
                $this->date_sent->setTnefBuffer($value);
            }

        case TNEF_ADATESENT:
            $this->date_sent = new TnefDate;
            $this->date_sent->setTnefBuffer($value);
        }
    }

    function receiveMapiAttribute($attr_type, $attr_name, $value, $length) {
        switch($attr_name) {
        case TNEF_MAPI_CONVERSATION_TOPIC:
            $this->topic = $value;
            break;

        case TNEF_MAPI_SENT_REP_EMAIL_ADDR:
            $this->from = $value;
            break;

        case TNEF_MAPI_SENT_REP_NAME:
            $this->from_name = $value;
            break;
        }
    }
}
