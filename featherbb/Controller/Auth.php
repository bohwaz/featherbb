<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller;

use FeatherBB\Core\Error;
use FeatherBB\Core\Random;
use FeatherBB\Core\Track;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;
use FeatherBB\Model\Auth as ModelAuth;
use FeatherBB\Model\Cache;

class Auth
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->feather->user->language.'/login.mo');
    }

    public function login()
    {
        if (!$this->feather->user->is_guest) {
            Url::redirect($this->feather->pathFor('home'), 'Already logged in');
        }

        if ($this->feather->request->isPost()) {
            $this->feather->hooks->fire('login_start');
            $form_username = Utils::trim($this->feather->request->post('req_username'));
            $form_password = Utils::trim($this->feather->request->post('req_password'));
            $save_pass = (bool) $this->feather->request->post('save_pass');

            $user = ModelAuth::get_user_from_name($form_username);

            if (!empty($user->password)) {
                $form_password_hash = Random::hash($form_password); // Will result in a SHA-1 hash
                if ($user->password == $form_password_hash) {
                    if ($user->group_id == $this->feather->forum_env['FEATHER_UNVERIFIED']) {
                        ModelAuth::update_group($user->id, $this->feather->forum_settings['o_default_user_group']);
                        if (!$this->feather->cache->isCached('users_info')) {
                            $this->feather->cache->store('users_info', Cache::get_users_info());
                        }
                    }

                    ModelAuth::delete_online_by_ip($this->feather->request->getIp());
                    // Reset tracked topics
                    Track::set_tracked_topics(null);

                    $expire = ($save_pass) ? $this->feather->now + 1209600 : $this->feather->now + $this->feather->forum_settings['o_timeout_visit'];
                    $expire = $this->feather->hooks->fire('expire_login', $expire);
                    ModelAuth::feather_setcookie($user->id, $form_password_hash, $expire);

                    Url::redirect($this->feather->pathFor('home'), __('Login redirect'));
                }
            }
            throw new Error(__('Wrong user/pass').' <a href="'.$this->feather->pathFor('resetPassword').'">'.__('Forgotten pass').'</a>', 403);
        } else {
            $this->feather->template->setPageInfo(array(
                                'active_page' => 'login',
                                'title' => array(Utils::escape($this->feather->forum_settings['o_board_title']), __('Login')),
                                'required_fields' => array('req_username' => __('Username'), 'req_password' => __('Password')),
                                'focus_element' => array('login', 'req_username'),
                                )
                        )->addTemplate('login/form.php')->display();
        }
    }

    public function logout($token)
    {
        $token = $this->feather->hooks->fire('logout_start', $token);

        if ($this->feather->user->is_guest || !isset($token) || $token != Random::hash($this->feather->user->id.Random::hash($this->feather->request->getIp()))) {
            Url::redirect($this->feather->pathFor('home'), 'Not logged in');
        }

        ModelAuth::delete_online_by_id($this->feather->user->id);

        // Update last_visit (make sure there's something to update it with)
        if (isset($this->feather->user->logged)) {
            ModelAuth::set_last_visit($this->feather->user->id, $this->feather->user->logged);
        }

        ModelAuth::feather_setcookie(1, Random::hash(uniqid(rand(), true)), time() + 31536000);
        $this->feather->hooks->fire('logout_end');

        Url::redirect($this->feather->pathFor('home'), __('Logout redirect'));
    }

    public function forget()
    {
        if (!$this->feather->user->is_guest) {
            Url::redirect($this->feather->pathFor('home'), 'Already logged in');
        }

        if ($this->feather->request->isPost()) {
            // Validate the email address
            $email = strtolower(Utils::trim($this->feather->request->post('req_email')));
            if (!$this->feather->email->is_valid_email($email)) {
                throw new Error(__('Invalid email'), 400);
            }
            $user = ModelAuth::get_user_from_email($email);

            if ($user) {
                // Load the "activate password" template
                $mail_tpl = trim(file_get_contents($this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->feather->user->language.'/mail_templates/activate_password.tpl'));
                $mail_tpl = $this->feather->hooks->fire('mail_tpl_password_forgotten', $mail_tpl);

                // The first row contains the subject
                $first_crlf = strpos($mail_tpl, "\n");
                $mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
                $mail_message = trim(substr($mail_tpl, $first_crlf));

                // Do the generic replacements first (they apply to all emails sent out here)
                $mail_message = str_replace('<base_url>', Url::base().'/', $mail_message);
                $mail_message = str_replace('<board_mailer>', $this->feather->forum_settings['o_board_title'], $mail_message);

                $mail_message = $this->feather->hooks->fire('mail_message_password_forgotten', $mail_message);

                if ($user->last_email_sent != '' && (time() - $user->last_email_sent) < 3600 && (time() - $user->last_email_sent) >= 0) {
                    throw new Error(sprintf(__('Email flood'), intval((3600 - (time() - $user->last_email_sent)) / 60)), 429);
                }

                // Generate a new password and a new password activation code
                $new_password = Random::pass(12);
                $new_password_key = Random::pass(8);

                ModelAuth::set_new_password($new_password, $new_password_key, $user->id);

                // Do the user specific replacements to the template
                $cur_mail_message = str_replace('<username>', $user->username, $mail_message);
                $cur_mail_message = str_replace('<activation_url>', $this->feather->pathFor('profileAction', ['action' => 'change_pass']).'?key='.$new_password_key, $cur_mail_message);
                $cur_mail_message = str_replace('<new_password>', $new_password, $cur_mail_message);
                $cur_mail_message = $this->feather->hooks->fire('cur_mail_message_password_forgotten', $cur_mail_message);

                $this->feather->email->feather_mail($email, $mail_subject, $cur_mail_message);

                Url::redirect($this->feather->pathFor('home'), __('Forget mail').' <a href="mailto:'.$this->feather->utils->escape($this->feather->forum_settings['o_admin_email']).'">'.$this->feather->utils->escape($this->feather->forum_settings['o_admin_email']).'</a>.', 200);
            } else {
                throw new Error(__('No email match').' '.Utils::escape($email).'.', 400);
            }
        }

        $this->feather->template->setPageInfo(array(
//                'errors'    =>    $this->model->password_forgotten(),
                'active_page' => 'login',
                'title' => array(Utils::escape($this->feather->forum_settings['o_board_title']), __('Request pass')),
                'required_fields' => array('req_email' => __('Email')),
                'focus_element' => array('request_pass', 'req_email'),
            )
        )->addTemplate('login/password_forgotten.php')->display();
    }
}
