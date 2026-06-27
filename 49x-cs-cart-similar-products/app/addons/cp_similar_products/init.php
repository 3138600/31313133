<?php

if (!defined('BOOTSTRAP')) { die('Access denied'); }

fn_register_hooks(
   'get_products_pre',
   'get_products'
);