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
    const DEFAULT_SHOW = 2;
    const DEFAULT_SORT = 'desc';
    const DEFAULT_OUTPUT = 'json';
    const TTL = 180;

    protected $sort,
              $show,
              $output;

    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'lang/'.$this->feather->user->language.'/common.mo');
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'lang/'.$this->feather->user->language.'/index.mo');

        // Set filtering params
        $get = $this->feather->request->get();
        $this->sort = (!isset($get['sort']) || !in_array($get['sort'], array('asc', 'desc'))) ? self::DEFAULT_SORT : (string) $get['sort'];
        $this->output = (!isset($get['output']) || !in_array($get['output'], array('json'))) ? self::DEFAULT_OUTPUT : (string) $get['output'];
        $this->show = (!isset($get['show']) || $get['show'] < 1) ? self::DEFAULT_SHOW : (int) $get['show'];
        $this->show = (isset($get['all'])) ? null : $this->show;
    }

    public function topics($fid = null)
    {
        $fid = ($fid < 1) ? $fid = null : (int) $fid;

        $cache_id = 'topics:'.$fid.':'.$this->show.':'.$this->sort;

        if (!$this->feather->cache->isCached($cache_id)) {
            $this->feather->cache->store($cache_id, \model\api::get_topics($fid, $this->show, $this->sort), self::TTL);
        }

        $data = $this->feather->cache->retrieve($cache_id);
        
        if (!empty($data)) {
            echo json_encode($data, JSON_PRETTY_PRINT);
        } else {
            echo 'Rien';
        }
    }
}
