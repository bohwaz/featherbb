<?php

namespace FeatherBB;

use DI\Container;
use FeatherBB\Core\Interfaces\Feather;
use FeatherBB\Core\Interfaces\SlimStatic;
use FeatherBB\Middleware\Auth;
use FeatherBB\Middleware\Core;
use FeatherBB\Middleware\Csrf;
use FeatherBB\Middleware\RedirectNonTrailingSlash;
use Slim\App;
use Slim\Factory\AppFactory;

/**
 * Copyright (C) 2015-2019 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Start a session for flash messages
session_cache_limiter(false);
session_start();

// Load Conposer dependencies
require 'vendor/autoload.php';

// Create Container using PHP-DI
$container = new Container();

// Set container to create App with on AppFactory
AppFactory::setContainer($container);
$app = AppFactory::create();

// Instantiate Slim
SlimStatic::boot($app);

// Allow static proxies to be called from anywhere in App
Statical::addNamespace('*', __NAMESPACE__.'\\*');

Feather::add(new RedirectNonTrailingSlash);
Feather::add(new Csrf);
Feather::add(new Auth);
Feather::add(new Core);

// Load the routes
require 'featherbb/routes.php';

// Run it, baby!
Feather::run();
