<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace model;
use DB;

class api
{
    protected static $app;

    public static function get_topics($fid = null, $show, $sort)
    {
        self::$app = \Slim\Slim::getInstance();

        $select_show_recent_topics = array('forum' => 'f.forum_name', 't.id', 't.subject', 'author' => 't.poster', 'date' => 't.posted', 'last_post_date' => 'p.posted', 'last_post_author' => 'p.poster', 'last_post' => 'p.message');
        $where_show_recent_topics = array(
            array('fp.read_forum' => 'IS NULL'),
            array('fp.read_forum' => '1')
        );

        $result = \DB::for_table('topics')->table_alias('t')
                        ->select_many($select_show_recent_topics)
                        ->inner_join('posts', array('p.id', '=', 't.last_post_id'), 'p')
                        ->inner_join('forums', array('f.id', '=', 't.forum_id'), 'f')
                        ->left_outer_join('forum_perms', array('fp.forum_id', '=', 't.forum_id'), 'fp')
                        ->left_outer_join('forum_perms', array('fp.group_id', '=', self::$app->user->g_id), null, true)
                        ->where_any_is($where_show_recent_topics)
                        ->where_null('t.moved_to');
        if (!is_null($fid)) {
            $result = $result->where('t.forum_id', $fid);
        }

        if ($sort == 'asc') {
            $result = $result->order_by_asc('p.posted');
        } else {
            $result = $result->order_by_desc('p.posted');
        }

        return $result->limit($show)
                    ->find_array();
    }
}
