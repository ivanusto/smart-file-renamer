<?php
/**
 * Plugin Name: Smart File Renamer
 * Plugin URI: https://github.com/ivanusto/smart-file-renamer
 * Description: Automatically renames files with accents and special characters during upload for better SEO.
 * Version: 1.2.3
 * Author: Ivan Lin
 * Author URI: https://github.com/ivanusto
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: smart-file-renamer
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Keep the historical class name: Omni Webmaster & SEO Suite detects it via
// class_exists( 'SmartFileRenamer' ) to avoid renaming files twice.
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
final class SmartFileRenamer {

    private static ?self $instance = null;

    /**
     * Route of the REST request currently being served, if any.
     */
    private string $rest_route = '';

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Only rename files that are actually being uploaded or sideloaded.
        //
        // The obvious hook here is 'sanitize_file_name', but that filter is global:
        // WordPress, themes and plugins run it over every name they treat as a file
        // name, including generated CSS caches and temp files. Renaming those breaks
        // whichever code wrote the file under its original name (for example
        // "style_dynamic.css" would come back as "style-dynamic.css").
        add_filter( 'wp_handle_upload_prefilter', [ $this, 'rename_upload' ] );
        add_filter( 'wp_handle_sideload_prefilter', [ $this, 'rename_upload' ] );
        add_filter( 'rest_pre_dispatch', [ $this, 'remember_rest_route' ], 10, 3 );
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    /**
     * Record the REST route being served, so rename_upload() can recognise a
     * sideload without re-parsing the request.
     *
     * @param mixed           $result  Response to replace the requested version with.
     * @param WP_REST_Server  $server  Server instance.
     * @param WP_REST_Request $request Request used to generate the response.
     * @return mixed The unchanged $result.
     */
    public function remember_rest_route( $result, $server, $request ) {
        if ( $request instanceof WP_REST_Request ) {
            $this->rest_route = (string) $request->get_route();
        }
        return $result;
    }

    /**
     * Whether the current request is WordPress 7.1's sub-size sideload.
     *
     * Client-side media processing generates every sub-size in the browser and
     * posts them back one at a time to /wp/v2/media/{id}/sideload, along with
     * companion files such as the HEIC original or a converted animated GIF.
     * Those go through wp_handle_upload() and wp_handle_sideload() like any
     * other upload, so this plugin's prefilter runs on them too - except the
     * name the browser sends is already derived from the stored base name.
     *
     * Renaming it again is wrong twice over: the optional date prefix gets
     * stacked ("2026-08-20-2026-08-20-photo-150x150.jpg"), and because the
     * result no longer begins with the attachment's base name, core's
     * filter_wp_unique_filename() stops stripping the collision suffix and
     * leaves a "-1" on every file.
     */
    private function is_sideload_request(): bool {
        if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST || '' === $this->rest_route ) {
            return false;
        }

        return 1 === preg_match( '#^/wp/v2/media/\d+/sideload$#', $this->rest_route );
    }

    /**
     * Normalize the file name of an upload in progress
     *
     * @param array $file Upload array as passed by WordPress ('name', 'type', 'tmp_name', ...).
     */
    public function rename_upload( array $file ): array {
        if ( $this->is_sideload_request() ) {
            return $file;
        }

        if ( ! empty( $file['name'] ) ) {
            $file['name'] = $this->rename_file( $file['name'] );
        }
        return $file;
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
