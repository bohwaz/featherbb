<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model;

use FeatherBB\Core\Database as DB;

class Auth
{
    public static function load_user($user_id)
    {
        $user_id = (int) $user_id;
        $result['select'] = array('u.*', 'g.*', 'o.logged', 'o.idle');
        $result['where'] = array('u.id' => $user_id);
        $result['join'] = ($user_id == 1) ? Request::getServerParams()['REMOTE_ADDR'] : 'u.id';
        $escape = ($user_id == 1) ? true : false;

        $result = DB::for_table('users')
                    ->table_alias('u')
                    ->select_many($result['select'])
                    ->inner_join('groups', array('u.group_id', '=', 'g.g_id'), 'g')
                    ->left_outer_join('online', array('o.user_id', '=', $result['join']), 'o', $escape)
                    ->where($result['where']);
        $result = $result->find_result_set();

        foreach ($result as $user) {
            return $user;
        }
    }

    public static function delete_online_by_ip($ip)
    {
        $delete_online = DB::for_table('online')->where('ident', $ip);
        $delete_online = Container::get('hooks')->fireDB('delete_online_login', $delete_online);
        return $delete_online->delete_many();
    }

    public static function delete_online_by_id($user_id)
    {
        // Remove user from "users online" list
        $delete_online = DB::for_table('online')->where('user_id', $user_id);
        $delete_online = Container::get('hooks')->fireDB('delete_online_logout', $delete_online);
        return $delete_online->delete_many();
    }

    public static function get_user_from_name($username)
    {
        $user = DB::for_table('users')->where('username', $username);
        $user = Container::get('hooks')->fireDB('find_user_login', $user);
        return $user->find_one();
    }

    public static function get_user_from_email($email)
    {
        $result['select'] = array('id', 'username', 'last_email_sent');
        $result = DB::for_table('users')
            ->select_many($result['select'])
            ->where('email', $email);
        $result = Container::get('hooks')->fireDB('password_forgotten_query', $result);
        return $result->find_one();
    }

    public static function update_group($user_id, $group_id)
    {
        $update_usergroup = DB::for_table('users')->where('id', $user_id)
            ->find_one()
            ->set('group_id', $group_id);
        $update_usergroup = Container::get('hooks')->fireDB('update_usergroup_login', $update_usergroup);
        return $update_usergroup->save();
    }

    public static function set_last_visit($user_id, $last_visit)
    {
        $update_last_visit = DB::for_table('users')->where('id', (int) $user_id)
            ->find_one()
            ->set('last_visit', (int) $last_visit);
        $update_last_visit = Container::get('hooks')->fireDB('update_online_logout', $update_last_visit);
        return $update_last_visit->save();
    }

    public static function set_new_password($pass, $key, $user_id)
    {
        $query['update'] = array(
            'activate_string' => hash($pass),
            'activate_key'    => $key,
            'last_email_sent' => time(),
        );

        $query = DB::for_table('users')
                    ->where('id', $user_id)
                    ->find_one()
                    ->set($query['update']);
        $query = Container::get('hooks')->fireDB('password_forgotten_mail_query', $query);
        return $query->save();
    }

    public static function feather_setcookie($user_id, $password, $expires)
    {
        $cookie_data = array('user_id' => $user_id,
            'password_hash' => hash_hmac('sha1', $password, Config::get('forum_settings')['cookie_seed'].'_password_hash'),
            'expires' => $expires,
            'checksum' => hash_hmac('sha1', $user_id.$expires, Config::get('forum_settings')['cookie_seed'].'_checksum'));
        setcookie(Config::get('forum_settings')['cookie_name'], json_encode($cookie_data), $expires);
    }
}
