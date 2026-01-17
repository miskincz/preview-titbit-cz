jQuery(document).ready(function($) {
    // Načíst další produkty
    $('#load-more').on('click', function() {
        var button = $(this);
        var productList = $('#product-list');
        var termId = parseInt(productList.attr('data-term'));
        var currentPage = parseInt(productList.attr('data-page'));
        var maxPage = parseInt(button.attr('data-max-page'));
        
        button.text('Načítání...').prop('disabled', true);
        
        $.ajax({
            url: mytheme.ajax_url,
            type: 'POST',
            data: {
                action: 'load_more_products',
                term: termId,
                page: currentPage
            },
            success: function(response) {
                productList.find('ul').append(response);
                
                var newPage = currentPage + 1;
                productList.attr('data-page', newPage);
                
                if (newPage >= maxPage) {
                    button.remove();
                } else {
                    button.text('Načíst další').prop('disabled', false);
                }
            },
            error: function() {
                button.text('Chyba - zkusit znovu').prop('disabled', false);
            }
        });
    });
    
    // Načíst další blogové příspěvky
    $('#load-more-blog').on('click', function() {
        var button = $(this);
        var blogList = $('#blog-list');
        var categoryId = parseInt(blogList.attr('data-category'));
        var currentPage = parseInt(blogList.attr('data-page'));
        var maxPage = parseInt(button.attr('data-max-page'));
        
        button.text('Načítání...').prop('disabled', true);
        
        $.ajax({
            url: mytheme.ajax_url,
            type: 'POST',
            data: {
                action: 'load_more_blog_posts',
                category: categoryId,
                page: currentPage
            },
            success: function(response) {
                blogList.append(response);
                
                var newPage = currentPage + 1;
                blogList.attr('data-page', newPage);
                
                if (newPage >= maxPage) {
                    button.remove();
                } else {
                    button.text('Načíst další').prop('disabled', false);
                }
            },
            error: function() {
                button.text('Chyba - zkusit znovu').prop('disabled', false);
            }
        });
    });
});

// SMAŽTE tento soubor - používáme load-more-posts.js
