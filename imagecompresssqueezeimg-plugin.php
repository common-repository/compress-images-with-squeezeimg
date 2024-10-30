<?php
namespace Imagecompresspro;

require_once plugin_dir_path( __FILE__ ) . 'includes/class-imagecompresssqueezeimg-includes.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-imagecompresssqueezeimg-ajax.php';
require_once plugin_dir_path( IMAGECOMPRESSSQUEEZ_PLUGIN_FILE ) . 'includes/images_compress_squeezeimg/images.php';
require_once plugin_dir_path( IMAGECOMPRESSSQUEEZ_PLUGIN_FILE ) . 'includes/images_compress_squeezeimg/images_cnd.php';

require_once IMAGECOMPRESSSQUEEZ_PLUGIN_DIR . '/includes/class-imagecompresssqueezeimg-db.php';

use images_compress_squeezeimg\images as Images;
use images_compress_squeezeimg_cdn\images_cnd as Images_cdn;
use Includes\imagecompresssqueezeimg_includes as includes;
use ImagecompressproAjax\imagecompresssqueezeimg_ajax as ImgAjax;
use ImagecompresssqueezeimgDataBase\ImagecompresssqueezeimgDb as connect;

class ImagecompressproPlugin
{
    private static $instance;
    /**
     * @var array
     */
    private $admin_pages;

    private $includes;

    private $connction;

    public function __construct()
    {

        $this->connction = new connect();

        $this->settings = $this->connction->getSettings();

        $this->includes = new includes;

        add_action( 'plugins_loaded', array($this, 'lowInit'), 5);
        add_action( 'wp_ajax_imagecompresssqueezeimg_settings_helper', array(new ImgAjax(), 'update_settings'));
        add_action( 'wp_ajax_imagecompresssqueezeimg_check_cdn_config', array(new ImgAjax(), 'update_cdn_config'));
        add_action( 'wp_ajax_imagecompresssqueezeimg_cdn_compress', array(new Images_cdn(), 'compressImage'));
        add_action( 'wp_ajax_imagecompresssqueezeimg_cdn_purge_images', array(new Images_cdn(), 'purgeSDN'));
        add_action( 'wp_ajax_imagecompresssqueezeimg_check_cdn_config', array(new ImgAjax(), 'update_cdn_config'));
        add_action( 'wp_ajax_imagecompresssqueezeimg_url_compress_igm', array(new ImgAjax(), 'compress_igm'));
        add_action( 'wp_ajax_imagecompresssqueezeimg_try_compress_one_igm', array(new ImgAjax(), 'one_compress_igm'));
        add_action( 'wp_ajax_imagecompresssqueezeimg_url_delete_img', array(new ImgAjax(), 'delete_img'));
        add_action( 'wp_ajax_imagecompresssqueezeimg_getFolderTree', array(new ImgAjax(), 'getFolderTree'));
        add_action( 'wp_ajax_imagecompresssqueezeimg_getCompressImg', array(new ImgAjax(), 'getCompressImg'));
        add_action( 'wp_ajax_imagecompresssqueezeimg_media_compress_igm', array(new ImgAjax(), 'getMediaCompress'));
        add_action( 'wp_ajax_imagecompresssqueezeimg_compressOneImg', array(new ImgAjax(), 'compressOneImg'));
        add_action( 'wp_ajax_imagecompresssqueezeimg_media_restore_backup', array(new ImgAjax(), 'compressRestoreBackup'));
        add_action( 'wp_ajax_imagecompresssqueezeimg_sitemap_xml', array(new ImgAjax(), 'createImageSitemapXml'));
        add_action( 'imagecompress_squeezeimg_cron', array($this, 'image_squeezeimg_cron_func'));
        add_action( 'manage_media_custom_column', array($this, 'imagecompresssqueezeimg_columns'), 10, 2 );

//        add_action( 'rest_api_init', function () {
//            register_rest_route( 'imagecompress', '/getimage', array(
//                'methods'  => 'GET',
//                'callback' => 'imagecompresssqueezeimg_getImage',
//            ) );
//        } );
        add_filter( 'manage_media_columns', array($this, 'columns') );
        add_filter( 'cron_schedules', array($this, 'intervals_imagecompress') );
        add_filter( 'the_content', array($this, 'setPreloader'));
        add_filter( 'wp_generate_attachment_metadata', [$this, 'custom_upload_filter']);
        add_action( 'admin_notices', [$this, 'sample_admin_notice__success'] );

        if (isset($this->settings['status_cdn']) && $this->settings['status_cdn'] == 'A') {
            add_action( 'template_include', [$this, 'content_handler_cdn'] );
        }

    }

    public function content_handler_cdn ($path_to_salvation) {
        $hash = $this->settings['squeeze_cdn_hash'];

        $cdn_image = new Images_cdn();

        if (!$path_to_salvation || !file_exists($path_to_salvation)) {
            return $path_to_salvation;
        } else {
            ob_start();
            include $path_to_salvation;
            $html = ob_get_clean();
            $fg_img_list = $this->get_images_list($html);

//            $compresseds = $this->get_compressed_cdn_img($fg_img_list);
//            if ($compresseds) {
//                foreach ($compresseds as $path => $image) {
//                    if ($image['compressed']) {
//                        $html = str_replace($path, $cdn_image->createCdnImagePath($hash, $image['path']), $html);
//                    }
//                }
//            }

            foreach ($fg_img_list as $fg_img) {
                $html = str_replace(
                        $fg_img, $cdn_image->createCdnImagePath(
                                $hash, str_replace(get_site_url(), '', $fg_img)
                        ), $html
                );
            }

            echo $html;
            die;
        }

    }

    public function get_compressed_cdn_img ($images) {
        return $this->connction->getCdnImages($images);
    }

    public function get_images_list ($html) {
        $srcs = [];

        if (class_exists('\DOMDocument') && false) {
            $doc = new \DOMDocument();
            @$doc->loadHTML($html);
            $imageTags = $doc->getElementsByTagName('img');

            if (!empty($imageTags)) {
                foreach($imageTags as $tag) {
                    $srcs[] = $tag->getAttribute('src');
                }
            }
        } else {
            $results = [];
            preg_match_all('/<img[^>]+src=[\',\"]([^\",^\']+)[^>]+>/', $html, $results);
            if (isset($results[1])) {
                $srcs = $results[1];
            }
        }

        foreach ($srcs as $key => $src) {
            if (!strripos($src, $_SERVER['SERVER_NAME'])) {
                unset($srcs[$key]);
            }
        }

        return $srcs;
    }

    public function routebulk () {
        require IMAGECOMPRESSSQUEEZ_PLUGIN_DIR . '/admin/partials/imagecompresssqueezeimg-admin-media.php';
    }

    public function custom_upload_filter( $file ) {

        $settings = $this->connction->getSettings();

        // конвертим при загрузке ?
        if ($this->getSetting(
            $settings,
            'status',
            'A',
            true,
            false)
        ) {
            $uploadPath = wp_get_upload_dir();
            $image = new Images();
            foreach (IMAGECOMPRESS_FORMATS as $format) {
                if ($this->getSetting(
                    $settings,
                    $format . '_convert_upload',
                    'A',
                    true,
                    false)
                ) {
                    // вот тут конвертим
                    $this->compresUploaded(
                        $file['file'],
                        $settings,
                        $image,
                        $format,
                        ABSPATH . IMAGECOMPRESSSQUEEZ_CONTENT_BASE . '/uploads/'
                    );
                    if ($this->getSetting(
                        $settings,
                        'compress_thumbnail_' . $format,
                        'A',
                        true,
                        false)
                    ) {
                        // вот тут конвертим thumbnail
                        foreach ($file['sizes'] as $imgThumbnail) {
                            $this->compresUploaded(
                                $imgThumbnail['file'],
                                $settings,
                                $image,
                                $format,
                                $uploadPath['path'] . '/'
                            );
                        }
                    }
                }
            }
        }

        return $file;
    }

    public function compresUploaded ($upload, $settings, $image, $format, $path)
    {
        $filename = $path . $upload;
        $type = $format;  //type
        $quality = $settings['quality_of_compress']; //quality
        $replace_origin_images = 0; //replace_origin_images
        $data = $image->getApiSendSettings($type, $settings['api_token']);
        $response = $image->compressOneimg($filename, $type, $quality, $replace_origin_images, $data);
        return $response;
    }

    public function lowInit() {
        $this->initHooks();
    }

    public function initHooks() {
        add_action('admin_menu', array($this,'admin_pages'));
    }

    public function admin_pages() {
        $admin_pages = array();

        $admin_pages[] = add_options_page(
            __('Imagecompresspro Settings','imagecompresssqueezeimg'),
            'Image Compress Squeezeimg',
            'manage_options',
            'wp-imagecompress-settings',
            array($this, 'route')
        );

        $admin_pages[] = add_media_page(
                __('Images Compress Squeezeimg','imagecompresssqueezeimg'),
                __('Images Compress Squeezeimg','imagecompresssqueezeimg'),
                'edit_others_posts', 'wp-imagecompress-media',
                array( $this, 'routebulk' )
        );

        $this->admin_pages = $admin_pages;
    }

    public function route() {

        $cdn = 'D';

        if (isset($this->settings['squeeze_cdn_service']) && $this->settings['squeeze_cdn_service'] == 'A') {
            $cdn = 'A';
        }


        if ($cdn == 'D') {
            echo '<a id="swith_cdn_button" data-status="' . $cdn . '" href="#">Start using CDN </a>';
            require IMAGECOMPRESSSQUEEZ_PLUGIN_DIR . '/admin/partials/imagecompresssqueezeimg-admin-display.php';
        } else {
            if (isset($this->settings['squeeze_cdn_token']) && isset($this->settings['squeeze_cdn_hash']) ) {
                $cdn_image = new Images_cdn();
                $countNewImages = $cdn_image->getNewImgs();

                echo '<a id="swith_cdn_button" data-status="' . $cdn . '" href="#">Stop using CND</a>';
                require IMAGECOMPRESSSQUEEZ_PLUGIN_DIR . '/admin/partials/imagecompresssqueezeimg-admin-display-cdn.php';
            } else {
                echo '<a id="swith_cdn_button" data-status="' . $cdn . '" href="#">Back</a>';
                require IMAGECOMPRESSSQUEEZ_PLUGIN_DIR . '/admin/partials/imagecompresssqueezeimg-admin-check-cdn.php';
            }
        }

    }

    function my_custom_admin_notice(){
        global $countNewImages;
        if ($countNewImages > 0) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php printf(
                    /* translators: %d: количество новых изображений */
                        esc_html__('Добавлено новых изображений: %d', 'my-text-domain'),
                        intval($countNewImages)
                    );
                    ?></p>
            </div>
            <?php
        }
    }

    public static function getInstance() {
        if (is_null(self::$instance))
        {
            self::$instance = new ImagecompressproPlugin();
        }
        return self::$instance;
    }

    public function columns( $defaults ) {
        $settings = $this->connction->getSettings();
        if (isset($settings['status']) and $settings['status'] == 'A') {
            $defaults['wp-squeezeimg-original'] = __('Original Size ', 'imagecompresssqueezeimg');

            $defaults['wp-squeezeimg-compresss'] = __('Squeezeimg', 'imagecompresssqueezeimg');
            $defaults['wp-squeezeimg-compresss'] .=
                '&nbsp;<a href="options-general.php?page=wp-imagecompresssqueezeimg-settings&part=stats" title="'
                . __('Squeezeimg', 'imagecompresssqueezeimg')
                . '"><span class="dashicons dashicons-dashboard"></span></a>';

        }
        return $defaults;
    }

    public function imagecompresssqueezeimg_columns($column_name, $id, $extended = false) {
        $settings = $this->connction->getSettings();
        if (isset($settings['status']) and $settings['status'] == 'A') {
            $files = [];
            $files['main'] = wp_get_original_image_path($id);
            $files['thumb'] = [];
            foreach (get_intermediate_image_sizes() as $size) {
                $img = image_get_intermediate_size($id, $size);
                if (empty($img)) { continue; }
                $files['thumb'][] = ABSPATH . IMAGECOMPRESSSQUEEZ_CONTENT_BASE . '/uploads/' . $img['path'];
            }
            $thumbnailSize = 0;
            $thumbnailCount = count($files['thumb']);
            if ($column_name == 'wp-squeezeimg-original') {
                foreach ($files['thumb'] as $thumbnail){
                    $thumbnailSize += filesize($thumbnail);
                }
                echo esc_html(__('Original file: ','imagecompresssqueezeimg')) . round(filesize($files['main']) / 1024, 2) . " KB";
                echo '<br>';
                if ($thumbnailCount > 0) {
                    echo esc_html(__('+ ' . $thumbnailCount . ' thumbnail: ','imagecompresssqueezeimg')) . round($thumbnailSize / 1024, 2) . " KB";
                }
            } elseif ($column_name == 'wp-squeezeimg-compresss') {
                foreach (IMAGECOMPRESS_FORMATS as $ket => $format) {
                    $compressedIndex = null;
                    switch ($format) {
                        case 'jpg':
                            $type = preg_replace('/^.+\./', '', $files['main']);
                            $compressedIndex = "_compress." . $type;
                            $buttonText = 'Compress ';
                            break;
                        case 'jp2':
                            $compressedIndex = ".jp2";
                            $buttonText = 'Convert to ' . $format;
                            break;
                        case 'webp':
                            $compressedIndex = ".webp";
                            $buttonText = 'Convert to ' . $format;
                            break;
                        case 'avif':
                            $compressedIndex = ".avif";
                            $buttonText = 'Convert to ' . $format;
                            break;
                    }
                    if($ket){ echo '<hr>'; }

                    $compressed = [];
                    $compressed['main'] = $files['main'] . $compressedIndex;
                    $compressed['thumb'] = [];
                    foreach ($files['thumb'] as $thumbs) {
                        $compressed['thumb'][] = $thumbs . $compressedIndex;
                    }


                    $images = new Images();
                    $compressedImageList = $images->getAllImageConvert('jpg', false, $this->getSetting($settings, 'replace_origin_images'));

                    if ($settings['replace_origin_images'] == 'A' and $format == 'jpg') {
                        if (in_array($files['main'], $compressedImageList)) {
                            $compressed['main'] = $files['main'];
                        } else {
                            $compressed['main'] = false;
                        }

                        $thimbReplaceValid = true;
                        foreach ($files['thumb'] as $thumbAAAaaa) {
                            if (!in_array($thumbAAAaaa, $compressedImageList)) {
                                $thimbReplaceValid = false;
                            }
                        }
                        if ($thimbReplaceValid) {
                            $compressed['thumb'] = $files['thumb'];
                        } else {
                            $compressed['thumb'] = false;
                        }
                    }

                    echo '<button data-media-compress-format="' . esc_attr($format)
                        . '" data-media-id="' . $id . '" data-format="' . esc_attr($format)
                        . '" data-images=\'' . json_encode($files,  JSON_UNESCAPED_SLASHES)
                        . '\' class="imagecompresssqueezeimg-media-convert-button button button-primary no-outline">'
                        . __($buttonText, 'imagecompresssqueezeimg') . '</button>';
                    if (!($settings['replace_origin_images'] == 'A' and $format == 'jpg')) {
                        echo '<button data-media-compress-format="' . esc_attr($format) . '" data-media-id="'
                            . esc_attr($id) . '" data-format="' . ($format) . '" data-images=\''
                            . esc_html(json_encode(['orig' => $files, 'compress' => $compressed],
                                JSON_UNESCAPED_SLASHES))
                            . '\' class="imagecompresssqueezeimg-media-restore-backup restore-backup">'
                            . esc_html(__('Restore Backup','imagecompresssqueezeimg')) . '</button>';
                    }
                    echo '<br>';

                    echo '<span class="media-main-image-size">' . __($format . ' image: ','imagecompresssqueezeimg')
                        . '<span style="position: absolute !important;" data-media-compress-format="'
                        . esc_attr($format)
                        . '" data-media-id="' . esc_attr($id) . '" class="media-main-image-size-value">'
                        . esc_attr($this->getFileSize($compressed['main'] . '')) . '</span>' . '</span><br>';


                    if ( $thumbnailCount > 0) {
                        echo '<div><span class="media-thumbnail-image-size">'
                            . esc_html(__('+ ' . esc_attr($thumbnailCount) .' '. esc_attr($format)
                                . ' thumbnail: ','imagecompresssqueezeimg'))
                            . '<span style="position: absolute !important;" data-media-compress-format="'
                            . esc_attr($format)
                            . '" data-media-id="'
                            . esc_attr($id) . '" class="media-thumbnail-image-size-value">'
                            . $this->getFilesSize($compressed['thumb'], '') . '</span></div>';
                    }


                }
            }
        }
    }

    public function intervals_imagecompress($new_schedules)
    {
        $schedules['minute'] = array(
            'interval' => 60,
            'display'  => __('Minute','imagecompresssqueezeimg')
        );
        return $schedules;
    }

    public function getFileSize($file)
    {
        if(file_exists($file) and !is_dir($file))
        {
            return round(filesize($file) / 1024, 2) . " KB";
        } else {
            return __('not compress', 'imagecompresssqueezeimg');
        }
    }

    public function getFilesSize($files, $type)
    {
        if (!$files){
            return __('not compress', 'imagecompresssqueezeimg');
        }
        $size  = 0;
        foreach ($files as $file) {
            if(file_exists($file . $type) and !is_dir($file . $type))
            {
                $size += filesize($file . $type);
            } else {
                return __('not compress', 'imagecompresssqueezeimg');
            }
        }
        return round($size / 1024, 2) . " KB";
    }

    public function setPreloader($content) {

        $settings = $this->connction->getSettings();

        if(isset($settings['lazy_load']) and $settings['lazy_load'] == 'A'){
            if(isset($settings['squeezeimg_loader']) and !empty($settings['squeezeimg_loader'])){
                $loader = $settings['squeezeimg_loader'];
                $content = preg_replace(
                    '@<img(.*?)\s(src=[\'|"].*?[\'|"])(.*?)>@ms',
                    '<img$1 srcset="' . urldecode($loader) . '" data-imagecompresssqueezeimg-$2$3>',
                    $content
                );
            }
        }
        return $content;
    }

    public function image_squeezeimg_cron_func()
    {
        $images = new Images(null);
        $settings = $this->connction->getSettings();
        $status = $settings['status'];
        $quality = $settings['quality_of_compress'];
        $limit = $settings['squeezeimg_count_send_cron'];
        $replace = $settings['replace_origin_images'];
        $type = $settings['compress_squeezeimg_cron_type'];
        $token = $settings['api_token'];
        if($type == 'all'){
            $types = ['jpg','webp','jp2', 'avif'];
        } else {
            $types = [$type];
        }

        $images->cron($status,$quality,$limit,$replace,$types,$token);
    }

    public function getSetting ($settings, $key, $gues = 'A', $true = null, $false = false) {
        if (isset($settings[$key]) and $settings[$key] === $gues) {
            if ($true == null) { $true = $settings[$key];}
            return $true;
        } else {
            return $false;
        }
    }
}
