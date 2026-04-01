<?php
/**
 * Plugin Name: Reverse Title
 * Description: Reverses the document title on singular posts, pages, and custom post types.
 * Version: 1.0
 * Author: Christefano Reyes
 * Plugin URI: https://github.com/christefano/wp-reverse-title/
 * Author URI: https://github.com/christefano/
 * Text Domain: wp-reverse-title
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Tested up to: 6.7
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WP_REVERSE_TITLE_VERSION', '1.0' );
define( 'WP_REVERSE_TITLE_OPT_OUT_KEY', '_wp_reverse_title_opt_out' );
define( 'WP_REVERSE_TITLE_BASENAME', plugin_basename( __FILE__ ) );

register_uninstall_hook( __FILE__, 'wp_reverse_title_uninstall' );

// ---------------------------------------------------------------------------
// Title reversal (front-end filters - intentionally outside is_admin())
// ---------------------------------------------------------------------------

add_filter( 'document_title_separator', 'wp_reverse_title_separator' );
add_filter( 'document_title_parts',     'wp_reverse_title_parts' );

/**
 * Returns whether title reversal should apply on the current request.
 * Cached in a static variable since both document_title_separator and
 * document_title_parts need this result within the same page load.
 *
 * @return bool
 */
function wp_reverse_title_should_reverse() {
    static $result = null;

    if ( null === $result ) {
        $singular = is_singular();
        $opted_out = $singular && (bool) get_post_meta( get_the_ID(), WP_REVERSE_TITLE_OPT_OUT_KEY, true );

        $result = (bool) apply_filters(
            'wp_reverse_title_enabled',
            $singular && ! is_front_page() && ! $opted_out
        );
    }

    return $result;
}

/**
 * Replaces the default title separator when a reversal is active on the
 * current request. Registered before document_title_parts because WordPress
 * calls document_title_separator first inside wp_get_document_title().
 *
 * @param string $sep The current separator.
 * @return string The custom separator, or the original if none is set or no reversal is happening.
 */
function wp_reverse_title_separator( $sep ) {
    if ( ! wp_reverse_title_should_reverse() ) {
        return $sep;
    }

    $custom_sep = trim( get_option( 'wp_reverse_title_separator', '' ) );

    /**
     * Filters the title separator used in reversed titles only.
     *
     * @param string $custom_sep The separator from Settings, or empty string for WP default.
     */
    $custom_sep = apply_filters( 'wp_reverse_title_separator', $custom_sep );

    return ( '' !== $custom_sep ) ? $custom_sep : $sep;
}

/**
 * Reverses the document title parts (e.g. "Post Title – Site Name" becomes
 * "Site Name – Post Title") on singular posts and pages, excluding the front
 * page and any post that has the per-post opt-out checked.
 *
 * The condition is passed through the 'wp_reverse_title_enabled' filter,
 * allowing other plugins or themes to override the logic without modifying
 * this file.
 *
 * The separator is handled in wp_reverse_title_separator() above, which runs
 * first because WordPress calls document_title_separator before
 * document_title_parts inside wp_get_document_title().
 *
 * array_reverse() is called with preserve_keys = true intentionally -
 * WordPress assembles the <title> from array values in order, so key
 * preservation is correct here.
 *
 * @param array $title Associative array of title parts from WordPress.
 * @return array Modified (or unchanged) title parts array.
 */
function wp_reverse_title_parts( $title ) {
    if ( ! wp_reverse_title_should_reverse() ) {
        return $title;
    }

    return array_reverse( $title, true ); // true = preserve keys
}

// ---------------------------------------------------------------------------
// Plugin action links (Settings, README, View more details, Donate)
// ---------------------------------------------------------------------------

if ( is_admin() ) {
    add_filter( 'plugin_action_links_' . WP_REVERSE_TITLE_BASENAME, 'wp_reverse_title_action_links' );
    add_filter( 'plugin_row_meta', 'wp_reverse_title_row_meta', 10, 2 );
    add_action( 'admin_menu',     'wp_reverse_title_add_settings_page' );
    add_action( 'admin_init',     'wp_reverse_title_register_settings' );
    add_action( 'add_meta_boxes', 'wp_reverse_title_add_meta_box' );
    add_action( 'save_post',      'wp_reverse_title_save_meta_box' );
}

/**
 * Adds Settings and README links to the plugin entry on the Plugins admin list.
 *
 * @param array $links Existing action links.
 * @return array Modified action links with Settings and README prepended.
 */
function wp_reverse_title_action_links( $links ) {
    $extra = array(
        sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'options-general.php?page=wp-reverse-title' ) ), __( 'Settings', 'wp-reverse-title' ) ),
        sprintf( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_url( 'https://github.com/christefano/wp-reverse-title/blob/main/README.md' ), __( 'README', 'wp-reverse-title' ) ),
    );

    return array_merge( $extra, $links );
}

/**
 * Adds View more details and Donate links to the plugin row meta on the Plugins admin list.
 *
 * @param array  $links Existing row meta links.
 * @param string $file  Plugin file path being rendered.
 * @return array Modified row meta links.
 */
function wp_reverse_title_row_meta( $links, $file ) {
    if ( WP_REVERSE_TITLE_BASENAME !== $file ) {
        return $links;
    }

    $links[] = sprintf(
        '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
        esc_url( 'https://github.com/christefano/wp-reverse-title/' ),
        __( 'View more details', 'wp-reverse-title' )
    );

    $links[] = sprintf(
        '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
        esc_url( 'https://macchess.org/donate' ),
        __( 'Donate', 'wp-reverse-title' )
    );

    return $links;
}

// ---------------------------------------------------------------------------
// Settings page (Settings -> Reverse Title)
// ---------------------------------------------------------------------------

/**
 * Registers the Reverse Title settings page under the Settings menu.
 */
function wp_reverse_title_add_settings_page() {
    add_options_page(
        __( 'Reverse Title', 'wp-reverse-title' ),
        __( 'Reverse Title', 'wp-reverse-title' ),
        'manage_options',
        'wp-reverse-title',
        'wp_reverse_title_render_settings_page'
    );
}

/**
 * Registers the plugin settings with the WordPress Settings API.
 */
function wp_reverse_title_register_settings() {
    register_setting(
        'wp_reverse_title_settings',
        'wp_reverse_title_separator',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
            'autoload'          => 'yes',
        )
    );

    register_setting(
        'wp_reverse_title_settings',
        'wp_reverse_title_show_meta_box',
        array(
            'type'              => 'boolean',
            'sanitize_callback' => function( $value ) { return (bool) $value; },
            'default'           => true,
            'autoload'          => 'yes',
        )
    );

    add_settings_section(
        'wp_reverse_title_main',
        '',
        '__return_false',
        'wp-reverse-title'
    );

    add_settings_field(
        'wp_reverse_title_separator',
        __( 'Custom separator', 'wp-reverse-title' ),
        'wp_reverse_title_render_separator_field',
        'wp-reverse-title',
        'wp_reverse_title_main'
    );

    add_settings_field(
        'wp_reverse_title_show_meta_box',
        __( 'Per-post opt-out', 'wp-reverse-title' ),
        'wp_reverse_title_render_meta_box_field',
        'wp-reverse-title',
        'wp_reverse_title_main'
    );
}

/**
 * Renders the custom separator input field with a live preview and Reset link.
 */
function wp_reverse_title_render_separator_field() {
    $value = get_option( 'wp_reverse_title_separator', '' );
    ?>
    <input
        type="text"
        name="wp_reverse_title_separator"
        id="wp_reverse_title_separator"
        value="<?php echo esc_attr( $value ); ?>"
        style="width: 300px;"
    />
    <a href="#" id="wp_reverse_title_reset" style="margin-left: 8px; text-decoration: none; color: #a00;" aria-label="<?php esc_attr_e( 'Reset separator to default', 'wp-reverse-title' ); ?>">
        <?php esc_html_e( 'Reset', 'wp-reverse-title' ); ?>
    </a>
    <p class="description">
        <?php esc_html_e( 'Replaces the default WordPress title separator (–) in reversed titles. Leave blank to use the WordPress default. Accepts any length of text - use this to add a custom tagline or slogan between your site name and page title.', 'wp-reverse-title' ); ?>
    </p>
    <p style="margin-top: 8px;">
        <strong><?php esc_html_e( 'Preview:', 'wp-reverse-title' ); ?></strong>
        <span id="wp_reverse_title_preview" style="font-family: monospace; margin-left: 6px;"></span>
    </p>
    <script>
    ( function() {
        var input   = document.getElementById( 'wp_reverse_title_separator' );
        var preview = document.getElementById( 'wp_reverse_title_preview' );
        var reset   = document.getElementById( 'wp_reverse_title_reset' );
        var site    = <?php echo wp_json_encode( get_bloginfo( 'name' ) ); ?>;
        var example = <?php echo wp_json_encode( __( 'About', 'wp-reverse-title' ) ); ?>;

        function updatePreview() {
            var sep = input.value.trim();
            preview.textContent = sep === ''
                ? site + ' \u2013 ' + example
                : site + ' ' + sep + ' ' + example;
        }

        input.addEventListener( 'input', updatePreview );

        reset.addEventListener( 'click', function( e ) {
            e.preventDefault();
            input.value = '';
            updatePreview();
            input.focus();
        } );

        updatePreview();
    } )();
    </script>
    <?php
}

/**
 * Renders the meta box enable/disable checkbox field.
 */
function wp_reverse_title_render_meta_box_field() {
    $enabled = (bool) get_option( 'wp_reverse_title_show_meta_box', true );
    ?>
    <label for="wp_reverse_title_show_meta_box">
        <input
            type="checkbox"
            name="wp_reverse_title_show_meta_box"
            id="wp_reverse_title_show_meta_box"
            value="1"
            <?php checked( $enabled ); ?>
        />
        <?php esc_html_e( 'Show the opt-out meta box on all public post types', 'wp-reverse-title' ); ?>
    </label>
    <p class="description">
        <?php esc_html_e( 'When enabled, a Reverse Title meta box appears in the editor sidebar allowing individual posts to opt out of title reversal.', 'wp-reverse-title' ); ?>
    </p>
    <?php
}

/**
 * Renders the full Settings -> Reverse Title page.
 */
function wp_reverse_title_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap">
        <!-- Reverse Title <?php echo esc_html( WP_REVERSE_TITLE_VERSION ); ?> -->
        <h1><?php esc_html_e( 'Reverse Title', 'wp-reverse-title' ); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'wp_reverse_title_settings' );
            do_settings_sections( 'wp-reverse-title' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// ---------------------------------------------------------------------------
// Per-post opt-out meta box (all public post types)
// ---------------------------------------------------------------------------

/**
 * Registers the opt-out meta box on all public post types, including posts,
 * pages, and any registered custom post types. Only runs when the per-post
 * opt-out setting is enabled under Settings -> Reverse Title.
 */
function wp_reverse_title_add_meta_box() {
    if ( ! get_option( 'wp_reverse_title_show_meta_box', true ) ) {
        return;
    }

    $post_types = get_post_types( array( 'public' => true ), 'names' );

    foreach ( $post_types as $post_type ) {
        add_meta_box(
            'wp_reverse_title_opt_out',
            __( 'Reverse Title', 'wp-reverse-title' ),
            'wp_reverse_title_render_meta_box',
            $post_type,
            'side',
            'default'
        );
    }
}

/**
 * Renders the opt-out checkbox inside the meta box.
 *
 * @param WP_Post $post The current post object.
 */
function wp_reverse_title_render_meta_box( $post ) {
    wp_nonce_field( 'wp_reverse_title_meta_box', 'wp_reverse_title_nonce' );

    $opted_out = (bool) get_post_meta( $post->ID, WP_REVERSE_TITLE_OPT_OUT_KEY, true );
    ?>
    <label for="wp_reverse_title_opt_out">
        <input
            type="checkbox"
            name="wp_reverse_title_opt_out"
            id="wp_reverse_title_opt_out"
            value="1"
            <?php checked( $opted_out ); ?>
        />
        <?php esc_html_e( "Don't reverse page title and site name for this post", 'wp-reverse-title' ); ?>
    </label>
    <?php
}

/**
 * Saves the opt-out checkbox value when the post is saved.
 *
 * @param int $post_id The ID of the post being saved.
 */
function wp_reverse_title_save_meta_box( $post_id ) {
    if (
        ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
        wp_is_post_revision( $post_id ) ||
        ! isset( $_POST['wp_reverse_title_nonce'] ) ||
        ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wp_reverse_title_nonce'] ) ), 'wp_reverse_title_meta_box' ) ||
        ! current_user_can( 'edit_post', $post_id )
    ) {
        return;
    }

    $opt_out = isset( $_POST['wp_reverse_title_opt_out'] )
        ? sanitize_text_field( wp_unslash( $_POST['wp_reverse_title_opt_out'] ) )
        : '';

    if ( ! empty( $opt_out ) ) {
        update_post_meta( $post_id, WP_REVERSE_TITLE_OPT_OUT_KEY, '1' );
    } else {
        delete_post_meta( $post_id, WP_REVERSE_TITLE_OPT_OUT_KEY );
    }
}

// ---------------------------------------------------------------------------
// Uninstall
// ---------------------------------------------------------------------------

/**
 * Cleans up plugin data on uninstall.
 * Removes both plugin options and all per-post opt-out meta entries.
 */
function wp_reverse_title_uninstall() {
    delete_option( 'wp_reverse_title_separator' );
    delete_option( 'wp_reverse_title_show_meta_box' );

    delete_post_meta_by_key( WP_REVERSE_TITLE_OPT_OUT_KEY );
}
