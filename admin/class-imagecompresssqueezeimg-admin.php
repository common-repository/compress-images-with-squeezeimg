<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    imagecompresssqueezeimg
 * @subpackage imagecompresssqueezeimg/admin
 */

require_once plugin_dir_path( IMAGECOMPRESSSQUEEZ_PLUGIN_FILE ) . 'includes/class-imagecompresssqueezeimg-includes.php';

use Includes\imagecompresssqueezeimg_includes as includes;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    imagecompresssqueezeimg
 * @subpackage imagecompresssqueezeimg/admin
 * @author     Your Name <email@example.com>
 */
class imagecompresssqueezeimg_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $imagecompresssqueezeimg    The ID of this plugin.
	 */
	private $imagecompresssqueezeimg;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

    /**
     * @var includes
     */
    public $includes;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $imagecompresssqueezeimg       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $imagecompresssqueezeimg, $version ) {

		$this->imagecompresssqueezeimg = $imagecompresssqueezeimg;
		$this->version = $version;
        $this->includes = new includes;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in imagecompresssqueezeimg_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The imagecompresssqueezeimg_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

//        wp_enqueue_style($this->includes->getAdminStyle(), $this->includes->getAdminStyleUrl());
        wp_enqueue_style( $this->imagecompresssqueezeimg, plugin_dir_url( __FILE__ ) . 'css/imagecompresssqueezeimg-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in imagecompresssqueezeimg_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The imagecompresssqueezeimg_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
        wp_enqueue_script( $this->imagecompresssqueezeimg, plugin_dir_url( __FILE__ ) . 'js/imagecompresssqueezeimg-admin.js', array( 'jquery' ), $this->version, false );
//        wp_enqueue_script(
//            'pinta_imagecompresssqueezeimg-sc-admin',
//            $this->includes->getAdminJsUrl(),
//            $this->includes->getAdminJs()
//        );
//        wp_enqueue_script(
//            'pinta_imagecompresssqueezeimg-sc-media',
//            $this->includes->getAdminJsUrl(),
//            $this->includes->getMediaJs()
//        );
	}

}
