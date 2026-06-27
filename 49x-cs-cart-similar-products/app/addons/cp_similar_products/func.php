<?php

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

function fn_cp_similar_products_get_products_pre(&$params, &$items_per_page, &$lang_code)
{
    if (!empty($params['cp_similar_by_feature']) || !empty($params['cp_similar_by_tags'])) {
        
        $product_id = !empty($params['cp_similar_products_for_product_id']) ? (int)$params['cp_similar_products_for_product_id'] : 0;
        
        if (!$product_id) {
            $params['pid'] = array(0);
            return;
        }

        // Проверяем статус товара
        $product_status = db_get_field("SELECT status FROM ?:products WHERE product_id = ?i", $product_id);
        if (empty($product_status) || !in_array($product_status, array('A', 'H'))) {
            $params['pid'] = array(0);
            return;
        }

        $product = array();
        $product['main_category'] = db_get_field("SELECT category_id FROM ?:products_categories WHERE product_id = ?i AND link_type = 'M'", $product_id);
        $product['price'] = (float) db_get_field("SELECT price FROM ?:product_prices WHERE product_id = ?i AND lower_limit = 1 AND usergroup_id = 0", $product_id);
        
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
        $limit = !empty($params['limit']) ? (int)$params['limit'] : 50;
        
        // ПОХОЖИЕ ПО ХАРАКТЕРИСТИКАМ (Оптимизировано под MySQL)
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
                    // Строгое совпадение всех характеристик
                    $variants_count = count($variant_ids);
                    $products_ids = db_get_fields(
                        "SELECT product_id FROM ?:product_features_values 
                         WHERE variant_id IN (?a) AND product_id <> ?i 
                         GROUP BY product_id 
                         HAVING COUNT(DISTINCT variant_id) = ?i",
                        $variant_ids, $product_id, $variants_count
                    );
                } else {
                    // Взвешенный поиск силами MySQL (вместо перебора в PHP)
                    $cases = array();
                    foreach ($feature_weights as $f_id => $weight) {
                        $cases[] = db_quote("WHEN ?i THEN ?d", $f_id, $weight);
                    }
                    $case_sql = empty($cases) ? "1" : "CASE feature_id " . implode(" ", $cases) . " ELSE 1 END";

                    // Вычисляем баллы прямо в запросе и сразу забираем топ лучших
                    $products_ids = db_get_fields(
                        "SELECT product_id FROM ?:product_features_values 
                         WHERE variant_id IN (?a) AND product_id <> ?i 
                         GROUP BY product_id 
                         ORDER BY SUM(?p) DESC 
                         LIMIT ?i",
                        $variant_ids, $product_id, $case_sql, $limit * 2
                    );
                }
            }
        } 
        // ПОХОЖИЕ ПО ТЕГАМ (Добавлена релевантность)
        elseif (!empty($params['cp_similar_by_tags'])) {
            if (Registry::get('addons.tags.status') == 'A') {
                // Сортируем по количеству совпадающих тегов (самые похожие - первые)
                $products_ids = db_get_fields(
                    "SELECT t2.object_id FROM ?:tag_links t1
                     JOIN ?:tag_links t2 ON t1.tag_id = t2.tag_id
                     WHERE t1.object_type = 'P' AND t2.object_type = 'P' 
                     AND t1.object_id = ?i AND t2.object_id <> ?i
                     GROUP BY t2.object_id
                     ORDER BY COUNT(t2.tag_id) DESC
                     LIMIT ?i", 
                    $product_id, $product_id, $limit * 2
                );
            }
        }

        $params['pid'] = empty($products_ids) ? array(0) : $products_ids;

        // Отмечаем, что товары уже отсортированы по релевантности
        if (!empty($products_ids)) {
            $params['cp_similarity_ordered'] = true;
        }

        // ПАГИНАЦИЯ (вывод конкретного товара)
        if (!empty($params['exact_row_number']) && is_numeric($params['exact_row_number']) && $params['exact_row_number'] > 0) {
            $params['page'] = (int)$params['exact_row_number'];
            $params['items_per_page'] = 1;
            $items_per_page = 1;
        }

        // НАСТРОЙКИ НАЛИЧИЯ
        $params['hide_out_of_stock'] = (empty($params['cp_similar_in_stock']) || $params['cp_similar_in_stock'] !== 'Y') ? 'N' : 'Y';
    }
}

function fn_cp_similar_products_get_products(&$params, &$fields, &$sortings, &$condition, &$join, &$sorting, &$group_by)
{
    // Применяем сортировку нативным для CS-Cart способом
    if (!empty($params['cp_similarity_ordered']) && !empty($params['pid'])) {
        // Добавляем свою виртуальную сортировку по порядку переданных ID
        $sortings['cp_relevance'] = db_quote("FIELD(products.product_id, ?a)", $params['pid']);
        $params['sort_by'] = 'cp_relevance';
        $params['sort_order'] = 'asc';
    }
}

function fn_cp_similar_products_get_feature_name($feature_id, $lang_code = CART_LANGUAGE) 
{
    return db_get_field("SELECT description FROM ?:product_features_descriptions WHERE feature_id = ?i AND lang_code = ?s", $feature_id, $lang_code);
}