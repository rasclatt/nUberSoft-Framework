<?php
namespace Nubersoft\nAutomator;

class Observer extends \Nubersoft\nAutomator implements \Nubersoft\nObserver
{
	use \Nubersoft\nUser\enMasse;
	
	protected	$config;
	protected	$actionName	=	'action';
	
	public	function listen()
	{
		# Normalize the config array
		$array	=	$this->normalizeWorkflowArray($this->config);
		
		$this->doWorkflow($array);
	}
	
	public	function setFlow($value, $type = 'work')
	{
		$method	=	ucfirst($type);
		
		$this->config	=	$this->{"getClient{$method}flow"}($value);
		
		if(empty($this->config)) {
			$this->config	=	$this->{"getSystem{$method}flow"}($value);
		}
		return $this;
	}
	
	public	function setWorkflow($value)
	{
		$this->setFlow($value);
		return $this;
	}
	
	public	function setBlokflow($value)
	{
		$this->setFlow($value, 'block');
		return $this;
	}
	
	public	function setActionKey($value)
	{
		$this->actionName	=	$value;
		return $this;
	}
	
	public	function runBlockflow()
	{
		$templates	=	$this->getDataNode('templates');
		$args		=	func_get_args();
		$file		=	$args[0].'.xml';
		$dir		=	'settings'.DS.'blockflows';
		$actdir		=	'settings'.DS.'actions';
		$pgpath		=	(is_dir($templates['paths']['page'])); 
		$blocks		=	[
			'page' => (!empty($templates['paths']['page']) && $pgpath)? str_replace(DS.DS,DS,$templates['paths']['page'].DS.$dir.DS.$file)  : false,
			'client' => NBR_CLIENT_SETTINGS.DS.'blockflows'.DS.$file,
			'site' => (!empty($templates['paths']['site']))? str_replace(DS.DS,DS,$templates['paths']['site'].DS.$dir.DS.$file) : false,
			'default' => NBR_SETTINGS.DS.'blockflows'.DS.$file
		];
		
		$actionStore['object']	=
		$storage['object']	=	[];
		
		$templates	=	array_filter($templates);
		
		if(!empty($this->getRequest($this->actionName))) {
			$actions	=	array_filter(array_unique([
				'site' => (!empty($templates['paths']['site']))? str_replace(DS.DS,DS,$templates['paths']['site'].DS.'core'.DS.$actdir.DS.$file) : false,
				'client' => NBR_CLIENT_SETTINGS.DS.'actions'.DS.$file,
				'page' => (!empty($templates['paths']['page']))? str_replace(DS.DS,DS,$templates['paths']['page'].DS.$actdir.DS.$file) : false,
				'default' => str_replace(DS.DS, DS, NBR_CORE.DS.$actdir.DS.$file)
			]));
			
			foreach($actions as $actObj) {
				if(empty($actObj))
					continue;
			
				if(is_file($actObj))
					$actionStore	=	$this->normalizeWorkflowArray(array_merge($actionStore['object'],$this->toArray(simplexml_load_file($actObj))));
				
				if(!empty($actionStore['object'])){
					foreach($actionStore['object'] as $acevent => $actobj) {
						if(strpos($acevent, ',') !== false) {
							$events_exp	=	array_filter(array_map('trim',explode(',',$acevent)));
							if(!in_array($this->getRequest($this->actionName), $events_exp)) {
								unset($actionStore['object'][$acevent]);
							}
						}
						else {
							if($acevent != $this->getRequest($this->actionName)) {
								unset($actionStore['object'][$acevent]);
							}
						}
					}
				}
			}
		}
		$blocks	=	array_filter(array_unique($blocks));
		foreach($blocks as $config) {
			if(empty($config))
				continue;
			
			if(is_file($config)) {
				$storage	=	$this->normalizeWorkflowArray(array_merge($storage['object'], $this->toArray(simplexml_load_file($config))));
			}
		}
		
		$obj	=	(!empty($actionStore['object']))? array_merge($actionStore['object'], $storage['object']) : $storage['object'];
		$new	=	[];
		foreach($obj as $event => $details) {
			$name	=	(strpos($event, ',') !== false)? array_filter(array_map('trim', explode(',', $event))) : $event;
			if(is_array($name)) {
				$count	=	count($name);
				for($i = 0; $i < $count; $i++) {
					if($this->getPost($this->actionName) == $name[$i] || $this->getGet($this->actionName) == $name[$i]) {
						$new[$name[$i]]			=	$details;
						$new[$name[$i]]['name']	=	$name[$i];
					}
				}
			}
			else {
				$new[$event]			=	$details;
				$new[$event]['name']	=	$name;
			}
		}
		
		$obj	=	$new;
		unset($new);
		
		usort($obj, function($a, $b) {
			if((empty($a['@attributes']['after']) && empty($b['@attributes']['after'])) && (empty($a['@attributes']['before']) && empty($b['@attributes']['before'])))
				return 1;
			
			if(!is_array($b['name']))
				$b['name']	=	[$b['name']];

			if(!empty($a['@attributes']['after']) || !empty($b['@attributes']['after'])) {
				
				if(!empty($a['@attributes']['after']))
					return	(in_array($a['@attributes']['after'], $b['name']))? 1 : -1;

				if(!empty($b['@attributes']['after']))
					return	(in_array($b['@attributes']['after'], $a['name']))? 1 : -1;
			}
			else {
				if(!empty($a['@attributes']['before']))
					return	(in_array($a['@attributes']['before'], $b['name']))? -1 : 1;

				if(!empty($b['@attributes']['before']))
					return	(in_array($b['@attributes']['before'], $a['name']))? -1 : 1;
			}
		});
		
		foreach($obj as $key => $object) {
			
			if(!is_array($object['name']))
				$object['name']	=	[$object['name']];
			
			if(!empty($object['@attributes'])) {
				$REQ	=	$object['@attributes'];
				
				if(!empty($object['@attributes']['request'])) {
					if($REQ['request'] == 'post' && !in_array($this->getPost('action'), $object['name'])) {
						unset($obj[$key]);
					}
					elseif($REQ['request'] == 'get' && !in_array($this->getGet('action'), $object['name']))  {
						unset($obj[$key]);
						continue;
					}
				}
				
				if(!empty($REQ['is_ajax'])) {
					$ajax_on	=	($REQ['is_ajax'] == 'true');
					if((!$this->isAjaxRequest() && $ajax_on) || ($this->isAjaxRequest() && !$ajax_on)) {
						unset($obj[$key]);
						continue;
					}
				}
				
				if(!empty($REQ['is_admin'])) {
					$admin_on	=	($REQ['is_admin'] == 'true');
					if((!$this->isAdmin() && $admin_on) || ($this->isAdmin() && !$admin_on)) {
						unset($obj[$key]);
						continue;
					}
				}
				
				if(!empty($REQ['is_loggedin'])) {
					$is_loggedin_on	=	($REQ['is_loggedin'] == 'true');
					if((!$this->isLoggedIn() && $is_loggedin_on) || ($this->isLoggedIn() && !$is_loggedin_on)) {
						unset($obj[$key]);
						continue;
					}
				}
			}
		}
		
		$this->doWorkflow(['object' => $obj]);
		return $this;
	}
}