<?php
/**
 * tnef_file_rtf.php
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
 * @version $Id: tnef_file_rtf.php 1171 2008-02-23 10:56:54Z tokul $
 */

/** */
define("CRTF_UNCOMPRESSED",          0x414c454d);
define("CRTF_COMPRESSED",            0x75465a4c);

/**
 * @package plugins
 * @subpackage attachment_tnef
 */
class TnefFileRTF extends TnefFileBase {
    var $size;

    function TnefFileRTF($tnef_debug, $buffer) {
        $this->TnefFileBase($tnef_debug);
        $this->type = "application/rtf";
        $this->name = "EmbeddedRTF.rtf";

        $this->decode_crtf($buffer);
    }

    function getSize() {
        return $this->size;
    }

    function decode_crtf(&$buffer) {
        $size_compressed = tnef_geti32($buffer);
        $this->size = tnef_geti32($buffer);
        $magic = tnef_geti32($buffer);
        $crc32 = tnef_geti32($buffer);

        if ($this->tnef_debug) {
            tnef_log("CRTF: size comp=$size_compressed, size=$this->size");
        }

        switch ($magic) {
        case CRTF_COMPRESSED:
            $this->uncompress_rtf($buffer);
            break;

        case CRTF_UNCOMPRESSED:
            $this->content = $buffer;
            break;

        default:
            if ($this->tnef_debug) {
                tnef_log("Unknown Compressed RTF Format");
            }
            break;
        }
    }

    /**
     * Uncompresses rtf part
     * @param string $buffer
     */
    function uncompress_rtf(&$buffer) {
        $uncomp = '';
        $in = 0;
        $out = 0;
        $flags = 0;
        $flag_count = 0;

        $preload = "{\\rtf1\\ansi\\mac\\deff0\\deftab720{\\fonttbl;}{\\f0\\fnil \\froman \\fswiss \\fmodern \\fscript \\fdecor MS Sans SerifSymbolArialTimes New RomanCourier{\\colortbl\\red0\\green0\\blue0\n\r\\par \\pard\\plain\\f0\\fs20\\b\\i\\u\\tab\\tx";
        $length_preload = strlen($preload);
        for ($cnt = 0; $cnt < $length_preload; $cnt++) {
            $uncomp.= $preload{$cnt};
            $out++;
        }
        // FIXME: document me
        while ($out < ($this->size + $length_preload)) {
            if (($flag_count++ % 8) == 0) {
                $flags = ord($buffer{$in++});
            } else {
                $flags = $flags >> 1;
            }
            //
            if (($flags & 1) != 0) {
                $offset = ord($buffer{$in++});
                $length = ord($buffer{$in++});
                $offset = ($offset << 4) | ($length >> 4);
                $length = ($length & 0xF) + 2;
                $offset = ((int)($out / 4096)) * 4096 + $offset;
                if ($offset >= $out) {
                    $offset -= 4096;
                }
                $end = $offset + $length;
                while ($offset < $end) {
                    $uncomp.= $uncomp[$offset++];
                    $out++;
                }
            } else {
                $uncomp.= $buffer{$in++};
                $out++;
            }
        }
        // FIXME: why $preload is added and then removed.
        $this->content = substr_replace($uncomp, "", 0, $length_preload);
        $length=strlen($this->content);
        if ($this->tnef_debug) {
            tnef_log("real=$length, est=$this->size out=$out");
        }
    }
}
