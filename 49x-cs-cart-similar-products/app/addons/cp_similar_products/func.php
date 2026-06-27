<?php

use Tygh\Registry;
use Tygh\Enum\ProductTracking;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

function fn_cp_similar_products_get_products_pre(&$params, &$items_per_page, &$lang_code)
{
    if (!empty($params['cp_similar_by_feature']) || !empty($params['cp_similar_by_tags'])) {
        
        // 1. БЕЗОПАСНОЕ ПОЛУЧЕНИЕ ДАННЫХ ТОВАРА
        $product_id = !empty($params['cp_similar_products_for_product_id']) ? (int)$params['cp_similar_products_for_product_id'] : 0;
        
        if (!$product_id) {
            $params['pid'] = array(0);
            return;
        }

        // Пытаемся получить товар из View, если нет - грузим минимально необходимые данные из БД
        $product = Registry::get('view')->getTemplateVars('product');
        if (empty($product) || $product['product_id'] != $product_id) {
            $product = db_get_row("SELECT price, category_id as main_category FROM ?:products WHERE product_id = ?i", $product_id);
        }
        
        $params['exclude_pid'] = $product_id;
        
        // КАТЕГОРИИ
        if (!empty($params['exact_category_id'])) {
            $params['cid'] = explode(',', str_replace(' ', '', $params['exact_category_id']));
            if (!empty($params['similar_subcats']) && $params['similar_subcats'] == 'Y') {
                $params['subcats'] = 'Y';
            }
        } elseif (!empty($params['similar_category']) && $params['similar_category'] == 'Y' && !empty($product['main_category'])) {
            $params['cid'] = $product['main_category'];
            if (!empty($params['similar_subcats']) && $params['similar_subcats'] == 'Y') {
                $params['subcats'] = 'Y';
            }
        }
        
        // ЦЕНЫ
        if (!empty($product['price']) && !empty($params['percent_range'])) {
            $range = $product['price'] / 100 * $params['percent_range'];
            $params['price_from'] = $product['price'] - $range;
            $params['price_to'] = $product['price'] + $range;
        }

        $products_ids = array();
        
        // ПОХОЖИЕ ПО ХАРАКТЕРИСТИКАМ
        if (!empty($params['cp_similar_by_feature']) && !empty($params['feature_ids'])) {
            $feature_ids = explode(",", $params['feature_ids']);
            
            $current_variants = db_get_hash_single_array(
                "SELECT feature_id, variant_id FROM ?:product_features_values WHERE product_id = ?i AND lang_code = ?s AND feature_id IN (?a)",
                array('feature_id', 'variant_id'), $product_id, $lang_code, $feature_ids
            );

            if (!empty($current_variants)) {
                $variant_ids = array_values($current_variants);
                $feature_weights = array();
                
                if (!empty($params['feature_weights'])) {
                    $pairs = explode(',', $params['feature_weights']);
                    foreach ($pairs as $pair) {
                        $parts = explode(':', $pair);
                        if (isset($parts[0]) && isset($parts[1])) {
                            $feature_weights[(int)trim($parts[0])] = (float)trim($parts[1]);
                        }
                    }
                }

                if (empty($feature_weights)) {
                    // ОПТИМИЗАЦИЯ 1: Strict AND через SQL GROUP BY (вместо array_intersect)
                    $variants_count = count($variant_ids);
                    $products_ids = db_get_fields(
                        "SELECT product_id FROM ?:product_features_values 
                         WHERE variant_id IN (?a) AND product_id <> ?i 
                         GROUP BY product_id 
                         HAVING COUNT(DISTINCT variant_id) = ?i",
                        $variant_ids, $product_id, $variants_count
                    );
                } else {
                    // ОПТИМИЗАЦИЯ 2: Взвешенный поиск. Выполняем 1 запрос вместо множества в цикле.
                    $related_products = db_get_array(
                        "SELECT product_id, feature_id FROM ?:product_features_values 
                         WHERE variant_id IN (?a) AND product_id <> ?i",
                        $variant_ids, $product_id
                    );

                    $scored_products = array();
                    foreach ($related_products as $row) {
                        $pid = $row['product_id'];
                        $fid = $row['feature_id'];
                        $weight = isset($feature_weights[$fid]) ? $feature_weights[$fid] : 1;
                        
                        if (!isset($scored_products[$pid])) {
                            $scored_products[$pid] = 0;
                        }
                        $scored_products[$pid] += $weight;
                    }

                    if (!empty($scored_products)) {
                        arsort($scored_products);
                        // ОПТИМИЗАЦИЯ 3: Обрезаем массив, чтобы SQL запрос не "взорвался" от CASE WHEN
                        $limit = !empty($params['limit']) ? (int)$params['limit'] : 50;
                        $scored_products = array_slice($scored_products, 0, $limit * 2, true); 
                        
                        $products_ids = array_keys($scored_products);
                        $params['cp_scored_products'] = $scored_products;
                    }
                }
            }
        } 
        // ПОХОЖИЕ ПО ТЕГАМ
        elseif (!empty($params['cp_similar_by_tags'])) {
            // ОПТИМИЗАЦИЯ 4: Один JOIN запрос вместо двух
            $products_ids = db_get_fields(
                "SELECT t2.object_id FROM ?:tag_links t1
                 JOIN ?:tag_links t2 ON t1.tag_id = t2.tag_id
                 WHERE t1.object_type = 'P' AND t2.object_type = 'P' 
                 AND t1.object_id = ?i AND t2.object_id <> ?i
                 GROUP BY t2.object_id", 
                $product_id, $product_id
            );
        }

        $params['pid'] = empty($products_ids) ? array(0) : $products_ids;

        // ПАГИНАЦИЯ (вывод конкретного товара)
        if (!empty($params['exact_row_number']) && is_numeric($params['exact_row_number']) && $params['exact_row_number'] > 0) {
            $params['page'] = (int)$params['exact_row_number'];
            $params['items_per_page'] = 1;
            $items_per_page = 1;
        }

        // НАСТРОЙКИ НАЛИЧИЯ (Оптимизировано, без регулярных выражений в post-хуке)
        if (empty($params['cp_similar_in_stock']) || $params['cp_similar_in_stock'] !== 'Y') {
            $params['hide_out_of_stock'] = 'N';
            $params['amount_from'] = ''; // Корректное отключение проверки остатка в CS-Cart
        } else {
             $params['hide_out_of_stock'] = 'Y';
             $params['amount_from'] = 1;
        }
    }
}

function fn_cp_similar_products_get_products(&$params, &$fields, &$sortings, &$condition, &$join, &$sorting, &$group_by)
{
    if (!empty($params['cp_similar_by_feature']) || !empty($params['cp_similar_by_tags'])) {
        
        // Сортировка по баллам (весам) характеристик
        if (!empty($params['cp_scored_products'])) {
            $order_cases = array();
            foreach ($params['cp_scored_products'] as $pid => $score) {
                $order_cases[] = db_quote("WHEN ?i THEN ?d", $pid, $score);
            }
            if (!empty($order_cases)) {
                // Массив обрезан в pre-хуке, поэтому запрос будет безопасным
                $sorting = "CASE products.product_id " . implode(" ", $order_cases) . " ELSE 0 END DESC";
            }
        }
    }
}

function fn_cp_similar_products_get_feature_name($feature_id, $lang_code = CART_LANGUAGE) 
{
    return db_get_field("SELECT description FROM ?:product_features_descriptions WHERE feature_id = ?i AND lang_code = ?s", $feature_id, $lang_code);
}