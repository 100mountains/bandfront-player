<?php
/*
Plugin Name: Bandfront Player
Plugin URI: https://therob.lol
Version: 0.1
Text Domain: bandfront-player
Author: Bleep
Author URI: https://therob.lol
Description: Bandfront Player is a WordPress plugin that integrates a music player into WooCommerce product pages, allowing users to play audio files associated with products. It supports various player layouts, controls, and features like secure playback and file management. 
License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

add_action( 'init', function(){
	add_filter( 'get_post_metadata', function( $v, $object_id, $meta_key, $single, $meta_type = '' ){
		if ( '_elementor_element_cache' == $meta_key ) {
			global $wpdb;
			if ( $wpdb->get_var( $wpdb->prepare('SELECT COUNT(*) FROM ' . $wpdb->postmeta . ' WHERE post_id=%d AND meta_key="_elementor_element_cache" AND meta_value LIKE "%bfp%";', $object_id ) ) ) return false;
		}
		return $v;
	}, 10, 5 );
} );

// CONSTANTS
define( 'BFP_PLUGIN_PATH', __FILE__ );
define( 'BFP_PLUGIN_BASE_NAME', plugin_basename( __FILE__ ) );
define( 'BFP_WEBSITE_URL', get_home_url( get_current_blog_id(), '', is_ssl() ? 'https' : 'http' ) );
define( 'BFP_PLUGIN_URL', plugins_url( '', __FILE__ ) );
define( 'BFP_DEFAULT_PLAYER_LAYOUT', 'mejs-classic' );
define( 'BFP_DEFAULT_SINGLE_PLAYER', 0 );
define( 'BFP_DEFAULT_PLAYER_VOLUME', 1 );
define( 'BFP_DEFAULT_PLAYER_CONTROLS', 'default' );
define( 'BFP_FILE_PERCENT', 50 );
define( 'BFP_REMOTE_TIMEOUT', 300 );
define( 'BFP_DEFAULT_PlAYER_TITLE', 1 );
define( 'BFP_VERSION', '0.1' );

require_once 'inc/auto_update.inc.php';

// Load admin class if in admin
if (is_admin()) {
    require_once 'includes/class-bfp-admin.php';
}

// Load config class
require_once 'includes/class-bfp-config.php';

// Load file handler class  
require_once 'includes/class-bfp-file-handler.php';

// Load player manager class
require_once 'includes/class-bfp-player-manager.php';

// Load audio processor class
require_once 'includes/class-bfp-audio-processor.php';

// Load WooCommerce integration class
require_once 'includes/class-bfp-woocommerce.php';

// Load hooks manager class
require_once 'includes/class-bfp-hooks-manager.php';

// Load player renderer class
require_once 'includes/class-bfp-player-renderer.php';

// Load widgets
require_once 'widgets/playlist_widget.php';

if ( ! class_exists( 'BandfrontPlayer' ) ) {
	class BandfrontPlayer {

		// ******************** ATTRIBUTES ************************

		private $_files_directory_path;
		private $_files_directory_url;
		private $_force_purchased_flag = 0;
		private $_force_hook_title     = 0;

		private $_purchased_product_flag = false;
		private $_current_user_downloads = array();

	private $_hooks = array();
	
	// Admin instance
	private $_admin;
	
	// Config instance
	private $_config;
	
	// File handler instance
	private $_file_handler;
	
	// Player manager instance  
	private $_player_manager;
	
	// Audio processor instance
	private $_audio_processor;
	
	// WooCommerce instance
	private $_woocommerce;
	
	// Hooks manager instance
	private $_hooks_manager;

		// Player renderer instance
	private $_player_renderer;

		/**
		 * Get config instance
		 */
		public function get_config() {
			return $this->_config;
		}
		
		/**
		 * Get file handler instance
		 */
		public function get_file_handler() {
			return $this->_file_handler;
		}
		
		/**
		 * Get player manager instance
		 */
		public function get_player_manager() {
			return $this->_player_manager;
		}
		
		/**
		 * Get audio processor instance
		 */
		public function get_audio_processor() {
			return $this->_audio_processor;
		}
		
		/**
		 * Get WooCommerce instance
		 */
		public function get_woocommerce() {
			return $this->_woocommerce;
		}
		
		/**
		 * Get available player layouts
		 */
		public function get_player_layouts() {
			return $this->_config->get_player_layouts();
		}
		
		/**
		 * Get available player controls
		 */
		public function get_player_controls() {
			return $this->_config->get_player_controls();
		}

		/**
		 * BFP constructor
		 *
		 * @access public
		 * @return void
		 */
		public function __construct() {

			// Initialize hooks manager and get hooks configuration
			$this->_hooks_manager = new BFP_Hooks_Manager($this);
			$this->_hooks = $this->_hooks_manager->get_hooks_config();
			
			// Initialize config functionality
			$this->_config = new BFP_Config($this);
			
			// Initialize file handler
			$this->_file_handler = new BFP_File_Handler($this);
			
			// Initialize file directory properties from file handler
			$this->_files_directory_path = $this->_file_handler->get_files_directory_path();
			$this->_files_directory_url = $this->_file_handler->get_files_directory_url();
			
			// Initialize player manager
			$this->_player_manager = new BFP_Player_Manager($this);
			
			// Initialize audio processor
			$this->_audio_processor = new BFP_Audio_Processor($this);
			
			// Initialize WooCommerce integration
			$this->_woocommerce = new BFP_WooCommerce($this);
			
			// Initialize player renderer
			$this->_player_renderer = new BFP_Player_Renderer($this);
			
			// Initialize admin functionality if in admin area
			if (is_admin()) {
				$this->_admin = new BFP_Admin($this);
			}

		} // End __constructor

		public function activation() {
			$this->_file_handler->_clearDir( $this->get_files_directory_path() );
		}

		public function deactivation() {
			$this->_file_handler->_clearDir( $this->get_files_directory_path() );
		}

		public function plugins_loaded() {
			if ( ! class_exists( 'woocommerce' ) ) {
				return;
			}

			add_action( 'init', function() {
				load_plugin_textdomain( 'bandfront-player', false, basename( dirname( __FILE__ ) ) . '/languages/' );
			});

			add_filter( 'the_title', array( &$this, 'include_main_player_filter' ), 11, 2 );
			$this->init_force_in_title();
			$this->_load_addons();

			// Integration with the content editors
			require_once dirname( __FILE__ ) . '/pagebuilders/builders.php';
			BFP_BUILDERS::run();
		}

		public function get_product_attr( $product_id, $attr, $default = false ) {
			return $this->_config->get_product_attr($product_id, $attr, $default);
		} // End get_product_attr

		public function get_global_attr( $attr, $default = false ) {
			return $this->_config->get_global_attr($attr, $default);
		} // End get_global_attr

		// ******************** WordPress ACTIONS **************************

		public function init() {
			// Check if WooCommerce is installed or not
			if ( ! class_exists( 'woocommerce' ) ) {
				add_shortcode(
					'bfp-playlist',
					function( $atts ) {
						return '';
					}
				);
				return; }
			$_current_user_id = get_current_user_id();
			if (
				$this->get_global_attr( '_bfp_registered_only', 0 ) &&
				0 == $_current_user_id
			) {
				$this->set_insert_player(false);
			}

			// function to run daily for deleting the purchased files
			add_action( 'bfp_delete_purchased_files', array( $this, 'delete_purchased_files' ) );
			if ( ! wp_next_scheduled( 'bfp_delete_purchased_files' ) ) {
				wp_schedule_event( time(), 'daily', 'bfp_delete_purchased_files' );
			}

			if ( ! is_admin() ) {
				add_filter( 'bfp_preload', array( $this, 'preload' ), 10, 2 );

				// Define the shortcode for the playlist_widget
				add_shortcode( 'bfp-playlist', array( &$this, 'replace_playlist_shortcode' ) );
				$this->_preview();
				if ( isset( $_REQUEST['bfp-action'] ) && 'play' == $_REQUEST['bfp-action'] ) {
					if ( isset( $_REQUEST['bfp-product'] ) ) {
						$product_id = @intval( $_REQUEST['bfp-product'] );
						if ( ! empty( $product_id ) ) {
							$product = wc_get_product( $product_id );
							if ( false !== $product ){
								$this->update_playback_counter( $product_id );
								if ( isset( $_REQUEST['bfp-file'] ) ) {
									$files = $this->_get_product_files(
										array(
											'product' => $product,
											'file_id' => sanitize_key( $_REQUEST['bfp-file'] ),
										)
									);
									if ( ! empty( $files ) ) {
										$file_url = $files[ sanitize_key( $_REQUEST['bfp-file'] ) ]['file'];
										$this->_tracking_play_event( $product_id, $file_url );
										$this->_output_file(
											array(
												'product_id'   => $product_id,
												'url'          => $file_url,
												'secure_player' => @intval( $this->get_product_attr( $product_id, '_bfp_secure_player', 0 ) ),
												'file_percent' => @intval( $this->get_product_attr( $product_id, '_bfp_file_percent', BFP_FILE_PERCENT ) ),
											)
										);
									}
								}
							}
						}
					}
					exit;
				} else {
					// Use default WooCommerce hooks for Storefront theme
					// Single product page hooks
					foreach ( $this->_hooks['all_players'] as $_hook_name => $_hook_data ) {
						add_action( $_hook_name, array( $this, 'include_players' ), 10, $_hook_data );
					}

					// Allows to call the players directly by themes
					add_action( 'bfp_main_player', array( &$this, 'include_main_player' ), 11 );
					add_action( 'bfp_all_players', array( &$this, 'include_all_players' ), 11 );

					// Integration with woocommerce-product-table by barn2media
					add_filter( 'wc_product_table_data_name', array( &$this, 'product_table_data_name' ), 11, 2 );

					$players_in_cart = $this->get_global_attr( '_bfp_players_in_cart', false );
					if ( $players_in_cart ) {
						add_action( 'woocommerce_after_cart_item_name', array( &$this, 'player_in_cart' ), 11, 2 );
					}

					// Add product id to audio tag
					add_filter( 'bfp_audio_tag', array( &$this, 'add_data_product' ), 99, 4 );

					// Add class name to the feature image of product
					add_filter( 'woocommerce_product_get_image', array( &$this, 'add_class_attachment' ), 99, 6 );
					add_filter( 'woocommerce_single_product_image_thumbnail_html', array( &$this, 'add_class_single_product_image' ), 99, 2 );

					// Include players with the titles
					if (
						$this->get_global_attr( '_bfp_force_main_player_in_title', 1 ) &&
						! empty( $_SERVER['REQUEST_URI'] )
						/*
						 ! empty( $_SERVER['REQUEST_URI'] ) &&
						stripos( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), 'wc/store' ) !== false */
					) {
						add_filter( 'woocommerce_product_title', array( &$this, 'woocommerce_product_title' ), 10, 2 );

						add_filter( 'esc_html', array( &$this, 'esc_html' ), 10, 2 );
					}

					// For accepting the <source> tags
					add_filter( 'wp_kses_allowed_html', array( &$this, 'allowed_html_tags' ), 10, 2 );
				}
			}

		} // End init

		public function delete_post( $post_id, $demos_only = false, $force = false ) {
			return $this->_file_handler->delete_post($post_id, $demos_only, $force);
		} // End delete_post

		public function esc_html( $safe_text, $text ) {
			if ( strpos( $safe_text, 'bfp-player-container' ) !== false ) {
				return $text;
			}
			return $safe_text;
		} // End esc_html

		public function enqueue_resources() {
			if ( $this->get_enqueued_resources() ) {
				return;
			}
			$this->set_enqueued_resources(true);

			if ( function_exists( 'wp_add_inline_script' ) ) {
				wp_add_inline_script( 'wp-mediaelement', 'try{if(mejs && mejs.i18n && "undefined" == typeof mejs.i18n.locale) mejs.i18n.locale={};}catch(mejs_err){if(console) console.log(mejs_err);};' );
			}

			// Registering resources
			wp_enqueue_style( 'wp-mediaelement' );
			wp_enqueue_style( 'wp-mediaelement-skins', plugin_dir_url( __FILE__ ) . 'vendors/mejs-skins/mejs-skins.min.css', array(), BFP_VERSION );
			wp_enqueue_style( 'wp-mediaelement-modern-bfp-skins', plugin_dir_url( __FILE__ ) . 'vendors/mejs-skins/modern-bfp-skin.css', array(), BFP_VERSION );
			wp_enqueue_style( 'bfp-style', plugin_dir_url( __FILE__ ) . 'css/style.css', array(), BFP_VERSION );
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'wp-mediaelement' );
			wp_enqueue_script( 'bfp-script', plugin_dir_url( __FILE__ ) . 'js/public.js', array( 'jquery', 'wp-mediaelement' ), BFP_VERSION );

			$play_all = $GLOBALS['BandfrontPlayer']->get_global_attr(
				'_bfp_play_all',
				// This option is only for compatibility with versions previous to 1.0.28
				$GLOBALS['BandfrontPlayer']->get_global_attr( 'play_all', 0 )
			);

			$play_simultaneously = $GLOBALS['BandfrontPlayer']->get_global_attr( '_bfp_play_simultaneously', 0 );

			if ( function_exists( 'is_product' ) && is_product() ) {
				global $post;
				$post_types = $this->_get_post_types();
				if ( ! empty( $post ) && in_array( $post->post_type, $post_types ) ) {
					$play_all = $GLOBALS['BandfrontPlayer']->get_product_attr(
						$post->ID,
						'_bfp_play_all',
						// This option is only for compatibility with versions previous to 1.0.28
						$GLOBALS['BandfrontPlayer']->get_product_attr(
							$post->ID,
							'play_all',
							$play_all
						)
					);
				}
			}

			wp_localize_script(
				'bfp-script',
				'bfp_global_settings',
				array(
					'fade_out'            => $GLOBALS['BandfrontPlayer']->get_global_attr( '_bfp_fade_out', 1 ),
					'play_all'            => intval( $play_all ),
					'play_simultaneously' => intval( $play_simultaneously ),
					'ios_controls'        => $GLOBALS['BandfrontPlayer']->get_global_attr( '_bfp_ios_controls', false ),
					'onload'              => $GLOBALS['BandfrontPlayer']->get_global_attr( '_bfp_onload', false ),
				)
			);
		} // End enqueue_resources

		/**
		 * Replace the shortcode to display a playlist with all songs.
		 */
		public function replace_playlist_shortcode( $atts ) {
			return $this->_woocommerce->replace_playlist_shortcode($atts);
		} // End replace_playlist_shortcode

		/**
		 * Used for accepting the <source> tags
		 */
		public function allowed_html_tags( $allowedposttags, $context ) {
			if ( ! in_array( 'source', $allowedposttags ) ) {
				$allowedposttags['source'] = array(
					'src'  => true,
					'type' => true,
				);
			}
			return $allowedposttags;
		} // End allowed_html_tags

		public function preload( $preload, $audio_url ) {
			return $this->_audio_processor->preload($preload, $audio_url);
		} // End preload

		// ******************** WOOCOMMERCE ACTIONS ************************

		public function woocommerce_user_product( $product_id ) {
			return $this->_woocommerce->woocommerce_user_product($product_id);
		} // End woocommerce_user_product

		public function woocommerce_user_download( $product_id ) {
			return $this->_woocommerce->woocommerce_user_download($product_id);
		}

		public function woocommerce_product_title( $title, $product ) {
			return $this->_woocommerce->woocommerce_product_title($title, $product);
		} // End woocommerce_product_title

		/**
		 * Load the additional attributes to select the player layout, and if would be secure player or not
		 */


		public function get_player(
			$audio_url,
			$args = array()
		) {
			$default_args = array(
				'media_type'         => 'mp3',
				'player_style'       => BFP_DEFAULT_PLAYER_LAYOUT,
				'player_controls'    => BFP_DEFAULT_PLAYER_CONTROLS,
				'duration'           => false,
				'estimated_duration' => false,
				'volume'             => 1,
			);

			$args = array_merge( $default_args, $args );
			$id   = ( ! empty( $args['id'] ) ) ? 'id="' . esc_attr( $args['id'] ) . '"' : '';

			$args['player_style'] = ( $args['player_style'] == 'bfp-custom-skin' ? 'mejs-classic ' : '' ) . $args['player_style'];

		if (
				false === $this->_purchased_product_flag &&
				! empty( $args['product_id'] ) &&
				$args['duration'] &&
				@intval( $this->get_product_attr( $args['product_id'], '_bfp_secure_player', 0 ) ) &&
				(
					$file_percent = @intval( // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments
						$this->get_product_attr(
							$args['product_id'],
							'_bfp_file_percent',
							BFP_FILE_PERCENT
						)
					)
				) != 0
			) {
				$duration_components = explode( ':', $args['duration'] );
				if ( count( $duration_components ) ) {
					$duration_components = array_reverse( $duration_components );
					$duration_in_seconds = 0;
					$counter             = count( $duration_components );
					for ( $i = 0; $i < $counter; $i++ ) {
						$duration_in_seconds += pow( 60, $i ) * $duration_components[ $i ];
					}

					if ( $duration_in_seconds ) {
						$args['estimated_duration'] = ceil( $duration_in_seconds * $file_percent / 100 );
					}
				}
			}

			$preload = ( ! empty( $args['preload'] ) ) ? $args['preload'] : $GLOBALS['BandfrontPlayer']->get_global_attr(
				'_bfp_preload',
				// This option is only for compatibility with versions previous to 1.0.28
					$GLOBALS['BandfrontPlayer']->get_global_attr( 'preload', 'none' )
			);
			$preload = apply_filters( 'bfp_preload', $preload, $audio_url );

			return '<audio ' . (
					(
						isset( $args['volume'] ) &&
						is_numeric( $args['volume'] ) &&
						0 <= $args['volume'] * 1 &&
						$args['volume'] * 1 <= 1
					) ? 'volume="' . esc_attr( $args['volume'] ) . '"' : ''
				) . ' ' . $id . ' preload="none" data-lazyloading="' . esc_attr( $preload ) . '" class="bfp-player ' . esc_attr( $args['player_controls'] ) . ' ' . esc_attr( $args['player_style'] ) . '" ' . ( ( ! empty( $args['duration'] ) ) ? 'data-duration="' . esc_attr( $args['duration'] ) . '"' : '' ) . ( ( ! empty( $args['estimated_duration'] ) ) ? ' data-estimated_duration="' . esc_attr( $args['estimated_duration'] ) . '"' : '' ) . '><source src="' . esc_url( $audio_url ) . '" type="audio/' . esc_attr( $args['media_type'] ) . '" /></audio>';

		} // End get_player

		public function get_product_files( $id ) {
			$product = wc_get_product( $id );
			if ( ! empty( $product ) ) {
				return $this->_get_product_files(
					array(
						'product' => $product,
						'all'     => 1,
					)
				);
			}
			return array();
		}

		public function generate_audio_url( $product_id, $file_id, $file_data = array() ) {
			 return $this->_audio_processor->generate_audio_url( $product_id, $file_id, $file_data );
		}

		public function include_main_player_filter( $value, $id ) {
			global $wp;
			if (
				$this->_force_hook_title &&
				did_action('woocommerce_init') &&
				false === stripos( $value, '<audio' )
			) {
				try {
					if (
						( wp_doing_ajax() || ! is_admin() ) &&
						(
							! function_exists( 'is_product' ) ||
							! is_product() ||
							( is_product() && get_queried_object_id() != $id )
						) &&
						! is_cart() &&
						! is_page( 'cart' ) &&
						! is_checkout() &&
						is_int( $id ) &&
						empty( $_REQUEST['bfp_nonce'] )
					) {
						$p = wc_get_product( $id );
						if ( ! empty( $p ) ) {
							add_filter( 'esc_html', array( &$this, 'esc_html' ), 10, 2 );

							$player = '';
							$player = $this->include_main_player( $p, false );
							$value = $player . $value;
						}
					}
				} catch ( Exception $err ) {
					error_log( $err->getMessage() );
				}
			}
			return $value;
		}

		public function include_players( ...$args ) {
			if ( ! $this->get_inserted_player() ) {
				$this->set_inserted_player(true);
				if ( ! empty( $args ) ) {
					$this->include_all_players( $args[0] );
				} else {
					$this->include_all_players();
				}
			}

			if ( ! empty( $args ) ) {
				return $args[0];
			}
		} // End include_players

		// Replace original methods with delegation calls
		public function include_main_player( $product = '', $_echo = true ) {
			return $this->_player_renderer->include_main_player($product, $_echo);
		}

		public function include_all_players( $product = '' ) {
			return $this->_player_renderer->include_all_players($product);
		}

		private function _get_recursive_product_files( $product, $files_arr ) {
			return $this->_player_renderer->_get_recursive_product_files($product, $files_arr);
		}

		private function _get_product_files( $args ) {
			return $this->_player_renderer->_get_product_files($args);
		}

		public function player_in_cart( $cart_item, $cart_item_key ) {
			$product = wc_get_product( $cart_item['product_id'] );
			$this->include_all_players( $product );
		} // player_in_cart

		// Integration with woocommerce-product-table by barn2media
		public function product_table_data_name( $name, $product ) {
			if ( false === stripos( $name, '<audio' ) ) {
				$player = $this->include_main_player( $product, false );
				$player = str_replace( '<div ', '<div style="display:inline-block" ', $player );
				$name   = $player . $name;
			}
			return $name;
		} // product_table_data_name

		public function add_data_product( $player, $product_id, $index, $url ) {
			$player = preg_replace( '/<audio\b/i', '<audio controlslist="nodownload" data-product="' . esc_attr( $product_id ) . '" ', $player );
			return $player;
		} // End add_data_product

		private function _add_class( $html, $product ) {
			if ( preg_match( '/<img\b[^>]*>/i', $html, $image ) ) {
				$id = $product->get_id();
				if ( $GLOBALS['BandfrontPlayer']->get_product_attr( $id, '_bfp_on_cover', 0 ) ) {
					if ( preg_match( '/\bclass\s*=/i', $image[0] ) ) {
						$tmp_image = preg_replace( '/\bclass\s*=\s*[\'"]/i', "$0product-$id ", $image[0] );
					} else {
						$tmp_image = preg_replace( '/<img\b/i', "<img $0 class=\"product-$id\" ", $image[0] );
					}

					$html = str_replace( $image[0], $tmp_image, $html );
				}
			}

			return $html;
		} // End _add_class

		public function add_class_attachment( $html, $product, $size, $attr, $placeholder, $image ) {
			$id   = $product->get_id();
			$html = $this->_add_class( $html, $product );
			return $html;
		} // End add_class_attachment

		public function add_class_single_product_image( $html, $post_thumbnail_id ) {
			global $product;

			if ( ! empty( $product ) ) {
				$html = $this->_add_class( $html, $product );
			}
			return $html;
		} // add_class_single_product_image

		public function delete_purchased_files() {
			return $this->_file_handler->delete_purchased_files();
		} // End delete_purchased_files

		public function init_force_in_title( $v = null ) {
			if ( is_admin() ) {
				$this->_force_hook_title = 0;
				return;
			}

			if ( is_numeric( $v ) ) {
				$this->_force_hook_title = intval( $v );
				return;
			}

			$this->_force_hook_title = 1; // Default enabled for Storefront theme

			// Integration with "WOOF – Products Filter for WooCommerce" by realmag777
			if ( isset( $_REQUEST['action'] ) && 'woof_draw_products' == $_REQUEST['action'] ) {
				$this->_force_hook_title = 1;
			}

		} // End init_force_in_title

		// ******************** PRIVATE METHODS ************************

		private function get_ip_address() {
			if( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
				$ip = $_SERVER['HTTP_CLIENT_IP'];
			} elseif (! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
			} else{
				$ip = $_SERVER['REMOTE_ADDR'];
			}
			return $ip;
		} // End update_playback_counter

		public function clear_expired_transients() {
			$transient = get_transient( 'bfp_clear_expired_transients' );
			if( ! $transient || 24 * 60 * 60 <= time() - intval( $transient ) ) {
				set_transient( 'bfp_clear_expired_transients', time() );
				delete_expired_transients();
			}
		} // End clear_expired_transients

		private function update_playback_counter( $product_id ) {

			$ip = $this->get_ip_address();
			$transient_name = 'bfp-playback-record-' . md5( $ip ) . '-' . $product_id;
			$transient = get_transient( $transient_name );
			if ( ! get_transient( $transient_name ) ) {
				set_transient( $transient_name, 1, 12 * 60 * 60 );

				$counter = get_post_meta( $product_id, '_bfp_playback_counter', true );

				if ( is_numeric( $counter ) ) $counter = intval( $counter );
				else $counter = 0;

				$counter++;
				update_post_meta( $product_id, '_bfp_playback_counter', $counter );
			}
		} // End update_playback_counter

		public function _get_post_types( $mysql_in = false ) {
			 $post_types = array( 'product' );
			if ( ! empty( $GLOBALS['bfp_post_types'] ) && is_array( $GLOBALS['bfp_post_types'] ) ) {
				$post_types = $GLOBALS['bfp_post_types'];
			}
			if ( $mysql_in ) {
				return '"' . implode( '","', $post_types ) . '"';
			}
			return $post_types;
		} // End _get_post_types

		private function _load_addons() {
			$path = __DIR__ . '/addons';
			$path = __DIR__ . "/addons";
			$bfp = $this;

			if ( file_exists( $path ) ) {
				$addons = dir( $path );
				while ( false !== ( $entry = $addons->read() ) ) {
					if ( strlen( $entry ) > 3 && strtolower( pathinfo( $entry, PATHINFO_EXTENSION ) ) == "php" ) {
						include_once $addons->path . "/" . $entry;
					}
				}
			}
		} // End _load_addons

		private function _preview() {
			$user          = wp_get_current_user();
			$allowed_roles = array( 'editor', 'administrator', 'author' );

			if ( array_intersect( $allowed_roles, $user->roles ) ) {
				if ( ! empty( $_REQUEST['bfp-preview'] ) ) {
					// Sanitizing variable
					$preview = sanitize_text_field( wp_unslash( $_REQUEST['bfp-preview'] ) );

					// Remove every shortcode that is not in the plugin
					remove_all_shortcodes();
					add_shortcode( 'bfp-playlist', array( &$this, 'replace_playlist_shortcode' ) );

					if ( has_shortcode( $preview, 'bfp-playlist' ) ) {
						print '<!DOCTYPE html>';
						$if_empty = __( 'There are no products that satisfy the block\'s settings', 'bandfront-player' );
						wp_enqueue_script( 'jquery' );
						$output = do_shortcode( $preview );
						if ( preg_match( '/^\s*$/', $output ) ) {
							$output = '<div>' . $if_empty . '</div>';
						}

						// Deregister all scripts and styles for loading only the plugin styles.
						global  $wp_styles, $wp_scripts;
						if ( ! empty( $wp_scripts ) ) {
							$wp_scripts->reset();
						}
						$this->enqueue_resources();
						if ( ! empty( $wp_styles ) ) {
							$wp_styles->do_items();
						}
						if ( ! empty( $wp_scripts ) ) {
							$wp_scripts->do_items();
						}

						print '<div class="bfp-preview-container">' . $output . '</div>';  // phpcs:ignore WordPress.Security.EscapeOutput
						print '<script type="text/javascript">jQuery(window).on("load", function(){ var frameEl = window.frameElement; if(frameEl) frameEl.height = jQuery(".bfp-preview-container").outerHeight(true)+25; });</script>';
						exit;
					}
				}
			}
		} // End _preview

		public function _clearDir($dirPath) {
			return $this->_file_handler->_clearDir($dirPath);
		}
		
		public function get_files_directory_path() {
			return $this->_file_handler->get_files_directory_path();
		}
		
		public function get_files_directory_url() {
			return $this->_file_handler->get_files_directory_url();
		}

		private function _get_duration_by_url( $url ) {
			return $this->_audio_processor->get_duration_by_url($url);
		} // End _get_duration_by_url

		private function _generate_audio_url( $product_id, $file_index, $file_data = array() ) {
			return $this->_audio_processor->generate_audio_url($product_id, $file_index, $file_data);
		} // End _generate_audio_url

		private function _delete_truncated_files( $product_id ) {
			return $this->_file_handler->delete_truncated_files($product_id);
		} // End _delete_truncated_files

		/**
		 * Check if the file is an m3u or m3u8 playlist
		 */
		private function _is_playlist( $file_path ) {
			return $this->_audio_processor->is_playlist($file_path);
		} // End _is_playlist

		/**
		 * Check if the file is an audio file and return its type or false
		 */
		private function _is_audio( $file_path ) {
			return $this->_audio_processor->is_audio($file_path);
		} // End _is_audio

		private function _sort_list( $product_a, $product_b ) {
			if (
				! is_object( $product_a ) || ! method_exists( $product_a, 'get_menu_order' ) ||
				! is_object( $product_b ) || ! method_exists( $product_b, 'get_menu_order' )
			) {
				return 0;
			}

			$menu_order_a = $product_a->get_menu_order();
			$menu_order_b = $product_b->get_menu_order();

			if ( $menu_order_a == $menu_order_b ) {
				if (
				! method_exists( $product_a, 'get_name' ) ||
				! method_exists( $product_b, 'get_name' )
				) {
					return 0;
				}

				$name_a = $product_a->get_name();
				$name_b = $product_b->get_name();
				if ( $name_a == $name_b ) {
					return 0;
				}
				return ( $name_a < $name_b ) ? -1 : 1;
			}
			return ( $menu_order_a < $menu_order_b ) ? -1 : 1;
		} // End _sort_list

		private function _edit_files_array( $product_id, $files, $play_src = 0 ) {
			$p_files = array();
			foreach ( $files as $key => $file ) {
				$p_key = $key . '_' . $product_id;
				if ( gettype( $file ) == 'object' ) {
					$file = (array) $file->get_data();
				}
				$file['product']   = $product_id;
				$file['play_src']  = $play_src;
				$p_files[ $p_key ] = $file;
			}
			return $p_files;
		} // end _edit_files_array

		private function _demo_file_name( $url ) {
			return $this->_audio_processor->demo_file_name($url);
		} // End _demo_file_name

		private function _valid_demo( $file_path ) {
			return $this->_audio_processor->valid_demo($file_path);
		} // End _valid_demo

		private function _fix_url( $url ) {
			return $this->_audio_processor->fix_url($url);
		} // End _fix_url

		private function _output_file( $args ) {
			return $this->_audio_processor->output_file($args);
		} // End _output_file

		private function truncate_file( $file_path, $file_percent ) {
			return $this->_audio_processor->truncate_file($file_path, $file_percent);
		}

		private function _is_local( $url ) {
			return $this->_audio_processor->is_local($url);
		} // End _is_local

		private function _tracking_play_event( $product_id, $file_url ) {
			return $this->_audio_processor->tracking_play_event($product_id, $file_url);
		} // _tracking_play_event

		/**
		 * Delegation methods for player state management
		 */
	public function get_enqueued_resources() {
		return $this->_player_manager->get_enqueued_resources();
	}
	
	public function set_enqueued_resources($value) {
		return $this->_player_manager->set_enqueued_resources($value);
	}
	
	public function get_insert_player() {
		return $this->_player_manager->get_insert_player();
	}
	
	public function set_insert_player($value) {
		return $this->_player_manager->set_insert_player($value);
	}
	
	public function get_inserted_player() {
		return $this->_player_manager->get_inserted_player();
	}
	
	public function set_inserted_player($value) {
		return $this->_player_manager->set_inserted_player($value);
	}
	
	public function get_insert_main_player() {
		return $this->_player_manager->get_insert_main_player();
	}
	
	public function set_insert_main_player($value) {
		return $this->_player_manager->set_insert_main_player($value);
	}
	
	public function get_insert_all_players() {
		return $this->_player_manager->get_insert_all_players();
	}
	
	public function set_insert_all_players($value) {
		return $this->_player_manager->set_insert_all_players($value);
	}

	/**
	 * Get purchased product flag
	 */
	public function get_purchased_product_flag() {
		return isset($this->_purchased_product_flag) ? $this->_purchased_product_flag : false;
	}

	/**
	 * Set purchased product flag
	 */
	public function set_purchased_product_flag($value) {
		$this->_purchased_product_flag = $value;
	}

	/**
	 * Get force purchased flag
	 */
	public function get_force_purchased_flag() {
		return isset($this->_force_purchased_flag) ? $this->_force_purchased_flag : false;
	}

	/**
	 * Set force purchased flag
	 */
	public function set_force_purchased_flag($value) {
		$this->_force_purchased_flag = $value;
	}

	/**
	 * Get current user downloads
	 */
	public function get_current_user_downloads() {
		return isset($this->_current_user_downloads) ? $this->_current_user_downloads : array();
	}

	/**
	 * Set current user downloads
	 */
	public function set_current_user_downloads($value) {
		$this->_current_user_downloads = $value;
	}

		// ******************** WordPress ACTIONS **************************
	}
} // End class BandfrontPlayer

$GLOBALS['BandfrontPlayer'] = new BandfrontPlayer();

add_filter( 'option_sbp_settings', array( 'BandfrontPlayer', 'troubleshoot' ) );