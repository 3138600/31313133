{** conversations section **}

{capture name="mainbox"}

<form action="{""|fn_url}" method="post" class="form-horizontal form-edit" name="conversations_form" enctype="multipart/form-data">
    <input type="hidden" name="conversation_id" value="{$conversation.conversation_id}" />
    <input type="hidden" name="result_ids" value="conversation_holder_reload,message_field_reload" />
    {capture name="tabsbox"}
        <div id="content_general">
            <div class="control-group">
                <label for="elm_conversation_subject" class="control-label cm-required">{__("subject")}</label>
                <div class="controls">
                    <input type="text" name="conversation_data[subject]" id="elm_conversation_subject" value="{$conversation.subject}" size="25" class="input-large" />
                </div>
            </div>

            <div class="control-group">
                <label class="control-label">{__("recipients")}:</label>
                <div class="controls">
                    {if $vendor_admins}
                        <ul>
                            {foreach from=$conversation.recipients item=user_id}
                                <li>
                                    {if $vendor_admins.$user_id && $is_root_admin}
                                        <select name="conversation_data[recipients][]">
                                            {foreach from=$vendor_admins item=name key=_id}
                                                <option value="{$_id}" {if $_id == $user_id}selected{/if}>{$name.name}</option>
                                            {/foreach}
                                        </select>
                                    {else}
                                        <a href="{"profiles.update&user_id=`$user_id`"|fn_url}"">{$user_id|fn_get_user_name}</a>
                                        <input type="hidden" name="conversation_data[recipients][]" value="{$user_id}">
                                    {/if}
                                </li>
                            {/foreach}
                        </ul>
                    {else}
                    {include file="pickers/users/picker.tpl" input_name="conversation_data[recipients]" item_ids=$conversation.recipients placement="right" but_meta="btn btn-primary"}
                    {/if}
                </div>
            </div>
        <!--content_general--></div>
        <div class="control-group cp-conversation-row" id="content_messages">
            <div class="controls">
                <div id="conversation_holder_reload">
                    <div  class="cp-messages-holder">
                    {if $conversation.messages_params.total_items > $conversation.messages|sizeof && $conversation.messages_params.total_items > $conversation.messages_params.start + $settings.Appearance.admin_elements_per_page}
                        <div class="cp-messages-holder__load_more">
                            <a onclick="loadMoreMessages({$conversation.conversation_id}, {$conversation.messages_params.start + $settings.Appearance.admin_elements_per_page});">{__('show_earlier_messages')}</a>
                        </div>
                    {/if}
                    {foreach from=$conversation.messages item=message}
                        <div class="cp-message-item {if $message.user_id == $auth.user_id}cp-my-message{/if}">
                            {if $message.humanized_time}
                                {if $message.user_image}
                                    <div class="cp-conversation-avatar">
                                        {include file="common/image.tpl" image=$message.user_image image_width=40 image_height=40}
                                    </div>
                                {else}
                                    <div class="cp-conversation-no-avatar"><i class="cp-icon-user"></i></div>
                                {/if}
                            {/if}
                            <div class="cp-message-wrapper__outer">

                                <div class="cp-message-wrapper">
                                    {$message.message nofilter}
                                    {if $message.files}
                                        <div class="cp-message-item__images-wrap">
                                            <div class="cp-message-item__images-message">{__('attached_files')}:</div>
                                            {foreach from=$message.files item=file}
                                                <div class="cp-message-item__image">
                                                    <a href="{$file.url}" target="_blank">
                                                        {if $file.is_pdf == 'Y'}
                                                            <span class="pdf-file">
                                                                <i class="cp-icon-file-pdf"></i>
                                                            </span>
                                                        {else}
                                                            <img src="{$file.thumb}">
                                                        {/if}
                                                    </a>
                                                </div>
                                            {/foreach}
                                        </div>
                                    {/if}
                                </div>
                                {if $message.humanized_time}
                                <div class="cp-message-item__date">
                                    <span class="cp-conversation-user-name"><strong>{$message.user_name}</strong>, </span>{$message.humanized_time}
                                </div>
                                {/if}
                            </div>
                        </div>
                    {/foreach}
                    </div>
                <!--conversation_holder_reload--></div>

                <div class="cp-message-field" id="message_field_reload">
                    <div class="control-group">
                        <label class="control-label">{__("message")}</label>
                        <div class="controls">
                            <textarea name="conversation_data[message]" id="message_container"></textarea>
                        </div>
                    </div>
                    <div class="control-group cp-custom-image-uploader">
                        <label class="control-label">{__("attach_image")}</label>
                        <div class="controls">
                            <div class="cm-row-item" id="box_new_image">
                                <div class="pull-right cp-multiple-buttons">
                                    {include file="buttons/multiple_buttons.tpl" item_id="new_image" remove_item_on_delete=true}
                                </div>
                                {include file="common/fileuploader.tpl" var_name="message_files[0]" allowed_ext="jpeg,jpg,gif,png,pdf"}
                            </div>
                        </div>
                    </div>
                    <div class="control-group cp-message-field__buttons">
                        <div class="controls">
                            <input class="btn btn-primary cm-ajax" type="submit" value="{__('send')}" name="dispatch[conversations.send_new_message]">
                        </div>
                    </div>
                <!--message_field_reload--></div>
            </div>
        <!--content_messages--></div>
    {/capture}
    {include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox active_tab=$smarty.request.selected_section track=true}

    {capture name="buttons"}
        {if !$conversation}
            {include file="buttons/save_cancel.tpl" but_role="submit-link" but_target_form="conversations_form" but_name="dispatch[conversations.update]"}
        {else}
            {include file="buttons/save_cancel.tpl" but_name="dispatch[conversations.update]" but_role="submit-link" but_target_form="conversations_form" hide_first_button=$hide_first_button hide_second_button=$hide_second_button save=$conversation}
        {/if}
    {/capture}
</form>
{/capture}

{if !$conversation}
    {assign var="title" value=__("new_conversation")}
{else}
    {assign var="title" value="{__("conversation_beetween")}: `$conversation.formatted_usernames`"}
{/if}
{include file="common/mainbox.tpl" title=$title content=$smarty.capture.mainbox buttons=$smarty.capture.buttons select_languages=true}

{** conversation section **}
