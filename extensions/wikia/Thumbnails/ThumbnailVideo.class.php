<?
/**
 * Media transform output for video thumbnails
 *
 * @ingroup Media
 */

class ThumbnailVideo extends ThumbnailImage {

//	function ThumbnailVideo( $file, $url, $width, $height, $path = false, $page = false ) {
//
//		$this->file = $file;
//
//		/*
//		 * Get thumbnail url
//		 */
//		$oImageServing = new ImageServing( null, $width, $height );
//		$this->url = $oImageServing->getUrl( $file, $file->getWidth(), $file->getHeight() );
//
//		# These should be integers when they get here.
//		# If not, there's a bug somewhere.  But let's at
//		# least produce valid HTML code regardless.
//		$this->width = round( $width );
//		$this->height = round( $height );
//		$this->path = $path;
//		$this->page = $page;
//	}

	// temporary hack - start
	// rewrite URLs to point to a proper video thumbnails storage during images migration
	// @author macbre
	function __construct( $file, $url, $width, $height, $path = false, $page = false ) {
		global $wgWikiaVideoImageHost;
		parent::__construct( $file, $url, $width, $height, $path, $page );

		// handle videos comming from shared repo (video.wikia.com)
		if ( !empty( $wgWikiaVideoImageHost ) && ( $file instanceof WikiaForeignDBFile ) ) {
			// replace with a proper video domain for production
			$domain = parse_url($this->url, PHP_URL_HOST);
			$this->url = str_replace("http://{$domain}/", $wgWikiaVideoImageHost, $this->url);
		}

		#var_dump(__METHOD__); var_dump($this->url);
	}
	// temporary hack - end

	function getFile( ) {
		return $this->file;
	}

	function getUrl( ) {
		return $this->url;
	}

	function getPath( ) {
		return $this->path;
	}

	function getPage( ) {
		return $this->page;
	}

	function getWidth( ) {
		return $this->width;
	}

	function getHeight( ) {
		return $this->height;
	}

	/*
	 * Render video thumbnail as image thumbnail
	 */
	function renderAsThumbnailImage( $options ) {

		$thumb = new ThumbnailImage(
				$this->getFile(),
				$this->getUrl(),
				$this->getWidth(),
				$this->getHeight(),
				$this->getPath(),
				$this->getPage()
		);

		// make sure to replace 'image' css class whith 'video' css class
		// in order to make thumbnail be handled correctly by RTE
		if ( isset( $options['img-class'] ) ) {

			$imgClasses = explode( ' ', $options['img-class'] );
			$changeIndex = array_search( 'image', $imgClasses );

			if ( $changeIndex !== false ) {
				$imgClasses[$changeIndex] = "video";
			} else {
				$imgClasses[] = "video";
			}

			$options['img-class'] = implode( ' ', $imgClasses );
		} else {

			$options['img-class'] = "video";
		}
		return $thumb->toHtml( array( 'img-class' => $options['img-class'] ) );
	}

	/**
	 * @param array $options
	 * @return mixed|string
	 * @throws MWException
	 */
	function toHtml( $options = array() ) {
		$app = F::app();

		if ( count( func_get_args() ) == 2 ) {
			throw new MWException( __METHOD__ .' called in the old style' );
		}

		// Check if the editor is requesting, if so, render image thumbnail instead
		if ( !empty( $app->wg->RTEParserEnabled ) ) {
			return $this->renderAsThumbnailImage($options);
		}

		if ( !(F::app()->checkSkin( 'wikiamobile' ) ) ) {
			$options['useTemplate'] = true;
		}

		wfProfileIn( __METHOD__ );

		// Migrate to new system which uses a template instead of this toHtml method
		if( !empty( $options['useTemplate'] ) ) {
			$html = $app->renderView( 'ThumbnailVideoController', 'thumbnail',  [
				'file' => $this->file,
				'url' => $this->url,
				'width' => $this->width,
				'height' => $this->height,
				'options' => $options,
			] );

			wfProfileOut( __METHOD__ );

			return $html;
		}

		$alt = empty( $options['alt'] ) ? '' : $options['alt'];

		/*
		 * in order to disable RDF metadata in video thumbnails
		 * pass disableRDF parameter to toHtml method
		 */
		$useRDFData = ( !empty( $options['disableRDF'] ) && $options['disableRDF'] == true ) ? false : true;


		/**
		 * Note: if title is empty and alt is not, make the title empty, don't
		 * use alt; only use alt if title is not set
		 * wikia change, Inez
		 */
		$title = !isset( $options['title'] ) ? $alt : $options['title'];

		$videoTitle = $this->file->getTitle();

		$linkAttribs = array(
			'href' => $videoTitle->getLocalURL(),
		);

		if ( !empty( $options['id'] ) ) {
			$linkAttribs['id'] = $options['id'];
		}

		if ( $useRDFData ) { // bugId: #46621
			$linkAttribs['itemprop'] = 'video';
			$linkAttribs['itemscope'] = '';
			$linkAttribs['itemtype'] = 'http://schema.org/VideoObject';
		}

		// let extension override any link attributes
		if ( isset( $options['linkAttribs'] ) && is_array( $options['linkAttribs'] ) ) {
			$linkAttribs = array_merge( $linkAttribs, $options['linkAttribs'] );
		}

		$extraClasses = 'video';
		if ( empty( $options['noLightbox'] ) ) {
			$extraClasses .= ' image lightbox';
		}
		$linkAttribs['class'] = empty( $linkAttribs['class'] ) ? $extraClasses : $linkAttribs['class'] . ' ' . $extraClasses;

		$attribs = array(
			'alt' => $alt,
			'src' => empty( $options['src'] ) ? $this->url : $options['src'] ,
			'width' => $this->width,
			'height' => $this->height,
			'data-video-name' => htmlspecialchars( $videoTitle->getText() ),
			'data-video-key' => htmlspecialchars( urlencode( $videoTitle->getDBKey() ) ),
		);

		if ( $useRDFData ) {
			$attribs['itemprop'] = 'thumbnail';
		}

        // lazy loading
		if ( !empty( $options['usePreloading'] ) ) {
			$attribs['data-src'] = $this->url;
		}

		// this is used for video thumbnails on file page history tables to insure you see the older version of a file when thumbnail is clicked.
		if ( $this->file instanceof OldLocalFile ) {
			$archive_name = $this->file->getArchiveName();
			if ( !empty( $archive_name ) ) {
				$linkAttribs['href'] .= '?t='.$this->file->getTimestamp();
				$linkAttribs['data-timestamp'] = $this->file->getTimestamp();
			}
		}

		if ( !empty( $options['valign'] ) ) {
			$attribs['style'] = "vertical-align: {$options['valign']}";
		}
		$attribs['class'] = 'Wikia-video-thumb';
		if ( !empty( $options['img-class'] ) ) {
			$attribs['class'] .= ' ' . $options['img-class'];
		}

		if ( isset( $options['imgExtraStyle'] ) ) {
			if ( !isset( $attribs['style'] ) ) {
				$attribs['style'] = '';
			}
			$attribs['style'] .= $options['imgExtraStyle'];
		}

		if ( isset( $options['duration'] ) && $options['duration'] == true ) {
			$duration = WikiaFileHelper::formatDuration( $this->file->getMetadataDuration() );
		}

		if ( isset( $options['constHeight'] ) ) {
			$this->appendHtmlCrop($linkAttribs, $options);
		}

		$html = Xml::openElement( 'a', $linkAttribs );

		if ( !empty( $duration ) ) {
			$timerProp = array( 'class'=>'timer' );
			if ( $useRDFData ) {
				$timerProp['itemprop'] = 'duration';
			}
			$html .= Xml::element( 'div', $timerProp,  $duration );
		}
		$playButtonHeight =  ( isset( $options['constHeight'] ) && $this->height > $options['constHeight'] ) ? $options['constHeight'] : $this->height;
		$html .= WikiaFileHelper::videoPlayButtonOverlay( $this->width, $playButtonHeight );
		$html .= Xml::element( 'img', $attribs, '', true );


		if ( empty( $options['hideOverlay'] ) ) {
			$html .= WikiaFileHelper::videoInfoOverlay( $this->width, $videoTitle );
		}

		$html .= ( $linkAttribs && isset( $linkAttribs['href'] ) ) ? Xml::closeElement( 'a' ) : '';

		//give extensions a chance to modify the markup
		wfRunHooks( 'ThumbnailVideoHTML', array( $options, $linkAttribs, $attribs, $this->file,  &$html ) );

		wfProfileOut( __METHOD__ );

		return $html;
	}

	private function appendHtmlCrop( &$linkAttribs, $options ) {

		if ( !isset( $linkAttribs['style'] ) ) $linkAttribs['style'] = '';

		$linkAttribs['style'] .= 'overlay:hidden;';

		if ( $this->height <= $options['constHeight'] ) {

			$linkAttribs['style'] .= "height:{$this->height}px;";
			$linkAttribs['style'] .= 'margin-bottom:'.( $options['constHeight'] - $this->height )."px;";
			$linkAttribs['style'] .= 'padding-top:'.floor( ($options['constHeight'] - $this->height)/2 )."px;";

		} else {

			$linkAttribs['style'] .= "height:{$options['constHeight']}px;";
		}
	}
}
