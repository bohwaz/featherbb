<?php

/**
 * Copyright (C) 2015-2019 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model\Api;

use FeatherBB\Core\Error;
use FeatherBB\Core\Interfaces\Hooks;

class Forum extends Api
{
    public function display($id)
    {
        $forum = new \FeatherBB\Model\Forum();

        Hooks::bind('model.forum.get_info_forum_query', function ($curForum) {
            $curForum = $curForum->select('f.num_posts');
            return $curForum;
        });

        try {
            $data = $forum->getForumInfo($id);
        } catch (Error $e) {
            return $this->errorMessage;
        }

        $data = $data->asArray();

        $data['moderators'] = unserialize($data['moderators']);

        return $data;
    }
}
