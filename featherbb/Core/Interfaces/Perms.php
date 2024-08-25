<?php
namespace FeatherBB\Core\Interfaces;


class Perms extends SlimSugar
{
    public static function allowGroup($gid = null, $permission = null)
    {
        return static::$slim->getContainer()->get('perms')->allowGroup($gid, $permission);
    }

    public static function denyGroup($gid = null, $permission = null)
    {
        return static::$slim->getContainer()->get('perms')->denyGroup($gid, $permission);
    }

    public static function can($user = null, $permission = null)
    {
        return static::$slim->getContainer()->get('perms')->can($user, $permission);
    }

    public static function getUserPermissions($user = null)
    {
        return static::$slim->getContainer()->get('perms')->getUserPermissions($user);
    }

    public static function getGroupPermissions($groupId = null, $perm = null)
    {
        return static::$slim->getContainer()->get('perms')->getGroupPermissions($groupId, $perm);
    }
}