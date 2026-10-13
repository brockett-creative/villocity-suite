<?php
/**
 * Community Resources module: a card grid of links (town hall, library, school...).
 *
 * @package VilloCity
 */

namespace BCG\VilloCity\Modules\Resources;

use BCG\VilloCity\Admin\AdminMenu;
use BCG\VilloCity\Module;
use BCG\VilloCity\Support\Icons;

defined( 'ABSPATH' ) || exit;

/**
 * Reference implementation of a custom-post-type module.
 *
 * The post type and meta keys are unchanged from 2.x (voc_resource, _resource_url,
 * _resource_btn_text, _resource_icon) so existing content keeps working.
 *
 * Fixes vs 2.x: the button-text field showed literal PHP in the editor, the
 * `columns` shortcode attribute was ignored, saves had no capability check,
 * and the CSS was printed inline on every render.
 */
final class ResourcesModule extends Module {

	public const POST_TYPE = 'voc_resource';

	private const NONCE = 'voc_resource_meta';

	/**
	 * Has the stylesheet been enqueued?
	 *
	 * @var bool
	 */
	private bool $assets_enqueued = false;

	/**
	 * Module ID (matches 2.x).
	 */
	public static function id(): string {
		return 'resources';
	}

	/**
	 * Label.
	 */
	public static function label(): string {
		return __( 'Community Resources', 'villocity-suite' );
	}

	/**
	 * Description.
	 */
	public static function description(): string {
		return __( 'Card grid of links to local institutions and services, shown with [villocity_resources].', 'villocity-suite' );
	}

	/**
	 * Shortcodes.
	 *
	 * @return string[]
	 */
	public static function shortcodes(): array {
		return array( 'villocity_resources' );
	}

	/**
	 * Hooks.
	 */
	public function boot(): void {
		add_action( 'init', array( $this, 'register_content_types' ) );
		add_action( 'init', array( $this, 'register_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );

		if ( is_admin() ) {
			add_action( 'add_meta_boxes_' . self::POST_TYPE, array( $this, 'add_meta_box' ) );
			add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_meta' ), 10, 2 );
		}
	}

	/**
	 * Post type and meta registration.
	 */
	public function register_content_types(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'       => array(
					'name'          => __( 'Community Resources', 'villocity-suite' ),
					'singular_name' => __( 'Resource', 'villocity-suite' ),
					'add_new_item'  => __( 'Add New Resource', 'villocity-suite' ),
					'edit_item'     => __( 'Edit Resource', 'villocity-suite' ),
					'all_items'     => __( 'Resources', 'villocity-suite' ),
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => AdminMenu::SLUG,
				'show_in_rest' => true,
				'supports'     => array( 'title', 'editor', 'page-attributes' ),
			)
		);

		$meta = array(
			'_resource_url'      => 'esc_url_raw',
			'_resource_btn_text' => 'sanitize_text_field',
			'_resource_icon'     => array( self::class, 'sanitize_icon' ),
		);
		foreach ( $meta as $key => $sanitize ) {
			register_post_meta(
				self::POST_TYPE,
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => $sanitize,
					'auth_callback'     => static function ( $allowed, $meta_key, $post_id ) {
						return current_user_can( 'edit_post', $post_id );
					},
				)
			);
		}
	}

	/**
	 * Only allow known icon names.
	 *
	 * @param mixed $value Raw value.
	 */
	public static function sanitize_icon( $value ): string {
		$value = sanitize_key( (string) $value );
		return Icons::exists( $value ) ? $value : 'building';
	}

	/**
	 * Icon choices for the editor.
	 *
	 * @return array<string, string>
	 */
	private static function icon_choices(): array {
		return array(
			'building' => __( 'Government / civic', 'villocity-suite' ),
			'store'    => __( 'Shop / commerce', 'villocity-suite' ),
			'archive'  => __( 'History / museum', 'villocity-suite' ),
			'book'     => __( 'Library / education', 'villocity-suite' ),
			'shield'   => __( 'Public safety', 'villocity-suite' ),
			'globe'    => __( 'Website', 'villocity-suite' ),
			'users'    => __( 'Community', 'villocity-suite' ),
			'phone'    => __( 'Contact', 'villocity-suite' ),
			'map-pin'  => __( 'Location', 'villocity-suite' ),
		);
	}

	/**
	 * Meta box.
	 */
	public function add_meta_box(): void {
		add_meta_box( 'voc_resource_fields', __( 'Resource link', 'villocity-suite' ), array( $this, 'render_meta_box' ), self::POST_TYPE, 'normal', 'high' );
	}

	/**
	 * Render meta box.
	 *
	 * @param \WP_Post $post Post.
	 */
	public function render_meta_box( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE, self::NONCE . '_nonce' );
		$html = $this->view(
			'meta-box',
			array(
				'url'      => (string) get_post_meta( $post->ID, '_resource_url', true ),
				'btn_text' => (string) get_post_meta( $post->ID, '_resource_btn_text', true ),
				'icon'     => self::sanitize_icon( get_post_meta( $post->ID, '_resource_icon', true ) ),
				'choices'  => self::icon_choices(),
			)
		);
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the view.
	}

	/**
	 * Save meta box fields.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post.
	 */
	public function save_meta( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST[ self::NONCE . '_nonce' ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE . '_nonce' ] ) ), self::NONCE ) ) {
			return;
		}
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Sanitization happens in the callbacks passed to register_post_meta().
		foreach ( array( '_resource_url', '_resource_btn_text', '_resource_icon' ) as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_post_meta( $post_id, $key, wp_unslash( $_POST[ $key ] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			}
		}
	}

	/**
	 * Shortcode registration.
	 */
	public function register_shortcode(): void {
		foreach ( self::shortcodes() as $tag ) {
			add_shortcode( $tag, array( $this, 'shortcode' ) );
		}
	}

	/**
	 * Register front-end CSS.
	 */
	public function register_assets(): void {
		wp_register_style( 'villocity-resources', $this->url( 'assets/resources.css' ), array(), $this->asset_version( 'assets/resources.css' ) );
	}

	/**
	 * [villocity_resources columns="4" limit="0"]
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 */
	public function shortcode( $atts ): string {
		$atts = shortcode_atts(
			array(
				'columns' => '4',
				'limit'   => '0',
			),
			$atts,
			'villocity_resources'
		);

		$limit = absint( $atts['limit'] );
		$posts = get_posts(
			array(
				'post_type'        => self::POST_TYPE,
				'post_status'      => 'publish',
				'posts_per_page'   => $limit > 0 ? $limit : 100,
				'orderby'          => array(
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				),
				'no_found_rows'    => true,
				'suppress_filters' => false,
			)
		);

		if ( ! $posts ) {
			return '';
		}

		if ( ! $this->assets_enqueued ) {
			wp_enqueue_style( 'villocity-resources' );
			$this->assets_enqueued = true;
		}

		$items = array();
		foreach ( $posts as $post ) {
			$url      = (string) get_post_meta( $post->ID, '_resource_url', true );
			$btn_text = (string) get_post_meta( $post->ID, '_resource_btn_text', true );
			$items[]  = array(
				'title'    => get_the_title( $post ),
				'desc'     => wp_strip_all_tags( $post->post_content ),
				'url'      => $url,
				'external' => $url && wp_parse_url( $url, PHP_URL_HOST ) && wp_parse_url( $url, PHP_URL_HOST ) !== wp_parse_url( home_url(), PHP_URL_HOST ),
				'btn_text' => '' !== $btn_text ? $btn_text : __( 'Visit website', 'villocity-suite' ),
				'icon'     => Icons::svg( self::sanitize_icon( get_post_meta( $post->ID, '_resource_icon', true ) ) ),
			);
		}

		return $this->view(
			'grid',
			array(
				'items'   => $items,
				'columns' => max( 1, min( 6, absint( $atts['columns'] ) ) ),
			)
		);
	}
}
