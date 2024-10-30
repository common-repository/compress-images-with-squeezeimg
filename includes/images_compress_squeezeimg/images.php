<?php
namespace images_compress_squeezeimg;
require  __DIR__."/autoload.php";

//define('PW_IMSI_ROOT_PROJECR_DIR', ABSPATH);
//define('PW_IMSI_ROOT_PROJECR_DIR', '/home/username/workspase/www/wordpress/wp-admin/');
define('PW_IMSI_HTTPS_CATALOG', get_home_url());

class images
{

    private $dir_root;
    private $compresser;

    public function __construct($path = null)
    {

        global $wpdb;

        if ($path == null) {
            if (!defined('PW_IMSI_ROOT_PROJECR_DIR')) {
                define('PW_IMSI_ROOT_PROJECR_DIR', ABSPATH);
            }
        } else {
            if (!defined('PW_IMSI_ROOT_PROJECR_DIR')) {
                define('PW_IMSI_ROOT_PROJECR_DIR', ABSPATH . $path);
            }
        }

        $abspath = PW_IMSI_ROOT_PROJECR_DIR;
        $this->dir_root = PW_IMSI_ROOT_PROJECR_DIR;

        $this->compresser =  new \Pinta\Components\Imagescompress\Type\SqueezeimgCompress(
            $abspath,
            IMAGECOMPRESS_CACHE_DIR,
            ['jpg','jpeg','png','gif'],
            'Wordpress',
            [
                "db_host"=> "localhost",
                "db_name"=> DB_NAME,
                "db_user"=> DB_USER,
                "db_password"=> DB_PASSWORD,
                "database_backend"=> "mysqli",
                "table_prefix"=> $wpdb->prefix,
           ]
        );
    }
    /** @var int access rights of created folders (octal) */
    protected static $access_rights = 0777;

    public function getApiSendSettings($type,$token)
    {
       return $this->compresser->getApiSendSettings($type,$token);
    }

    public function compress_one_image_cdn () {

    }

    public function getToken($token)
    {
        return  $this->compresser->getToken($token);
    }
    public function convert($type,$limit ,$quality, $page ,$replace,$data = array())
    {
        return  $this->compresser->convert($type,$limit ,$quality, $page ,$replace,$data);
    }

    public  function convertFile($image, $destination_url, $quality,$data = array(), $conmressInfo = array())
    {
        $result = $this->compresser->convertFileSqueezeimg($image, $destination_url, $quality,$data);
        if (isset($result['status']) and $result['status']) {
            $connection = $this->compresser->getDB();
            $type_db = null;
            if ($conmressInfo['replace'] && ($conmressInfo['type'] == 'jpg')) {
                $type_db = 'replace';
            } else {
                $type_db = $conmressInfo['type'];
            }

            $connection->update(ABSPATH.$image,$type_db,1);
        }
        return  $result;
    }

    /**
     * Get all images origin
     * @param false $count
     * @return array|false|int
     */
    public function getAllImageOrigin($count = false, $folder = '')
    {
       return $this->compresser->getAllImages($count, $folder);
    }

    /**
     * Get all images convert
     * @param false $count
     * @return array
     */
    public function getAllImageConvert($type, $count,$replace)
    {
        return $this->compresser->getAllConvertImages($type, $count,$replace);
    }


    /**
     * Create parent folders for the image in the new filesystem.
     *
     * @return bool success
     */
    public static function createImgFolder($folder = '')
    {
        if (!file_exists(self::IMAGE_COMPRESS_FOLDER . $folder)) {
            // Apparently sometimes mkdir cannot set the rights, and sometimes chmod can't. Trying both.
            $success = @mkdir(self::IMAGE_COMPRESS_FOLDER . $folder, self::$access_rights, true);
            $chmod = @chmod(self::IMAGE_COMPRESS_FOLDER . $folder, self::$access_rights);
            // Create an index.php file in the new folder
        } else {
            $success = true;
            $chmod = true;
        }
        if ($success || $chmod) {
            return true;
        } else {
            return false;
        }
    }


    public function deleteImages($type,$replace)
    {
        return $this->compresser->deleteImages($type,$replace);
    }

    public static function setlog($data)
    {
        return @file_put_contents(self::LOG_FILE, date("Y-m-d H:i:s") . " " . $data . PHP_EOL, 8 | 2);
    }

    public function getTreeFolder($folder = '')
    {
        return $this->compresser->getTreeFolder($folder);
    }

    public function getDefaultTreeFolder()
    {
        return $this->compresser->getDefaultTreeFolder();
    }

    public function getCompressLog($folder, $format, $quality, $page = 1, $limit = 1, $replace= false)
    {
        return $this->compresser->getCompressLog($folder, $format, $quality, $page , $limit ,$replace);
    }

    public function compressOneimg($image, $format, $quality,$replace,$data = array())
    {
        return $this->compresser->compressOneimg($image, $format, $quality,$replace,$data);
    }


    public  function eventUpload($image,$status_module,$quality,$replace,$webp_status,$jp2_status,$token)
    {
        return $this->compresser->eventUpload($image,$status_module,$quality,$replace,$webp_status,$jp2_status,$token);
    }

    public function tryCompress($format, $token)
    {
        $result = array();
        $data = $this->getApiSendSettings($format,$token);
        $response =  $this->compresser->tryCompress($format, '', 60, false, $token);
        foreach ($response as $key => $image){
            $result[$key] = str_replace($this->dir_root,PW_IMSI_HTTPS_CATALOG.'/',$image);
        }
        return $result;
    }

    public function rglobBase($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);
        foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge($files, $this->rglobBase($dir . '/' . basename($pattern), $flags));
        }
        return $files;
    }

    public function cron($status,$quality,$limit,$replace,$types,$token)
    {
        $this->compresser->cron($status,$quality,$limit,$replace,$types,$token);

    }
    public function checkServer()
    {
        $images =  $this->compresser->getAllImages(false,'image');
        if(!empty($images)){
            return $this->compresser->checkServer(str_replace($this->dir_root,PW_IMSI_HTTPS_CATALOG,$images[0]));
        }

        return false;
    }
}
