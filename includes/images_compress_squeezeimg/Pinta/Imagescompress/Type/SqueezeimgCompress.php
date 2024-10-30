<?php

namespace Pinta\Components\Imagescompress\Type;

use Pinta\Components\Imagescompress\Extension\CompressExtension;
use Pinta\Components\Imagescompress\Helper\ImageToWebp;

class SqueezeimgCompress extends CompressExtension
{
    public function getToken($token)
    {
        $data = ['token' => $token];
        $response = wp_remote_post('https://squeezeimg.com/api/getinfo', array(
            'method' => 'POST',
            'timeout' => 15,
            'redirection' => 5,
            'httpversion' => '1.1',
            'blocking' => true,
            'headers' => array(),
            'body' => $data,
            'cookies' => array()
        ));

        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $result = wp_remote_retrieve_body($response);
        $content_json = json_decode($result, true);

        if ($response_code != 200 || is_null($content_json)) {
            $content_json = ['error' => 'Failed to retrieve token info', 'details' => $result];
        }

        return $content_json;
    }

    public function convertFile($original_name, $destination_url, $quality,$data = array())
    {
        return $this->convertFileSqueezeimg($original_name,$destination_url,$quality,$data);
    }
    public function compressFileForType($original_name, $destination_url, $quality,$data = array())
    {
            return $this->convertFileSqueezeimg($original_name,$destination_url,$quality,$data);
    }

    public function convertFileSqueezeimg($origin, $destination_url, $quality, $data) {
        if (!$this->hasBlock()) {
            $origin = html_entity_decode($origin);
            if (file_exists($origin)) {
                if (file_exists($destination_url) && ($origin != $destination_url)) {
                    return ['status' => true, 'error' => ''];
                }
                $file_size = number_format(filesize($origin) / 1024000, 2);

                if (($file_size < 50) && (filesize($origin) > 1)) {
                    $file = $origin;
                    $mime = mime_content_type($file);
                    $info = pathinfo($file);
                    $name = $info['basename'];
                    $file_content = file_get_contents($file);  // Read file content
                    $data["file_name"] = basename($file);
                    $data["qlt"] = $quality;

                    $boundary = wp_generate_password(24, false, false);
                    $headers = [
                        'Content-Type' => 'multipart/form-data; boundary=' . $boundary
                    ];

                    $body = "--$boundary\r\n";
                    $body .= 'Content-Disposition: form-data; name="file"; filename="' . $name . "\"\r\n";
                    $body .= "Content-Type: $mime\r\n\r\n";
                    $body .= $file_content . "\r\n";

                    foreach ($data as $key => $value) {
                        $body .= "--$boundary\r\n";
                        $body .= 'Content-Disposition: form-data; name="' . $key . "\"\r\n\r\n";
                        $body .= $value . "\r\n";
                    }
                    $body .= "--$boundary--";

                    $response = wp_remote_post('https://api.squeezeimg.com/plugin', array(
                        'method' => 'POST',
                        'timeout' => 45,
                        'headers' => $headers,
                        'body' => $body,
                        'data_format' => 'body'
                    ));

                    if (is_wp_error($response)) {
                        return ['status' => false, 'error' => $response->get_error_message()];
                    }

                    $response_code = wp_remote_retrieve_response_code($response);
                    $result = wp_remote_retrieve_body($response);
                    $decoded_result = json_decode($result, true);

                    if (isset($decoded_result['error'])) {
                        if (is_array($decoded_result['error'])) {
                            //exit('Error Compress Api array');
                        } elseif (is_string($decoded_result['error'])) {
                            if (isset($decoded_result['eventObject']) && ( strpos($decoded_result['eventObject'],'tariff') !== false)) {
                                $this->setBlock();
                                return ['status' => false, 'error' => 'limit'];
                            }
                        }
                    }

                    if (strpos($decoded_result['error'], 'token') !== false) {
                        exit($result);
                    }

                    if ($response_code == 200 && !isset($decoded_result['error'])) {
                        file_put_contents($destination_url, $result);
                        return ['status' => true];
                    } else {
                        return ['status' => false, 'error' => isset($decoded_result['error']) ? $decoded_result['error'] : 'Unknown error'];
                    }
                } else {
                    return ['status' => false, 'error' => "File size is too large " . $origin];
                }
            } else {
                return ['status' => false, 'error' => "File does not exist " . $origin];
            }
        } else {
            return ['status' => false, 'error' => 'Limit reached'];
        }
    }


    public function getApiSendSettings($type,$token)
    {
        if(empty($token)){
            return false;
        }
        $response = array();
        $response['token'] = $token;
        if($type == 'jpg'){
            $response['method'] = 'compress';
        } else {
            $response['method'] = 'convert';
            $response['to'] = $type;
        }
        if(!empty($this->source)){
            $response['source'] = " Plugin: ".$this->source;
        }
        return $response;
    }
    public function cron($status, $quality, $limit = 100, $replace = false, $types = array(),$token = '')
    {
        if (empty($types)) {
            return false;
        }
        $start = microtime(true);

        if ($status) {
            foreach ($types as $type) {
                if($replace and $type == 'jpg'){
                    $type_db = 'replace';
                } else {
                    $type_db = $type;
                }

                $data = $this->getApiSendSettings($type, $token);
                $free_files = $this->db_data->all($type_db,'', $limit, false);

                if (!empty($free_files)) {
                    $free_files = array_splice($free_files, 0, ($limit / count($types)));
                    foreach ($free_files as $file) {
                        $this->compressOneimg($file, $type, $quality, $replace,$data);
                    }
                }
            }
        }
        echo esc_html('Time: ' . round(microtime(true) - $start, 4) .
            ' sek. => '.$limit. " images".PHP_EOL);
    }
}