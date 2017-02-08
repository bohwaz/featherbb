<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model;

use FeatherBB\Core\Database as DB;
use FeatherBB\Core\Random;
use FeatherBB\Core\Utils;
use FeatherBB\Core\Url;
use Firebase\JWT\JWT;

class Auth
{
    public static function loadUser($user_id)
    {
        $user_id = (int) $user_id;
        $result['select'] = ['u.*', 'g.*', 'o.logged', 'o.idle'];
        $result['where'] = ['u.id' => $user_id];
        $result['join'] = ($user_id == 1) ? Utils::getIp() : 'u.id';
        $escape = ($user_id == 1) ? true : false;

        $result = DB::for_table('users')
                    ->table_alias('u')
                    ->select_many($result['select'])
                    ->inner_join('groups', ['u.group_id', '=', 'g.g_id'], 'g')
                    ->left_outer_join('online', ['o.user_id', '=', $result['join']], 'o', $escape)
                    ->where($result['where'])
                    ->find_one();

        return $result;
    }

    public static function deleteOnlineByIP($ip)
    {
        $delete_online = DB::for_table('online')->where('ident', $ip);
        $delete_online = Container::get('hooks')->fireDB('delete_online_login', $delete_online);
        return $delete_online->delete_many();
    }

    public static function deleteOnlineById($user_id)
    {
        // Remove user from "users online" list
        $delete_online = DB::for_table('online')->where('user_id', $user_id);
        $delete_online = Container::get('hooks')->fireDB('delete_online_logout', $delete_online);
        return $delete_online->delete_many();
    }

    public static function getUserFromName($username)
    {
        $user = DB::for_table('users')->where('username', $username);
        $user = Container::get('hooks')->fireDB('find_user_login', $user);
        return $user->find_one();
    }

    public static function getUserFromEmail($email)
    {
        $result['select'] = ['id', 'username', 'last_email_sent'];
        $result = DB::for_table('users')
            ->select_many($result['select'])
            ->where('email', $email);
        $result = Container::get('hooks')->fireDB('password_forgotten_query', $result);
        return $result->find_one();
    }

    public static function updateGroup($user_id, $group_id)
    {
        $update_usergroup = DB::for_table('users')->where('id', $user_id)
            ->find_one()
            ->set('group_id', $group_id);
        $update_usergroup = Container::get('hooks')->fireDB('update_usergroup_login', $update_usergroup);
        return $update_usergroup->save();
    }

    public static function setLastVisit($user_id, $last_visit)
    {
        $update_last_visit = DB::for_table('users')->where('id', (int) $user_id)
            ->find_one()
            ->set('last_visit', (int) $last_visit);
        $update_last_visit = Container::get('hooks')->fireDB('update_online_logout', $update_last_visit);
        return $update_last_visit->save();
    }

    public static function setNewPassword($pass, $key, $user_id)
    {
        $query['update'] = [
            'activate_string' => Utils::passwordHash($pass),
            'activate_key'    => $key,
            'last_email_sent' => time(),
        ];

        $query = DB::for_table('users')
                    ->where('id', $user_id)
                    ->find_one()
                    ->set($query['update']);
        $query = Container::get('hooks')->fireDB('password_forgotten_mail_query', $query);
        return $query->save();
    }

    public static function updatePassword($user_id, $clear_password)
    {
        $query = DB::for_table('users')
            ->where('id', $user_id)
            ->find_one()
            ->set('password', Utils::passwordHash($clear_password));
        $query = Container::get('hooks')->fireDB('update_password_query', $query);
        return $query->save();
    }

    public static function generateJwt($user, $expire)
    {
        $issuedAt   = time();
        $tokenId    = base64_encode(Random::key(32));
        $serverName = Url::baseStatic();

        /*
        * Create the token as an array
        */
        $data = [
            'iat'  => $issuedAt,         // Issued at: time when the token was generated
            'jti'  => $tokenId,          // Json Token Id: an unique identifier for the token
            'iss'  => $serverName,       // Issuer
            'exp'  => $expire,           // Expire after 30 minutes of idle or 14 days if "remember me"
            'data' => [                  // Data related to the signer user
                'userId'   => $user->id, // userid from the users table
                'userName' => $user->username, // User name
            ]
        ];

        /*
        * Extract the key, which is coming from the config file.
        *
        * Generated with base64_encode(openssl_random_pseudo_bytes(64));
        */
        $secretKey = base64_decode(ForumSettings::get('jwt_token'));

        /*
        * Extract the algorithm from the config file too
        */
        $algorithm = ForumSettings::get('jwt_algorithm');

        /*
        * Encode the array to a JWT string.
        * Second parameter is the key to encode the token.
        *
        * The output string can be validated at http://jwt.io/
        */
        $jwt = JWT::encode(
            $data,      //Data to be encoded in the JWT
            $secretKey, // The signing key
            $algorithm  // Algorithm used to sign the token, see https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40#section-3
        );

        return $jwt;
    }

    public static function setCookie($jwt, $expire)
    {
        // Store cookie to client storage
        setcookie(ForumSettings::get('cookie_name'), $jwt, $expire, '/', '', false, true);
    }
}
