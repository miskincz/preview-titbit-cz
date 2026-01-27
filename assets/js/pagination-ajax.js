jQuery(document).ready(function($) {
    // Inicializuj aktivní stránku při načtení
    var productList = $('#product-list');
    if (productList.length) {
        var currentPage = parseInt(productList.attr('data-page')) || 1;
        var paginationNav = $('.pagination');
        if (paginationNav.length) {
            // Nastav aktivní class na správném pagináčním linku
            paginationNav.find('.pagination__link').each(function() {
                var pageNum = parseInt($(this).text());
                if (pageNum === currentPage) {
                    $(this).addClass('pagination__link--active').attr('aria-current', 'page');
                } else {
                    $(this).removeClass('pagination__link--active').removeAttr('aria-current');
                }
            });
        }
    }
    
    // Stránkování s AJAX
    $(document).on('click', '.pagination__link', function(e) {
        e.preventDefault();
        
        var link = $(this);
        var url = link.attr('href');
        var page = link.text();
        var productList = $('#product-list');
        var termId = parseInt(productList.attr('data-term'));
        
        // Vygenerujem paged hodnotu
        var urlParams = new URLSearchParams(new URL(url, window.location.href).search);
        var paged = parseInt(urlParams.get('paged')) || 1;
        
        $.ajax({
            url: mytheme.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'load_more_products',
                term: termId,
                page: paged - 1  // API očekává page (0-indexed)
            },
            success: function(response) {
                // Nahradí všechny produkty novými
                productList.find('ul').html(response.html);
                productList.attr('data-page', response.paged);
                
                // Regeneruj stránkování
                regeneratePagination(response.paged, response.max_pages);
                
                // Scroll na seznam produktů
                $('html, body').animate({
                    scrollTop: productList.offset().top - 100
                }, 300);
            },
            error: function() {
                alert('Chyba při načítání produktů');
            }
        });
    });
    
    // "Načíst další" tlačítko - původní AJAX
    $(document).on('click', '#load-more', function() {
        var button = $(this);
        var productList = $('#product-list');
        var termId = parseInt(productList.attr('data-term'));
        var currentPage = parseInt(productList.attr('data-page')) || 1;
        var maxPage = parseInt(button.attr('data-max-page')) || 1;
        
        // Pokud jsme na poslední stránce, neloaduj více
        if (currentPage >= maxPage) {
            button.remove();
            return;
        }
        
        button.text('Načítání...').prop('disabled', true);
        
        $.ajax({
            url: mytheme.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'load_more_products',
                term: termId,
                page: currentPage
            },
            success: function(response) {
                // Přidej nové produkty
                productList.find('ul').append(response.html);
                
                var newPage = currentPage + 1;
                productList.attr('data-page', newPage);
                
                // Regeneruj stránkování
                regeneratePagination(newPage, response.max_pages);
                
                if (newPage >= response.max_pages) {
                    button.remove();
                } else {
                    button.text('Načíst další').prop('disabled', false);
                }
            },
            error: function(error) {
                console.error('AJAX Error:', error);
                button.text('Chyba - zkusit znovu').prop('disabled', false);
            }
        });
    });
    
    // Regeneruj stránkování
    function regeneratePagination(currentPage, maxPages) {
        var paginationNav = $('.pagination');
        if (paginationNav.length === 0) return;
        
        var pages_to_show = 5;
        var range = Math.floor(pages_to_show / 2);
        var start = Math.max(1, currentPage - range);
        var end = Math.min(maxPages, start + pages_to_show - 1);
        
        if (end - start + 1 < pages_to_show) {
            start = Math.max(1, end - pages_to_show + 1);
        }
        
        var html = '';
        
        // Čísla stran
        var lastPrinted = 0;
        for (var i = 1; i <= maxPages; i++) {
            if (i == 1 || i == maxPages || (i >= start && i <= end)) {
                // Tři tečky pokud je mezera
                if (i > lastPrinted + 1 && lastPrinted > 0) {
                    html += '<span class="pagination__dots">...</span>';
                }
                
                var activeClass = (i === currentPage) ? ' pagination__link--active' : '';
                var ariaCurrent = (i === currentPage) ? ' aria-current="page"' : '';
                html += '<a href="?paged=' + i + '" class="pagination__link' + activeClass + '"' + ariaCurrent + '>' + i + '</a>';
                lastPrinted = i;
            }
        }
        
        paginationNav.html(html);
    }
});
