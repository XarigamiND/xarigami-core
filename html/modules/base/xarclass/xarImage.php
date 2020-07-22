<?php
/**
 * A basic class for image resizing
 *
 * @package modules
 * @copyright (C) 20010-2011 2skies.com
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @subpackage Xarigami Base module
 */

class xarImage
{

    public $image;
    public $imageType;

   public function __construct()
   {

   }

   public function getImage($fileName)
   {
        $imageInfo = getimagesize($fileName);
        $this->imageType = $imageInfo[2];
        switch ($this->imageType) {
            case IMAGETYPE_JPEG:
                $this->image = imagecreatefromjpeg($fileName);
                break;
            case IMAGETYPE_GIF:
                $this->image = imagecreatefromgif($fileName);
                break;
            case IMAGETYPE_PNG:
                 $this->image = imagecreatefrompng($fileName);
                 break;
            case IMAGETYPE_WBMP:
                 $this->image = imagecreatefromwbmp($fileName);
                 break;
            default:
                $this->image = '';
                break;
       }

    }

    public function saveImage($fileName, $imageType= IMAGETYPE_JPEG, $compression=75, $permissions=null)
    {
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                 imagejpeg($this->image,$fileName,$compression);
                break;
            case IMAGETYPE_GIF:
                 imagegif($this->image,$fileName);
                break;
            case IMAGETYPE_PNG:
                 imagepng($this->image,$fileName);
                 break;
            case IMAGETYPE_WBMP:
                  imagewbmp($this->image,$fileName);
                 break;
            }
        if( $permissions != null) {
            chmod($fileName,$permissions);
        }
    }

    public function outputImage($imageType= IMAGETYPE_JPEG)
    {

        switch ($imageType) {
            case IMAGETYPE_JPEG:
                 imagejpeg($this->image);
                break;
            case IMAGETYPE_GIF:
                imagegif($this->image);
                break;
            case IMAGETYPE_PNG:
                imagepng($this->image);
                 break;
            case IMAGETYPE_WBMP:
                  imagewbmp($this->image);
                 break;
        }
    }
    public function getWidth()
    {
        return imagesx($this->image);
    }

    public function getHeight()
    {
        return imagesy($this->image);
    }

    public function resizeByHeight($height)
    {
        $ratio = $height/$this->getHeight();
        $width = $this->getWidth() * $ratio;
        $this->_resizeImage($width,$height);
    }

    function resizeByWidth($width)
    {
        $ratio = $width/$this->getWidth();
        $height = $this->getheight() * $ratio;
        $this->_resizeImage($width,$height);
    }

    function scaleImage($scale)
    {
        $width = $this->getWidth() * $scale/100;
        $height = $this->getheight() * $scale/100;
        $this->_resizeImage($width,$height);
    }

    private function _resizeImage($width, $height)
    {
        $resizedimage = imagecreatetruecolor($width, $height);
        imagecopyresampled($resizedimage, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
        $this->image =$resizedimage;
    }
}
?>
