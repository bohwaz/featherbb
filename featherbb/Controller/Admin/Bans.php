<?php

/**
 * Copyright (C) 2015-2019 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller\Admin;

use FeatherBB\Core\AdminUtils;
use FeatherBB\Core\Error;
use FeatherBB\Core\Interfaces\ForumSettings;
use FeatherBB\Core\Interfaces\Hooks;
use FeatherBB\Core\Interfaces\Input;
use FeatherBB\Core\Interfaces\Lang;
use FeatherBB\Core\Interfaces\User;
use FeatherBB\Core\Interfaces\View;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

class Bans
{
    public function __construct()
    {
        $this->model = new \FeatherBB\Model\Admin\Bans();
        Lang::load('admin/bans');
        Lang::load('admin/common');

        if (!User::can('mod.ban_users')) {
            throw new Error(__('No permission'), '403');
        }
    }

    public function display($req, $res, $args)
    {
        Hooks::fire('controller.admin.bans.display');

        // Display bans
        if (Input::query('find_ban')) {
            $banInfo = $this->model->findBan();

            // Determine the ban offset (based on $_GET['p'])
            $numPages = ceil($banInfo['num_bans'] / 50);

            $p = (!Input::query('p') || Input::query('p') <= 1 || Input::query('p') > $numPages) ? 1 : intval(Input::query('p'));
            $startFrom = 50 * ($p - 1);

            $banData = $this->model->findBan($startFrom);

            View::setPageInfo([
                    'admin_console' => true,
                    'page' => $p,
                    'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('Bans'), __('Results head')],
                    'paging_links' => '<span class="pages-label">' . __('Pages') . ' </span>' . Url::paginateOld($numPages, $p, '?find_ban=&amp;' . implode('&amp;', $banInfo['query_str'])),
                    'ban_data' => $banData['data'],
                ]
            )->addTemplate('@forum/admin/bans/search_ban')->display();
        } else {
            AdminUtils::generateAdminMenu('bans');

            View::setPageInfo([
                    'admin_console' => true,
                    'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('Bans')],
                ]
            )->addTemplate('@forum/admin/bans/admin_bans')->display();
        }
    }

    public function add($req, $res, $args)
    {
        Hooks::fire('controller.admin.bans.add');

        if (Input::post('add_edit_ban')) {
            return $this->model->insertBan();
        }

        AdminUtils::generateAdminMenu('bans');

        $banId = (isset($args['id'])) ? $args['id'] : null;

        View::setPageInfo([
                'admin_console' => true,
                'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('Bans')],
                'ban' => $this->model->addBanInfo($banId),
            ]
        )->addTemplate('@forum/admin/bans/add_ban')->display();
    }

    public function delete($req, $res, $args)
    {
        Hooks::fire('controller.admin.bans.delete');

        // Remove the ban
        return $this->model->removeBan($args['id']);
    }

    public function edit($req, $res, $args)
    {
        Hooks::fire('controller.admin.bans.edit');

        if (Input::post('add_edit_ban')) {
            return $this->model->insertBan();
        }
        AdminUtils::generateAdminMenu('bans');

        View::setPageInfo([
                'admin_console' => true,
                'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('Bans')],
                'ban' => $this->model->editBanInfo($args['id']),
            ]
        )->addTemplate('@forum/admin/bans/add_ban')->display();
    }
}
