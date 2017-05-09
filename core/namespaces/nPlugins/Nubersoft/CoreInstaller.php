<?php
namespace nPlugins\Nubersoft;

class CoreInstaller extends \Nubersoft\nFileHandler
	{
		private	$DEPLOY_ROOT;
		
		public	function deployPackage(\Nubersoft\nApp $nApp)
			{
				$adminLink	=	$nApp->adminUrl('/?requestTable='.$nApp->getGet('requestTable'));
				
				if(!$nApp->isAdmin())
					return;
				
				$this->DEPLOY_ROOT	=	NBR_CLIENT_SETTINGS.DS.'deploy';
				
				if(!is_dir($this->DEPLOY_ROOT))
					return $this->doUniversalResponse($this->getDefaultMessage(false),$nApp);
				
				$contents	=	$nApp->getDirList($this->DEPLOY_ROOT);
				
				if(empty($contents['host']))
					return $this->doUniversalResponse($this->getNoPackagesMessage(false),$nApp);
				
				$zip	=	new \ZipArchive();
				
				foreach($contents['host'] as $pkg) {
					$copied	=	$this->unZipToTemp($zip,$nApp,$pkg);
					if($copied)
						$this->deleteContents($copied);
				}
				
				$nApp->getHelper('nRouter')->addRedirect($adminLink);
			}
		
		private	function unZipToTemp(\ZipArchive $zip, \Nubersoft\nApp $nApp, $path)
			{
				if($zip->open($path) !== false) {
					$ROOT	=	pathinfo($path,PATHINFO_DIRNAME);
					$zip->extractTo($ROOT);
					$zip->close();
					$newContent	=	$ROOT.DS.pathinfo($path,PATHINFO_FILENAME);
					$contents	=	$nApp->getDirList($newContent);
					
					foreach($contents['host'] as $filepath) {
						if(!is_file($filepath))
							continue;
						
						$new['from'][]	=	$filepath;
						$new['to'][]	=	$nApp->toSingleDs(NBR_ROOT_DIR.DS.str_replace($newContent ,'',$filepath));
					}
					
					$new	=	array_filter($new);
					
					if(empty($new))
						return $this->doUniversalResponse($this->getNoPackagesMessage(false),$nApp);
					
					foreach($new['to'] as $key => $filepath) {
						if(!$nApp->isDir(pathinfo($filepath,PATHINFO_DIRNAME)))
							return $this->doUniversalResponse($this->getErrorMessage(),$nApp);
						
						if(!copy($new['from'][$key],$filepath))
							return $this->doUniversalResponse($this->getErrorMessage(),$nApp);
						else
							unlink($new['from'][$key]);
					}
					
					$nApp->saveIncidental('deploy',array('msg'=>'Package installed: '.pathinfo($path,PATHINFO_FILENAME)));
					unlink($path);
					return $newContent;
				}
				
				return false;
			}
		
		private	function getDefaultMessage($type = true)
			{
				return ($type)? 'Files deployed' : 'No files to deploy';
			}
			
		private	function getNoPackagesMessage($type = true)
			{
				return ($type)? 'All packages were deployed' : 'No packages to deploy';
			}
		
		private	function getErrorMessage()
			{
				return 'There was a prodlem creating a required directory/file';
			}
		
		private	function doUniversalResponse($message,$nApp)
			{
				if($nApp->isAjaxRequest())
					$nApp->ajaxResponse(array('alert'=>$message));
				
				$nApp->saveIncidental('deploy',array('msg'=>$message));

			}
	}