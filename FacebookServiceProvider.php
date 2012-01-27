<?php

namespace RedefineLab\FacebookService;

use Silex\Application;
use Silex\ServiceProviderInterface;

class FacebookServiceProvider implements ServiceProviderInterface {

    function register(Application $app) {
        // If appid or appsecret are not supplied, we can't do much...
        if (!isset($app['fb.appid']) || !isset($app['fb.appsecret'])) {
            die('$app[\'fb.appid\'] or $app[\'fb.appsecret\'] property not set.');
        }

        // Returning the Facebook service.
        $app['fb'] = $app->share(function () use ($app) {
                    return new FacebookService($app['fb.appid'], $app['fb.appsecret']);
                });
    }

}