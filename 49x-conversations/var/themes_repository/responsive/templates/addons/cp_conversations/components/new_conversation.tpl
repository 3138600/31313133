<div class="hidden" title="{__("new_conversation")}" id="compose_new_message">
	<form action="{""|fn_url}" method="post" name="new_conversation" class="form-horizontal form-edit cm-disable-empty-files" enctype="multipart/form-data">
        <input type="hidden" name="dispatch" value="conversations.send_new_message">
        {if "ULTIMATE"|fn_allowed_for}
            {assign var="cur_company_id" value=$runtime.company_id}
        {else}
            {assign var="cur_company_id" value=$product.company_id}
        {/if}
        {if $search_result.order_id}
            <input type="hidden" name="conversation_data[order_id]" value="{$search_result.order_id}">
        {/if}
        <input type="hidden" name="cur_company_id" value="{$cur_company_id}" />
        <div class="ty-control-group">
            <label class="ty-control-group__title cm-required" for="recipient_input">{__("recipient")}:</label>
            <div  id="recipient_field_reload">
                <div class="cp-recipient-selector-wrapper">
                    <input class="cp-recipient-selector ty-input-text" id="recipient_input" type="text" placeholder="{__("start_typing_name")}" value="{$search_result.recipient_name}" {if $search_result.recipient_id}readonly{/if} autocomplete="off">
                    {if $search_result.recipient_id && !$search_result.cp_skip_delete_rec}
                        <a href="{"conversations.new"|fn_url}" class="cm-ajax cp-recipient-selector-clear" data-ca-target-id="compose_new_message">
                            <i class="ty-icon-cancel-circle"></i>
                        </a>
                    {/if}
                    <input type="hidden" name="conversation_data[recipient_id]" value="{$search_result.recipient_id}">
                </div>
            <!--recipient_field_reload--></div>
            <div id="recipient_reload">
                {if $search_result.recipients}
                    <ul class="cp-recipient-search-result">
                        {foreach from=$search_result.recipients item=result}
                            <li>
                                <a href="{"conversations.new&recipient_id=`$result.object_id`&cur_comp_id=`$cur_company_id`"|fn_url}" class="cm-ajax" data-ca-target-id="compose_new_message">{$result.name}</a>
                            </li>
                        {/foreach}
                    </ul>
                {/if}
                {if $addons.cp_conversations.allow_conversations_with_admin == "Y"}
                    {if !$search_result.recipient_id}
                        <label class="ty-control-group__title">{__("or")}</label>
                        {__("contact")}<a href="{"conversations.new&admin_message=Y"|fn_url}" class="cm-ajax" data-ca-target-id="compose_new_message"> <strong>{__("store_admin")}</strong></a>
                    {/if}
                {/if}
            <!--recipient_reload--></div>
        </div>
        <div id="conversations_reload">
            {if $search_result.conversations}
                <div class="ty-control-group">
                    <label class="ty-control-group__title" for="conversation">{__("conversation")}:</label>
                    <select class="cp-conversation-selector ty-input-text" name="conversation_id" id="conversation" data-recipient-id="{$search_result.recipient_id}" {if !$search_result.conversations}disabled{/if}>
                        <option value="">{__("new_conversation")}</option>
                        {if !$search_result.order_id}
                            {foreach from=$search_result.conversations item=conv}
                                <option value="{$conv.conversation_id}" {if $search_result.conversation_data.conversation_id == $conv.conversation_id}selected{/if}>{$conv.subject}</option>
                            {/foreach}
                        {/if}
                    </select>
                </div>
            {else}
                <input type="hidden" name="conversation_id" value="0">
            {/if}
            <div class="ty-control-group">
                <label class="ty-control-group__title cm-required" for="subject">{__("subject")}:</label>
                <input id="subject" class="ty-input-text" type="text" name="conversation_data[subject]" value="{if $smarty.request.subject_order}{if $search_result.cp_company_name}{$search_result.cp_company_name} - {/if}{__("order")}#{$smarty.request.subject_order}: {elseif $smarty.request.subject_product}{$smarty.request.subject_product}: {else}{$search_result.conversation_data.subject}{/if}" {if $search_result.conversation_data.subject || !$search_result.recipient_id}readonly{/if}>
            </div>
            <div class="control-group">
                <label class="ty-control-group__title cm-required" for="message">{__("message")}:</label>
                <textarea id="message" name="conversation_data[message]" cols="55" rows="8" class="ty-input-text" {if !$search_result.recipient_id}readonly{/if}></textarea>
            </div>

            <div class="control-group cp-custom-image-uploader cp-conversation-history">
                <div class="cp-conversation-messages__buttons cp-custom-image-uploader ty-right">
                    <a class="ty-btn cp-attach-image-button" onclick="addFileUploader();"><i class="ty-icon-plus" ></i>{__("attach_image")}</a>
                </div>
                <div class="cp-conversation-messages__file-inputs-wrap">
                </div>

            </div>
            <div class="buttons-container buttons-container-picker">
                <input type="submit" class="ty-float-right ty-btn ty-btn__primary" value="{__("send")}">
                <a class="ty-btn cm-dialog-closer">{__("cancel")}</a>
            </div>
        <!--conversations_reload--></div>
	</form>
<!--compose_new_message--></div>