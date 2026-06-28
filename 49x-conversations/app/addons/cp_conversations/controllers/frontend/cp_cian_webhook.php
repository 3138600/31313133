<?php

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'incoming') {
    // Автоматическое создание таблицы связи при первом запуске вебхука
    $table_exists = db_get_field("SHOW TABLES LIKE '?:cp_conversations_cian_map'");
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

    // Получаем сырые данные из тела запроса (ЦИАН присылает JSON)
    $payload = file_get_contents('php://input');
    
    if (!empty($payload)) {
        $data = json_decode($payload, true);
        
        // Подключаем функции работы с ЦИАН
        require_once Registry::get('config.dir.addons') . 'cp_conversations/func.cian.php';
        
        if (is_array($data)) {
            // Передаем данные в функцию обработки
            fn_cp_conversations_cian_process_incoming($data);
        }
    }

    // Обязательно возвращаем HTTP 200 OK, чтобы ЦИАН понял, что вебхук доставлен
    header("HTTP/1.1 200 OK");
    header("Content-Type: application/json");
    echo json_encode(['status' => 'ok']);
    exit;
}