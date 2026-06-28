{** conversations section **}
<div class="span4">
    {include file='addons/cp_conversations/components/side_bar.tpl' detailed=true}
</div>
<div class="span12">
        <div class="span16 cp-conversations-top-wrapper">
            <form action="{""|fn_url}" method="post" class="form-horizontal form-edit" name="conversations_form" enctype="multipart/form-data">
                <input type="hidden" name="result_ids" value="conversations_*">
                <input type="hidden" name="conversation_ids[]" value="{$conversation.conversation_id}">
                {assign var="c_url" value=$config.current_url|fn_query_remove:"folder":"folder_id"}
                <input type="hidden" name="redirect_url" value="{$c_url}" />
                {include file='addons/cp_conversations/components/top_panel.tpl' detailed=true selected_folders=$conversation.folders}
            </form>
        </div>
        <div class="container-fluid cp-conversation-history">
            <div class="{if $vendor_info}span12{else}span16{/if}">
                <div id="conversations_list_reload">
                    <div class="cp-conversation-subject">{$conversation.subject}</div>
                    <div class="cp-conversation-recipients">
                        {__('beetween_you_and')} {$conversation.formatted_recipients}
                    </div>
                    <div class="cp-conversation-messages__history">
                        {foreach from=$conversation.messages item=message}
                            <div class="cp-conversation-messages__history-item-wrapper {if $message.user_id != $auth.user_id}cp-incoming-message{/if}">
                                <div class="cp-conversation-messages__history-user-image-wrapper">
                                    <div class="cp-conversation-messages__history-user-image">
                                        {if $message.user_image}
                                            {include file="common/image.tpl" images=$message.user_image image_width=40}
                                        {else}
                                            <i class="cp-icon-user"></i>
                                        {/if}
                                    </div>
                                </div>
                                <div class="cp-conversation-messages__history-user-name-text">
                                    <div class="cp-conversation-messages__history-user-name">
                                        {$message.user_name}
                                    </div>
                                    <div class="cp-conversation-messages__history-text">
                                        {$message.message}
                                    </div>
                                    {if $message.files}
                                        <div class="cp-conversation-messages__attachements">
                                            {foreach from=$message.files item=image}
                                                <a href="{$image.url}" target="_blank" {if $image.is_pdf == 'Y'}class="pdf"{/if}>
                                                    {if $image.is_pdf == 'Y'}
                                                        <i class="cp-icon-file-pdf"></i>
                                                    {else}
                                                        <img src="{$image.thumb}">
                                                    {/if}
                                                </a>
                                            {/foreach}
                                        </div>
                                    {/if}
                                </div>
                                <div class="cp-conversation-messages__history-date">
                                    {$message.humanized_time}
                                </div>
                            </div>
                            {if $conversation.messages_params.group_for_customer && $message@iteration == 1}
                                <div class="cp-conversation-messages__history-item-wrapper cp-load-more-link">
                                    <a href="{$c_url|fn_link_attach:'view_all=Y'}" class="cm-ajax" data-ca-target-id="conversations_list_reload">{__('view_n_read_messages', ['[amount]' => $conversation.messages_params.messages_amount])}</a>
                                </div>
                            {/if}
                        {/foreach}
                        <div class="cp-conversation-messages__history-item-wrapper cp-conversation-new-message">
                            <div class="cp-conversation-messages__history-user-image-wrapper">
                                <div class="cp-conversation-messages__history-user-image">
                                    {if $current_user_data.user_image}
                                        {include file="common/image.tpl" images=$current_user_data.user_image image_width=40}
                                    {else}
                                        <i class="cp-icon-user"></i>
                                    {/if}
                                </div>
                            </div>
                            <div class="cp-conversation-messages__history-user-name-text">
                                <div class="cp-conversation-messages__history-user-name">
                                    {$current_user_data.name}
                                </div>
                                <div class="cp-conversation-messages__history-text">
                                    <div class="cp-conversation-messages__buttons-textarea">
                                        <form action="{""|fn_url}" method="post" class="cm-ajax form-horizontal form-edit" name="new_conversations_form" enctype="multipart/form-data">
                                            <input type="hidden" name="result_ids" value="conversations_*">
                                            <input type="hidden" name="dispatch" value="conversations.send_new_message">
                                            <input type="hidden" name="conversation_id" value="{$conversation.conversation_id}" />
                                            {assign var="c_url" value=$config.current_url|fn_query_remove:"folder":"folder_id"}
                                            <input type="hidden" name="redirect_url" value="{$c_url}" />
                                            <label for="conversation_massage" class="cm-required ty-control-group__title cm-no-failed-msg"></label>
                                            <textarea cols="55" rows="3" placeholder="{__('type_your_reply')}" name="conversation_data[message]" id="conversation_massage" class="cm-no-failed-msg"></textarea>

                                            <div class="cp-conversation-messages__file-inputs-wrap">
                                            </div>

                                            <div class="cp-conversation-messages__buttons cp-custom-image-uploader">
                                                <input type="submit" class="ty-btn ty-btn__primary" value="{__('send')}">
                                                <a class="ty-btn ty-float-right" onclick="addFileUploader();">{__('attach_image')}</a>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <!--conversations_list_reload--></div>
            </div>
            {if $vendor_info}
                <div class="span4 cp-conversation-vendor-wrap">
                    <div class="cp-conversation-vendor-info">
                        <div class="cp-conversation-vendor-logo">
                            <a href="{"companies.view&company_id=`$vendor_info.company.company_id`"|fn_url}">
                                {include file="common/image.tpl" images=$vendor_info.company.logos.theme.image image_width="100" class="ty-company-image"}
                            </a>
                        </div>
                        <div class="cp-conversation-vendor-name">
                            <a href="{"companies.view&company_id=`$vendor_info.company.company_id`"|fn_url}">
                                {$vendor_info.company.company}
                            </a>
                        </div>
                    </div>
                    <div class="cp-conversation-vendor-orders">
                        <div class="cp-conversation-vendor-side-title">{__('latest_orders')}</div>
                        {if $vendor_info.orders}
                            {foreach from=$vendor_info.orders item=order}
                                <div class="cp-conversation-vendor-orders__item">
                                    <div class="cp-conversation-vendor-orders__item-link">
                                        <a href="{"orders.details&order_id=`$order.order_id`"|fn_url}">#{$order.order_id}</a>
                                    </div>
                                    <div class="cp-conversation-vendor-orders__item-total">
                                        {__('total')}: {include file="common/price.tpl" value=$order.total}
                                    </div>
                                </div>
                            {/foreach}
                        {else}
                            <div class="ty-no-items">{__('no_items')}</div>
                        {/if}
                    </div>
                    <div class="cp-conversation-vendor-conversations">
                        <div class="cp-conversation-vendor-side-title">{__('latest_conversations')}</div>
                        {if $vendor_info.conversations}
                            {foreach from=$vendor_info.conversations item=conversation}
                                <div class="cp-conversation-vendor-conversations__item">
                                    <div class="cp-conversation-vendor-conversation__item-link">
                                        <a href="{"conversations.view&conversation_id=`$conversation.conversation_id`"|fn_url}">{$conversation.subject}</a>
                                    </div>
                                    <div class="cp-conversation-vendor-conversation__item-total">
                                        {$conversation.last_message.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}
                                    </div>
                                </div>
                            {/foreach}
                        {else}
                            <div class="ty-no-items"></div>
                        {/if}
                    </div>
                </div>
            {/if}
        </div>
    </div>
</div>

{capture name="mainbox_title"}
    <span>{if !$conversation}{__("new_conversation")}{else}{__("conversation_beetween")}: {$conversation.formatted_usernames}{/if}
{/capture}

{include file="common/previewer.tpl"}
{script src="js/tygh/product_image_gallery.js"}

{** conversation section **}
