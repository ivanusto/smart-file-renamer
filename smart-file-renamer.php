<?php
/**
 * Plugin Name: Smart File Renamer
 * Plugin URI: https://github.com/ivanlin/smart-file-renamer
 * Description: Automatically renames files with accents and special characters during upload for better SEO.
 * Version: 1.2.0
 * Author: Ivan Lin
 * Author URI: https://github.com/ivanlin
 * License: Apache-2.0
 * License URI: http://www.apache.org/licenses/LICENSE-2.0
 * Text Domain: smart-file-renamer
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class SmartFileRenamer {

    private static ?self $instance = null;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter( 'sanitize_file_name', [ $this, 'rename_file' ] );
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    public function rename_file( string $filename ): string {
        $extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
        $name      = pathinfo( $filename, PATHINFO_FILENAME );

        // Transliterate Latin diacritics to ASCII equivalents (WordPress built-in, 200+ characters)
        $name = remove_accents( $name );

        // Normalize separators: spaces and underscores become hyphens
        $name = str_replace( [ ' ', '_' ], '-', $name );

        // Strip remaining non-ASCII characters (CJK, symbols, etc.)
        $name = preg_replace( '/[^A-Za-z0-9\-]/', '', $name );

        $name = strtolower( $name );

        // Collapse consecutive hyphens and strip edge hyphens
        $name = preg_replace( '/-{2,}/', '-', $name );
        $name = trim( $name, '-' );

        // Fallback when the entire name is stripped
        if ( '' === $name ) {
            $name = 'file-' . time();
        }

        if ( get_option( 'sfr_add_date_prefix', false ) ) {
            $name = gmdate( 'Y-m-d' ) . '-' . $name;
        }

        return $extension !== '' ? "{$name}.{$extension}" : $name;
    }

    public function add_admin_menu(): void {
        add_options_page(
            __( 'Smart File Renamer Settings', 'smart-file-renamer' ),
            __( 'File Renamer', 'smart-file-renamer' ),
            'manage_options',
            'smart-file-renamer',
            [ $this, 'render_settings_page' ]
        );
    }

    public function register_settings(): void {
        register_setting(
            'smart-file-renamer',
            'sfr_add_date_prefix',
            [
                'type'              => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default'           => false,
            ]
        );

        add_settings_section(
            'sfr_main_section',
            __( 'General Settings', 'smart-file-renamer' ),
            [ $this, 'section_callback' ],
            'smart-file-renamer'
        );

        add_settings_field(
            'sfr_add_date_prefix',
            __( 'Add Date Prefix', 'smart-file-renamer' ),
            [ $this, 'date_prefix_callback' ],
            'smart-file-renamer',
            'sfr_main_section'
        );
    }

    public function section_callback(): void {
        echo '<p>' . esc_html__( 'Configure how your files should be renamed.', 'smart-file-renamer' ) . '</p>';
    }

    public function date_prefix_callback(): void {
        $value = get_option( 'sfr_add_date_prefix', false );
        printf(
            '<input type="checkbox" name="sfr_add_date_prefix" %s value="1"> %s',
            checked( $value, true, false ),
            esc_html__( 'Add date prefix to file names (YYYY-MM-DD)', 'smart-file-renamer' )
        );
    }

    public function render_settings_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'smart-file-renamer' );
                do_settings_sections( 'smart-file-renamer' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}

SmartFileRenamer::instance();

register_activation_hook( __FILE__, static function (): void {
    add_option( 'sfr_add_date_prefix', false );
} );

register_deactivation_hook( __FILE__, static function (): void {
    delete_option( 'sfr_add_date_prefix' );
} );
