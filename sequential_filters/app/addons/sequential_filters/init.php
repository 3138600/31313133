<?php
/*
 * Инициализация модуля и регистрация хуков
 */

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// Регистрируем хук, который срабатывает после получения списка фильтров товаров
fn_register_hooks(
    'get_product_filters_post'
);