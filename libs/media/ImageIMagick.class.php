<?php

/**
 * @class ImageIMagick implements the AbstractImage interface / class. simple image
 * operations like resizing and cropping are thus standardized
 *
 * @see AbstractImage
 */
class ImageIMagick extends AbstractImage {
	private $im;
	private $target_im;

	/**
	 * GD JPEG quality parameter
	 */
	const JPEG_QUALITY = 96;

	/**
	 * initialize ImageGD's custom properties after the parent's
	 */
	public function __construct() {
		parent::__construct();

		$this->im = null;
		$this->target_im = null;
	}

	/**
	 * forces subclasses to implement a validation method
	 *
	 * @param [in] $data array $_FILES element for the image in question
	 *
	 * @returns bool
	 */
	public function validImage(array $data = null) {
		if ($data['error'] > 0)
			return false;

		if (Imagick::pingImage($data['tmp_name']))
			return true;
		else
			return false;
	}

	/**
	 * Open a GD image resource handle, set the initial width and height variables
	 *
	 * @param [in] $filename string file path
	 *
	 * @returns bool
	 */
	public function openImpl($filename) {
		$this->im = new Imagick($filename);

		if ($this->im) {
            $dim = $this->im->getSize();
			$this->width = $dim['columns']; //imagesx($this->im);
			$this->height = $dim['rows']; //imagesy($this->im);
			return true;
		}
		else {
			Log::raiseError('error opening image file: '. $filename);
			return false;
		}
	}

	/**
	 * Write the image with any existing transformations
	 *
	 * @param [in] $filename string target file path
	 *
	 * @returns bool
	 */
	public function writeImpl($filename) {
		if ($this->target_im) {
			imageinterlace($this->target_im, 1);
			if (!imagejpeg($this->target_im, $filename, self::JPEG_QUALITY))
				return false;

			imagedestroy($this->target_im);
			$this->target_im = null;
		}
		else //no transformations done at all, so just copy it instead
			$this->copy($filename);
	}

	/**
	 * Close the image, clean up
	 */
	public function close() {
		if ($this->im !== null)
			imagedestroy($this->im);
		if ($this->target_im !== null)
			imagedestroy($this->target_im);
	}

	/**
	 * Resize the image to the given dimensions
	 *
	 * @see resize()
	 *
	 * @param [in] $w int
	 * @param [in] $h int
	 *
	 * @returns bool
	 */
	protected function resizeImpl($w, $h) {
		if (!$this->target_im)
			$source_im = $this->im;
		else
			$source_im = $this->target_im;

		$this->target_im = imagecreatetruecolor($w, $h);

		return imagecopyresampled(
			$this->target_im,
			$source_im,
			0, 0,
			0, 0,
			$w, $h,
			$this->width, $this->height);
	}

	/**
	 * Crop the image to the given dimensions
	 *
	 * @see crop()
	 *
	 * @param [in] $l int - left bound; offset from origin
	 * @param [in] $t int - top bound; offset from origin
	 * @param [in] $r int - right bound; offset from origin
	 * @param [in] $b int - bottom bound; offset from origin
	 *
	 * @returns bool
	 */
	protected function cropImpl($l, $t, $r, $b) {
		if (!$this->target_im)
			$source_im = $this->im;
		else
			$source_im = $this->target_im;

		$w = $r - $l;
		$h = $b - $t;
		$this->target_im = imagecreatetruecolor($w, $h);

		return imagecopy(
			$this->target_im,
			$source_im,
			0, 0,
			$l, $t,
			$w, $h);
	}

	/**
	 * Resize and crop the image in one swoop, the gained efficiency of this method depends on the library used
	 *
	 * @see resizeCrop()
	 *
	 * @param [in] $l int - left bound; offset from origin
	 * @param [in] $t int - top bound; offset from origin
	 * @param [in] $r int - right bound; offset from origin
	 * @param [in] $b int - bottom bound; offset from origin
	 * @param [in] $w int - width of target
	 * @param [in] $h int - height of target
	 *
	 * @returns bool
	 */
	protected function resizeCropImpl($l, $t, $r, $b, $w, $h) {
		if (!$this->target_im)
			$source_im = $this->im;
		else
			$source_im = $this->target_im;

		$this->target_im = imagecreatetruecolor($w, $h);

		return imagecopyresampled(
			$this->target_im,
			$source_im,
			0, 0, //dst x,y
			$l, $t, //src x,y
			$w, $h, //dst w,h
			$r - $l, $b - $t); //src w,h
	}
}
