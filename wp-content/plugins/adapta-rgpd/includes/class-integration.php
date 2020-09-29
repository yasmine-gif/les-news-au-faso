<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @package ARGPD
 * @subpackage Integration
 * @since 0.0.0
 *
 * @author César Maeso <info@superadmin.es>
 * @copyright (c) 2018, César Maeso (https://superadmin.es)
 */

/**
 * Integration class.
 *
 * @since  0.0.0
 */
class ARGPD_Integration {

	/**
	 * Parent plugin class.
	 *
	 * @var    string
	 * @since  0.0.0
	 */
	protected $plugin = null;


	/**
	 * Constructor.
	 *
	 * @since  0.0.0
	 *
	 * @param string $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		// set parent plugin.
		$this->plugin = $plugin;

		// register scripts and styles.
		$this->register();

		// initiate our hooks.
		$this->hooks();
	}


	/**
	 * Register scripts and Styles for cookies banner
	 *
	 * @since  0.0.0
	 */
	public function register() {

		// Register scripts.
		wp_register_script(
			'argpd-cookies-eu-banner',
			$this->plugin->url . 'assets/js/cookies-eu-banner.js',
			array(
				'jquery',
			),
			$this->plugin->version
		);

		// Register styles.
		$settings = $this->plugin->argpd_settings->get_settings();
		wp_register_style(
			'argpd-cookies-eu-banner',
			sprintf( '%sassets/css/cookies-banner-%s.css', $this->plugin->url, $settings['cookies-theme'] ),
			array(),
			$this->plugin->version
		);

		// Register Style duty to inform (Deber de informar).
		wp_register_script(
			'argpd-duty-to-inform',
			$this->plugin->url . 'assets/js/duty-to-inform.js',
			array(
				'jquery',
			),
			$this->plugin->version
		);

		wp_register_style(
			'argpd-informbox',
			sprintf( '%sassets/css/inform-box-%s.css', $this->plugin->url, $settings['informbox-theme'] ),
			array(),
			$this->plugin->version
		);

		// Register admin styles.
		wp_register_style(
			'argpd-admin',
			$this->plugin->url . 'assets/css/argpd-admin.css',
			array(),
			$this->plugin->version
		);
	}

	/**
	 * Register scripts and Styles for admin panel
	 */
	public function enqueue_admin_assets() {
		wp_enqueue_style( 'argpd-admin' );
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  0.0.0
	 */
	public function hooks() {

		$settings = $this->plugin->argpd_settings->get_settings();

		// Hooks if option-footer.
		if ( $settings['option-footer'] ) {

			// action for show footer.
			add_action( 'wp_footer', array( $this, 'show_footer_links' ) );

			// register legal menu.
			add_action( 'init', array( $this, 'register_legal_menu' ) );
			add_action( 'init', array( $this, 'create_legal_menu' ) );

			// add items to legal menu.
			add_filter( 'wp_nav_menu_items', array( $this, 'add_menu_legal_items' ), 10, 2 );
		}

		// Hooks if option-comments.
		if ( $settings['option-comments'] ) {
			while ( true ) {

				// disable if jetpack-comments is active.
				if ( class_exists( 'Jetpack' ) && Jetpack::is_module_active( 'comments' ) ) {
					break;
				}

				add_action( 'pre_comment_on_post', array( $this, 'check_consentimiento' ) );
				add_filter( 'comment_form_submit_field', array( $this, 'add_field' ) );
				break;
			}
		}

		// Hooks if have a duty to inform.
		if ( $settings['option-comments'] || $settings['option-forms'] ) {
			wp_enqueue_style( 'argpd-informbox' );
		}

 

		// Hooks if option-cookies.
		if ( $settings['option-cookies'] ) {
			// echo cookies banner.
			add_action( 'wp_footer', array( $this, 'cookiesbanner_footer' ) );

			// assets.
			wp_enqueue_style( 'argpd-cookies-eu-banner' );
			wp_enqueue_script( 'argpd-cookies-eu-banner' );

			// filters assets.
			add_filter( 'script_loader_src', [ $this, 'filter_load_assets' ], 10, 2 );

			// Eliminar los scripts sin consentimiento.
			if ( !isset( $_COOKIE['hasConsent'] ) || $_COOKIE['hasConsent'] != 'true' ) {
				
				add_action( 'template_redirect', array( $this, 'buffer_start' ), 1 );
				add_action( 'shutdown', array( $this, 'buffer_end' ), 1 );

				add_filter( 'argpd_unconsent_patterns', array( $this, 'unconsent_patterns' ) );				
				require_once dirname( __FILE__ ) . '/../lib/unconsent.php';
			}

			// actions.
			add_action( 'wp_footer', array( $this, 'argpd_banner_cookies' ) );
			
			if ( is_user_logged_in() ) {
				add_action( 'wp_ajax_accept_cookie_consent', array( $this, 'accept_cookie_consent' ) );
			} else {				
				add_action( 'wp_ajax_nopriv_accept_cookie_consent', array( $this, 'accept_cookie_consent' ) );
			}
		}

		// Enqueue admin assets.
		if ( current_user_can( 'manage_options' ) ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		}	

		// Evita la indexación en buscadores.
		add_action( 'wp_head', array( $this, 'noindex_meta' ) );
		add_filter( 'wp_sitemaps_posts_query_args', array( $this, 'exclude_legal_pages_from_sitemap' ), 10, 2 );
	}


	private function unconsent_patterns( $patterns ) {
		$patterns[]= "prueba";
		return $patterns;
	}


	/**
	 * Ajax to enqueue scripts dinamically
	 *
	 * @since  1.3.0
	 */
	public function accept_cookie_consent() {

		check_ajax_referer( 'accept_cookie_consent', 'security' );

		$collection = [];
		$data = wp_scripts();
		$settings = $this->plugin->argpd_settings;
		foreach ( $settings->get_setting( 'scripts-reject' ) as $script ) {

			foreach ( $data->registered as $k => $el ) {

				if ( $k != $script ) {
					continue;
				}

				if ( ! isset( $el->src ) ) {
					continue;
				}

				$url = $this->plugin->argpd_ui->prepare_url( $el->src );
				if ( strpos( $url, plugins_url() ) !== 0 ) {
						continue;
				}

				array_push( $collection, $url );
			}
		}
		print_r( join( ',', $collection ) );
		wp_die();
	}


	public function argpd_banner_cookies() {
		?>

<script type="text/javascript">
jQuery(function ($) {

	'use strict';

	window.Adapta_RGPD = window.Adapta_RGPD || {};

	// load events
	Adapta_RGPD.cargarEventos= function(){
		// Toggle visibility "Mas".
		$(".argpd-mas").click(function(){
			$(".argpd-informar > ul").toggle(); 
		});

		$("#cookies-eu-banner-closed").click(function(){
			var date = new Date();
      		date.setTime(date.getTime() + 33696000000);
      		document.cookie = 'hasConsent' + '=' + 'configure' + ';expires=' + date.toGMTString() + ';path=/';
      		window.location.reload();
		});

		<?php 
			$settings = $this->plugin->argpd_settings;
			if ( $settings->get_setting( 'cookies-fixed' ) == 1 ) : ?>
		window.setTimeout(function(){
			if( $("#cookies-eu-banner").length == 0 ) {
				$('#cookies-eu-banner-closed').show();
			}		
		}, 100);		
		<?php endif;
		?> 
	}

	// init
	Adapta_RGPD.init = function(){

		Adapta_RGPD.cargarEventos();
		
		jQuery(document).ready(function($) {
			// Prevent for search engine execute ajax function.
			var bots = /bot|crawler|spider|crawling/i;
			var isBot = bots.test(navigator.userAgent);
			if ( isBot ) {
				return;
			}

			<?php 
				$settings = $this->plugin->argpd_settings;
				if ( $settings->get_setting( 'cookies-reload' ) == 1 ) : ?>
					$("#cookies-eu-accept").click(function(){
						setTimeout( function(){ window.location.reload(); }, 100 );
					});
				<?php endif;
				
				if ( $settings->get_setting( 'cookies-fixed' ) == 1 ) : ?>
				$("#cookies-eu-reject").click(function(){
					$('#cookies-eu-banner-closed').show();
				});
				<?php endif; ?>

			// cookies-eu-banner callback
			new CookiesEuBanner(function () {
				var ajaxurl = '<?php echo esc_attr( admin_url( 'admin-ajax.php' ) ); ?>';
				var data = {
					action: 'accept_cookie_consent',
					security: '<?php echo esc_attr( wp_create_nonce( 'accept_cookie_consent' ) ); ?>'
				};				
				$.post( ajaxurl, data, function(response) {
					<?php if ( $settings->get_setting( 'cookies-fixed' ) == 1 ) : ?>
					$('#cookies-eu-banner-closed').show();
					<?php endif; ?>

					if ( undefined !== response.success && false === response.success ) {						
						return;
					}		
					var	scripts= response.split(",");
					scripts.forEach(function(src){
						var script = document.createElement( 'script' );
						script.setAttribute( "src", src );
						document.getElementsByTagName( "head" )[0].appendChild( script );
					});				
				});				
			}, true);
		});
	}
	
	Adapta_RGPD.init();
});
</script> 
		
		<?php
	}

	/**
	 * Engueue scripts and styles
	 *
	 * @since  0.0.0
	 */
	public function enqueue() {
		// Allows to disable enqueuing files on a particular page.
		$enqueue_agrpd = apply_filters( 'wp_agrpd_enqueue', true );
	}

	/**
	 * Add view for comment form submit
	 *
	 * @since  0.0.0
	 */
	public function add_field( $submit_field = '' ) {
		$consentimiento_view    = $this->plugin->pages->consentimiento_view();
		$deber_de_informar_view = $this->plugin->pages->deber_de_informar_view();
		return $consentimiento_view . $deber_de_informar_view . $submit_field;
	}

	/**
	 * Test if privacy is checked in comments
	 *
	 * @since  0.0.0
	 */
	public function check_consentimiento() {
		if ( ! isset( $_POST['agdpr-consentimiento'] ) ) {
			wp_die( __( 'Para poder comentar debes aceptar la política de privacidad.' ) );
		}
	}

	/**
	 * Echo cookies banner
	 *
	 * @since  0.0.0
	 */
	public function cookiesbanner_footer() {
		echo $this->plugin->pages->cookiesbanner_view();
	}

	/**
	 * Echo cookies banner
	 *
	 * @since  1.0.0
	 */
	public function show_footer_links() {

		// echo pre footer text.
		echo $this->plugin->pages->footer_links_view();

		if ( has_nav_menu( 'menu-argpd' ) ) {
			wp_nav_menu(
				array(
					'container'       => 'div',
					'container_class' => 'argpd-footer',
					'menu_class'      => '',
					'theme_location'  => 'menu-argpd',
					'fallback_cb'     => false,
				)
			);
		}
	}

	/**
	 * Añade la etiqueta robots con valor noindex,follow para excluir
	 * los textos legales
	 *
	 * @since  1.0.0
	 */
	public function noindex_meta() {
		if ( ! is_singular() ) {
			return;
		}

		$settings    = $this->plugin->argpd_settings;
		$legal_pages = array(
			(int) $settings->get_setting( 'cookiesID' ),
			(int) $settings->get_setting( 'privacidadID' ),
			(int) $settings->get_setting( 'avisolegalID' ),
		);

		$noindex = ( (int) $settings->get_setting( 'robots-index' ) == 1 ) ? false : true;
		if ( $noindex && in_array( get_the_ID(), $legal_pages ) ) {
			echo "\n\n" . '<meta name="robots" content="noindex,follow" />' . "\n\n";
		}
	}
	
	/**
	 * Excluye los textos legales en el Sitemap.
	 * https://make.wordpress.org/core/2020/07/22/new-xml-sitemaps-functionality-in-wordpress-5-5/
	 *
	 * @since  1.2.0
	 */
	public function exclude_legal_pages_from_sitemap($args, $post_type) {

		$settings    = $this->plugin->argpd_settings;
		$legal_pages = array(
			(int) $settings->get_setting( 'cookiesID' ),
			(int) $settings->get_setting( 'privacidadID' ),
			(int) $settings->get_setting( 'avisolegalID' ),
		);

		$noindex = ( (int) $settings->get_setting( 'robots-index' ) == 1 ) ? false : true;
		if ( $noindex ) {
			$args['post__not_in'] = isset( $args['post__not_in'] ) ? $args['post__not_in'] : array();
			$args['post__not_in'] = $legal_pages;			
		}

		return $args;
	}

	/**
	 * Register legal menu.
	 *
	 * @since  1.1
	 */
	public function register_legal_menu() {
		register_nav_menus(
			array(
				'menu-argpd' => esc_html__( 'Menú para los textos legales - RGPD', 'argpd' ),
			)
		);
	}

	/**
	 * Add menu items to legal menu.
	 *
	 * @param string $items items.
	 * @param string $args args.
	 * @since  1.1
	 */
	public function add_menu_legal_items( $items, $args ) {

		if ( 'menu-argpd' == $args->theme_location ) {

			$i = '';
			$settings = $this->plugin->argpd_settings;

			if ( '0' != $settings->get_setting( 'avisolegalID' ) ) {
				$aviso_legal_url = $settings->get_setting( 'avisolegalURL' );
				$i .= sprintf( '<li><a href="%s">%s</a></li>', esc_url( $aviso_legal_url ), esc_html__( 'Aviso Legal', 'argpd' ) );
			}

			if ( '0' != $settings->get_setting( 'privacidadID' ) ) {
				$privacidad_url = $settings->get_setting( 'privacidadURL' );
				$i .= sprintf( '<li><a href="%s">%s</a></li>', esc_url( $privacidad_url ), esc_html__( 'Política de Privacidad', 'argpd' ) );
			}

			if ( '0' != $settings->get_setting( 'cookiesID' ) ) {
				$cookies_url = $settings->get_setting( 'cookiesURL' );
				$i .= sprintf( '<li><a href="%s">%s</a></li>', esc_url( $cookies_url ), esc_html__( 'Política de Cookies', 'argpd' ) );
			}

			if ( '0' == $settings->get_setting( 'cookies-fixed' ) ) {				
				$i .= sprintf( '<li><a id="#cookies-eu-banner-closed" href="#">%s</a></li>', esc_html__( 'Configuración de Cookies', 'argpd' ) );
			}
			$items = $i . $items;
		}
		return $items;
	}

	/**
	 * Create menu 'Textos legales' if not exists and asign
	 * to menu-argpd location.
	 *
	 * @since  1.2
	 */
	public function create_legal_menu() {

		$locations = get_nav_menu_locations();
		if ( empty( $locations ) || ! array_key_exists( 'menu-argpd', $locations ) || empty( $locations['menu-argpd'] ) ) {

			// create menu if not exists.
			$name = 'Textos legales - Adapta RGPD';
			if ( ! wp_get_nav_menu_object( $name ) ) {
				wp_create_nav_menu( $name );
			}

			// asign menu-argpd location to menu.
			$menu = wp_get_nav_menu_object( $name );
			$locations['menu-argpd'] = $menu->term_id;
			set_theme_mod( 'nav_menu_locations', $locations );
		}
	}

	/**
	 * Conditional filtering script_loader_src filter.
	 *
	 * @since  1.3
	 *
	 * @param string $src src.
	 * @param string $handle handle.
	 */
	public function filter_load_assets( $src, $handle ) {

		$settings = $this->plugin->argpd_settings->get_settings();

		if ( empty( $settings['scripts-reject'] ) ) {
			return $src;
		}

		// not filter if not option-cookies.
		if ( ! $settings['option-cookies'] ) {
			return $src;
		}

		// not filter if has consent.
		if ( isset( $_COOKIE['hasConsent'] ) && $_COOKIE['hasConsent'] == 'true' ) {
			return $src;
		}

		// not filter admins.
		if ( current_user_can( 'manage_options' ) ) {
			return $src;
		}

		foreach ( $settings['scripts-reject'] as $script ) {
			if ( $script == $handle ) {
				return false;
			}
		}
		return $src;
	}

	/**
	 * Filtra el buffer de salida eliminando scripts
	 * concocidos que instalan cookies
	 *
	 * @since  1.3
	 *
	 */
	public function filter_buffer( $buffer ) {

		$unconsent= array();		
		$unconsent= apply_filters( 'argpd_unconsent_patterns', $unconsent );
	
		$script_pattern = '/(<script.*?>)(\X*?)<\/script>/i';
		if ( preg_match_all( $script_pattern, $buffer, $matches ) ) {
			foreach ( $matches[2] as $key => $script ) {
				// busca en cada script las cadenas no permitidas
				if ( $this->strpos_arr( $script, $unconsent ) !== false ) {
					// elimina el script no permitido
					$buffer= str_replace( $matches[1][$key].$script.'</script>', '', $buffer );
				}
			}
		}
  		return $buffer;
	}

	/**
	 * Crea un búfer de salida.
	 *
	 * @since  1.3
	 *
	 */
	public function buffer_start() { 
		ob_start( array( $this, 'filter_buffer' ) ); 
	}

	/**
	 * Vuelca el búfer de salia.
	 *
	 * @since  1.3
	 *
	 */
	public  function buffer_end() { 
		ob_end_flush(); 
	}

	/**
	 * Busca un string en un array 
	 *
	 * @since  1.3
	 *
	 * @param string $haystack un texto donde buscar.
	 * @param array $needle los textos a buscar.
	 */
	private function strpos_arr( $haystack, $needle ) {

        if ( !is_array( $needle ) ) $needle = array( $needle );

    	foreach ( $needle as $what ) {
        	if ( ( $pos = strpos( $haystack, $what ) ) !== false ) return $pos; 
    	}
    	
    	return false;
    }

}
