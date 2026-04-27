<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CPF_Rest_API {

    public function init() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        // Endpoint: obtener productos con filtros
        register_rest_route( 'cpf/v1', '/products', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_products' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'category'   => [ 'type' => 'string',  'default' => '',    'sanitize_callback' => 'sanitize_text_field' ],
                'attributes' => [ 'type' => 'string',  'default' => '',    'sanitize_callback' => 'sanitize_text_field' ],
                'min_price'  => [ 'type' => 'number',  'default' => 0 ],
                'max_price'  => [ 'type' => 'number',  'default' => 0 ],
                'orderby'    => [ 'type' => 'string',  'default' => 'date','sanitize_callback' => 'sanitize_key' ],
                'order'      => [ 'type' => 'string',  'default' => 'DESC','sanitize_callback' => 'sanitize_key' ],
                'page'       => [ 'type' => 'integer', 'default' => 1,     'minimum' => 1 ],
                'per_page'   => [ 'type' => 'integer', 'default' => 12,    'minimum' => 1, 'maximum' => 100 ],
                'search'     => [ 'type' => 'string',  'default' => '',    'sanitize_callback' => 'sanitize_text_field' ],
            ],
        ] );

        // Endpoint: obtener opciones de filtros (categorías, atributos, rango de precio)
        register_rest_route( 'cpf/v1', '/filters', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_filters' ],
            'permission_callback' => '__return_true',
        ] );
    }

    // -------------------------------------------------------------------------
    // GET /cpf/v1/products
    // -------------------------------------------------------------------------
    public function get_products( $request ) {
        $args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => intval( $request['per_page'] ),
            'paged'          => intval( $request['page'] ),
        ];

        $tax_query = [];

        // Filtro por categoría (acepta múltiples slugs separados por coma)
        if ( ! empty( $request['category'] ) ) {
            $tax_query[] = [
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => array_map( 'sanitize_text_field', explode( ',', $request['category'] ) ),
                'operator' => 'IN',
            ];
        }

        // Filtro por atributos (formato: pa_color:rojo,azul|pa_talla:s,m)
        if ( ! empty( $request['attributes'] ) ) {
            $groups = explode( '|', $request['attributes'] );
            foreach ( $groups as $group ) {
                $parts = explode( ':', $group, 2 );
                if ( count( $parts ) === 2 && ! empty( $parts[1] ) ) {
                    $tax_query[] = [
                        'taxonomy' => sanitize_key( $parts[0] ),
                        'field'    => 'slug',
                        'terms'    => array_map( 'sanitize_text_field', explode( ',', $parts[1] ) ),
                        'operator' => 'IN',
                    ];
                }
            }
        }

        if ( ! empty( $tax_query ) ) {
            $tax_query['relation'] = 'AND';
            $args['tax_query']     = $tax_query;
        }

        // Filtro por precio
        $min = floatval( $request['min_price'] );
        $max = floatval( $request['max_price'] );
        if ( $min > 0 || $max > 0 ) {
            $meta_query = [ 'key' => '_price', 'type' => 'NUMERIC' ];
            if ( $min > 0 && $max > 0 ) {
                $meta_query['value']   = [ $min, $max ];
                $meta_query['compare'] = 'BETWEEN';
            } elseif ( $min > 0 ) {
                $meta_query['value']   = $min;
                $meta_query['compare'] = '>=';
            } else {
                $meta_query['value']   = $max;
                $meta_query['compare'] = '<=';
            }
            $args['meta_query'] = [ $meta_query ];
        }

        // Búsqueda por texto
        if ( ! empty( $request['search'] ) ) {
            $args['s'] = sanitize_text_field( $request['search'] );
        }

        // Ordenamiento
        $this->apply_orderby( $args, $request['orderby'] );

        $query    = new WP_Query( $args );
        $products = [];

        foreach ( $query->posts as $post ) {
            $product = wc_get_product( $post->ID );
            if ( ! $product ) continue;
            $products[] = $this->format_product( $product );
        }

        return rest_ensure_response( [
            'products'     => $products,
            'total'        => (int) $query->found_posts,
            'total_pages'  => (int) $query->max_num_pages,
            'current_page' => (int) $request['page'],
        ] );
    }

    // -------------------------------------------------------------------------
    // GET /cpf/v1/filters
    // -------------------------------------------------------------------------
    public function get_filters( $request ) {
        // Categorías (excluir Sin categorizar / Uncategorized)
        $exclude_ids = [];
        foreach ( [ 'uncategorized', 'sin-categorizar', 'uncategorised' ] as $slug ) {
            $term = get_term_by( 'slug', $slug, 'product_cat' );
            if ( $term ) {
                $exclude_ids[] = $term->term_id;
            }
        }

        $raw_cats = get_terms( [
            'taxonomy'   => 'product_cat',
            'hide_empty' => true,
            'parent'     => 0,
            'orderby'    => 'name',
            'exclude'    => $exclude_ids,
        ] );

        $categories = [];
        if ( ! is_wp_error( $raw_cats ) ) {
            foreach ( $raw_cats as $cat ) {
                $categories[] = [
                    'id'    => $cat->term_id,
                    'name'  => $cat->name,
                    'slug'  => $cat->slug,
                    'count' => $cat->count,
                ];
            }
        }

        // Atributos de producto
        $attributes = [];
        $attr_taxonomies = wc_get_attribute_taxonomies();
        foreach ( $attr_taxonomies as $tax ) {
            $terms = get_terms( [
                'taxonomy'   => 'pa_' . $tax->attribute_name,
                'hide_empty' => true,
                'orderby'    => 'name',
            ] );
            if ( is_wp_error( $terms ) || empty( $terms ) ) continue;

            $term_list = [];
            foreach ( $terms as $term ) {
                $term_list[] = [
                    'id'    => $term->term_id,
                    'name'  => $term->name,
                    'slug'  => $term->slug,
                    'count' => $term->count,
                ];
            }
            $attributes[] = [
                'id'       => $tax->attribute_id,
                'name'     => $tax->attribute_label,
                'taxonomy' => 'pa_' . $tax->attribute_name,
                'type'     => $tax->attribute_type,
                'terms'    => $term_list,
            ];
        }

        // Marcas — detectar taxonomía según el plugin instalado
        $brand_taxonomies = [
            'product_brand'      => 'Marcas',
            'pwb-brand'          => 'Marcas',
            'yith_product_brand' => 'Marcas',
            'pa_marca'           => 'Marcas',
            'pa_brand'           => 'Marcas',
        ];
        foreach ( $brand_taxonomies as $brand_tax => $brand_label ) {
            if ( ! taxonomy_exists( $brand_tax ) ) continue;
            $brand_terms = get_terms( [
                'taxonomy'   => $brand_tax,
                'hide_empty' => true,
                'orderby'    => 'name',
            ] );
            if ( is_wp_error( $brand_terms ) || empty( $brand_terms ) ) continue;
            $term_list = [];
            foreach ( $brand_terms as $term ) {
                $term_list[] = [
                    'id'    => $term->term_id,
                    'name'  => $term->name,
                    'slug'  => $term->slug,
                    'count' => $term->count,
                ];
            }
            $attributes[] = [
                'id'       => 0,
                'name'     => $brand_label,
                'taxonomy' => $brand_tax,
                'type'     => 'select',
                'terms'    => $term_list,
            ];
            break;
        }

        // Rango de precio
        global $wpdb;
        $price_range = $wpdb->get_row( $wpdb->prepare(
            "SELECT MIN(CAST(pm.meta_value AS DECIMAL(10,2))) AS min_price,
                    MAX(CAST(pm.meta_value AS DECIMAL(10,2))) AS max_price
             FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
             WHERE pm.meta_key = %s
               AND p.post_status = 'publish'
               AND p.post_type   = 'product'
               AND pm.meta_value != ''",
            '_price'
        ) );

        return rest_ensure_response( [
            'categories'  => $categories,
            'attributes'  => $attributes,
            'price_range' => [
                'min' => floatval( $price_range->min_price ?? 0 ),
                'max' => ceil( floatval( $price_range->max_price ?? 1000 ) ),
            ],
        ] );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------
    private function format_product( $product ) {
        $image_id  = $product->get_image_id();
        $image_url = $image_id
            ? wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' )
            : wc_placeholder_img_src( 'woocommerce_thumbnail' );

        $categories = wp_get_post_terms( $product->get_id(), 'product_cat', [ 'fields' => 'names' ] );

        $created_timestamp = $product->get_date_created()
            ? $product->get_date_created()->getTimestamp()
            : 0;

        return [
            'id'            => $product->get_id(),
            'name'          => $product->get_name(),
            'permalink'     => get_permalink( $product->get_id() ),
            'image'         => $image_url,
            'price'         => (float) $product->get_price(),
            'regular_price' => (float) $product->get_regular_price(),
            'sale_price'    => (float) $product->get_sale_price(),
            'on_sale'       => $product->is_on_sale(),
            'in_stock'      => $product->is_in_stock(),
            'product_type'  => $product->get_type(),
            'categories'    => ! is_wp_error( $categories ) ? $categories : [],
            'rating'        => (float) $product->get_average_rating(),
            'review_count'  => (int) $product->get_review_count(),
        ];
    }

    private function apply_orderby( &$args, $orderby ) {
        switch ( $orderby ) {
            case 'price_asc':
                $args['orderby']  = 'meta_value_num';
                $args['meta_key'] = '_price';
                $args['order']    = 'ASC';
                break;
            case 'price_desc':
                $args['orderby']  = 'meta_value_num';
                $args['meta_key'] = '_price';
                $args['order']    = 'DESC';
                break;
            case 'popularity_desc':
                $args['orderby']  = 'meta_value_num';
                $args['meta_key'] = 'total_sales';
                $args['order']    = 'DESC';
                break;
            case 'rating_desc':
                $args['orderby']  = 'meta_value_num';
                $args['meta_key'] = '_wc_average_rating';
                $args['order']    = 'DESC';
                break;
            case 'title_asc':
                $args['orderby'] = 'title';
                $args['order']   = 'ASC';
                break;
            case 'date_asc':
                $args['orderby'] = 'date';
                $args['order']   = 'ASC';
                break;
            default: // date_desc
                $args['orderby'] = 'date';
                $args['order']   = 'DESC';
                break;
        }
    }   
}
