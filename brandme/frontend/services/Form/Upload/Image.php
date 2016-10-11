<?php

namespace Frontend\Services\Form\Upload;

use Phalcon\Http\Request\File;
use Phalcon\Image\Adapter\Imagick;

/**
 * Uploads and resizes images using Imagick, because GD is quite terrible
 * Class Image
 *
 * @package Frontend\Services\Form\File
 */
class Image
{

    const MAX_IMAGE_FILE_SIZE = 3.5;

    /**
     * Process file uploaded from platform.
     * Note the dimensions param should be of a higher resolution than the destination param resolution, in order to maintain image quality
     *
     * @param File $file
     * @param      $dimensions [min width, min height, output width, output height]
     * @param      $destination
     * @return array|string
     */
    public static function upload(File $file, $dimensions, $destination)
    {
        if (!in_array($file->getRealType(), ['image/gif', 'image/jpeg', 'image/png'])) {
            return [$file->getName().' is not an acceptable image format'];
        }
        //give them a tolerance of 500kb to avoid hassles
        if (($file->getSize() / 1048576) > self::MAX_IMAGE_FILE_SIZE) {
            return [$file->getName().' is larger than the maximum file size of 3 MB'];
        }
        $tmp = $file->getTempName();
        if (!filesize($tmp)) {
            return ['Please upload a valid image'];
        }
        list($x, $y) = getimagesize($tmp);
        if ($x < $dimensions[0] || $y < $dimensions[1]) {
            return ['Please upload an image with a higher resolution'];
        }
        switch ($file->getRealType()) {
            case 'image/png':
                $ext = '.png';
                break;
            case 'image/gif':
                $ext = '.gif';
                break;
            case 'image/jpeg':
                $ext = '.jpg';
                break;
        }

        $path = $destination.'/'.sha1(uniqid(time(), true)).$ext;

        $image = new Imagick($tmp);
        /**
         * resize the image so that the destined output ratio is met reasonably
         */
        if ($x > $y) {
            //wider than height
            $width = $image->getWidth() * ($dimensions[3] / $image->getHeight());
            $image->resize($width, $dimensions[3]);
            $offsetX = ($width - $dimensions[2]) / 2;
            $offsetY = 0;
        } else {
            //taller than width
            $height = $image->getHeight() * ($dimensions[2] / $image->getWidth());
            $image->resize($dimensions[2], $height);
            $offsetX = 0;
            $offsetY = ($height - $dimensions[3]) / 2;
        }
        $image
            ->crop($dimensions[2], $dimensions[3], $offsetX, $offsetY)
            ->save(APPLICATION_PUBLIC_DIR.$path, 100);
        @unlink($tmp);

        return $path;
    }

    /**
     * Grabs image from a given URL
     *
     * @param $url
     * @param $dimensions
     * @param $destination
     * @return string
     */
    public static function uploadFromUrl($url, $dimensions, $destination)
    {

        $tmpImage = file_get_contents($url);
        $contentType = false;
        $contentSize = false;
        foreach ($http_response_header as $value) {
            if (preg_match('/^Content-Type:/i', $value)) {
                $contentType = preg_replace('/^Content-Type:\s/i', '', $value);
            }
            if (preg_match('/^Content-Length:/i', $value)) {
                $contentSize = preg_replace('/^Content-Length:\s/i', '', $value);
            }
        }
        if (($contentSize / 1048576) > self::MAX_IMAGE_FILE_SIZE) {
            return false;
        }
        if (!in_array($contentType, ['image/gif', 'image/jpeg', 'image/png'])) {
            return false;
        }
        $nameTmp = tempnam(sys_get_temp_dir(), 'brandme-');
        file_put_contents($nameTmp, $tmpImage);

        list($x, $y) = getimagesize($nameTmp);
        if ($x < $dimensions[0] || $y < $dimensions[1]) {
            return false;
        }
        switch ($contentType) {
            case 'image/png':
                $ext = '.png';
                break;
            case 'image/gif':
                $ext = '.gif';
                break;
            case 'image/jpeg':
                $ext = '.jpg';
                break;
        }
        $path = $destination.'/'.sha1(uniqid(time(), true)).$ext;
        $image = new Imagick($nameTmp);
        /**
         * resize the image so that the destined output ratio is met reasonably
         */
        if ($x > $y) {
            //wider than height
            $width = $image->getWidth() * ($dimensions[3] / $image->getHeight());
            $image->resize($width, $dimensions[3]);
            $offsetX = ($width - $dimensions[2]) / 2;
            $offsetY = 0;
        } else {
            //taller than width
            $height = $image->getHeight() * ($dimensions[2] / $image->getWidth());
            $image->resize($dimensions[2], $height);
            $offsetX = 0;
            $offsetY = ($height - $dimensions[3]) / 2;
        }
        $image
            ->crop($dimensions[2], $dimensions[3], $offsetX, $offsetY)
            ->save(APPLICATION_PUBLIC_DIR.$path);
        @unlink($nameTmp);

        return $path;
    }

}