<?php

/**
 * @link              https://squeezeimg.com/
 *
 * @wordpress-plugin
 * Plugin Name:      Compress Images with Squeezeimg (Webp/Jp2/Avif)
 * Description:       The "Compress Images with Squeezeimg (Webp/Jp2/Avif)" plugin helps to optimize all your images and improve the performance of your website
 * Version:           1.0.7
 * Author:            squeezeimg
 * Author URI:        https://squeezeimg.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       compress-images-with-squeezeimg
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
define('IMAGECOMPRESSSQUEEZ_VERSION', '1.0.8');
define('IMAGECOMPRESSSQUEEZ_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IMAGECOMPRESSSQUEEZ_PLUGIN_BASENAME', 'imagecompresssqueezeimg');
define('IMAGECOMPRESSSQUEEZ_CONTENT_BASE', basename(WP_CONTENT_DIR));
define('IMAGECOMPRESSSQUEEZ_PLUGIN_FILE', IMAGECOMPRESSSQUEEZ_PLUGIN_DIR . 'imagecompresssqueezeimg.php');
define('IMAGECOMPRESS_PLUGIN_SETTINGS_PAGE', 'wp-imagecompress-settings');
define('IMAGECOMPRESS_ADMIN_PAGE', get_admin_url());
define('IMAGECOMPRESS_REST_GET_IMG', 'wp-json/imagecompress/getimage');//get_home_url()
define('IMAGECOMPRESS_CRON_URL', plugins_url('public/cron/cron.php', IMAGECOMPRESSSQUEEZ_PLUGIN_FILE));
define(
    'IMAGECOMPRESS_CACHE_DIR',
    plugin_dir_path( IMAGECOMPRESSSQUEEZ_PLUGIN_FILE ) . 'includes/images_compress_squeezeimg'
);
define('IMAGECOMPRESS_CONFIG_FILE', IMAGECOMPRESSSQUEEZ_PLUGIN_DIR . 'config');
define('IMAGECOMPRESS_HTACCESS', ABSPATH . '/.htaccess');
define('IMAGECOMPRESS_FORMATS', ['jpg', 'webp', 'jp2', 'avif']);


require_once IMAGECOMPRESSSQUEEZ_PLUGIN_DIR . 'includes/class-imagecompresssqueezeimg.php';
require_once IMAGECOMPRESSSQUEEZ_PLUGIN_DIR . 'includes/class-imagecompresssqueezeimg-db.php';
require_once IMAGECOMPRESSSQUEEZ_PLUGIN_DIR . 'imagecompresssqueezeimg-plugin.php';

use images_compress_squeezeimg\images as Images;
use ImagecompresssqueezeimgDataBase\ImagecompresssqueezeimgDb as dataBase;
/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-imagecompresssqueezeimg-activator.php
 */
function activate_imagecompresssqueezeimg() {
    $dbShema = new dataBase();
    $dbShema->install();
	require_once IMAGECOMPRESSSQUEEZ_PLUGIN_DIR . 'includes/class-imagecompresssqueezeimg-activator.php';
	imagecompresssqueezeimg_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-imagecompresssqueezeimg-deactivator.php
 */
function deactivate_imagecompresssqueezeimg() {
    $dbShema = new dataBase();
    $dbShema->uninstall();
	require_once IMAGECOMPRESSSQUEEZ_PLUGIN_DIR . 'includes/class-imagecompresssqueezeimg-deactivator.php';
	imagecompresssqueezeimg_Deactivator::deactivate();
}

register_activation_hook( IMAGECOMPRESSSQUEEZ_PLUGIN_FILE, 'activate_imagecompresssqueezeimg' );
register_deactivation_hook( IMAGECOMPRESSSQUEEZ_PLUGIN_FILE, 'deactivate_imagecompresssqueezeimg' );

function run_imagecompresssqueezeimg() {

	$plugin = new imagecompresssqueezeimg();
    $plugin->loadHooks();
    $plugin->run();

}

run_imagecompresssqueezeimg();

if (! function_exists("wpICP"))	{

    function wpICP()
    {
        return \Imagecompresspro\ImagecompressproPlugin::getInstance();
    }
}

wpICP();

function imagecompresssqueezeimg_getImage( WP_REST_Request $request ) {

    $imagesall = new Images();
    $connect = new dataBase();
    $settings = $connect->getSettings();
    $relase = 0;
    if(isset($settings['replace_origin_images']) and $settings['replace_origin_images'] == 'A'){
        $relase = 1;
    }
    $link = html_entity_decode(sanitize_text_field($_REQUEST['url']));

    $dir_root = ABSPATH;
    $file = $dir_root."/".$link;

    if(!file_exists($file)){

        $link = str_replace(' ','%20',$link);
        $name = basename(sanitize_text_field($_REQUEST['url']));
        $name = str_replace(' ','*',$name);
        $files = $imagesall->rglobBase($dir_root."/*".$name);
        if(!empty($files)){
            $link = str_replace($dir_root."/",'',$files[0]);
        }
    }

    $image = $link;
    $extension = pathinfo($image, PATHINFO_EXTENSION);
    try {
        if ($settings['status'] == 'A' && !is_null($settings['api_token'])) {
            if ($settings['status'] == 'A') {
                $format = 'jpg';
            }
            if ($settings['convert_images_to_webp_format'] == 'A') {
                $format = 'webp';
            }
            if ($settings['convert_images_to_avif_format'] == 'A') {
                $format = 'avif';
            }
            $safari = false;
            if (isset($_SERVER['HTTP_USER_AGENT'])) {
                if ((strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== false) &&
                    (strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') === false)) {
                    $safari = true;
                }
            }
            if ($safari) {
                if ($settings['convert_images_to_jp2_format'] == 'A') {
                    $format = 'jp2';
                }
            }



            if (file_exists($image)) {
                $pathinfo = pathinfo($image);



                if (!empty($pathinfo)) {
                    $new_name = $image;
                    if ($format == 'jpg') {
                        if (strpos($image,"_compress.jpg") !== false) {
                            header("Content-type: image/" . $extension);
                            header("IMAGE-COMPRESS-PRO: PINTA-ORIGIN-JPG");
                            header("IMAGE-COMPRESS-PRO-SIZE: "
                                . imagecompresssqueezeimg_filesizeFormatted($image));
                            header('Content-Length: ' . filesize($image));
                            echo file_get_contents($image);
                            exit;
                        } else {
                            $new_name .=  '_compress';
                        }
                    }
                    $new_file = ABSPATH . $new_name . "." . $format;

                    if (file_exists($new_file)) {

                        header("Content-type: image/" . $format);
                        header("IMAGE-COMPRESS-PRO: PINTA-WEBP");
                        header("IMAGE-COMPRESS-PRO-SIZE: "
                            . imagecompresssqueezeimg_filesizeFormatted($new_file));
                        header('Content-Length: ' . filesize($new_file));

                        echo file_get_contents($new_file);
                        exit;
                    } else {
                        $needle = '/imagecompresssqueezeimg/admin/images';
                        if (!strripos($image, $needle)) {
                            $quality = ($settings['quality_of_compress']) ?
                                $settings['quality_of_compress'] : 90;
                            if ($settings['status'] == 'A') {
                                $extension = pathinfo($image,PATHINFO_EXTENSION);
                                $new_file = $image . '_compress'.".".$extension;
                                $data = $imagesall->getApiSendSettings('jpg',$settings['api_token']);
                                $comresInfo = [
                                    'replace' => $relase,
                                    'type' => 'jpg'
                                ];

                                $imagesall->convertFile($image,$new_file, $quality,$data, $comresInfo);
                            }

                            if ($settings['convert_images_to_webp_format'] == 'A') {
                                $new_file = $image.".webp";
                                $data = $imagesall->getApiSendSettings('webp',$settings['api_token']);
                                $comresInfo = [
                                    'replace' => $relase,
                                    'type' => 'webp'
                                ];
                                $imagesall->convertFile($image,$new_file, $quality,$data, $comresInfo);
                            }

                            if ($settings['convert_images_to_jp2_format'] == 'A') {
                                $new_file = $image.".jp2";
                                $data = $imagesall->getApiSendSettings('jp2',$settings['api_token']);
                                $comresInfo = [
                                    'replace' => $relase,
                                    'type' => 'jp2'
                                ];
                                $imagesall->convertFile($image,$new_file, $quality,$data, $comresInfo);
                            }

                            if ($settings['convert_images_to_avif_format'] == 'A') {
                                $new_file = $image.".avif";
                                $data = $imagesall->getApiSendSettings('avif',$settings['api_token']);
                                $comresInfo = [
                                    'replace' => $relase,
                                    'type' => 'avif'
                                ];
                                $imagesall->convertFile($image,$new_file, $quality,$data, $comresInfo);
                            }
                        }
                    }
                }
            } else {
                header("LE-FIL23E: file not exists");
            }
        }
    } catch (\Exception $e) {
        //  echo $e->getMessage();die;
    }

    ob_get_clean();

    header("Content-type: image/" . $extension);
    header("IMAGE-COMPRESS-PRO: PINTA-ORIGIN");
    header("IMAGE-COMPRESS-PRO-SIZE: " . imagecompresssqueezeimg_filesizeFormatted($image));
    header('Content-Length: ' . filesize($image));

    echo file_get_contents($image);
    exit;

}

function imagecompresssqueezeimg_filesizeFormatted($path)
{
    $size = filesize($path);
    $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    $power = $size > 0 ? floor(log($size, 1024)) : 0;
    return number_format($size / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
}



function imagecompresssqueezeimg_checkServer($link)
{
    /*if (strpos($link, 'http') === false) {
        $this->throwError('Not found link. exaple need https://test.com/image.png in '.$link);
    }*/
    if (strpos($link, 'http') === false) {
        wp_die('Not found link. Example needs https://test.com/image.png in '.$link);
    }

    // Use wp_remote_get instead of cURL
    $response = wp_remote_get($link, array('method' => 'GET'));

    // Check for a successful response
    if (is_wp_error($response)) {
        wp_die('Error fetching URL: ' . $response->get_error_message());
    }

    // Get headers as array
    $headers = wp_remote_retrieve_headers($response);
    $server_header = isset($headers['server']) ? $headers['server'] : false;

    // Check server types
    if (stripos($server_header, 'nginx') !== false) {
        return 'nginx';
    }
    if (stripos($server_header, 'apache') !== false) {
        return 'apache';
    }

    // Return the server type if found
    if ($server_header) {
        return $server_header;
    }

    return false;
    /*$ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $link);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $output = curl_exec($ch);
    curl_close($ch);
    preg_match('@[S|s]erver\s*:?\s*(.*?)\s+@', $output, $match);
    preg_match('@[A|a]pache@', $output, $apache);
    preg_match('@[N|n]ginx@', $output, $nginx);

    if (!empty($nginx)) {
        return 'nginx';
    }
    if (!empty($apache)) {
        return 'apache';
    }
    if (isset($match[1])) {
        return $match[1];
    }

    return false;*/
}