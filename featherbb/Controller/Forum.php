<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller;

use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

class Forum
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->model = new \FeatherBB\Model\Forum();
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->feather->user->language.'/forum.mo');
    }

    public function display($fid, $name = null, $page = null)
    {
        // Fetch some informations about the forum
        $cur_forum = $this->model->get_info_forum($fid);

        // Is this a redirect forum? In that case, redirect!
        if ($cur_forum['redirect_url'] != '') {
            header('Location: '.$cur_forum['redirect_url']);
            exit;
        }

        // Sort out who the moderators are and if we are currently a moderator (or an admin)
        $mods_array = ($cur_forum['moderators'] != '') ? unserialize($cur_forum['moderators']) : array();
        $is_admmod = ($this->feather->user->g_id == $this->feather->forum_env['FEATHER_ADMIN'] || ($this->feather->user->g_moderator == '1' && array_key_exists($this->feather->user->username, $mods_array))) ? true : false;

        $sort_by = $this->model->sort_forum_by($cur_forum['sort_by']);

        // Can we or can we not post new topics?
        if (($cur_forum['post_topics'] == '' && $this->feather->user->g_post_topics == '1') || $cur_forum['post_topics'] == '1' || $is_admmod) {
            $post_link = "\t\t\t".'<p class="postlink conr"><a href="'.$this->feather->urlFor('newTopic', ['fid' => $fid]).'">'.__('Post topic').'</a></p>'."\n";
        } else {
            $post_link = '';
        }

        // Determine the topic offset (based on $page)
        $num_pages = ceil($cur_forum['num_topics'] / $this->feather->user->disp_topics);

        $p = (!isset($page) || $page <= 1 || $page > $num_pages) ? 1 : intval($page);
        $start_from = $this->feather->user->disp_topics * ($p - 1);
        $url_forum = Url::url_friendly($cur_forum['forum_name']);

        // Generate paging links
        $paging_links = '<span class="pages-label">'.__('Pages').' </span>'.Url::paginate($num_pages, $p, 'forum/'.$fid.'/'.$url_forum.'/#');

        $forum_actions = $this->model->get_forum_actions($fid, $this->feather->forum_settings['o_forum_subscriptions'], $cur_forum['is_subscribed']);

        $this->feather->template->addAsset('canonical', $this->feather->urlFor('Forum', ['id' => $fid, 'name' => $url_forum]));
        if ($num_pages > 1) {
            if ($p > 1) {
                $this->feather->template->addAsset('prev', $this->feather->urlFor('ForumPaginate', ['id' => $fid, 'name' => $url_forum, 'page' => intval($p-1)]));
            }
            if ($p < $num_pages) {
                $this->feather->template->addAsset('next', $this->feather->urlFor('ForumPaginate', ['id' => $fid, 'name' => $url_forum, 'page' => intval($p+1)]));
            }
        }

        if ($this->feather->forum_settings['o_feed_type'] == '1') {
            $this->feather->template->addAsset('feed', 'extern.php?action=feed&amp;fid='.$fid.'&amp;type=rss', array('title' => __('RSS forum feed')));
        } elseif ($this->feather->forum_settings['o_feed_type'] == '2') {
            $this->feather->template->addAsset('feed', 'extern.php?action=feed&amp;fid='.$fid.'&amp;type=atom', array('title' => __('Atom forum feed')));
        }

        $this->feather->template->setPageInfo(array(
            'title' => array(Utils::escape($this->feather->forum_settings['o_board_title']), Utils::escape($cur_forum['forum_name'])),
            'active_page' => 'Forum',
            'page_number'  =>  $p,
            'paging_links'  =>  $paging_links,
            'is_indexed' => true,
            'id' => $fid,
            'fid' => $fid,
            'forum_data' => $this->model->print_topics($fid, $sort_by, $start_from),
            'cur_forum' => $cur_forum,
            'post_link' => $post_link,
            'start_from' => $start_from,
            'url_forum' => $url_forum,
            'forum_actions' => $forum_actions,
        ))->addTemplate('Forum.php')->display();
    }
}
