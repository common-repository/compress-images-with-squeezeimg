<?php

namespace images_compress_squeezeimg_cdn;

use ImagecompresssqueezeimgDataBase\ImagecompresssqueezeimgDb as connect;

class images_cnd
{
    public $connection;
    public $settings;

    public function __construct()
    {
        $this->connection = new connect();

        $this->settings = $this->connection->getSettings();
    }

    public function compressImage()
    {
        $result = 0;

        $images = $this->connection->getCdnNotCompressed();

        if (!empty($images)) {
            foreach ($images as $image) {
                if ($this->compressOneImage($image)) {
                    $this->connection->updateCompressedCDN($image);
                    $result++;
                }
            }
        }
        echo $result;
        exit();
    }

    public function purgeSDN()
    {
        $result = $this->connection->purgeSDNImages();

        return $result;
    }

    public function getNewImgs()
    {
        $rootDir = ABSPATH;

        $files = $this->scanDirectories($rootDir . 'wp-content');

        $result = $this->connection->inserNewImages($files);

        return $result;
    }

    private function scanDirectories($rootDir)
    {
        $files = [];

        $directories = new \RecursiveDirectoryIterator($rootDir);
        $iterator = new \RecursiveIteratorIterator($directories);


        foreach ($iterator as $file) {
            if ($file->isFile() && $this->isImageFile($file)) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function isImageFile($file)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file);
        finfo_close($finfo);

        $acceptedMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/avif',
            'image/x-jp2',
            'image/tiff',
            'image/webp'
        ];

        return in_array($mimeType, $acceptedMimeTypes);
    }

    public function compressOneImage($image)
    {
        $mime = mime_content_type($image->path);
        $file_name = basename($image->path);
        $file_path = curl_file_create($image->path, $mime, $file_name);

        $domain = $this->settings['domain_name'];
        $newFilename = md5($image->path);
        $api_key = $this->settings['squeeze_cdn_token'];

        $url = "https://api.squeezeimg.com/module/cdn/upload?newFilename=$newFilename&domain=$domain";
        $headers = array('Authorization' => $api_key);

        $body = array('file' => $file_path);

        $args = array(
            'headers' => $headers,
            'body' => $body,
            'timeout' => 60,
            'redirection' => 5,
            'httpversion' => '1.1',
            'blocking' => true,
            'method' => 'POST',
        );

        $response = wp_remote_post($url, $args);
        $status = true;

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            echo "Something went wrong: $error_message";
            $status = false;
        } else {
            $result = wp_remote_retrieve_body($response);
            $resultArray = json_decode($result, true);
            if (isset($resultArray['success']) && !$resultArray['success']) {
                $status = false;
            }
        }

        return $status;
    }

    public function getRemoteFileInfo($url)
    {
        $response = wp_remote_head($url);

        if (is_wp_error($response)) {
            return [
                'fileExists' => false,
                'fileSize' => 0,
                'error' => $response->get_error_message(),
            ];
        }

        $httpResponseCode = wp_remote_retrieve_response_code($response);
        $fileSize = isset($response['headers']['content-length']) ? (int) $response['headers']['content-length'] : 0;

        return [
            'fileExists' => $httpResponseCode === 200,
            'fileSize' => $fileSize
        ];
    }

    public function createCdnImagePath($hash, $two)
    {
        $images = $this->connection->getCdnNotCompressed();

        if (!empty($images)) {
            foreach ($images as $image) {
                if ($image->path != $two) {
                    return $two;
                }
            }
        }

        $newFilename = md5(rtrim(ABSPATH, '/') . $two);
        $mime = explode('.', basename($two))[1];

        if ($mime == 'jpg') {
            $mime = 'jpeg';
        }

        $url = 'https://cnd-squeezeimg.nyc3.digitaloceanspaces.com/' . $this->settings['domain_name'] . '/compressed/' . $newFilename . '.' . $mime;

        if ($this->settings['convert_images_to_webp_format'] == 'A') {
            $mime = 'webp';

            $url = 'https://cnd-squeezeimg.nyc3.digitaloceanspaces.com/' . $this->settings['domain_name'] . '/webp/' . $newFilename . '.' . $mime;
        }

        return $url;
    }
}