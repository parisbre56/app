<?php

class EditHubVideosController extends WikiaController {

	public function popularVideoRow() {
		$video = $this->getVal('video');
		$this->errorMsg = $this->getVal('errorMsg');
		
		if( !empty($video) ) {
			$this->sectionNo = $video['section-no'];
			$this->videoTitle = $video['title'];
			$this->videoFullUrl = $video['fullUrl'];
			$this->videoTime = $video['videoTime'];
			$this->videoThumbnail = $video['videoThumb'];
		}
		
		//button messages
		$this->removeMsg = wfMessage('edit-hub-edithub-remove')->text();
		
		//blank image
		$this->blankImgUrl = $this->wg->BlankImgUrl;
		
		$this->response->setTemplateEngine(WikiaResponse::TEMPLATE_ENGINE_MUSTACHE);
	}
}
