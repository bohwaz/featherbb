<?php

/**
 * Copyright (C) 2015-2019 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model;

use FeatherBB\Core\Database as DB;
use FeatherBB\Core\Interfaces\ForumEnv;
use FeatherBB\Core\Interfaces\Hooks;
use FeatherBB\Core\Interfaces\User;
use FeatherBB\Core\Utils;

class Userlist
{
    // Counts the number of user for a specific query
    public function userCount($username, $showGroup)
    {
        // Fetch user count
        $numUsers = DB::table('users')->tableAlias('u')
                        ->whereGt('u.id', 1)
                        ->whereNotEqual('u.group_id', ForumEnv::get('FEATHER_UNVERIFIED'));

        if ($username != '') {
            $numUsers = $numUsers->whereLike('u.username', str_replace('*', '%', $username));
        }
        if ($showGroup > -1) {
            $numUsers = $numUsers->where('u.group_id', $showGroup);
        }

        $numUsers = $numUsers->count('id');

        $numUsers = Hooks::fire('model.userlist.fetch_user_count', $numUsers);

        return $numUsers;
    }

    // Generates the dropdown menu containing groups
    public function dropdownMenu($showGroup)
    {
        $showGroup = Hooks::fire('model.userlist.generate_dropdown_menu_start', $showGroup);

        $dropdownMenu = '';

        $result['select'] = ['g_id', 'g_title'];

        $result = DB::table('groups')
                        ->selectMany($result['select'])
                        ->whereNotEqual('g_id', ForumEnv::get('FEATHER_GUEST'))
                        ->orderBy('g_id');
        $result = Hooks::fireDB('model.userlist.generate_dropdown_menu_query', $result);
        $result = $result->findMany();

        foreach ($result as $curGroup) {
            if ($curGroup['g_id'] == $showGroup) {
                $dropdownMenu .= "\t\t\t\t\t\t\t".'<option value="'.$curGroup['g_id'].'" selected="selected">'.Utils::escape($curGroup['g_title']).'</option>'."\n";
            } else {
                $dropdownMenu .= "\t\t\t\t\t\t\t".'<option value="'.$curGroup['g_id'].'">'.Utils::escape($curGroup['g_title']).'</option>'."\n";
            }
        }

        $dropdownMenu = Hooks::fire('model.userlist.generate_dropdown_menu', $dropdownMenu);

        return $dropdownMenu;
    }

    // Prints the users
    public function printUsers($username, $startFrom, $sortBy, $sortDir, $showGroup)
    {
        $userlistData = [];

        $username = Hooks::fire('model.userlist.print_users_start', $username, $startFrom, $sortBy, $sortDir, $showGroup);

        // Retrieve a list of user IDs, LIMIT is (really) expensive so we only fetch the IDs here then later fetch the remaining data
        $result = DB::table('users')
                    ->select('u.id')
                    ->tableAlias('u')
                    ->whereGt('u.id', 1)
                    ->whereNotEqual('u.group_id', ForumEnv::get('FEATHER_UNVERIFIED'));

        if ($username != '') {
            $result = $result->whereLike('u.username', str_replace('*', '%', $username));
        }
        if ($showGroup > -1) {
            $result = $result->where('u.group_id', $showGroup);
        }

        $result = $result->orderBy($sortBy, $sortDir)
                         ->orderByAsc('u.id')
                         ->limit(User::getPref('disp.topics'))
                         ->offset($startFrom);

        $result = Hooks::fireDB('model.userlist.print_users_query', $result);
        $result = $result->findMany();

        if ($result) {
            $userIds = [];
            foreach ($result as $curUserId) {
                $userIds[] = $curUserId['id'];
            }

            // Grab the users
            $result['select'] = ['u.id', 'u.username', 'u.title', 'u.num_posts', 'u.registered', 'g.g_id', 'g.g_user_title'];

            $result = DB::table('users')
                          ->tableAlias('u')
                          ->selectMany($result['select'])
                          ->leftOuterJoin('groups', ['g.g_id', '=', 'u.group_id'], 'g')
                          ->whereIn('u.id', $userIds)
                          ->orderBy($sortBy, $sortDir)
                          ->orderByAsc('u.id');
            $result = Hooks::fireDB('model.userlist.print_users_grab_query', $result);
            $result = $result->findMany();

            foreach ($result as $userData) {
                $userlistData[] = $userData;
            }
        }

        $userlistData = Hooks::fire('model.userlist.print_users', $userlistData);

        return $userlistData;
    }
}
