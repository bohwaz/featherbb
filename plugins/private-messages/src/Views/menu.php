<?php

/**
* Copyright (C) 2015 FeatherBB
* based on code by (C) 2008-2015 FluxBB
* and Rickard Andersson (C) 2002-2008 PunBB
* License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher.
*/
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}

?>

        <div class="linkst">
            <div class="inbox">
                <ul class="crumbs">
                    <li><a href="<?= Url::base() ?>"><?php _e('Index') ?></a></li>
                    <li><span>»&#160;</span><a href="<?= $feather->pathFor('Conversations') ?>"><?= _e('PMs', 'private_messages') ?></a></li>
                    <li><span>»&#160;</span><a href="<?= $feather->pathFor('Conversations', ['id' => $inbox->id]) ?>"><?= Utils::escape($inbox->name) ?></a></li>
                    <li><span>»&#160;</span><strong><?php _e('My conversations', 'private_messages') ?></strong></li>
                    <li class="postlink actions conr"><span><a href="<?= $feather->pathFor('newConversation') ?>"><?php _e('Send message', 'private_messages') ?></a></span></li>
                </ul>
                <div class="pagepost"></div>
                <div class="clearer"></div>
            </div>
        </div>

        <div id="adminconsole" class="block2col">
            <div id="adminmenu" class="blockmenu">
                <h2><span><?php _e('Folders', 'private_messages') ?></span></h2>
                <div class="box">
                    <div class="inbox">
                        <ul>
                            <?php if(!empty($folders)):
                            foreach ($folders as $folder) { ?>
                                <li class="isactive"><a href="<?= $feather->pathFor('Conversations', ['id' => $folder->id]) ?>"><?= Utils::escape($folder->name) ?> (1)</a></li>
                            <?php } endif; ?>
                            <li><a href="<?= $feather->pathFor('adminIndex') ?>"><?php _e('Index') ?></a></li>
                        </ul>
                    </div>
                </div>
                <h2><span><?php _e('Storage', 'private_messages') ?></span></h2>
                <div class="box">
                    <div class="inbox">
                        <ul>
                            <li>Inbox: 0% full</li>
                            <li><div id="pm_bar_style" style="width:0px;"></div></li>
                            <li>Quota: 0 / &infin;</li>
                        </ul>
                    </div>
                </div>
                <br />
                <h2><span><?php _e('Options', 'private_messages') ?></span></h2>
                <div class="box">
                    <div class="inbox">
                        <ul>
                            <li><a href="http://localhost/panther/pms_misc.php?action=blocked">Blocked Users</a></li>
                            <li><a href="http://localhost/panther/pms_misc.php?action=folders">My Folders</a></li>
                        </ul>
                    </div>
                </div>
            </div>
