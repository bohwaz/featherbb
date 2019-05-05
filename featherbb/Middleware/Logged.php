<?php
/**
 *
 * Copyright (C) 2015-2019 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 *
 * $app = new \Slim\Slim();
 * $app->add(new \Slim\Extras\Middleware\FeatherBBAuth());
 *
 */

namespace FeatherBB\Middleware;
use FeatherBB\Core\Interfaces\Router;
use FeatherBB\Core\Interfaces\User;

/**
 * Middleware to check if user is logged
 */
class Logged
{
    public function __invoke($request, $response, $next)
    {
        // Redirect user to login page if not logged
        if (User::get()->is_guest) {
            return Router::redirect(Router::pathFor('login'));
        }

        $response = $next($request, $response);
        return $response;
    }
}
