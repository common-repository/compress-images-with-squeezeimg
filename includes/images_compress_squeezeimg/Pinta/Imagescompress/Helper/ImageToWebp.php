<?php


namespace Pinta\Components\Imagescompress\Helper;


class ImageToWebp
{

    private $source = null;

    protected function getImageResource()
    {
        // Find the extension of source image.
        $extension = strtolower(strrchr($this->source, '.'));
        // Convert image to resource object according to its type.
        try {
            switch ($extension) {
                case '.jpg':
                case '.jpeg':
                    $img = @imagecreatefromjpeg($this->source);
                    if ($img) {
                        imagepalettetotruecolor($img);
                    } else {
                        $img = false;
                    }
                    break;
                case '.png':
                    $img = @imagecreatefrompng($this->source);
                    imagepalettetotruecolor($img);
                    imagealphablending($img, true);
                    imagesavealpha($img, true);
                    break;
                default:
                    $img = false;
                    break;
            }
        } catch (\Exception $e) {
            $img = false;

        }
        return $img;
    }

    public function convert($source, $destination, $quality = 80)
    {
        // Set default values globally
        $this->source = $source;
        // Convert to webp, yey
        try {
            $source2 = $this->getImageResource();
            if ($source2) {
                imagewebp($source2, $destination, $quality);
            }
        } catch (\Exception $e) {

        }
    }

}
