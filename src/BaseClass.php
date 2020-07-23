<?php
namespace WPChina\WPChinaAPI;

use WP_Http;
use WP_REST_Request;

class BaseClass{
    public static function request_wp_api(WP_REST_Request $request) {
        $http = new WP_Http();
        return $http->request(
            'https://api.wordpress.org'.$request->get_route(),
            [
                'method' => $request->get_method(),
                'body' => $request->get_body_params()
            ]
        );
    }
}