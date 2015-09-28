<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Core;

class AdminUtils
{
    protected static $feather;

    public static function generateAdminMenu($page = '')
    {
        self::$feather = \Slim\Slim::getInstance();

        self::$feather->template->setPageInfo(array(
            'page'    =>    $page,
            'menu_items' => self::$feather->hooks->fire('admin.menu', self::load_default_menu()),
            'plugins'    =>    self::adminPluginsMenu(true),
            ), 1
        )->addTemplate('admin/menu.php');
    }

    /**
     * Add plugin options to menu if needed
     */
    public static function adminPluginsMenu($isAdmin = false)
    {
        self::$feather = \Slim\Slim::getInstance();

        $menuItems = [];
        $menuItems = self::$feather->hooks->fire('admin.plugin.menu', $menuItems);

        return $menuItems;
    }

    /**
     * Generate breadcrumbs from an array of name and URLs
     */
    public static function breadcrumbs_admin(array $links)
    {
        foreach ($links as $name => $url) {
            if ($name != '' && $url != '') {
                $tmp[] = '<span><a href="' . $url . '">'.Utils::escape($name).'</a></span>';
            } else {
                $tmp[] = '<span>'.__('Deleted').'</span>';
                return implode(' » ', $tmp);
            }
        }
        return implode(' » ', $tmp);
    }


    /**
     * Fetch admin IDs
     */
    public static function get_admin_ids()
    {
        self::$feather = \Slim\Slim::getInstance();

        if (!self::$feather->cache->isCached('admin_ids')) {
            self::$feather->cache->store('admin_ids', \FeatherBB\Model\Cache::get_admin_ids());
        }

        return self::$feather->cache->retrieve('admin_ids');
    }

    protected static function load_default_menu()
    {
        return array(
            'mod.users' => array('title' => 'Users',
                                 'url' => 'adminUsers'),
            'mod.bans' => array('title' => 'Bans',
                                'url' => 'adminBans'),
            'mod.reports' => array('title' => 'Reports',
                                   'url' => 'adminReports'),
            'board.options' => array('title' => 'Options',
                                     'url' => 'adminOptions'),
            'board.permissions' => array('title' => 'Permissions',
                                         'url' => 'adminPermissions'),
            'board.categories' => array('title' => 'Categories',
                                        'url' => 'adminCategories'),
            'board.forums' => array('title' => 'Forums',
                                    'url' => 'adminForums'),
            'board.groups' => array('title' => 'User groups',
                                    'url' => 'adminGroups'),
            'board.plugins' => array('title' => 'Plugins',
                                     'url' => 'adminPlugins'),
            'board.censoring' => array('title' => 'Censoring',
                                       'url' => 'adminCensoring'),
            'board.parser' => array('title' => 'Parser',
                                    'url' => 'adminParser'),
            'board.maintenance' => array('title' => 'Maintenance',
                                         'url' => 'adminMaintenance'),
         );
    }
}
