{foreach from=$features item=cur_cat}
    {assign var="cat_id" value=$cur_cat.feature_id}
    {assign var="comb_id" value="cat_`$cur_cat.feature_id`_`$random`"}
    {assign var="title_id" value="feature_`$cur_cat.feature_id`"}
    <table width="100%" class="table table-tree table-middle">
        {if $header && !$feature_id}
            {assign var="header" value=""}
            <thead>
                <tr>
                    <th>
                        {if $display != "radio"}
                            {include file="common/check_items.tpl"}
                        {/if}
                    </th>
                    <th width="100%">
                        {__("features")}
                    </th>
                </tr>
            </thead>
        {/if}
                <tr>
                    <td class="left first-column" width="1%">
                        <input type="checkbox" id="input_cat_{$cur_cat.feature_id}" name="{$checkbox_name}[{$cur_cat.feature_id}]" value="{$cur_cat.feature_id}" class="cm-item" />
                    </td>
                    <td style="padding-left: {$_shift}px;">
                        <label id="{$title_id}" class="inline-label" for="input_cat_{$cur_cat.feature_id}">{$cur_cat.feature_id|fn_cp_similar_products_get_feature_name}</label>
                    </td>
                </tr>
    </table>
    <div{if !$expand_all} class="hidden"{/if} id="{$comb_id}">
        {include file="addons/cp_similar_products/views/components/features_simple.tpl" features=$cur_cat.features}
    </div>
{/foreach}
