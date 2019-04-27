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
use FeatherBB\Core\Interfaces\ForumSettings;
use FeatherBB\Core\Interfaces\Hooks;
use FeatherBB\Core\Interfaces\Input;
use FeatherBB\Core\Interfaces\Prefs;
use FeatherBB\Core\Interfaces\Router;
use FeatherBB\Core\Utils;
use FeatherBB\Model\Cache;

class Options
{
    public function update()
    {
        $form = [
            'board_title'            => Utils::trim(Input::post('form_board_title')),
            'board_desc'            => Utils::trim(Input::post('form_board_desc')),
            'base_url'                => Utils::trim(Input::post('form_base_url')),
            // 'default_timezone'        => floatval(Input::post('form_default_timezone')),
            // 'default_dst'            => Input::post('form_default_dst') != '1' ? '0' : '1',
            // 'default_lang'            => Utils::trim(Input::post('form_default_lang')),
            // 'default_style'            => Utils::trim(Input::post('form_default_style')),
            // 'time_format'            => Utils::trim(Input::post('form_time_format')),
            // 'date_format'            => Utils::trim(Input::post('form_date_format')),
            'timeout_visit'            => (intval(Input::post('form_timeout_visit')) > 0) ? intval(Input::post('form_timeout_visit')) : 1,
            'timeout_online'        => (intval(Input::post('form_timeout_online')) > 0) ? intval(Input::post('form_timeout_online')) : 1,
            'show_version'            => Input::post('form_show_version') != '1' ? '0' : '1',
            'show_user_info'        => Input::post('form_show_user_info') != '1' ? '0' : '1',
            'show_post_count'        => Input::post('form_show_post_count') != '1' ? '0' : '1',
            // 'smilies'                => Input::post('form_smilies') != '1' ? '0' : '1',
            // 'smilies_sig'            => Input::post('form_smilies_sig') != '1' ? '0' : '1',
            'make_links'            => Input::post('form_make_links') != '1' ? '0' : '1',
            'topic_review'            => (intval(Input::post('form_topic_review')) >= 0) ? intval(Input::post('form_topic_review')) : 0,
            // 'disp_topics_default'    => intval(Input::post('form_disp_topics_default')),
            // 'disp_posts_default'    => intval(Input::post('form_disp_posts_default')),
            'indent_num_spaces'        => (intval(Input::post('form_indent_num_spaces')) >= 0) ? intval(Input::post('form_indent_num_spaces')) : 0,
            'quote_depth'            => (intval(Input::post('form_quote_depth')) > 0) ? intval(Input::post('form_quote_depth')) : 1,
            'quickpost'                => Input::post('form_quickpost') != '1' ? '0' : '1',
            'users_online'            => Input::post('form_users_online') != '1' ? '0' : '1',
            'censoring'                => Input::post('form_censoring') != '1' ? '0' : '1',
            'signatures'            => Input::post('form_signatures') != '1' ? '0' : '1',
            'show_dot'                => Input::post('form_show_dot') != '1' ? '0' : '1',
            'topic_views'            => Input::post('form_topic_views') != '1' ? '0' : '1',
            'quickjump'                => Input::post('form_quickjump') != '1' ? '0' : '1',
            'gzip'                    => Input::post('form_gzip') != '1' ? '0' : '1',
            'search_all_forums'        => Input::post('form_search_all_forums') != '1' ? '0' : '1',
            'additional_navlinks'    => Utils::trim(Input::post('form_additional_navlinks')),
            'report_method'            => intval(Input::post('form_report_method')),
            'mailing_list'            => Utils::trim(Input::post('form_mailing_list')),
            'avatars'                => Input::post('form_avatars') != '1' ? '0' : '1',
            'avatars_dir'            => Utils::trim(Input::post('form_avatars_dir')),
            'avatars_width'            => (intval(Input::post('form_avatars_width')) > 0) ? intval(Input::post('form_avatars_width')) : 1,
            'avatars_height'        => (intval(Input::post('form_avatars_height')) > 0) ? intval(Input::post('form_avatars_height')) : 1,
            'avatars_size'            => (intval(Input::post('form_avatars_size')) > 0) ? intval(Input::post('form_avatars_size')) : 1,
            'admin_email'            => strtolower(Utils::trim(Input::post('form_admin_email'))),
            'webmaster_email'        => strtolower(Utils::trim(Input::post('form_webmaster_email'))),
            'forum_subscriptions'    => Input::post('form_forum_subscriptions') != '1' ? '0' : '1',
            'topic_subscriptions'    => Input::post('form_topic_subscriptions') != '1' ? '0' : '1',
            'smtp_host'                => Utils::trim(Input::post('form_smtp_host')),
            'smtp_user'                => Utils::trim(Input::post('form_smtp_user')),
            'smtp_ssl'                => Input::post('form_smtp_ssl') != '1' ? '0' : '1',
            'regs_allow'            => Input::post('form_regs_allow') != '1' ? '0' : '1',
            'regs_verify'            => Input::post('form_regs_verify') != '1' ? '0' : '1',
            'regs_report'            => Input::post('form_regs_report') != '1' ? '0' : '1',
            'rules'                    => Input::post('form_rules') != '1' ? '0' : '1',
            'rules_message'            => Utils::trim(Input::post('form_rules_message')),
            // 'default_email_setting'    => intval(Input::post('form_default_email_setting')),
            'announcement'            => Input::post('form_announcement') != '1' ? '0' : '1',
            'announcement_message'    => Utils::trim(Input::post('form_announcement_message')),
            'maintenance'            => Input::post('form_maintenance') != '1' ? '0' : '1',
            'maintenance_message'    => Utils::trim(Input::post('form_maintenance_message')),
        ];

        $prefs = [
            'language'            => Utils::trim(Input::post('form_default_lang')),
            'style'            => Utils::trim(Input::post('form_default_style')),
            'dst'            => Input::post('form_default_dst') != '1' ? '0' : '1',
            'timezone'        => floatval(Input::post('form_default_timezone')),
            'time_format'            => Utils::trim(Input::post('form_time_format')),
            'date_format'            => Utils::trim(Input::post('form_date_format')),
            'show.smilies'                => Input::post('form_smilies') != '1' ? '0' : '1',
            'show.smilies.sig'            => Input::post('form_smilies_sig') != '1' ? '0' : '1',
            'disp.topics'    => intval(Input::post('form_disp_topics_default')),
            'disp.posts'    => intval(Input::post('form_disp_posts_default')),
            'email.setting'    => intval(Input::post('form_default_email_setting')),
        ];

        $form = Hooks::fire('model.admin.options.update_options.form', $form);
        $prefs = Hooks::fire('model.admin.options.update_options.prefs', $prefs);

        if ($form['board_title'] == '') {
            throw new Error(__('Must enter title message'), 400);
        }

        // Make sure base_url doesn't end with a slash
        if (substr($form['base_url'], -1) == '/') {
            $form['base_url'] = substr($form['base_url'], 0, -1);
        }

        // Convert IDN to Punycode if needed
        if (preg_match('/[^\x00-\x7F]/', $form['base_url'])) {
            if (!function_exists('idn_to_ascii')) {
                throw new Error(__('Base URL problem'), 400);
            } else {
                $form['base_url'] = idn_to_ascii($form['base_url']);
            }
        }

        $languages = \FeatherBB\Core\Lister::getLangs();
        if (!in_array($prefs['language'], $languages)) {
            throw new Error(__('Bad request'), 404);
        }

        $styles = \FeatherBB\Core\Lister::getStyles();
        if (!in_array($prefs['style'], $styles)) {
            throw new Error(__('Bad request'), 404);
        }

        if ($prefs['time_format'] == '') {
            $prefs['time_format'] = 'H:i:s';
        }

        if ($prefs['date_format'] == '') {
            $prefs['date_format'] = 'Y-m-d';
        }

        if (!Email::isValidEmail($form['admin_email'])) {
            throw new Error(__('Invalid e-mail message'), 400);
        }

        if (!Email::isValidEmail($form['webmaster_email'])) {
            throw new Error(__('Invalid webmaster e-mail message'), 400);
        }

        if ($form['mailing_list'] != '') {
            $form['mailing_list'] = strtolower(preg_replace('%\s%S', '', $form['mailing_list']));
        }

        // Make sure avatars_dir doesn't end with a slash
        if (substr($form['avatars_dir'], -1) == '/') {
            $form['avatars_dir'] = substr($form['avatars_dir'], 0, -1);
        }

        if ($form['additional_navlinks'] != '') {
            $form['additional_navlinks'] = Utils::trim(Utils::linebreaks($form['additional_navlinks']));
        }

        // Change or enter a SMTP password
        if (Input::post('form_smtp_change_pass')) {
            $smtpPass1 = Input::post('form_smtp_pass1') ? Utils::trim(Input::post('form_smtp_pass1')) : '';
            $smtpPass2 = Input::post('form_smtp_pass2') ? Utils::trim(Input::post('form_smtp_pass2')) : '';

            if ($smtpPass1 == $smtpPass2) {
                $form['smtp_pass'] = $smtpPass1;
            } else {
                throw new Error(__('SMTP passwords did not match'), 400);
            }
        }

        if ($form['announcement_message'] != '') {
            $form['announcement_message'] = Utils::linebreaks($form['announcement_message']);
        } else {
            $form['announcement_message'] = __('Enter announcement here');
            $form['announcement'] = '0';
        }

        if ($form['rules_message'] != '') {
            $form['rules_message'] = Utils::linebreaks($form['rules_message']);
        } else {
            $form['rules_message'] = __('Enter rules here');
            $form['rules'] = '0';
        }

        if ($form['maintenance_message'] != '') {
            $form['maintenance_message'] = Utils::linebreaks($form['maintenance_message']);
        } else {
            $form['maintenance_message'] = __('Default maintenance message');
            $form['maintenance'] = '0';
        }

        if ($form['timeout_online'] >= $form['timeout_visit']) {
            throw new Error(__('Timeout error message'), 400);
        }

        if ($form['report_method'] < 0 || $form['report_method'] > 2) {
            throw new Error(__('Bad request'), 400);
        }

        // Make sure the number of displayed topics and posts is between 3 and 75
        if ($prefs['disp.topics'] < 3) {
            $prefs['disp.topics'] = 3;
        } elseif ($prefs['disp.topics'] > 75) {
            $prefs['disp.topics'] = 75;
        }

        if ($prefs['disp.posts'] < 3) {
            $prefs['disp.posts'] = 3;
        } elseif ($prefs['disp.posts'] > 75) {
            $prefs['disp.posts'] = 75;
        }

        if ($prefs['email.setting'] < 0 || $prefs['email.setting'] > 2) {
            throw new Error(__('Bad request'), 400);
        }

        foreach ($form as $key => $input) {
            // Only update values that have changed
            if (array_key_exists('o_'.$key, Container::get('forum_settings')) && ForumSettings::get('o_'.$key) != $input) {
                if ($input != '' || is_int($input)) {
                    DB::table('config')->where('conf_name', 'o_'.$key)
                                                               ->updateMany('conf_value', $input);
                } else {
                    DB::table('config')->where('conf_name', 'o_'.$key)
                                                               ->updateManyExpr('conf_value', 'NULL');
                }
            }
        }

        Prefs::set($prefs);

        // Regenerate the config cache
        $config = array_merge(Cache::getConfig(), Cache::getPreferences());
        CacheInterface::store('config', $config);

        return Router::redirect(Router::pathFor('adminOptions'), __('Options updated redirect'));
    }

    public function styles()
    {
        $styles = \FeatherBB\Core\Lister::getStyles();
        $styles = Hooks::fire('model.admin.options.get_styles.styles', $styles);

        $output = '';

        foreach ($styles as $temp) {
            if (ForumSettings::get('style') == $temp) {
                $output .= "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$temp.'" selected="selected">'.str_replace('_', ' ', $temp).'</option>'."\n";
            } else {
                $output .= "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$temp.'">'.str_replace('_', ' ', $temp).'</option>'."\n";
            }
        }

        $output = Hooks::fire('model.admin.options.get_styles.output', $output);
        return $output;
    }

    public function languages()
    {
        $langs = \FeatherBB\Core\Lister::getLangs();
        $langs = Hooks::fire('model.admin.options.get_langs.langs', $langs);

        $output = '';

        foreach ($langs as $temp) {
            if (ForumSettings::get('language') == $temp) {
                $output .= "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$temp.'" selected="selected">'.str_replace('_', ' ', $temp).'</option>'."\n";
            } else {
                $output .= "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$temp.'">'.str_replace('_', ' ', $temp).'</option>'."\n";
            }
        }

        $output = Hooks::fire('model.admin.options.get_langs.output', $output);
        return $output;
    }

    public function times()
    {
        $times = [5, 15, 30, 60];
        $times = Hooks::fire('model.admin.options.get_times.times', $times);

        $output = '';

        foreach ($times as $time) {
            $output .= "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$time.'>'.sprintf(__('Minutes'), $time).'</option>'."\n";
        }

        $output = Hooks::fire('model.admin.options.get_times.output', $output);
        return $output;
    }
}
