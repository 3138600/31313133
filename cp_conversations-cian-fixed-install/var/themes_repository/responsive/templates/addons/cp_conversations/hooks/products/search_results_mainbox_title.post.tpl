{capture name="mainbox_title"}
    <span class="ty-mainbox-title__left">{$_title}</span>
    <span class="ty-mainbox-title__right" id="products_search_total_found_{$block.block_id}">
        <div class="ty-right">
            {assign var="return_current_url" value=$config.current_url|escape:url}
            <a class="ty-btn ty-btn__primary btn-compose cp-compose-button cm-dialog-opener cm-ajax cp-enabled" href="{"conversations.new&recipient_id=`$company_id`?return_url=`$return_current_url`"|fn_url}" data-ca-dialog-title="{__('new_conversation')}" data-ca-target-id="compose_new_message">
                <i class="cp-icon-edit"></i><span class="button-text">{__('contact_vendor')}</span>
            </a>
        </div>
        <div class="ty-right">{$title_extra nofilter}</div>
    <!--products_search_total_found_{$block.block_id}--></span>
{/capture}