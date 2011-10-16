Thumbnail Helper for CakePHP
=====
http://agenceamiral.com/labs/cakephp_thumbnail

ThumbnailHelper is a small CakePHP Helper library that allow the scaling, croping and framing of images files.
Thumbnails are generated at the display and are cached for better performance.


Installation
===================================

Put the thumbnail.php file in the app/views/helpers directory


Usage
===================================


<?php 
	// Add the Thumbnail helper to the helpers array at the top of your controller
	var $helpers = array('Thumbnail');
?>

<?php
	// This will return the path of the resized image
	$thumbnail->get($original_image_path, array('size'=>'200', 'transform'=>'scale'));
?>

Options (general):

* transform : 'crop', 'scale', 'frame'
* cache : the path of the cache, defaults to ./thumbnails

Options (crop):

* width : Width of the thumbnail
* height : Height of the thumbnail
* size : Alias of width and height for square thumbnails

Options (scale):

* size : Maximum width or height

Options (frame):

* width : Width of the frame
* height : Height of the frame
* bgcolor : Color of the background
