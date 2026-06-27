<?php

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'picker') {
    $lang_code = CART_LANGUAGE;
    
    // ИСПРАВЛЕНИЕ: Выбираем только те характеристики, у которых есть варианты (списки, чекбоксы и т.д.)
    // S - Текстовый список, M - Множественный выбор, E - Расширенная (Бренды), N - Числовой список, C - Чекбокс
    $features = db_get_hash_array(
        "SELECT d.feature_id 
         FROM ?:product_features_descriptions d
         LEFT JOIN ?:product_features f ON f.feature_id = d.feature_id
         WHERE d.lang_code = ?s AND f.feature_type IN ('S', 'M', 'E', 'N', 'C')", 
        'feature_id', $lang_code
    );
    
    Registry::get('view')->assign('features', $features);
    Registry::get('view')->display('addons/cp_similar_products/pickers/picker_contents.tpl');
    exit;
}