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
        if (isset($_REQUEST['addon_data']) && $_REQUEST['addon'] == 'cp_similar_products') {
            // Весь блок с проверкой лицензии через Http::get вырезан.
            // Модуль больше не будет деактивироваться при сохранении настроек с "неправильным" ключом.
            return;
        }
    }
}