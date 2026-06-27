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
        
        // НОВАЯ ЛОГИКА ДЛЯ ПОЛЬЗОВАТЕЛЬСКОЙ КАТЕГОРИИ
        if (!empty($params['exact_category_id'])) {
            // Если указан точный ID (или несколько через запятую), используем его
            $params['cid'] = is_array($params['exact_category_id']) ? $params['exact_category_id'] : explode(',', str_replace(' ', '', $params['exact_category_id']));
            if (!empty($params['similar_subcats']) && $params['similar_subcats'] == 'Y') {
                $params['subcats'] = 'Y';
            }
        } elseif (!empty($params['similar_category']) && $params['similar_category'] == 'Y' && !empty($product['main_category'])) {
            // Иначе используем главную категорию просматриваемого товара
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

        // 1. ИЩЕМ ID ТОВАРОВ ЗАРАНЕЕ, чтобы передать их ядру через нативный параметр pid
        $product_id = !empty($params['cp_similar_products_for_product_id']) ? $params['cp_similar_products_for_product_id'] : 0;
        
        if ($product_id) {
            $products_ids = array();
            
            if (!empty($params['cp_similar_by_feature']) && !empty($params['feature_ids'])) {
                $feature_ids = explode(",", $params['feature_ids']);
                $variant_ids = db_get_fields("SELECT variant_id FROM ?:product_features_values WHERE product_id = ?i AND lang_code = ?s AND feature_id IN (?a)", $product_id, $lang_code, $feature_ids);
                
                if (!empty($variant_ids)) {
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
                }
            } elseif (!empty($params['cp_similar_by_tags'])) {
                $tag_ids = db_get_fields("SELECT tag_id FROM ?:tag_links WHERE object_id = ?i", $product_id);
                if (!empty($tag_ids)) {
                    $products_ids = db_get_fields("SELECT object_id FROM ?:tag_links WHERE tag_id IN (?a) AND object_id <> ?i", $tag_ids, $product_id);
                }
            }

            if (empty($products_ids)) {
                $params['pid'] = array(0); // Товары не найдены
            } else {
                $params['pid'] = $products_ids;
            }
        } else {
            $params['pid'] = array(0);
        }

        // 2. ОТКЛЮЧЕНИЕ СИСТЕМНЫХ ПРОВЕРОК НАЛИЧИЯ (ГЛАВНЫЙ ФИКС)
        if (empty($params['cp_similar_in_stock']) || $params['cp_similar_in_stock'] !== 'Y') {
            $params['hide_out_of_stock'] = 'N';
            
            // Важно: передаем именно строгий (boolean) true. Ядро CS-Cart проверяет (!$params['show_out_of_stock_products']).
            $params['show_out_of_stock_products'] = true; 
            $params['skip_inventory_check'] = true;
            
            if (isset($params['amount_from'])) {
                unset($params['amount_from']);
            }
        }

        // 3. ПАГИНАЦИЯ ДЛЯ ТОЧНОГО НОМЕРА СТРОКИ
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
        
        // Поиск ID мы уже сделали в хуке _pre, поэтому ядро само подставит `IN (ID1, ID2)` в запрос.
        
        // Если галочка "Показывать только в наличии" СТОИТ в настройках блока:
        if (!empty($params['cp_similar_in_stock']) && $params['cp_similar_in_stock'] == 'Y') {
            // Принудительно проверяем остатки (добавляем SQL-фильтр модуля)
            $condition .= db_quote(" AND (IF(products.tracking = ?s, inventory_b.amount >= 1, products.amount >= 1) OR (products.tracking = 'D'))", ProductTracking::TRACK_WITH_OPTIONS);
            $join .= " LEFT JOIN ?:product_options_inventory as inventory_b ON inventory_b.product_id = products.product_id AND inventory_b.amount >= 1";
        } 
        else {
            // Защитный механизм на случай, если сторонние модули попытались вернуть проверку нулевых остатков:
            $condition = preg_replace('/AND\s+\(?\s*products\.amount\s*>\s*0\s*\)?/i', '', $condition);
        }
    }
}

function fn_cp_similar_products_get_feature_name($feature_id, $lang_code = CART_LANGUAGE) 
{
    return db_get_field("SELECT description FROM ?:product_features_descriptions WHERE feature_id = ?i AND lang_code = ?s", $feature_id, $lang_code);
}