<?php
/*
 * Регистрация нового шаблона блока в менеджере блоков CS-Cart
 */

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if (isset($schema['product_filters'])) {
    
    // Проверяем: если оригинальный шаблон задан просто строкой,
    // то преобразуем его в массив, чтобы система поняла, что теперь шаблонов несколько.
    if (isset($schema['product_filters']['templates']) && is_string($schema['product_filters']['templates'])) {
        $default_template = $schema['product_filters']['templates'];
        $schema['product_filters']['templates'] = array(
            $default_template => array()
        );
    }

    // Добавляем наш новый шаблон в этот список. 
    $schema['product_filters']['templates']['addons/sequential_filters/blocks/product_filters/sequential.tpl'] = array();
}

return $schema;