<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller;

class api
{
    const MAX_DISPLAY = 15;

    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'lang/'.$this->feather->user->language.'/common.mo');
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'lang/'.$this->feather->user->language.'/index.mo');
    }

    public function topics($output = 'json', $show = self::MAX_DISPLAY, $fid = null)
    {
        if (!in_array($output, array('json', 'atom'))) {
            $output = 'json';
        }

        $show = ($show < 1 || $show > 50) ? $show = self::MAX_DISPLAY : (int) $show;
        $fid = ($fid < 1) ? $fid = null : (int) $fid;
        
        $data = \model\api::get_topics($fid, $show);
        if (!empty($data)) {
            echo json_encode($data, JSON_PRETTY_PRINT);
        } else {
            echo 'Rien';
        }
    }
}
