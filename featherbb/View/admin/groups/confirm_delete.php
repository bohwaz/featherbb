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

	<div class="blockform">
		<h2><span><?php _e('Group delete head') ?></span></h2>
		<div class="box">
			<form method="post" action="<?= $feather->pathFor('deleteGroup', ['id' => $id]) ?>">
				<input type="hidden" name="<?= $csrf_key; ?>" value="<?= $csrf_token; ?>">
				<div class="inform">
				<input type="hidden" name="group_to_delete" value="<?= $feather->pathFor('deleteGroup', ['id' => $id]) ?>" />
					<fieldset>
						<legend><?php _e('Confirm delete subhead') ?></legend>
						<div class="infldset">
							<p><?php printf(__('Confirm delete info'), Utils::escape($group_title)) ?></p>
							<p class="warntext"><?php _e('Confirm delete warn') ?></p>
						</div>
					</fieldset>
				</div>
				<p class="buttons"><input type="submit" name="del_group_comply" value="<?php _e('Delete') ?>" tabindex="1" /><a href="javascript:history.go(-1)" tabindex="2"><?php _e('Go back') ?></a></p>
			</form>
		</div>
	</div>
	<div class="clearer"></div>
</div>
