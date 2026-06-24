<?php

use Tygh\Registry;

$allow_start_conv = false;
if (fn_allowed_for('MULTIVENDOR')) {
    $cur_company_id = Registry::get('runtime.vendor_id');
} else {
    $cur_company_id = Registry::get('runtime.company_id');
}
if (!empty($cur_company_id)) {
    if (fn_allowed_for('MULTIVENDOR')) {
        $avail_admins = db_get_array("SELECT ?:users.user_id FROM ?:users 
            LEFT JOIN ?:companies ON ?:companies.company_id = ?:users.company_id
            WHERE ?:users.company_id = ?i  AND ?:users.user_type = ?s AND ?:users.status = ?s AND ?:companies.status = ?s", $cur_company_id, 'V', 'A', 'A');
    } else {
        $avail_admins = db_get_array("SELECT user_id FROM ?:users WHERE company_id = ?i AND is_root = ?s AND user_type = ?s AND status = ?s AND cp_for_conversation = ?s", $cur_company_id, 'Y', 'A', 'A', 'Y');
    }
} elseif (fn_allowed_for('MULTIVENDOR')) {
    $avail_admins = db_get_array("SELECT ?:users.user_id FROM ?:users 
        LEFT JOIN ?:companies ON ?:companies.company_id = ?:users.company_id
        WHERE ?:users.user_type = ?s AND ?:users.status = ?s AND ?:companies.status = ?s", 'V', 'A', 'A');
}
if (Registry::get('addons.cp_conversations.allow_conversations_with_admin') == 'Y' || !empty($avail_admins)) {
    $allow_start_conv = true;
}
Registry::get('view')->assign('cp_allow_start_conv', $allow_start_conv);