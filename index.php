<?php

/*
 * Plugin Name: WP-China-API
 * Description: 试图提供api.wordpress.org的本土化版本。该插件只负责部分API，另外的部分要么从官方反代，要么依托Nginx转发给其他wordpress社区已有的解决方案上
 * Author: WP中国本土化社区
 * Version: 1.0.0
 * Author URI:https://wp-china.org
 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require __DIR__ . '/vendor/autoload.php';
}

/**
 * 路由注册
 */
add_action('rest_api_init', function () {
    register_rest_route(
        'plugins',
        '/update-check/1.1',
        [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => ['WPChina\WPChinaAPI\Plugins\UpdateCheck', 'v11'],
        ]
    );
    register_rest_route(
        'plugins',
        '/info/1.2',
        [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => ['WPChina\WPChinaAPI\Plugins\Info', 'v12'],
        ]
    );

    register_rest_route(
        'themes',
        '/update-check/1.1',
        [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => ['WPChina\WPChinaAPI\Themes\UpdateCheck', 'v11'],
        ]
    );
    register_rest_route(
        'themes',
        '/info/1.2',
        [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => ['WPChina\WPChinaAPI\Themes\Info', 'v12'],
        ]
    );
});