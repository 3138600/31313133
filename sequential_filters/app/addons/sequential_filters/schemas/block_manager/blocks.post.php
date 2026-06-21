<?php
/*
 * Регистрация нового шаблона блока в менеджере блоков CS-Cart
 */

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if (isset($schema['product_filters'])) {
    
    // 1. Проверяем: если оригинальный шаблон задан просто строкой (как это бывает по умолчанию),
    // то преобразуем его в правильный массив, чтобы система поняла, что теперь шаблонов несколько.
    if (isset($schema['product_filters']['templates']) && is_string($schema['product_filters']['templates'])) {
        $default_template = $schema['product_filters']['templates'];
        $schema['product_filters']['templates'] = array(
            $default_template => array()
        );
    }

    // 2. Добавляем наш новый шаблон в этот список. 
    // Теперь система увидит 2 шаблона и покажет выпадающий список "Шаблон" в настройках блока.
    $schema['product_filters']['templates']['addons/sequential_filters/blocks/product_filters/sequential.tpl'] = array();
}

return $schema;