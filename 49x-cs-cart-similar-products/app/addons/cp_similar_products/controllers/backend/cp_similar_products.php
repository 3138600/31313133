<?php

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'picker') {
    $lang_code = CART_LANGUAGE;
    $features = db_get_hash_array("SELECT feature_id FROM ?:product_features_descriptions WHERE lang_code = ?s", 'feature_id', $lang_code);
    Registry::get('view')->assign('features', $features);
    Registry::get('view')->display('addons/cp_similar_products/pickers/picker_contents.tpl');
    exit;
}