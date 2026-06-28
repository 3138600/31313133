{** conversations section **}

{capture name="mainbox"}
{include file="common/pagination.tpl" save_current_page=true save_current_url=true div_id=$smarty.request.content_id}
{if $conversations}
    <form action="{""|fn_url}" method="post" name="conversations_form">
    <div class="table-responsive-wrapper">
        <table class="table table-middle cp-conversations-table table-responsive">
            <thead>
                <tr>
                    <th width="1%" class="left mobile-hide">{include file="common/check_items.tpl"}</th>
                    <th  width="10%">&nbsp;</th>
                    <th  width="10%">{__("recipient")}</th>
                    <th width="49%">{__("conversation")}</th>
                    <th width="20%">{__("messages")}</th>
                    <th width="15%">{__("date")}</th>  
                    <th width="10%" class="right">&nbsp;</th>
                </tr>
            </thead>
            {foreach from=$conversations item=conversation}
                {*
                <tr class="cp-external-click" data-ca-external-click-id="conversations_opener_{$conversation.conversation_id}">
                *}
                <tr>
                    <td class="left mobile-hide">
                        <input type="checkbox" name="conversation_ids[]" value="{$conversation.conversation_id}" class="cm-item" />
                    </td>
                    <td class="mobile-hide">
                        {if $conversation.last_message.user_image}
                            {include file="common/image.tpl" image=$conversation.last_message.user_image image_width=40 image_height=40}
                        {else}
                            <div class="cp-conversation-no-avatar"><i class="cp-icon-user"></i></div>
                        {/if}
                    </td>
                    <td data-th="{__("recipient")}">
                        <a href="{"profiles.update&user_id=`$conversation.last_message.user_id`"|fn_url}">{$conversation.last_message.user_id|fn_get_user_name}</a>
                    </td>
                    <td class="nowrap row-status " data-th="{__("conversation")}">
                        <div class="cp-subject {if $conversation.read == "N"}unread{/if}">
                            <a href="{"conversations.update&conversation_id=`$conversation.conversation_id`"|fn_url}" id="conversations_opener_{$conversation.conversation_id}">
                                {$conversation.subject}
                            </a>
                        </div>
                        <div class="cp-last-message">{$conversation.last_message.message}</div>
                    </td>
                    <td data-th="{__("messages")}">
                        <a class="badge" href="{"conversations.update&conversation_id=`$conversation.conversation_id`&selected_section=messages"|fn_url}">{$conversation.messages_amount}</a>
                    </td>
                    <td data-th="{__("date")}" width="15%">
                        {$conversation.last_message.humanized_time}
                        {if $conversation.last_message.user_id == $auth.user_id}
                            <a href="{"conversations.update&conversation_id=`$conversation.conversation_id`"|fn_url}" class="cp-reply-link"><i class="cp-icon-reply"></i></a>
                        {/if}
                    </td>
                    <td class="mobile-hide">
                        {capture name="tools_list"}
                            <li>{btn type="list" text=__("edit") href="conversations.update?conversation_id=`$conversation.conversation_id`"}</li>
                            <li>{btn type="list" class="cm-confirm" text=__("delete") href="conversations.delete?conversation_id=`$conversation.conversation_id`" method="POST"}</li>
                        {/capture}
                        <div class="hidden-tools">
                            {dropdown content=$smarty.capture.tools_list}
                        </div>
                    </td>
                </tr>
            {/foreach}
        </table>
    </div>
    </form>
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}
{include file="common/pagination.tpl" div_id=$smarty.request.content_id}

{capture name="buttons"}
    <span class="mobile-hide">
    {capture name="tools_list"}
        {if $conversations}
            <li>{btn type="delete_selected" dispatch="dispatch[conversations.m_delete]" form="conversations_form"}</li>
        {/if}
    {/capture}
    {dropdown content=$smarty.capture.tools_list}
    </span>
    {*
    <a class="btn btn-primary cm-dialog-opener cm-ajax" href="{"conversations.new"|fn_url}" data-ca-dialog-title="{__("new_conversation")}" data-ca-target-id="compose_new_message">
        <i class="cp-icon-edit"></i>{__("compose")}
    </a>
    *}
{/capture}
{capture name="adv_buttons"}
    <a class="btn btn-primary cm-dialog-opener cm-ajax" href="{"conversations.new?recipient_id=`$start_with_user_id`"|fn_url}" data-ca-dialog-title="{__("new_conversation")}" data-ca-target-id="compose_new_message">
        <i class="cp-icon-edit"></i>{__("compose")}
    </a>
	    {capture name="add_new_picker"}
        {include file="addons/cp_conversations/views/conversations/components/cian_start_form.tpl"}
    {/capture}

    {include file="common/popupbox.tpl" 
        id="start_cian_chat_form" 
        text="Написать в CIAN" 
        content=$smarty.capture.add_new_picker 
        title="Новое сообщение в CIAN" 
        act="general" 
        icon="icon-plus" 
        link_text="Написать в CIAN"
    }
    {* ========================================================== *}
    {* КОНЕЦ НОВОГО КОДА                                          *}
    {* ========================================================== *}
{/capture}

{capture name="sidebar"}
    <div class="sidebar-row">
        <h6>{__("search")}</h6>
        <form action="{""|fn_url}" method="get">
            <input type="hidden" name="dispatch" value="conversations.manage">
            <div class="sidebar-field">
                <label>{__('find_text')}</label>
                <input type="text" name="q" size="20" value="{$smarty.request.q}">
            </div>
            <div class="sidebar-field">
                <label>{__("recipient")}</label>
                {include file="pickers/users/picker.tpl" input_name=recipient_ids item_ids=$smarty.request.recipient_ids view_mode="mixed"}
            </div>
            <input class="btn " type="submit" name="dispatch[conversations.manage]" value="{__("search")}">
        </form>
    </div>
{/capture}
{if $start_with_user_id}
    <script>
        $(document).ready(function() {
            var cur_new_url = "{$start_with_new_url}";
            if (cur_new_url.length > 0) {
                window.history.pushState({}, document.title, cur_new_url);
            }
            $('.adv-buttons a[data-ca-target-id="compose_new_message"]').click();
        });
    </script>
{/if}
{/capture}
{include file="common/mainbox.tpl" title=__("conversations") content=$smarty.capture.mainbox buttons=$smarty.capture.buttons adv_buttons=$smarty.capture.adv_buttons sidebar=$smarty.capture.sidebar select_languages=true}

{** ad section **}