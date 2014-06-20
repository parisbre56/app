<?php

/**
 * Script that migrates single non-xwiki file to xwiki promote data
 *
 * @ingroup Maintenance
 */

require_once(dirname( __FILE__ ) . '/../../../../maintenance/Maintenance.php');

class MigrateSingleFileToXWiki extends Maintenance {

	/**
	 * Set script options
	 */
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Migrate single promote file data';
		$this->addArg( 'srcUrl', 'Image source url' );
		$this->addArg( 'cityId', 'Destination cityId' );
		$this->addArg( 'cityLang', 'Destination lang code' );
		$this->addArg( 'imgType', 'Image type (0-main, 1-additional)' );
		$this->addArg( 'imgIndex', 'Image index (0-main, 1-9 - additional)' );
		$this->addArg( 'lastEdited', 'Last image edit date' );
		$this->addArg( 'reviewStatus', 'Image review status' );
		$this->addArg( 'reviewStart', 'Review start date' );
		$this->addArg( 'reviewEnd', 'Review end date' );
		$this->addArg( 'reviewerId', 'UserId of reviewer' );

	}

	public function execute() {
		global $wgExternalSharedDB;

		$mdb = wfGetDB( DB_MASTER, [], $wgExternalSharedDB );
		$image = [
			'city_id' => $this->getArg(1),
			'city_lang_code' => $this->getArg(2),
			'image_type' => $this->getArg(3),
			'image_index' => $this->getArg(4),
			'image_review_status' => $this->getArg(5),
			'last_edited' => $this->getArg(6),
			'review_start' => $this->getArg(7),
			'review_end' => $this->getArg(8),
			'reviewer_id' => $this->getArg(9)
		];

		$image_name = implode( '.', [$this->getArg(1), time(), uniqid()] );

		$this->uploadSingleXwikiImage($mdb, $this->getArg(0), $image, $image_name );
	}

	/**
	 * @param $update
	 * @param $db Database
	 */
	private function insertImageToXwiki( $update, $db ) {
		$result = true;
		$oldIgnore = $db->ignoreErrors( false );

		try {
			$db->insert( 'city_visualization_images_xwiki', $update );
		} catch (DBQueryError $e) {
			$result = false;
		}
		$db->ignoreErrors( $oldIgnore );

		return $result;
	}

	/**
	 * @param $db Database storing visualization images table
	 * @param $file File source file object
	 * @param $image array
	 * @param $image_name string Destination file name
	 */
	public function uploadSingleXwikiImage( $db, $source_url, $image, $image_name ) {
		echo 'File from ' . $source_url . ' is being uploaded as ' . $image_name . ' ...', PHP_EOL;

		$xwiki_file = new PromoXWikiImage($image_name);

		$image['image_name'] = $image_name;

		$uploadResult = $xwiki_file->uploadByUrl( $source_url );
		if ($uploadResult == 0) {
			echo $image_name . ' finished uploading' . PHP_EOL;
			echo $image_name . ' access url: ' . $xwiki_file->getUrl() . PHP_EOL;
			$updateResult = $this->insertImageToXwiki( $image, $db );
			if ($updateResult) {
				echo $image_name . ' stored in DB' . PHP_EOL;
			} else {
				echo $image_name . ' storing in DB failed' . PHP_EOL;
			}
		} else {
			echo $image_name . ' returned error code ' . $uploadResult . PHP_EOL;
		}
	}
}

$maintClass = 'MigrateSingleFileToXWiki';
require_once(RUN_MAINTENANCE_IF_MAIN);
