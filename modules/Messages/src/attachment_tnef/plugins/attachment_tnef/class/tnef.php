<?php
/**
 * Main class loader
 *
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
 * @version $Id: tnef.php 1174 2008-02-23 18:24:25Z tokul $
 */

/**
 * @package plugins
 * @subpackage attachment_tnef
 * @since 0.7.nsm
 */
class TnefReceiver {
    /**
     * Object contents (use getContent method)
     * @var string
     */
    var $contents = '';
    /**
     * Debug controls
     * @var boolean
     */
    var $tnef_debug = false;

    /** Generic output functions */

    /**
     * Shows part size
     * @return integer size of part
     */
    function getSize() {
        return strlen($this->content);
    }

    /**
     * Outputs part body
     * @return string
     */
    function getContent() {
        return $this->content;
    }

    /** Generic input functions */

    /**
     * Enter description here...
     *
     * @param integer $attribute
     * @param string $value
     * @param integer $length
     */
    function receiveTnefAttribute($attribute, $value, $length) {

    }

    /**
     * Enter description here...
     *
     * @param integer $attr_type
     * @param integer $attr_name
     * @param string $value
     * @param integer $length
     */
    function receiveMapiAttribute($attr_type, $attr_name, $value, $length) {

    }
}

include_once(SM_PATH . 'plugins/attachment_tnef/class/tnef_attachment.php');

include_once(SM_PATH . 'plugins/attachment_tnef/class/tnef_date.php');
include_once(SM_PATH . 'plugins/attachment_tnef/class/tnef_mailinfo.php');

include_once(SM_PATH . 'plugins/attachment_tnef/class/tnef_file_base.php');
include_once(SM_PATH . 'plugins/attachment_tnef/class/tnef_file.php');
include_once(SM_PATH . 'plugins/attachment_tnef/class/tnef_file_rtf.php');

include_once(SM_PATH . 'plugins/attachment_tnef/class/tnef_vcard.php');