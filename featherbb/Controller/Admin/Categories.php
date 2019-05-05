<?php

/**
 * Copyright (C) 2015-2019 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher.
 */

namespace FeatherBB\Controller\Admin;

use FeatherBB\Core\AdminUtils;
use FeatherBB\Core\Error;
use FeatherBB\Core\Interfaces\Container;
use FeatherBB\Core\Interfaces\Cache as CacheInterface;
use FeatherBB\Core\Interfaces\ForumSettings;
use FeatherBB\Core\Interfaces\Hooks;
use FeatherBB\Core\Interfaces\Input;
use FeatherBB\Core\Interfaces\Lang;
use FeatherBB\Core\Interfaces\Router;
use FeatherBB\Core\Interfaces\User;
use FeatherBB\Core\Interfaces\View;
use FeatherBB\Core\Utils;
use FeatherBB\Model\Cache;

class Categories
{
    public function __construct()
    {
        $this->model = new \FeatherBB\Model\Admin\Categories();
        Lang::load('admin/categories');
        if (!User::isAdmin()) {
            throw new Error(__('No permission'), '403');
        }
    }

    public function add($req, $res, $args)
    {
        Hooks::fire('controller.admin.categories.add');

        $catName = Utils::trim(Input::post('cat_name'));
        if ($catName == '') {
            return Router::redirect(Router::pathFor('adminCategories'), __('Must enter name message'));
        }

        if ($this->model->addCategory($catName)) {
            return Router::redirect(Router::pathFor('adminCategories'), __('Category added redirect'));
        } else { //TODO, add error message
            return Router::redirect(Router::pathFor('adminCategories'), __('Category added redirect'));
        }
    }

    public function edit($req, $res, $args)
    {
        Hooks::fire('controller.admin.categories.edit');

        if (empty(Input::post('cat'))) {
            throw new Error(__('Bad request'), '400');
        }

        foreach (Input::post('cat') as $catId => $properties) {
            $category = ['id' => (int) $catId,
                              'name' => Utils::escape($properties['name']),
                              'order' => (int) $properties['order'],];
            if ($category['name'] == '') {
                return Router::redirect(Router::pathFor('adminCategories'), __('Must enter name message'));
            }
            $this->model->updateCategory($category);
        }

        // Regenerate the quick jump cache
        CacheInterface::store('quickjump', Cache::quickjump());

        return Router::redirect(Router::pathFor('adminCategories'), __('Categories updated redirect'));
    }

    public function delete($req, $res, $args)
    {
        Hooks::fire('controller.admin.categories.delete');

        $catToDelete = (int) Input::post('cat_to_delete');

        if ($catToDelete < 1) {
            throw new Error(__('Bad request'), '400');
        }

        if (intval(Input::post('disclaimer')) != 1) {
            return Router::redirect(Router::pathFor('adminCategories'), __('Delete category not validated'));
        }

        if ($this->model->deleteCategory($catToDelete)) {
            return Router::redirect(Router::pathFor('adminCategories'), __('Category deleted redirect'));
        } else {
            return Router::redirect(Router::pathFor('adminCategories'), __('Unable to delete category'));
        }
    }

    public function display($req, $res, $args)
    {
        Hooks::fire('controller.admin.categories.display');

        AdminUtils::generateAdminMenu('categories');

        View::setPageInfo([
                'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('Categories')],
                'active_page' => 'admin',
                'admin_console' => true,
                'cat_list' => $this->model->categoryList(),
        ])->addTemplate('@forum/admin/categories')->display();
    }
}
