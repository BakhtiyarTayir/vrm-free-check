/**
 * VRM Check History JavaScript
 */

(function($) {
    'use strict';
    
    // Глобальные переменные
    let currentPage = 1;
    let isLoading = false;
    
    $(document).ready(function() {
        initFilters();
        initLoadMore();
    });
    
    /**
     * Инициализация фильтров
     */
    function initFilters() {
        // Поиск по VRM
        $('#vrm-search').on('input', debounce(function() {
            filterHistory();
        }, 300));
        
        // Фильтр по типу
        $('#vrm-filter-type').on('change', function() {
            filterHistory();
        });
        
        // Очистка фильтров
        $('#vrm-clear-filters').on('click', function() {
            $('#vrm-search').val('');
            $('#vrm-filter-type').val('');
            filterHistory();
        });
    }
    
    /**
     * Фильтрация истории
     */
    function filterHistory() {
        const searchTerm = $('#vrm-search').val().toLowerCase();
        const filterType = $('#vrm-filter-type').val();
        
        $('.vrm-history-item').each(function() {
            const $item = $(this);
            const vrm = $item.find('.vrm-badge').text().toLowerCase();
            const type = $item.find('.vrm-type-badge').hasClass('vrm-premium') ? 'premium' : 'basic';
            
            let showItem = true;
            
            // Фильтр по VRM
            if (searchTerm && !vrm.includes(searchTerm)) {
                showItem = false;
            }
            
            // Фильтр по типу
            if (filterType && type !== filterType) {
                showItem = false;
            }
            
            $item.toggle(showItem);
        });
        
        // Показать/скрыть сообщение "No results"
        checkEmptyState();
    }
    
    /**
     * Проверка пустого состояния
     */
    function checkEmptyState() {
        const visibleItems = $('.vrm-history-item:visible').length;
        
        if (visibleItems === 0 && !$('.vrm-history-no-results').length) {
            $('.vrm-history-list').append(
                '<div class="vrm-history-no-results vrm-history-empty">' +
                    '<div class="vrm-empty-icon">🔍</div>' +
                    '<h3>No Results Found</h3>' +
                    '<p>Try adjusting your filters or search term.</p>' +
                '</div>'
            );
        } else if (visibleItems > 0) {
            $('.vrm-history-no-results').remove();
        }
    }
    
    /**
     * Инициализация "Load More"
     */
    function initLoadMore() {
        $('#vrm-load-more').on('click', function() {
            if (isLoading) return;
            
            isLoading = true;
            const $button = $(this);
            const originalText = $button.text();
            
            $button.text('Loading...').prop('disabled', true);
            
            // Здесь будет AJAX запрос для загрузки следующей страницы
            // Пока просто симулируем
            setTimeout(function() {
                $button.text(originalText).prop('disabled', false);
                isLoading = false;
                
                // Если больше нет данных, скрыть кнопку
                // $button.hide();
            }, 1000);
        });
    }
    
    /**
     * Debounce функция
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
})(jQuery);

/**
 * Просмотр отчёта
 */
function viewReport(checkId) {
    console.log('View report:', checkId);
    // Перенаправляем на страницу полного отчёта
    window.location.href = '/vrm-report/' + checkId + '/';
}

/**
 * Скачать отчет в PDF
 */
function downloadReport(checkId) {
    console.log('Downloading report:', checkId);
    
    // Создаем форму для скачивания
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = vrmHistory.ajax_url;
    form.target = '_blank';
    
    // Добавляем параметры
    const params = {
        action: 'vrm_download_report',
        check_id: checkId,
        nonce: vrmHistory.nonce
    };
    
    for (const key in params) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = params[key];
        form.appendChild(input);
    }
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}
