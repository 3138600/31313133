<?php

$schema['central']['messages'] = array(
    'position' => 9000,
    'items' => array(
        'conversations' => array(
            'href' => 'conversations.manage',
            'position' => 100,
            'attrs' => array(
                'main' => array(
                    'data-unread-messages' => fn_cp_conversations_get_unread_messages(),
                ),
                'class'=>'is-addon'
            ),
        )
    )
);

return $schema;