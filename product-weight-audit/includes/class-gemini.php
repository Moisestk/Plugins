<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PWA_Gemini {

    const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';

    public static function analyze( int $product_id ): array {
        $api_key = get_option( 'pwa_gemini_api_key', '' );
        if ( ! $api_key ) {
            return [ 'error' => 'API key no configurada.' ];
        }

        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return [ 'error' => 'Producto no encontrado.' ];
        }

        $unit_weight = get_option( 'woocommerce_weight_unit', 'kg' );
        $unit_dim    = get_option( 'woocommerce_dimension_unit', 'cm' );

        $body = [
            'contents'         => [ [ 'parts' => self::build_parts( $product, $unit_weight, $unit_dim ) ] ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'temperature'      => 0.2,
            ],
        ];

        $response = wp_remote_post(
            self::API_URL . '?key=' . $api_key,
            [
                'timeout' => 30,
                'headers' => [ 'Content-Type' => 'application/json' ],
                'body'    => wp_json_encode( $body ),
            ]
        );

        if ( is_wp_error( $response ) ) {
            return [ 'error' => $response->get_error_message() ];
        }

        $code = wp_remote_retrieve_response_code( $response );
        $raw  = wp_remote_retrieve_body( $response );
        $data = json_decode( $raw, true );

        if ( $code !== 200 ) {
            $msg = $data['error']['message'] ?? "Error HTTP {$code}";
            return [ 'error' => $msg, 'retry' => $code === 429 ];
        }

        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $text = trim( preg_replace( '/^```(?:json)?\s*|\s*```$/m', '', $text ) );
        $json = json_decode( $text, true );

        if ( ! is_array( $json ) || ! isset( $json['weight'], $json['length'], $json['width'], $json['height'] ) ) {
            return [ 'error' => 'Respuesta inesperada de Gemini.' ];
        }

        return [
            'weight'     => (string) round( (float) $json['weight'], 3 ),
            'length'     => (string) round( (float) $json['length'], 1 ),
            'width'      => (string) round( (float) $json['width'], 1 ),
            'height'     => (string) round( (float) $json['height'], 1 ),
            'confidence' => sanitize_key( $json['confidence'] ?? 'low' ),
            'reasoning'  => sanitize_text_field( $json['reasoning'] ?? '' ),
        ];
    }

    private static function build_parts( WC_Product $product, string $unit_weight, string $unit_dim ): array {
        $name        = $product->get_name();
        $description = wp_strip_all_tags( $product->get_description() ?: $product->get_short_description() );
        $description = mb_substr( $description, 0, 600 );
        $terms       = get_the_terms( $product->get_id(), 'product_cat' ) ?: [];
        $category    = implode( ', ', wp_list_pluck( $terms, 'name' ) );

        $prompt  = "Eres un experto en logística y embalaje de productos de e-commerce.\n";
        $prompt .= "Analiza este producto y estima el peso y dimensiones de su empaque/caja para envío.\n\n";
        $prompt .= "Producto: {$name}\n";
        if ( $category )    $prompt .= "Categoría: {$category}\n";
        if ( $description ) $prompt .= "Descripción: {$description}\n";
        $prompt .= "\nResponde ÚNICAMENTE con este JSON exacto (sin markdown, sin texto adicional):\n";
        $prompt .= "{\n";
        $prompt .= "  \"weight\": <peso en {$unit_weight}>,\n";
        $prompt .= "  \"length\": <largo en {$unit_dim}>,\n";
        $prompt .= "  \"width\": <ancho en {$unit_dim}>,\n";
        $prompt .= "  \"height\": <alto en {$unit_dim}>,\n";
        $prompt .= "  \"confidence\": \"high\" | \"medium\" | \"low\",\n";
        $prompt .= "  \"reasoning\": \"explicación breve en español de por qué esos valores\"\n";
        $prompt .= "}";

        $parts = [ [ 'text' => $prompt ] ];

        $image_id = $product->get_image_id();
        if ( $image_id ) {
            $sized = wp_get_attachment_image_src( $image_id, 'medium' );
            $file  = $sized ? self::url_to_path( $sized[0] ) : get_attached_file( $image_id );

            if ( $file && file_exists( $file ) && filesize( $file ) < 4 * 1024 * 1024 ) {
                $mime = wp_check_filetype( $file )['type'] ?? '';
                if ( in_array( $mime, [ 'image/jpeg', 'image/png', 'image/webp', 'image/gif' ], true ) ) {
                    $image_data = file_get_contents( $file );
                    if ( $image_data !== false ) {
                        $parts[] = [
                            'inline_data' => [
                                'mime_type' => $mime,
                                'data'      => base64_encode( $image_data ),
                            ],
                        ];
                    }
                }
            }
        }

        return $parts;
    }

    private static function url_to_path( string $url ): string {
        $upload_dir = wp_upload_dir();
        $base_url   = $upload_dir['baseurl'];
        $base_dir   = $upload_dir['basedir'];

        if ( strpos( $url, $base_url ) === 0 ) {
            return str_replace( $base_url, $base_dir, $url );
        }
        return '';
    }
}
