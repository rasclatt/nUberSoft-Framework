<?php
$DS	=	DIRECTORY_SEPARATOR;
require_once(__DIR__.$DS.'..'.$DS.'..'.$DS.'config.php');

use Nubersoft\nApp as nApp;

if(!is_admin())
	return;

$dir			=	(isset(nApp::call()->getDataNode('site')->cache_folder))? nApp::call()->getDataNode('site')->cache_folder : false;
$valid_dir		=	is_dir($dir);
$valid_start	=	$valid_dir;
$try_delete		=	false;
		
if(nApp::call()->getGet('cache')) {
	if(nApp::call()->getGet('cache') !== 'delete')
		return;
	
	if($valid_dir) {
		$try_delete	=	true;
		DeleteCache::Delete($dir,DeleteCache::ADMIN,DeleteCache::KEEP_DIR,DeleteCache::SUPRESS_ERR);
	}

	$valid_dir	=	is_dir($dir);
}

$icn	=	"";
// If no folder
if(!$valid_dir) {
	// If no folder, but was folder else empty
	$icn	=	($valid_start)? "_success":"_empty";
}
?>	<div id="plugin_DeleteCache_wrap">
		<div class="button_trigger" data-instruct="deletecache" data-toggle="<?php echo ($valid_dir)? "on":"off"; ?>" data-val="<?php if(nApp::call()->getGet('requestTable')) echo nApp::call()->getGet()->requestTable; ?>" style="background-image: url(/core_images/buttons/deleteCache<?php echo $icn; ?>.png); <?php if($valid_dir) { ?>cursor: pointer;<?php } ?>">
		</div>
	</div>