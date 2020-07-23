<?php

namespace WPChina\WPChinaAPI\Plugins;

use WP_REST_Request;
use WP_REST_Response;
use WPChina\WPChinaAPI\BaseClass;

class UpdateCheck
{
    public static function v11(WP_REST_Request $request) {
        $result = BaseClass::request_wp_api($request);
        $body = json_decode($result['body'], true);
        $body = add_trans_update_info($request, $body, 'plugin');

        return new WP_REST_Response($body, 200);
    }
}