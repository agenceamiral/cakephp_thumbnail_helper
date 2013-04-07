<?php 
/**
* ThumbnailHelper for CakePHP 
* http://agenceamiral.com/labs/cakephp_thumbnail
*
* Copyright (c) 2011 Amiral Agence Web <info@agenceamiral.com>
*
* Licensed under the MIT license.
* http://agenceamiral.com/labs/MIT-license.txt
*
* --------------------------------------------------------------------
* @package CakePHP
* @subpackage Helpers
*
* @author Gabriel Boucher <gabriel@agenceamiral.com>
* @author Eric Forgues <eric@agenceamiral.com>
* @version v2.0.3
*
*/
class ThumbnailHelper extends Helper {
	
	function get($complete_path, $options = array()) {
		if (!$complete_path) {
			return null;
		}
		if (substr($complete_path, 0, 4) == 'http') {
			return $complete_path;
		}
		if(substr($complete_path, 0, 1) == '/') {
			$complete_path = urldecode(substr($complete_path, 1));
		}
		//******* options and default values *******
		$newWidth = null;
		$newHeight = null;
		$maxWidth = null;
		$maxHeight = null;
		$size = null;
		$transform = 'scale';
		$bgcolor = "000000";
		$cache = WWW_ROOT.dirname($complete_path).'/thumbnails/';
		//*************************
		$quality = 100;
		if (!empty($options)) {
			foreach($options as $option=>$value) {
				
				switch($option) {
					case 'width':
						$newWidth = $value;
						break;
					case 'height':
						$newHeight = $value;
						break;
					case 'maxWidth':
						$maxWidth = $value;
						break;
					case 'maxHeight':
						$maxHeight = $value;
						break;
					case 'size':
						$size = $value;
						break;
					case 'transform':
						$transform = $value;
						break;
					case 'bgcolor':
						$bgcolor = $value;
						break;
					case 'cache':
						if(substr($value, -1) == '/'){
							$cache = WWW_ROOT.$value;
						}
						else {
							$cache = WWW_ROOT.$value.'/';
						}
						break;

				}				
			}
		}	
		
		if ($newWidth == null && $newHeight == null) {
			$newWidth = $newHeight = $size;
		}
		
		// create the thumbnail destination path
		if ($cache) {
			
			if (!is_writeable($cache)) {
				if (!mkdir($cache)) {
					debug("You must set either a cache folder or temporal folder for image processing. And the folder has to be writable.");
					if (strlen($cache))	{
						debug("Cache Folder \"".$cache."\" has permissions ".substr(sprintf('%o', fileperms($cache)), -4));
						debug("Please run \"chmod 777 $cache\"");
					}
					exit();
				}
			}
			
			$split = explode('.', $complete_path);
			$filename = null;
			for($i=0;$i<(count($split)-1);$i++) {
				$filename .= $split[$i];
			}
			$filenameopt = '';
			if($transform == 'frame' || ($transform == 'crop' && !$size)) {
				$filenameopt = $newWidth . 'x' . $newHeight.'-'.$bgcolor;
			}
			elseif($maxHeight != null && $maxWidth != null) {
				$filenameopt = $transform.'_max-'.$maxWidth.'x'.$maxHeight;
			}
			else {
				$filenameopt = $transform.'-'.$size;
			}		
			$filename = $filename . '-' . $filenameopt. '.' . $split[(count($split)-1)];
			$dest = $cache . basename($filename);
		}
		
		// flush the cached image
		if(isset($options['flush']) && $options['flush']===true && file_exists($dest)) {	
			unlink($dest);
		}
		if(!($cache && file_exists($dest))) {
			list($oldWidth, $oldHeight, $type) = getimagesize(urldecode($complete_path)); 
			$ext = $this->_image_type_to_extension($type);
			
			if ($transform == 'frame') {
				$temp_images = $this->_frame($complete_path, $ext, $newWidth, $newHeight, $oldWidth, $oldHeight, $bgcolor);
			}
			else if ($transform == 'scale') {
				
				
				if (($newHeight != null || $newWidth != null)) {
					$size = array();
				}
				
				if ($newHeight != null) {
					$size['height'] = $newHeight;
				}
				
				if ($newWidth != null) {
					$size['width'] = $newWidth;
				}
				
				if ($maxWidth != null && $maxHeight != null) {
					$size = array();
					$size['maxWidth'] = $maxWidth;
					$size['maxHeight'] = $maxHeight;
				}
				
				$temp_images = $this->_scale($complete_path, $ext, $size);
			}
			else if ($transform == 'crop') {
				if ($newWidth && $newHeight) {
					$size = array('width'=>$newWidth, 'height'=>$newHeight);
				}
				$temp_images = $this->_crop($complete_path, $ext, $size);
			}		
			
			switch($ext) {
				case 'gif' :
					imagegif($temp_images['new'], $dest, $quality);
					break;
				case 'png' :
					$quality = ($quality > 1)? floor((($quality-1) / 10)):0;
					imagepng($temp_images['new'], $dest, $quality);
					break;
				case 'jpg' :
					imagejpeg($temp_images['new'], $dest, $quality);
					break;
				case 'jpeg' :
					imagejpeg($temp_images['new'], $dest, $quality);
					break;
				default :
					return false;
					break;
			}
			
			foreach ($temp_images as $tmpimg) {
				@imagedestroy($tmpimg);
			}
		}
		
		return '/'.substr($dest, strlen(WWW_ROOT));
	}

	function _frame($img, $ext, $newWidth, $newHeight, $oldWidth, $oldHeight, $bgcolor) {
		
		if ($newWidth OR $newHeight) {
	
				if(($newWidth > $oldWidth) && ($newHeight > $oldHeight)) 
				{
					$applyWidth = $oldWidth;
					$applyHeight = $oldHeight;
				} 
				else
				{
					if(($newWidth/$newHeight) < ($oldWidth/$oldHeight)) 
					{
						$applyHeight = $newWidth*$oldHeight/$oldWidth;
						$applyWidth = $newWidth;
					} 
					else
					{
						$applyWidth = $newHeight*$oldWidth/$oldHeight;
						$applyHeight = $newHeight;
					}
				}

				switch($ext) {
					case 'jpeg' :
					case 'jpg' :
						$oldImage = imagecreatefromjpeg($img);
						$newImage = imagecreatetruecolor($newWidth, $newHeight);
						break;
					case 'gif' :
						$oldImage = imagecreatefromgif($img);
						$newImage = imagecreate($newWidth, $newHeight);
						break;
					case 'png' :
						$oldImage = imagecreatefrompng($img);
						$newImage = imagecreatetruecolor($newWidth, $newHeight);
			            imagealphablending($newImage,FALSE);
						imagesavealpha($newImage,TRUE);
						break;
					default :
						return false;
						break;
				}

				sscanf($bgcolor, "%2x%2x%2x", $red, $green, $blue);
				$newColor = ImageColorAllocate($newImage, $red, $green, $blue);
				
				$width = round(($newWidth-$applyWidth)/2);
				$height = round(($newHeight-$applyHeight)/2);
				$applyWidth = round($applyWidth);
				$applyHeight = round($applyHeight);
				
				imagefill($newImage,0,0,$newColor);
				imagecopyresampled($newImage, $oldImage, $width, $height, 0, 0, $applyWidth, $applyHeight, $oldWidth, $oldHeight);
				
				return array('new'=>$newImage, 'old'=>$oldImage);
		}
	}
	
	function _crop($img, $ext, $new_size) {
      	switch($ext){
          	case "jpeg":
          	case "jpg":
            	$img_src = ImageCreateFromjpeg($img);
           		break;
           	case "gif":
            	$img_src = imagecreatefromgif($img);
           		break;
            case "png":
            	$img_src = imagecreatefrompng($img);
           		break;
      	}

      	$width = imagesx($img_src);
      	$height = imagesy($img_src);

		if (is_array($new_size)) {
			$new_width = $new_size['width'];
	      	$new_height = $new_size['height'];
		}
		else {
			$new_width = $new_height = $new_size;
		}
		if($new_height/$new_width > 1 && $new_height/$new_width < 1) {
			$ratio = $new_width/$width;
			$witdh_ratio = $width*$ratio;
			$height_ratio = $height*$ratio;
			if($height_ratio < $new_height) {
				$height_ratio = $height_ratio*($new_height/$height_ratio);
				$witdh_ratio = $witdh_ratio*($new_height/$height_ratio);
			}
		}
		else {
			$ratio = $new_height/$height;
			$witdh_ratio = $width*$ratio;
			$height_ratio = $height*$ratio;
			if($witdh_ratio < $new_width) {
				$height_ratio = $height_ratio*($new_width/$witdh_ratio);
				$witdh_ratio = $witdh_ratio*($new_width/$witdh_ratio);
			}	
		}
		
		$crop_y = ($height_ratio - $new_height)/2;
		$crop_x = ($witdh_ratio - $new_width)/2;		
		
		
		$resampled = imagecreatetruecolor(round($witdh_ratio), round($height_ratio));
		$cropped = imagecreatetruecolor($new_width, $new_height);
		if ($ext == "png") {
            imagealphablending($cropped,FALSE);
			imagesavealpha($cropped,TRUE);
            imagealphablending($resampled,FALSE);
			imagesavealpha($resampled,TRUE);
		}
		
		$witdh_ratio = round($witdh_ratio);
		$height_ratio = round($height_ratio);
		$crop_x = round($crop_x);
		$crop_y = round($crop_y);
		
		//– Resample
		imagecopyresampled($resampled, $img_src, 0, 0, 0, 0, $witdh_ratio, $height_ratio, $width, $height);
		//– Crop
		imagecopy($cropped, $resampled, 0, 0, $crop_x, $crop_y, $new_width, $new_height);
			
		return array('new'=>$cropped, 'old1'=>$resampled, 'old2'=>$img_src);
  }

	function _scale($img, $ext, $size)    {
		switch($ext) {
		    case "jpeg":
		    case "jpg":
		    $img_src = ImageCreateFromjpeg ($img);
		    break;
		    case "gif":
		    $img_src = imagecreatefromgif ($img);
		    break;
		    case "png":
		    $img_src = imagecreatefrompng ($img);
		    break;
		}

		$true_width = imagesx($img_src);
		$true_height = imagesy($img_src);

		if (is_array($size)) {

			if (isset($size['width']) && isset($size['height'])) {
				
				$width=$size['width'];
				$height=$size['height'];
				
			} elseif (isset($size['height'])) {
				
				$height=$size['height'];
				$width = ($height/$true_height)*$true_width;
				
			} elseif (isset($size['width'])) {
				
				$width=$size['width'];
				$height = ($width/$true_width)*$true_height;
				
			} elseif(isset($size['maxWidth'])) {
				if($true_width >= $true_height) {
					$width = $size['maxWidth'];
					$height = $true_height/$true_width*$size['maxWidth'];
					if($height > $size['maxHeight']) {
						$height = $size['maxHeight'];
						$width = $true_width/$true_height*$size['maxHeight'];
					}
				}
				else {
					$height = $size['maxHeight'];
					$width = $true_width/$true_height*$size['maxHeight'];
					if($width > $size['maxWidth']) {
						$width = $size['maxWidth'];
						$height = $true_height/$true_width*$size['maxWidth'];
					}
				}
			}
			
		}
		else {
			if ($true_width>=$true_height)
			{
			    $width=$size;
			    $height = ($width/$true_width)*$true_height;
			}
			else
			{
			    $height=$size;
			    $width = ($height/$true_height)*$true_width;
			}
		}

		
		$width = round($width);
		$height = round($height);
		$img_des = ImageCreateTrueColor($width,$height);
		if ($ext == "png") {
            imagealphablending($img_des,FALSE);
			imagesavealpha($img_des,TRUE);
		}
		imagecopyresampled ($img_des, $img_src, 0, 0, 0, 0, $width, $height, $true_width, $true_height);

		return array('new'=>$img_des, 'old'=>$img_src);
			
  }
  

	function _image_type_to_extension($imagetype) {
		if(empty($imagetype)) return false;
		switch($imagetype) {
			case IMAGETYPE_GIF    : return 'gif';
			case IMAGETYPE_JPEG    : return 'jpg';
			case IMAGETYPE_PNG    : return 'png';
			case IMAGETYPE_SWF    : return 'swf';
			case IMAGETYPE_PSD    : return 'psd';
			case IMAGETYPE_BMP    : return 'bmp';
			case IMAGETYPE_TIFF_II : return 'tiff';
			case IMAGETYPE_TIFF_MM : return 'tiff';
			case IMAGETYPE_JPC    : return 'jpc';
			case IMAGETYPE_JP2    : return 'jp2';
			case IMAGETYPE_JPX    : return 'jpf';
			case IMAGETYPE_JB2    : return 'jb2';
			case IMAGETYPE_SWC    : return 'swc';
			case IMAGETYPE_IFF    : return 'aiff';
			case IMAGETYPE_WBMP    : return 'wbmp';
			case IMAGETYPE_XBM    : return 'xbm';
			default                : return false;
		}
	}

	function grey($complete_path) {
		if(substr($complete_path, 0, 1) == '/') {
			$complete_path = substr($complete_path, 1);
		}

		$quality = 100;
		$cache = WWW_ROOT.dirname($complete_path).'/grey/';

		$split = explode('.', $complete_path);
		$filename = null;
		for($i=0;$i<(count($split)-1);$i++) {
			$filename .= $split[$i];
		}
		$filename = $filename . '-grey.' . $split[(count($split)-1)];
		$dest = $cache . basename($filename);

		if (!is_writeable($cache)) {
			if (!mkdir($cache)) {
				debug("You must set either a cache folder or temporal folder for image processing. And the folder has to be writable.");
				if (strlen($cache))	{
					debug("Cache Folder \"".$cache."\" has permissions ".substr(sprintf('%o', fileperms($cache)), -4));
					debug("Please run \"chmod 777 $cache\"");
				}
				exit();
			}
		}

		if(!($cache && file_exists($dest))) {
			list($oldWidth, $oldHeight, $type) = getimagesize($complete_path); 
			$ext = $this->_image_type_to_extension($type);

			switch($ext){
	          	case "jpeg":
	          	case "jpg":
	            	$img_src = ImageCreateFromjpeg($complete_path);
	           		break;
	           	case "gif":
	            	$img_src = imagecreatefromgif($complete_path);
	           		break;
	            case "png":
	            	$img_src = imagecreatefrompng($complete_path);
		            imagealphablending($img_src,FALSE);
					imagesavealpha($img_src,TRUE);
	           		break;
	      	}

	      	imagefilter($img_src, IMG_FILTER_GRAYSCALE);

	      	switch($ext) {
				case 'gif' :
					imagegif($img_src, $dest, $quality);
					break;
				case 'png' :
					$quality = ($quality > 1)? floor((($quality-1) / 10)):0;
					imagepng($img_src, $dest, $quality);
					break;
				case 'jpg' :
				case 'jpeg' :
					imagejpeg($img_src, $dest, $quality);
					break;
				default :
					return false;
					break;
			}

			@imagedestroy($img_src);
		}
		
		return '/'.substr($dest, strlen(WWW_ROOT));
	}

}
?>