<?php
/*
Plugin Name: LazyLoad Content Images
Plugin URI:  https://benjihughes.co.uk/blog/lazyload-wordpress-images/
Description: Uses lazysizes.js to lazy load images in post/page content.
Version:     1.0.3
Author:      Benji Hughes
Author URI:  https://benjihughes.co.uk
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

defined( 'ABSPATH' ) or die( 'Cannot be executed manually.' );

/**
 * Class LazyLoadImages
 */
class LazyLoadImages {

	/**
	 * Store plugin options.
	 * @var array|void
	 */
	protected $options;


	/**
	 * LazyLoadImages constructor.
	 *
	 */
	function __construct() {

		add_filter( 'the_content', array( $this, 'convert_to_lazyload' ), 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'load_lazysizes_script' ) );
		add_action( 'admin_menu', array( $this, 'lazyload_add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'lazyload_settings_init' ) );

		$this->options = get_option( 'lazyload_settings' );
	}


	public function lazyload_add_admin_menu() {
		add_options_page( 'LazyLoad Images', 'LazyLoad Images', 'manage_options', 'lazyload_image', array(
			$this,
			'lazyload_options_page'
		) );
	}

	public function lazyload_settings_init() {

		register_setting( 'pluginPage', 'lazyload_settings' );

		add_settings_section(
			'lazyload_pluginPage_section',
			__( 'General Settings', 'wordpress' ),
			array( $this, 'lazyload_settings_section_callback' ),
			'pluginPage'
		);

		add_settings_field(
			'lazyload_checkbox_loadscript',
			__( 'Include lazysizes.js', 'wordpress' ),
			array( $this, 'lazyload_checkbox_loadscript_render' ),
			'pluginPage',
			'lazyload_pluginPage_section'
		);

		add_settings_field(
			'lazyload_text_classname',
			__( 'Class name to target (leave blank to convert all images)', 'wordpress' ),
			array( $this, 'lazyload_text_classname_render' ),
			'pluginPage',
			'lazyload_pluginPage_section'
		);

	}

	public function lazyload_checkbox_loadscript_render() {

		if ( ! isset( $this->options['lazyload_checkbox_loadscript'] ) ) {
			$this->options['lazyload_checkbox_loadscript'] = 0;
		}
		?>

        <input type="checkbox"
               name="lazyload_settings[lazyload_checkbox_loadscript]" <?php echo checked( $this->options['lazyload_checkbox_loadscript'], 1 ); ?>
               value="1">

		<?php
	}

	public function lazyload_settings_section_callback() {

		echo __( '', 'wordpress' );

	}

	public function lazyload_text_classname_render() {

		if ( ! isset( $this->options['lazyload_text_classname'] ) ) {
			$this->options['lazyload_text_classname'] = '';
		}
		?>

        <input type="text" name="lazyload_settings[lazyload_text_classname]"
               value="<?php echo $this->options['lazyload_text_classname']; ?>">

		<?php
	}

	public function lazyload_options_page() {
		?>

        <form action="options.php" method="post">

			<?php
			settings_fields( 'pluginPage' );
			do_settings_sections( 'pluginPage' );
			submit_button();
			?>

        </form>

		<?php

	}


	/**
	 * Regex matches all suitable img tags and replaces src & srcset
	 * with their data- counterparts.
	 *
	 * Outputs the original image tag inside a <noscript> tag as a
	 * fallback.
	 *
	 * @param $content
	 *
	 * @return string
	 */
	public function convert_to_lazyload( $content ) {

		$className = isset( $this->options['lazyload_text_classname'] ) ? $this->options['lazyload_text_classname'] : '';

		// Capture all images with the specified class, or all images if no class specified.
		if ( strlen( $className ) > 0 ) {
			$string = '/<img(?:.*?)class="(?:.*?)' . $className . '(?:.*?)"(?:.*?)\/>/i';
		} else {
			$string = '/<img(?:.*?)(class="(?:.*?)")?(?:.*?)\/>/i';
		}

		preg_match_all( $string, $content, $matches, PREG_SET_ORDER );

		foreach ( $matches as $val ) {


			// Get the matched element and create our fallback markup.
			$val = $val[0];
			$fallback = "<noscript>$val</noscript>";


			if ( strpos( $val, 'class=' ) == false ) {
				// Add lazyload class if no classes exist.
				$replace = str_replace( '<img ', '<img class="lazyload" ', $val );
			} else {
				// Append the lazyload class to class list.
				$replace = str_replace( 'class="', 'class="lazyload ', $val );
			}

			// Replace src with data-src, and srcset with data-srcset.
			$replace = str_replace( 'src=', 'data-src=', $replace );

			$replace = str_replace( 'srcset=', 'data-srcset=', $replace ) . $fallback;

			// Replace original image tag with our new one, including the fallback.
			$content = str_replace( $val, $replace, $content );
		}

		return $content;
	}

	/**
	 * Enqueues lazysizes.js file in the footer if the option is selected.
	 *
	 */
	public function load_lazysizes_script() {


		if ( isset( $this->options['lazyload_checkbox_loadscript'] )
		     && $this->options['lazyload_checkbox_loadscript'] ) {
			wp_enqueue_script( 'lazysizes', plugin_dir_url( __FILE__ ) . 'js/lazysizes.js', '', '1', true );
		}
	}
}

$lazy_load_images = new LazyLoadImages();
