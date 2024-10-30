<?php


namespace Pinta\Components\Imagescompress\Extension;

use Monolog\Handler\PHPConsoleHandler;
use Pinta\Components\Imagescompress\Helper\ImageToWebp;
use Pinta\Components\Imagescompress\Extension\Compress;
use Pinta\Components\Imagescompress\DB\DataDB;

abstract class CompressExtension
{
    const JSON_DB_FILE = 'db_files_save.json';
    const JSON_DB_FILE_CACHE = 'db_files_save_cache';
    const BLOCK_REQUEST = 'block_request.txt';
    protected $extensions = array();

    protected $mimeTypeSupported = array();

    protected $root_dir;

    protected $cache_dir;

    protected $source;

    protected $db_data;


    public function __construct($root_dir, $cache_dir, $extensions = array(),$source = '', $db_array = array())
    {

        $this->root_dir = $root_dir;

        $this->cache_dir = $cache_dir;
        $this->source = $source;

        $this->extensions = $extensions;
        $this->setMimeTypes($extensions);
        if(!empty($db_array)){
            $this->db_data = new DataDB($root_dir,$db_array);
            $this->db_data->check();
        } else {
            $this->throwError("database configuration not loaded");
        }

    }

    public function hasBlock()
    {
        $file = $this->cache_dir . DIRECTORY_SEPARATOR . self::BLOCK_REQUEST;

        return file_exists($file);
    }
    public function setBlock()
    {
        $file = $this->cache_dir . DIRECTORY_SEPARATOR . self::BLOCK_REQUEST;
        file_put_contents($file,1);
    }
    public function removeBlock()
    {
        $file = $this->cache_dir . DIRECTORY_SEPARATOR . self::BLOCK_REQUEST;
        if(file_exists($file)){
            unlink($file);
        }
    }
    private function setMimeTypes($extensions)
    {
        if (empty($extensions)) {
            $this->throwError('extension empty');
        }
        foreach ($extensions as $item) {
            if (($item == 'jpg') || ($item == 'jpg')) {
                $this->mimeTypeSupported[] = 'image/jpeg';
            } else {
                $this->mimeTypeSupported[] = 'image/' . $item;
            }
        }
    }

    public function getAjaxAllImages($folders)
    {
        if (!empty($this->extensions)) {
            $formats = implode(",", $this->extensions);
            $formats2 = implode("|", $this->extensions);

        } else {
            $this->throwError('extension empty');
        }

        $pattern_origin = "/.*(?<!_compress)\.(" . $formats2 . ")$/S";
        $pattern_compress = "/.*?(_compress)\.(" . $formats2 . ")$/S";
        $origin = array();
        $compress = array();
        $webp = array();
        $jp2 = array();

        $images = [];
        foreach ($folders as $folder) {

            $pattern2 = $folder . "/*.{" . $formats . "}";
            $images2 = $this->rglob2($this->root_dir . DIRECTORY_SEPARATOR . $pattern2, GLOB_BRACE);
            $images = array_merge($images, $images2);

            $pattern3 = $folder . "/*.webp";
            $webp2 = $this->rglob2($this->root_dir . DIRECTORY_SEPARATOR . $pattern3, GLOB_BRACE);
            $webp = array_merge($webp, $webp2);

            $pattern4 = $folder . "/*.jp2";
            $jp22 = $this->rglob2($this->root_dir . DIRECTORY_SEPARATOR . $pattern4, GLOB_BRACE);
            $jp2 = array_merge($jp2, $jp22);

        }
        if (!empty($images)) {
            foreach ($images as $image) {
                if (preg_match($pattern_origin, $image)) {
                    $origin[] = $images;
                } elseif (preg_match($pattern_compress, $image)) {
                    $compress[] = $images;
                }
            }

            $result = [
                'images' => count($origin),
                'convert' => [
                    "jpg" => count($compress),
                    "webp" => count($webp),
                    "jp2" => count($jp2)
                ]
            ];
        } else {
            $result = $this->getAjaxAllImages2();
        }

        return $result;

    }

    public function getAjaxAllImages2()
    {
        if (!empty($this->extensions)) {
            $formats = implode("|", $this->extensions);
            $formats2 = implode("|", $this->extensions);
            $formats .= '|jp2';
            $formats .= '|webp';
        } else {
            $this->throwError('extension empty');
        }
        $pattern = "/.*?\.(" . $formats . ")$/S";

        $pattern_origin = "/.*(?<!_compress)\.(" . $formats2 . ")$/S";
        $pattern_compress = "/.*?(_compress)\.(" . $formats2 . ")$/S";
        $pattern_webp = "/.*?\.webp$/S";
        $pattern_jp2 = "/.*?\.jp2$/S";
        $origin = array();
        $compress = array();
        $webp = array();
        $jp2 = array();
        $images = $this->rglob($this->root_dir . DIRECTORY_SEPARATOR, $pattern);

        foreach ($images as $image) {
            if (preg_match($pattern_origin, $image)) {
                $origin[] = $images;
            } elseif (preg_match($pattern_compress, $image)) {
                $compress[] = $images;
            } elseif (preg_match($pattern_webp, $image)) {
                $webp[] = $images;
            } elseif (preg_match($pattern_jp2, $image)) {
                $jp2[] = $images;
            }
        }

        $result = [
            'images' => count($origin),
            'convert' => [
                "jpg" => count($compress),
                "webp" => count($webp),
                "jp2" => count($jp2)
            ]
        ];
        return $result;
    }

    public function updateAll()
    {
        if (!empty($this->extensions)) {
            $formats = implode("|", $this->extensions);
        } else {
            $this->throwError('extension empty');
        }
        $pattern = "/.*(?<!_compress)\.(" . $formats . ")$/S";

        $images = $this->rglob($this->root_dir , $pattern);

        foreach ($images as $image) {
            $needle = '/imagecompresssqueezeimg/admin/images';
            if (strripos($image, $needle)) { continue; }
            $this->db_data->update($image,'ignore');
        }

        return $images;
    }
    public function checkUpdateFiles()
    {
        if (!empty($this->extensions)) {
            $formats = implode("|", $this->extensions);
        } else {
            $this->throwError('extension empty');
        }
        $pattern = "/.*(?<!_compress)\.(" . $formats . ")$/S";

        $images = $this->rglob($this->root_dir . "/" , $pattern);
        $db_images = $this->db_data->all();

        foreach ($db_images as $db_images_k => $db_images_v) {
            if (!stristr($db_images_v, $this->root_dir) ) {
                unset($db_images[$db_images_k]);
            }
        }

        if(count($db_images) < count($images)){
            $this->updateAll();
        }

    }
    public function getAllImages($count = false, $folder = '',$limit = 0,$type = '')
    {
        $this->checkUpdateFiles();
        $images = $this->db_data->all($type,$folder,$limit);

        if (empty($images)) {
            $images = $this->updateAll();
        }

        foreach ($images as $key => $image) {
            if (!stristr($image, $this->root_dir) ) {
                unset($images[$key]);
            }
        }

        if ($count) {
            return count($images);
        } else {
            return $images;
        }
    }

    public function getAllConvertImages($type, $count = false, $replace = false)
    {

        if ($replace && ($type == 'jpg')) {
            $images = $this->db_data->all('replace');
        } else {
            $images = $this->db_data->all($type);
        }

        if ($count) {
            return count($images);
        } else {
            return $images;
        }

    }
    public function getAllConvertFiles($type, $count = false, $replace = false)
    {
        if (!empty($this->extensions)) {
            $formats = implode("|", $this->extensions);
        } else {
            $this->throwError('extension empty');
        }
        $data = [];
        if ($type == 'jpg') {
            $type = 'jpg';
            $pattern = '/.*\\.(' . $formats . ')_compress\.(' . $formats . ')$/';
        } elseif ($type == 'webp') {
            $pattern = '/.*\\.webp$/';
        } elseif ($type == 'jp2') {
            $pattern = '/.*\\.jp2$/';
        } elseif ($type == 'avif') {
            $pattern = '/.*\\.avif$/';
        } else {
            $this->throwError('Type is not valide line ' . __LINE__ . ' file ' . __FILE__);

        }
        if ($replace && ($type == 'jpg')) {
            $images = $this->db_data->all('replace');
        } else {
            $images = $this->rglob($this->root_dir, $pattern);
        }


        if ($count) {
            return count($images);
        } else {
            return $images;
        }

    }
    public function convert($type, $limit = 100, $quality = 80, $page = 1, $replace = false, $datas = array())
    {

        $data = array();
        $data['status'] = false;
        $data['count'] = array();
        if (!empty($type)) {
            if ($page == 1) {
                $offset = 0;
            } else {
                $offset = ($page - 1) * $limit;
            }

            if ($type == 'jpg') {
                $format = "_compress";
            } elseif ($type == 'webp') {
                $format = '.webp';
            } elseif ($type == 'jp2') {
                $format = '.jp2';
            } elseif ($type == 'avif') {
                $format = '.avif';
            }

            if ($replace and $type == 'jpg') {
                $type_db = 'replace';
            } else {
                $type_db = $type;
            }

            $images = $this->db_data->all($type_db,'', $limit, false);

            if (!empty($images)) {
                foreach ($images as $image) {
                    $check = false;
                    $original_name = $image;
                    $destination_url = null;
                    if ($type == 'jpg') {
                        $extension = pathinfo($original_name, PATHINFO_EXTENSION);
                        $destination_url = $original_name . $format . "." . $extension;

                    } else {
                        $destination_url = $original_name . $format;
                    }

                    if ($type == "jpg") {
                        if ($replace) {
                            $check = $this->db_data->isNotReplace($original_name);
                            $destination_url = $original_name;
                        } else {
                            $check = true;
                        }
                        if ($check) {
                            $result = $this->compressFileForType($original_name, $destination_url, $quality, $datas);
                        } else {
                            $result = [
                                'status' => true,
                                'error' => "The file has already been compressed "
                            ];
                        }
                    } else {
                        $result = $this->convertFile($original_name, $destination_url, $quality, $datas);
                    }
                    if (isset($result['status']) && $result['status']) {
                        if ($replace && ($type == 'jpg')) {
                            $type_db = 'replace';
                        } else {
                            $type_db = $type;
                        }

                        $this->db_data->update($original_name,$type_db,1);
                        $data['status'] = $result['status'];
                        $data['count'][] = $result;
                    } else {
                        $data['error'][] = $result;
                    }
                }
            }
            $data['page'] = $page++;
        }
        return $data;
    }

    public function rglob2($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);
        foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge($files, $this->rglob2($dir . '/' . basename($pattern), $flags));
        }

        return $files;
    }

    public function rglob($dir, $pattern)
    {

        if (!is_dir($dir)) {
            return $this->throwError('Is not directory ' . $dir);
        }
        if (substr($dir, -1, 1) != DIRECTORY_SEPARATOR) {
            $dir .= DIRECTORY_SEPARATOR;
        }

        $directory = new \RecursiveDirectoryIterator($dir);
        $ite = new \RecursiveIteratorIterator($directory);
        $files = new \RegexIterator($ite, $pattern, \RegexIterator::MATCH);
        $result = [];
        foreach ($files as $file) {
            $result[] = $file->getPathName();
        }

        return $result;
    }

    public function deleteImages($type, $replace = false)
    {
        $status = false;
        $error = 'Not working';
        $data = array();
        if (!empty($type) && !$replace) {
            try {
                $files = $this->getAllConvertFiles($type, false, $replace);

                if (!empty($files)) {
                    foreach ($files as $file) {
                        $regex = '/'.preg_quote($this->root_dir, '/').'/';
                        if (!preg_match($regex, $file)) { continue; }
                        if ($type == 'jpg') {
                            if (strpos($file, "_compress.") !== false) {
                                unlink($file);
                            }
                        } else {
                            unlink($file);
                        }
                    }
                    $status = true;
                    $error = '';
                } else {
                    $status = false;
                    $error = 'Files not found';
                }
            } catch (\Exception $e) {
                $status = false;
                $error = $e->getMessage();
            }
        }
        if($status){
            $this->db_data->remove($type);
        }
        return [
            'status' => $status,
            'error' => $error
        ];
    }


    public function getTreeFolder($folder)
    {

        $result = [];
        $dir_root = $this->root_dir;
        if (!empty($folder)) {
            $dir_root .= "/" . $folder;
        }
        $dirs = glob($dir_root . "/*", GLOB_ONLYDIR);

        foreach ($dirs as $dir) {
            $relativPath = str_replace($dir_root . "/", '', $dir);
            $result[] = $relativPath;

        }

        return $result;
    }

    public function getDefaultTreeFolder()
    {
        $folders = glob($this->root_dir . "/*", GLOB_ONLYDIR);

        return [
            'jpg' => $folders,
            'webp' => $folders,
            'jp2' => $folders,
        ];
    }

    public function getCompressLog($folder, $format, $quality, $page = 1, $limit = 1, $replace = false, $data = array())
    {
        if (empty($format) || empty($quality)) {
            return ['error' => 'Missing params'];
        }

        $response = array();

        $images = $this->db_data->all($format,$folder, 0, false);

        if (!empty($images)) {
            if ($page == 1) {
                $offset = 0;
            } else {
                $offset = ($page - 1) * $limit;
            }
            $type = '.bez_formata_vse_ploho';
            if ($format == 'jpg') {
                $type = "_compress.";
            } elseif ($format == 'webp') {
                $type = ".webp";
            } elseif ($format == 'jp2') {
                $type = ".jp2";
            } elseif ($format == 'avif') {
                $format = '.avif';
            }

            foreach ($images as $item) {
                $extension = pathinfo($item, PATHINFO_EXTENSION);
                if ($format == 'jpg') {
                    $file_name = $item . $type . $extension;
                } else {
                    $file_name = $item . $type;
                }

                if ((strpos($item, '_compress.') === false) && !file_exists($file_name)) {
                    $check = false;
                    if ($replace && ($format == 'jpg')) {
                        $check = $this->db_data->isNotReplace($item);
                    } else {
                        $check = true;
                    }

                    if ($check) {
                        $explode = explode('/', $item);
                        $response[] = [
                            'name' => end($explode),
                            'filename' => $item,
                            'size' => $this->filesize_formatted($item)
                        ];

                    }
                }
            }
        }

        return $response;
    }

    public function compressOneimg($image, $format, $quality, $replace = false, $data = array())
    {
        $response = array();

        if (file_exists($image)) {
            if ($format == 'jpg') {
                $type = "_compress.";
            } elseif ($format == 'webp') {
                $type = '.webp';
            } elseif ($format == 'jp2') {
                $type = '.jp2';
            } elseif ($format == 'avif') {
                $type = '.avif';
            }

            $extension = pathinfo($image, PATHINFO_EXTENSION);
            if ($format == 'jpg') {
                $file_name = $image . $type . $extension;
            } else {
                $file_name = $image . $type;
            }
            $check = false;
            $original_name = $image;
            $destination_url = $file_name;

            if ($format == 'jpg') {

                if ($replace) {
                    $check = $this->db_data->isNotReplace($original_name);
                    $destination_url = $original_name;
                } else {
                    $check = true;
                }
                if ($check) {
                    $result = $this->compressFileForType($original_name, $destination_url, $quality, $data);
                } else {
                    $result = [
                        'status' => true,
                        'error' => "The file has already been compressed "
                    ];
                }

            } else {
                $result = $this->convertFile($original_name, $destination_url, $quality, $data);
            }
            if (isset($result['status']) && $result['status']) {
                if ($replace && ($format == 'jpg') ) {
                    $type_db = 'replace';
                } else {
                    $type_db = $format;

                }
                $this->db_data->update($original_name,$type_db,1);
                $response['status'] = $result['status'];
                $explode = explode('/', $original_name);
                if (isset($result['size'])) {
                    $response['image']['size'] = $this->filesize_formatted('', $result['size']);
                } else {
                    $response['image']['size'] = $this->filesize_formatted($destination_url);
                }

                $response['image']['name'] = end($explode);
                $response['image']['filename'] = $destination_url;
            } else {
                $response['status'] = false;
                $explode = explode('/', $original_name);
                $response['image'] = [
                    'name' => end($explode),
                ];
            }

        } else {
            $response['error'] = "file not exists";
        }

        return $response;
    }

    public function filesize_formatted($path = '', $size_file = 0)
    {
        if (empty($path)) {
            $size = $size_file;
        } else {
            $size = filesize($path);
        }
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $power = $size > 1024 ? floor(log($size, 1024)) : null;
        if (!is_null($power)) {
            $response = number_format($size / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
        } else {
            $response = $size . ' ' . $units[0];
        }

        return $response;
    }

    public function eventUpload($image, $status_module, $quality, $replace, $webp_status, $jp2_status, $token = '')
    {
        try {
            $pathinfo = pathinfo($image);
            if (!empty($pathinfo) && in_array($pathinfo['extension'], $this->extensions)) {
                if ($status_module) {

                    if ($status_module) {
                        $extension = pathinfo($image, PATHINFO_EXTENSION);
                        $new_file = $image . "_compress" . "." . $extension;
                        if ($replace) {
                            $new_file = $image;
                        }
                        $data = $this->getApiSendSettings('jpg', $token);
                        $this->compressFileForType($image, $new_file, $quality, $data);
                    }
                    if ($webp_status) {
                        $data = $this->getApiSendSettings('webp', $token);
                        $new_file = $image . ".webp";
                        $this->convertFile($image, $new_file, $quality, $data);
                    }
                    if ($jp2_status) {
                        $data = $this->getApiSendSettings('jp2', $token);
                        $new_file = $image . ".jp2";
                        $this->convertFile($image, $new_file, $quality, $data);
                    }
                }
            }
        } catch (\Exception $e) {

        }
    }

    public function tryCompress($format, $folder = '', $quality = 60, $replace = false, $token = '')
    {
        $images = $this->getOneImages($folder);
        if(!empty($token)){
            $data = $this->getApiSendSettings($format, $token);
        } else {
            $data = '';
        }

        if (!empty($images)) {

            $image = $images[rand(0, (count($images) - 1))];
            $result = $this->compressOneimg($image, $format, $quality, $replace, $data);

            $response = [
                'origin' => $image,
                'compress' => isset($result['image']['filename'])? $result['image']['filename'] : ''
            ];

            return $response;
        }
        return ['error' => "Missing images or compress images"];

    }

    public function getOneImages($folder = '')
    {
        $images = $this->db_data->all('',$folder, 500, false);


        return $images;

    }

    public function rglobOne($dir, $pattern)
    {

        if (!is_dir($dir)) {
            return $this->throwError('Is not directory ' . $dir);
        }
        if (substr($dir, -1, 1) != DIRECTORY_SEPARATOR) {
            $dir .= DIRECTORY_SEPARATOR;
        }

        $directory = new \RecursiveDirectoryIterator($dir);
        $ite = new \RecursiveIteratorIterator($directory);
        $files = new \RegexIterator($ite, $pattern, \RegexIterator::MATCH);
        $result = [];
        foreach ($files as $file) {
            $result[] = $file->getPathName();
            if (count($result) > 500) {
                break;
            }
        }

        return $result;
    }

    public function throwError($str)
    {
        throw new \Exception($str);
    }


    public function getApiSendSettings($type, $token)
    {
        return false;
    }

    public function checkServer($link)
    {
        if (strpos($link, 'http') === false) {
            $this->throwError('Not found link. Example needed: https://test.com/image.png in ' . $link);
        }

        $response = wp_remote_get($link, array('method' => 'HEAD'));

        if (is_wp_error($response)) {
            return false;
        }

        $headers = wp_remote_retrieve_headers($response);

        $headers_array = array();
        foreach ($headers as $name => $value) {
            $headers_array[strtolower($name)] = strtolower($value);
        }

        if (isset($headers_array['server'])) {
            $server = strtolower($headers_array['server']);
            if (strpos($server, 'nginx') !== false) {
                return 'nginx';
            }
            if (strpos($server, 'apache') !== false) {
                return 'apache';
            }
            return $server;
        }

        return false;
    }

    public function getDB()
    {
        return $this->db_data;
    }
    abstract public function compressFileForType($original_name, $destination_url, $quality, $data = array());

    abstract public function convertFile($image, $destination_url, $quality, $data = array());

}