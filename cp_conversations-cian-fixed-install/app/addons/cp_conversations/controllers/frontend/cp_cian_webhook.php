<?php

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'incoming') {

    // Проверка секрета вебхука (если задан в настройках модуля) — защита публичного эндпоинта
    $secret = Registry::get('addons.cp_conversations.cian_webhook_secret');
    if (!empty($secret)) {
        $provided = isset($_REQUEST['secret']) ? $_REQUEST['secret'] : '';
        if (!is_string($provided) || !hash_equals((string) $secret, (string) $provided)) {
            header('HTTP/1.1 403 Forbidden');
            exit;
        }
    }

    // Страховка: создаём таблицу связи, если её ещё нет (на случай установки без переустановки аддона)
    $table_exists = db_get_field("SHOW TABLES LIKE ?l", Registry::get('config.table_prefix') . 'cp_conversations_cian_map');
    if (!$table_exists) {
        db_query(
            "CREATE TABLE IF NOT EXISTS `?:cp_conversations_cian_map` (
                `conversation_id` int(11) unsigned NOT NULL,
                `cian_chat_id` varchar(128) NOT NULL,
                PRIMARY KEY (`conversation_id`,`cian_chat_id`),
                KEY `cian_chat_id` (`cian_chat_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
        );
    }

    // Получаем сырое тело запроса (ЦИАН присылает JSON)
    $payload = file_get_contents('php://input');

    if (!empty($payload)) {
        $data = json_decode($payload, true);

        // Функции работы с ЦИАН подключены в init.php; подстраховываемся require_once
        if (!function_exists('fn_cp_conversations_cian_process_incoming')) {
            require_once Registry::get('config.dir.addons') . 'cp_conversations/func.cian.php';
        }

        if (is_array($data)) {
            fn_cp_conversations_cian_process_incoming($data);
        }
    }

    // Обязательно возвращаем HTTP 200 OK, чтобы ЦИАН счёл вебхук доставленным
    header('HTTP/1.1 200 OK');
    header('Content-Type: application/json');
    echo json_encode(array('status' => 'ok'));
    exit;
}
