(function(_, $) {
    // Массив для хранения идентификаторов уже видимых фильтров на экране
    var currentFilters = [];

    // Хук на завершение рендера/обновления блоков (включая AJAX)
    $.ceEvent('on', 'ce.commoninit', function(context) {
        
        // Ищем контейнер с фильтрами в обновленном контексте
        var $filtersWrapper = context.find('.ty-product-filters__wrapper');
        if (context.hasClass('ty-product-filters__wrapper')) {
            $filtersWrapper = context;
        }

        if ($filtersWrapper && $filtersWrapper.length) {
            var newFilters = [];
            
            // Пробегаемся по каждому блоку фильтра внутри
            $filtersWrapper.find('.ty-product-filters__block').each(function() {
                var $block = $(this);
                
                // Пытаемся получить уникальный ID фильтра (CS-Cart использует префикс sw_content_ для заголовков)
                var filterId = $block.find('[id^="sw_content_"]').attr('id');
                if (!filterId) {
                    // Фолбэк: если ID нет, берем текст заголовка
                    filterId = $block.find('.ty-product-filters__title').text().trim();
                }
                
                if (filterId) {
                    newFilters.push(filterId);

                    // Если массив видимых фильтров уже был заполнен (т.е. это не первая загрузка страницы)
                    // и текущего фильтра в нем не было — значит он только что появился из-за AJAX.
                    if (currentFilters.length > 0 && currentFilters.indexOf(filterId) === -1) {
                        $block.addClass('sq-filter-animate');
                    }
                }
            });
            
            // Обновляем состояние текущих видимых фильтров
            currentFilters = newFilters;
        }
    });
}(Tygh, Tygh.$));