<?php
namespace Nubersoft\nGlobal;

class Observer extends \Nubersoft\nGlobal implements \Nubersoft\nObserver
{
	public	function listen()
	{
		$this->createContainer(function(\Nubersoft\DataNode $DataNode){
			
			if(isset($_FILES['file']['tmp_name'])) {
				$_FILES['file']['tmp_name']	=	array_filter($_FILES['file']['tmp_name']);
				if(empty($_FILES['file']['tmp_name']))
					$_FILES	=	null;
			}
			
			foreach([
				'_ENV' => $_ENV,
				'_GET' => $_GET,
				'_POST' => $_POST,
				'_FILES' => $_FILES,
				'_REQUEST' => $_REQUEST,
				'_SERVER' => $_SERVER,
				'_SESSION' => $_SESSION,
				'_COOKIE' => $_COOKIE
			] as $key => $array) {
				$DataNode->setNode($key, $this->sanitize($array));
			}
			
		});
	}
}