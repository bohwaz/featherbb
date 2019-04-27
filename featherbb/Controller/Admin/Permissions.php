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
use FeatherBB\Core\Interfaces\Lang;
use FeatherBB\Core\Interfaces\Request;
use FeatherBB\Core\Interfaces\User;
use FeatherBB\Core\Interfaces\View;
use FeatherBB\Core\Utils;

class Permissions
{
    public function __construct()
    {
        $this->model = new \FeatherBB\Model\Admin\Permissions();
        Lang::load('admin/permissions');
        if (!User::isAdmin()) {
            throw new Error(__('No permission'), '403');
        }
    }

    public function display($req, $res, $args)
    {
        Hooks::fire('controller.admin.permissions.display');

        // Update permissions
        if (Request::isPost()) {
            return $this->model->update();
        }

        AdminUtils::generateAdminMenu('permissions');

        return View::setPageInfo([
                'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('Permissions')],
                'active_page' => 'admin',
                'admin_console' => true,
            ]
        )->addTemplate('@forum/admin/permissions')->display();
    }
}
