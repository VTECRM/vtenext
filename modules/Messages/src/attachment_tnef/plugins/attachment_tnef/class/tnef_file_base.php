<?php
/**
 * tnef_file_base.php
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
 * @version $Id: tnef_file_base.php 1166 2008-02-23 07:18:12Z tokul $
 */

/**
 * @package plugins
 * @subpackage attachment_tnef
 */
class TnefFileBase {
    var $name;
    var $type;
    var $content;
    var $created;
    var $modified;
    var $tnef_debug;

    function TnefFileBase($tnef_debug) {
        $this->name = "Untitled";
        $this->type = "application/octet-stream";
        $this->content = "";
        $this->tnef_debug = $tnef_debug;
    }

    function getName() {
        return $this->name;
    }

    function getType() {
        return $this->type;
    }

    function getSize() {
        return strlen($this->content);
    }

    function &getCreated() {
        return $this->created;
    }

    function &getModified() {
        return $this->modified;
    }

    function getContent() {
        return $this->content;
    }
}
