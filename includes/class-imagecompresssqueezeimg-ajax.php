<?php

namespace ImagecompressproAjax;

require_once IMAGECOMPRESSSQUEEZ_PLUGIN_DIR . 'helper/' . IMAGECOMPRESSSQUEEZ_PLUGIN_BASENAME . '-modal-constructor.php';
require_once IMAGECOMPRESSSQUEEZ_PLUGIN_DIR . 'includes/class-' . IMAGECOMPRESSSQUEEZ_PLUGIN_BASENAME . '-db.php';

require_once plugin_dir_path( IMAGECOMPRESSSQUEEZ_PLUGIN_FILE ) . 'includes/images_compress_squeezeimg/images.php';

use images_compress_squeezeimg\images as Images;
use images_compress_squeezeimg_cdn\images_cnd as Images_cdn;
use ImagecompressproModalConstructor\imagecompresssqueezeimg_modal_constructor as ModalConstructor;
use ImagecompresssqueezeimgDataBase\ImagecompresssqueezeimgDb as connect;

class imagecompresssqueezeimg_ajax {

    /**
     * @var \ImagecompressproDataBase\ImagecompressproDb
     */
    private $connction;

    public function __construct() {
        $this->connction = new connect();
    }

    public function insert_img_cdn()
    {
        $image_cdn = new Images_cdn();
        $countFiles = $image_cdn->getNewImgs();


        return $countFiles;
    }

    public function update_cdn_config()
    {
        $response = [];
        if (isset($_POST['values'])) {
            parse_str($_POST['values'], $values);

            if (!isset($values['api_token']) || !isset($values['domain_name'])) {
                $response = [
                    'status' => false,
                    'text' => '<span style="color: red">Required parameters are missing.</span>'
                ];
            } else {
                $api_url = 'https://squeezeimg.com/api/storage/access';
                $auth_header = 'Authorization: Basic YWRtaW46YXdkYXNkITE=';
                $body = [
                    "token" => $values['api_token'],
                    "domain" => $values['domain_name']
                ];

                $api_response = wp_remote_post($api_url, [
                    'body' => $body,
                    'headers' => [
                        'Authorization' => $auth_header,
                        'Content-Type' => 'application/x-www-form-urlencoded'
                    ],
                    'timeout' => 10,
                    'httpversion' => '1.1',
                    'sslverify' => false
                ]);

                if (is_wp_error($api_response)) {
                    $response['status'] = false;
                    $response['text'] = '<span style="color: red">' . $api_response->get_error_message() . '</span>';
                } else {
                    $result = json_decode(wp_remote_retrieve_body($api_response), true);

                    if (!empty($result['status'])) {
                        $response = [
                            'status' => true,
                            'text' => '<span style="color: green">Success</span>'
                        ];

                        $settings = [
                            ['key' => 'squeeze_cdn_token', 'value' => $result['token']],
                            ['key' => 'squeeze_cdn_hash', 'value' => $result['hash']],
                            ['key' => 'api_token', 'value' => $values['api_token']],
                            ['key' => 'domain_name', 'value' => $values['domain_name']]
                        ];

                        $this->connection->setSettings($settings);
                        $this->insert_img_cdn();
                    } else {
                        $response['status'] = false;
                        $response['text'] = '<span style="color: red">' . $result['error'] . '</span>';
                    }
                }
            }
        } else {
            $response = [
                'status' => false,
                'text' => '<span style="color: red">No values posted.</span>'
            ];
        }

        echo json_encode($response);
        die;
    }

    public function updateImageList () {
        $pattern = ltrim(ABSPATH . "/*.{jpg,jpeg,png}", '/');

        $images = $this->rglob2($this->root_dir . DIRECTORY_SEPARATOR . $pattern, GLOB_BRACE);

        foreach ($images as $image) {
            $this->connction->updateCdnImage($image);
        }

        return $images;
    }

    public function rglob2($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);
        foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge($files, $this->rglob2($dir . '/' . basename($pattern), $flags));
        }

        return $files;
    }

    public function check_cdn_config($inc_val = null) {
        if ($inc_val === null && isset($_POST['values'])) {
            parse_str($_POST['values'], $params);
        } else {
            $params = $inc_val;
        }

        $api_token = $params['api_token'] ?? null;
        $domain_name = $params['domain_name'] ?? null;

        if (!$api_token || !$domain_name) {
            return ['error' => 'API token or domain name missing.'];
        }

        $data = [
            'token' => $api_token,
            'domain' => $domain_name
        ];

        $response = wp_remote_post('https://squeezeimg.com/api/storage/access', [
            'method' => 'POST',
            'timeout' => 15,
            'body' => $data,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ]
        ]);

        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        $result = wp_remote_retrieve_body($response);
        $content_json = json_decode($result, true);

        if (is_null($content_json)) {
            $content_json = ['error' => 'Failed to parse response.', 'response' => $result];
        }

        if (!empty($content_json['token'])) {
            $data = [
                'api_token' => $content_json['token'],
                'status_cdn' => 'A',
            ];

            $this->connection->setSettings($data);
        }

        return $content_json;
    }

    public function update_settings($inc_val = null) {
        $values = [];

        if (!$inc_val) {
            $incoming_value = $_POST['values'];
        }
        if (isset($incoming_value)) {
            $splitVal = explode('&', $incoming_value);

            foreach ($splitVal as $item) {
                list($key, $value) = explode('=', $item);
                $values[] = ['key' => $key, 'value' => $value];
            }

            $result = $this->connction->setSettings($values);
            $settings = $this->connction->getSettings();

            $this->setCron($settings);

            if (isset($settings['gzip_enabled']) and $settings['gzip_enabled'] == 'A') {
                $this->switchGzipSetting(true);
            } else {
                $this->switchGzipSetting(false);
            }
            if (isset($settings['status']) and $settings['status'] == 'A') {
                $this->switchEnabledHtaccessRules(true);
            } else {
                $this->switchEnabledHtaccessRules(false);
            }
            if (isset($settings['convert_images_to_webp_format']) and $settings['convert_images_to_webp_format'] == 'A') {
                $this->enbleFormat(true, 'webp');
            } else {
                $this->enbleFormat(false, 'webp');
            }
            if (isset($settings['convert_images_to_jp2_format']) and $settings['convert_images_to_jp2_format'] == 'A') {
                $this->enbleFormat(true, 'jp2');
            } else {
                $this->enbleFormat(false, 'jp2');
            }
            if (isset($settings['convert_images_to_avif_format']) and $settings['convert_images_to_avif_format'] == 'A') {
                $this->enbleFormat(true, 'avif');
            } else {
                $this->enbleFormat(false, 'avif');
            }
            if (isset($settings["squeezeimg_loader"])) {
                file_put_contents(IMAGECOMPRESS_CONFIG_FILE."/lazy_load.image", $settings["squeezeimg_loader"]);
            }
            if (isset($settings["lazy_load"]) && ($settings["lazy_load"] == 'A')) {
                if (!file_exists(IMAGECOMPRESS_CONFIG_FILE . "/lazy_load.enabled")) {
                    file_put_contents(IMAGECOMPRESS_CONFIG_FILE . "/lazy_load.enabled",1);
                }
            } else {
                if (file_exists(IMAGECOMPRESS_CONFIG_FILE . "/lazy_load.enabled")) {
                    unlink(IMAGECOMPRESS_CONFIG_FILE . "/lazy_load.enabled");
                }
            }

            $replace_origin_images = null;
            $replace_origin_not_lang = null;
            if(isset($settings['replace_origin_images']) and $settings['replace_origin_images'] == 'A')
            {
                $replace_origin_images = __(" On", IMAGECOMPRESSSQUEEZ_PLUGIN_BASENAME);
                $replace_origin_not_lang = true;
            } else {
                $replace_origin_images = __(" Off", IMAGECOMPRESSSQUEEZ_PLUGIN_BASENAME);
                $replace_origin_not_lang = false;
            }

            if (empty($result)){
                echo json_encode([
                    'replace_origin_images' => $replace_origin_images,
                    'replace_origin_not_lang' => $replace_origin_not_lang,
                    'status' => 'true',
                ]);
            } else {
                echo 'false';
            }

        } else {
            echo false;
        }
        die;
    }

    public function setCron($settings){
        wp_clear_scheduled_hook('imagecompress_squeezeimg_cron', [], false);
        if($this->getStateIfSet($settings, 'status') and $this->getStateIfSet($settings, 'cron_status')) {
            wp_schedule_event(time(), $settings['call_cron_time'], 'imagecompress_squeezeimg_cron', [], false);
        }
    }

    public function enbleFormat($status,$type)
    {
        if ($status) {
            if (!file_exists(IMAGECOMPRESS_CONFIG_FILE . "/" . $type . ".enabled")) {
                file_put_contents(IMAGECOMPRESS_CONFIG_FILE . "/" . $type . ".enabled", 1);
            }
        } else {
            if (file_exists(IMAGECOMPRESS_CONFIG_FILE . "/" . $type . ".enabled")) {
                unlink(IMAGECOMPRESS_CONFIG_FILE . "/" . $type . ".enabled");
            }
        }
    }

    public function switchGzipSetting($on = false)
    {
        $htaccessPath = IMAGECOMPRESS_HTACCESS;
        if(!file_exists($htaccessPath)){
            return false;
        }
        if ($on) {
            $toggleText = "
          ###IMAGE_CONVERTER_START###
          <ifModule mod_gzip.c>
              mod_gzip_on Yes
              mod_gzip_dechunk Yes
              mod_gzip_item_include file \.(html?|txt|css|js|php|pl)$
              mod_gzip_item_include mime ^application/x-javascript.*
              mod_gzip_item_include mime ^text/.*
              mod_gzip_item_exclude rspheader ^Content-Encoding:.*gzip.*
              mod_gzip_item_exclude mime ^image/.* 
              mod_gzip_item_include handler ^cgi-script$
            </ifModule>
            <IfModule mod_deflate.c>
              AddOutputFilterByType DEFLATE text/html
              AddOutputFilterByType DEFLATE text/css
              AddOutputFilterByType DEFLATE text/javascript
              AddOutputFilterByType DEFLATE text/xml
              AddOutputFilterByType DEFLATE text/plain
              AddOutputFilterByType DEFLATE image/x-icon
              AddOutputFilterByType DEFLATE image/svg+xml
              AddOutputFilterByType DEFLATE application/rss+xml
              AddOutputFilterByType DEFLATE application/javascript
              AddOutputFilterByType DEFLATE application/x-javascript
              AddOutputFilterByType DEFLATE application/xml
              AddOutputFilterByType DEFLATE application/xhtml+xml 
              AddOutputFilterByType DEFLATE application/x-font  
              AddOutputFilterByType DEFLATE application/x-font-truetype  
              AddOutputFilterByType DEFLATE application/x-font-ttf  
              AddOutputFilterByType DEFLATE application/x-font-otf 
              AddOutputFilterByType DEFLATE application/x-font-opentype 
              AddOutputFilterByType DEFLATE application/vnd.ms-fontobject
              AddOutputFilterByType DEFLATE font/ttf 
              AddOutputFilterByType DEFLATE font/otf 
              AddOutputFilterByType DEFLATE font/opentype
            # For Older Browsers Which Can't Handle Compression
              BrowserMatch ^Mozilla/4 gzip-only-text/html 
              BrowserMatch ^Mozilla/4\.0[678] no-gzip
              BrowserMatch \bMSIE !no-gzip !gzip-only-text/html
            </IfModule>
            <IfModule mod_mime.c>
            AddType text/css .css
            AddType text/x-component .htc
            AddType application/x-javascript .js
            AddType application/javascript .js2
            AddType text/javascript .js3
            AddType text/x-js .js4
            AddType video/asf .asf .asx .wax .wmv .wmx
            AddType video/avi .avi
            AddType image/bmp .bmp
            AddType application/java .class
            AddType video/divx .divx
            AddType application/msword .doc .docx
            AddType application/vnd.ms-fontobject .eot
            AddType application/x-msdownload .exe
            AddType image/gif .gif
            AddType application/x-gzip .gz .gzip
            AddType image/x-icon .ico
            AddType image/jpeg .jpg .jpeg .jpe
            AddType image/webp .webp
            AddType application/json .json
            AddType application/vnd.ms-access .mdb
            AddType audio/midi .mid .midi
            AddType video/quicktime .mov .qt
            AddType audio/mpeg .mp3 .m4a
            AddType video/mp4 .mp4 .m4v
            AddType video/mpeg .mpeg .mpg .mpe
            AddType video/webm .webm
            AddType application/vnd.ms-project .mpp
            AddType application/x-font-otf .otf
            AddType application/vnd.ms-opentype ._otf
            AddType application/vnd.oasis.opendocument.database .odb
            AddType application/vnd.oasis.opendocument.chart .odc
            AddType application/vnd.oasis.opendocument.formula .odf
            AddType application/vnd.oasis.opendocument.graphics .odg
            AddType application/vnd.oasis.opendocument.presentation .odp
            AddType application/vnd.oasis.opendocument.spreadsheet .ods
            AddType application/vnd.oasis.opendocument.text .odt
            AddType audio/ogg .ogg
            AddType application/pdf .pdf
            AddType image/png .png
            AddType application/vnd.ms-powerpoint .pot .pps .ppt .pptx
            AddType audio/x-realaudio .ra .ram
            AddType image/svg+xml .svg .svgz
            AddType application/x-shockwave-flash .swf
            AddType application/x-tar .tar
            AddType image/tiff .tif .tiff
            AddType application/x-font-ttf .ttf .ttc
            AddType application/vnd.ms-opentype ._ttf
            AddType audio/wav .wav
            AddType audio/wma .wma
            AddType application/vnd.ms-write .wri
            AddType application/font-woff .woff
            AddType application/font-woff2 .woff2
            AddType application/vnd.ms-excel .xla .xls .xlsx .xlt .xlw
            AddType application/zip .zip
            </IfModule>
            <IfModule mod_expires.c>
            ExpiresActive On
            ExpiresByType text/css A31536000
            ExpiresByType text/x-component A31536000
            ExpiresByType application/x-javascript A31536000
            ExpiresByType application/javascript A31536000
            ExpiresByType text/javascript A31536000
            ExpiresByType text/x-js A31536000
            ExpiresByType video/asf A31536000
            ExpiresByType video/avi A31536000
            ExpiresByType image/bmp A31536000
            ExpiresByType application/java A31536000
            ExpiresByType video/divx A31536000
            ExpiresByType application/msword A31536000
            ExpiresByType application/vnd.ms-fontobject A31536000
            ExpiresByType application/x-msdownload A31536000
            ExpiresByType image/gif A31536000
            ExpiresByType application/x-gzip A31536000
            ExpiresByType image/x-icon A31536000
            ExpiresByType image/jpeg A31536000
            ExpiresByType image/webp A31536000
            ExpiresByType application/json A31536000
            ExpiresByType application/vnd.ms-access A31536000
            ExpiresByType audio/midi A31536000
            ExpiresByType video/quicktime A31536000
            ExpiresByType audio/mpeg A31536000
            ExpiresByType video/mp4 A31536000
            ExpiresByType video/mpeg A31536000
            ExpiresByType video/webm A31536000
            ExpiresByType application/vnd.ms-project A31536000
            ExpiresByType application/x-font-otf A31536000
            ExpiresByType application/vnd.ms-opentype A31536000
            ExpiresByType application/vnd.oasis.opendocument.database A31536000
            ExpiresByType application/vnd.oasis.opendocument.chart A31536000
            ExpiresByType application/vnd.oasis.opendocument.formula A31536000
            ExpiresByType application/vnd.oasis.opendocument.graphics A31536000
            ExpiresByType application/vnd.oasis.opendocument.presentation A31536000
            ExpiresByType application/vnd.oasis.opendocument.spreadsheet A31536000
            ExpiresByType application/vnd.oasis.opendocument.text A31536000
            ExpiresByType audio/ogg A31536000
            ExpiresByType application/pdf A31536000
            ExpiresByType image/png A31536000
            ExpiresByType application/vnd.ms-powerpoint A31536000
            ExpiresByType audio/x-realaudio A31536000
            ExpiresByType image/svg+xml A31536000
            ExpiresByType application/x-shockwave-flash A31536000
            ExpiresByType application/x-tar A31536000
            ExpiresByType image/tiff A31536000
            ExpiresByType application/x-font-ttf A31536000
            ExpiresByType application/vnd.ms-opentype A31536000
            ExpiresByType audio/wav A31536000
            ExpiresByType audio/wma A31536000
            ExpiresByType application/vnd.ms-write A31536000
            ExpiresByType application/font-woff A31536000
            ExpiresByType application/font-woff2 A31536000
            ExpiresByType application/vnd.ms-excel A31536000
            ExpiresByType application/zip A31536000
            </IfModule>
            ###IMAGE_CONVERTER_END###
            ";
            $copyHtaccess = file_get_contents($htaccessPath);
            $checkHtaccess = preg_match('/###IMAGE_CONVERTER_START###.*?IMAGE_CONVERTER_END###/ms', $copyHtaccess);
            if (empty($checkHtaccess)) {
                $copyHtaccess .= $toggleText;
                file_put_contents($htaccessPath, $copyHtaccess);
            }

        } else {
            $copyHtaccess = file_get_contents($htaccessPath);
            $copyHtaccess = preg_replace('/###IMAGE_CONVERTER_START###.*?IMAGE_CONVERTER_END###/ms', '', $copyHtaccess);
            file_put_contents($htaccessPath, $copyHtaccess);
        }

    }

    public function switchEnabledHtaccessRules($on = false)
    {
        $htaccessPath = IMAGECOMPRESS_HTACCESS;

        if ($on) {

            $scheme = 'http';
            if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
                isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
                $scheme .= 's';
            }

            $baseUrl = get_site_url() . '/';

            if(file_exists($htaccessPath)) {
                $toggleText = "###IMAGE_CONVERTER_SQUEZZEIMG_HTACCESS_START###
    <IfModule mod_rewrite.c>
 <IfModule mod_env.c>
 SetEnv HTTP_MOD_REWRITE On
 </IfModule>

 RewriteEngine on
 <IfModule mod_headers.c>
 	<filesMatch \"\.(webp)$\">
 			Header set Content-Type \"image/webp\"
 	</filesMatch>
 	<filesMatch \"\.(jp2)$\">
     			Header set Content-Type \"image/jp2\"
     </filesMatch>
     <filesMatch \"(_compress\.jpg)$\">
           Header set image-compress-squeezeimg  \"compress\"
     </filesMatch>
 </IfModule>
 RewriteCond " . IMAGECOMPRESS_CONFIG_FILE . "/jpg.enabled -f
 RewriteRule . - [E=FORMAT_IMG:_compress.jpg]
 RewriteCond expr \" %{HTTP_ACCEPT} -strmatch '*avif*'\"
 RewriteCond " . IMAGECOMPRESS_CONFIG_FILE . "/avif.enabled -f
 RewriteRule . - [E=FORMAT_IMG:.avif]
 RewriteCond expr \" %{HTTP_ACCEPT} -strmatch '*webp*'\"
 RewriteCond " . IMAGECOMPRESS_CONFIG_FILE . "/webp.enabled -f
 RewriteRule . - [E=FORMAT_IMG:.webp]
 RewriteCond expr \"! %{HTTP_ACCEPT} -strmatch '*webp*'\"
 RewriteCond " . IMAGECOMPRESS_CONFIG_FILE . "/jp2.enabled -f
 RewriteRule . - [E=FORMAT_IMG:.jp2]



 
 RewriteCond " . IMAGECOMPRESS_CONFIG_FILE . "/getimage.php -f
 RewriteCond expr \"! %{REQUEST_URI} -strmatch '*images_compress_squeezeimg*'\"
 RewriteCond expr \"! %{REQUEST_URI} -strmatch '*_compress*'\"
 RewriteCond %{REQUEST_URI} .*\.(jpg|jpeg|png)$ [NC]
 RewriteRule (.+)\.(jpe?g|png)$ " . $baseUrl . IMAGECOMPRESS_REST_GET_IMG . "?url=$1.$2 [NC,L]
 

 </IfModule>
            ###IMAGE_CONVERTER_SQUEZZEIMG_HTACCESS_END###
 ";
//                  RewriteCond %{REQUEST_FILENAME}%{ENV:FORMAT_IMG} -f
//                  RewriteRule (.*\.(jpg|png|jpeg)$) %{REQUEST_URI}%{ENV:FORMAT_IMG} [NC,L]
                $copyHtaccess = file_get_contents($htaccessPath);
                $checkHtaccess = preg_match('/###IMAGE_CONVERTER_SQUEZZEIMG_HTACCESS_START###.*?IMAGE_CONVERTER_SQUEZZEIMG_HTACCESS_END###/ms', $copyHtaccess);
                if (empty($checkHtaccess)) {
                    $toggleText .= $copyHtaccess;
                    file_put_contents($htaccessPath, $toggleText);
                }
            }
            if (!file_exists(IMAGECOMPRESS_CONFIG_FILE . "/" . "jpg.enabled")) {
                file_put_contents(IMAGECOMPRESS_CONFIG_FILE . "/" . "jpg.enabled", 1);
            }
            if (!file_exists(IMAGECOMPRESS_CONFIG_FILE . "/" . "getimage.php")) {
                file_put_contents(IMAGECOMPRESS_CONFIG_FILE . "/" . "getimage.php", 1);
            }
        } else {
            if(file_exists($htaccessPath)) {
                $copyHtaccess = file_get_contents($htaccessPath);
                $copyHtaccess = preg_replace('/###IMAGE_CONVERTER_SQUEZZEIMG_HTACCESS_START###.*?IMAGE_CONVERTER_SQUEZZEIMG_HTACCESS_END###/ms', '', $copyHtaccess);
                file_put_contents($htaccessPath, $copyHtaccess);
            }
            if (file_exists(IMAGECOMPRESS_CONFIG_FILE . "/" . "jpg.enabled")) {
                unlink(IMAGECOMPRESS_CONFIG_FILE . "/" . "jpg.enabled");
            }
            if (file_exists(IMAGECOMPRESS_CONFIG_FILE . "/" . "getimage.php")) {
                unlink(IMAGECOMPRESS_CONFIG_FILE . "/" . "getimage.php");
            }
        }
    }

    public function compress_igm() {

        $request = $_REQUEST;
        $settings = $this->connction->getSettings();

        if(!isset($request['name']) || !isset($request['page'])){
            $response = ['error' => "Missing params"];
        } else {
            if (isset($_POST['root_path']) and $_POST['root_path'] == 'media') {
                $image = new Images(IMAGECOMPRESSSQUEEZ_CONTENT_BASE . '/uploads');
            } else {
                $image = new Images();
            }

            if(!is_null($this->getIfSet($settings, 'api_token'))){
                $data = $image->getApiSendSettings(sanitize_text_field($request['name']), $this->getIfSet($settings, 'api_token'));

                $response = $image->convert (
                    sanitize_text_field($request['name']),
                    $this->getIfSet($settings, 'count_send_images_in_request', 1),
                    $this->getIfSet($settings, 'quality_of_compress', 60),
                    sanitize_text_field($request['page']),
                    $this->getStateIfSet($settings, 'replace_origin_images'),
                    $data
                );


            } else {
                $response = [
                    'error' => 'Token not found'
                ];
            }
        }
        $this->jsonResponse($response);
    }

    public function one_compress_igm()
    {
        $response = [];
        $type = sanitize_text_field($_REQUEST['type']);
        $token = sanitize_text_field($_REQUEST['token']);
        if ( !empty($type)) {
            $image = new Images();
            $response = $image->tryCompress($type, $token);
        } else {
            $response = [
                'error' => 'Missing params'
            ];
        }
        $this->jsonResponse($response);
    }

    public function getCompressImg()
    {
        $response = [];
        $image = new Images();
        $folder = sanitize_text_field($_REQUEST['folder']);
        $type = sanitize_text_field($_REQUEST['type']);
        $settings = $this->connction->getSettings('replace_origin_images');

        $quality = sanitize_text_field($_REQUEST['quality']);
        $page = 1;
        if (isset($_REQUEST['page'])) {
            $page = sanitize_text_field($_REQUEST['page']);
        }
        if (!empty($folder) && !empty($type)  && !empty($type)) {
            $response = $image->getCompressLog(
                $folder,
                $type,
                $quality,
                $page,
                1,
                $this->getStateIfSet($settings,'replace_origin_images'));
        } else {
            $response = [
                'error' => 'Missing params'
            ];
        }

        $this->jsonResponse($response);
    }

    public function getMediaCompress()
    {
        $image = new Images();
        $settings = $this->connction->getSettings();

        $images = json_decode(stripcslashes($_POST['images_json']), true);

        $format = sanitize_text_field($_POST['formatconvert']);
        $mainImage = sanitize_text_field($images['main']);
        $thumbImage = $images['thumb'];

        $mainImageStatus = null;
        $thumbImageStatus = null;
        $mainImageSize = 0;
        $sizesImageCompress = 0;
        $secondImageCompress = [];

        $mainImgCompress = $this->compresOneImage($mainImage, $settings, $image, $format);
        if (!isset($mainImgCompress['error'])) {
            $mainImageStatus = true;
            $mainImageSize = round(filesize($mainImgCompress['image']['filename']) / 1024, 2);
        } else {
            $mainImageStatus = false;
        }
        foreach ($thumbImage as $thumbImg) {
            $thumImageCompress = $this->compresOneImage(sanitize_text_field($thumbImg), $settings, $image, $format);

            if (!isset($thumImageCompress['error'])) {
                $sizesImageCompress += filesize($thumImageCompress['image']['filename']);
            } else {
                $thumbImageStatus = false;
                break;
            }
            $thumbImageStatus = true;
        }
        $output = [];
        $output['statuses'] = ['main' => $mainImageStatus, 'thumb' => $thumbImageStatus];
        $output['sizes'] = ['main' => $mainImageSize, 'thumb' => round($sizesImageCompress / 1024, 2)];

        echo json_encode($output);
        die;
    }

    public function compresOneImage ($filename, $settings, $image, $format)
    {
        $type = $format;  //type
        $quality = $settings['quality_of_compress']; //quality
        $replace_origin_images = $this->getStateIfSet($settings, 'replace_origin_images'); //replace_origin_images
        $data = $image->getApiSendSettings($type, $settings['api_token']);
        $response = $image->compressOneimg($filename, $type, $quality, $replace_origin_images, $data);
        return $response;
    }

    public function compressRestoreBackup(){
        $images = json_decode(stripcslashes($_POST['images_json']), true);
        $compressImage = sanitize_text_field($images['compress']);
        $origImage = sanitize_text_field($images['orig']);

        if (file_exists($compressImage['main']) and file_exists($origImage['main'])) {
            unlink($compressImage['main']);
        }
        if (!empty($compressImage['thumb'])) {
            foreach ($compressImage['thumb'] as $key => $thumbs) {
                if (file_exists($thumbs)) {
                    unlink($thumbs);
                }
            }
        }
        echo json_encode($compressImage);
        die;
    }

    public function compressOneImg()
    {

        $response = [];
        $image = new Images();
        $filename = sanitize_text_field($_REQUEST['filename']);
        $type = sanitize_text_field($_REQUEST['type']);
        $quality = sanitize_text_field($_REQUEST['quality']);
        $settings = $this->connction->getSettings();

        if(!empty($filename) && !empty($type) && !empty($quality)){
            if(!is_null($this->getIfSet($settings,'api_token'))){

                $data = $image->getApiSendSettings($type, $this->getIfSet( $settings, 'api_token'));

                $response = $image->compressOneimg($filename,$type,$quality,$this->getStateIfSet($settings,'replace_origin_images'),$data);

            } else {
                $response = [
                    'error' => 'Token not found'
                ];
            }
        } else {
            $response = [
                'error' => 'Missing params'
            ];
        }
        $this->jsonResponse($response);
    }

    public function delete_img()
    {
        $settings = $this->connction->getSettings('replace_origin_images');
        $request = $_REQUEST;
        if(!isset($request['name'])){
            $response = ['error' => "Missing params"];
        } else {
            if (isset($_POST['root_path']) and $_POST['root_path'] == 'media') {
                $image = new Images(IMAGECOMPRESSSQUEEZ_CONTENT_BASE . '/uploads');
            } else {
                $image = new Images();
            }
            $response = $image->deleteImages(sanitize_text_field($request['name']), $this->getStateIfSet($settings, 'replace_origin_images'));
        }
        $this->jsonResponse($response);
    }

    public function createImageSitemapXml(){
        $PATH = IMAGECOMPRESSSQUEEZ_PLUGIN_DIR . '/sitemap.xml';
        $URL = content_url() . '/' . 'plugins/' . IMAGECOMPRESSSQUEEZ_PLUGIN_BASENAME . '/sitemap.xml';
        echo esc_html($URL);
        die;
        $posts = get_posts();
        $XML = '<?xml version="1.0" encoding="UTF-8"?>
    <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';

        foreach ($posts as $post) {
            $args = array(
                'post_type' => 'attachment',
                'ID' => $post->ID,
            );
            $XML .= '
<url>
    <loc>' . $post->guid . '</loc>';
            foreach (get_children($args) as $child) {
                $XML .= '
        <image:image>
            <image:loc><![CDATA[' . $child->guid . ']]></image:loc>
            <image:caption><![CDATA[' . $child->post_name . ']]></image:caption>
        </image:image>';
            }
            $XML .= '
    </url>';
        }
        $XML .= '
</urlset>';

        file_put_contents($PATH, $XML);

        echo esc_html(IMAGECOMPRESSSQUEEZ_PLUGIN_DIR . '/sitemap.xml');
        die;
    }

    public function getFolderTree(){
        $response = [];
        $path = sanitize_text_field($_REQUEST['path']);
        if(!empty($path)){
            $image = new Images();
            $response = $image->getTreeFolder($path);
        }
        $this->jsonResponse($response);
    }

    public function jsonResponse($data)
    {
        $result = $this->connction->getSettings('status');
        if((isset($result['status']) and $result['status'] == 'A')){
            header('Content-Type: application/json');
            echo json_encode($data);
            die;
        } else {
            $data = ['error' => "Plugin disable"];
        }
    }

    public function getStateIfSet($settings, $key, $ifSet = 1, $ifNotSet = 0) {
        if(isset($settings[$key]) and $settings[$key] == 'A') {
            return $ifSet;
        } else {
            return $ifNotSet;
        }
    }

    public function getIfSet($settings, $key, $returnNotSet = null ) {
        if(isset($settings[$key])) {
            return $settings[$key];
        } else {
            return $returnNotSet;
        }
    }
}