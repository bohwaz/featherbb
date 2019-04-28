<?php

/**
 * Copyright (C) 2015-2019 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller;

use FeatherBB\Core\Error;
use FeatherBB\Core\Interfaces\ForumSettings;
use FeatherBB\Core\Interfaces\Hooks;
use FeatherBB\Core\Interfaces\Lang;
use FeatherBB\Core\Interfaces\Router;
use FeatherBB\Core\Interfaces\User;
use FeatherBB\Core\Interfaces\View;
use FeatherBB\Core\Track;
use FeatherBB\Core\Utils;
use FeatherBB\Model\Auth;

class Index
{
    public function __construct()
    {
        $this->model = new \FeatherBB\Model\Index();
        Lang::load('index');
        Lang::load('misc');
    }

    public function display($req, $res, $args)
    {
        Hooks::fire('controller.index.index');
        return View::setPageInfo([
            'title' => [Utils::escape(ForumSettings::get('o_board_title'))],
            'active_page' => 'index',
            'is_indexed' => true,
            'index_data' => $this->model->printCategoriesForums(),
            'stats' => $this->model->stats(),
            'online'    =>    $this->model->usersOnline(),
            'forum_actions'        =>    $this->model->forumActions(),
            'cur_cat'   => 0
        ])->addTemplate('@forum/index')->display();
    }

    public function rules()
    {
        Hooks::fire('controller.index.rules');

        if (ForumSettings::get('o_rules') == 0 || (User::get()->is_guest && !User::can('board.read') && ForumSettings::get('o_regs_allow') == 0)) {
            throw new Error(__('Bad request'), 404);
        }

        return View::setPageInfo([
            'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Forum rules')],
            'active_page' => 'rules'
            ]
        )->addTemplate('@forum/misc/rules')->display();
    }

    public function markread()
    {
        Hooks::fire('controller.index.markread');

        Auth::setLastVisit(User::get()->id, User::get()->logged);
        // Reset tracked topics
        Track::setTrackedTopics(null);
        return Router::redirect(Router::pathFor('home'), __('Mark read redirect'));
    }
}
