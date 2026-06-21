<?php
/*
 * Основная логика обработки фильтров
 */

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * Хук для изменения списка фильтров перед их выводом
 * * @param array $filters Массив фильтров
 * @param array $params  Параметры поиска
 */
function fn_sequential_filters_get_product_filters_post(&$filters, $params) {
    // Применяем логику только на витрине (Customer area)
    if (AREA !== 'C') {
        return;
    }

    if (empty($filters)) {
        return;
    }

    // Флаг, разрешающий показ следующего фильтра
    $show_next = true;

    foreach ($filters as $filter_id => $filter) {
        $has_selected_options = false;
        $has_enabled_variants = false;
        
        // Проверяем, является ли фильтр ползунком (цена, дата и т.д.)
        $is_slider = (!empty($filter['filter_style']) && $filter['filter_style'] == 'slider') 
                     || in_array($filter['filter_type'], ['R', 'D']);

        // 1. Проверяем наличие выбранных опций и доступных (не заблокированных) вариантов
        if (!empty($filter['selected_variants'])) {
            $has_selected_options = true;
            $has_enabled_variants = true;
        } elseif (!empty($filter['variants'])) {
            foreach ($filter['variants'] as $v) {
                if (!empty($v['selected'])) {
                    $has_selected_options = true;
                }
                // Если вариант не disabled (или его вообще нет), значит он доступен
                if (empty($v['disabled'])) {
                    $has_enabled_variants = true;
                }
            }
        }

        // 2. Специфичная проверка для ползунков (слайдеров)
        if ($is_slider) {
            $has_enabled_variants = true; // Слайдеры обычно всегда имеют диапазон
            
            // Если левая граница сдвинута от минимума
            if (isset($filter['left']) && isset($filter['min']) && $filter['left'] > $filter['min']) {
                $has_selected_options = true;
            }
            // Если правая граница сдвинута от максимума
            if (isset($filter['right']) && isset($filter['max']) && $filter['right'] < $filter['max']) {
                $has_selected_options = true;
            }
        }

        // 3. Скрываем фильтры, которые в данный момент не могут быть выбраны
        // Если это не слайдер, нет доступных вариантов и ничего не выбрано - скрываем
        if (!$is_slider && !$has_enabled_variants && !$has_selected_options) {
            unset($filters[$filter_id]);
            continue; // Пропускаем этот фильтр, не прерывая общую очередь
        }

        // 4. Логика последовательного отображения
        if (!$show_next) {
            unset($filters[$filter_id]);
            continue;
        }

        // 5. Обновляем флаг очереди для СЛЕДУЮЩЕЙ итерации.
        // Если в текущем показанном фильтре ничего не выбрано, 
        // все последующие фильтры будут скрыты.
        if (!$has_selected_options) {
            $show_next = false;
        }
    }
}