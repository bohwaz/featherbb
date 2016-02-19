<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller;

use FeatherBB\Core\Error;
use FeatherBB\Core\Track;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;
use FeatherBB\Model\Auth;

class Index
{
    public function __construct()
    {
        $this->model = new \FeatherBB\Model\Index();
        load_textdomain('featherbb', Config::get('forum_env')['FEATHER_ROOT'].'featherbb/lang/'.Container::get('user')->language.'/index.mo');
        load_textdomain('featherbb', Config::get('forum_env')['FEATHER_ROOT'].'featherbb/lang/'.Container::get('user')->language.'/misc.mo');
    }

    public function display($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.index.index');
        View::setPageInfo(array(
            'title' => array(Utils::escape(Config::get('forum_settings')['o_board_title'])),
            'active_page' => 'index',
            'is_indexed' => true,
            'index_data' => $this->model->print_categories_forums(),
            'stats' => $this->model->collect_stats(),
            'online'    =>    $this->model->fetch_users_online(),
            'forum_actions'        =>    $this->model->get_forum_actions(),
            'cur_cat'   => 0
        ))->addTemplate('index.php')->display();
    }

    public function rules()
    {
        Container::get('hooks')->fire('controller.index.rules');

        if (Config::get('forum_settings')['o_rules'] == '0' || (Container::get('user')->is_guest && Container::get('user')->g_read_board == '0' && Config::get('forum_settings')['o_regs_allow'] == '0')) {
            throw new Error(__('Bad request'), 404);
        }

        View::setPageInfo(array(
            'title' => array(Utils::escape(Config::get('forum_settings')['o_board_title']), __('Forum rules')),
            'active_page' => 'rules'
            ))->addTemplate('misc/rules.php')->display();
    }

    public function markread()
    {
        Container::get('hooks')->fire('controller.index.markread');

        Auth::set_last_visit(Container::get('user')->id, Container::get('user')->logged);
        // Reset tracked topics
        Track::set_tracked_topics(null);
        return Router::redirect(Router::pathFor('home'), __('Mark read redirect'));
    }
}
