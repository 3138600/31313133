<?php

$schema['cp_similar_by_tags'] = array (
    'limit' => array (
        'type' => 'input',
        'default_value' => 3,
    ),
    'exact_row_number' => array (
        'type' => 'input',
        'default_value' => '',
    ),
    'exact_category_id' => array (
        'type' => 'input',
        'default_value' => '',
    ),
    'percent_range' => array (
        'type' => 'input',
        'unset_empty' => true,
    ),
    'similar_category' => array (
        'type' => 'checkbox',
        'default_value' => 'Y'
    ),
    'similar_subcats' => array (
        'type' => 'checkbox',
        'default_value' => 'Y'
    ),
    'cp_similar_in_stock' => array (
        'type' => 'checkbox',
        'default_value' => 'Y'
    ),
);

$schema['cp_similar_by_feature'] = array (
    'feature_ids' => array (
    'type' => 'picker',
    'option_name' => 'cp_add_features',
    'picker' => 'addons/cp_similar_products/pickers/picker.tpl',
    'picker_params' => array(
        'multiple' => true,
        'use_keys' => 'N',
        'view_mode' => 'table',
    ),
),
    'feature_weights' => array (
        'type' => 'input',
        'default_value' => '',
    ),
    'limit' => array (
        'type' => 'input',
        'default_value' => 3,
    ),
    'exact_row_number' => array (
        'type' => 'input',
        'default_value' => '',
    ),
    'exact_category_id' => array (
        'type' => 'input',
        'default_value' => '',
    ),
    'percent_range' => array (
        'type' => 'input',
        'unset_empty' => true,
    ),
    'similar_category' => array (
        'type' => 'checkbox',
        'default_value' => 'Y'
    ),
    'similar_subcats' => array (
        'type' => 'checkbox',
        'default_value' => 'Y'
    ),
    'cp_similar_in_stock' => array (
        'type' => 'checkbox',
        'default_value' => 'Y'
    ),
);

return $schema;