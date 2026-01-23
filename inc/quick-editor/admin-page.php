<?php
/**
 * Quick Product Editor - Admin stránka pro upravování produktů
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registrace admin submenu pod Produkty
 */
add_action( 'admin_menu', function() {
    add_submenu_page(
        'edit.php?post_type=produkt',
        __( 'Rychlé editace', 'mytheme' ),
        __( 'Rychlé editace', 'mytheme' ),
        'manage_options',
        'quick-product-editor',
        'qpe_render_admin_page'
    );
} );

/**
 * Vykreslení admin stránky
 */
function qpe_render_admin_page() {
    ?>
    <div class="wrap qpe-container">
        <h1><?php esc_html_e( 'Rychlý editor produktů', 'mytheme' ); ?></h1>
        
        <div class="qpe-wrapper">
            <!-- Vyhledávání a filtrování -->
            <div class="qpe-toolbar">
                <div class="qpe-search-group">
                    <input 
                        type="text" 
                        id="qpe-search" 
                        placeholder="<?php esc_attr_e( 'Hledat produkty...', 'mytheme' ); ?>" 
                        class="qpe-search-input"
                    >
                    <button id="qpe-search-btn" class="button button-primary">
                        <?php esc_html_e( 'Hledat', 'mytheme' ); ?>
                    </button>
                </div>
                
                <div class="qpe-filter-group">
                    <label for="qpe-category"><?php esc_html_e( 'Kategorie:', 'mytheme' ); ?></label>
                    <select id="qpe-category" class="qpe-filter">
                        <option value=""><?php esc_html_e( '-- Všechny kategorie --', 'mytheme' ); ?></option>
                        <?php
                        // Načíst kategorie s hierarchií
                        $categories = get_terms( [
                            'taxonomy'   => 'produkt_kategorie',
                            'hide_empty' => false,
                            'orderby'    => 'name',
                            'hierarchical' => true,
                        ] );
                        
                        if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
                            // Vytvořit hierarchickou strukturu
                            $cat_tree = qpe_build_category_tree( $categories );
                            qpe_render_category_options( $cat_tree, 0 );
                        }
                        ?>
                    </select>
                </div>
                
                <button id="qpe-reset-btn" class="button">
                    <?php esc_html_e( 'Resetovat', 'mytheme' ); ?>
                </button>
            </div>
            
            <!-- Tabulka s produkty -->
            <div class="qpe-table-wrapper">
                <table class="qpe-table wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th class="col-img"><?php esc_html_e( 'Obrázek', 'mytheme' ); ?></th>
                            <th class="col-name"><?php esc_html_e( 'Název', 'mytheme' ); ?></th>
                            <th class="col-acf"><?php esc_html_e( 'Balení', 'mytheme' ); ?></th>
                            <th class="col-acf"><?php esc_html_e( 'Dostupnost', 'mytheme' ); ?></th>
                            <th class="col-acf"><?php esc_html_e( 'Fotogalerie', 'mytheme' ); ?></th>
                            <th class="col-actions"><?php esc_html_e( 'Akce', 'mytheme' ); ?></th>
                        </tr>
                    </thead>
                    <tbody id="qpe-products-list">
                        <tr class="qpe-loading">
                            <td colspan="6" style="text-align: center; padding: 20px;">
                                <span class="spinner is-active"></span>
                                <?php esc_html_e( 'Načítání...', 'mytheme' ); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginace -->
            <div class="qpe-pagination" id="qpe-pagination">
                <!-- Vygenerováno JavaScriptem -->
            </div>
        </div>
    </div>
    <?php
}

/**
 * Registrace stylů a skriptů
 */
add_action( 'admin_enqueue_scripts', function( $hook ) {
    // Načíst CSS jen na stránce quick-editoru
    if ( strpos( $hook, 'quick-product-editor' ) === false ) {
        return;
    }
    
    // Cesty k assetům
    $inc_dir = dirname( __FILE__ );
    $theme_uri = get_template_directory_uri();
    $css_file = $inc_dir . '/quick-editor/quick-editor-optimized.css';
    $js_file = $inc_dir . '/quick-editor/quick-editor.js';
    
    // Načíst optimalizovaný CSS
    if ( file_exists( $css_file ) ) {
        wp_enqueue_style(
            'qpe-styles',
            $theme_uri . '/inc/quick-editor/quick-editor/quick-editor-optimized.css',
            [],
            filemtime( $css_file )
        );
    }
    
    // Načíst JavaScript
    if ( file_exists( $js_file ) ) {
        wp_enqueue_script(
            'qpe-script',
            $theme_uri . '/inc/quick-editor/quick-editor/quick-editor.js',
            ['jquery', 'wp-util'],
            filemtime( $js_file ),
            true
        );
    }
    
    // Lokalizovat data pro JavaScript
    wp_localize_script( 'qpe-script', 'qpeData', [
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'qpe_nonce' ),
        'perPage' => 20,
    ] );
} );

/**
 * Vytvoří hierarchickou strukturu kategorií (parent => children)
 */
function qpe_build_category_tree( $categories, $parent = 0 ) {
    $tree = [];
    
    foreach ( $categories as $category ) {
        if ( intval( $category->parent ) === $parent ) {
            $tree[] = $category;
        }
    }
    
    return $tree;
}

/**
 * Rekurzivně vykreslí category options s odsazením
 */
function qpe_render_category_options( $categories, $depth = 0 ) {
    if ( empty( $categories ) ) {
        return;
    }
    
    $indent = str_repeat( '— ', $depth );
    
    foreach ( $categories as $category ) {
        printf(
            '<option value="%d">%s%s</option>',
            intval( $category->term_id ),
            esc_html( $indent ),
            esc_html( $category->name )
        );
        
        // Rekurzivně vykreslít dětské kategorie
        $children = qpe_build_category_tree( 
            get_terms( [ 'taxonomy' => 'produkt_kategorie', 'hide_empty' => false ] ),
            $category->term_id 
        );
        
        if ( ! empty( $children ) ) {
            qpe_render_category_options( $children, $depth + 1 );
        }
    }
}

/**
 * Vlastní CSS pro admin stránku (fallback)
 */
add_action( 'admin_head', function() {
    echo '<style>
        .qpe-skeleton {
            height: 20px;
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 4px;
        }
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>';
} );
