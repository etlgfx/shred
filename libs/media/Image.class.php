<?php

class Image extends AbstractDBObject {

	protected static $output_callbacks = array(
			'create_ts' => 'Date',
			'update_ts' => 'Date',
			'description' => 'FullText',
			);

	protected function getXMLNodeName() {
		return 'image';
	}

	protected function setFromArray(array $array) {
		$this->data = $array;

		return true;
	}

	public function setTags(array $array = null) {
		$this->data['tags'] = $array;
	}

	public function getUserId() {
		return (int)$this->data['user_id'];
	}

	public function getImageId() {
		return (int)$this->data['image_id'];
	}

	public function getGalleryId() {
		return (int)$this->data['gallery_id'];
	}

	protected function preProcessData() {
		$this->data['path'] = ImageAPI::generateImagePathTail($this->data['image_id'], $this->data['hash']);

		return $this->data;
	}
}
