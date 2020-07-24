<?php
    function request_wp_api(WP_REST_Request $request) {
        $http = new WP_Http();
        return $http->request(
            'https://api.wordpress.org'.$request->get_route(),
            [
                'method' => $request->get_method(),
                'body' => $request->get_body_params()
            ]
        );
    }

    function add_trans_update_info(WP_REST_Request $request, $body, $type) {
        global $wpdb;

        $request_plugins = json_decode($request->get_body_params()[$type.'s'], true)[$type.'s'];
        $request_translations = json_decode($request->get_body_params()['translations'], true);
        $translations_updated = [];
        foreach ($request_plugins as $name => $meta) {
            $slug = explode('/', $name)[0];
            $project = $wpdb->get_row($wpdb->prepare('select id from wp_4_gp_projects where `slug`=%s;', [$slug]));
            if (empty($project->id)) { // 项目ID为空的，说明是用户安装了但是翻译平台未翻译，需要加入到队列中进行Get请求，相当于是强制给缓存到云存储上，等着更新监控程序监测到后去走一遍生成语言包的流程
                $post_data = [
                    'app_key' => 'sbvrgrgbg10rgye5y5ebfbgdyyhdgsrg',
                    'app_token' => 'usbvbhu0srfefeafrrgy5rgrgrfrfegsrg',
                    'queue_name' => 'get_wp_package',
                    'type' => 'real_time',
                    'stepping_time' => 0,
                    'max_time_interval' => 0
                ];
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, 'http://127.0.0.1:5999/api/addQueue');
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
                curl_exec($curl);

                $post_data = [
                    'app_key' => 'sbvrgrgbg10rgye5y5ebfbgdyyhdgsrg',
                    'app_token' => 'usbvbhu0srfefeafrrgy5rgrgrfrfegsrg',
                    'queue_name' => 'get_wp_package',
                    'url' => sprintf('https://download.wp-china-yes.net/%s/%s.zip', $type.'s', $meta['TextDomain'])
                ];
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, 'http://127.0.0.1:5999/api/addTask');
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
                curl_exec($curl);

                continue;
            }
            $translation_set = $wpdb->get_row($wpdb->prepare('select id from wp_4_gp_translation_sets where `project_id`=%d;', [$project->id]));
            if (empty($translation_set->id)) {
                continue;
            }
            $translation_update_time = $wpdb->get_row($wpdb->prepare('select meta_value from wp_4_gp_meta where `object_type`=%s and `object_id`=%d and meta_key=%s;', ['translation_set', $translation_set->id, '_traduttore_build_time']));
            if (empty($translation_update_time->meta_value)) {
                continue;
            }
            // $translation_update_time = date("Y-m-d H:i:s", strtotime($translation_update_time->meta_value));
            $translation_update_time = $translation_update_time->meta_value;
            $project_version = $wpdb->get_row($wpdb->prepare('select meta_value from wp_4_gp_meta where `object_type`=%s and `object_id`=%d and meta_key=%s;', ['project', $project->id, '_traduttore_version']));
            if (empty($project_version->meta_value)) {
                continue;
            }
            $project_version = $project_version->meta_value;

            if (!file_exists(sprintf(WP_CONTENT_DIR.'/traduttore/%s-zh_CN-%s.zip', $slug, $project_version))) {
                continue;
            }

            $db_translations = [
                'type' => $type,
                'slug' => $slug,
                'language' => 'zh_CN',
                'version' => $project_version ?? '1.0',
                'updated' => $translation_update_time,
                'package' => sprintf('https://wp-china.org/wp-content/traduttore/%s-zh_CN-%s.zip', $slug, $project_version),
                'autoupdate' => true
            ];

            if (empty($db_translations)) {
                continue;
            }
            /*
             $request_translations的数据结构:
             array(3) {
                ["gutenberg"]=>
                    array(1) {
                        ["zh_CN"]=>
                        array(4) {
                        ["POT-Creation-Date"]=>
                        string(0) ""
                        ["PO-Revision-Date"]=>
                        string(24) "2019-06-28 10:58:11+0000"
                        ["Project-Id-Version"]=>
                        string(45) "Plugins - Gutenberg - Stable (latest release)"
                        ["X-Generator"]=>
                        string(21) "GlotPress/3.0.0-alpha"
                    }
                }
             }
             */
            $request_translation_update_time = null;
            if (key_exists($slug, $request_translations)) {
                if (key_exists('zh_CN', $request_translations[$slug])) {
                    if (key_exists('PO-Revision-Date', $request_translations[$slug]['zh_CN'])) {
                        $request_translation_update_time = $request_translations[$slug]['zh_CN']['PO-Revision-Date'];
                    }
                }
            }

            if ($request_translation_update_time == '+0000' || empty($request_translation_update_time)) {
                if (version_compare($project_version, $meta['Version'])) {
                    $translations_updated = array_merge($translations_updated, [$db_translations]);
                }
            }
            else if (strtotime($db_translations['updated']) > strtotime($request_translation_update_time)) {
                $translations_updated = array_merge($translations_updated, [$db_translations]);
            }
        }

        $body['translations'] = $translations_updated;

        return $body;
    }

    function query_translation_package(string $slug): array {
        global $wpdb;
        $project = $wpdb->get_row($wpdb->prepare('select id from wp_4_gp_projects where `slug`=%s;', [$slug]));
        if (empty($project->id)) {
            return [];
        }
        $translation_set = $wpdb->get_row($wpdb->prepare('select id from wp_4_gp_translation_sets where `project_id`=%d;', [$project->id]));
        if (empty($translation_set->id)) {
            return [];
        }
        $translation_update_time = $wpdb->get_row($wpdb->prepare('select meta_value from wp_4_gp_meta where `object_type`=%s and `object_id`=%d and meta_key=%s;', ['translation_set', $translation_set->id, '_traduttore_build_time']));
        if (empty($translation_update_time->meta_value)) {
            return [];
        }
        // $translation_update_time = date("Y-m-d H:i:s", strtotime($translation_update_time->meta_value));
        $translation_update_time = $translation_update_time->meta_value;
        $project_version = $wpdb->get_row($wpdb->prepare('select meta_value from wp_4_gp_meta where `object_type`=%s and `object_id`=%d and meta_key=%s;', ['project', $project->id, '_traduttore_version']));
        if (empty($project_version->meta_value)) {
            return [];
        }
        $project_version = $project_version->meta_value;

        if (!file_exists(sprintf(WP_CONTENT_DIR.'/traduttore/%s-zh_CN-%s.zip', $slug, $project_version))) {
            return [];
        }

        return [
            'type' => 'plugin',
            'slug' => $slug,
            'language' => 'zh_CN',
            'version' => $project_version ?? '1.0',
            'updated' => $translation_update_time,
            'package' => sprintf('https://wp-china.org/wp-content/traduttore/%s-zh_CN-%s.zip', $slug, $project_version),
            'autoupdate' => true
        ];
}