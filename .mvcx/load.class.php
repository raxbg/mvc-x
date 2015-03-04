<?php

class Load {
	private $app;
	public $session;
	public $request;
	private $vars = array();
	function __construct($app,$session,$request) {
		$this->app = $app;
		$this->session = $session;
		$this->request = $request;
	}
	public function __set($index, $value) {
		$this->vars[$index] = $value;
	}
	public function setvars($vars) {
		$this->vars = $vars;
	}
	
	private function debug($path) {
		$debug_info = '';
		if ($this->app->debug_mode  == 1) {
			$debug_info .= '<code style="padding:5px 20px; margin:0;border-top:2px dashed #c7254e; display:block;">';
			$debug_info .= '<h3>DEBUG</h3>';

		
			$debug_info .= '<table class="table" style="color:#222"><thead><tr><th colspan="2">Database Queries</th></tr></thead>';        
			foreach (db::$log as $k=> $lg) {
				$debug_info .= '<tr><td>'.($k+1)."\t".$lg['time'].' microseconds'." \t".$lg['query'].'</td></tr>';
			}  
			$debug_info .= '</table>';        
			$debug_info .= '<table class="table" style="color:#222"><thead><tr><th>Page Lifecycle</th></tr></thead>';        
			$debug_info .= "<tr><td>Loaded view:</td><td>".$path.'</td></tr>';	
			$debug_info .= "<tr><td>Loaded controller:</td><td>".$this->app->router->file.'</td></tr>';            
			$debug_info .= '</table>';        
			$debug_info .= '</code>';
		}
		return $debug_info;
	}
	
	function model($name) {
		$path = $this->app->router->path.DS.'model'.DS.$name.'.php';
		if (!file_exists($path)) {
			throw new Exception('Model not found in '. $path);
			return false;			
		}
		include $path;
		$themodel = new $name($this->app);
		$this->app->router->controllerObject->$name = $themodel;
		$this->app->router->modelObject = $themodel;
	}
	
	function view($name,$echo=true,$smart_elements=true) {
		$x = '';
		$template = '';
		if ($this->app->template !== false && !empty($this->app->template)) {
			$template = DS . 'template' . DS . $this->app->template;
		}

		$xconfig = array();
		foreach ($this->app->router->extensions as $ext_dir) {
			include $ext_dir.DS.'xconfig.php';
			foreach ($xconfig as $xc) {
				if (!empty($xc['override_views']) && $xc['override_views'] == true) {
					$trytpl = $ext_dir.DS.'view' . DS . $name . '.tpl';
					if (file_exists($trytpl)) {
						$path = $trytpl;
						break 2;
					}
				}
			}
		}
		
		if (empty($path) || !file_exists($path)) {
			$path = $this->app->router->path . DS . 'view' . $template . DS . $name . '.tpl';
		}
		
		if (!file_exists($path)) {
			$path = SITE_PATH . DS . 'app'. DS .$this->app->dir. DS .'view'. $template . DS . $name . '.tpl';
			if (!file_exists($path)) {
				$path = SITE_PATH . DS .'.mvcx'. DS .'mvc'. DS .'view'. $template . DS . $name . '.tpl';
			}
		}
		
		// If the view is not found in the specified template, try without template
		if (!file_exists($path) && !empty($template)) {
			$path = $this->app->router->path . DS . 'view' . DS . $name . '.tpl';
			if (!file_exists($path)) {
				$path = SITE_PATH . DS . 'app'. DS .$this->app->dir. DS .'view' . DS . $name . '.tpl';
				if (!file_exists($path)) {
					$path = SITE_PATH . DS .'.mvcx'. DS .'mvc'. DS .'view'. DS . $name . '.tpl';
				}
			}
		}
		
		if (file_exists($path) == false) {
			throw new Exception('Template not found in '. $path);
			return false;
		}
		
		if (!isset($_BASEHREF)) { 
			$protocol = stripos($_SERVER['SERVER_PROTOCOL'],'https') === true ? 'https://' : 'http://';
			$_BASEHREF = $protocol.$this->app->url.'/';
		}
		$_DEBUG = '';
		if (!empty($this->app->debug_mode)) {
			$_DEBUG = $this->debug($path);
		}
		$_CONTROLLER = $this->app->router->controller;
		$_ACTION = $this->app->router->action;
		$_APPPATH = $this->app->router->path;
		$_BODYCLASS = $_CONTROLLER . ' ' . $_ACTION;
		$_TEMPLATE = $this->app->template;
		extract($this->vars);
		if ($this->app->smart_elements == true && $smart_elements == true) {
			
			ob_start();
			include $path;
			$contents = ob_get_contents();
			ob_end_clean();
			
			$content = $contents;
			//$content = file_get_contents($path); 
			
			/* First level lookup for tags */
			preg_match_all("/\[[^\]]*\]/", $content, $matches);
			if (!empty($matches[0])) {

				foreach ($matches[0] as $match) {
					$m = explode(':',trim($match,'[]'));
					if (count($m) < 2) continue;
					
					if (stripos($m[1],'=') !== false) {
						$phpcode = trim(substr($m[1],stripos($m[1],' ')));
						$m[1] = trim(str_replace($phpcode,'',$m[1]));
						$phpcode = '$element_data = array(\''.str_replace(array('=',' ','=>',','),array('=>',',','\'=>\'','\',\''),$phpcode).'\');';
						eval($phpcode);
							
					}
					$viewcontent = $this->view('element'.DS.$m[0].DS.$m[1],false,false);
					$content = str_replace($match,$viewcontent,$content);
					
				}
				
				/* Second level lookup for tags */
				preg_match_all("/\[[^\]]*\]/", $content, $matches);
				if (!empty($matches[0])) {
	
					foreach ($matches[0] as $match) {
						$m = explode(':',trim($match,'[]'));
						if (count($m) < 2) continue;
						$viewcontent = $this->view('element'.DS.$m[0].DS.$m[1],false,false);
						$content = str_replace($match,$viewcontent,$content);
						
					}
				}
					
				$temp_file = tempnam(sys_get_temp_dir(), 'mvc');
				file_put_contents($temp_file,$content);
				include $temp_file;
				unlink($temp_file);
				return;
			}
		} 
		if ($echo == true) {
			include ($path); 
		} else {
			return file_get_contents($path);	
		}

	}
 
}