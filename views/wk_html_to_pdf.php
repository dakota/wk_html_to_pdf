<?php
	class WkHtmlToPdfView extends View {
		/**
		 * @brief the default options for WkHtmlToPdf View class
		 * 
		 * @access protected
		 * @var array
		 */
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

		/**
		 * @breif execute the WkHtmlToPdf commands for rendering pdfs
		 * 
		 * @access private
		 * 
		 * @param string $cmd the command to execute
		 * @param string $input
		 * 
		 * @return string the result of running the command to generate the pdf 
		 */
		private function __exec($cmd, $input = '') {
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

		/**
		 * @brief build up parts of the command that will later be executed
		 * 
		 * @access private
		 * 
		 * @param string $commandType the part of the command to build up
		 * 
		 * @return string a part of the command for rendering pdfs 
		 */
		private function __subCommand($commandType) {
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

		/**
		 * @brief get the command to render a pdf 
		 * 
		 * @access private
		 * 
		 * @return string the command for generating the pdf
		 */
		private function __getCommand() {
			$command = $this->options['binary'];

			$command .= ($this->options['copies'] > 1) ? " --copies " . $this->options['copies'] : "";
			$command .= " --orientation " . $this->options['orientation'];
			$command .= " --page-size " . $this->options['pageSize'];
			$command .= ($this->options['toc'] === true) ? " --toc" : "";
			$command .= ($this->options['grayscale'] === true) ? " --grayscale" : "";
			$command .= ($this->options['password'] !== false) ? " --password " . $this->options['password'] : "";
			$command .= ($this->options['username'] !== false) ? " --username " . $this->options['username'] : "";
			$command .= $this->__subCommand('footer') . $this->__subCommand('header');

			$command .= ' --title "' . $this->options['title'] . '"';
			$command .= ' "%input%"';
			$command .= " -";
			
			return $command;
		}

		/**
		 * @brief render a pdf document from some html
		 * 
		 * @access private
		 * 
		 * @return the data from the rendering
		 */
		private function __renderPdf() {
			file_put_contents($this->outputFile, $this->output);

			$content = $this->__exec(str_replace('%input%', $this->outputFile, $this->__getCommand()));

			if(strpos(mb_strtolower($content['stderr']), 'error')) {
				throw new Exception("System error <pre>" . $content['stderr'] . "</pre>");
			}

			if(mb_strlen($content['stdout'], 'utf-8') === 0) {
				throw new Exception("WKHTMLTOPDF didn't return any data");
			}

			if((int)$content['return'] > 1) {
				throw new Exception("Shell error, return code: " . (int)$content['return']);
			}

			return $content['stdout'];
		}

		/**
		 * @brief public interface for setting options
		 * 
		 * This is a basic setter method for setting options from external sources
		 * 
		 * @access public
		 * 
		 * @param string $key Key to set
		 * @param mixed $value Value to set
		 * 
		 * @return nothing
		 */
		public function setOption($key, $value) {
			$this->options[$key] = $value;
		}
		
		/**
		 * @brief public interface for rendering pdfs
		 * 
		 * This is the render method that will be called by cake as per normal 
		 * view classes.
		 * 
		 * Depending on the options that are configured, WkHtmlToPdf will either
		 * offer the pdf for download, embed it directly in the browser, save the 
		 * data to disk or return the raw pdf data to the calling method.
		 * 
		 * @access public
		 * 
		 * @param string $action the action being rendered
		 * @param string $layout the layout being used
		 * @param string $file a specifc file to render
		 * 
		 * @return mixed 
		 */
		public function render($action = null, $layout = null, $file = null) {
			$renderedTemplate = parent::render($action, $layout, $file);
			$this->outputFile = TMP . rand() . '.html';		

			if(empty($this->options['title'])) {
				$this->options['title'] = $this->getVar('title_for_layout');
			}

			if(!is_executable($this->options['binary'])) {
				throw new Exception($this->options['binary'] . ' is not executable.');
			}

			if(!function_exists('proc_open')) {
				throw new Exception('Settings on the server prevent shell commands from being executed.');
			}

			$filename = $this->options['filename'];
			switch($this->options['mode']) {
				case 'download':
					if(!headers_sent()) {
						$this->output = $this->__renderPdf();
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
					} 
					
					else {
						throw new Exception("Headers already sent");
					}
					break;
					
				case 'string':
					$this->output = $this->__renderPdf();
					break;
				
				case 'embedded':
					if(!headers_sent()) {
						$this->output = $this->__renderPdf();
						header("Content-type: application/pdf");
						header("Cache-control: public, must-revalidate, max-age=0");
						header("Pragme: public");
						header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
						header("Last-Modified: " . gmdate('D, d m Y H:i:s') . " GMT");
						header("Content-Length " . mb_strlen($this->output));
						header('Content-Disposition: inline; filename="' . basename($filename) . '";');
					}
					
					else {
						throw new Exception("Headers already sent");
					}
					break;
					
				case 'save':
					file_put_contents($filename, $this->__renderPdf());
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
