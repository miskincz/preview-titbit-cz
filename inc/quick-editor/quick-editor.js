/**
 * Quick Product Editor - JavaScript (Inline edita v tabulce)
 * Optimalizováno pro výkon s debouncing, caching, event delegation
 */

(function($) {
    'use strict';
    
    /**
     * Utility funkcí pro debouncing a throttling
     */
    var Debounce = {
        timers: {},
        debounce: function(key, fn, delay) {
            clearTimeout(this.timers[key]);
            this.timers[key] = setTimeout(fn, delay || 300);
        }
    };
    
    /**
     * Cache pro produkty a ACF data
     */
    var Cache = {
        products: {},
        acfData: {},
        clear: function(key) {
            if (key) {
                delete this.products[key];
                delete this.acfData[key];
            } else {
                this.products = {};
                this.acfData = {};
            }
        }
    };
    
    /**
     * Objekt pro správu editoru produktů
     */
    var QPE = {
        currentPage: 1,
        perPage: qpeData.perPage || 20,
        searchQuery: '',
        selectedCategory: 0,
        $container: null,
        $table: null,
        $paginationContainer: null,
        isLoading: false,
        ajaxQueue: [],
        
        /**
         * Inicializace
         */
        init: function() {
            this.cacheDOM();
            this.bindEvents();
            this.loadProducts();
        },
        
        /**
         * Cache DOM elementy pro lepší výkon
         */
        cacheDOM: function() {
            this.$container = $('.qpe-container');
            this.$table = $('#qpe-products-list');
            this.$paginationContainer = $('#qpe-pagination');
        },
        
        /**
         * Binding events - event delegation s cacheovanými selektory
         */
        bindEvents: function() {
            var self = this;
            
            // Vyhledávání - s debouncing
            this.$container.on('keyup', '#qpe-search', function(e) {
                if (e.which === 13) { // Enter
                    e.preventDefault();
                    self.performSearch();
                } else {
                    // Debounce vyhledávání na 500ms
                    Debounce.debounce('search', function() {
                        self.performSearch();
                    }, 500);
                }
            });
            
            this.$container.on('click', '#qpe-search-btn', function(e) {
                e.preventDefault();
                self.performSearch();
            });
            
            // Filtrování - kategorie
            this.$container.on('change', '#qpe-category', function() {
                self.currentPage = 1;
                self.selectedCategory = parseInt($(this).val()) || 0;
                self.loadProducts();
            });
            
            // Reset
            this.$container.on('click', '#qpe-reset-btn', function(e) {
                e.preventDefault();
                $('#qpe-search').val('');
                $('#qpe-category').val('');
                self.currentPage = 1;
                self.searchQuery = '';
                self.selectedCategory = 0;
                Cache.clear();
                self.loadProducts();
            });
            
            // Paginace - delegace
            this.$container.on('click', '#qpe-pagination a', function(e) {
                e.preventDefault();
                var pageNum = parseInt($(this).attr('data-page'));
                if (!isNaN(pageNum) && pageNum > 0) {
                    self.currentPage = pageNum;
                    self.loadProducts();
                    // Smooth scroll
                    $('html, body').animate({
                        scrollTop: $('.qpe-table-wrapper').offset().top - 100
                    }, 300);
                }
            });
            
            // Edita - event delegation na tabulce
            this.$table.on('click', 'tr', function(e) {
                if ($(e.target).closest('a').length) return; // Ignorovat linky
                
                var $row = $(this);
                var productId = $row.data('product-id');
                if (!productId) return;
                
                var $target = $(e.target);
                var $imgCell = $target.closest('.col-img');
                var $acfCell = $target.closest('.qpe-acf-cell');
                
                if ($imgCell.length) {
                    e.preventDefault();
                    self.openThumbnailEditor(productId);
                } else if ($acfCell.length) {
                    e.preventDefault();
                    var fieldName = $acfCell.attr('data-field');
                    
                    if (fieldName === 'produkty_dostupnost' || fieldName === 'produkty_baleni') {
                        self.openInlineEditor($acfCell, productId, fieldName);
                    } else if (fieldName === 'produkty_galerie' || fieldName === 'acf_galerie') {
                        self.openGalleryEditor($acfCell, productId);
                    } else {
                        self.openEditModal(productId, fieldName);
                    }
                } else {
                    self.openEditModal(productId, null);
                }
            });
            
            // Modal akce
            this.$container.on('click', '#qpe-modal-save', function(e) {
                e.preventDefault();
                self.saveProduct();
            });
            
            this.$container.on('click', '#qpe-modal-close, #qpe-modal-close-btn', function(e) {
                e.preventDefault();
                self.closeModal();
            });
            
            // Zavřít modal kliknutím na overlay
            this.$container.on('click', '.qpe-modal-overlay', function(e) {
                if ($(e.target).hasClass('qpe-modal-overlay')) {
                    self.closeModal();
                }
            });
        },
        
        /**
         * Vyhledávání s validací
         */
        performSearch: function() {
            this.currentPage = 1;
            var newQuery = $('#qpe-search').val().trim();
            
            if (newQuery !== this.searchQuery) {
                this.searchQuery = newQuery;
                Cache.clear(); // Vyčistit cache při novém vyhledávání
                this.loadProducts();
            }
        },
        
        /**
         * Načtení produktů s caching a error handling
         */
        loadProducts: function() {
            var self = this;
            
            if (this.isLoading) return; // Zabránit duplicitním requestům
            
            var cacheKey = this.currentPage + '_' + this.searchQuery + '_' + this.selectedCategory;
            
            // Pokud je v cache, použít
            if (Cache.products[cacheKey]) {
                this.renderProducts(Cache.products[cacheKey]);
                this.renderPagination(Cache.products[cacheKey]);
                return;
            }
            
            this.isLoading = true;
            this.showLoading();
            
            // AJAX s timeout
            $.ajax({
                url: qpeData.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                timeout: 30000, // 30 sekund timeout
                data: {
                    action: 'qpe_load_products',
                    nonce: qpeData.nonce,
                    paged: self.currentPage,
                    per_page: self.perPage,
                    search: self.searchQuery,
                    category: self.selectedCategory
                },
                success: function(response) {
                    self.isLoading = false;
                    if (response.success && response.data) {
                        Cache.products[cacheKey] = response.data;
                        self.renderProducts(response.data);
                        self.renderPagination(response.data);
                    } else {
                        self.showError(response.data || 'Chyba při načítání');
                    }
                },
                error: function(xhr, status, error) {
                    self.isLoading = false;
                    var errMsg = 'Chyba při načítání';
                    if (status === 'timeout') {
                        errMsg = 'Timeout - server neodpověděl';
                    } else if (status === 'error') {
                        errMsg = 'Chyba serveru (kód ' + xhr.status + ')';
                    }
                    self.showError(errMsg);
                }
            });
        },
        
        /**
         * Zobrazení loading stavu
         */
        showLoading: function() {
            var skeletonRows = '';
            for (var i = 0; i < 5; i++) {
                skeletonRows += '<tr class="qpe-loading">' +
                    '<td class="col-img"><div class="qpe-skeleton" style="width:60px;height:60px;border-radius:4px;"></div></td>' +
                    '<td class="col-name"><div class="qpe-skeleton" style="height:14px;"></div></td>' +
                    '<td class="col-acf"><div class="qpe-skeleton" style="height:14px;"></div></td>' +
                    '<td class="col-acf"><div class="qpe-skeleton" style="height:14px;"></div></td>' +
                    '<td class="col-acf"><div class="qpe-skeleton" style="height:14px;"></div></td>' +
                    '<td class="col-actions"><div class="qpe-skeleton" style="height:14px;"></div></td>' +
                    '</tr>';
            }
            this.$table.html(skeletonRows);
        },
        
        /**
         * Zobrazení chyby s retry
         */
        showError: function(message) {
            var self = this;
            this.$table.html(
                '<tr><td colspan="6" style="text-align: center; padding: 40px;">' +
                '<div style="color: #d32f2f; font-size: 16px;"><strong>❌ ' + this.escapeHtml(message) + '</strong></div>' +
                '<p style="margin-top: 10px;"><button class="button qpe-retry-btn">Zkusit znovu</button></p>' +
                '</td></tr>'
            );
            
            this.$table.off('click.retry').on('click.retry', '.qpe-retry-btn', function(e) {
                e.preventDefault();
                self.loadProducts();
            });
        },
        
        /**
         * Vykreslení produktů v tabulce
         */
        renderProducts: function(data) {
            var self = this;
            var html = '';
            
            if (!data.products || data.products.length === 0) {
                html = '<tr><td colspan="6" class="qpe-no-results">' +
                       '<div class="qpe-no-results-icon">📭</div>' +
                       '<strong>Žádné produkty nenalezeny</strong>' +
                       '</td></tr>';
            } else {
                $.each(data.products, function(index, product) {
                    html += self.renderProductRow(product);
                });
            }
            
            $('#qpe-products-list').html(html);
        },
        
        /**
         * Vykreslení jednoho řádku produktu
         */
        renderProductRow: function(product) {
            var thumbnail = '';
            
            if (product.thumbnail_url) {
                thumbnail = '<img src="' + product.thumbnail_url + '" alt="' + this.escapeHtml(product.title) + '">';
            } else {
                thumbnail = '<div class="qpe-no-image">Bez obrázku</div>';
            }
            
            // ACF fieldy - Balení a Dostupnost
            var baleni = product.acf_baleni || '—';
            var dostupnost = product.acf_dostupnost || '—';
            var galerie = product.acf_galerie || [];
            
            // Formátovat pole pro zobrazení
            if (typeof baleni === 'object' && baleni.length) {
                baleni = baleni.join(', ');
            } else if (typeof baleni === 'object') {
                baleni = '—';
            }
            
            if (typeof dostupnost === 'object' && dostupnost.length) {
                dostupnost = dostupnost.join(', ');
            } else if (typeof dostupnost === 'object') {
                dostupnost = '—';
            }
            
            // Počet fotek v galerii
            var galerie_text = '—';
            var galerie_count = 0;
            
            if (galerie) {
                // Pokud je to pole (array)
                if (Array.isArray(galerie)) {
                    galerie_count = galerie.length;
                } 
                // Pokud je to objekt (např. ACF vrací někdy objekt)
                else if (typeof galerie === 'object' && galerie !== null) {
                    galerie_count = Object.keys(galerie).length;
                }
                // Fallback na string
                else if (typeof galerie === 'string' && galerie.length > 0) {
                    galerie_text = galerie;
                }
            }
            
            // Formátovat na text
            if (galerie_count > 0) {
                galerie_text = galerie_count + ' ' + (galerie_count === 1 ? 'foto' : 'fotek');
            }
            
            // Debugging - vypsat do console
            if (!this.debuggedGalerie && galerie_count === 0 && JSON.stringify(galerie) !== '[]' && JSON.stringify(galerie) !== '{}') {
                console.log('Galerie debug:', 'raw:', galerie, 'type:', typeof galerie, 'isArray:', Array.isArray(galerie), 'keys:', Object.keys(galerie || {}));
                this.debuggedGalerie = true;
            }
            
            var html = '<tr data-product-id="' + product.id + '" style="cursor: pointer;">' +
                       '<td class="col-img">' + thumbnail + '</td>' +
                       '<td class="col-name"><strong>' + this.escapeHtml(product.title) + '</strong></td>' +
                       '<td class="col-acf qpe-acf-cell" data-field="produkty_baleni">' + this.escapeHtml(baleni) + '</td>' +
                       '<td class="col-acf qpe-acf-cell" data-field="produkty_dostupnost">' + this.escapeHtml(dostupnost) + '</td>' +
                       '<td class="col-acf qpe-acf-cell qpe-galerie-cell" data-field="produkty_galerie" title="' + this.escapeHtml(galerie_text) + '">' + this.escapeHtml(galerie_text) + '</td>' +
                       '<td class="col-actions">' +
                       '<div class="qpe-actions">' +
                       '<a href="' + product.edit_link + '" target="_blank" class="qpe-edit-link" title="Otevřít v editoru">Upravit</a>' +
                       '<a href="' + product.view_link + '" target="_blank" class="qpe-view-link" title="Zobrazit na webu">Zobrazit</a>' +
                       '</div>' +
                       '</td>' +
                       '</tr>';
            
            return html;
        },
        
        /**
         * Otevření modal editoru
         */
        openEditModal: function(productId, focusField) {
            var self = this;
            
            // Najít produkt v seznamu
            var $row = $('tr[data-product-id="' + productId + '"]');
            var title = $row.find('.col-name strong').text();
            var thumbImg = $row.find('.col-img img').attr('src') || '';
            
            // Vytvořit základní modal
            var modalHtml = '<div class="qpe-modal-overlay">' +
                            '<div class="qpe-modal">' +
                            '<div class="qpe-modal-header">' +
                            '<h2>Úprava: ' + self.escapeHtml(title) + '</h2>' +
                            '<button id="qpe-modal-close" class="qpe-modal-close">×</button>' +
                            '</div>' +
                            '<div class="qpe-modal-body">' +
                            '<div class="qpe-form-group">' +
                            '<label>Úvodní obrázek:</label>' +
                            '<div class="qpe-image-preview" id="qpe-image-preview">' +
                            (thumbImg ? '<img src="' + thumbImg + '" alt="Náhled">' : '<p class="qpe-no-image-text">Žádný obrázek</p>') +
                            '</div>' +
                            '<button type="button" id="qpe-upload-btn" class="button">Změnit obrázek</button>' +
                            '<input type="hidden" id="qpe-thumbnail-id" value="">' +
                            '</div>' +
                            '<div class="qpe-form-group">' +
                            '<label for="qpe-edit-title">Název produktu:</label>' +
                            '<input type="text" id="qpe-edit-title" value="' + self.escapeHtml(title) + '" class="widefat">' +
                            '</div>' +
                            '<div id="qpe-acf-fields"><span class="spinner is-active"></span> Načítám pole...</div>' +
                            '</div>' +
                            '<div class="qpe-modal-footer">' +
                            '<button id="qpe-modal-close-btn" class="button">Zrušit</button>' +
                            '<button id="qpe-modal-save" class="button button-primary" data-product-id="' + productId + '">Uložit</button>' +
                            '</div>' +
                            '</div>' +
                            '</div>';
            
            // Vložit modal do DOM
            $('body').append(modalHtml);
            
            // Upravit image uploader
            var frame;
            $(document).on('click', '#qpe-upload-btn', function(e) {
                e.preventDefault();
                
                if (frame) {
                    frame.open();
                    return;
                }
                
                frame = wp.media({
                    title: 'Vyberte obrázek produktu',
                    button: {
                        text: 'Vybrat obrázek'
                    },
                    multiple: false
                });
                
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#qpe-thumbnail-id').val(attachment.id);
                    $('#qpe-image-preview').html('<img src="' + attachment.url + '" alt="Náhled">');
                });
                
                frame.open();
            });
            
            // Načtení ACF polí
            self.loadACFData(productId, focusField);
            
            // Zaměřit se na title input
            $('#qpe-edit-title').focus();
        },
        
        /**
         * Načtení ACF dat
         */
        loadACFData: function(productId, focusField) {
            var self = this;
            
            $.ajax({
                url: qpeData.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'qpe_load_acf_data',
                    nonce: qpeData.nonce,
                    product_id: productId
                },
                success: function(response) {
                    console.log('ACF Data Loaded:', response.data);
                    if (response.success) {
                        self.renderACFFields(response.data, focusField);
                    } else {
                        $('#qpe-acf-fields').html('<p style="color: #d32f2f;">Chyba: ' + response.data + '</p>');
                    }
                },
                error: function() {
                    $('#qpe-acf-fields').html('<p style="color: #d32f2f;">Chyba při načítání polí.</p>');
                }
            });
        },
        
        /**
         * Vykreslení ACF polí
         */
        renderACFFields: function(fields, focusField) {
            var self = this;
            var html = '';
            
            $.each(fields, function(fieldName, fieldData) {
                var value = fieldData.value || '';
                var fieldType = fieldData.type || 'text';
                var inputHtml = '';
                
                // Speciální handling pro checkbox typ
                if (fieldType === 'checkbox') {
                    inputHtml = '<div class="qpe-acf-checkbox-group" data-field="' + fieldName + '">';
                    
                    if (Array.isArray(value)) {
                        // Pokud je pole, vytvořit checkbox pro každou hodnotu
                        $.each(value, function(index, val) {
                            inputHtml += '<label class="qpe-checkbox-option">' +
                                        '<input type="checkbox" name="' + fieldName + '[]" value="' + self.escapeHtml(val) + '" checked data-field="' + fieldName + '">' +
                                        '<span>' + self.escapeHtml(val) + '</span>' +
                                        '</label>';
                        });
                    } else {
                        // Jednoduchý checkbox
                        inputHtml += '<label class="qpe-checkbox-option">' +
                                    '<input type="checkbox" name="' + fieldName + '" value="1" ' + (value ? 'checked' : '') + ' data-field="' + fieldName + '">' +
                                    '<span>Zaškrtnuto</span>' +
                                    '</label>';
                    }
                    
                    inputHtml += '</div>';
                } else if (fieldType === 'textarea') {
                    // Textarea
                    if (typeof value === 'object') {
                        value = JSON.stringify(value);
                    }
                    inputHtml = '<textarea name="' + fieldName + '" class="widefat" rows="6" data-field="' + fieldName + '">' + 
                               self.escapeHtml(value) + 
                               '</textarea>';
                } else {
                    // Text input (výchozí)
                    if (typeof value === 'object') {
                        value = JSON.stringify(value);
                    }
                    inputHtml = '<input type="text" name="' + fieldName + '" class="widefat" value="' + 
                               self.escapeHtml(value) + '" data-field="' + fieldName + '">';
                }
                
                html += '<div class="acf-field">' +
                        '<div class="acf-label">' +
                        '<label>' + self.escapeHtml(fieldData.label) + '</label>' +
                        '</div>' +
                        '<div class="acf-input">' +
                        inputHtml +
                        '</div>' +
                        '</div>';
            });
            
            $('#qpe-acf-fields').html(html);
            
            // Fokus na pole když se otevře modal
            if (focusField) {
                var $focusTarget = $('[data-field="' + focusField + '"]').first();
                if ($focusTarget.length) {
                    setTimeout(function() {
                        $focusTarget.focus();
                        // Scroll do tohoto pole
                        var scrollTarget = $focusTarget.closest('.acf-field').offset().top - 200;
                        $('.qpe-modal-body').animate({ scrollTop: scrollTarget }, 300);
                    }, 100);
                }
            }
        },
        
        /**
         * Renderování jednotlivého ACF input prvku
         */
        renderACFInput: function(field) {
            var self = this;
            var html = '';
            var value = field.value || '';
            var type = field.type || 'text';
            
            switch(type) {
                case 'text':
                    html = '<input type="text" id="' + field.id + '" name="' + field.name + '" value="' + self.escapeHtml(value) + '" class="widefat" data-field="' + field.name + '">';
                    break;
                
                case 'textarea':
                    html = '<textarea id="' + field.id + '" name="' + field.name + '" class="widefat" rows="6" data-field="' + field.name + '">' + self.escapeHtml(value) + '</textarea>';
                    break;
                
                case 'checkbox':
                    // Checkbox field - renderovat checkboxy
                    html = '<div class="qpe-acf-checkbox-group" data-field="' + field.name + '">';
                    
                    if (field.choices && typeof field.choices === 'object') {
                        // Pokud má ACF choices definované
                        $.each(field.choices, function(choice_value, choice_label) {
                            var checked = (Array.isArray(value) && value.indexOf(choice_value) !== -1) ? 'checked' : '';
                            html += '<label class="qpe-checkbox-option">' +
                                   '<input type="checkbox" name="' + field.name + '[]" value="' + self.escapeHtml(choice_value) + '" ' + checked + ' data-field="' + field.name + '">' +
                                   self.escapeHtml(choice_label) +
                                   '</label>';
                        });
                    } else if (Array.isArray(value)) {
                        // Jestli nemáme choices, vytvořit checkbox pro každou hodnotu
                        $.each(value, function(index, val) {
                            html += '<label class="qpe-checkbox-option">' +
                                   '<input type="checkbox" name="' + field.name + '[]" value="' + self.escapeHtml(val) + '" checked data-field="' + field.name + '">' +
                                   self.escapeHtml(val) +
                                   '</label>';
                        });
                    }
                    
                    html += '</div>';
                    break;
                
                case 'select':
                    html = '<select id="' + field.id + '" name="' + field.name + '" class="widefat" data-field="' + field.name + '">';
                    if (field.choices && typeof field.choices === 'object') {
                        html += '<option value="">-- Vybrat --</option>';
                        $.each(field.choices, function(choice_value, choice_label) {
                            var selected = (value === choice_value) ? 'selected' : '';
                            html += '<option value="' + self.escapeHtml(choice_value) + '" ' + selected + '>' + self.escapeHtml(choice_label) + '</option>';
                        });
                    }
                    html += '</select>';
                    break;
                
                default:
                    // Výchozí - text input
                    if (typeof value === 'object') {
                        value = JSON.stringify(value);
                    }
                    html = '<input type="text" id="' + field.id + '" name="' + field.name + '" value="' + self.escapeHtml(value) + '" class="widefat" data-field="' + field.name + '">';
            }
            
            return html;
        },
        
        /**
         * Uložení produktu
         */
        saveProduct: function() {
            var self = this;
            var productId = $('#qpe-modal-save').data('product-id');
            var newTitle = $('#qpe-edit-title').val().trim();
            var thumbnailId = $('#qpe-thumbnail-id').val();
            
            // Sbírání ACF dat
            var acfData = {};
            
            // Sbírat data ze všech input prvků s data-field atributem
            $('[data-field]').each(function() {
                var $field = $(this);
                var fieldName = $field.data('field');
                var $parent = $field.closest('.qpe-acf-checkbox-group, .acf-input');
                
                // Checkbox - sbírat všechny zaškrtnuté
                if ($field.is('input[type="checkbox"]')) {
                    // Pokud je field name s [], sbírat všechny checkboxy
                    if (fieldName.indexOf('[') !== -1) {
                        // Přeskočit - bude zpracováno v $.fn.serializeArray
                        return;
                    }
                    
                    // Hledat všechny checkboxy v kontejneru s tímto polem
                    if ($parent.length) {
                        var checkedValues = [];
                        $parent.find('input[type="checkbox"]:checked').each(function() {
                            checkedValues.push($(this).val());
                        });
                        
                        if (!acfData.hasOwnProperty(fieldName)) {
                            acfData[fieldName] = checkedValues;
                        }
                    } else {
                        // Jednoduchý checkbox
                        if (!acfData.hasOwnProperty(fieldName)) {
                            acfData[fieldName] = $field.is(':checked') ? '1' : '';
                        }
                    }
                } else if (!acfData.hasOwnProperty(fieldName)) {
                    // Text input, textarea - sbírat val()
                    acfData[fieldName] = $field.val();
                }
            });
            
            if (!newTitle) {
                alert('Název produktu nemůže být prázdný!');
                return;
            }
            
            // AJAX požadavek pro uložení
            $.ajax({
                url: qpeData.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'qpe_save_product',
                    nonce: qpeData.nonce,
                    product_id: productId,
                    title: newTitle,
                    thumbnail_id: thumbnailId
                },
                success: function(response) {
                    if (response.success) {
                        // Uložit ACF fieldy
                        if (Object.keys(acfData).length > 0) {
                            $.ajax({
                                url: qpeData.ajaxUrl,
                                type: 'POST',
                                dataType: 'json',
                                data: {
                                    action: 'qpe_save_acf',
                                    nonce: qpeData.nonce,
                                    product_id: productId,
                                    acf_data: acfData
                                },
                                success: function(response2) {
                                    alert('Produkt byl úspěšně uložen!');
                                    self.closeModal();
                                    self.loadProducts();
                                },
                                error: function() {
                                    alert('Produkt uložen, ale ACF pole se nepovedlo uložit.');
                                    self.closeModal();
                                    self.loadProducts();
                                }
                            });
                        } else {
                            alert('Produkt byl úspěšně uložen!');
                            self.closeModal();
                            self.loadProducts();
                        }
                    } else {
                        alert('Chyba: ' + response.data);
                    }
                },
                error: function() {
                    alert('AJAX chyba při ukládání!');
                }
            });
        },
        
        /**
         * Zavření modalu
         */
        closeModal: function() {
            $('.qpe-modal-overlay').remove();
        },
        
        /**
         * Vykreslení paginace
         */
        renderPagination: function(data) {
            var self = this;
            var html = '';
            var totalPages = parseInt(data.total_pages);
            var currentPage = parseInt(data.current_page);
            
            if (totalPages <= 1) {
                $('#qpe-pagination').html('');
                return;
            }
            
            // Předchozí stránka
            if (currentPage > 1) {
                html += '<a href="#" data-page="' + (currentPage - 1) + '" class="prev">← Předchozí</a>';
            } else {
                html += '<span class="disabled prev">← Předchozí</span>';
            }
            
            // Čísla stránek
            var startPage = Math.max(1, currentPage - 2);
            var endPage = Math.min(totalPages, currentPage + 2);
            
            if (startPage > 1) {
                html += '<a href="#" data-page="1" class="first">1</a>';
                if (startPage > 2) {
                    html += '<span class="dots">...</span>';
                }
            }
            
            for (var i = startPage; i <= endPage; i++) {
                if (i === currentPage) {
                    html += '<span class="current">' + i + '</span>';
                } else {
                    html += '<a href="#" data-page="' + i + '">' + i + '</a>';
                }
            }
            
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    html += '<span class="dots">...</span>';
                }
                html += '<a href="#" data-page="' + totalPages + '" class="last">' + totalPages + '</a>';
            }
            
            // Další stránka
            if (currentPage < totalPages) {
                html += '<a href="#" data-page="' + (currentPage + 1) + '" class="next">Další →</a>';
            } else {
                html += '<span class="disabled next">Další →</span>';
            }
            
            // Informace o počtu
            var from = (currentPage - 1) * self.perPage + 1;
            var to = Math.min(currentPage * self.perPage, data.total_posts);
            
            html += '<div style="margin-top: 10px; font-size: 12px; color: #666;">' +
                    'Zobrazuji ' + from + '–' + to + ' z ' + data.total_posts + ' produktů' +
                    '</div>';
            
            $('#qpe-pagination').html(html);
        },
        
        /**
         * Zobrazení chyby
         */
        showError: function(message) {
            var errorHtml = '<tr><td colspan="6" style="padding: 20px; color: #d32f2f; text-align: center;">' +
                           '<strong>⚠️ Chyba:</strong> ' + this.escapeHtml(message) +
                           '</td></tr>';
            $('#qpe-products-list').html(errorHtml);
        },
        
        /**
         * Inline edita (dostupnost, balení, atd.)
         */
        openInlineEditor: function($cell, productId) {
            var self = this;
            var fieldName = $cell.attr('data-field');
            
            // Pokud už je editor otevřen, zastavit
            if ($cell.find('.qpe-inline-editor').length) {
                return;
            }
            
            // Načíst metadata o poli (typ, dostupné volby, atd.)
            $.ajax({
                url: qpeData.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'qpe_load_acf_data',
                    nonce: qpeData.nonce,
                    product_id: productId
                },
                success: function(response) {
                    if (response.success && response.data[fieldName]) {
                        var fieldData = response.data[fieldName];
                        self.renderInlineEditor($cell, productId, fieldName, fieldData);
                    }
                }
            });
        },
        
        /**
         * Vykreslení inline editoru
         */
        renderInlineEditor: function($cell, productId, fieldName, fieldData) {
            var self = this;
            var currentValue = $cell.text().trim();
            var editorHtml = '';
            
            // Používat aktuální hodnotu z fieldData místo display textu
            // Protože display text je formatovaný ("2 položky") ne aktuální data
            if (fieldData.value !== undefined) {
                currentValue = fieldData.value;
            }
            
            console.log('renderInlineEditor - fieldName:', fieldName, 'fieldData:', fieldData, 'type:', fieldData.type, 'value:', currentValue);
            
            // Zkontrolovat, zda je to checkbox typ a máme dostupné volby
            var isCheckbox = (fieldData.type === 'checkbox' || fieldData.type === 'checkboxes');
            var hasChoices = fieldData.choices && Object.keys(fieldData.choices).length > 0;
            
            if (isCheckbox && hasChoices) {
                // Checkbox editor
                console.log('Rendering as CHECKBOX');
                editorHtml = '<div class="qpe-inline-checkbox-group" data-field="' + fieldName + '">';
                
                var selectedValues = fieldData.value || [];
                if (typeof selectedValues === 'string') {
                    selectedValues = [selectedValues];
                }
                if (!Array.isArray(selectedValues)) {
                    selectedValues = [];
                }
                
                $.each(fieldData.choices, function(choiceValue, choiceLabel) {
                    var checked = selectedValues.indexOf(choiceValue) !== -1 ? 'checked' : '';
                    editorHtml += '<label class="qpe-inline-checkbox">' +
                                 '<input type="checkbox" value="' + self.escapeHtml(choiceValue) + '" ' + checked + '> ' +
                                 '<span>' + self.escapeHtml(choiceLabel) + '</span>' +
                                 '</label>';
                });
                
                editorHtml += '</div>';
                $cell.html(editorHtml);
                self.setupCheckboxEditor($cell, productId, fieldName, currentValue);
            } else {
                // Text editor
                console.log('Rendering as TEXT');
                
                // Textarea pro balení (podporuje čárkou oddělené hodnoty)
                if (fieldName === 'baleni') {
                    editorHtml = '<textarea class="qpe-inline-editor qpe-textarea-editor" data-product-id="' + productId + '" data-field="' + fieldName + '">' + self.escapeHtml(currentValue) + '</textarea>';
                } else {
                    editorHtml = '<input type="text" class="qpe-inline-editor" value="' + self.escapeHtml(currentValue) + '" data-product-id="' + productId + '" data-field="' + fieldName + '">';
                }
                
                $cell.html(editorHtml);
                self.setupTextEditor($cell, productId, fieldName, currentValue);
            }
        },
        
        /**
         * Setup pro text editor
         */
        setupTextEditor: function($cell, productId, fieldName, currentValue) {
            var self = this;
            
            var $input = $cell.find('.qpe-inline-editor');
            $input.focus();
            
            // Pro input type="text" - select all
            if ($input.is('input[type="text"]')) {
                $input.select();
            }
            
            // Uložení - Enter (jen pro text input, ne pro textarea)
            $input.on('keypress', function(e) {
                if (e.which === 13 && $input.is('input[type="text"]')) {
                    e.preventDefault();
                    self.saveInlineField($cell, productId, fieldName, $input.val());
                }
            });
            
            // Uložení - Ctrl+Enter (pro textarea)
            $input.on('keydown', function(e) {
                if (e.which === 13 && e.ctrlKey && $input.is('textarea')) {
                    e.preventDefault();
                    self.saveInlineField($cell, productId, fieldName, $input.val());
                }
            });
            
            // Zrušení - Escape
            $input.on('keydown', function(e) {
                if (e.which === 27) {
                    e.preventDefault();
                    self.cancelInlineEditor($cell, currentValue);
                }
            });
            
            // Uložení - blur
            $input.on('blur', function() {
                var newValue = $input.val();
                if (newValue !== currentValue) {
                    self.saveInlineField($cell, productId, fieldName, newValue);
                } else {
                    self.cancelInlineEditor($cell, currentValue);
                }
            });
        },
        
        /**
         * Setup pro checkbox editor
         */
        setupCheckboxEditor: function($cell, productId, fieldName, currentValue) {
            var self = this;
            var $container = $cell.find('.qpe-inline-checkbox-group');
            var $checkboxes = $container.find('input[type="checkbox"]');
            
            console.log('setupCheckboxEditor - container:', $container, 'checkboxes:', $checkboxes.length);
            
            // Zajistit, že se event váže správně
            $checkboxes.each(function(idx) {
                var $checkbox = $(this);
                console.log('Binding checkbox ' + idx + ':', $checkbox.val());
                
                $checkbox.on('change', function(e) {
                    // Zabránit event bubblingu
                    e.stopPropagation();
                    e.preventDefault();
                    
                    console.log('Checkbox changed:', $checkbox.val(), 'checked:', $checkbox.is(':checked'));
                    var selectedValues = [];
                    $container.find('input[type="checkbox"]:checked').each(function() {
                        selectedValues.push($(this).val());
                    });
                    console.log('Selected values:', selectedValues);
                    self.saveInlineField($cell, productId, fieldName, selectedValues);
                });
                
                // Zabránit bubblingu na click
                $checkbox.on('click', function(e) {
                    e.stopPropagation();
                });
                
                // Kliknutelný label - také zabránit bubblingu
                $checkbox.attr('id', 'qpe-checkbox-' + idx);
                $checkbox.closest('label').attr('for', 'qpe-checkbox-' + idx);
                $checkbox.closest('label').on('click', function(e) {
                    e.stopPropagation();
                });
            });
            
            // Zabránit bubblingu na celém containeru
            $container.on('click', function(e) {
                e.stopPropagation();
            });
            
            // Fokus na první checkbox
            $checkboxes.first().focus();
        },
        
        /**
         * Uložení inline pole
         */
        saveInlineField: function($cell, productId, fieldName, fieldValue) {
            var self = this;
            
            console.log('saveInlineField called:', { productId, fieldName, fieldValue });
            
            // Zobrazit loading
            $cell.html('<span class="spinner is-active" style="display: inline-block; margin-top: -2px;"></span>');
            
            // Připravit data pro AJAX
            var acfDataObj = {};
            acfDataObj[fieldName] = fieldValue;
            
            $.ajax({
                url: qpeData.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'qpe_save_acf',
                    nonce: qpeData.nonce,
                    product_id: productId,
                    field_name: fieldName,
                    acf_data: acfDataObj
                },
                success: function(response) {
                    console.log('saveInlineField success:', response);
                    if (response.success) {
                        // Zobrazit novou hodnotu
                        var displayValue = fieldValue;
                        if (Array.isArray(fieldValue)) {
                            // Zobrazit všechny vybrané hodnoty oddělené čárkami
                            displayValue = fieldValue.join(', ');
                        }
                        $cell.text(self.escapeHtml(displayValue));
                        $cell.addClass('qpe-field-saved');
                        
                        // Obnovit data v tabulce (reloadovat řádek)
                        // Najdi řádek s tímto produktem a reloaduj jeho data
                        var $row = $cell.closest('tr');
                        if ($row.length > 0) {
                            var postId = $row.data('post-id');
                            console.log('Refreshing row for post', postId);
                            
                            // Reloaduj data z serveru
                            $.ajax({
                                url: qpeData.ajaxUrl,
                                type: 'POST',
                                dataType: 'json',
                                data: {
                                    action: 'qpe_load_products',
                                    nonce: qpeData.nonce,
                                    post_id: postId
                                },
                                success: function(refreshResp) {
                                    if (refreshResp.success && refreshResp.data.products && refreshResp.data.products.length > 0) {
                                        var updatedProduct = refreshResp.data.products[0];
                                        console.log('Product data refreshed:', updatedProduct);
                                        // Data jsou teď aktualizovaná v tabulce když si příště klikneš
                                    }
                                }
                            });
                        }
                        
                        // Odstranit highlight po chvíli
                        setTimeout(function() {
                            $cell.removeClass('qpe-field-saved');
                        }, 1500);
                    } else {
                        alert('Chyba: ' + response.data);
                        // Zpět na původní zobrazení
                        location.reload();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('saveInlineField error:', error, xhr);
                    alert('AJAX chyba při ukládání!');
                    $cell.text(fieldValue);
                }
            });
        },
        
        /**
         * Zrušení inline editoru
         */
        cancelInlineEditor: function($cell, originalValue) {
            $cell.text(originalValue);
        },
        
        /**
         * Otevřít editor fotky (featured image)
         * Modal pro nahrátí/výměnu/smazání fotky
         */
        openThumbnailEditor: function(productId) {
            var self = this;
            
            // Kontrola, zda je wp.media dostupné
            if (typeof wp === 'undefined' || !wp.media) {
                alert('Media picker není dostupný. Prosím, editujte fotku v administraci.');
                return;
            }
            
            // Získat obrázek z řádku
            var $row = $('tr[data-product-id="' + productId + '"]');
            var $img = $row.find('.col-img img');
            var thumbUrl = $img.length ? $img.attr('src') : '';
            var thumbHtml = '';
            
            if (thumbUrl) {
                thumbHtml = '<div style="text-align: center; margin-bottom: 15px;">' +
                          '<img src="' + self.escapeHtml(thumbUrl) + '" alt="Náhled" style="max-width: 200px; max-height: 200px; border-radius: 4px; border: 1px solid #ddd;">' +
                          '</div>';
            } else {
                thumbHtml = '<div style="text-align: center; margin-bottom: 15px; padding: 40px 20px; background: #f5f5f5; border-radius: 4px; color: #999; font-size: 14px;">' +
                          'Bez obrázku' +
                          '</div>';
            }
            
            // Vytvořit modal s volbami
            var modalHtml = '<div class="qpe-modal-overlay">' +
                          '<div class="qpe-modal">' +
                          '<div class="qpe-modal-header">' +
                          '<h2>Fotka produktu</h2>' +
                          '<button class="qpe-modal-close-btn">✕</button>' +
                          '</div>' +
                          '<div class="qpe-modal-body">' +
                          thumbHtml +
                          '<div style="display: flex; gap: 10px; flex-direction: column;">' +
                          '<button class="button button-primary" id="qpe-upload-thumbnail" style="width: 100%;">Nahrát/Změnit fotku</button>' +
                          '<button class="button button-secondary" id="qpe-delete-thumbnail" style="width: 100%; color: #d32f2f;">Smazat fotku</button>' +
                          '</div>' +
                          '</div>' +
                          '</div>' +
                          '</div>';
            
            $('body').append(modalHtml);
            
            // Zavřít modal
            $('body').on('click', '.qpe-modal-close-btn, .qpe-modal-overlay', function(e) {
                if (e.target !== this && !$(e.target).hasClass('qpe-modal-close-btn')) return;
                $('.qpe-modal-overlay').remove();
                $('body').off('click', '.qpe-modal-close-btn, .qpe-modal-overlay');
                $('body').off('click', '#qpe-upload-thumbnail');
                $('body').off('click', '#qpe-delete-thumbnail');
            });
            
            // Nahrát fotku
            $('body').on('click', '#qpe-upload-thumbnail', function(e) {
                e.preventDefault();
                $('.qpe-modal-overlay').remove();
                
                // Vytvořit WordPress media frame
                var frame = wp.media({
                    title: 'Vybrat fotku produktu',
                    button: {
                        text: 'Vybrat fotku'
                    },
                    multiple: false,
                    library: {
                        type: 'image'
                    }
                });
                
                // Při výběru fotky
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    var thumbnailId = attachment.id;
                    
                    console.log('Thumbnail selected:', thumbnailId, attachment.url);
                    
                    // Uložit fotku přes AJAX
                    $.ajax({
                        url: qpeData.ajaxUrl,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'qpe_save_thumbnail',
                            nonce: qpeData.nonce,
                            product_id: productId,
                            thumbnail_id: thumbnailId
                        },
                        success: function(response) {
                            console.log('Thumbnail save response:', response);
                            
                            if (response.success) {
                                // Znovu načíst produkty aby se zobrazila nová fotka
                                self.loadProducts();
                            } else {
                                alert('Chyba: ' + (response.data || 'Neznámá chyba'));
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Thumbnail save error:', error);
                            alert('Chyba při ukládání fotky!');
                        }
                    });
                });
                
                frame.open();
            });
            
            // Smazat fotku
            $('body').on('click', '#qpe-delete-thumbnail', function(e) {
                e.preventDefault();
                
                if (confirm('Opravdu chceš smazat fotku produktu?')) {
                    $('.qpe-modal-overlay').remove();
                    
                    // Smazat fotku přes AJAX
                    $.ajax({
                        url: qpeData.ajaxUrl,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'qpe_delete_thumbnail',
                            nonce: qpeData.nonce,
                            product_id: productId
                        },
                        success: function(response) {
                            console.log('Thumbnail delete response:', response);
                            
                            if (response.success) {
                                // Znovu načíst produkty aby zmizela fotka
                                self.loadProducts();
                            } else {
                                alert('Chyba: ' + (response.data || 'Neznámá chyba'));
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Thumbnail delete error:', error);
                            alert('Chyba při mazání fotky!');
                        }
                    });
                }
                
                $('body').off('click', '.qpe-modal-close-btn, .qpe-modal-overlay');
                $('body').off('click', '#qpe-upload-thumbnail');
                $('body').off('click', '#qpe-delete-thumbnail');
            });
        },
        
        /**
         * Otevřít modal editor fotogalerie
         */
        openGalleryEditor: function($cell, productId) {
            var self = this;
            
            $.ajax({
                url: qpeData.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'qpe_load_acf_data',
                    nonce: qpeData.nonce,
                    product_id: productId
                },
                success: function(response) {
                    if (response.success) {
                        var galleryData = response.data.produkty_galerie || {};
                        var imageData = galleryData.value || [];  // Pole objektů {id, url}
                        
                        // Vytvořit modal s galerií
                        var galleryGridHtml = '';
                        
                        if (imageData && imageData.length > 0) {
                            imageData.forEach(function(imgObj, idx) {
                                var imgUrl = imgObj.url || imgObj;  // Kompatibilita se starým formátem
                                galleryGridHtml += '<div class="qpe-gallery-edit-item" data-image-idx="' + idx + '" data-image-id="' + (imgObj.id || 0) + '" draggable="true">' +
                                                '<img src="' + self.escapeHtml(imgUrl) + '" alt="Foto ' + (idx + 1) + '" loading="lazy">' +
                                                '<button class="qpe-gallery-edit-remove" data-idx="' + idx + '" title="Smazat fotku">✕</button>' +
                                                '</div>';
                            });
                        } else {
                            galleryGridHtml = '<p class="qpe-gallery-edit-empty">Zatím nejsou přiřazeny žádné fotky.</p>';
                        }
                        
                        var modalHtml = '<div class="qpe-modal-overlay">' +
                                      '<div class="qpe-modal" style="max-width: 700px;">' +
                                      '<div class="qpe-modal-header">' +
                                      '<h2>Fotogalerie produktu</h2>' +
                                      '<button class="qpe-modal-close-btn">✕</button>' +
                                      '</div>' +
                                      '<div class="qpe-modal-body">' +
                                      '<div class="qpe-gallery-edit-grid" id="qpe-modal-gallery-grid">' +
                                      galleryGridHtml +
                                      '</div>' +
                                      '</div>' +
                                      '<div style="padding: 15px; border-top: 1px solid #ddd; display: flex; gap: 10px;">' +
                                      '<button class="button button-secondary qpe-gallery-add-btn" style="flex: 1;">+ Přidat fotku</button>' +
                                      '<button class="button button-primary qpe-gallery-save-btn" style="flex: 1;">Uložit</button>' +
                                      '</div>' +
                                      '</div>' +
                                      '</div>';
                        
                        $('body').append(modalHtml);
                        
                        var $modal = $('.qpe-modal-overlay').last();
                        var $editor = $modal.find('.qpe-gallery-editor');
                        var currentImages = [];
                        
                        // Inicializovat pole IDs z existujících fotek
                        imageData.forEach(function(imgObj) {
                            if (imgObj.id) {
                                currentImages.push(imgObj.id);
                            }
                        });
                        
                        // Zavřít modal
                        $modal.on('click', '.qpe-modal-close-btn, .qpe-modal-overlay', function(e) {
                            if (e.target !== this && !$(e.target).hasClass('qpe-modal-close-btn')) return;
                            $modal.remove();
                            $modal.off('click');
                            $modal.off('dragstart');
                            $modal.off('dragend');
                            $modal.off('dragover');
                            $modal.off('dragleave');
                        });
                        
                        // Smazat fotku
                        $modal.on('click', '.qpe-gallery-edit-remove', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            var idx = $(this).closest('.qpe-gallery-edit-item').index();
                            currentImages.splice(idx, 1);
                            // Přerenderit grid s novými IDs
                            self.renderGalleryGridFromIds($modal.find('#qpe-modal-gallery-grid'), currentImages);
                        });
                        
                        // Přidat fotku
                        $modal.on('click', '.qpe-gallery-add-btn', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            self.openMediaPicker(currentImages, function(newImageIds) {
                                currentImages = newImageIds;
                                // Znovu načíst obrázky s URLs pro vykreslení
                                self.renderGalleryGridFromIds($modal.find('#qpe-modal-gallery-grid'), currentImages);
                            });
                        });
                        
                        // Uložit galerii
                        $modal.on('click', '.qpe-gallery-save-btn', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            self.saveGallery(productId, currentImages, $cell);
                            $modal.remove();
                        });
                        
                        // Drag-drop pro přesouvání fotek
                        var draggedItem = null;
                        
                        $modal.on('dragstart', '.qpe-gallery-edit-item', function(e) {
                            draggedItem = $(this);
                            $(this).addClass('qpe-gallery-dragging');
                            e.originalEvent.dataTransfer.effectAllowed = 'move';
                        });
                        
                        $modal.on('dragend', '.qpe-gallery-edit-item', function(e) {
                            $(this).removeClass('qpe-gallery-dragging');
                            draggedItem = null;
                        });
                        
                        $modal.on('dragover', '.qpe-gallery-edit-item', function(e) {
                            if (draggedItem && draggedItem[0] !== this) {
                                e.preventDefault();
                                $(this).addClass('qpe-gallery-drag-over');
                                
                                var allItems = $modal.find('.qpe-gallery-edit-item');
                                var draggedIndex = allItems.index(draggedItem);
                                var targetIndex = allItems.index(this);
                                
                                if (draggedIndex < targetIndex) {
                                    $(this).after(draggedItem);
                                } else {
                                    $(this).before(draggedItem);
                                }
                                
                                // Aktualizovat pořadí v currentImages
                                var newOrder = [];
                                $modal.find('.qpe-gallery-edit-item').each(function() {
                                    var imageId = $(this).data('image-id');
                                    if (imageId) newOrder.push(imageId);
                                });
                                currentImages = newOrder;
                            }
                        });
                        
                        $modal.on('dragleave', '.qpe-gallery-edit-item', function(e) {
                            $(this).removeClass('qpe-gallery-drag-over');
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Gallery load error:', error);
                    alert('Chyba při načítání fotogalerie!');
                }
            });
        },
        
        /**
         * Přerenderit grid fotek v editoru (ze starého formátu - jen URLs)
         */
        renderGalleryGrid: function($container, images) {
            var self = this;
            var gridHtml = '';
            
            if (images && images.length > 0) {
                images.forEach(function(imageUrl, idx) {
                    gridHtml += '<div class="qpe-gallery-edit-item" data-image-idx="' + idx + '" draggable="true">' +
                              '<img src="' + self.escapeHtml(imageUrl) + '" alt="Foto ' + (idx + 1) + '" loading="lazy">' +
                              '<button class="qpe-gallery-edit-remove" data-idx="' + idx + '" title="Smazat fotku">✕</button>' +
                              '</div>';
                });
            } else {
                gridHtml = '<p class="qpe-gallery-edit-empty">Žádné fotky nejsou přiřazeny.</p>';
            }
            
            $container.html(gridHtml);
        },
        
        /**
         * Přerenderit grid fotek z IDs - načíst URLs a zobrazit
         */
        renderGalleryGridFromIds: function($container, imageIds) {
            var self = this;
            var gridHtml = '';
            
            if (!imageIds || imageIds.length === 0) {
                $container.html('<p class="qpe-gallery-edit-empty">Žádné fotky nejsou přiřazeny.</p>');
                return;
            }
            
            // Nacistit URLs pro jednotlivé IDs
            $.ajax({
                url: qpeData.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'qpe_get_image_urls',
                    nonce: qpeData.nonce,
                    image_ids: imageIds
                },
                success: function(response) {
                    if (response.success && response.data) {
                        var urls = response.data;
                        
                        if (urls && urls.length > 0) {
                            urls.forEach(function(imgObj, idx) {
                                var imgUrl = imgObj.url || imgObj;
                                gridHtml += '<div class="qpe-gallery-edit-item" data-image-idx="' + idx + '" data-image-id="' + (imgObj.id || imageIds[idx] || 0) + '" draggable="true">' +
                                          '<img src="' + self.escapeHtml(imgUrl) + '" alt="Foto ' + (idx + 1) + '" loading="lazy">' +
                                          '<button class="qpe-gallery-edit-remove" data-idx="' + idx + '" title="Smazat fotku">✕</button>' +
                                          '</div>';
                            });
                        } else {
                            gridHtml = '<p class="qpe-gallery-edit-empty">Žádné fotky nejsou přiřazeny.</p>';
                        }
                        
                        $container.html(gridHtml);
                    }
                }
            });
        },
        
        /**
         * Otevřít WordPress Media Picker a vrátit vybrané fotky (IDs)
         */
        openMediaPicker: function(currentImageIds, callback) {
            // Pokud wp.media není dostupné, otevřít admin editor
            if (typeof wp === 'undefined' || !wp.media) {
                alert('Media picker není dostupný. Prosím, editujte galerii v administraci.');
                return;
            }
            
            var frame = wp.media({
                title: 'Vybrat fotky do galerie',
                button: {
                    text: 'Vybrat fotky'
                },
                multiple: true,
                library: {
                    type: 'image'
                }
            });
            
            frame.on('select', function() {
                var selection = frame.state().get('selection');
                var newImageIds = currentImageIds.slice();  // Kopie stávajících IDs
                
                selection.each(function(attachment) {
                    var imageId = attachment.id;
                    if (imageId && newImageIds.indexOf(imageId) === -1) {
                        newImageIds.push(imageId);  // Přidat ID (ne URL!)
                    }
                });
                
                callback(newImageIds);  // Vrátit pole IDs
            });
            
            frame.open();
        },
        
        /**
         * Uložit galerii přes AJAX
         */
        saveGallery: function(productId, images, $cell) {
            var self = this;
            
            console.log('Saving gallery for product', productId, 'with images:', images);
            
            $.ajax({
                url: qpeData.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'qpe_save_gallery',
                    nonce: qpeData.nonce,
                    product_id: productId,
                    images: images  // Pole IDs
                },
                success: function(response) {
                    console.log('Save response:', response);
                    
                    if (response.success) {
                        // Ukazat green highlight
                        $cell.addClass('qpe-cell-saved');
                        setTimeout(function() {
                            $cell.removeClass('qpe-cell-saved');
                        }, 1500);
                        
                        // Aktualizovat obsah buňky
                        self.renderCellContent($cell, productId);
                    } else {
                        console.error('Save failed:', response.data);
                        alert('Chyba: ' + (response.data || 'Neznámá chyba'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Gallery save error:', error);
                    console.error('XHR:', xhr);
                    alert('Chyba při ukládání fotogalerie!');
                }
            });
        },
        
        /**
         * Vykreslit obsah buňky fotogalerie (počet fotek)
         */
        renderCellContent: function($cell, productId) {
            var self = this;
            
            $.ajax({
                url: qpeData.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'qpe_load_acf_data',
                    nonce: qpeData.nonce,
                    product_id: productId
                },
                success: function(response) {
                    if (response.success) {
                        var galleryData = response.data.produkty_galerie || {};
                        var images = galleryData.value || [];
                        var count = images.length;
                        var text = count === 0 ? 'Žádné' : count === 1 ? '1 fotka' : count + ' fotek';
                        $cell.html(text);
                    }
                }
            });
        },
        
        /**
         * Escape HTML
         */
        escapeHtml: function(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };
    
    /**
     * Inicializace při načtení DOM
     */
    $(document).ready(function() {
        QPE.init();
    });
    
})(jQuery);
