<?php

namespace ImagecompresssqueezeimgDataBase;

use PHPMailer\PHPMailer\Exception;

class ImagecompresssqueezeimgDb
{
    /**
     * @var wpdb
     */
    private $db;

    private $errors;

    public function __construct()
    {
        $this->errors = [];
    }

    public function install()
    {
        global $wpdb;
        $table_name_settings = $wpdb->prefix . "imagecompress_settings";
        $table_name_cdn_img = $wpdb->prefix . "imagecompress_cdn_img";

        $charset_collate = $wpdb->get_charset_collate();

        $sql = [];

        $sql[] = "CREATE TABLE IF NOT EXISTS `$table_name_settings` (
              `id` INT(11) NOT NULL AUTO_INCREMENT,
              `_key` VARCHAR(255) NULL DEFAULT NULL,
              `_value` VARCHAR(255) NULL DEFAULT NULL,
              PRIMARY KEY (`id`)
             ) $charset_collate;";

        $sql[] = "CREATE TABLE IF NOT EXISTS `$table_name_cdn_img` (
              `id` INT(11) NOT NULL AUTO_INCREMENT,
              `path` VARCHAR(255) NULL DEFAULT NULL,
              `compressed` BOOLEAN NOT NULL DEFAULT 0,
              `size_origin` INT(11),
              `size_compess` INT(11),
              `file_path` VARCHAR(255) NULL DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `path` (`path`)
             ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        foreach ($sql as $query) {
            dbDelta($query);
        }
    }

    public function updateCdnImage($file)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "imagecompress_cdn_img";
        $filesize = @filesize($file);

        if ($filesize !== false) {
            try {
                $sql = $wpdb->prepare(
                    "INSERT INTO `$table_name` (`path`, `compressed`, `size_origin`, `size_compess`) 
                 VALUES (%s, 0, %d, 0)
                 ON DUPLICATE KEY UPDATE 
                 `size_origin` = VALUES(`size_origin`), 
                 `size_compess` = VALUES(`size_compess`)",
                    $file, $filesize
                );

                $result = $wpdb->query($sql);
                return $result;
            } catch (Exception $e) {
                return false;
            }
        }

        return false;
    }

    public function getCdnNotCompressed($limit = 10)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "imagecompress_cdn_img";

        if ($limit !== false) {
            $sql = $wpdb->prepare("SELECT * FROM $table_name WHERE compressed = 0 LIMIT %d;", $limit);
        } else {
            $sql = "SELECT * FROM $table_name WHERE compressed = 0;";
        }

        $result = $wpdb->get_results($sql);

        return $result;
    }


    public function countAllImage()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "imagecompress_cdn_img";

        $sql = "SELECT * FROM $table_name;";

        $results = $wpdb->get_results($sql);

        return $results;
    }


    public function updateCompressedCDN($image)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "imagecompress_cdn_img"; // Формируем имя таблицы с префиксом

        $sql = $wpdb->prepare("UPDATE $table_name SET compressed = 1 WHERE id = %d", $image->id);

        $result = $wpdb->query($sql);

        return $result;
    }


    public function purgeSDNImages()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "imagecompress_cdn_img";

        $sql = "DELETE FROM $table_name";

        $result = $wpdb->query($sql);

        return $result;
    }

    public function inserNewImages($files)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "imagecompress_cdn_img";
        $notFoundFiles = [];

        $results = $wpdb->get_results("SELECT path FROM $table_name");

        if (count($results) > 0) {
            foreach ($files as $file) {
                foreach ($results as $row) {

                    if ($row->path != $file) {
                        $notFoundFiles[] = $file;
                    }
                }
            }
        } else {
            $notFoundFiles = $files;
        }

        $count = 0;
        if (!empty($notFoundFiles)) {
            foreach ($notFoundFiles as $foundFile) {
                $sizeInBytes = filesize($foundFile);
                $sql = $wpdb->prepare("INSERT INTO $table_name (`path`, `size_origin`) VALUES (%s, %d)", $foundFile, $sizeInBytes);

                $results = $wpdb->query($sql);

                if ($results) {
                    $count++;
                }
            }
        }

        return $count;
    }

    public function cdnUpdateImage($size_compress, $image)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "imagecompress_cdn_img";

        $sql = $wpdb->prepare(
            "UPDATE $table_name SET size_compress = %d WHERE id = %d",
            $size_compress,
            $image->id
        );

        return $wpdb->query($sql);
    }


    public function getCdnImages($imgs)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "imagecompress_cdn_img";
        $otp = [];

        if (!empty($imgs)) {
            $placeholders = array_fill(0, count($imgs), '%s');
            $placeholders = implode(', ', $placeholders);

            $sql = $wpdb->prepare(
                "SELECT * FROM $table_name WHERE path IN ($placeholders)",
                $imgs
            );

            $results = $wpdb->get_results($sql);

            $have = [];

            if (!empty($results)) {
                foreach ($results as $imt) {
                    $otp[$imt->path] = ['compressed' => $imt->compressed, 'path' => $imt->file_path];
                    $have[] = $imt->path;
                }
            }

            foreach ($imgs as $img) {
                if (!in_array($img, $have)) {
                    $wpdb->insert(
                        $table_name,
                        [
                            'path' => $img,
                            'compressed' => 0,
                            'size_origin' => null,
                            'size_compress' => null
                        ],
                        [
                            '%s', // path
                            '%d', // compressed
                            '%d', // size_origin
                            '%d'  // size_compress
                        ]
                    );
                }
            }
        }

        return $otp;
    }

    public function uninstall()
    {
//        $this->db->query("DROP TABLE IF EXISTS `" . $this->table_prefix . "imagecompress_settings`;");
//        $this->db->query("DROP TABLE IF EXISTS `" . $this->table_prefix . "imagecompress_cdn_img`;");
    }

    public function setSettings($settings)
    {
        global $wpdb; // Declare the global $wpdb object
        $table_name = $wpdb->prefix . "imagecompress_settings";

        if (!is_array($settings) || empty($settings)) {
            return false;
        }

        $errors = [];

        foreach ($settings as $setting) {
            if (empty($setting) || !is_array($setting) || empty($setting['key']) || !isset($setting['value'])) {
                continue;
            }

            $key = $setting['key'];
            $val = $setting['value'];

            $result = $wpdb->get_results($wpdb->prepare(
                "SELECT id FROM $table_name WHERE _key = %s",
                $key
            ));

            if (empty($result)) {
                $insert_result = $wpdb->insert(
                    $table_name,
                    ['_key' => $key, '_value' => $val],
                    ['%s', '%s']
                );
                if ($insert_result === false) {
                    $errors['saving'][] = 'Error inserting ' . $key . ' : ' . $val;
                }
            } else {
                $id = $result[0]->id;
                $update_result = $wpdb->update(
                    $table_name,
                    ['_key' => $key, '_value' => $val],
                    ['id' => $id],
                    ['%s', '%s'],
                    ['%d']
                );
                if ($update_result === false) {
                    $errors['saving'][] = 'Error updating ' . $key . ' : ' . $val;
                }
            }
        }

        return $errors;
    }


    public function getSettings($key = null)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "imagecompress_settings";
        $otp = [];

        if ($key === null) {
            $result = $wpdb->get_results("SELECT * FROM $table_name");
        } else {
            $result = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_name WHERE _key = %s",
                $key
            ));
        }

        foreach ($result as $item) {
            $otp[$item->_key] = $item->_value;
        }

        return $otp;
    }
}