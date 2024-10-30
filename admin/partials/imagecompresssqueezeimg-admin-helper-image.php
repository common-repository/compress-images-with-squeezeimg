<?php

    namespace ImageHelperAllInOne;

    require_once plugin_dir_path( IMAGECOMPRESSSQUEEZ_PLUGIN_FILE ) . 'includes/images_compress_squeezeimg/images.php';

    use images_compress_squeezeimg\images as Images;

    class adminHelperImages{

        public $countAllImage;

        private $images;

        private $connection;

        private $types;

        public function __construct($connection, $path = null)
        {
            $this->images = new Images($path);
            $this->connection = $connection;
            $this->settings = $this->connection->getSettings();
            $this->countAllImage = $this->images->getAllImageOrigin(true);
            $this->types = ['jpg', 'webp', 'jp2', 'avif'];
        }

        public function countAllImageConvert() {
            $countAllImageConvert = [];

            $this->isSetAndIn($this->countAllImage, 0, 'style="display:none"', '');
            foreach ($this->types as $format){
                $countAllImageConvert[$format] = $this->images->getAllImageConvert($format, true, $squeezeimg_origin_replace);
            }
        }

        public function getAllImageTypes() {
            $countAllImageConvert = [];
            $result = $this->connection->getSettings('replace_origin_images');
            $squeezeimg_origin_replace = (bool)$this->isSetAndInArray($result, 'replace_origin_images');

            foreach ($this->types as $format) {
                $countAllImageConvert[$format] = $this->images->getAllImageConvert($format, true, $squeezeimg_origin_replace);
            }

            return $countAllImageConvert;
        }

        public function getTree(){
            return $this->images->getTreeFolder();
        }

        public function getSetting($key) {
            //api_token
            //count_send_images_in_request
            return $this->getIfSet($this->settings, $key);
        }

        private function isSetAndInArray($array, $key, $searc = 'A', $_true = 1, $_false = 0) {
            if(isset($array[$key]) and $array[$key] == $searc){
                return $_true;
            } else {
                return $_false;
            }
        }

        private function getIfSet($array, $key){
            if (isset($array[$key])) {
                return $array[$key];
            } else {
                return null;
            }
        }

        private function isSetAndIn($target, $value, $_true, $_false){
            if($target == $value) {
                return $_true;
            } else {
                return $_false;
            }
        }

        public function getCronOptions() {
            $output = [];
            foreach (wp_get_schedules() as $key => $schedul){
                $output[] = ['val' => $key, 'text' => $schedul['display']];
            }
            return $output;
            //var_dump('<pre>', wp_get_schedules());
            //var_dump('<pre>', get_option( 'cron' ));
            //die;
        }
    }