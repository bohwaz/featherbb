<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model\Admin;

use FeatherBB\Core\Database as DB;

class Forums
{
    public function addForum($cat_id, $forum_name)
    {
        $set_add_forum = ['forum_name' => $forum_name,
                                'cat_id' => $cat_id];

        $set_add_forum = Container::get('hooks')->fire('model.admin.forums.add_forum', $set_add_forum);

        $forum = DB::for_table('forums')
                    ->create()
                    ->set($set_add_forum);
        $forum->save();

        return $forum->id();
    }

    public function updateForum($forum_id, array $forum_data)
    {
        $update_forum = DB::for_table('forums')
                    ->find_one($forum_id)
                    ->set($forum_data);
        $update_forum = Container::get('hooks')->fireDB('model.admin.forums.update_forum_query', $update_forum);
        $update_forum = $update_forum->save();

        return $update_forum;
    }

    public function delete_forum($forum_id)
    {
        $forum_id = Container::get('hooks')->fire('model.admin.forums.delete_forum_start', $forum_id);

        // Prune all posts and topics
        $this->maintenance = new \FeatherBB\Model\Admin\Maintenance();
        $this->maintenance->prune($forum_id, 1, -1);

        // Delete the forum
        $delete_forum = DB::for_table('forums')->find_one($forum_id);
        $delete_forum = Container::get('hooks')->fireDB('model.admin.forums.delete_forum_query', $delete_forum);
        $delete_forum->delete();

        // Delete forum specific group permissions and subscriptions
        $delete_forum_perms = DB::for_table('forum_perms')->where('forum_id', $forum_id);
        $delete_forum_perms = Container::get('hooks')->fireDB('model.admin.forums.delete_forum_perms_query', $delete_forum_perms);
        $delete_forum_perms->delete_many();

        $delete_forum_subs = DB::for_table('forum_subscriptions')->where('forum_id', $forum_id);
        $delete_forum_subs = Container::get('hooks')->fireDB('model.admin.forums.delete_forum_subs_query', $delete_forum_subs);
        $delete_forum_subs->delete_many();

        // Delete orphaned redirect topics
        $orphans = DB::for_table('topics')
                    ->table_alias('t1')
                    ->left_outer_join('topics', ['t1.moved_to', '=', 't2.id'], 't2')
                    ->where_null('t2.id')
                    ->where_not_null('t1.moved_to');
        $orphans = Container::get('hooks')->fireDB('model.admin.forums.delete_orphan_redirect_topics_query', $orphans);
        $orphans = $orphans->find_many();

        if (count($orphans) > 0) {
            $orphans->delete_many();
        }

        return true; // TODO, better error handling
    }

    public function getForumInfo($forum_id)
    {
        $result = DB::for_table('forums')
                    ->where('id', $forum_id);
        $result = Container::get('hooks')->fireDB('model.admin.forums.get_forum_infos', $result);
        $result = $result->find_one();

        return $result;
    }

    public function getForums()
    {
        $forum_data = [];
        $forum_data = Container::get('hooks')->fire('model.admin.forums.get_forums_start', $forum_data);

        $select_get_forums = ['cid' => 'c.id', 'c.cat_name', 'cat_position' => 'c.disp_position', 'fid' => 'f.id', 'f.forum_name', 'forum_position' => 'f.disp_position'];

        $result = DB::for_table('categories')
                    ->table_alias('c')
                    ->select_many($select_get_forums)
                    ->inner_join('forums', ['c.id', '=', 'f.cat_id'], 'f')
                    ->order_by_asc('f.disp_position')
                    ->order_by_asc('c.disp_position');
        $result = Container::get('hooks')->fireDB('model.admin.forums.get_forums_query', $result);
        $result = $result->find_array();

        foreach ($result as $forum) {
            if (!isset($forum_data[$forum['cid']])) {
                $forum_data[$forum['cid']] = ['cat_name' => $forum['cat_name'],
                                                   'cat_position' => $forum['cat_position'],
                                                   'cat_forums' => []];
            }
            $forum_data[$forum['cid']]['cat_forums'][] = ['forum_id' => $forum['fid'],
                                                               'forum_name' => $forum['forum_name'],
                                                               'position' => $forum['forum_position']];
        }

        $forum_data = Container::get('hooks')->fire('model.admin.forums.get_forums', $forum_data);
        return $forum_data;
    }

    public function updatePositions($forum_id, $position)
    {
        Container::get('hooks')->fire('model.admin.forums.update_positions_start', $forum_id, $position);

        return DB::for_table('forums')
                ->find_one($forum_id)
                ->set('disp_position', $position)
                ->save();
    }

    public function getPermissions($forum_id)
    {
        $perm_data = [];
        $forum_id = Container::get('hooks')->fire('model.admin.forums.get_permissions_start', $forum_id);

        $select_permissions = ['g.g_id', 'g.g_title', 'fp.read_forum', 'fp.post_replies', 'fp.post_topics'];

        $permissions = DB::for_table('groups')
                        ->table_alias('g')
                        ->select_many($select_permissions)
                        ->left_outer_join('forum_perms', 'g.g_id=fp.group_id AND fp.forum_id='.$forum_id, 'fp')
                        ->where_not_equal('g.g_id', ForumEnv::get('FEATHER_ADMIN'))
                        ->order_by_asc('g.g_id');
        $permissions = Container::get('hooks')->fireDB('model.admin.forums.get_permissions_query', $permissions);
        $permissions = $permissions->find_many();

        foreach ($permissions as $cur_perm) {
            $group_permissions = Container::get('perms')->getGroupPermissions($cur_perm['g_id']);

            $cur_perm['board.read'] = isset($group_permissions['board.read']);
            $cur_perm['read_forum'] = ($cur_perm['read_forum'] != '0') ? true : false;
            $cur_perm['post_replies'] = ((!isset($group_permissions['topic.reply']) && $cur_perm['post_replies'] == '1') || (isset($group_permissions['topic.reply']) && $cur_perm['post_replies'] != '0')) ? true : false;
            $cur_perm['post_topics'] = ((!isset($group_permissions['topic.post']) && $cur_perm['post_topics'] == '1') || (isset($group_permissions['topic.post']) && $cur_perm['post_topics'] != '0')) ? true : false;

            // Determine if the current settings differ from the default or not
            $cur_perm['read_forum_def'] = ($cur_perm['read_forum'] == '0') ? false : true;
            $cur_perm['post_replies_def'] = (($cur_perm['post_replies'] && !isset($group_permissions['topic.reply'])) || (!$cur_perm['post_replies'] && isset($group_permissions['topic.reply']))) ? false : true;
            $cur_perm['post_topics_def'] = (($cur_perm['post_topics'] && !isset($group_permissions['topic.post'])) || (!$cur_perm['post_topics'] && isset($group_permissions['topic.post']))) ? false : true;

            $perm_data[] = $cur_perm;
        }

        $perm_data = Container::get('hooks')->fire('model.admin.forums.get_permissions', $perm_data);
        return $perm_data;
    }

    public function getDefaultGroupPermissions($fetch_admin = true)
    {
        $perm_data = [];

        $result = DB::for_table('groups')->select('g_id');

        if (!$fetch_admin) {
            $result->where_not_equal('g_id', ForumEnv::get('FEATHER_ADMIN'));
        }

        $result = $result->order_by_asc('g_id');
        $result = Container::get('hooks')->fireDB('model.admin.forums.get_default_group_permissions_query', $result);
        $result = $result->find_array();

        foreach ($result as $cur_perm) {
            $group_permissions = Container::get('perms')->getGroupPermissions($cur_perm['g_id']);
            $cur_perm['board.read'] = $group_permissions['board.read'];
            $cur_perm['topic.reply'] = $group_permissions['topic.reply'];
            $cur_perm['topic.post'] = $group_permissions['topic.post'];

            $perm_data[] = $cur_perm;
        }

        return $perm_data;
    }

    public function updatePermissions(array $permissions_data)
    {
        $permissions_data = Container::get('hooks')->fire('model.admin.forums.update_permissions_start', $permissions_data);

        $permissions = DB::for_table('forum_perms')
                            ->where('forum_id', $permissions_data['forum_id'])
                            ->where('group_id', $permissions_data['group_id'])
                            ->delete_many();

        if ($permissions) {
            return DB::for_table('forum_perms')
                    ->create()
                    ->set($permissions_data)
                    ->save();
        }
    }

    public function deletePermissions($forum_id, $group_id = null)
    {
        $result = DB::for_table('forum_perms')
                    ->where('forum_id', $forum_id);

        if ($group_id) {
            $result->where('group_id', $group_id);
        }

        $result = Container::get('hooks')->fireDB('model.admin.forums.delete_permissions_query', $result);

        return $result->delete_many();
    }
}
