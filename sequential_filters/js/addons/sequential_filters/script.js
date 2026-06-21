(function(_, $) {
    // Храним видимые фильтры с привязкой к ID родительского контейнера,
    // чтобы скрипт корректно работал, если на странице несколько блоков фильтров
    var blockFiltersState = {};

    // Хук на завершение рендера/обновления блоков (включая AJAX)
    $.ceEvent('on', 'ce.commoninit', function(context) {
        
        // Ищем контейнеры с фильтрами в обновленном контексте
        var $filtersBlocks = context.find('.cm-product-filters');
        if (context.hasClass('cm-product-filters')) {
            $filtersBlocks = context;
        }

        if ($filtersBlocks.length) {
            $filtersBlocks.each(function() {
                var $container = $(this);
                // Получаем ID блока фильтров или генерируем временный, если его вдруг нет
                var containerId = $container.attr('id') || 'default_filters_' + Math.random();
                var newFilters = [];
                
                $container.find('.ty-product-filters__block').each(function() {
                    var $block = $(this);
                    
                    // Пытаемся получить уникальный ID фильтра
                    var filterId = $block.find('[id^="sw_content_"]').attr('id');
                    if (!filterId) {
                        filterId = $block.find('.ty-product-filters__title').text().trim();
                    }
                    
                    if (filterId) {
                        newFilters.push(filterId);

                        // Проверяем, если стейт для этого блока уже существует и текущего фильтра в нем не было
                        if (blockFiltersState[containerId] && blockFiltersState[containerId].indexOf(filterId) === -1) {
                            
                            // Снимаем класс, если он уже был
                            $block.removeClass('sq-filter-animate');
                            
                            // Принудительно вызываем "Reflow" (перерисовку DOM браузером),
                            // чтобы анимация гарантированно перезапустилась с самого начала
                            void $block[0].offsetWidth;
                            
                            // Вешаем класс анимации
                            $block.addClass('sq-filter-animate');
                        }
                    }
                });
                
                // Обновляем состояние конкретного блока фильтров
                blockFiltersState[containerId] = newFilters;
            });
        }
    });
}(Tygh, Tygh.$));