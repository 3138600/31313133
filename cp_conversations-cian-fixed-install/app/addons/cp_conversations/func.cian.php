<?php

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * Базовый адрес публичного API ЦИАН.
 */
if (!defined('CP_CIAN_API_BASE')) {
    define('CP_CIAN_API_BASE', 'https://public-api.cian.ru');
}

/**
 * Низкоуровневый запрос к API ЦИАН.
 *
 * @param string     $method  HTTP-метод (GET|POST)
 * @param string     $path    Путь, начиная с / (например, /v1/send-message)
 * @param array|null $payload Тело запроса (будет закодировано в JSON) или null
 * @param array      $query   GET-параметры
 * @return array [bool $ok, int $http_code, string $error, array|null $decoded]
 */
function fn_cp_conversations_cian_request($method, $path, $payload = null, $query = array())
{
    $token = Registry::get('addons.cp_conversations.cian_api_token');
    if (empty($token)) {
        return array(false, 0, 'Токен API ЦИАН не задан в настройках модуля cp_conversations.', null);
    }

    $url = CP_CIAN_API_BASE . $path;
    if (!empty($query)) {
        $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($query);
    }

    $headers = array('Authorization: Bearer ' . $token);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    if (strtoupper($method) === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            $headers[] = 'Content-Type: application/json';
        }
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response   = curl_exec($ch);
    $http_code  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    $decoded = json_decode($response, true);
    $ok      = ($http_code >= 200 && $http_code < 300);
    $error   = $ok ? '' : (!empty($curl_error) ? $curl_error : (string) $response);

    return array($ok, $http_code, $error, $decoded);
}

/**
 * Отправка текстового сообщения в чат ЦИАН.
 * Endpoint: POST /v1/send-message
 *
 * @param string|int $chat_id      ID чата ЦИАН
 * @param string     $message_text Текст сообщения
 * @return array [bool $success, string $message]
 */
function fn_cp_conversations_cian_send_message($chat_id, $message_text)
{
    list($ok, $code, $error, $data) = fn_cp_conversations_cian_request('POST', '/v1/send-message', array(
        'chatId'  => (int) $chat_id,
        'content' => array(
            'text' => $message_text,
        ),
    ));

    if ($ok) {
        return array(true, 'Сообщение успешно отправлено в ЦИАН.');
    }

    if ($code === 0 && !empty($error)) {
        return array(false, $error);
    }

    return array(false, 'Ошибка отправки в ЦИАН (код: ' . $code . '): ' . $error);
}

/**
 * Пометить чат ЦИАН прочитанным.
 * Endpoint: POST /v1/read-chat
 *
 * @param string|int $chat_id ID чата ЦИАН
 * @return bool
 */
function fn_cp_conversations_cian_read_chat($chat_id)
{
    list($ok) = fn_cp_conversations_cian_request('POST', '/v1/read-chat', array(
        'chatId' => (int) $chat_id,
    ));

    return $ok;
}

/**
 * Получить информацию по чату ЦИАН.
 * Endpoint: GET /v1/get-chat
 *
 * @param string|int $chat_id ID чата ЦИАН
 * @return array|null Данные чата (result.chat) или null
 */
function fn_cp_conversations_cian_get_chat($chat_id)
{
    list($ok, $code, $error, $data) = fn_cp_conversations_cian_request('GET', '/v1/get-chat', null, array(
        'chatId' => (int) $chat_id,
    ));

    if ($ok && isset($data['result']['chat']) && is_array($data['result']['chat'])) {
        return $data['result']['chat'];
    }

    return null;
}

/**
 * Сформировать публичный URL вебхука магазина для регистрации в ЦИАН.
 * Строит адрес по обычному (http) хосту магазина и принудительно поднимает схему до https,
 * чтобы не получить пустой https-хост на магазинах без настроенного защищённого соединения.
 *
 * @return string
 */
function fn_cp_conversations_cian_webhook_url()
{
    $url = fn_url('cp_cian_webhook.incoming', 'C', 'http');

    // Если по какой-то причине вернулся относительный адрес — достроим абсолютный из настроек
    if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
        $host = Registry::get('config.current_host');
        if (empty($host)) {
            $host = Registry::get('config.http_host');
        }
        $path = Registry::get('config.current_path');
        if (empty($path)) {
            $path = Registry::get('config.http_path');
        }
        $url = 'http://' . $host . $path . '/index.php?dispatch=cp_cian_webhook.incoming';
    }

    // Принудительно https (ЦИАН принимает только https)
    $url = preg_replace('#^http://#i', 'https://', $url);

    $secret = Registry::get('addons.cp_conversations.cian_webhook_secret');
    if (!empty($secret)) {
        $url .= (strpos($url, '?') === false ? '?' : '&') . 'secret=' . urlencode($secret);
    }

    return $url;
}

/**
 * Регистрация (подписка) вебхука в ЦИАН.
 * Endpoint: POST /v3/subscribe-webhooks
 * Вызывается автоматически после сохранения токена (см. fn_settings_actions_addons_cp_conversations).
 *
 * @param string $new_token Новый токен (если не задан — берётся из настроек)
 * @return bool
 */
function fn_cp_conversations_cian_subscribe_webhook($new_token = '')
{
    // Если токен ещё не сохранён в реестре (момент сохранения настроек) — временно подставим переданный
    if (!empty($new_token)) {
        Registry::set('addons.cp_conversations.cian_api_token', $new_token);
    }

    $token = Registry::get('addons.cp_conversations.cian_api_token');
    if (empty($token)) {
        return false;
    }

    // URL вебхука нашего магазина (надёжно: реальный хост + принудительный https)
    $store_url = fn_cp_conversations_cian_webhook_url();

    list($ok, $code, $error, $data) = fn_cp_conversations_cian_request('POST', '/v3/subscribe-webhooks', array(
        'url'          => $store_url,
        // Подписываемся на все входящие типы сообщений (без исходящих — чтобы не получать эхо своих ответов)
        'webhookTypes' => array(
            'offersMessagesIncoming',
            'inquiryOffersMessagesIncoming',
            'newbuildingMessagesIncoming',
            'agentMessagesIncoming',
        ),
    ));

    if ($ok && isset($data['operationId'])) {
        fn_set_notification('N', __('notice'), 'Вебхук ЦИАН успешно зарегистрирован: ' . $store_url);
        return true;
    }

    fn_set_notification('E', __('error'), 'Ошибка регистрации вебхука ЦИАН (код: ' . $code . '). ' . $error);
    return false;
}

/**
 * Проверка, является ли массив списком (последовательные числовые ключи с 0).
 *
 * @param array $array
 * @return bool
 */
function fn_cp_conversations_cian_is_list($array)
{
    if (!is_array($array)) {
        return false;
    }
    if (function_exists('array_is_list')) {
        return array_is_list($array);
    }
    $i = 0;
    foreach ($array as $k => $v) {
        if ($k !== $i++) {
            return false;
        }
    }
    return true;
}

/**
 * Достать вложенное значение по пути ключей.
 *
 * @param array $array
 * @param array $path  Например, array('content', 'text')
 * @return mixed|null
 */
function fn_cp_conversations_cian_dig($array, $path)
{
    $value = $array;
    foreach ($path as $key) {
        if (is_array($value) && array_key_exists($key, $value)) {
            $value = $value[$key];
        } else {
            return null;
        }
    }
    return $value;
}

/**
 * Нормализовать произвольную структуру вебхука ЦИАН в список пар [chatId, text].
 * Вебхук может содержать одно или несколько сообщений (см. документацию ЦИАН),
 * поэтому поддерживаем разные обёртки и расположения полей.
 *
 * @param array $data Декодированный JSON вебхука
 * @return array Массив пар array(array($chat_id, $text), ...)
 */
function fn_cp_conversations_cian_extract_messages($data)
{
    $pairs = array();
    if (!is_array($data)) {
        return $pairs;
    }

    // Определяем контейнер со списком сообщений
    if (isset($data['events']) && is_array($data['events'])) {
        $items = $data['events'];
    } elseif (isset($data['messages']) && is_array($data['messages'])) {
        $items = $data['messages'];
    } elseif (isset($data['result']['messages']) && is_array($data['result']['messages'])) {
        $items = $data['result']['messages'];
    } elseif (fn_cp_conversations_cian_is_list($data)) {
        $items = $data;
    } else {
        $items = array($data); // одиночный объект
    }

    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }

        // ID чата (разные возможные расположения)
        $chat_id = fn_cp_conversations_cian_dig($item, array('chatId'));
        if ($chat_id === null) { $chat_id = fn_cp_conversations_cian_dig($item, array('chat_id')); }
        if ($chat_id === null) { $chat_id = fn_cp_conversations_cian_dig($item, array('chat', 'chatId')); }
        if ($chat_id === null) { $chat_id = fn_cp_conversations_cian_dig($item, array('chat', 'id')); }

        // Текст сообщения (приоритет — content.text по документации)
        $text = fn_cp_conversations_cian_dig($item, array('content', 'text'));
        if ($text === null) { $text = fn_cp_conversations_cian_dig($item, array('message', 'content', 'text')); }
        if ($text === null) { $text = fn_cp_conversations_cian_dig($item, array('message', 'text')); }
        if ($text === null) { $text = fn_cp_conversations_cian_dig($item, array('text')); }

        if ($chat_id !== null && is_string($text) && $text !== '') {
            $pairs[] = array((string) $chat_id, $text);
        }
    }

    return $pairs;
}

/**
 * Обработка входящего вебхука ЦИАН: сохраняем все полученные сообщения.
 *
 * @param array $data Декодированный JSON вебхука
 */
function fn_cp_conversations_cian_process_incoming($data)
{
    $pairs = fn_cp_conversations_cian_extract_messages($data);
    foreach ($pairs as $pair) {
        fn_cp_conversations_cian_save_message_to_db($pair[0], $pair[1]);
    }
}

/**
 * Сохранение входящего сообщения ЦИАН в систему диалогов.
 * При отсутствии связанного диалога создаёт новый по схеме базового модуля,
 * пытается задать осмысленную тему через get-chat и назначает диалог администраторам.
 *
 * @param string|int $cian_chat_id ID чата ЦИАН
 * @param string     $text         Текст сообщения
 * @return int|bool ID созданного сообщения или false
 */
function fn_cp_conversations_cian_save_message_to_db($cian_chat_id, $text)
{
    if (empty($cian_chat_id) || !is_string($text) || $text === '') {
        return false;
    }

    $conversation_id = db_get_field("SELECT conversation_id FROM ?:cp_conversations_cian_map WHERE cian_chat_id = ?s", $cian_chat_id);

    if (empty($conversation_id)) {
        // Пытаемся получить осмысленную тему диалога из данных чата ЦИАН
        $subject = 'CIAN #' . $cian_chat_id;
        $chat = fn_cp_conversations_cian_get_chat($cian_chat_id);
        if (is_array($chat)) {
            $title = fn_cp_conversations_cian_dig($chat, array('offer', 'title'));
            if ($title === null) { $title = fn_cp_conversations_cian_dig($chat, array('object', 'title')); }
            if (is_string($title) && $title !== '') {
                $subject = function_exists('mb_substr') ? mb_substr('CIAN: ' . $title, 0, 255) : substr('CIAN: ' . $title, 0, 255);
            }
        }

        $conversation_id = db_query("INSERT INTO ?:cp_conversations ?e", array(
            'subject'   => $subject,
            'timestamp' => time(),
            'author_id' => 0,
            'order_id'  => 0,
        ));

        if (empty($conversation_id)) {
            return false;
        }

        db_query(
            "INSERT INTO ?:cp_conversations_cian_map (conversation_id, cian_chat_id) VALUES (?i, ?s)",
            $conversation_id, $cian_chat_id
        );

        // Назначаем диалог администраторам, чтобы он отображался во «Входящих»
        $admin_ids = db_get_fields("SELECT user_id FROM ?:users WHERE cp_for_conversation = ?s", 'Y');
        if (empty($admin_ids)) {
            $admin_ids = db_get_fields("SELECT user_id FROM ?:users WHERE user_type = ?s AND is_root = ?s", 'A', 'Y');
        }
        foreach ($admin_ids as $admin_id) {
            db_query("REPLACE INTO ?:cp_conversation_users ?e", array(
                'conversation_id' => $conversation_id,
                'recipient_id'    => $admin_id,
                'read'            => 'N',
            ));
        }
    }

    // user_id = 0 => входящее от ЦИАН; fn_cp_conversations_add_message не пересылает его обратно
    return fn_cp_conversations_add_message(array(
        'conversation_id' => $conversation_id,
        'user_id'         => 0,
        'message'         => $text,
        'timestamp'       => time(),
    ));
}
