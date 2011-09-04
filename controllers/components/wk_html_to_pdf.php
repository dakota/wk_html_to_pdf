<?php

class WkHtmlToPdfComponent extends Object {
	public function initialize(&$controller) {
		if($controller->RequestHandler->ext == 'pdf') {
			$controller->view = 'WkHtmlToPdf.WkHtmlToPdf';
		}
	}

}
