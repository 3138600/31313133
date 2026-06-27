{if $feature_id == "0"}
    {assign var="feature" value=$default_name}
{else}
    {assign var="feature" value=$feature_id|fn_cp_similar_products_get_feature_name|default:"`$ldelim`feature`$rdelim`"}
{/if}

<tr {if !$clone}id="{$holder}_{$feature_id}" {/if}class="cm-js-item{if $clone} cm-clone hidden{/if}">
    {if $position_field}
        <td>
            <input type="text" name="{$input_name}[{$feature_id}]" value="{math equation="a*b" a=$position b=10}" size="3" class="input-micro" {if $clone}disabled="disabled"{/if} />
        </td>
    {/if}
    <td><a href="{"product_features.update?feature_id=`$feature_id`"|fn_url}">{$feature}</a></td>
    <td>
        {capture name="tools_list"}
            {if !$hide_delete_button && !$view_only}
                <li><a onclick="Tygh.$.cePicker('delete_js_item', '{$holder}', '{$feature_id}', 'c'); return false;">{__("delete")}</a></li>
            {/if}
        {/capture}
        <div class="hidden-tools">
            {dropdown content=$smarty.capture.tools_list}
        </div>
    </td>
</tr>


