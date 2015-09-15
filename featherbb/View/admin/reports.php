<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;
use FeatherBB\Core\AdminUtils;

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}
?>

	<div class="blockform">
		<h2><span><?php _e('New reports head') ?></span></h2>
		<div class="box">
			<form method="post" action="<?= $feather->pathFor('adminReports') ?>">
				<input type="hidden" name="<?= $csrf_key; ?>" value="<?= $csrf_token; ?>">
<?php
if (!empty($report_data)) {
    foreach ($report_data as $report) {
        ?>
				<div class="inform">
					<fieldset>
						<legend><?php printf(__('Report subhead'), $feather->utils->format_time($report['created'])) ?></legend>
						<div class="infldset">
							<table class="aligntop">
								<tr>
									<th scope="row"><?php printf(__('Reported by'), ($report['reporter'] != '') ? '<a href="'.$feather->pathFor('userProfile', ['id' => $report['reported_by']]).'">'.Utils::escape($report['reporter']).'</a>' : __('Deleted user')) ?></th>
									<td class="location">
                                        <?= AdminUtils::breadcrumbs_admin(array(
                                            $report['forum_name'] => $feather->pathFor('Forum', ['id' => $report['forum_id'], 'name' => Url::url_friendly($report['forum_name'])]),
											$report['subject'] => $feather->pathFor('Forum', ['id' => $report['topic_id'], 'name' => Url::url_friendly($report['subject'])]),
											sprintf(__('Post ID'), $report['pid']) => $feather->pathFor('viewPost', ['pid' => $report['pid']]).'#p'.$report['pid']
                                        )); ?>
                                    </td>
								</tr>
								<tr>
									<th scope="row"><?php _e('Reason') ?><div><input type="submit" name="zap_id[<?= $report['id'] ?>]" value="<?php _e('Zap') ?>" /></div></th>
									<td><?= str_replace("\n", '<br />', Utils::escape($report['message'])) ?></td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
<?php

    }
} else {
    ?>
				<div class="inform">
					<fieldset>
						<legend><?php _e('None') ?></legend>
						<div class="infldset">
							<p><?php _e('No new reports') ?></p>
						</div>
					</fieldset>
				</div>
<?php

}

?>
			</form>
		</div>
	</div>

	<div class="blockform block2">
		<h2><span><?php _e('Last 10 head') ?></span></h2>
		<div class="box">
			<div class="fakeform">
<?php

if (!empty($report_zapped_data)) {
    foreach ($report_zapped_data as $report) {
        ?>
				<div class="inform">
					<fieldset>
						<legend><?php printf(__('Zapped subhead'), $feather->utils->format_time($report['zapped']), ($report['zapped_by'] != '') ? '<a href="'.$feather->pathFor('userProfile', ['id' => $report['zapped_by_id']]).'">'.Utils::escape($report['zapped_by']).'</a>' : __('NA')) ?></legend>
						<div class="infldset">
							<table class="aligntop">
								<tr>
									<th scope="row"><?php printf(__('Reported by'), ($report['reporter'] != '') ? '<a href="'.$feather->pathFor('userProfile', ['id' => $report['reported_by']]).'">'.Utils::escape($report['reporter']).'</a>' : __('Deleted user')) ?></th>
									<td class="location">
                                        <?= AdminUtils::breadcrumbs_admin(array(
                                            $report['forum_name'] => $feather->pathFor('Forum', ['id' => $report['forum_id'], 'name' => Url::url_friendly($report['forum_name'])]),
											$report['subject'] => $feather->pathFor('Forum', ['id' => $report['topic_id'], 'name' => Url::url_friendly($report['subject'])]),
											sprintf(__('Post ID'), $report['pid']) => $feather->pathFor('viewPost', ['pid' => $report['pid']]).'#p'.$report['pid']
                                        )) ?>
                                    </td>
								</tr>
								<tr>
									<th scope="row"><?php _e('Reason') ?></th>
									<td><?= str_replace("\n", '<br />', Utils::escape($report['message'])) ?></td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
<?php

    }
} else {
    ?>
				<div class="inform">
					<fieldset>
						<legend><?php _e('None') ?></legend>
						<div class="infldset">
							<p><?php _e('No zapped reports') ?></p>
						</div>
					</fieldset>
				</div>
<?php

}

?>
			</div>
		</div>
	</div>
	<div class="clearer"></div>
</div>
