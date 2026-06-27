<?php

$schema['products']['content']['items']['fillings']['cp_similar_by_tags'] = array (
    'params' => array (
        'cp_similar_by_tags' => true,
        'request' => array(
            'cp_similar_products_for_product_id' => '%PRODUCT_ID%'
        ),
    ),
);

$schema['products']['content']['items']['fillings']['cp_similar_by_feature'] = array (
    'params' => array (
        'cp_similar_by_feature' => true,
        'request' => array(
            'cp_similar_products_for_product_id' => '%PRODUCT_ID%'
        ),
    ),
);
$schema['products']['cache']['request_handlers'][] = '%PRODUCT_ID%';
// ИСПРАВЛЕНИЕ: Правильные таблицы для автоматического сброса кэша блока
$schema['products']['cache']['update_handlers'][] = 'product_features_values';
$schema['products']['cache']['update_handlers'][] = 'tag_links';

return $schema;