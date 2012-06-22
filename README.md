Thumbnail Helper for CakePHP 2.*
=====
http://agenceamiral.com/labs/cakephp_thumbnail

ThumbnailHelper is a small CakePHP Helper library that allow the scaling, croping and framing of images files.
Thumbnails are generated at the display and are cached for better performance.


Installation
===================================

Put the ThumbnailHelper.php file in the app/View/Helper directory


Usage
===================================


<?php 

	// Add the Thumbnail helper to the helpers array at the top of your controller
	var $helpers = array('Thumbnail');

?>

<?php

	// This will return the path of the resized image
	$this->Thumbnail->get($original_image_path, array('size'=>'200', 'transform'=>'scale'));

?>

Options (general):

* transform : 'crop', 'scale', 'frame'
* cache : the path of the cache, defaults to ./thumbnails

Options (crop):

* width : Width of the thumbnail
* height : Height of the thumbnail
* size : Alias of width and height for square thumbnails

Options (scale):

* width : Width of the thumbnail
* height : Height of the thumbnail
* size : Alias of width and height for square thumbnails
* maxWidth : Maximum width (use with maxHeight)
* maxHeight : Maximum height (use with maxWidth)

Options (frame):

* width : Width of the frame
* height : Height of the frame
* bgcolor : Color of the background
