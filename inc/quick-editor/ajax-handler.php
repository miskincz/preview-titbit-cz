<?php
/**
 * AJAX Handler pro Quick Product Editor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AJAX akce pro načtení produktů - OPTIMALIZOVÁNO
 */
add_action( 'wp_ajax_qpe_load_products', 'qpe_load_products_callback' );

function qpe_load_products_callback() {
    // Ověření nonce a oprávnění
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'qpe_nonce' ) ) {
        wp_send_json_error( __( 'Bezpečnostní kontrola selhala.', 'mytheme' ) );
    }
    
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( __( 'Nemáte oprávnění pro tuto akci.', 'mytheme' ) );
    }
    
    // Parametry s validací
    $paged = max( 1, intval( $_POST['paged'] ?? 1 ) );
    $per_page = min( 100, max( 5, intval( $_POST['per_page'] ?? 20 ) ) ); // Omezit na max 100
    $search = sanitize_text_field( $_POST['search'] ?? '' );
    $category = intval( $_POST['category'] ?? 0 );
    
    // Cache key
    $cache_key = 'qpe_products_' . md5( $paged . '_' . $per_page . '_' . $search . '_' . $category );
    
    // Zkusit cache
    $cached = wp_cache_get( $cache_key, 'qpe' );
    if ( false !== $cached ) {
        wp_send_json_success( $cached );
    }
    
    // Optimalizovaný query
    $args = [
        'post_type'      => 'produkt',
        'posts_per_page' => $per_page,
        'paged'          => $paged,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'suppress_filters' => false,
    ];
    
    // Vyhledávání
    if ( ! empty( $search ) ) {
        $args['s'] = $search;
    }
    
    // Kategorie
    if ( $category > 0 ) {
        $args['tax_query'] = [
            [
                'taxonomy' => 'produkt_kategorie',
                'field'    => 'term_id',
                'terms'    => $category,
            ]
        ];
    }
    
    $query = new WP_Query( $args );
    $products = [];
    
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            
            $product_id = get_the_ID();
            $thumbnail_id = get_post_thumbnail_id( $product_id );
            $thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'thumbnail' ) : '';
            
            // Kategorie
            $categories = get_the_terms( $product_id, 'produkt_kategorie' );
            $category_names = wp_list_pluck( $categories && ! is_wp_error( $categories ) ? $categories : [], 'name' );
            
            // Galerie
            $gallery_ids = get_post_meta( $product_id, '_product_gallery', true );
            $gallery_ids = $gallery_ids ? array_filter( array_map( 'intval', explode( ',', $gallery_ids ) ) ) : [];
            
            $products[] = [
                'id'                => $product_id,
                'title'             => get_the_title(),
                'thumbnail_url'     => $thumbnail_url,
                'categories'        => implode( ', ', $category_names ),
                'edit_link'         => get_edit_post_link( $product_id, 'raw' ),
                'view_link'         => get_permalink( $product_id ),
                'acf_baleni'        => function_exists( 'get_field' ) ? ( get_field( 'produkty_baleni', $product_id ) ?: '' ) : '',
                'acf_dostupnost'    => function_exists( 'get_field' ) ? ( get_field( 'produkty_dostupnost', $product_id ) ?: '' ) : '',
                'acf_galerie'       => $gallery_ids,
            ];
        }
        
        wp_reset_postdata();
    }
    
    $response = [
        'products'      => $products,
        'total_posts'   => $query->found_posts,
        'total_pages'   => $query->max_num_pages,
        'current_page'  => $paged,
    ];
    
    // Cache na 10 minut
    wp_cache_set( $cache_key, $response, 'qpe', 10 * MINUTE_IN_SECONDS );
    
    wp_send_json_success( $response );
}

/**
 * AJAX akce pro vyhledávání s autocomplete - OPTIMALIZOVÁNO
 */
add_action( 'wp_ajax_qpe_search_products', 'qpe_search_products_callback' );

function qpe_search_products_callback() {
    // Ověření
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'qpe_nonce' ) ) {
        wp_send_json_error( __( 'Bezpečnostní kontrola selhala.', 'mytheme' ) );
    }
    
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( __( 'Nemáte oprávnění pro tuto akci.', 'mytheme' ) );
    }
    
    $search = sanitize_text_field( $_POST['search'] ?? '' );
    
    // Minimální délka vyhledávání
    if ( strlen( $search ) < 2 ) {
        wp_send_json_success( [] );
    }
    
    // Maximální délka
    if ( strlen( $search ) > 100 ) {
        wp_send_json_error( __( 'Vyhledávání je příliš dlouhé.', 'mytheme' ) );
    }
    
    // Cache key
    $cache_key = 'qpe_search_' . md5( $search );
    $cached = wp_cache_get( $cache_key, 'qpe' );
    if ( false !== $cached ) {
        wp_send_json_success( $cached );
    }
    
    // Query
    $args = [
        'post_type'      => 'produkt',
        's'              => $search,
        'posts_per_page' => 10,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'fields'         => 'ids', // Jen IDs pro lepší výkon
    ];
    
    $query = new WP_Query( $args );
    $results = [];
    
    if ( ! empty( $query->posts ) ) {
        foreach ( $query->posts as $post_id ) {
            $results[] = [
                'id'    => $post_id,
                'title' => get_the_title( $post_id ),
            ];
        }
    }
    
    // Cache na 5 minut
    wp_cache_set( $cache_key, $results, 'qpe', 5 * MINUTE_IN_SECONDS );
    
    wp_send_json_success( $results );
}

/**
 * AJAX akce pro uložení produktu (název) - OPTIMALIZOVÁNO
 */
add_action( 'wp_ajax_qpe_save_product', 'qpe_save_product_callback' );

function qpe_save_product_callback() {
    // Ověření
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'qpe_nonce' ) ) {
        wp_send_json_error( __( 'Bezpečnostní kontrola selhala.', 'mytheme' ) );
    }
    
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( __( 'Nemáte oprávnění pro tuto akci.', 'mytheme' ) );
    }
    
    // Parametry s validací
    $product_id = intval( $_POST['product_id'] ?? 0 );
    $title = sanitize_text_field( $_POST['title'] ?? '' );
    $thumbnail_id = intval( $_POST['thumbnail_id'] ?? 0 );
    
    // Validace
    if ( ! $product_id || empty( $title ) ) {
        wp_send_json_error( __( 'Chybí povinné údaje.', 'mytheme' ) );
    }
    
    // Ověřit, že je to skutečný produkt
    $post = get_post( $product_id );
    if ( ! $post || $post->post_type !== 'produkt' ) {
        wp_send_json_error( __( 'Produkt nebyl nalezen.', 'mytheme' ) );
    }
    
    // Validace délky názvu
    if ( strlen( $title ) > 200 ) {
        wp_send_json_error( __( 'Název je příliš dlouhý (max 200 znaků).', 'mytheme' ) );
    }
    
    // Aktualizace názvu
    $updated = wp_update_post( [
        'ID'         => $product_id,
        'post_title' => $title,
    ] );
    
    if ( is_wp_error( $updated ) ) {
        wp_send_json_error( $updated->get_error_message() );
    }
    
    // Aktualizace featured image
    if ( $thumbnail_id > 0 ) {
        if ( wp_attachment_is_image( $thumbnail_id ) ) {
            set_post_thumbnail( $product_id, $thumbnail_id );
        } else {
            wp_send_json_error( __( 'Vybraný soubor není obrázek.', 'mytheme' ) );
        }
    }
    
    // Vyčistit cache
    wp_cache_delete_group( 'qpe' );
    
    wp_send_json_success( __( 'Produkt byl úspěšně uložen.', 'mytheme' ) );
}

/**
 * AJAX akce pro načtení ACF dat produktu
 */
add_action( 'wp_ajax_qpe_load_acf_data', 'qpe_load_acf_data_callback' );

function qpe_load_acf_data_callback() {
    // Ověření nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'qpe_nonce' ) ) {
        wp_send_json_error( __( 'Bezpečnostní kontrola selhala.', 'mytheme' ) );
    }
    
    // Ověření oprávnění
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( __( 'Nemáte oprávnění pro tuto akci.', 'mytheme' ) );
    }
    
    // Parametry
    $product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
    
    if ( ! $product_id ) {
        wp_send_json_error( __( 'Produkt nenalezen.', 'mytheme' ) );
    }
    
    // Pole k načtení (všechna kromě složení)
    $fields = [
        'produkty_galerie' => 'Foto galerie',
        'produkty_souvisejici' => 'Související produkty',
        'produkty_reels' => 'Reels',
        'produkty_baleni' => 'Balení',
        'produkty_loga-retezcu' => 'Loga řetězců',
        'produkty_eshopy' => 'E-shopy',
        'produkty_dostupnost' => 'Dostupnost',
    ];
    
    $data = [];
    
    foreach ( $fields as $field_name => $field_label ) {
        $value = null;
        $field_type = 'text'; // Výchozí typ
        $choices = [];
        
        // Speciální zpracování pro fotogalerii - načíst z custom meta pole!
        if ( $field_name === 'produkty_galerie' ) {
            $gallery_ids = get_post_meta( $product_id, '_product_gallery', true );
            $gallery_ids = $gallery_ids ? explode( ',', $gallery_ids ) : [];
            
            // Vrátit objekty s IDs a URLs (ID se potom použije při ukládání)
            $image_data = [];
            if ( ! empty( $gallery_ids ) ) {
                foreach ( $gallery_ids as $image_id ) {
                    if ( ! empty( $image_id ) ) {
                        $image_url = wp_get_attachment_image_url( $image_id, 'large' );
                        if ( $image_url ) {
                            $image_data[] = [
                                'id' => intval( $image_id ),
                                'url' => $image_url
                            ];
                        }
                    }
                }
            }
            $value = $image_data;
        } else if ( function_exists( 'get_field' ) ) {
            // Ostatní pole z ACF
            $value = get_field( $field_name, $product_id );
        }
        
        // Zjistit typ fieldu z ACF (jen pokud není galerie)
        if ( $field_name !== 'produkty_galerie' && function_exists( 'get_field_object' ) ) {
            $field_object = get_field_object( $field_name, $product_id );
            if ( $field_object ) {
                if ( isset( $field_object['type'] ) ) {
                    $field_type = $field_object['type'];
                }
                // Získat dostupné volby pro checkboxy/select
                if ( isset( $field_object['choices'] ) && is_array( $field_object['choices'] ) ) {
                    $choices = $field_object['choices'];
                }
            }
        }
        
        $data[ $field_name ] = [
            'label' => $field_label,
            'value' => $value,
            'type' => $field_type,
            'choices' => $choices,
        ];
    }
    
    wp_send_json_success( $data );
}

/**
 * AJAX akce pro uložení ACF dat
 */
add_action( 'wp_ajax_qpe_save_acf', 'qpe_save_acf_callback' );

function qpe_save_acf_callback() {
    // Ověření nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'qpe_nonce' ) ) {
        wp_send_json_error( __( 'Bezpečnostní kontrola selhala.', 'mytheme' ) );
    }
    
    // Ověření oprávnění
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( __( 'Nemáte oprávnění pro tuto akci.', 'mytheme' ) );
    }
    
    // Parametry
    $product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
    
    if ( ! $product_id ) {
        wp_send_json_error( __( 'Produkt nenalezen.', 'mytheme' ) );
    }
    
    // jQuery odesílá pole jako acf_data[field_name]
    // Zpracování ACF dat z $_POST
    $acf_data = [];
    
    // Nejdříve zkusit direktní přístup k acf_data jako objektu/poli
    if ( isset( $_POST['acf_data'] ) ) {
        // Případ 1: acf_data je pole (objekt v JavaScriptu)
        if ( is_array( $_POST['acf_data'] ) ) {
            foreach ( $_POST['acf_data'] as $key => $value ) {
                $field_name = sanitize_text_field( $key );
                if ( is_array( $value ) ) {
                    $acf_data[ $field_name ] = array_map( 'sanitize_text_field', $value );
                } else {
                    $acf_data[ $field_name ] = sanitize_textarea_field( $value );
                }
            }
        }
    }
    
    // Případ 2: Fallback pro starší zpracování - hledat klíče ve formátu: acf_data[field_name]
    if ( empty( $acf_data ) ) {
        foreach ( $_POST as $key => $value ) {
            // Hledáme klíče ve formátu: acf_data[field_name]
            if ( strpos( $key, 'acf_data' ) === 0 ) {
                // Možné formáty: acf_data[field_name] nebo acf_data[field_name][]
                if ( preg_match( '/^acf_data\[([^\]]+)\](?:\[\])?$/', $key, $matches ) ) {
                    $field_name = sanitize_text_field( $matches[1] );
                    
                    // Ošetřit hodnotu
                    if ( is_array( $value ) ) {
                        $acf_data[ $field_name ] = array_map( 'sanitize_text_field', $value );
                    } else {
                        $acf_data[ $field_name ] = sanitize_textarea_field( $value );
                    }
                }
            }
        }
    }
    
    // Uložení ACF dat
    if ( function_exists( 'update_field' ) && ! empty( $acf_data ) ) {
        foreach ( $acf_data as $field_name => $field_value ) {
            update_field( $field_name, $field_value, $product_id );
        }
    }
    
    wp_send_json_success( __( 'Pole byla úspěšně uložena.', 'mytheme' ) );
}

/**
 * AJAX akce pro uložení fotogalerie
 */
add_action( 'wp_ajax_qpe_save_gallery', 'qpe_save_gallery_callback' );

function qpe_save_gallery_callback() {
    // Ověření nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'qpe_nonce' ) ) {
        wp_send_json_error( __( 'Bezpečnostní kontrola selhala.', 'mytheme' ) );
    }
    
    // Ověření oprávnění
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( __( 'Nemáte oprávnění pro tuto akci.', 'mytheme' ) );
    }
    
    // Parametry
    $product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
    $images = isset( $_POST['images'] ) ? (array) $_POST['images'] : [];
    
    error_log( 'QPE Save Gallery - Product ID: ' . $product_id . ', Images: ' . print_r( $images, true ) );
    
    if ( ! $product_id ) {
        wp_send_json_error( __( 'Produkt nenalezen.', 'mytheme' ) );
    }
    
    // Obrázky jsou nyní IDs (která přicházejí z JavaScriptu jako čísla)
    $image_ids = [];
    foreach ( $images as $image_id ) {
        $image_id = intval( $image_id );
        // Ověřit, že je to validní attachment
        if ( $image_id > 0 && get_post_type( $image_id ) === 'attachment' ) {
            $image_ids[] = $image_id;
        }
    }
    
    error_log( 'QPE Validated IDs: ' . print_r( $image_ids, true ) );
    
    // Uložit jako comma-separated IDs do custom meta pole
    $gallery_string = implode( ',', $image_ids );
    error_log( 'QPE Gallery String: ' . $gallery_string );
    
    $updated = update_post_meta( $product_id, '_product_gallery', $gallery_string );
    error_log( 'QPE Meta Updated: ' . ( $updated ? 'yes' : 'no' ) );
    
    // Vyčistit cache
    wp_cache_delete_group( 'qpe' );
    
    wp_send_json_success( __( 'Fotogalerie byla úspěšně uložena.', 'mytheme' ) );
}

/**
 * AJAX akce pro vrácení URLs z attachment IDs
 */
add_action( 'wp_ajax_qpe_get_image_urls', 'qpe_get_image_urls_callback' );

function qpe_get_image_urls_callback() {
    // Ověření nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'qpe_nonce' ) ) {
        wp_send_json_error( __( 'Bezpečnostní kontrola selhala.', 'mytheme' ) );
    }
    
    // Ověření oprávnění
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( __( 'Nemáte oprávnění pro tuto akci.', 'mytheme' ) );
    }
    
    // Parametry
    $image_ids = isset( $_POST['image_ids'] ) ? (array) $_POST['image_ids'] : [];
    
    if ( empty( $image_ids ) ) {
        wp_send_json_success( [] );
    }
    
    // Vrátit URLs pro jednotlivé IDs
    $image_urls = [];
    foreach ( $image_ids as $image_id ) {
        $image_id = intval( $image_id );
        if ( $image_id > 0 && get_post_type( $image_id ) === 'attachment' ) {
            $image_url = wp_get_attachment_image_url( $image_id, 'large' );
            if ( $image_url ) {
                $image_urls[] = [
                    'id' => $image_id,
                    'url' => $image_url
                ];
            }
        }
    }
    
    wp_send_json_success( $image_urls );
}

/**
 * Aktualizace AJAX pro vrácení ACF dat při načítání produktů
 */
add_filter( 'qpe_product_data', function( $product, $product_id ) {
    if ( function_exists( 'get_field' ) ) {
        $product['acf_baleni'] = get_field( 'produkty_baleni', $product_id ) ?: '';
        $product['acf_dostupnost'] = get_field( 'produkty_dostupnost', $product_id ) ?: '';
    }
    return $product;
}, 10, 2 );
/**
 * AJAX handler pro uložení featured image (fotky produktu)
 */
add_action( 'wp_ajax_qpe_save_thumbnail', 'qpe_save_thumbnail_callback' );

function qpe_save_thumbnail_callback() {
    // Ověření nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'qpe_nonce' ) ) {
        wp_send_json_error( __( 'Bezpečnostní kontrola selhala.', 'mytheme' ) );
    }
    
    // Ověření oprávnění
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( __( 'Nemáte oprávnění pro tuto akci.', 'mytheme' ) );
    }
    
    // Získat parametry
    $product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
    $thumbnail_id = isset( $_POST['thumbnail_id'] ) ? intval( $_POST['thumbnail_id'] ) : 0;
    
    // Validace
    if ( ! $product_id || ! $thumbnail_id ) {
        wp_send_json_error( __( 'Chybějící parametry.', 'mytheme' ) );
    }
    
    // Ověřit, že produktu existuje a je typu 'produkt'
    $product = get_post( $product_id );
    if ( ! $product || $product->post_type !== 'produkt' ) {
        wp_send_json_error( __( 'Produkt neexistuje.', 'mytheme' ) );
    }
    
    // Ověřit, že attachment existuje
    $attachment = get_post( $thumbnail_id );
    if ( ! $attachment || $attachment->post_type !== 'attachment' ) {
        wp_send_json_error( __( 'Obrázek neexistuje.', 'mytheme' ) );
    }
    
    // Uložit featured image
    $updated = set_post_thumbnail( $product_id, $thumbnail_id );
    
    error_log( 'QPE Save Thumbnail - Product ID: ' . $product_id . ', Thumbnail ID: ' . $thumbnail_id . ', Updated: ' . ( $updated ? 'yes' : 'no' ) );
    
    if ( $updated || $thumbnail_id === get_post_thumbnail_id( $product_id ) ) {
        wp_send_json_success( __( 'Fotka byla úspěšně uložena.', 'mytheme' ) );
    } else {
        wp_send_json_error( __( 'Nepodařilo se uložit fotku.', 'mytheme' ) );
    }
}

/**
 * AJAX handler pro smazání featured image (fotky produktu)
 */
add_action( 'wp_ajax_qpe_delete_thumbnail', 'qpe_delete_thumbnail_callback' );

function qpe_delete_thumbnail_callback() {
    // Ověření nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'qpe_nonce' ) ) {
        wp_send_json_error( __( 'Bezpečnostní kontrola selhala.', 'mytheme' ) );
    }
    
    // Ověření oprávnění
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( __( 'Nemáte oprávnění pro tuto akci.', 'mytheme' ) );
    }
    
    // Získat parametry
    $product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
    
    // Validace
    if ( ! $product_id ) {
        wp_send_json_error( __( 'Chybějící parametry.', 'mytheme' ) );
    }
    
    // Ověřit, že produktu existuje a je typu 'produkt'
    $product = get_post( $product_id );
    if ( ! $product || $product->post_type !== 'produkt' ) {
        wp_send_json_error( __( 'Produkt neexistuje.', 'mytheme' ) );
    }
    
    // Smazat featured image
    $deleted = delete_post_thumbnail( $product_id );
    
    error_log( 'QPE Delete Thumbnail - Product ID: ' . $product_id . ', Deleted: ' . ( $deleted ? 'yes' : 'no' ) );
    
    if ( $deleted || ! has_post_thumbnail( $product_id ) ) {
        wp_send_json_success( __( 'Fotka byla úspěšně smazána.', 'mytheme' ) );
    } else {
        wp_send_json_error( __( 'Nepodařilo se smazat fotku.', 'mytheme' ) );
    }
}
