<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}
?>

<div class="linkst">
	<div class="inbox crumbsplus">
		<ul class="crumbs">
			<li><a href="<?= $feather->pathFor('adminIndex') ?>"><?php _e('Admin').' '.__('Index') ?></a></li>
			<li><span>»&#160;</span><a href="<?= $feather->pathFor('addBan') ?>"><?php _e('Bans') ?></a></li>
			<li><span>»&#160;</span><strong><?php _e('Results head') ?></strong></li>
		</ul>
		<div class="pagepost">
			<p class="pagelink"><?= $paging_links ?></p>
		</div>
		<div class="clearer"></div>
	</div>
</div>


<div id="bans1" class="blocktable">
	<h2><span><?php _e('Results head') ?></span></h2>
	<div class="box">
		<div class="inbox">
			<table>
			<thead>
				<tr>
					<th class="tcl" scope="col"><?php _e('Results username head') ?></th>
					<th class="tc2" scope="col"><?php _e('Results e-mail head') ?></th>
					<th class="tc3" scope="col"><?php _e('Results IP address head') ?></th>
					<th class="tc4" scope="col"><?php _e('Results expire head') ?></th>
					<th class="tc5" scope="col"><?php _e('Results message head') ?></th>
					<th class="tc6" scope="col"><?php _e('Results banned by head') ?></th>
					<th class="tcr" scope="col"><?php _e('Results actions head') ?></th>
				</tr>
			</thead>
			<tbody>
<?php

        foreach ($ban_data as $cur_ban) {
            ?>
				<tr>
					<td class="tcl"><?= ($cur_ban['username'] != '') ? Utils::escape($cur_ban['username']) : '&#160;' ?></td>
					<td class="tc2"><?= ($cur_ban['email'] != '') ? Utils::escape($cur_ban['email']) : '&#160;' ?></td>
					<td class="tc3"><?= ($cur_ban['ip'] != '') ? Utils::escape($cur_ban['ip']) : '&#160;' ?></td>
					<td class="tc4"><?= $feather->utils->format_time($cur_ban['expire'], true) ?></td>
					<td class="tc5"><?= ($cur_ban['message'] != '') ? Utils::escape($cur_ban['message']) : '&#160;' ?></td>
					<td class="tc6"><?= ($cur_ban['ban_creator_username'] != '') ? '<a href="'.$feather->pathFor('userProfile', ['id' => $cur_ban['ban_creator']]).'">'.Utils::escape($cur_ban['ban_creator_username']).'</a>' : __('Unknown') ?></td>
					<td class="tcr"><?= '<a href="'.$feather->pathFor('editBan', ['id' => $cur_ban['id']]).'">'.__('Edit').'</a> | <a href="'.$feather->pathFor('deleteBan', ['id' => $cur_ban['id']]).'">'.__('Remove').'</a>' ?></td>
				</tr>
<?php

        }
        if (empty($ban_data)) {
            echo "\t\t\t\t".'<tr><td class="tcl" colspan="7">'.__('No match').'</td></tr>'."\n";
        }

?>
			</tbody>
			</table>
		</div>
	</div>
</div>

<div class="linksb">
	<div class="inbox crumbsplus">
		<div class="pagepost">
			<p class="pagelink"><?= $paging_links ?></p>
		</div>
        <ul class="crumbs">
            <li><a href="<?= $feather->pathFor('adminIndex') ?>"><?php _e('Admin').' '.__('Index') ?></a></li>
            <li><span>»&#160;</span><a href="<?= $feather->pathFor('adminBans') ?>"><?php _e('Bans') ?></a></li>
            <li><span>»&#160;</span><strong><?php _e('Results head') ?></strong></li>
        </ul>
		<div class="clearer"></div>
	</div>
</div>
