{capture name="advanced_options_`$obj_id`"}
    {if $show_product_options}
        <div class="cm-reload-{$obj_prefix}{$obj_id}" id="advanced_options_update_{$obj_prefix}{$obj_id}">
            {if $cp_allow_start_conv}
                {if "ULTIMATE"|fn_allowed_for}
                    {assign var="cp_conv_comp_id" value=$runtime.company_id}
                {else}
                    {assign var="cp_conv_comp_id" value=$product.company_id}
                {/if}
                {include file="views/companies/components/product_company_data.tpl" company_name=$product.company_name company_id=$cp_conv_comp_id}
                <div class="product-list-field">
                    {*
                    <label class="ty-control-group__label">&nbsp;</label>
                    *}
                    <span class="ty-control-group__item">
                        {assign var="return_current_url" value=$config.current_url|escape:url}
                        
                        <a class="ty-btn ty-btn__primary btn-compose cp-compose-button cm-dialog-opener cm-ajax" href="{"conversations.new&recipient_id=`$cp_conv_comp_id`&product_id=`$product.product_id`&subject_product=`$product.product`?return_url=`$return_current_url`"|fn_url}" data-ca-dialog-title="{__('new_conversation')}" data-ca-target-id="compose_new_message">
                            <i class="cp-icon-edit"></i><span>{__('contact_vendor')}</span>
                        </a>
                    </span>
                </div>
            {/if}
            {hook name="products:options_advanced"}
            {/hook}
        <!--advanced_options_update_{$obj_prefix}{$obj_id}--></div>
    {/if}
{/capture}