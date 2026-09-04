<?php
/**
 * Plugin Name: Smart File Renamer
 * Plugin URI: https://github.com/ivanusto/smart-file-renamer
 * Description: Automatically renames files with accents and special characters during upload for better SEO.
 * Version: 1.3.0
 * Author: Ivan Lin
 * Author URI: https://github.com/ivanusto
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: smart-file-renamer
 * Domain Path: /languages
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

    /**
     * Original upload names of the files renamed during this request, keyed by
     * the base name this plugin gave them.
     *
     * @var array<string, string>
     */
    private array $original_titles = [];

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
        add_filter( 'wp_insert_attachment_data', [ $this, 'keep_original_title' ], 10, 2 );
        add_action( 'init', [ $this, 'load_textdomain' ] );
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    /**
     * Load the bundled zh_TW translation from /languages.
     *
     * WordPress only loads translations automatically when they are hosted on
     * translate.wordpress.org, which covers plugins in the .org directory but
     * not this one, so the .mo files shipped with the plugin need this call.
     * It runs on 'init' rather than 'plugins_loaded' because WordPress 6.7
     * warns about translations loaded before then; every string here is used
     * from an admin callback, all of which run later.
     */
    public function load_textdomain(): void {
        // phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound
        load_plugin_textdomain( 'smart-file-renamer', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
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

        if ( empty( $file['name'] ) ) {
            return $file;
        }

        $original     = (string) $file['name'];
        $file['name'] = $this->rename_file( $original );

        if ( $file['name'] !== $original ) {
            $key   = pathinfo( $file['name'], PATHINFO_FILENAME );
            $title = sanitize_text_field( pathinfo( $original, PATHINFO_FILENAME ) );

            if ( '' !== $key && '' !== $title ) {
                $this->original_titles[ $key ] = $title;
            }
        }

        return $file;
    }

    /**
     * Keep the name the visitor uploaded as the media library title.
     *
     * Both upload paths already do this on their own: media_handle_upload()
     * reads $_FILES before the prefilter runs, and the REST controller keeps
     * $files['file']['name'], which the prefilter never sees because PHP passes
     * the array by value. This filter is the safety net for the fallbacks -
     * WP_REST_Attachments_Controller::create_item() ends with "Fall back to the
     * original approach" and titles the attachment after the *stored* file - so
     * a renamed upload can never end up titled "2026-09-04-153012", which is
     * unsearchable in the media library.
     *
     * The title is only restored when it still matches the name this plugin
     * generated for that same file during this request (plus any "-1" collision
     * suffix wp_unique_filename() added), so a title typed by a person or read
     * out of the image's IPTC metadata is left alone.
     *
     * @param array $data    Sanitized, slashed attachment data about to be inserted.
     * @param array $postarr Raw attachment data passed to wp_insert_post().
     * @return array The attachment data, with the original upload name as its title.
     */
    public function keep_original_title( array $data, array $postarr ): array {
        if ( empty( $this->original_titles ) || ! empty( $postarr['ID'] ) ) {
            return $data;
        }

        $title = isset( $data['post_title'] ) ? (string) $data['post_title'] : '';

        if ( '' === $title ) {
            return $data;
        }

        if ( ! isset( $this->original_titles[ $title ] ) ) {
            // Two files renamed to the same name in the same second: the second
            // one is stored as "<name>-1" by wp_unique_filename().
            $title = (string) preg_replace( '/-\d+$/', '', $title );

            if ( ! isset( $this->original_titles[ $title ] ) ) {
                return $data;
            }
        }

        // $data is slashed, as wp_insert_post() expects.
        $data['post_title'] = wp_slash( $this->original_titles[ $title ] );

        return $data;
    }

    public function rename_file( string $filename ): string {
        $extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );

        // Serial mode replaces the name outright, so none of the normalization
        // below applies. The upload time is taken in the site's own time zone:
        // a file uploaded at 00:30 in Taipei belongs to that day, not to the
        // UTC day before it.
        if ( get_option( 'sfr_serial_filename', false ) ) {
            $name = current_time( 'Y-m-d-His' );

            return '' !== $extension ? "{$name}.{$extension}" : $name;
        }

        $name = pathinfo( $filename, PATHINFO_FILENAME );

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

        // Site time zone, like serial mode above: gmdate() would stamp an
        // upload made at 03:00 in Taipei with the previous day's date.
        if ( get_option( 'sfr_add_date_prefix', false ) ) {
            $name = current_time( 'Y-m-d' ) . '-' . $name;
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

        register_setting(
            'smart-file-renamer',
            'sfr_serial_filename',
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
            'sfr_serial_filename',
            __( 'Time-Based File Names', 'smart-file-renamer' ),
            [ $this, 'serial_filename_callback' ],
            'smart-file-renamer',
            'sfr_main_section'
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

    public function serial_filename_callback(): void {
        $value = get_option( 'sfr_serial_filename', false );
        printf(
            '<input type="checkbox" name="sfr_serial_filename" %1$s value="1"> %2$s<p class="description">%3$s</p>',
            checked( $value, true, false ),
            esc_html__( 'Name every uploaded file after its upload time (YYYY-MM-DD-HHMMSS)', 'smart-file-renamer' ),
            esc_html__( 'The media library title keeps the name the file was uploaded under, so files stay searchable by their original name. This replaces the whole file name, so the date prefix below is not applied on top of it.', 'smart-file-renamer' )
        );
    }

    public function date_prefix_callback(): void {
        $value = get_option( 'sfr_add_date_prefix', false );
        printf(
            '<input type="checkbox" name="sfr_add_date_prefix" %1$s value="1"> %2$s<p class="description">%3$s</p>',
            checked( $value, true, false ),
            esc_html__( 'Add date prefix to file names (YYYY-MM-DD)', 'smart-file-renamer' ),
            esc_html__( 'The file keeps its sanitized name with the upload date in front of it: today-news.jpg becomes 2026-09-04-today-news.jpg, which keeps a large media library in chronological order. Ignored when time-based file names are on, since those already start with the date.', 'smart-file-renamer' )
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
    add_option( 'sfr_serial_filename', false );
} );
