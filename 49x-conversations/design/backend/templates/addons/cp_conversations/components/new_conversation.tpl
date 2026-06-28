<div class="hidden" title="{__("new_conversation")}" id="compose_new_message">
	<form action="{""|fn_url}" method="post" name="new_conversation" class="form-horizontal form-edit cm-disable-empty-files" enctype="multipart/form-data">
        {if $search_result.order_id}
            <input type="hidden" name="conversation_data[order_id]" value="{$search_result.order_id}">
        {/if}
        <fieldset>
            <div class="control-group">
                <label class="control-label cm-required" for="recipient_input">{__("recipient")}:</label>
                <div class="controls">
                    <div class="cp-recipient-selector-wrapper" id="recipient_field_reload">
                        <input class="cp-recipient-selector input-large" id="recipient_input" type="text" placeholder="{__("start_typing_name")}" value="{$search_result.recipient_name}" {if $search_result.recipient_id}readonly{/if} autocomplete="off">
                        {if $search_result.recipient_id}
                            {if $cp_skip_delete_rec}
                                <a href="{"conversations.new"|fn_url}" class="cm-ajax cp-recipient-selector-clear" data-ca-target-id="compose_new_message">
                                    <i class="icon-remove"></i>
                                </a>
                            {/if}
                            <input type="hidden" name="conversation_data[recipient_id]" value="{$search_result.recipient_id}">
                        {/if}
                    <!--recipient_field_reload--></div>
                    <div id="recipient_reload">
                        {if $search_result.recipients}
                            <ul class="cp-recipient-search-result">
                                {foreach from=$search_result.recipients item=result}
                                    <li>
                                        <a href="{"conversations.new&recipient_id=`$result.object_id`"|fn_url}" class="cm-ajax" data-ca-target-id="compose_new_message">{$result.name} ({$result.email}, {$result.object_id})</a>
                                    </li>
                                {/foreach}
                            </ul>
                        {/if}
                    <!--recipient_reload--></div>
                </div>
            </div>
            <div id="conversations_reload">
                {if $search_result.conversations}
                    <div class="control-group">
                        <label class="control-label" for="conversation">{__("conversation")}:</label>
                        <div class="controls">
                            <select class="cp-conversation-selector" name="conversation_id" id="conversation" data-recipient-id="{$smarty.request.recipient_id}" {if !$search_result.conversations}disabled{/if}>
                                <option value="">{__("new_conversation")}</option>
                                {if !$search_result.order_id}
                                    {foreach from=$search_result.conversations item=conv}
                                        <option value="{$conv.conversation_id}" {if $search_result.conversation_data.conversation_id == $conv.conversation_id}selected{/if}>{$conv.subject}</option>
                                    {/foreach}
                                {/if}
                            </select>
                        </div>
                    </div>
                {else}
                    <input type="hidden" name="conversation_id" value="0">
                {/if}
                <div class="control-group">
                    <label class="control-label cm-required" for="subject">{__("subject")}:</label>
                    <div class="controls">
                        <input id="subject" class="input-large" type="text" name="conversation_data[subject]" value="{if $smarty.request.subject_order}{if $search_result.cp_company_name}{$search_result.cp_company_name} - {/if}{__("order")} #{$smarty.request.subject_order}: {else}{$search_result.conversation_data.subject}{/if}" {if $search_result.conversation_data.subject || !$search_result.recipient_id}readonly{/if}>
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label cm-required" for="message">{__("message")}:</label>
                    <div class="controls">
                        <textarea id="message" name="conversation_data[message]" cols="55" rows="8" class="input-large" {if !$search_result.recipient_id}readonly{/if}></textarea>
                    </div>
                </div>
                {*
                <div class="control-group cp-custom-image-uploader">
                    <label class="control-label">{__("attach_image")}</label>
                    <div class="controls">
                        <div id="box_new_image">
                            <div class="pull-right cp-multiple-buttons">{include file="buttons/multiple_buttons.tpl" item_id="new_image"}</div>
                            {include file="common/attach_images.tpl" image_name="message_images" image_object_type="message_images" no_thumbnail=true hide_images=true hide_alt=true hide_titles=true image_type="A"}
                        </div>
                    </div>
                </div>
                *}
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
            <!--conversations_reload--></div>
        </fieldset>
        <div class="buttons-container buttons-container-picker">
            {include file="buttons/save_cancel.tpl" but_text=__("send") but_form="new_conversation" but_name="dispatch[conversations.send_new_message]" cancel_action="close" hide_first_button=false}
        </div>
	</form>
<!--compose_new_message--></div>