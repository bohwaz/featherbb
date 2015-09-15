<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller;

use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;
use FeatherBB\Core\Lister;
use FeatherBB\Core\Random;
use FeatherBB\Middleware\Core;

class Install
{
    protected $supported_dbs = array('mysql' => 'MySQL',
        'pgsql' => 'PostgreSQL',
        'sqlite' => 'SQLite',
        'sqlite3' => 'SQLite3',
    );
    protected $available_langs;
    protected $optional_fields = array('db_user', 'db_pass', 'db_prefix');
    protected $install_lang = 'English';
    protected $default_style = 'FeatherBB';
    protected $config_keys = array('db_type', 'db_host', 'db_name', 'db_user', 'db_pass', 'db_prefix');
    protected $errors = array();

    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->model = new \FeatherBB\Model\Install();
        $this->available_langs = Lister::getLangs();
        $this->feather->template->setStyle('FeatherBB');
    }

    public function run()
    {
        if (!empty($this->feather->request->post('choose_lang'))) {
            if (in_array(Utils::trim($this->feather->request->post('install_lang')), $this->available_langs)) {
                $this->install_lang = $this->feather->request->post('install_lang');
            }
        }
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->install_lang.'/install.mo');

        if ($this->feather->request->isPost() && empty($this->feather->request->post('choose_lang'))) {
            $missing_fields = array();
            $data = array_map(function ($item) {
                return Utils::escape(Utils::trim($item));
            }, $this->feather->request->post('install'));

            foreach ($data as $field => $value) {
                // Handle empty fields
                if (empty($value)) {
                    // If the field is required, or if user and pass are missing even though mysql or pgsql are selected as DB
                    if (!in_array($field, $this->optional_fields) || (in_array($field, array('db_user')) && in_array($data['db_type'], array('mysql', 'pgsql')))) {
                        $missing_fields[] = $field;
                    }
                }
            }

            if (!empty($missing_fields)) {
                $this->errors = 'The following fields are required but are missing : '.implode(', ', $missing_fields);
            } else { // Missing fields, so we don't need to validate the others
                // VALIDATION
                // Make sure base_url doesn't end with a slash
                if (substr($data['base_url'], -1) == '/') {
                    $data['base_url'] = substr($data['base_url'], 0, -1);
                }

                // Validate username and passwords
                if (Utils::strlen($data['username']) < 2) {
                    $this->errors[] = __('Username 1');
                } elseif (Utils::strlen($data['username']) > 25) { // This usually doesn't happen since the form element only accepts 25 characters
                    $this->errors[] = __('Username 2');
                } elseif (!strcasecmp($data['username'], 'Guest')) {
                    $this->errors[] = __('Username 3');
                } elseif (preg_match('%[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}%', $data['username']) || preg_match('%((([0-9A-Fa-f]{1,4}:){7}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}:[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){5}:([0-9A-Fa-f]{1,4}:)?[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){4}:([0-9A-Fa-f]{1,4}:){0,2}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){3}:([0-9A-Fa-f]{1,4}:){0,3}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){2}:([0-9A-Fa-f]{1,4}:){0,4}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(([0-9A-Fa-f]{1,4}:){0,5}:((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(::([0-9A-Fa-f]{1,4}:){0,5}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|([0-9A-Fa-f]{1,4}::([0-9A-Fa-f]{1,4}:){0,5}[0-9A-Fa-f]{1,4})|(::([0-9A-Fa-f]{1,4}:){0,6}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){1,7}:))%', $data['username'])) {
                    $this->errors[] = __('Username 4');
                } elseif ((strpos($data['username'], '[') !== false || strpos($data['username'], ']') !== false) && strpos($data['username'], '\'') !== false && strpos($data['username'], '"') !== false) {
                    $this->errors[] = __('Username 5');
                } elseif (preg_match('%(?:\[/?(?:b|u|i|h|colou?r|quote|code|img|url|email|list)\]|\[(?:code|quote|list)=)%i', $data['username'])) {
                    $this->errors[] = __('Username 6');
                }

                if (Utils::strlen($data['password']) < 6) {
                    $this->errors[] = __('Short password');
                } elseif ($data['password'] != $data['password_conf']) {
                    $this->errors[] = __('Passwords not match');
                }

                // Validate email
                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    $this->errors[] = __('Wrong email');
                }

                // Validate language
                if (!in_array($data['default_lang'], Lister::getLangs())) {
                    $this->errors[] = __('Error default language');
                }

                // Check if the cache directory is writable
                if (!is_writable($this->feather->forum_env['FORUM_CACHE_DIR'])) {
                    $this->errors[] = sprintf(__('Alert cache'), $this->feather->forum_env['FORUM_CACHE_DIR']);
                }

                // Check if default avatar directory is writable
                if (!is_writable($this->feather->forum_env['FEATHER_ROOT'].'style/img/avatars/')) {
                    $this->errors[] = sprintf(__('Alert avatar'), $this->feather->forum_env['FEATHER_ROOT'].'style/img/avatars/');
                }

                // Validate db_prefix if existing
                if (!empty($data['db_prefix']) && ((strlen($data['db_prefix']) > 0 && (!preg_match('%^[a-zA-Z_][a-zA-Z0-9_]*$%', $data['db_prefix']) || strlen($data['db_prefix']) > 40)))) {
                    $this->errors[] = sprintf(__('Table prefix error'), $data['db_prefix']);
                }
            }

            // End validation and check errors
            if (!empty($this->errors)) {
                $this->feather->template->setPageInfo(array(
                    'languages' => $this->available_langs,
                    'supported_dbs' => $this->supported_dbs,
                    'data' => $data,
                    'errors' => $this->errors,
                ))->addTemplate('install.php')->display(false);
            } else {
                $data['default_style'] = $this->default_style;
                $data['avatars'] = in_array(strtolower(@ini_get('file_uploads')), array('on', 'true', '1')) ? 1 : 0;
                $this->create_config($data);
            }
        } else {
            $base_url = str_replace('index.php', '', $this->feather->request->getUrl().$this->feather->request->getRootUri());
            $data = array('title' => __('My FeatherBB Forum'),
                'description' => __('Description'),
                'base_url' => $base_url,
                'default_lang' => $this->install_lang);
            $this->feather->template->setPageInfo(array(
                'languages' => $this->available_langs,
                'supported_dbs' => $this->supported_dbs,
                'data' => $data,
                'alerts' => array(),
            ))->addTemplate('install.php')->display(false);
        }
    }

    public function create_config(array $data)
    {
        // Generate config ...
        $config = array();
        foreach ($data as $key => $value) {
            if (in_array($key, $this->config_keys)) {
                $config[$key] = $value;
            }
        }

        $config = array_merge($config, array('cookie_name' => mb_strtolower($this->feather->forum_env['FORUM_NAME']).'_cookie_'.Random::key(7, false, true),
            'cookie_seed' => Random::key(16, false, true)));

        // ... And write it on disk
        if ($this->write_config($config)) {
            $this->create_db($data);
        }
    }

    public function create_db(array $data)
    {
        Core::init_db($data);

        // Load appropriate language
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$data['default_lang'].'/install.mo');

        // Handle db prefix
        $data['db_prefix'] = (!empty($data['db_prefix'])) ? $data['db_prefix'] : '';

        // Create tables
        foreach ($this->model->get_database_scheme() as $table => $sql) {
            if (!$this->model->create_table($data['db_prefix'].$table, $sql)) {
                // Error handling
                $this->errors[] = 'A problem was encountered while creating table '.$table;
            }
        }

        // Populate group table with default values
        foreach ($this->model->load_default_groups() as $group_name => $group_data) {
            $this->model->add_data('groups', $group_data);
        }
        // Populate user table with default values
        $this->model->add_data('users', $this->model->load_default_user());
        $this->model->add_data('users', $this->model->load_admin_user($data));
        // Populate categories, forums, topics, posts
        $this->model->add_mock_forum($this->model->load_mock_forum_data($data));
        // Store config in DB
        $this->model->save_config($this->load_default_config($data));

        // Handle .htaccess
        if (function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules())) {
            $this->write_htaccess();
        }

        // Install success flash message
        $flash = new \Slim\Middleware\Flash();
        $flash->set('success', __('Message'));
        $flash->save();

        // Redirect to homepage
        Url::redirect($this->feather->pathFor('home'));
    }

    public function write_config($array)
    {
        return file_put_contents($this->feather->forum_env['FORUM_CONFIG_FILE'], '<?php'."\n".'$featherbb_config = '.var_export($array, true).';');
    }

    public function write_htaccess()
    {
        $data = file_get_contents($this->feather->forum_env['FEATHER_ROOT'].'.htaccess.dist');
        return file_put_contents($this->feather->forum_env['FEATHER_ROOT'].'.htaccess', $data);
    }

    public function load_default_config(array $data)
    {
        return array(
            'o_cur_version'                => $this->feather->forum_env['FORUM_VERSION'],
            'o_database_revision'        => $this->feather->forum_env['FORUM_DB_REVISION'],
            'o_searchindex_revision'    => $this->feather->forum_env['FORUM_SI_REVISION'],
            'o_parser_revision'            => $this->feather->forum_env['FORUM_PARSER_REVISION'],
            'o_board_title'                => $data['title'],
            'o_board_desc'                => $data['description'],
            'o_default_timezone'        => 0,
            'o_time_format'                => 'H:i:s',
            'o_date_format'                => 'Y-m-d',
            'o_timeout_visit'            => 1800,
            'o_timeout_online'            => 300,
            'o_redirect_delay'            => 1,
            'o_show_version'            => 0,
            'o_show_user_info'            => 1,
            'o_show_post_count'            => 1,
            'o_signatures'                => 1,
            'o_smilies'                    => 1,
            'o_smilies_sig'                => 1,
            'o_make_links'                => 1,
            'o_default_lang'            => $data['default_lang'],
            'o_default_style'            => $data['default_style'],
            'o_default_user_group'        => 4,
            'o_topic_review'            => 15,
            'o_disp_topics_default'        => 30,
            'o_disp_posts_default'        => 25,
            'o_indent_num_spaces'        => 4,
            'o_quote_depth'                => 3,
            'o_quickpost'                => 1,
            'o_users_online'            => 1,
            'o_censoring'                => 0,
            'o_show_dot'                => 0,
            'o_topic_views'                => 1,
            'o_quickjump'                => 1,
            'o_gzip'                    => 0,
            'o_additional_navlinks'        => '',
            'o_report_method'            => 0,
            'o_regs_report'                => 0,
            'o_default_email_setting'    => 1,
            'o_mailing_list'            => $data['email'],
            'o_avatars'                    => $data['avatars'],
            'o_avatars_dir'                => 'style/img/avatars',
            'o_avatars_width'            => 60,
            'o_avatars_height'            => 60,
            'o_avatars_size'            => 10240,
            'o_search_all_forums'        => 1,
            'o_base_url'                => $data['base_url'],
            'o_admin_email'                => $data['email'],
            'o_webmaster_email'            => $data['email'],
            'o_forum_subscriptions'        => 1,
            'o_topic_subscriptions'        => 1,
            'o_smtp_host'                => null,
            'o_smtp_user'                => null,
            'o_smtp_pass'                => null,
            'o_smtp_ssl'                => 0,
            'o_regs_allow'                => 1,
            'o_regs_verify'                => 0,
            'o_announcement'            => 0,
            'o_announcement_message'    => __('Announcement'),
            'o_rules'                    => 0,
            'o_rules_message'            => __('Rules'),
            'o_maintenance'                => 0,
            'o_maintenance_message'        => __('Maintenance message'),
            'o_default_dst'                => 0,
            'o_feed_type'                => 2,
            'o_feed_ttl'                => 0,
            'p_message_bbcode'            => 1,
            'p_message_img_tag'            => 1,
            'p_message_all_caps'        => 1,
            'p_subject_all_caps'        => 1,
            'p_sig_all_caps'            => 1,
            'p_sig_bbcode'                => 1,
            'p_sig_img_tag'                => 0,
            'p_sig_length'                => 400,
            'p_sig_lines'                => 4,
            'p_allow_banned_email'        => 1,
            'p_allow_dupe_email'        => 0,
            'p_force_guest_email'        => 1
        );
    }
}
