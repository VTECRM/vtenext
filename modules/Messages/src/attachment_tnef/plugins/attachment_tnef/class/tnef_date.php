<?php
/**
 * tnef_date.php
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
 * @version $Id: tnef_date.php 1169 2008-02-23 10:30:29Z tokul $
 */

/**
 * @package plugins
 * @subpackage attachment_tnef
 */
class TnefDate {
    var $year;
    var $month;
    var $day;
    var $hour;
    var $minute;
    var $second;

    /** Constructor */
    function TnefDate() {
    }

    function setTnefBuffer($buffer) {
        $this->year = tnef_geti16($buffer);
        $this->month = tnef_geti16($buffer);
        $this->day = tnef_geti16($buffer);
        $this->hour = tnef_geti16($buffer);
        $this->minute = tnef_geti16($buffer);
        $this->second = tnef_geti16($buffer);
    }

    /**
     * Returns date and time with seconds in YYYY-MM-DD hh:mm:ss format
     * @return string
     */
    function getString() {
        return sprintf("%04d-%02d-%02d %02d:%02d:%02d",
                       $this->year, $this->month, $this->day, $this->hour, $this->minute, $this->second);
    }

    /**
     * Returns date stamp
     * @return integer Date in unixtime
     * @since 0.7.nsm
     */
    function getUnixtime() {
        return mktime($this->hour,$this->minute,$this->second,$this->month,$this->day,$this->year);
    }
}
