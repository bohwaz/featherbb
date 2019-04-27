<?php

/**
 * Copyright (C) 2015-2019 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model\Admin;

use FeatherBB\Core\Database as DB;
use FeatherBB\Core\Email;
use FeatherBB\Core\Error;
use FeatherBB\Core\Interfaces\Cache as CacheInterface;
use FeatherBB\Core\Interfaces\Container;
use FeatherBB\Core\Interfaces\ForumEnv;
use FeatherBB\Core\Interfaces\Hooks;
use FeatherBB\Core\Interfaces\Input;
use FeatherBB\Core\Interfaces\Perms;
use FeatherBB\Core\Interfaces\Router;
use FeatherBB\Core\Interfaces\User;
use FeatherBB\Core\Utils;
use FeatherBB\Model\Cache;

class Bans
{
    public function addBanInfo($id = null)
    {
        $ban = [];

        $id = Hooks::fire('model.admin.bans.add_ban_info_start', $id);

        // If the ID of the user to ban was provided through GET (a link from profile.php)
        if (is_numeric($id)) {
            $ban['user_id'] = $id;
            if ($ban['user_id'] < 2) {
                throw new Error(__('Bad request'), 404);
            }

            $selectAddBanInfo = ['group_id', 'username', 'email'];
            $result = DB::table('users')->selectMany($selectAddBanInfo)
                        ->where('id', $ban['user_id']);

            $result = Hooks::fireDB('model.admin.bans.add_ban_info_query', $result);
            $result = $result->findOne();

            if ($result) {
                $groupId = $result['group_id'];
                $ban['ban_user'] = $result['username'];
                $ban['email'] = $result['email'];
            } else {
                throw new Error(__('No user ID message'), 404);
            }
        } else {
            // Otherwise the username is in POST

            $ban['ban_user'] = Utils::trim(Input::post('new_ban_user'));

            if ($ban['ban_user'] != '') {
                $selectAddBanInfo = ['id', 'group_id', 'username', 'email'];
                $result = DB::table('users')->selectMany($selectAddBanInfo)
                    ->where('username', $ban['ban_user'])
                    ->whereGt('id', 1);

                $result = Hooks::fireDB('model.admin.bans.add_ban_info_query', $result);
                $result = $result->findOne();

                if ($result) {
                    $ban['user_id'] = $result['id'];
                    $groupId = $result['group_id'];
                    $ban['ban_user'] = $result['username'];
                    $ban['email'] = $result['email'];
                } else {
                    throw new Error(__('No user message'), 404);
                }
            }
        }

        // Make sure we're not banning an admin or moderator
        if (isset($groupId)) {
            if ($groupId == ForumEnv::get('FEATHER_ADMIN')) {
                throw new Error(sprintf(__('User is admin message'), Utils::escape($ban['ban_user'])), 403);
            }

            $isModeratorGroup = Perms::getGroupPermissions($groupId, 'mod.is_mod');

            if ($isModeratorGroup) {
                throw new Error(sprintf(__('User is mod message'), Utils::escape($ban['ban_user'])), 403);
            }
        }

        // If we have a $ban['user_id'], we can try to find the last known IP of that user
        if (isset($ban['user_id'])) {
            $ban['ip'] = DB::table('posts')->where('poster_id', $ban['user_id'])
                            ->orderByDesc('posted')
                            ->findOneCol('poster_ip');

            if (!$ban['ip']) {
                $ban['ip'] = DB::table('users')->where('id', $ban['user_id'])
                                 ->findOneCol('registration_ip');
            }
        }

        $ban['mode'] = 'add';

        $ban = Hooks::fire('model.admin.bans.add_ban_info', $ban);

        return $ban;
    }

    public function editBanInfo($id)
    {
        $ban = [];

        $id = Hooks::fire('model.admin.bans.edit_ban_info_start', $id);

        $ban['id'] = $id;

        $selectEditBanInfo = ['username', 'ip', 'email', 'message', 'expire'];
        $result = DB::table('bans')->selectMany($selectEditBanInfo)
            ->where('id', $ban['id']);

        $result = Hooks::fireDB('model.admin.bans.edit_ban_info_query', $result);
        $result = $result->findOne();

        if ($result) {
            $ban['ban_user'] = $result['username'];
            $ban['ip'] = $result['ip'];
            $ban['email'] = $result['email'];
            $ban['message'] = $result['message'];
            $ban['expire'] = $result['expire'];
        } else {
            throw new Error(__('Bad request'), 404);
        }

        $diff = (User::getPref('timezone') + User::getPref('dst')) * 3600;
        $ban['expire'] = ($ban['expire'] != '') ? gmdate('Y-m-d', $ban['expire'] + $diff) : '';

        $ban['mode'] = 'edit';

        $ban = Hooks::fire('model.admin.bans.edit_ban_info', $ban);

        return $ban;
    }

    public function insertBan()
    {
        $banUser = Utils::trim(Input::post('ban_user'));
        $banIp = Utils::trim(Input::post('ban_ip'));
        $banEmail = strtolower(Utils::trim(Input::post('ban_email')));
        $banMessage = Utils::trim(Input::post('ban_message'));
        $banExpire = Utils::trim(Input::post('ban_expire'));

        Hooks::fire('model.admin.bans.insert_ban_start', $banUser, $banIp, $banEmail, $banMessage, $banExpire);

        if ($banUser == '' && $banIp == '' && $banEmail == '') {
            throw new Error(__('Must enter message'), 400);
        } elseif (strtolower($banUser) == 'guest') {
            throw new Error(__('Cannot ban guest message'), 400);
        }

        // Make sure we're not banning an admin or moderator
        if (!empty($banUser)) {
            $groupId = DB::table('users')->where('username', $banUser)
                            ->whereGt('id', 1)
                            ->findOneCol('group_id');

            if ($groupId) {
                if ($groupId == ForumEnv::get('FEATHER_ADMIN')) {
                    throw new Error(sprintf(__('User is admin message'), Utils::escape($banUser)), 403);
                }

                $isModeratorGroup = Perms::getGroupPermissions($groupId, 'mod.is_mod');

                if ($isModeratorGroup) {
                    throw new Error(sprintf(__('User is mod message'), Utils::escape($banUser)), 403);
                }
            }
        }

        // Validate IP/IP range (it's overkill, I know)
        if ($banIp != '') {
            $banIp = preg_replace('%\s{2,}%S', ' ', $banIp);
            $addresses = explode(' ', $banIp);
            $addresses = array_map('trim', $addresses);

            for ($i = 0; $i < count($addresses); ++$i) {
                if (strpos($addresses[$i], ':') !== false) {
                    $octets = explode(':', $addresses[$i]);

                    for ($c = 0; $c < count($octets); ++$c) {
                        $octets[$c] = ltrim($octets[$c], "0");

                        if ($c > 7 || (!empty($octets[$c]) && !ctype_xdigit($octets[$c])) || intval($octets[$c], 16) > 65535) {
                            throw new Error(__('Invalid IP message'), 400);
                        }
                    }

                    $curAddress = implode(':', $octets);
                    $addresses[$i] = $curAddress;
                } else {
                    $octets = explode('.', $addresses[$i]);

                    for ($c = 0; $c < count($octets); ++$c) {
                        $octets[$c] = (strlen($octets[$c]) > 1) ? ltrim($octets[$c], "0") : $octets[$c];

                        if ($c > 3 || preg_match('%[^0-9]%', $octets[$c]) || intval($octets[$c]) > 255) {
                            throw new Error(__('Invalid IP message'), 400);
                        }
                    }

                    $curAddress = implode('.', $octets);
                    $addresses[$i] = $curAddress;
                }
            }

            $banIp = implode(' ', $addresses);
        }

        if ($banEmail != '' && !Email::isValidEmail($banEmail)) {
            if (!preg_match('%^[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,63})$%', $banEmail)) {
                throw new Error(__('Invalid e-mail message'), 400);
            }
        }

        if ($banExpire != '' && $banExpire != 'Never') {
            $banExpire = strtotime($banExpire.' GMT');

            if ($banExpire == -1 || !$banExpire) {
                throw new Error(__('Invalid date message').' '.__('Invalid date reasons'), 400);
            }

            $diff = (User::getPref('timezone') + User::getPref('dst')) * 3600;
            $banExpire -= $diff;

            if ($banExpire <= time()) {
                throw new Error(__('Invalid date message').' '.__('Invalid date reasons'), 400);
            }
        } else {
            $banExpire = 'NULL';
        }

        $banUser = ($banUser != '') ? $banUser : 'NULL';
        $banIp = ($banIp != '') ? $banIp : 'NULL';
        $banEmail = ($banEmail != '') ? $banEmail : 'NULL';
        $banMessage = ($banMessage != '') ? $banMessage : 'NULL';

        $insertUpdateBan = [
            'username'  =>  $banUser,
            'ip'        =>  $banIp,
            'email'     =>  $banEmail,
            'message'   =>  $banMessage,
            'expire'    =>  $banExpire,
        ];

        $insertUpdateBan = Hooks::fire('model.admin.bans.insert_ban_data', $insertUpdateBan);

        if (Input::post('mode') == 'add') {
            $insertUpdateBan['ban_creator'] = User::get()->id;

            $result = DB::table('bans')
                ->create()
                ->set($insertUpdateBan)
                ->save();
        } else {
            $result = DB::table('bans')
                ->where('id', Input::post('ban_id'))
                ->findOne()
                ->set($insertUpdateBan)
                ->save();
        }

        // Regenerate the bans cache
        CacheInterface::store('bans', Cache::getBans());

        return Router::redirect(Router::pathFor('adminBans'), __('Ban edited redirect'));
    }

    public function removeBan($banId)
    {
        $banId = Hooks::fire('model.admin.bans.remove_ban', $banId);

        $result = DB::table('bans')->where('id', $banId)
                    ->findOne();
        $result = Hooks::fireDB('model.admin.bans.remove_ban_query', $result);
        $result = $result->delete();

        // Regenerate the bans cache
        CacheInterface::store('bans', Cache::getBans());

        return Router::redirect(Router::pathFor('adminBans'), __('Ban removed redirect'));
    }

    public function findBan($startFrom = false)
    {
        $banInfo = [];

        Hooks::fire('model.admin.bans.find_ban_start');

        // trim() all elements in $form
        $banInfo['conditions'] = $banInfo['query_str'] = [];

        $expireAfter = Input::query('expire_after') ? Utils::trim(Input::query('expire_after')) : '';
        $expireBefore = Input::query('expire_before') ? Utils::trim(Input::query('expire_before')) : '';
        $banInfo['order_by'] = Input::query('order_by') && in_array(Input::query('order_by'), ['username', 'ip', 'email', 'expire']) ? 'b.'.Input::query('order_by') : 'b.username';
        $banInfo['direction'] = Input::query('direction') && Input::query('direction') == 'DESC' ? 'DESC' : 'ASC';

        $banInfo['query_str'][] = 'order_by='.$banInfo['order_by'];
        $banInfo['query_str'][] = 'direction='.$banInfo['direction'];

        // Build the query
        $result = DB::table('bans')->tableAlias('b')
                        ->whereGt('b.id', 0);

        // Try to convert date/time to timestamps
        if ($expireAfter != '') {
            $banInfo['query_str'][] = 'expire_after='.$expireAfter;

            $expireAfter = strtotime($expireAfter);
            if ($expireAfter === false || $expireAfter == -1) {
                throw new Error(__('Invalid date message'), 400);
            }

            $result = $result->whereGt('b.expire', $expireAfter);
        }
        if ($expireBefore != '') {
            $banInfo['query_str'][] = 'expire_before='.$expireBefore;

            $expireBefore = strtotime($expireBefore);
            if ($expireBefore === false || $expireBefore == -1) {
                throw new Error(__('Invalid date message'), 400);
            }

            $result = $result->whereLt('b.expire', $expireBefore);
        }

        if (Input::query('username')) {
            $result = $result->whereLike('b.username', str_replace('*', '%', Input::query('username')));
            $banInfo['query_str'][] = 'username=' . urlencode(Input::query('username'));
        }

        if (Input::query('ip')) {
            $result = $result->whereLike('b.ip', str_replace('*', '%', Input::query('ip')));
            $banInfo['query_str'][] = 'ip=' . urlencode(Input::query('ip'));
        }

        if (Input::query('email')) {
            $result = $result->whereLike('b.email', str_replace('*', '%', Input::query('email')));
            $banInfo['query_str'][] = 'email=' . urlencode(Input::query('email'));
        }

        if (Input::query('message')) {
            $result = $result->whereLike('b.message', str_replace('*', '%', Input::query('message')));
            $banInfo['query_str'][] = 'message=' . urlencode(Input::query('message'));
        }

        // Fetch ban count
        if (is_numeric($startFrom)) {
            $banInfo['data'] = [];
            $selectBans = ['b.id', 'b.username', 'b.ip', 'b.email', 'b.message', 'b.expire', 'b.ban_creator', 'ban_creator_username' => 'u.username'];

            $result = $result->selectMany($selectBans)
                             ->leftOuterJoin('users', ['b.ban_creator', '=', 'u.id'], 'u')
                             ->orderBy($banInfo['order_by'], $banInfo['direction'])
                             ->offset($startFrom)
                             ->limit(50)
                             ->findMany();

            foreach ($result as $curBan) {
                $banInfo['data'][] = $curBan;
            }
        } else {
            $banInfo['num_bans'] = $result->count('id');
        }

        Hooks::fire('model.admin.bans.find_ban', $banInfo);

        return $banInfo;
    }
}
