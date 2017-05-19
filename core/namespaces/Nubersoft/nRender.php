<?php
namespace Nubersoft;

class nRender extends \Nubersoft\nApp
	{
		private	$page,
				$data,
				$nTemplate;
				
		private	static	$settings,
						$config,
						$page_settings;
		
		public	function __construct()
			{
				$this->nTemplate	=	$this->getHelper('nTemplate');
				
				if(empty(self::$page_settings))
					$this->getHeadPrefs();
				
				return parent::__construct();
			}
		
		public	function getTemplateByType($type,$content = false)
			{
				return $this->nTemplate->{"get{$type}"}($content);
			}
		
		public	function getFrontEnd($content=false)
			{
				return $this->toSingleDs(NBR_ROOT_DIR.DS.$this->getTemplateByType('FrontEnd',$content));
			}
			
		public	function getBackEnd($content=false)
			{
				return $this->toSingleDs(NBR_ROOT_DIR.DS.$this->getTemplateByType('BackEnd',$content));
			}
		
		public	function getCss()
			{
				return NBR_ROOT_DIR.DS.$this->nTemplate->getTemplateFrom('css');
			}
		
		public	function getJs()
			{
				return NBR_ROOT_DIR.DS.$this->nTemplate->getTemplateFrom('js');
			}
		/*
		**	@description	Core tool to fetch a file from the current template directory
		*/
		public	function getTemplatePlugin()
			{
				$opts	=	func_get_args();
				$dir	=	(!empty($opts[0]))? $opts[0] : false;
				$append	=	(!empty($opts[1]))? $opts[1] : false;
				$path	=	$this->toSingleDs(NBR_ROOT_DIR.DS.$this->nTemplate->getTemplateFrom('plugins'.DS.$dir,$append));
				
				return $path;
			}
		
		public	function templatePluginExists($dir,$append = 'index.php')
			{
				return is_file($this->toSingleDs($this->getTemplatePlugin($dir,$append)));
			}
		/*
		**	@description	Fetches the plugin from the current template folder
		**					/client/template/{$current}/plugins/{$dir}/{$append}
		*/
		public	function useTemplatePlugin($dir,$append = 'index.php')
			{
				$path	=	$this->toSingleDs($this->getTemplatePlugin($dir,$append));
				return $this->render($path);
			}
		/*
		**	@description	Same function as useTemplatePlugin() only the path is found in
		**					/client/template/plugins/{$dir}/{$append}
		*/
		public	function useGlobalPlugin($dir,$append = 'index.php')
			{
				$path	=	$this->toSingleDs(NBR_CLIENT_DIR.DS.'template'.DS.'plugins'.DS.$dir.$append);
				return $this->render($path);
			}
		
		public	function error404($error404,$message = false)
			{
				if(is_file($error404))
					return $this->render($error404,$message);
				elseif(is_string($error404))
					return $error404;
			}
		
		public	function siteOffline($options)
			{
				if(!is_file($options['filename']))
					throw new \Exception('File not found.');
				
				ob_start();
				include($options['filename']);
				$data	=	ob_get_contents();
				ob_end_clean();
				
				return $data;
			}
		
		public	function renderView()
			{
				unset(NubeData::$settings->configs);
				unset(NubeData::$settings->xml_add_list);
				$template	=	$this->getTemplate();
				ob_start();
				include($template);
				$data	=	ob_get_contents();
				ob_end_clean();
				echo $data;
			}
		
		public	function getDefault()
			{
				$template	=	DS.'core'.DS.'template'.DS.'default';
				$prefs		=	$this->toArray($this->getSitePrefs());
				
				if(is_array($prefs)) {
					$prefs	=	$this->getMatchedArray(
									array('content','template_folder'),
									'_',
									$prefs
								);
				}
				
				return (!empty($prefs['template_folder'][0]))? $prefs['template_folder'][0] : $template;
			}
		
		public	function getDoc($template,$type = 'frontend')
			{
				$this->data	=	(!empty($this->data))? $this->data : false; 
				$inc		=	($type == 'frontend')? $this->getFrontEndPath($template) : $this->getBackEndPath($template);
				# If file is good, render
				if(is_file($inc))
					return $this->render($inc,$this->data);
				# Throw exception
				throw new \Exception('Template file not found: '.$this->Safe()->encode($inc));
			}
			
		protected	function getConfig()
			{
				if(!empty(self::$config))
					return self::$config;

				$config		=	$this->toSingleDs($this->getTemplatePath(DS.'settings'.DS.'config.xml'));
				self::$config	=	$config;
				return	$config;
			}
		/*
		**	@description	Determines if the page is to be admin
		*/
		private	function determineAdminStatus($page)
			{
				if(empty($page)) {
					if(!$this->isAdmin())
						return false;
				}
				elseif(empty($page['is_admin'])) {
					if(!$this->isAdmin())
						return false;
				}
				elseif($page['is_admin'] == 2) {
					if(!$this->isAdmin())
						return false;
				}
				
				return true;
			}
		
		public	function getTemplateBase()
			{
				if(!isset(self::$page_settings['current_base_template']))
					self::$page_settings['current_base_template']	=	pathinfo($this->getSite('template_current'),PATHINFO_DIRNAME);
					
				return	self::$page_settings['current_base_template'];
			}
		
		protected	function cacheCall($path)
			{
				if(!empty($path['state']))
					$state	=	$path['state'];
				else
					$state	=	(empty($this->getPageURI('is_admin')) || $this->getPageURI('is_admin') > 1)? 'base_view' : 'admin_view';
				
				if(!empty($path['toggled']))
					$toggled	=	$path['toggled'];
				else
					$toggled	=	(!empty($this->getDataNode('_SESSION')->toggle->edit))? 'is_toggled' : 'not_toggle';
				$post		=	$this->getPost('action');
				$get		=	$this->getGet('action');
				$usePost	=	(is_string($post))? DS.$post : '';
				$useGet		=	(is_string($get))? DS.$get : '';
				$country	=	(!empty($this->getSession('LOCALE')))? trim($this->getSession('LOCALE'),'/') : 'en';
				$base		=	(!empty($path['base']))? $path['base'] : 'prefs';
				$type		=	(!empty($path['type']))? $path['type'] : 'base';
				$ext		=	(!empty($path['ext']))? '.'.$path['ext'] : '.json';
				$cacheDir	=	$this->getCacheFolder();
				$tempBase	=	$this->getDataNode('site')->templates->template_site->dir;
				$defPath	=	(!empty($this->getPageURI('full_path')))? trim(str_replace('/',DS,$this->getPageURI('full_path')),DS) : 'static';
				$ID			=	(!empty($this->getPageURI('ID')))? $this->getPageURI('ID') : (($defPath == 'static')? 'error' : md5($defPath));
				$loggedIn	=	($this->isLoggedIn())? 'loggedin' : 'loggedout';
				$usergroup	=	(!empty($this->getSession('usergroup')))? $this->getSession('usergroup') : 'static';
				$isSsl		=	($this->isSsl())? 'https' : 'http';
				return $this->toSingleDs($cacheDir.DS.$isSsl.DS.$base.DS.$country.DS.$type.DS.$tempBase.DS.$defPath.DS.$loggedIn.$useGet.$usePost.DS.$toggled.DS.$state.DS.$usergroup.DS.$ID.$ext);
			}
		
		public	function getMediaSrc($type = 'stylesheet',$frontend = false)
			{
				# create a cache file path
				$cached	=	$this->cacheCall(array('type'=>$type));
				$skip	=	false;
				if(is_file($cached) && !$skip)
					$contents	=	$this->getJson($cached);
				else {
					$config	=	$this->getConfig();
					$found	=	array();
					# See if there is a matching config file
					if(is_file($config))
						$parseConfig	=	$this->getHelper('nRegister')->parseXmlFile($config);
					# Get the automator to process the xml
					$nAutomator	=	$this->getHelper('nAutomator',$this);
					# Extract all arrays that have a matching key
					$this->flattenArrayByKey($parseConfig,$found,$type);
					# Fetch page data
					$page		=	$this->getPageURI();
					$is_front	=	(empty($this->getPageURI('is_admin')) || $this->getPageURI('is_admin') > 1);
					# If there are arrays to process
					if(!empty($found)) {
						# Get our html helper
						$nHtml	=	$this->getHelper('nHtml');
						# Count how many arrays were found
						$count	=	count($found);
						# Loop through those arrays and process results
						for($i=0; $i < $count; $i++) {
							if(!isset($found[$i]['include'][0]))
								$found[$i]['include']	=	array($found[$i]['include']);
							foreach($found[$i]['include'] as $key => $value) {
								$version	=	
								$local		=	true;
								if(isset($value['@attributes'])) {
									$attr	=	$value['@attributes'];
									if(isset($attr['is_admin'])) {
										if($attr['is_admin'] == 'true') {
											if(!$this->determineAdminStatus($page))
												continue;
										}
										else {
											if($this->determineAdminStatus($page))
												continue;
										}
									}
									
									if(isset($attr['logged_in']) && $attr['logged_in'] == 'true') {
										if(!$this->isLoggedIn())
											continue;
									}
									
									if(isset($attr['is_local']) && $attr['is_local'] == 'false')
										$local		=	false;
										
									if(isset($attr['version']) && $attr['version'] == 'false')
										$version	=	false;
										
									if(isset($attr['loadpage'])) {
										if($attr['loadpage'] != $this->getPageURI('full_path'))
											continue;
									}
									
									if(isset($attr['loadid'])) {
										if($attr['loadid'] != $this->getPageURI('ID'))
											continue;
									}
									
									if(isset($attr['frontend'])) {
										if($attr['frontend'] == 'true'){
											$display	=	($is_front);
										}
										else {
											$display	=	(!$is_front);
										}
										
										if(!$display)
											continue;
									}
									elseif(isset($attr['backend'])) {
										if($attr['backend'] == 'false'){
											$display	=	($is_front);
										}
										else {
											$display	=	(!$is_front);
										}
										
										if(!$display)
											continue;
									}
								
									if(isset($attr['state'])) {
										if($attr['state'] == 'offline') {
											if($this->siteLive())
												continue;
										}
									}
								
									if(isset($attr['action'])) {
										$requestMethod	=	'getPost';
										if(isset($attr['type'])) {
											switch($attr['type']) {
												case('request'):
													$requestMethod	=	'getRequest';
												case('get'):
													$requestMethod	=	'getGet';
											}
										}
										
										if($this->{$requestMethod}('action') != $attr['action']) {
											continue;
										}
									}
								}
								
								$path			=	$nAutomator->matchFunction($value['path']);

								$new[$type][]	=	($type == 'javascript')? $nHtml->javaScript($path,$local,$version) : $nHtml->styleSheet($path,$local,$version);
							}
						}
					}
					
					$contents	=	(!empty($new[$type]))? implode('',$new[$type]) : false;
					
					if(!empty($contents)) {
						$this->saveFile(json_encode($contents),$cached);
					}
				}
				
				return $contents;
			}
		
		public	function returnTemplatePath($name,$file = false,$frontend = false)
			{
				$file	=	(!empty($file))? $file : $name.'.php';
				$stored	=	$this->getStoredTemplatePath($name);
				if(!empty($stored))
					return $stored;
				
				$stored = $this->getTemplateFile($file,$frontend);
				$this->setStoredTemplatePath($name,$stored);
				return $stored;
			}
		
		public	function getStoredTemplatePath($name)
			{
				if(isset(self::$settings[$name]))
					return	self::$settings[$name];
			}
			
		public	function setStoredTemplatePath($name,$value)
			{
				if(!isset(self::$settings[$name]))
					self::$settings[$name]	=	$this->toSingleDs($value);
			}
		
		public	function getTemplateFile($filepath=false,$frontend='frontend')
			{
				$site		=	$this->getData()->getSite();
				
				if(!isset($site->default_template_dir)) {
					self::call('GetSitePrefs')->setPageRequestSettings();
					$site	=	$this->getData()->getSite();
				}
				
				$default	=	$site->default_template_dir.DS;
				$current	=	(isset($site->template))? pathinfo($site->template,PATHINFO_DIRNAME) : $default;
				$root		=	NBR_ROOT_DIR.DS;
				
				if(empty($filepath))
					return $current;
				
				$backend	=	DS.$frontend.DS.$filepath;
				
				if(is_file($file = $this->toSingleDs($root.$current.$backend)))
					return $file;
				else {
					$file = $this->toSingleDs($root.$default.$backend);
					return $file;
				}
			}
		/*
		**	@description	Renders the header
		*/
		public	function getHeader($dir='frontend',$settings = false)
			{
				return $this->setDefaultRenderTemplate($settings,'head',$dir);
			}
		
		public	function getFooter($dir='frontend',$settings = false)
			{
				return $this->setDefaultRenderTemplate($settings,'foot',$dir);
			}
		
		public	function setDefaultRenderTemplate($settings,$kind,$dir = 'frontend',$force = false)
			{
				$html	=	(!empty($settings['html']))? $settings['html'] : false;
				 
				if(!$html) {
					if(!empty($settings['link'])) {
						if(is_file($settings['link']))
							$link = $settings['link'];
					}
					
					if(empty($link)) {
						# Fetches the template front or backend
						$usePage		=	(!empty($this->getHtml('template')['is_admin']))? $this->getBackEnd($kind.'.php') : $this->getFrontEnd($kind.'.php');
						
						$root			=	NBR_ROOT_DIR.DS;
						$frontend		=	$dir.DS.$kind.'.php';
						$templatePath	=	$usePage;	
						$default		=	$this->toSingleDs($root.$this->getHtml('template')['default_template_dir'].DS.$frontend);
						$link			=	(is_file($templatePath) && !$force)? $templatePath : $default;
					}
					
					ob_start();
					include_once($link);
					$html	=	ob_get_contents();
					ob_end_clean();	
				}
				
				if($html)
					return $this->getFunction('use_markup',$html);
			}
		
		private	function processMeta($array)
			{
				$nHtml	=	$this->getHelper('nHtml');
				$Safe	=	self::call('Safe');
				foreach($array as $name => $content) {
					if(empty($content))
						continue;
					
					$new[]	=	$nHtml->getHtml('meta',array('name'=>$Safe->decode($name),'content'=>$Safe->decode($content)));
				}
				
				return (!empty($new))? $new : array();
			}
		
		public	function addHeadPrefAttr($key,$value)
			{
				self::$page_settings[$key]	=	$value;
			}
		
		public	function getHeadPrefs()
			{
				$nSafe		=	$this->getHelper('Safe');
				$template	=	$this->getSite();
				$getPrefs	=	$this->getData()->getPreferences();
				$page		=	$this->getDataNode('pageURI');
				
				if(empty($getPrefs) && empty(constant('NBR_PLATFORM')))
					$this->getHelper('CoreMySQL')->installRows('system_settings');
				
				# Incase there are no settings
				if(empty($getPrefs) && !empty(constant('NBR_PLATFORM'))) {
					self::$page_settings	=	array(
						'front'=>'',
						'back'=>'',
						'prefs'=>'',
						'template'=>'',
						'page'=>'',
						'meta'=>'',
						'title'=>'',
						'header'=>'',
						'inline_css'=>'',
						'favicons'=>'',
						'javascript'=>'',
						'js_lib'=>''
					);
					
					return $this;
				}
				
				foreach($getPrefs as $title => $obj) {
					${str_replace('settings_','',$title)}	=	$obj->content;
				}
				
				$prefs['page']		=	$page;
				$prefs['site']		=	$site;
				$prefs['header']	=	$head;
				$prefs['template']	=	(!empty($template) && !empty($template->template))? pathinfo($template->template,PATHINFO_DIRNAME) : $this->getTemplateFile();
				
				$prefs		=	$this->toArray($prefs);
				$template	=	$this->toArray($template);
				$page		=	$this->toArray($page); 
				$meta		=	false;
				if(!empty($page['page_options']['meta'])) {
					$meta	=	$this->processMeta($page['page_options']['meta']);
					$meta	=	(!empty($meta))? implode(PHP_EOL,$meta).PHP_EOL : false;
				}
				# This pulls from the default settings in site prefs
				if(empty($meta) && !empty($head->meta))
					$meta	=	$this->safe()->decode($head->meta);
				
				$page_menu	=	(isset($page['menu_name']))? $page['menu_name'] : $this->siteUrl();
				$title		=	(!empty($meta['title']))? $nSafe->decode($meta['title']) : $page_menu;
				$header		=	(!empty($prefs['header']))? $nSafe->decode($prefs['header']) : false;
				$inlineCss	=	(!empty($header['style']))? $nSafe->decode($header['style']).PHP_EOL : false;
				$favicons	=	(!empty($header['favicons']))? $nSafe->decode($header['favicons']) : false;
				$javascript	=	(!empty($header['javascript']))? $nSafe->decode($header['javascript']) : false;
				$js_lib		=	(!empty($header['javascript_lib']))? $nSafe->decode($header['javascript_lib']) : false;
				
				self::$page_settings	=	array(
					'front'=>$this->getFrontEnd(),
					'back'=>$this->getBackEnd(),
					'prefs'=>$prefs,
					'template'=>$template,
					'page'=>$page,
					'meta'=>$meta,
					'title'=>$title,
					'header'=>$header,
					'inline_css'=>$inlineCss,
					'favicons'=>$favicons,
					'javascript'=>$javascript,
					'js_lib'=>$js_lib
				);
				
				return $this;
			}
		
		public	function getHtml($key=false)
			{
				if(!empty($key))
					return (isset(self::$page_settings[$key]))? self::$page_settings[$key] : false;
				
				return self::$page_settings;
			}
		
		public	function error404Page()
			{
				$message	=	$this->toArray($this->getDataNode('error404'));
				$path		=	$this->toSingleDs($this->getDefaultTemplate().DS.'frontend'.DS.'error404.php');
				$display	=	$this->toSingleDs(NBR_ROOT_DIR.DS.$path);
				if(!is_file($display))
					throw new \Nubersoft\nException('Error template is invalid (not found ironically): '.$display);	
				ob_start();
				echo $this->getDoc('error.head.php');
?>

<body class="nbr">
<?php
				echo $this->error404($display,$message);
				echo $this->getDoc('error.foot.php');
?>
</body>
</html>
<?php
				$data	=	ob_get_contents();
				ob_end_clean();
				return $data;
			}
		
		public	function maintenancePage()
			{
				$display	=	$this->getFrontEndPath('maintenance.php');
				
				if(!is_file($display))
					throw new \Nubersoft\nException('Error template path is invalid: '.$display);
				
				ob_start();
				include($display);
				$data	=	ob_get_contents();
				ob_end_clean();
				return $data;
			}
		
		public	function isForbidden()
			{
				
			}
		
		public	function isPageNotFound()
			{
				if(empty($this->getDataNode('pageURI')))
					return	true;
				
				$page_live	=	$this->getDataNode('pageURI')->page_live;
				if($page_live != 'on')
					return true;
					
				return false;
			}
		
		public	function getViewPort($content = 'width=device-width')
			{
				$meta	=	array(
					'name'=>'viewport',
					'content'=>$content
				);
				return $this->getHelper('nHtml')->getHtml('meta',$meta).PHP_EOL;
			}
		
		public	function cacheBlock($content,$path,$raw = false)
			{
				$gPath	=	(!$raw)? $this->getCacheFolder().DS.$path : $path;
				# Create the cache file path
				$path	=	$this->toSingleDs($gPath);
				# Get cache engine
				$cache	=	$this->getHelper('BuildCache');
				# If there is a header file, just get it. If not, make it
				$cache->checkCacheFile($path)->startCaching();
				# This is what will be cached
				if($cache->allowRender())
					echo $content;
				# Stop caching, save content
				$cache->endCaching()->addContent($cache->getCached());
				# Return the output
				return $cache->renderBlock();
			}
		
		public	function cacheLayout($path,$func)
			{
				$cache	=	$this->getHelper('nCache');
				$cache->cacheBegin($path);
				if(!$cache->isCached()) {
					echo $func($this);
				}
				return $cache->cacheRender();
			}
		
		public	function renderSiteLogo($settings=false)
			{
				$nImage	=	$this->getHelper('nImage');
				$imgsrc	=	$nImage->toBase64($this->getFrontEnd('images'.DS.'default.png'));
				if(!empty($imgsrc)) {
					$file	=	$nImage->image($imgsrc,$settings,false,false);
					return $file;
				}
			}
		
		public	function renderAdminLogo($settings=false,$version = 'nubersoft.png')
			{
				$nImage	=	$this->getHelper('nImage');
				$imgsrc	=	$nImage->toBase64(NBR_MEDIA_IMAGES.DS.'logo'.DS.$version);
				if(!empty($imgsrc)) {
					$file	=	$nImage->image($imgsrc,$settings,false,false);
					return $file;
				}
			}
		
		public	function getSiteOfflineMsg($message = false)
			{
				$message	=	(!empty($message))? $message : 'Site is under maintenance';
				return (isset($this->getPrefsContent('site')->site_live->value))? $this->getPrefsContent('site')->site_live->value : $message;
			}
		
		public	function setCachePath($name,$array = false)
			{
				if(is_array($array)) {
					return implode(DS,$array).DS.$name;
				}
				
				return $this->getStandardPath(DS.$name);
			}
		
		public	function getMedia($type,$path,$options=false,$corepath=false)
			{
				$corepath	=	(!empty($corepath))? $corepath : NBR_MEDIA;
				switch($type) {
					case('image'):
						$use	=	'images';
						break;
					default:
						$use	=	$type;
				}
				
				$path	=	$this->toSingleDs($corepath.DS.$use.DS.$path);
				
				switch($type) {
					case('image'):
						$version	=	(isset($options['version']))? $options['version'] : false;
						$local		=	(isset($options['local']))? $options['local'] : false;
						
						if(isset($options['version']))
							unset($options['version']);
						if(isset($options['local']))
							unset($options['local']);
						
						$layout	=	$this->getHelper('nImage')->image($path,$options,$version,$local);
						break;
				}
				
				return trim($layout);
			}
	}