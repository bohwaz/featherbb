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
    const DEFAULT_SHOW = 15;
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
        $this->output = (!isset($get['output']) || !in_array($get['output'], array('json', 'atom'))) ? self::DEFAULT_OUTPUT : (string) $get['output'];
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
            switch ($this->output) {
                case 'json':
                    $this->toJson('topics', $data);
                    break;
                case 'atom':
                    $this->toAtom('topics', $data);
                    break;
            }
        } else {
            echo 'Rien';
        }
    }

    protected function toAtom($request_name, array $data)
    {
        //TODO : move to autoloader
        include $this->feather->forum_env['FEATHER_ROOT'].'include/classes/feedwriter/feed.php';
        include $this->feather->forum_env['FEATHER_ROOT'].'include/classes/feedwriter/atom.php';
        include $this->feather->forum_env['FEATHER_ROOT'].'include/classes/feedwriter/item.php';

        $feed = new \FeatherBB\Atom();
        $feed->setTitle($this->feather->forum_settings['o_board_title'].' - '.ucfirst($request_name));
        $feed->setLink($this->feather->request->getUrl().$this->feather->request->getScriptName());
        $feed->setDate(new \DateTime());

        foreach ($data as $item) {
            $feed_item = $feed->createNewItem();
            $feed_item->addElement('forum', $item['forum']);
            $feed_item->setTitle($item['subject']);
            $feed_item->setLink($this->feather->request->getUrl().$this->feather->url->get('topic/'.$item['id'].'/'.$this->feather->url->url_friendly($item['subject'])));
            $feed_item->setDate($item['date']);
            $feed_item->setAuthor($item['author']);
            $feed_item->addElement('last_poster', $item['last_post_author']);
            $feed_item->addElement('last_post_date', date(\DATE_ATOM, $item['last_post_date']));
            $feed_item->addElement('last_post', $item['last_post']);
            $feed->addItem($feed_item);
        }

        $this->feather->response->headers->set('Content-Type', $feed->getMIMEType());
        $this->feather->response->setBody($feed->generateFeed());
    }

    protected function toJson($request_name, array $data)
    {
        $this->feather->response->headers->set('Content-Type', 'application/json');
        $this->feather->response->setBody(json_encode($data));
    }
}
