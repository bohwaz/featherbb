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
		<h2><span><?php _e('Prune head') ?></span></h2>
		<div class="box">
			<form method="post" action="<?= $feather->pathFor('adminMaintenance') ?>">
				<input type="hidden" name="<?= $csrf_key; ?>" value="<?= $csrf_token; ?>">
				<div class="inform">
					<input type="hidden" name="action" value="prune" />
					<input type="hidden" name="prune_days" value="<?= $prune['days'] ?>" />
					<input type="hidden" name="prune_sticky" value="<?= $prune_sticky ?>" />
					<input type="hidden" name="prune_from" value="<?= $prune_from ?>" />
					<fieldset>
						<legend><?php _e('Confirm prune subhead') ?></legend>
						<div class="infldset">
							<p><?php printf(__('Confirm prune info'), $prune['days'], $prune['forum'], Utils::forum_number_format($prune['num_topics'])) ?></p>
							<p class="warntext"><?php _e('Confirm prune warn') ?></p>
						</div>
					</fieldset>
				</div>
				<p class="buttons"><input type="submit" name="prune_comply" value="<?php _e('Prune') ?>" /><a href="javascript:history.go(-1)"><?php _e('Go back') ?></a></p>
			</form>
		</div>
	</div>
	<div class="clearer"></div>
</div>