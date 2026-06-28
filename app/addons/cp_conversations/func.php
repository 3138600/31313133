<?php

if(!defined('BOOTSTRAP')) { die('Access denied'); }
use Tygh\Registry;
use Tygh\Storage;

//hooks
function fn_cp_conversations_get_order_info (&$order, $additional_data) {
    if (!empty($order) && !empty($order['order_id'])) {
        $order['exst_conversations'] = db_get_field("SELECT conversation_id FROM ?:cp_conversations WHERE order_id = ?i", $order['order_id']);
//         if (!empty($order['company_id'])) {
//             $order['cp_company_name'] = fn_get_company_name($order['company_id']);
//         }
    }
}
function fn_cp_conversations_update_profile($action, $user_data, $current_user_data) {
    if(!empty($user_data['user_id'])) {
        fn_attach_image_pairs('user_image', 'user_image', $user_data['user_id'], CART_LANGUAGE);
    }
}

function fn_cp_conversations_get_user_info($user_id, $get_profile, $profile_id, &$user_data) {
    if(!empty($user_id)) {
        $user_data['user_image'] = fn_get_image_pairs($user_id, 'user_image', 'M', true, true, CART_LANGUAGE);
    }
}

//functions
function fn_cp_conversations_add_message($message_data)
{
    if (empty($message_data['conversation_id'])) {
        return false;
    }

    $message = array(
        'conversation_id' => (int) $message_data['conversation_id'],
        'user_id'         => isset($message_data['user_id']) ? (int) $message_data['user_id'] : 0,
        'message'         => isset($message_data['message']) ? $message_data['message'] : '',
        'timestamp'       => !empty($message_data['timestamp']) ? (int) $message_data['timestamp'] : time(),
    );

    $message_id = db_query("INSERT INTO ?:cp_messages ?e", $message);

    if (!empty($message_id)) {
        // Прикрепление файлов, если они переданы как массив ['name' => ..., 'path' => ...]
        if (!empty($message_data['files']) && is_array($message_data['files'])) {
            foreach ($message_data['files'] as $file) {
                if (empty($file['name']) || empty($file['path'])) {
                    continue;
                }
                list($filesize, $filename) = Storage::instance('messages_files')->put($file['name'], array(
                    'file' => $file['path'],
                ));
                db_query("INSERT INTO ?:cp_conversation_message_files ?e", array(
                    'message_id' => $message_id,
                    'filename'   => $filename,
                ));
            }
        }

        // Помечаем диалог как непрочитанный для всех участников, кроме автора сообщения
        db_query(
            "UPDATE ?:cp_conversation_users SET `read` = ?s WHERE conversation_id = ?i AND recipient_id != ?i",
            'N', $message['conversation_id'], $message['user_id']
        );

        fn_set_hook('cp_conversations_add_message_post', $message_data, $message_id);

        // Отправка ответа в ЦИАН, если диалог привязан к чату ЦИАН.
        // user_id == 0 означает входящее сообщение из ЦИАН — его обратно не пересылаем.
        // skip_cian == true означает, что сообщение уже отправлено в ЦИАН вызывающим кодом.
        if (!empty($message['user_id']) && empty($message_data['skip_cian'])) {
            $cian_chat_id = db_get_field("SELECT cian_chat_id FROM ?:cp_conversations_cian_map WHERE conversation_id = ?i", $message['conversation_id']);
            if (!empty($cian_chat_id) && function_exists('fn_cp_conversations_cian_send_message')) {
                fn_cp_conversations_cian_send_message($cian_chat_id, $message['message']);
            }
        }
    }

    return $message_id;
}
function fn_cp_conversations_get_conversations($params) {
    $author_id = $_SESSION['auth']['user_id'];
    $default_params = array(
        'items_per_page' => Registry::get('settings.Appearance.admin_elements_per_page'),
        'page' => 1
    );
    $params = array_merge($default_params, $params);

    $condition = db_quote("1");

    $limit = db_paginate($params['page'], $params['items_per_page']);
    $company_id = Registry::get('runtime.company_id');

    $sortings = array(
        'timestamp' => 'message_timestamp'
    );
    $sorting = db_sort($params, $sortings, 'timestamp', 'desc');

    //get only customer's or vendor's converstions
    if(AREA == 'C') {
        $condition .= db_quote(" AND ?:cp_conversation_users.recipient_id = ?i", $author_id);
    } elseif(!empty($company_id)) {
        //check if I am root vendor
        if (fn_allowed_for('MULTIVENDOR')) {
            $is_root = db_get_field("SELECT user_id FROM ?:users WHERE user_id = ?i AND user_type = ?s AND is_root = ?s", $author_id, 'V', 'Y');
            if(!empty($is_root) || $author_id == 1) {
                $vendors_ids = db_get_fields("SELECT user_id FROM ?:users WHERE company_id = ?i AND user_type = ?s", $company_id, 'V');
                $condition .= db_quote(" AND ?:cp_conversation_users.recipient_id IN (?n)", $vendors_ids);
            } else {
                $condition .= db_quote(" AND ?:cp_conversation_users.recipient_id = ?i", $author_id);
            }
        } else {
            if (!empty($company_id)) {
                if ($author_id == 1) {
                    $avail_users = db_get_fields("SELECT user_id FROM ?:users WHERE cp_for_conversation = ?s AND company_id = ?i", 'Y', $company_id);
                    $condition .= db_quote(" AND ?:cp_conversation_users.recipient_id IN (?n)", $avail_users);
                } else {
                    $condition .= db_quote(" AND ?:cp_conversation_users.recipient_id = ?i", $author_id);
                }
            }
        }
    } else {
        //admin ablee to see all converaations
    }

    $having = '1';
    $fields = array(db_quote('SQL_CALC_FOUND_ROWS *'));

    if(!empty($params['q'])) {
        $conversation_ids = db_get_fields("SELECT conversation_id FROM ?:cp_messages WHERE message LIKE ?l", "%$params[q]%");
        $condition .= db_quote(" AND (subject LIKE(?l) OR ?:cp_conversations.conversation_id IN (?n))", "%$params[q]%", $conversation_ids);
        $params['folder'] = 'A';

    } elseif(!empty($params['folder_id'])) {
        if(!empty($params['folder'])) {
            unset($params['folder']);
        }
        $conversation_ids = db_get_fields("SELECT conversation_id FROM ?:cp_conversation_folder_links WHERE user_id = ?i AND folder_id = ?i", $author_id, $params['folder_id']);

        $condition .= db_quote(" AND ?:cp_conversations.conversation_id IN(?n) AND trash = ?s AND spam = ?s", $conversation_ids, 'N', 'N');

    } elseif(!empty($params['folder'])) {
        if($params['folder'] == 'I') {
            //inbox
            // $fields[] = db_quote("(SELECT user_id FROM ?:cp_messages WHERE ?:cp_messages.conversation_id = ?:cp_conversations.conversation_id ORDER BY timestamp DESC LIMIT 0, 1) as latest_user_id");
            // $having .= db_quote(" AND latest_user_id != ?i", $author_id);
            $bad_conversations = db_get_fields("SELECT conversation_id FROM ?:cp_conversation_users WHERE recipient_id = ?i AND (spam = ?s OR trash = ?s OR archive = ?s)", $author_id, 'Y', 'Y', 'Y');
            $condition .= db_quote(" AND ?:cp_conversations.conversation_id NOT IN(?n)", $bad_conversations);

        } elseif($params['folder'] == 'S') {
            //sent
            // $fields[] = db_quote("(SELECT user_id FROM ?:cp_messages WHERE ?:cp_messages.conversation_id = ?:cp_conversations.conversation_id ORDER BY timestamp DESC LIMIT 0, 1) as latest_user_id");
            // $having .= db_quote(" AND latest_user_id = ?i", $author_id);
            $answered_conversations = db_get_fields("SELECT conversation_id FROM ?:cp_messages WHERE user_id = ?i", $author_id);
            $condition .= db_quote(" AND ?:cp_conversations.conversation_id IN (?n)", $answered_conversations);

        } elseif($params['folder'] == 'A') {
            //all
            $bad_conversations = db_get_fields("SELECT conversation_id FROM ?:cp_conversation_users WHERE recipient_id = ?i AND (spam = ?s OR trash = ?s)", $author_id, 'Y', 'Y');
            $condition .= db_quote(" AND ?:cp_conversations.conversation_id NOT IN (?n)", $bad_conversations);

        } elseif($params['folder'] == 'U') {
            //unread
            $unread_conversations = db_get_fields("SELECT conversation_id FROM ?:cp_conversation_users WHERE recipient_id = ?i AND `read` = ?s", $author_id, 'N');
            $condition .= db_quote(" AND ?:cp_conversations.conversation_id IN (?n)", $unread_conversations);

        } elseif($params['folder'] == 'P') {
            //spam
            $spam_conversations = db_get_fields("SELECT conversation_id FROM ?:cp_conversation_users WHERE recipient_id = ?i AND spam = ?s", $author_id, 'Y');
            $condition .= db_quote(" AND ?:cp_conversations.conversation_id IN (?n)", $spam_conversations);

        } elseif($params['folder'] == 'T') {
            //trash
            $trash_conversations = db_get_fields("SELECT conversation_id FROM ?:cp_conversation_users WHERE recipient_id = ?i AND trash = ?s", $author_id, 'Y');
            $condition .= db_quote(" AND ?:cp_conversations.conversation_id IN (?n)", $trash_conversations);
        }
        //exclude spam, trash and archive conversations
        if(in_array($params['folder'], array('I', 'S', 'U'))) {
            $bad_conversations = db_get_fields("SELECT conversation_id FROM ?:cp_conversation_users WHERE recipient_id = ?i AND (spam = ?s OR trash = ?s)", $author_id, 'Y', 'Y');
            $condition .= db_quote(" AND ?:cp_conversations.conversation_id NOT IN(?n)", $bad_conversations);
        }
    }
    if(!empty($params['recipient_ids'])) {
        $conversation_ids = db_get_fields("SELECT conversation_id FROM ?:cp_conversation_users WHERE recipient_id IN (?n)", $params['recipient_ids']);
        $condition .= db_quote(" AND ?:cp_conversations.conversation_id IN (?n)", $conversation_ids);
    }

    //ability to order conversations by latest message date
    $fields[] = db_quote("(SELECT MAX(timestamp) FROM ?:cp_messages WHERE ?:cp_messages.conversation_id = ?:cp_conversations.conversation_id) as message_timestamp");

    $fields = implode(', ', $fields);

    $conversations = db_get_array("SELECT $fields FROM ?:cp_conversations 
        LEFT JOIN ?:cp_conversation_users ON ?:cp_conversations.conversation_id = ?:cp_conversation_users.conversation_id 
        WHERE $condition GROUP BY ?:cp_conversations.conversation_id HAVING $having $sorting $limit");
    $params['total_items'] = db_get_found_rows();
    if(!empty($conversations)) {
        foreach($conversations as &$conversation) {
            list($conversation['latest_messages'], $messages_params) = fn_cp_conversations_get_messages(array('limit' => 3, 'conversation_id' => $conversation['conversation_id'], 'group_by_time' => false));
            $conversation['messages_amount'] = $messages_params['total_items'];
            if(!empty($conversation['latest_messages'])) {
                $conversation['last_message'] = $conversation['latest_messages'][sizeof($conversation['latest_messages']) - 1];
            }
            $folder_ids = db_get_fields("SELECT folder_id FROM ?:cp_conversation_folder_links WHERE conversation_id = ?i AND user_id = ?i", $conversation['conversation_id'], $author_id);
            if(!empty($folder_ids)) {
                $conversation['folders'] = db_get_array("SELECT * FROM ?:cp_conversation_folders WHERE folder_id IN(?n)", $folder_ids);
            }
        }
    }
    return array($conversations, $params);
}

function fn_cp_conversations_get_conversation_data($conversation_id, $params = array()) {
    $author_id = $_SESSION['auth']['user_id'];
    if(empty($conversation_id)) {
        return array();
    }
    $conversation = db_get_row("SELECT * FROM ?:cp_conversations WHERE conversation_id = ?i", $conversation_id);
    if(!empty($conversation)) {
        $message_params = array(
            'conversation_id' => $conversation['conversation_id']
        );
        $message_params = array_merge($message_params, $params);
        $message_params['group_by_time'] = AREA == 'A';
        list($conversation['messages'], $conversation['messages_params']) = fn_cp_conversations_get_messages($message_params);
        $conversation['recipients'] = db_get_fields("SELECT recipient_id FROM ?:cp_conversation_users WHERE conversation_id = ?i", $conversation['conversation_id']);
        if(!empty($conversation['recipients'])) {
            $recipients_array = $names_array = array();
            foreach($conversation['recipients'] as $user_id) {
                $names_array[] = fn_get_user_name($user_id);
                if($_SESSION['auth']['user_id'] != $user_id) {
                    $recipients_array[] = fn_get_user_name($user_id);
                }
            }
            $conversation['formatted_usernames'] = implode(', ', $names_array);
            $conversation['formatted_recipients'] = implode(', ', $recipients_array);
        }
        $conversation['folders'] = db_get_fields("SELECT folder_id FROM ?:cp_conversation_folder_links WHERE conversation_id = ?i AND user_id = ?i", $conversation_id, $author_id);
    }
    return $conversation;
}

function fn_cp_conversations_get_messages($params) {
    static $user_images;
    static $user_names;
    if(empty($user_images)) {
        $user_images = array();
    }
    if(empty($user_names)) {
        $user_names = array();
    }
    $default_params = array(
        'items_per_page' => Registry::get('settings.Appearance.admin_elements_per_page'),
        'start' => 0,
        'page' => 1,
        'get_customer_images' => true,
        'get_message_images' => true,
        'group_by_time' => true,
        'group_for_customer' => false
    );
    $params = array_merge($default_params, $params);

    $limit = '';

    if(!empty($params['limit'])) {
        $limit = db_quote(" LIMIT 0, ?i", $params['limit']);

    } elseif(!empty($params['start'])) {
        $limit = db_quote(" LIMIT ?i, ?i", $params['start'], Registry::get('settings.Appearance.admin_elements_per_page'));

    } else {
        if(AREA == 'A') {
            $limit = db_paginate($params['page'], $params['items_per_page']);
        } 
    }

    $condition = db_quote(" 1");
    if(!empty($params['conversation_id'])) {
        $condition .= db_quote(" AND conversation_id = ?i", $params['conversation_id']);
    }

    $sortings = array(
        'timestamp' => '?:cp_messages.timestamp'
    );
    $sorting = db_sort($params, $sortings, 'timestamp', 'desc');

    $params['messages_amount'] = db_get_field("SELECT COUNT(*) FROM ?:cp_messages WHERE conversation_id = ?i", $params['conversation_id']);

    if($params['group_for_customer'] && $params['messages_amount'] > 3) {
        //in this case we shoul get one earlier messsage and two latest for conversation preview
        $earlier_messages = db_get_fields("SELECT message_id FROM ?:cp_messages WHERE conversation_id = ?i ORDER BY timestamp ASC LIMIT 0, 1", $params['conversation_id']);
        $latest_messages = db_get_fields("SELECT message_id FROM ?:cp_messages WHERE conversation_id = ?i ORDER BY timestamp DESC LIMIT 0, 2", $params['conversation_id']);
        $message_ids = array_merge($earlier_messages, $latest_messages);
        $limit = '';
        $condition .= db_quote(" AND message_id IN (?n)", $message_ids);
    } else {
        $params['group_for_customer'] = false;
    }

    $messages = db_get_array("SELECT SQL_CALC_FOUND_ROWS * FROM ?:cp_messages WHERE $condition $sorting $limit");

    $params['total_items'] = db_get_found_rows();
    //post processing
    if(!empty($messages)) {
        foreach($messages as &$message) {
            if(!empty($params['get_customer_images'])) {
                if(empty($user_images[$message['user_id']])) {
                    $user_images[$message['user_id']] = fn_get_image_pairs($message['user_id'], 'user_image', 'M', true, true, CART_LANGUAGE);
                }
                $message['user_image'] = $user_images[$message['user_id']];
            }
            if(!empty($params['get_message_images'])) {
                $files = db_get_array("SELECT item_id, filename FROM ?:cp_conversation_message_files WHERE message_id = ?i", $message['message_id']);
                if(!empty($files)) {
                    foreach($files as $file) {
                        $abs_path = Storage::instance('messages_files')->getAbsolutePath($file['filename']);
                        $rel_dir = str_replace('images/', '', fn_get_rel_dir($abs_path));
                        $message['files'][$file['item_id']] = array(
                            'url' => Storage::instance('messages_files')->getUrl($file['filename']),
                            'thumb' => fn_generate_thumbnail($rel_dir, 75, 75),
                            'is_pdf' => fn_get_file_ext($abs_path) == 'pdf' ? 'Y' : 'N'
                        );
                    }
                }
            }
            if(empty($user_names[$message['user_id']])) {
                $user_names[$message['user_id']] = fn_get_user_name($message['user_id']);
            }
            $message['user_name'] = $user_names[$message['user_id']];

            $message['humanized_time'] = fn_cp_conversations_convert_timestamp($message['timestamp']);
        }
        //group messages by time title
        if($params['group_by_time']) {
            foreach($messages as $k => $current_message) {
                if(empty($messages[$k + 1])) {
                    continue;
                }
                $next_message = $messages[$k + 1];
                if($current_message['user_id'] == $next_message['user_id'] && $current_message['humanized_time'] == $next_message['humanized_time']) {
                    unset($messages[$k + 1]['humanized_time']);
                }
            }
        }

    }
    return array(array_reverse($messages), $params);
}

function fn_cp_conversations_delete_conversation($conversation_id) {
    if(empty($conversation_id)) {
        return false;
    }
    $message_ids = db_get_fields("SELECT message_id FROM ?:cp_messages WHERE conversation_id = ?i", $conversation_id);
    if(!empty($message_ids)) {
        foreach($message_ids as $message_id) {
            fn_cp_conversations_delete_message($message_id);
        }
    }
    db_query("DELETE FROM ?:cp_conversation_users WHERE conversation_id = ?i", $conversation_id);
    db_query("DELETE FROM ?:cp_conversations WHERE conversation_id = ?i", $conversation_id);
}

function fn_cp_conversations_delete_message($message_id) {
    if(empty($message_id)) {
        return false;
    }
    fn_delete_image_pairs($message_id, 'message_images');
    db_query("DELETE FROM ?:cp_messages WHERE message_id = ?i", $message_id);
}

function fn_cp_conversations_convert_timestamp($timestamp) {
    //convert time stamp to humanized values
    $differ = time() - $timestamp;

    $second_in_minute = 60;
    $seconds_in_hour = $second_in_minute * 60;
    $seconds_in_day = $seconds_in_hour * 24;
    $seconds_in_week = $seconds_in_day * 7;

    if($differ < $second_in_minute) {
        return __('less_then_minutes');
    } elseif($differ < $seconds_in_hour) {
        return __('n_minutes_ago', array('[value]' => round($differ / $second_in_minute)));
    } elseif($differ < $seconds_in_day) {
        return __('n_hours_ago', array('[value]' => round($differ / $seconds_in_hour)));
    } elseif($differ < $seconds_in_week) {
        return __('n_days_ago', array('[value]' => round($differ / $seconds_in_day)));
    } else {
        $time_settings = Registry::get('settings.Appearance');
        //return date_format($timestamp, "$time_settings[date_format], $time_settings[time_format]");
        return fn_date_format($timestamp, $time_settings['date_format'].', '.$time_settings['time_format']);
    }
}

function fn_cp_conversations_get_unread_messages() {
    $unread = db_get_field("SELECT COUNT(*) FROM ?:cp_conversation_users WHERE `recipient_id` = ?i AND `read` = ?s", $_SESSION['auth']['user_id'], 'N');
    return $unread;
}

function fn_cp_conversations_generate_ekey() {
    $length = 64;
    $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $i = 0;
    $key = '';
    while($i < $length) {
        $key .= $alphabet[rand(0, strlen($alphabet) - 1)];
        $i++;
    }
    return $key;
}
/**
 * Обработчик info-настройки модуля (addon.xml -> notice_text).
 * Показывает администратору URL вебхука, который нужно указать в ЦИАН.
 *
 * @return string HTML-подсказка
 */
function fn_cp_conversations_info()
{
    $webhook_url = function_exists('fn_cp_conversations_cian_webhook_url')
        ? fn_cp_conversations_cian_webhook_url()
        : fn_url('cp_cian_webhook.incoming', 'C', 'https');

    return '<div class="control-group"><label class="control-label">URL вебхука ЦИАН:</label>'
        . '<div class="controls"><input type="text" class="input-large" readonly value="' . htmlspecialchars($webhook_url) . '">'
        . '<p class="muted description">Укажите токен API ЦИАН и сохраните настройки — вебхук будет зарегистрирован автоматически. '
        . 'При необходимости задайте этот адрес вручную в личном кабинете ЦИАН.</p></div></div>';
}

/**
 * Действие после сохранения настроек модуля.
 * CS-Cart вызывает fn_settings_actions_addons_<addon_id> после сохранения настроек аддона.
 * Используем для автоматической регистрации вебхука в ЦИАН при заданном токене.
 *
 * @param array  $new_settings Новые значения настроек
 * @param string $action       Тип действия
 */
function fn_settings_actions_addons_cp_conversations($new_settings, $action = '')
{
    $token = '';
    if (is_array($new_settings) && !empty($new_settings['cian_api_token'])) {
        $token = $new_settings['cian_api_token'];
    } else {
        $token = Registry::get('addons.cp_conversations.cian_api_token');
    }

    if (!empty($token) && function_exists('fn_cp_conversations_cian_subscribe_webhook')) {
        fn_cp_conversations_cian_subscribe_webhook($token);
    }
}
