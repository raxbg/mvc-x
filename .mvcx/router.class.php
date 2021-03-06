<?php

class Router extends Base {
	public $path;
	public $args = array();
	public $x = false;
	public $file;
	public $controller;
	public $controllerObject;
	public $modelObject;
	public $action;
	public $extensions = array();
	
	function __construct($registry) {
        parent::__construct($registry);
	}


	function setPath($path) {
		/*** check if path is a directory ***/
		if (is_dir($path) == false) {
			throw new Exception ('Invalid controller path: `' . $path . '`');
		}
		/*** set the path ***/
		$this->path = $path;
	}
	
	public function redirect($url) {
			header('Location: '.$url);
	}
	
	public function getCurrentUrl($back='') {
		$protocol = '//';
		$host = returnine($_SERVER['HTTP_HOST']);
		$uri = returnine($_SERVER['REQUEST_URI']);
		if (!empty($_SERVER['SERVER_PROTOCOL'])) {
			$protocol = $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://';
		}
		$url = "$protocol$host$uri";
		if ($back == '..') {
			$url = substr($url,0,strrpos($url,'/'));
		}
		if ($back == '../..') {
			$url = substr($url,0,strrpos($url,'/'));
			$url = substr($url,0,strrpos($url,'/'));
		}
		return $url;
	}
	
	public function setAutoPersist($controller) {

		$persistAction = returnine($controller->autoPersist['action'], $this->action);
		
		if ($persistAction == 'index') {
			$data = $this->modelObject->getAll();
			$this->controllerObject->set('persistence',$data);
		}
		if (!empty($this->args[0]) && $persistAction == 'view') {
			$data = $this->modelObject->getAllById($this->args[0]);
			$data = (isset($data[0])) ? $data[0] : array();
			$this->controllerObject->set($data);
			$this->controllerObject->set('persistence',$data);
		}
		if (!empty($this->args[0]) && $persistAction == 'edit') {
			$redirect = returnine($controller->autoPersist['flash']['redirect'],$this->getCurrentUrl('../..'));
			if ($controller->autoPersist['validate'] !== true) {
				if(!empty($controller->autoPersist['validate']['ifempty'])) {
					$msg = $controller->autoPersist['flash']['ifempty'];
				}
				$persistVars = returnine($_POST,false);
				$this->session->flashNotification($msg,'danger',$redirect,$persistVars);
				return;
			}
			$data = $this->modelObject->getAllById($this->args[0]);
			$data = (isset($data[0])) ? $data[0] : array();
			$this->controllerObject->set($data);
			$this->controllerObject->set('persistence',$data);
			if ($this->controllerObject->parentEdit($this->args[0])) {
				$data = $this->modelObject->getAllById($this->args[0]);
				$data = (isset($data[0])) ? $data[0] : array();
				$this->session->flashNotification($controller->autoPersist['flash']['success'],'success',$redirect,$data);
				return;
			}
		}
		if ($persistAction == 'add') {			
			if (!empty($_POST) && $controller->autoPersist['validate'] == true && ($controller->validate() !== true)) {
				$msg = $controller->autoPersist['flash']['ifempty'];
				$persistVars = returnine($_POST,false);
				$this->controllerObject->set('persistence',$_POST);
				$this->session->flashNotification($msg,'danger',null,$persistVars);
				return;
			}
			$redirect = returnine($controller->autoPersist['flash']['redirect'],$this->getCurrentUrl('..'));
			if ($this->controllerObject->parentAdd()) {
				$persistVars = returnine($_POST,false);
				$this->session->flashNotification($controller->autoPersist['flash']['success'],'success',$redirect,$persistVars);
				return;
			}

		}
		if (!empty($this->args[0]) && $persistAction == 'delete') {			
			$redirect = returnine($controller->autoPersist['flash']['redirect'],$this->getCurrentUrl('../..'));
			if ($this->controllerObject->parentDelete($this->args[0])) {
				$persistVars = array('id'=>$this->args[0]);
				$this->session->flashNotification($controller->autoPersist['flash']['delete'],'success',$redirect,$persistVars);
				return;
			}

		}

	}

	public function loader() {
        try {
            $this->getController();
        } catch (Exception $e) {
            echo $e->getMessage();
            exit;
        }

		if (db::table_exists($this->controller)) {
            try {
                $m = $this->load->model($this->controller);
                $this->modelObject = $m;
            } catch (ModelNotFoundException $e) {
                $this->log->debug('ModelNotFound', $e->getMessage(), NOTICE_DEBUG_GROUP);
            }
		}
		
		require_once $this->file;
		$class = ucfirst($this->controller).'Controller';
		$controller = new $class($this->registry);
		$this->controllerObject = $controller;

		if (is_callable(array($controller, $this->action)) == false) {
			$action = 'index';
		} else {
			$action = $this->action;
		}
		
		if (isset($this->request->data['hafur'])) {
			$controller->hafur();
			return;	
		}
		
		if (count($this->args) == 0) {
			$controller->$action();
		}
		if (count($this->args) == 1) {
			$controller->$action($this->args[0]);
		}
		if (count($this->args) == 2) {
			$controller->$action($this->args[0],$this->args[1]);
		}
		if (count($this->args) > 2) {
			call_user_func_array(array($controller,$action),$this->args);
		}
		
		if ($controller->autoPersist !== false) {
			$this->setAutoPersist($controller);
		}
		if ($controller->autoRender == true) {
			try {
				$this->app->load->view($this->controller.DS.$this->action);
			} catch (ViewNotFoundException $e) {
				echo $e->getMessage(); exit;
			}
		}
	}
	
	private function getExtensions() {
        if (!$this->extensions) {
            $dir = SITE_PATH.DS.'app'.DS.$this->app->dir.DS.DIRNAME_X;
            $extension_paths = array();
            if (file_exists($dir) && is_dir($dir)) {
                foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $filename) {
                    if (basename($filename) == 'xconfig.php') {
                        $extension_paths[] = dirname($filename);
                    }
                }
            }
            $this->extensions = $extension_paths;
        }
		return $this->extensions;
	}

	private function getController() {
		$route = empty($_GET['rt']) ? '' : $_GET['rt'];
		$route = trim(str_replace($this->app->uri,'',$route),'/');
		if (empty($route)) {
			$route = 'page/index';
		}

		$parts = explode('/', $route);
		if ($parts[0] == 'x') {
			if (isset($parts[1])) {
				$this->x = $parts[1];
				if (isset($parts[2])) {
					array_shift($parts);
				}
				array_shift($parts);
			}
		}
		$this->controller = $parts[0];
		
		if(isset($parts[1])) {
			$this->action = $parts[1];
		}
		
		if(isset($parts[2])) {
			for ($i = 2; $i < count($parts); $i++ ) {
				$this->args[($i-2)] = $parts[$i];	
			}
		}

	
		if (empty($this->controller)) {
			$this->controller = 'page';
		}
	

		if (empty($this->action)) {
			$this->action = 'index';
		}

		
		// graceful loading degradation X > APP > MVC core
		
		$tryorder = array(
			'extensions' => array(),
			'app' => SITE_PATH.DS.'app'.DS.$this->app->dir,
			'mvc' => SITE_PATH.DS.'.mvcx'.DS.'mvc'
		);

		$tryorder['extensions'] = $this->getExtensions($tryorder['extensions']);

		$filepath = '';
		foreach ($tryorder as $try) {
			if (is_array($try)) {
				if ($this->x !== false) {
					$trypath = SITE_PATH.DS.'app'.DS.$this->app->dir.DS.DIRNAME_X.DS.$this->x.DS.'controller'.DS.$this->controller.'.php';
					
					if (is_file($trypath) && is_readable($trypath)) {
						if (stripos(file_get_contents($trypath),'extends xcontroller') !== false) {
							$filepath = $trypath;
							$this->setPath(SITE_PATH.DS.'app'.DS.$this->app->dir.DS.DIRNAME_X.DS.$this->x);
							break;
						}
					} 	
				} else {
					foreach ($try as $x) {
						$trypath = $x . DS . 'controller'. DS . $this->controller . '.php';
						if (is_file($trypath) && is_readable($trypath) == true) {
							if (stripos(file_get_contents($trypath),'extends controller') !== false) {
								$filepath = $trypath;
								$this->setPath($x);
								break 2;
							}
	
						} 
					}
				}
			} else {
				$trypath = $try . DS . 'controller'. DS . $this->controller . '.php';
				
				if (is_file($trypath) && is_readable($trypath) == true) {
					$filepath = $trypath;
					$this->setPath($try);
					break;
				} 
			}
		}
		
		if (!empty($filepath)) {
			$this->file = $filepath;
		} else {
			// Show error page
			foreach ($tryorder as $type => $try) {
				if ($type == 'extensions') {
					continue;	
				}
				$trypath = $try . DS . 'controller'. DS . 'error.php';
				
				if (is_file($trypath) && is_readable($trypath) == true) {
					$filepath = $trypath;
					break;
				} 

			}
			if (!empty($filepath)) {
				$this->file = $filepath;
				$this->controller = 'error';
				$this->action = 'notfound';
			} else {
				throw new ControllerNotFoundException ('Error with unspecified page.');
			}
		}
	}

}

class ControllerNotFoundException extends Exception {}
