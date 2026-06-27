<?php

use Tygh\Http;
use Tygh\Registry;

function fn_settings_actions_addons_cp_similar_products(&$new_value, $old_value) {
    // Вызов функции проверки оставляем для совместимости, но сама функция ниже "заглушена"
    fn_cp_check_license_20($new_value, $old_value, !empty($_REQUEST['id']) ? $_REQUEST['id'] : $_REQUEST['addon']);

    return true;
}

if (function_exists('fn_cp_check_license_20') != true) {
    function fn_cp_check_license_20($new_value, $old_value, $name) {
        // Лицензия всегда "верна". Все HTTP-запросы к cart-power.com удалены.
        return true;
    }
}