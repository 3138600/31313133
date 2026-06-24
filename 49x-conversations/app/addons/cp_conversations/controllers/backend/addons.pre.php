<?php

use Tygh\Settings;
use Tygh\Registry;
use Tygh\Http;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    fn_trusted_vars (
        'addon_data'
    );

    if ($mode == 'update') {
        if (isset($_REQUEST['addon_data']) && $_REQUEST['addon'] == 'cp_conversations') {
            foreach($_REQUEST['addon_data']['options'] as $object_id => $val) {

                $data = db_get_row('SELECT name, value FROM ?:settings_objects WHERE object_id = ?i', $object_id);

                if ($data['name'] == 'licensekey') {

                    if (fn_allowed_for('MULTIVENDOR') != true) {
                        $companies = db_get_array('SELECT storefront, secure_storefront FROM ?:companies');
                    } else {
                        $companies = array(array('storefront' => fn_url('', 'C', 'http')));
                    }

                    $_cp_req = array(
                        'companies' => $companies,
                        'addon' => $_REQUEST['addon'],
                        'license' => $val
                    );

                    $request = json_encode($_cp_req);

                    $check_host = 'http://cart-power.com/index.php?dispatch=check_license_20.check';

                    $data = Http::get($check_host, array('request' => urlencode($request)), array(
                        'timeout' => 60
                    ));

                    preg_match('/\<status\>(.*)\<\/status\>/u', $data, $result);

                    $_status = 'FALSE';
                    if (isset($result[1])) {
                        $_status = $result[1];
                    }

                    if ($_status != 'TRUE') {
                        db_query("UPDATE ?:addons SET status = ?s WHERE addon = ?s", 'D', $_REQUEST['addon']);
                        fn_set_notification('W', __('warning'), __('cp_your_license_is_not_valid'));
                        return array(CONTROLLER_STATUS_REDIRECT, 'addons.manage');
                    }
                }
            }
        }

    }
}
