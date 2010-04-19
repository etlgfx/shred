<?php

require_once PATH_API .'AbstractMediaObject.class.php';

class ImageObject extends AbstractMediaObject {

	protected $relational_table = 'content_images';
	protected $table = 'images';
	protected $external_id = 'image_id';

}

?>
