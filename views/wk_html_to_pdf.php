<?php
class WkHtmlToPdfView extends View {
	protected $options = array(
		'footer' => array(),
		'header' => array(),
		'orientation' => 'Portrait',
		'pageSize' => 'A4',
		'mode' => 'download',
		'filename' => 'output',
		'binary' => '/usr/bin/wkhtmltopdf',
		'copies' => 1,
		'toc' => false,
		'grayscale' => false,
		'username' => false,
		'password' => false
	);

        private function exec($cmd, $input = "") {
                $result = array('stdout' => '', 'stderr' => '', 'return' => '');
 
		$proc = proc_open($cmd, array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes);
                fwrite($pipes[0], $input);
                fclose($pipes[0]);
 
                $result['stdout'] = stream_get_contents($pipes[1]);
                fclose($pipes[1]);
 
                $result['stderr'] = stream_get_contents($pipes[2]);
                fclose($pipes[2]);
 
                $result['return'] = proc_close($proc);
 
                return $result;
        }

	private function subCommand($commandType) {
		$data = $this->options[$commandType];
		$command = '';

		if(count($data) > 0) {
			$availableCommands = array(
				'left', 'right', 'center', 'font-name', 'html', 'line', 'spacing', 'font-size'
			);

			foreach($data as $key => $value) {
				if(in_array($key, $availableCommands)) {
					$command .= " --$commandType-$key \"$value\"";
				}
			}
		}
	
		return $command;
	}

	private function getCommand() {
		$command = $this->options['binary'];

		$command .= ($this->options['copies'] > 1) ? " --copies " . $this->options['copies'] : "";
		$command .= " --orientation " . $this->options['orientation'];
		$command .= " --page-size " . $this->options['pageSize'];
		$command .= ($this->options['toc'] === true) ? " --toc" : "";
		$command .= ($this->options['grayscale'] === true) ? " --grayscale" : "";
		$command .= ($this->options['password'] !== false) ? " --password " . $this->options['password'] : "";
		$command .= ($this->options['username'] !== false) ? " --username " . $this->options['username'] : "";
		$command .= $this->subCommand('footer') . $this->subCommand('header');

		$command .= ' --title "' . $this->options['title'] . '"';
		$command .= ' "%input%"';
		$command .= " -";
		return $command;
	}

	private function renderPdf() {
		file_put_contents($this->outputFile, $this->output);

		$content = $this->exec(str_replace('%input%', $this->outputFile, $this->getCommand()));

		if(strpos(mb_strtolower($content['stderr']), 'error'))
			throw new Exception("System error <pre>" . $content['stderr'] . "</pre>");

		if(mb_strlen($content['stdout'], 'utf-8') === 0)
			throw new Exception("WKHTMLTOPDF didn't return any data");

		if((int)$content['return'] > 1)
			throw new Exception("Shell error, return code: " . (int)$content['return']);

		return $content['stdout'];
	}

	public function render($action = null, $layout = null, $file = null) {
		$renderedTemplate = parent::render($action, $layout, $file);
		$this->outputFile = TMP . rand() . '.html';		
	
		if(empty($this->options['title'])) {
			$this->options['title'] = $this->getVar('title_for_layout');
		}


		$filename = $this->options['filename'];
		switch($this->options['mode']) {
			case 'download':
				if(!headers_sent()) {
					$this->output = $this->renderPdf();
					header("Content-Description: File Transfer");
					header("Cache-Control: public; must-revalidate, max-age=0");
					header("Pragme: public");
					header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
					header("Last-Modified: " . gmdate('D, d m Y H:i:s') . " GMT");
					header("Content-Type: application/force-download");
					header("Content-Type: application/octec-stream", false);
					header("Content-Type: application/download", false);
					header("Content-Type: application/pdf", false);
					header('Content-Disposition: attachment; filename="' . basename($filename) . '";');
					header("Content-Transfer-Encoding: binary");
					header("Content-Length " . mb_strlen($this->output));
				} else {
					throw new Exception("Headers already sent");
				}
				break;
			case 'string':
				$this->output = $this->renderPdf();
				break;
			case 'embedded':
				if(!headers_sent()) {
					$this->output = $this->renderPdf();
					header("Content-type: application/pdf");
					header("Cache-control: public, must-revalidate, max-age=0");
					header("Pragme: public");
					header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
					header("Last-Modified: " . gmdate('D, d m Y H:i:s') . " GMT");
					header("Content-Length " . mb_strlen($this->output));
					header('Content-Disposition: inline; filename="' . basename($filename) . '";');
				} else {
					throw new Exception("Headers already sent");
				}
				break;
			case 'save':
				file_put_contents($filename, $this->renderPdf());

				break;
			default:
				throw new Exception("Mode: " . $mode . " is not supported");

		}

		$filepath = $this->outputFile;
		if(!empty($filepath)) {
			unlink($filepath);
		}

		return $this->output;
	}	
}
