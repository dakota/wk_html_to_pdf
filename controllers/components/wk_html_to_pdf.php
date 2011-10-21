<?php
	class WkHtmlToPdfComponent extends Object {
		
		/**
		 * @brief initialise the WkHtmlToPdf Component
		 * 
		 * If there is an extention "pdf" in the url automatically set WkHtmlToPdf 
		 * as the View class
		 * 
		 * @access public
		 * 
		 * @param type $controller 
		 * 
		 * @return void
		 */
		public function initialize(&$controller) {
			if ($controller->RequestHandler->ext == 'pdf') {
				$controller->view = 'WkHtmlToPdf.WkHtmlToPdf';
			}
		}
	}
