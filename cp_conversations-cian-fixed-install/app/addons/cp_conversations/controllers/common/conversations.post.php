<?php

if(!defined('BOOTSTRAP')) { die('Access denied'); }

use Tygh\Registry;
use Tygh\Storage;

if (empty($_SESSION['auth']['user_id'])) {
    $url = !empty($_REQUEST['return_url']) ? $_REQUEST['return_url'] : Registry::get('config.current_url');
    if(defined('AJAX_REQUEST')) {
        Tygh::$app['ajax']->assign('force_redirection', fn_url("auth.login_form?return_url=" . urlencode($url)));
    }
    return array(CONTROLLER_STATUS_REDIRECT, fn_url("auth.login_form?return_url=" . urlencode($url)));
}

$author_id = $_SESSION['auth']['user_id'];
$params = $_REQUEST;

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if($mode == 'send_new_message') {

        // === Отправка в ЦИАН по выбранному виртуальному получателю "Чат ЦИАН #ID" ===
        $cp_recipient_raw = isset($params['conversation_data']['recipient_id']) ? trim($params['conversation_data']['recipient_id']) : '';
        if (preg_match('/^cian_(\d+)$/', $cp_recipient_raw, $cp_m) && function_exists('fn_cp_conversations_cian_send_message')) {
            $cian_chat_id = $cp_m[1];
            $cian_message = isset($params['conversation_data']['message']) ? $params['conversation_data']['message'] : '';

            if (!fn_string_not_empty($cian_message)) {
                fn_set_notification('E', __('error'), __('type_your_reply'));
                return array(CONTROLLER_STATUS_REDIRECT, fn_url('conversations.new&recipient_id=cian_' . $cian_chat_id));
            }

            // 1) Отправляем сообщение в ЦИАН
            list($cian_ok, $cian_info) = fn_cp_conversations_cian_send_message($cian_chat_id, $cian_message);
            if (!$cian_ok) {
                fn_set_notification('E', __('error'), $cian_info);
                return array(CONTROLLER_STATUS_REDIRECT, fn_url('conversations.new&recipient_id=cian_' . $cian_chat_id));
            }

            // 2) Находим или создаём связанный локальный диалог
            $conversation_id = db_get_field("SELECT conversation_id FROM ?:cp_conversations_cian_map WHERE cian_chat_id = ?s", $cian_chat_id);
            if (empty($conversation_id)) {
                $subject = !empty($params['conversation_data']['subject'])
                    ? $params['conversation_data']['subject']
                    : ('CIAN #' . $cian_chat_id);

                $conversation_id = db_query("INSERT INTO ?:cp_conversations ?e", array(
                    'subject'   => $subject,
                    'timestamp' => time(),
                    'author_id' => $author_id,
                    'order_id'  => 0,
                ));
                db_query("INSERT INTO ?:cp_conversations_cian_map (conversation_id, cian_chat_id) VALUES (?i, ?s)", $conversation_id, $cian_chat_id);

                // Участники диалога: автор + администраторы, отмеченные для диалогов
                $admin_ids = db_get_fields("SELECT user_id FROM ?:users WHERE cp_for_conversation = ?s", 'Y');
                $admin_ids[] = $author_id;
                foreach (array_unique($admin_ids) as $admin_id) {
                    db_query("REPLACE INTO ?:cp_conversation_users ?e", array(
                        'conversation_id' => $conversation_id,
                        'recipient_id'    => $admin_id,
                        'read'            => ($admin_id == $author_id) ? 'Y' : 'N',
                    ));
                }
            }

            // 3) Сохраняем исходящее сообщение локально без повторной отправки в ЦИАН
            fn_cp_conversations_add_message(array(
                'conversation_id' => $conversation_id,
                'user_id'         => $author_id,
                'message'         => $cian_message,
                'timestamp'       => time(),
                'skip_cian'       => true,
            ));

            fn_set_notification('N', __('notice'), __('message_has_been_sent'));
            $_mode = AREA == 'C' ? 'list' : 'update';
            return array(CONTROLLER_STATUS_OK, fn_url("conversations.$_mode&conversation_id=$conversation_id"));
        }
        // === Конец блока ЦИАН ===

        if(empty($params['conversation_id'])) {
            //create new conversation
            $data = array(
                'timestamp' => time(),
                'author_id' => $author_id,
                'subject' => $params['conversation_data']['subject']
            );
            if (!empty($params['conversation_data']['order_id'])) {
                $data['order_id'] = $params['conversation_data']['order_id'];
            }
            $params['conversation_id'] = db_query("INSERT INTO ?:cp_conversations ?e", $data);

            //assign selected users to created conversation
            foreach(array($author_id, $params['conversation_data']['recipient_id']) as $_id) {
                $data = array(
                    'conversation_id' => $params['conversation_id'],
                    'recipient_id' => $_id
                );
                db_query("REPLACE INTO ?:cp_conversation_users ?e", $data);
            }
            $cp_is_new_convers = true;
        }

        //create new message and assign it to selected conversation
        $data = array(
            'conversation_id' => $params['conversation_id'],
            'user_id' => $author_id,
            'message' => $params['conversation_data']['message'],
            'timestamp' => time()
        );
        $message_id = db_query("INSERT INTO ?:cp_messages ?e", $data);

        //attach images or files
        if(!empty($message_id)) {
            if(AREA == 'C') {
                if(!empty($_FILES['message_files'])) {
                    $files = array();
                    foreach($_FILES['message_files']['tmp_name'] as $k => $file) {
                        $files[] = array(
                            'path' => $file,
                            'name' => $_FILES['message_files']['name'][$k]
                        );
                    }
                }

            } else {
                $files = fn_filter_uploaded_data('message_files');
            }
            if(!empty($files)) {
                foreach($files as $file) {
                    list($filesize, $filename) = Storage::instance('messages_files')->put($file['name'], array(
                        'file' => $file['path']
                    ));
                    $udata = array(
                        'message_id' => $message_id,
                        'filename' => $filename
                    );
                    db_query("INSERT INTO ?:cp_conversation_message_files ?e", $udata);
                }
            }

            //send email fo all users assigned t this conversation
            if (empty($cp_is_new_convers)) {
                $user_ids = db_get_fields("SELECT recipient_id FROM ?:cp_conversation_users WHERE conversation_id = ?i AND `read` = ?s AND recipient_id != ?i", $params['conversation_id'], 'Y', $author_id);
            } else {
                $user_ids = db_get_fields("SELECT recipient_id FROM ?:cp_conversation_users WHERE conversation_id = ?i AND recipient_id != ?i", $params['conversation_id'], $author_id);
            }
            if(!empty($user_ids)) {
                foreach($user_ids as $user_id) {
                    $user_data = db_get_row("SELECT email, company_id FROM ?:users WHERE user_id = ?i AND user_type = ?s", $user_id, 'C');
                    if(empty($user_data)) {
                        continue;
                    }
                    $ekey = fn_cp_conversations_generate_ekey();
                    $udata = array(
                        'ekey' => $ekey
                    );
                    db_query("UPDATE ?:cp_conversation_users SET ?u WHERE conversation_id = ?i AND recipient_id = ?i", $udata, $params['conversation_id'], $user_id);
                    $mailer = Tygh::$app['mailer'];
//CP Tier
                    if (!empty($user_data['company_id'])) {
                        $link = fn_url('conversations.answer&ekey=' . $ekey . '&user_id=' . $user_id . '&conversation_id=' . $params['conversation_id'] . '&company_id=' . $user_data['company_id'], 'C');
                    } else {
                        $link = fn_url("conversations.answer&ekey=$ekey&user_id=$user_id&conversation_id=$params[conversation_id]", 'C');
                    }
//
                    $mailer->send(array(
                        'to' => $user_data['email'],
                        'from' => 'default_company_orders_department',
                        'data' => array(
                            'mess' => __('use_following_link_to_check_your_inbox'),
                            'link' => $link
                        ),
                        'tpl' => 'addons/cp_conversations/new_message.tpl',
                        'company_id' => $user_data['company_id'],
                    ), 'C', CART_LANGUAGE);
                }
            }

            //mark this conversation as unread for all users but author
            $udata = array('read' => 'N');
            db_query("UPDATE ?:cp_conversation_users SET ?u WHERE conversation_id = ?i AND recipient_id != ?i", $udata, $params['conversation_id'], $author_id);
            $udata = array('read' => 'Y');
            db_query("UPDATE ?:cp_conversation_users SET ?u WHERE conversation_id = ?i AND recipient_id = ?i", $udata, $params['conversation_id'], $author_id);

            fn_set_notification('N', __('notice'), __('message_has_been_sent'));
        }
        $_mode = AREA == 'C' ? 'list' : 'update';
        return array(CONTROLLER_STATUS_OK, fn_url("conversations.$_mode&conversation_id=$params[conversation_id]"));

    } elseif($mode == 'mass_update') {
        if(!empty($params['conversation_ids'])) {
            if($action == 'move_to_spam') {
                //unmark all selected conversations before moving
                $udata = array('spam' => 'N', 'archive' => 'N', 'trash'=> 'N');
                db_query("UPDATE ?:cp_conversation_users SET ?u WHERE recipient_id = ?i AND conversation_id IN (?n)", $udata, $author_id, $params['conversation_ids']);
                //delete selected conversations from all folders
                db_query("DELETE FROM ?:cp_conversation_folder_links WHERE user_id = ?i AND conversation_id IN (?n)", $author_id, $params['conversation_id']);
                //move to spam
                $udata = array('spam' => 'Y');
                db_query("UPDATE ?:cp_conversation_users SET ?u WHERE recipient_id = ?i AND conversation_id IN (?n)", $udata, $author_id, $params['conversation_ids']);
                fn_set_notification('N', __('notice'), __('marked_as_spam'));

            } elseif($action == 'move_to_archive') {
                //unmark all selected conversations before moving
                $udata = array('spam' => 'N', 'archive' => 'N', 'trash'=> 'N');
                db_query("UPDATE ?:cp_conversation_users SET ?u WHERE recipient_id = ?i AND conversation_id IN (?n)", $udata, $author_id, $params['conversation_ids']);
                //move to spam
                $udata = array('archive' => 'Y');
                db_query("UPDATE ?:cp_conversation_users SET ?u WHERE recipient_id = ?i AND conversation_id IN (?n)", $udata, $author_id, $params['conversation_ids']);
                fn_set_notification('N', __('notice'), __('successfully_archived'));

            } elseif($action == 'move_to_trash') {
                //unmark all selected conversations before moving
                $udata = array('spam' => 'N', 'archive' => 'N', 'trash'=> 'N');
                db_query("UPDATE ?:cp_conversation_users SET ?u WHERE recipient_id = ?i AND conversation_id IN (?n)", $udata, $author_id, $params['conversation_ids']);
                //remove selected conversations from all folders
                db_query("DELETE FROM ?:cp_conversation_folder_links WHERE user_id = ?i AND conversation_id IN (?n)", $author_id, $params['conversation_ids']);
                //move to spam
                $udata = array('trash' => 'Y');
                db_query("UPDATE ?:cp_conversation_users SET ?u WHERE recipient_id = ?i AND conversation_id IN (?n)", $udata, $author_id, $params['conversation_ids']);
                fn_set_notification('N', __('notice'), __('successfully_moved_to_trash'));

            } elseif($action == 'remove_from_folder') {
                //remove selected conversations from folder
                if(!empty($dispatch_extra)) {
                    $folder_id = $dispatch_extra;
                    db_query("DELETE FROM ?:cp_conversation_folder_links WHERE folder_id = ?i AND user_id = ?i AND conversation_id IN (?n)", $folder_id, $author_id, $params['conversation_ids']);
                    fn_set_notification('N', __('notice'), __('deleted_from_folder'));
                }

            } elseif($action == 'move_to_inbox') {
                //unmark all selected conversations before moving
                $udata = array('spam' => 'N', 'archive' => 'N', 'trash'=> 'N');
                db_query("UPDATE ?:cp_conversation_users SET ?u WHERE recipient_id = ?i AND conversation_id IN (?n)", $udata, $author_id, $params['conversation_ids']);
                fn_set_notification('N', __('notice'), __('successfully_moved_to_inbox'));

            } elseif($action == 'mark_as_unread') {
                //unmark all selected conversations before moving
                $udata = array('read' => 'N');
                db_query("UPDATE ?:cp_conversation_users SET ?u WHERE recipient_id = ?i AND conversation_id IN (?n)", $udata, $author_id, $params['conversation_ids']);
                fn_set_notification('N', __('notice'), __('marked_as_unread'));

            } elseif($action == 'move_to_folder' || $action == 'add_to_folder') {
                if(!empty($params['folder_ids']) || !empty($params['new_folder'])) {
                    $folder_ids = !empty($params['folder_ids']) ? $params['folder_ids'] : array();
                    if(!empty($params['new_folder'])) {
                        //create new folder and move selected conersations
                        $udata = array(
                            'folder' => $params['new_folder'],
                            'user_id' => $author_id
                        );
                        $folder_ids[] = db_query("INSERT INTO ?:cp_conversation_folders ?e", $udata);
                    }
                    if($action == 'move_to_folder') {
                        //if we moving conversations (not adding) we should add it to archive, so it will disapear from inbox
                        $udata = array('archive' => 'Y');
                        db_query("UPDATE ?:cp_conversation_users SET ?u WHERE recipient_id = ?i AND conversation_id IN (?n)", $udata, $author_id, $params['conversation_ids']);
                    }
                    foreach($params['conversation_ids'] as $conversation_id) {
                        //move converations into folders
                        foreach($folder_ids as $folder_id) {
                            $udata = array(
                                'folder_id' => $folder_id,
                                'conversation_id' => $conversation_id,
                                'user_id' => $author_id
                            );
                            db_query("INSERT INTO ?:cp_conversation_folder_links ?e", $udata);
                        }
                    }
                    fn_set_notification('N', __('notice'), __('conversations_successfully_moved'));
                }

            } elseif($action == 'mark_as_read') {
                $udata = array('read' => 'Y');
                db_query("UPDATE ?:cp_conversation_users SET ?u WHERE recipient_id = ?i AND conversation_id IN(?n)", $udata, $author_id, $params['conversation_ids']);
                fn_set_notification('N', __('notice'), __('marked_as_read'));

            } elseif($action == 'delete') {
                foreach($params['conversation_ids'] as $id) {
                    fn_cp_conversations_delete_conversation($id);
                    fn_set_notification('N', __('notice'), __('conversation_has_been_deleted'));
                }
            }

        } elseif($action == 'delete_folder') {
            if(!empty($params['folder_id'])) {
                db_query("DELETE FROM ?:cp_conversation_folders WHERE folder_id = ?i", $params['folder_id']);
                db_query("DELETE FROM ?:cp_conversation_folder_links WHERE folder_id = ?i", $params['folder_id']);
                fn_set_notification('N', __('notice'), __('folder_has_been_deleted'));
            }
            $_mode = AREA == 'C' ? 'list' : 'manage';
            return array(CONTROLLER_STATUS_OK, fn_url($url));
        }
        if(AREA == 'C') {
            if(!empty($params['redirect_url'])) {
                $url = $params['redirect_url'];

            } elseif(!empty($params['folder']) || !empty($params['folder_id'])) {
                $url = 'conversations.list';
                !empty($params['folder']) && $url .= "&folder=$params[folder]";
                !empty($params['folder_id']) && $url .= "&folder_id=$params[folder_id]";

            } elseif(!empty($params['conversation_id'])) {
                $url = "conversations.view&conversation_id=$params[conversation_id]";

            } else {
                $url = "conversations.list";
            }
        } else {
            $url = "conversations.manage";
        }
        return array(CONTROLLER_STATUS_OK, fn_url($url));
    }
} 

if($mode == 'new') {
    $result = array();

    // === Виртуальный получатель "Чат ЦИАН #ID" выбран — отдаём форму сразу ===
    if (!empty($params['recipient_id']) && is_string($params['recipient_id']) && preg_match('/^cian_(\d+)$/', $params['recipient_id'], $cp_cm)) {
        $result['recipient_id']   = $params['recipient_id'];
        $result['recipient_name'] = 'Чат ЦИАН #' . $cp_cm[1];
        $result['cp_skip_delete_rec'] = true;
        Registry::get('view')->assign('search_result', $result);
        Registry::get('view')->display('addons/cp_conversations/components/new_conversation.tpl');
        exit;
    }
    // === Конец блока ЦИАН ===

    if(!empty($params['q']) && fn_string_not_empty($params['q'])) {
        $q = $params['q'];
        
        if(AREA == 'A') {
            $arr = fn_explode(' ', $q);
            foreach ($arr as $k => $v) {
                if (!fn_string_not_empty($v)) {
                    unset($arr[$k]);
                }
            }
            $like_expression = ' (';
            $search_string = '%' . trim($q) . '%';
            if (sizeof($arr) == 2) {
                $like_expression .= db_quote('?:users.firstname LIKE ?l', '%' . array_shift($arr) . '%');
                $like_expression .= db_quote(' AND ?:users.lastname LIKE ?l', '%' . array_shift($arr) . '%');
            } else {
                $like_expression .= db_quote('?:users.firstname LIKE ?l', $search_string);
                $like_expression .= db_quote(' OR ?:users.lastname LIKE ?l', $search_string);
                $like_expression .= db_quote(' OR ?:users.email LIKE ?l', $search_string);//CP Tier
            }
            $like_expression .= ')';
            $condition = $like_expression;
            
            //$condition = db_quote("(firstname LIKE ?l OR lastname LIKE ?l)", "%$q%", "%$q%");
            $setting = Registry::get('addons.cp_conversations.allow_conversations_with_admin');
            if($setting == 'N') {
                $condition .= db_quote(" AND user_type != ?s", 'A');
            }
            if(fn_allowed_for('MULTIVENDOR')) {
                if (Registry::get('runtime.company_id')) {
                    $condition .= db_quote(" AND ?:users.user_id IN (?n) ", fn_get_company_customers_ids(Registry::get('runtime.company_id')));
                    $condition .= db_quote(" AND user_type != ?s", 'A');
                }
//CP Tier
            } elseif (fn_allowed_for('ULTIMATE')) {
                if (Registry::get('runtime.company_id')) {
                    $_condition = '';
                    if (Registry::get('settings.Stores.share_users') == 'Y') {
                        $_condition .= db_quote(" OR ?:users.user_id IN (SELECT DISTINCT user_id FROM ?:orders WHERE company_id = ?i) ", Registry::get('runtime.company_id'));
                    }
                    $condition .= db_quote(" AND (?:users.company_id = ?i $_condition)", Registry::get('runtime.company_id'));
                }
            }
            $condition .= db_quote(" AND status = ?s", 'A');
            $condition .= db_quote(" AND user_id != ?i", $author_id);
//
            $result['recipients'] = db_get_array("SELECT CONCAT_WS(' ', firstname, lastname) as name, user_id as object_id, 'U' as object_type, email FROM ?:users
                WHERE $condition LIMIT 0, 5");

            // ЦИАН: если в поле введён ID чата (число или "cian:ID") и задан токен — предлагаем виртуального получателя
            if (Registry::get('addons.cp_conversations.cian_api_token')
                && preg_match('/^\s*(?:cian[\s:_-]*)?(\d{3,})\s*$/i', $q, $cp_qm)) {
                if (!is_array($result['recipients'])) {
                    $result['recipients'] = array();
                }
                array_unshift($result['recipients'], array(
                    'name'        => 'Чат ЦИАН #' . $cp_qm[1],
                    'object_id'   => 'cian_' . $cp_qm[1],
                    'object_type' => 'CIAN',
                    'email'       => 'CIAN',
                ));
            }
        } else {
            $arr = fn_explode(' ', $q);
            foreach ($arr as $k => $v) {
                if (!fn_string_not_empty($v)) {
                    unset($arr[$k]);
                }
            }
            $like_expression = ' (';
            $search_string = '%' . trim($q) . '%';
            if (fn_allowed_for('MULTIVENDOR')) {
                if (sizeof($arr) == 2) {
                    $like_expression .= db_quote('?:companies.company LIKE ?l', '%' . array_shift($arr) . '%');
                } else {
                    $like_expression .= db_quote('?:companies.company LIKE ?l', $search_string);
                }
                $like_expression .= ')';
                $condition = $like_expression;
                $result['recipients'] = db_get_array("SELECT ?:companies.company as name, ?:companies.company_id as object_id, 'C' as object_type FROM ?:companies 
                    LEFT JOIN ?:users ON ?:users.company_id = ?:companies.company_id 
                    WHERE $condition AND ?:companies.status = ?s AND ?:users.user_type = ?s AND ?:users.status = ?s LIMIT 0, 5", 'A', 'V', 'A');
            } else {
                $cur_company_id = Registry::get('runtime.company_id');
                if (!empty($cur_company_id)) {
                    
                    if (sizeof($arr) == 2) {
                        $like_expression .= db_quote('?:users.firstname LIKE ?l', '%' . array_shift($arr) . '%');
                        $like_expression .= db_quote(' AND ?:users.lastname LIKE ?l', '%' . array_shift($arr) . '%');
                    } else {
                        $like_expression .= db_quote('?:users.firstname LIKE ?l', $search_string);
                        $like_expression .= db_quote(' OR ?:users.lastname LIKE ?l', $search_string);
                        $like_expression .= db_quote(' OR ?:users.email LIKE ?l', $search_string);//CP Tier
                    }
                    $like_expression .= ')';
                    $condition = $like_expression;
                
                    $result['recipients'] = db_get_array("SELECT CONCAT_WS(' ', firstname, lastname) as name, user_id as object_id, 'U' as object_type, email FROM ?:users
                        WHERE $condition AND user_type = ?s AND status = ?s AND cp_for_conversation = ?s AND company_id = ?i LIMIT 0, 5", 'A', 'A', 'Y', $cur_company_id);
                } else {
                    $result['recipients'] = array();
                }
            }
        }

    } elseif(!empty($params['recipient_id']) || !empty($params['admin_message'])) {
    
        $result['recipient_id'] = $params['recipient_id'];
        if(AREA == 'A') {
            $result['recipient_name'] = db_get_field("SELECT CONCAT_WS(' ', firstname, lastname) FROM ?:users WHERE user_id = ?i", $params['recipient_id']);
            $condition = '';
            $company_id = Registry::get('runtime.company_id');

            if(!empty($company_id)) {
                //vendor gets only his own conversations
                $my_conversations = db_get_fields("SELECT conversation_id FROM ?:cp_conversation_users WHERE recipient_id = ?i", $author_id);
                $condition .= db_quote(" AND ?:cp_conversations.conversation_id IN (?n)", $my_conversations);
            }
            $result['conversations'] = db_get_hash_array("SELECT ?:cp_conversations.conversation_id, subject FROM ?:cp_conversations 
                LEFT JOIN ?:cp_conversation_users ON ?:cp_conversations.conversation_id = ?:cp_conversation_users.conversation_id
                WHERE recipient_id = ?i $condition", 'conversation_id', $params['recipient_id']);

        } else {
            $my_conversations = db_get_fields("SELECT conversation_id FROM ?:cp_conversation_users WHERE recipient_id = ?i", $author_id);
            if(!empty($params['admin_message'])) {
                $result['recipient_id'] = db_get_field("SELECT user_id FROM ?:users WHERE company_id = ?i AND is_root = ?s AND user_type = ?s", 0, 'Y', 'A');
                $result['recipient_name'] = fn_get_user_name($result['recipient_id']);
                $result['conversations'] = db_get_hash_array("SELECT ?:cp_conversations.conversation_id, subject FROM ?:cp_conversations 
                    LEFT JOIN ?:cp_conversation_users ON ?:cp_conversations.conversation_id = ?:cp_conversation_users.conversation_id
                    WHERE recipient_id = ?i AND ?:cp_conversations.conversation_id IN (?n)", 'conversation_id', $result['recipient_id'], $my_conversations);

            } else {
                if (empty($params['conversation_id'])) {
                    if(fn_allowed_for('MULTIVENDOR')) {
                        $result['recipient_id'] = db_get_field("SELECT user_id FROM ?:users WHERE company_id = ?i AND is_root = ?s AND status = ?s", $params['recipient_id'], 'Y', 'A');
                    } else {
                        if (!empty($params['cur_comp_id'])) {
                            $cur_comp_id = $params['cur_comp_id'];
                        } else {
                            $cur_comp_id = $params['recipient_id'];
                        }
                        $result['recipient_id'] = db_get_field("SELECT MIN(user_id) FROM ?:users WHERE company_id = ?i AND is_root = ?s AND user_type = ?s AND status = ?s AND cp_for_conversation = ?s", $cur_comp_id, 'Y', 'A', 'A', 'Y');
                    }
                }
                if (fn_allowed_for('MULTIVENDOR')) {
                    if (!empty($result['recipient_id'])) {
                        $result['recipient_name'] = db_get_field("SELECT company FROM ?:companies WHERE company_id = ?i", $params['recipient_id']);
                        $recipients = db_get_fields("SELECT user_id FROM ?:users WHERE company_id = ?i AND user_type = ?s", $params['recipient_id'], 'V');
                    } elseif (Registry::get('addons.cp_conversations.allow_conversations_with_admin') == 'Y') {
                        $result['recipient_id'] = db_get_field("SELECT user_id FROM ?:users WHERE company_id = ?i AND is_root = ?s AND user_type = ?s", 0, 'Y', 'A');
                        $result['recipient_name'] = fn_get_user_name($result['recipient_id']);
                        $result['conversations'] = db_get_hash_array("SELECT ?:cp_conversations.conversation_id, subject FROM ?:cp_conversations 
                            LEFT JOIN ?:cp_conversation_users ON ?:cp_conversations.conversation_id = ?:cp_conversation_users.conversation_id
                            WHERE recipient_id = ?i AND ?:cp_conversations.conversation_id IN (?n)", 'conversation_id', $result['recipient_id'], $my_conversations);
                    }
                } else {
                    if (!empty($result['recipient_id'])) {
                        $result['recipient_name'] = db_get_field("SELECT CONCAT_WS(' ', firstname, lastname) FROM ?:users WHERE user_id = ?i", $result['recipient_id']);
                        $recipients = $result['recipient_id'];
                    } elseif (Registry::get('addons.cp_conversations.allow_conversations_with_admin') == 'Y') {
                        $result['recipient_id'] = db_get_field("SELECT user_id FROM ?:users WHERE company_id = ?i AND is_root = ?s AND user_type = ?s", 0, 'Y', 'A');
                        $result['recipient_name'] = fn_get_user_name($result['recipient_id']);
                        $result['conversations'] = db_get_hash_array("SELECT ?:cp_conversations.conversation_id, subject FROM ?:cp_conversations 
                            LEFT JOIN ?:cp_conversation_users ON ?:cp_conversations.conversation_id = ?:cp_conversation_users.conversation_id
                            WHERE recipient_id = ?i AND ?:cp_conversations.conversation_id IN (?n)", 'conversation_id', $result['recipient_id'], $my_conversations);
                    }
                }
                
                $result['conversations'] = db_get_hash_array("SELECT ?:cp_conversations.conversation_id, subject FROM ?:cp_conversations 
                    LEFT JOIN ?:cp_conversation_users ON ?:cp_conversations.conversation_id = ?:cp_conversation_users.conversation_id
                    WHERE recipient_id IN (?n) AND ?:cp_conversations.conversation_id IN (?n)", 'conversation_id', $recipients, $my_conversations);
            }
            if (!empty($params['product_id']) || !empty($params['order_id']) || !empty($params['vendor_id'])) {
                $result['cp_skip_delete_rec'] = true;
            }
        }
    } else {
//         if (AREA == 'C') {
//             if (fn_allowed_for('ULTIMATE') && Registry::get('runtime.company_id')) {
//                 $result['recipient_id'] = db_get_field("SELECT MIN(user_id) FROM ?:users WHERE company_id = ?i AND is_root = ?s AND user_type = ?s AND status = ?s AND cp_for_conversation = ?s", Registry::get('runtime.company_id'), 'Y', 'A', 'A', 'Y');
//                 if (!empty($result['recipient_id'])) {
//                     $result['recipient_name'] = db_get_field("SELECT CONCAT_WS(' ', firstname, lastname) FROM ?:users WHERE user_id = ?i", $result['recipient_id']);
//                     $recipients = $result['recipient_id'];
//                 }
// //                 $result['recipients'] = db_get_array("SELECT CONCAT_WS(' ', firstname, lastname) as name, user_id as object_id, 'U' as object_type, email FROM ?:users
// //                     WHERE company_id = ?i AND is_root = ?s AND user_type = ?s AND status = ?s AND cp_for_conversation = ?s LIMIT 0, 5", Registry::get('runtime.company_id'), 'Y', 'A', 'A', 'Y');
//             }
//         }
    }
    if(!empty($params['conversation_id'])) {
        $result['conversation_data'] = fn_cp_conversations_get_conversation_data($params['conversation_id']);
    }
    if (!empty($params['order_id'])) {
        $result['order_id'] = $params['order_id'];
        $result['cp_company_name'] = db_get_field("SELECT ?:companies.company FROM ?:companies LEFT JOIN ?:orders ON ?:orders.company_id = ?:companies.company_id WHERE ?:orders.order_id = ?i", $params['order_id']);
    }
    Registry::get('view')->assign('search_result', $result);
    Registry::get('view')->display('addons/cp_conversations/components/new_conversation.tpl');
    exit;

}