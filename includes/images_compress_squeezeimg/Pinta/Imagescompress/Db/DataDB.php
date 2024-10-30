<?php

namespace Pinta\Components\Imagescompress\DB;

use Pinta\Components\Imagescompress\DB\MySQLi;

class DataDB
{
    /*
     *     $db_array = [
     *  "db_host"=> "localhost",
     *   "db_name"=> "...",
     *   "db_user"=> "...",
     *   "db_password"=> "...",
     *   "database_backend"=> "mysqli",
     *   "table_prefix"=> "...",
     *  ];
     */
    protected $db;

    protected $dir_root;

    protected $table_prefix;

    protected $table_name;

    private $types = ['jpg', 'webp', 'jp2', 'avif', 'replace','ignore'];

    public function __construct($dir_root, $db_array)
    {
        $this->dir_root = $dir_root;
        if (!empty($db_array)) {
            $this->db = new MySQLi($db_array['db_host'], $db_array['db_user'], $db_array['db_password'], $db_array['db_name'], $db_array['db_host']);
            $this->table_prefix = $db_array['table_prefix'];
            $this->table_name = $this->table_prefix . 'image_compress_squeezeimg_replace';
        }
    }

    public function check()
    {
        $query = $this->db->query("SHOW TABLES LIKE '%" . $this->table_name . "%'; ");
        if ($query->num_rows > 0) {
            return true;
        } else {
            $query = $this->db->query("CREATE TABLE IF NOT EXISTS " . $this->table_name . " ( 
                `id` INT NOT NULL AUTO_INCREMENT , 
                `path` VARCHAR(255) NOT NULL , 
                `jpg` BOOLEAN NOT NULL DEFAULT FALSE ,
                `webp` BOOLEAN NOT NULL DEFAULT FALSE ,
                `jp2` BOOLEAN NOT NULL DEFAULT FALSE ,
                `avif` BOOLEAN NOT NULL DEFAULT FALSE ,
                `replace` BOOLEAN NOT NULL DEFAULT FALSE ,
                `ignore` BOOLEAN NOT NULL DEFAULT FALSE ,
                 PRIMARY KEY (`id`), UNIQUE (`path`)) ENGINE = MyISAM CHARSET=utf8 COLLATE utf8_general_ci");
            return $query;

        }
    }

    /**
     * Get array all images in database or all images for type
     *
     * @param string $type
     * @return array
     */
    public function all($type = '', $folder = '',$limit = 0, $convert = true)
    {
        $sql = "SELECT * FROM " . $this->table_name;
        $where = ' WHERE  1=1 ';
        $where .= " AND `ignore` IS FALSE";
        $where .= " AND `path` LIKE '%" . $this->dir_root . "%'";
        if (!empty($type)) {
            if ($this->validateType($type)) {
                throw  new \Exception('not correct types');
            }
            $where .= " AND `" . $type . "` IS ";
            if($convert){
                $where .= "TRUE ";
            } else {
                $where .= "FALSE ";
            }

        }
        if (!empty($folder)) {
            $dir = preg_replace('/\/\//', '/', $this->dir_root . "/" . $folder);
            $where .= " AND `path` LIKE '%" . $dir . "%'";
        }
        if ($limit > 0) {
            $where .= " LIMIT ".$limit;
        }

        $images = $this->db->query($sql . $where);

        $result = [];

        if (!empty($images->rows)) {
            foreach ($images->rows as $row) {
                $result[] = $row['path'];
            }
        }

        return $result;
    }

    /**
     * Check file is replace or not
     *
     * @param string $file
     * @return bool
     */
    public function isNotReplace($file)
    {
        $images = $this->db->query("SELECT `path` FROM " . $this->table_name . " WHERE `replace` IS TRUE AND `path`='" . $this->db->escape($file) . "'");

        if (!empty($images->row)) {
            return false;
        }

        return true;
    }

    /**
     * Insert or Update file and status in database
     *
     * @param string $file
     * @param string $type
     * @param int $compress 0 or 1
     * @return bool
     */
    public function update($file, $type = 'jpg', $compress = 0)
    {
        if ($this->validateType($type)) {
            throw  new \Exception('not correct types');
        }
        try {
            $query = $this->db->query("INSERT INTO " . $this->table_name . " 
                                    (`path`, `" . $type . "`) 
                                    VALUES ('" . $this->db->escape($file) . "', " . $compress . ") 
                                    ON DUPLICATE
                                     KEY UPDATE 
                                    `path`= VALUES(`path`),
                                    `" . $type . "`= VALUES(`" . $type . "`)");
            return $query;
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }

    /**
     * Removed compresed images and update status in database
     *
     * @param string $type
     * @return bool
     */
    public function remove($type = 'jpg')
    {
        $dir = preg_replace('/\/\//', '/', $this->dir_root);
        $where = " WHERE `path` LIKE '%" . $dir . "%'";

        if ($this->validateType($type)) {
            throw  new Exception('not correct types');
        }
        try {
            $query = $this->db->query("UPDATE " . $this->table_name . "  SET `" . $type . "` = 0 ".$where);
            return $query;
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }

    /**
     * Validate string $type must in array $this->types
     *
     * @param string $type
     * @return bool
     */
    private function validateType($type)
    {

        return !in_array($type, $this->types);
    }

}