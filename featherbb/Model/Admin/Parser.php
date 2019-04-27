<?php

/**
 * Copyright (C) 2015-2019 FeatherBB
 * Parser (C) 2011 Jeff Roberson (jmrware.com)
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model\Admin;

use FeatherBB\Core\Interfaces\ForumEnv;
use FeatherBB\Core\Interfaces\Hooks;

class Parser
{
    // Helper public function returns array of smiley image files
    //   stored in the style/img/smilies directory.
    public function getSmileyFiles()
    {
        $imgfiles = [];
        $filelist = scandir(ForumEnv::get('FEATHER_ROOT').'style/img/smilies');
        $filelist = Hooks::fire('model.admin.parser.get_smiley_files.filelist', $filelist);
        foreach ($filelist as $file) {
            if (preg_match('/\.(?:png|gif|jpe?g)$/', $file)) {
                $imgfiles[] = $file;
            }
        }
        $imgfiles = Hooks::fire('model.admin.parser.get_smiley_files.imgfiles', $imgfiles);
        return $imgfiles;
    }
}
