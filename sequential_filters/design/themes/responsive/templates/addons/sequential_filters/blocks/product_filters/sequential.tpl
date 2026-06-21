{** block-description:sequential_filters_template **}

{script src="js/tygh/product_filters.js"}

{if $block.type == "product_filters"}
    {$ajax_div_ids = "product_filters_*,products_search_*,category_products_*,product_features_*,breadcrumbs_*,currencies_*,languages_*,selected_filters_*"}
    {$curl = $config.current_url}
{else}
    {$curl = "products.search"|fn_url}
    {$ajax_div_ids = ""}
{/if}

{$filter_base_url = $curl|fn_query_remove:"result_ids":"full_render":"filter_id":"view_all":"req_range_id":"features_hash":"subcats":"page":"total"}

<div class="cm-product-filters" data-ca-target-id="{$ajax_div_ids}" data-ca-base-url="{$filter_base_url|fn_url}" id="product_filters_{$block.block_id}">
<div class="ty-product-filters__wrapper">
{if $items}

{assign var="encountered_unselected" value=false}

{foreach from=$items item="filter" name="filters"}
    
    {* --- НАЧАЛО ЛОГИКИ ПОСЛЕДОВАТЕЛЬНОСТИ --- *}
    {assign var="has_selected" value=false}
    {assign var="has_variants" value=false}

    {* Проверка: выбран ли фильтр? *}
    {if $filter.selected_variants|default:false || $filter.selected_range|default:false || $filter.selected_ranges|default:false}
        {assign var="has_selected" value=true}
    {/if}
    
    {if $filter.slider|default:false}
        {assign var="has_variants" value=true}
        {assign var="f_min" value=$filter.min|default:0}
        {assign var="f_max" value=$filter.max|default:0}
        {assign var="f_left" value=$filter.left|default:$f_min}
        {assign var="f_right" value=$filter.right|default:$f_max}
        {if $f_left > $f_min || $f_right < $f_max}
            {assign var="has_selected" value=true}
        {/if}
    {/if}
    
    {if !$has_selected && $filter.variants|default:false}
        {foreach from=$filter.variants item="v"}
            {if $v.selected|default:false}{assign var="has_selected" value=true}{/if}
        {/foreach}
    {/if}

    {* Проверка: есть ли вообще доступные варианты? *}
    {if $filter.variants|default:false}
        {foreach from=$filter.variants item="v"}
            {if !$v.disabled|default:false}{assign var="has_variants" value=true}{/if}
        {/foreach}
    {/if}
    {if $filter.ranges|default:false}
        {foreach from=$filter.ranges item="r"}
            {if !$r.disabled|default:false}{assign var="has_variants" value=true}{/if}
        {/foreach}
    {/if}

    {* Определяем, нужно ли показывать этот фильтр *}
    {assign var="show_this" value=false}
    {if $has_variants || $has_selected}
        {if $has_selected}
            {assign var="show_this" value=true}
            {assign var="encountered_unselected" value=false}
        {elseif !$encountered_unselected}
            {assign var="show_this" value=true}
            {assign var="encountered_unselected" value=true}
        {/if}
    {/if}
    {* --- КОНЕЦ ЛОГИКИ ПОСЛЕДОВАТЕЛЬНОСТИ --- *}


    {* ВЫВОД ФИЛЬТРА (только если show_this = true) *}
    {if $show_this}
        {hook name="blocks:product_filters_variants"}
        {assign var="filter_uid" value="`$block.block_id`_`$filter.filter_id`"}
        {assign var="cookie_name_show_filter" value="content_`$filter_uid`"}
        
        {if $filter.display|default:'N' == "N"}
            {* default behaviour of cm-combination *}
            {assign var="collapse" value=true}
            {if $smarty.cookies.$cookie_name_show_filter|default:false}
                {assign var="collapse" value=false}
            {/if}
        {else}
            {* reverse behaviour of cm-combination *}
            {assign var="collapse" value=false}
            {if $smarty.cookies.$cookie_name_show_filter|default:false}
                {assign var="collapse" value=true}
            {/if}
        {/if}

        {$reset_url = ""}
        {if $filter.selected_variants|default:false || $filter.selected_range|default:false}
            {$reset_url = $filter_base_url}
            {$fh = $smarty.request.features_hash|fn_delete_filter_from_hash:$filter.filter_id}
            {if $fh}
                {$reset_url = $filter_base_url|fn_link_attach:"features_hash=$fh"}
            {/if}
        {/if}

        <div class="ty-product-filters__block">
            <div id="sw_content_{$filter_uid}" class="ty-product-filters__switch cm-combination-filter_{$filter_uid}{if !$collapse} open{/if} cm-save-state {if $filter.display|default:'N' == "Y"}cm-ss-reverse{/if}">
                <span class="ty-product-filters__title">{$filter.filter}{if $filter.selected_variants|default:false} ({$filter.selected_variants|sizeof}){/if}{if $reset_url}<a class="cm-ajax cm-ajax-full-render cm-history" href="{$reset_url|fn_url}" data-ca-event="ce.filtersinit" data-ca-target-id="{$ajax_div_ids}" data-ca-scroll=".ty-mainbox-title"><i class="ty-icon-cancel-circle"></i></a>{/if}</span>
                <i class="ty-product-filters__switch-down ty-icon-down-open"></i>
                <i class="ty-product-filters__switch-right ty-icon-up-open"></i>
            </div>

            {hook name="blocks:product_filters_variants_element"}
                {if $filter.slider|default:false}
                    {if $filter.feature_type|default:'' == "D"}
                        {include file="blocks/product_filters/components/product_filter_datepicker.tpl" filter_uid=$filter_uid filter=$filter}
                    {else}
                        {include file="blocks/product_filters/components/product_filter_slider.tpl" filter_uid=$filter_uid filter=$filter}
                    {/if}
                {else}
                    {include file="blocks/product_filters/components/product_filter_variants.tpl" filter_uid=$filter_uid filter=$filter collapse=$collapse}
                {/if}
            {/hook}
        </div>
        {/hook}
    {/if}
{/foreach}

{if $ajax_div_ids}
<div class="ty-product-filters__tools clearfix">
    <a href="{$filter_base_url|fn_url}" rel="nofollow" class="ty-product-filters__reset-button cm-ajax cm-ajax-full-render cm-history" data-ca-event="ce.filtersinit" data-ca-scroll=".ty-mainbox-title" data-ca-target-id="{$ajax_div_ids}"><i class="ty-product-filters__reset-icon ty-icon-cw"></i> {__("reset")}</a>
</div>
{/if}

{/if}
</div>
</div>