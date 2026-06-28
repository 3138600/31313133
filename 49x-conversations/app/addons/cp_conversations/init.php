<?php

if(!defined('BOOTSTRAP')) { die('Access denied'); }

use Tygh\Registry;
use Tygh\Storage;

fn_register_hooks(
	'update_profile',
	'get_user_info',
	'get_order_info'
);

Registry::set('config.storage.messages_files', array(
    'prefix' => 'messages_files',
    'secured' => true,
    'dir' => Registry::get('config.dir.root') . '/images/'
));