<?php

namespace WPChina\WPChinaAPI\Plugins;

use WP_REST_Request;
use WP_REST_Response;

class Info
{
    public static function v12(WP_REST_Request $request)
    {
        $r = [
            'info' => [
              'page' => 1,
              'pages' => 99,
              'results' => 3564
            ],
            'plugins' => [
                [
                    'name' => 'LiteSpeed Cache',
                    'slug' => 'litespeed-cache',
                    'version' => ''
                ]
            ]
        ];

        return new WP_REST_Response([], 200);
    }
}