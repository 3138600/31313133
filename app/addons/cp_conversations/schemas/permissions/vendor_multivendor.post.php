<?php

$schema['controllers']['conversations'] = array (
    'modes' => array(
        'add' => array(
            'permissions' => true
        ),
        'update' => array(
            'permissions' => true
        ),
        'delete' => array(
            'permissions' => false
        ),
        'm_delete' => array(
            'permissions' => false
        ),
    ),
    'permissions' => true,
);

return $schema;
