<?php

use Tygh\Http;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * Отправка сообщения в чат ЦИАН
 *
 * @param string $cian_chat_id ID чата на стороне ЦИАН
 * @param string $message      Текст сообщения
 * @return bool|array Ответ от API ЦИАН
 */
function fn_cp_conversations_cian_send_message($cian_chat_id, $message)
{
    $token = Registry::get('addons.cp_conversations.cian_api_token');
    if (empty($token)) {
        return false;
    }

    $url = 'https://public-api.cian.ru/v1/send-message';

    $data = [
        'chatId' => (int)$cian_chat_id,
        'content' => [
            'text' => $message
        ]
    ];

    $headers = [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ];

    $response = Http::post($url, json_encode($data), [
        'headers' => $headers,
        'timeout' => 10
    ]);

    return json_decode($response, true);
}

/**
 * Обработка входящего сообщения от Webhook ЦИАН
 *
 * @param array $data Декодированный JSON от ЦИАН
 */
function fn_cp_conversations_cian_process_incoming($data)
{
    $cian_chat_id = isset($data['chatId']) ? $data['chatId'] : null;
    $text = isset($data['message']['text']) ? $data['message']['text'] : (isset($data['text']) ? $data['text'] : null);
    
    if (isset($data['events']) && is_array($data['events'])) {
        foreach($data['events'] as $event) {
             if (isset($event['chatId']) && isset($event['content']['text'])) {
                 fn_cp_conversations_cian_save_message_to_db($event['chatId'], $event['content']['text']);
             }
        }
        return;
    }

    if (empty($cian_chat_id) || empty($text)) {
        return;
    }
    
    fn_cp_conversations_cian_save_message_to_db($cian_chat_id, $text);
}

/**
 * Вспомогательная функция для сохранения сообщения
 */
function fn_cp_conversations_cian_save_message_to_db($cian_chat_id, $text) {
    $conversation_id = db_get_field("SELECT conversation_id FROM ?:cp_conversations_cian_map WHERE cian_chat_id = ?s", $cian_chat_id);

    if (!$conversation_id) {
        $conversation_data = [
            'status'     => 'A',
            'created_at' => time(),
            'type'       => 'cian' 
        ];
        
        $conversation_id = fn_cp_conversations_update_conversation($conversation_data, 0);
        
        if ($conversation_id) {
            db_query("INSERT INTO ?:cp_conversations_cian_map (conversation_id, cian_chat_id) VALUES (?i, ?s)", $conversation_id, $cian_chat_id);
        }
    }

    if ($conversation_id) {
        $message_data = [
            'conversation_id' => $conversation_id,
            'message'         => $text,
            'user_id'         => 0, 
            'timestamp'       => time(),
            'is_read'         => 'N'
        ];
        
        fn_cp_conversations_add_message($message_data);
    }
}

/**
 * Регистрация вебхука в ЦИАН
 * Вызывается автоматически при сохранении токена в настройках модуля
 * @param string $new_token Новый токен
 */
function fn_cp_conversations_cian_subscribe_webhook($new_token = '') {
    $token = !empty($new_token) ? $new_token : Registry::get('addons.cp_conversations.cian_api_token');
    
    if (empty($token)) {
        return false;
    }

    $url = 'https://public-api.cian.ru/v3/subscribe-webhooks';
    
    // Формируем правильный URL для вашего магазина
    $store_url = fn_url('cp_cian_webhook.incoming', 'C', 'current');
    // Если сайт использует HTTPS, принудительно ставим его, так как ЦИАН требует https
    $store_url = str_replace('http://', 'https://', $store_url);

    $data = [
        'url' => $store_url,
        // Подписываемся на входящие сообщения по объявлениям и чатам агентов
        'webhookTypes' => ['offersMessagesIncoming', 'agentMessagesIncoming'] 
    ];

    $headers = [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ];

    $response = Http::post($url, json_encode($data), [
        'headers' => $headers,
        'timeout' => 10
    ]);
    
    $result = json_decode($response, true);
    
    if (isset($result['operationId'])) {
        fn_set_notification('N', __('notice'), 'Вебхук ЦИАН успешно зарегистрирован: ' . $store_url);
        return true;
    } else {
        fn_set_notification('E', __('error'), 'Ошибка регистрации вебхука ЦИАН. Проверьте правильность токена.');
        return false;
    }
}
/**
 * Отправка нового сообщения в чат CIAN
 * * @param string|int $chat_id ID чата CIAN
 * @param string $message_text Текст сообщения
 * @return array [Успех выполнения (bool), Сообщение/Ответ (string)]
 */
function fn_cp_conversations_cian_send_message($chat_id, $message_text)
{
    // Получаем API ключ из настроек модуля
    $api_key = Registry::get('addons.cp_conversations.cian_api_key');
    
    if (empty($api_key)) {
        return [false, 'API ключ CIAN не задан в настройках модуля cp_conversations.'];
    }

    // Актуальный Endpoint из документации CIAN для отправки сообщения
    $url = '[https://public-api.cian.ru/v1/send-message](https://public-api.cian.ru/v1/send-message)'; 
    
    // Формируем тело запроса строго по документации CIAN
    $data = [
        'chatId' => (int)$chat_id,
        'content' => [
            'text' => $message_text
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    // Формируем заголовки. Авторизация Bearer Token
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key 
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    // CIAN возвращает 200 при успехе
    if ($http_code == 200) {
        return [true, 'Сообщение успешно отправлено в CIAN.'];
    } else {
        $error_msg = !empty($curl_error) ? $curl_error : $response;
        return [false, 'Ошибка отправки в CIAN (Код: ' . $http_code . '): ' . $error_msg];
    }
}