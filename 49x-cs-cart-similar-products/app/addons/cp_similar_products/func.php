<?php

use Tygh\Registry;
use Tygh\Enum\ProductTracking;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

function fn_cp_similar_products_get_products_pre(&$params, &$items_per_page, &$lang_code)
{
    if (!empty($params['cp_similar_by_feature']) || !empty($params['cp_similar_by_tags'])) {
        $product = Registry::get('view')->getTemplateVars('product');
        
        if (!empty($params['main_product_id'])) {
            $params['exclude_pid'] = $params['main_product_id'];
        }
        
        // ЛОГИКА ДЛЯ ПОЛЬЗОВАТЕЛЬСКОЙ КАТЕГОРИИ
        if (!empty($params['exact_category_id'])) {
            $params['cid'] = is_array($params['exact_category_id']) ? $params['exact_category_id'] : explode(',', str_replace(' ', '', $params['exact_category_id']));
            if (!empty($params['similar_subcats']) && $params['similar_subcats'] == 'Y') {
                $params['subcats'] = 'Y';
            }
        } elseif (!empty($params['similar_category']) && $params['similar_category'] == 'Y' && !empty($product['main_category'])) {
            $params['cid'] = $product['main_category'];
            if (!empty($params['similar_subcats']) && $params['similar_subcats'] == 'Y') {
                $params['subcats'] = 'Y';
            }
        }
        
        if (!empty($product['price']) && !empty($params['percent_range'])) {
            $range = $product['price'] / 100 * $params['percent_range'];
            $params['price_from'] = $product['price'] - $range;
            $params['price_to'] = $product['price'] + $range;
        }

        // ИЩЕМ ID ТОВАРОВ ЗАРАНЕЕ
        $product_id = !empty($params['cp_similar_products_for_product_id']) ? $params['cp_similar_products_for_product_id'] : 0;
        
        if ($product_id) {
            $products_ids = array();
            
            if (!empty($params['cp_similar_by_feature']) && !empty($params['feature_ids'])) {
                $feature_ids = explode(",", $params['feature_ids']);
                
                // Чтение весов характеристик
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
                
                // Получаем все варианты выбранных характеристик текущего товара
                $current_variants = db_get_hash_single_array(
                    "SELECT feature_id, variant_id FROM ?:product_features_values WHERE product_id = ?i AND lang_code = ?s AND feature_id IN (?a)",
                    array('feature_id', 'variant_id'), $product_id, $lang_code, $feature_ids
                );

                if (!empty($current_variants)) {
                    if (empty($feature_weights)) {
                        // ИСХОДНАЯ ЛОГИКА (Строгое совпадение AND)
                        $variant_ids = array_values($current_variants);
                        if(count($variant_ids) > 1) {
                            $products_ids_arr = array();
                            foreach($variant_ids as $variant_id) {
                                $products_ids_arr[$variant_id] = db_get_fields("SELECT product_id FROM ?:product_features_values WHERE variant_id = ?i AND lang_code = ?s AND product_id <> ?i", $variant_id, $lang_code, $product_id);
                            }
                            if (!empty($products_ids_arr)) {
                                $products_ids = call_user_func_array('array_intersect', $products_ids_arr);
                            }
                        } else {
                            $products_ids = db_get_fields("SELECT product_id FROM ?:product_features_values WHERE variant_id IN (?a) AND lang_code = ?s AND product_id <> ?i", $variant_ids, $lang_code, $product_id);
                        }
                    } else {
                        // НОВАЯ ЛОГИКА (Взвешенное нестрогое совпадение OR)
                        $scored_products = array();
                        foreach ($current_variants as $fid => $vid) {
                            $weight = isset($feature_weights[$fid]) ? $feature_weights[$fid] : 1;
                            $pids = db_get_fields("SELECT product_id FROM ?:product_features_values WHERE variant_id = ?i AND lang_code = ?s AND product_id <> ?i", $vid, $lang_code, $product_id);
                            foreach ($pids as $pid) {
                                if (!isset($scored_products[$pid])) {
                                    $scored_products[$pid] = 0;
                                }
                                $scored_products[$pid] += $weight;
                            }
                        }
                        if (!empty($scored_products)) {
                            arsort($scored_products); // Сортируем по баллам по убыванию
                            $products_ids = array_keys($scored_products);
                            // Сохраняем скоринг, чтобы применить правильную сортировку в основном SQL запросе CS-Cart
                            $params['cp_scored_products'] = $scored_products;
                        }
                    }
                }
            } elseif (!empty($params['cp_similar_by_tags'])) {
                $tag_ids = db_get_fields("SELECT tag_id FROM ?:tag_links WHERE object_id = ?i", $product_id);
                if (!empty($tag_ids)) {
                    $products_ids = db_get_fields("SELECT object_id FROM ?:tag_links WHERE tag_id IN (?a) AND object_id <> ?i", $tag_ids, $product_id);
                }
            }

            if (empty($products_ids)) {
                $params['pid'] = array(0);
            } else {
                $params['pid'] = $products_ids;
            }
        } else {
            $params['pid'] = array(0);
        }

        // ОТКЛЮЧЕНИЕ СИСТЕМНЫХ ПРОВЕРОК НАЛИЧИЯ
        if (empty($params['cp_similar_in_stock']) || $params['cp_similar_in_stock'] !== 'Y') {
            $params['hide_out_of_stock'] = 'N';
            $params['show_out_of_stock_products'] = true; 
            $params['skip_inventory_check'] = true;
            
            if (isset($params['amount_from'])) {
                unset($params['amount_from']);
            }
        }

        // ПАГИНАЦИЯ ДЛЯ ТОЧНОГО НОМЕРА СТРОКИ
        if (!empty($params['exact_row_number']) && is_numeric($params['exact_row_number']) && $params['exact_row_number'] > 0) {
            $params['page'] = (int)$params['exact_row_number'];
            $params['items_per_page'] = 1;
            $items_per_page = 1;
            unset($params['limit']);
        }
    }
}

function fn_cp_similar_products_get_products(&$params, &$fields, &$sortings, &$condition, &$join, &$sorting, &$group_by)
{
    if (!empty($params['cp_similar_by_feature']) || !empty($params['cp_similar_by_tags'])) {
        
        // Если галочка "Показывать только в наличии" СТОИТ в настройках блока:
        if (!empty($params['cp_similar_in_stock']) && $params['cp_similar_in_stock'] == 'Y') {
            $condition .= db_quote(" AND (IF(products.tracking = ?s, inventory_b.amount >= 1, products.amount >= 1) OR (products.tracking = 'D'))", ProductTracking::TRACK_WITH_OPTIONS);
            $join .= " LEFT JOIN ?:product_options_inventory as inventory_b ON inventory_b.product_id = products.product_id AND inventory_b.amount >= 1";
        } 
        else {
            $condition = preg_replace('/AND\s+\(?\s*products\.amount\s*>\s*0\s*\)?/i', '', $condition);
        }

        // Сортировка по весам (баллы, набранные по характеристикам)
        if (!empty($params['cp_scored_products'])) {
            $order_cases = array();
            foreach ($params['cp_scored_products'] as $pid => $score) {
                $order_cases[] = db_quote("WHEN ?i THEN ?d", $pid, $score);
            }
            if (!empty($order_cases)) {
                $sorting = "CASE products.product_id " . implode(" ", $order_cases) . " ELSE 0 END DESC";
            }
        }
    }
}

function fn_cp_similar_products_get_feature_name($feature_id, $lang_code = CART_LANGUAGE) 
{
    return db_get_field("SELECT description FROM ?:product_features_descriptions WHERE feature_id = ?i AND lang_code = ?s", $feature_id, $lang_code);
}