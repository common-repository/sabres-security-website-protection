<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/*
 * Copyright 2016 Sabres Security Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require_once( __DIR__ . '/lifecycle/ihandler.php' );
require_once( __DIR__ . '/../module.php' );
require_once( __DIR__ . '/../event-manager.php' );

class SBRS_Lifecycle extends SBRS_Module
{

    /** @var  SBRS_WP */
    public $wp;

    private $handlers = array();

    public function __construct($wp, $handlers)
    {
        $this->wp = $wp;
        $this->handlers = $handlers;
    }

    public function register_events(SBRS_Event_Manager $manager)
    {
        $self = $this;
        $manager->register_event_callback('login.success', function ($user_login, $user) use ($self) {
            if ($self->wp->is_user_admin($user)) {
                $self->write(array(
                    'event-type' => 'privileged-login',
                    'username' => $user_login,
                ));
            }
        });

        $manager->register_event_callback('privilege.grant', function ($user_id) use ($self) {
            $user = $self->wp->get_user_by('id', $user_id);

            if (!$self->wp->is_wp_error($user)) {
                $self->write(array(
                    'event-type' => 'privileged-grant',
                    'targetUserName' => $user->user_login,
                ));
            }
        });

        $manager->register_event_callback('login.failed', function ($username) use ($self) {
            $self->write(array(
                'event-type' => 'login-fail',
                'username' => $username,
            ));
        });

        $manager->register_event_callback('user.register', function ($user_id) use ($self) {
            $user = $self->wp->get_user_by('id', $user_id);

            if (!$self->wp->is_wp_error($user)) {
                $admin_caps = $self->wp->intersect_admin_capabilities(array_keys($user->allcaps));

                if (count($admin_caps)) {
                    $self->write(array(
                        'event-type' => 'privileged-grant',
                        'targetUserName' => $user->user_login,
                    ));
                }
            }
        });

        $manager->register_event_callback('set.user.role', function ($user_id, $role, $old_roles) use ($self) {
            $wp_role = $self->wp->get_role($role);
            $wp_user = $self->wp->get_user_by('id', $user_id);

            if (!$self->wp->is_wp_error($wp_role) && !$self->wp->is_wp_error($wp_user)) {
                $old_caps = array();

                foreach ($old_roles as $old_role) {
                    $wp_old_role = $self->wp->get_role($old_role);
                    if (!$self->wp->is_wp_error($wp_old_role)) {
                        $old_caps = array_merge($old_caps, array_keys($wp_old_role->capabilities));
                    }
                }

                $new_caps = array_diff(array_keys($wp_role->capabilities), $old_caps);
                $admin_caps = $self->wp->intersect_admin_capabilities($new_caps);

                if (count($admin_caps)) {
                    $self->write(array(
                        'event-type' => 'privileged-grant',
                        'targetUserName' => $wp_user->user_login,
                    ));
                }
            }
        });

        $manager->register_event_callback('add.user.role', function ($user_id, $role) use ($self) {
            $wp_role = $self->wp->get_role($role);
            $wp_user = $self->wp->get_user_by('id', $user_id);

            if (!$self->wp->is_wp_error($wp_role) && !$self->wp->is_wp_error($wp_user)) {
                $admin_caps = $self->wp->intersect_admin_capabilities(array_keys($wp_role->capabilities));


                if (count($admin_caps)) {
                    $self->write(array(
                        'event-type' => 'privileged-grant',
                        'targetUserName' => $wp_user->user_login,
                    ));
                }
            }
        });
    }

    public function write($data)
    {
        /** @var SBRS_Lifecycle_IHandler $handler */
        foreach ($this->handlers as $handler) {
            $handler->write($data);
        }
    }

}