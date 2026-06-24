{capture name="mainbox_title"}<span >{__('conversations')}</span>{/capture}
<div class="span4">
    {include file='addons/cp_conversations/components/side_bar.tpl'}
</div>
<div class="span12">
    <div class="cp-conversations-top-wrapper" id="conversations_list_reload">
        <div class="hidden-desktop hidden-tablet cp-conversations-mobile-search">
            <form action="{""|fn_url}" class="cm-ajax1" method="get">
                {assign var="c_url" value=$config.current_url|fn_query_remove:"folder":"folder_id"}

                <input type="hidden" name="result_ids" value="conversations_*">
                <input type="hidden" name="dispatch" value="conversations.list">
                <div class="cp-conversations-mobile-search__folders">
                    <select class="cp-folder-selector">
                        <option {if !$smarty.request.folder_id && $smarty.request.folder == "I"}selected{/if} value="{$c_url|fn_link_attach:"folder=I"}">{__('inbox')}</option>
                        <option {if !$smarty.request.folder_id && $smarty.request.folder == "S"}selected{/if} value="{$c_url|fn_link_attach:"folder=S"}">{__('sent')}</option>
                        <option {if !$smarty.request.folder_id && $smarty.request.folder == "A"}selected{/if} value="{$c_url|fn_link_attach:"folder=A"}">{__('all')}</option>
                        <option {if !$smarty.request.folder_id && $smarty.request.folder == "U"}selected{/if} value="{$c_url|fn_link_attach:"folder=U"}">{__('unread')}</option>
                        <option {if !$smarty.request.folder_id && $smarty.request.folder == "P"}selected{/if} value="{$c_url|fn_link_attach:"folder=P"}">{__('spam')}</option>
                        <option {if !$smarty.request.folder_id && $smarty.request.folder == "T"}selected{/if} value="{$c_url|fn_link_attach:"folder=T"}">{__('trash')}</option>
                        {if $customer_folders}
                            <option value="" disabled>---</option>
                            {foreach from=$customer_folders item=folder}
                                <option {if $smarty.request.folder_id == $folder.folder_id}selected{/if} value="{$c_url|fn_link_attach:"folder_id=`$folder.folder_id`"}">{$folder.folder}</option>
                            {/foreach}
                        {/if}
                    </select>
                </div>
                <div class="cp-conversations-mobile-search__search-form">
                    <div class="ty-search-block">
                        {strip}
                            <input type="text" name="q" value="{$smarty.request.q}" placeholder="{__('search')}" class="ty-search-block__input cm-hint">
                            {include file="buttons/magnifier.tpl" but_name="conversations.list" alt=__("search")}
                        {/strip}
                    </div>
                </div>
            </form>
        </div>
        <form name="conversations_form" action="{""|fn_url}" method="post">
            <input type="hidden" name="conversation_id" value="{$conversation.conversation_id}" />
            <input type="hidden" name="redirect_url" value="{$config.current_url}" />
            <input type="hidden" name="result_ids" value="conversations_*">
            <input type="hidden" name="folder" value="{$smarty.request.folder}">
            <input type="hidden" name="folder_id" value="{$smarty.request.folder_id}">
            {include file='addons/cp_conversations/components/top_panel.tpl'}
            {include file="common/pagination.tpl"}
            {if $conversations}
                <table class="table cp-conversations__table">
                    <thead class="hidden">
                        <th width="5%">&nbsp;</th>
                        <th width="10%">&nbsp;</th>
                        <th width="20%" class="cp-td-to-hide">&nbsp;</th>
                        <th width="30%">&nbsp;</th>
                        <th width="15%">&nbsp;</th>
                        <th width="25%">&nbsp;</th>
                    </thead>
                    {foreach from=$conversations item=conversation}
                        <tr class="cp-external-click" data-ca-external-click-id="conversations_opener_{$conversation.conversation_id}">
                            <td width="5%">
                                <input type="checkbox" name="conversation_ids[]" class="checkbox cm-item" value="{$conversation.conversation_id}">
                            </td>
                            <td width="10%" class="cp-conversation-history">
                                <div class="cp-conversation-messages__history-user-image-wrapper">
                                    <div class="cp-conversation-messages__history-user-image">
                                        {if $conversation.last_message.user_image}
                                            {include file="common/image.tpl" images=$conversation.last_message.user_image image_width=40}
                                        {else}
                                            <i class="cp-icon-user"></i>
                                        {/if}
                                    </div>
                                </div>
                            </td>
                            <td width="20%" class="cp-td-to-hide">
                                {$conversation.last_message.user_name}
                            </td>
                            <td width="50%">
                                <div class="cp-conversations__folders">
                                    {foreach from=$conversation.folders item=folder}
                                        <a class="cp-conversations__folder-item" href="{"conversations.list&folder_id=`$folder.folder_id`"|fn_url}">{$folder.folder}</a>
                                    {/foreach}
                                </div>
                                <div class="hidden-desktop hidden-tablet">
                                    <strong>{$conversation.last_message.user_name}</strong>
                                </div>
                                <div class="cp-conversations__subject {if $conversation.read == 'N'}unread{/if}">
                                    <a href="{"conversations.view&conversation_id=`$conversation.conversation_id`"|fn_url}" id="conversations_opener_{$conversation.conversation_id}">
                                        {$conversation.subject}
                                    </a>
                                </div>
                                <div>
                                    {$conversation.last_message.message}
                                </div>
                            </td>
                            <td width="5%" class="cp-td-to-hide"><span class="cp-badge">{$conversation.messages_amount}</span></td>
                            <td width="10%">
                                <span class="cp-humanized-time">{$conversation.last_message.humanized_time}</span>
                                {if $conversation.last_message.user_id == $auth.user_id}
                                    <a href="{"conversations.view&conversation_id=`$conversation.conversation_id`"|fn_url}" class="cp-reply-link"><i class="cp-icon-reply"></i></a>
                                {/if}
                                {* <span class="cp-badge hidden-desktop hidden-tablet">{$conversation.messages_amount}</span> *}
                            </td>
                        </tr>
                    {/foreach}
                </table>
            {else}
                <div class="ty-no-items">
                    {__('no_items')}
                </div>
            {/if}
            {include file="common/pagination.tpl"}
        </form>
    <!--conversations_list_reload--></div>
</div>