<?php
App::import('Vendor', 'WkHtmlToPdf.WkHtmlToPdf');

class WkHtmlToPdfView extends View {
	protected $options = array(
		'backgroundImage' => null,
		'headerElement' => null,
		'footer' => array(
			'font-size' => 8,
			'spacing' => 5
		),
		'header' => array(),
		'orientation' => 'Portrait',
		'path' => '/tmp/',
		'mode' => 'download',
		'filename' => 'output'
	);

	public function render($action = null, $layout = null, $file = null)
	{
		//Render view and clear output buffer
		$renderedTemplate = parent::render($action, $layout, $file);
		$this->output = '';
	
		$this->Pdf = new Wkhtmltopdf($this->options);

		$this->Pdf->setHtml($renderedTemplate);
		$this->Pdf->setTitle($this->getVar('title_for_layout'));

		$this->Pdf->output($this->options['mode'], $this->options['filename'] . '.pdf');

		return false;
	}
}
