<!DOCTYPE html>
<html>
<title><?php echo $this->getHtml('title') ?></title>
<head profile="http://www.w3.org/2005/10/profile">
<?php if(is_file(NBR_ROOT_DIR.DS.'pinlogo.svg')) { ?>
<link rel="mask-icon" href="/pinlogo.svg" color="red">
<?php } ?>
<?php if(is_file(NBR_ROOT_DIR.DS.'favicon.ico')) { ?>
<link rel="shortcut icon" href="/favicon.ico">
<?php } ?>
<?php if(is_file(NBR_ROOT_DIR.DS.'favicon.png')) { ?>
<link rel="apple-touch-icon image_src" href="/favicon.png">
<?php } ?>
<?php echo $this->getHtml('meta') ?>
<?php echo $this->getViewPort() ?>
<?php echo $this->getMediaSrc('javascript') ?>
<?php echo $this->getMediaSrc('stylesheet');
if(!empty($this->getHtml('javascript'))) {
?>
<script>
<?php echo $this->getHtml('javascript') ?>
</script>
<?php
}
?>
<?php echo $this->getTemplateDoc('noscript.php').PHP_EOL; ?>
<?php if(!empty($this->getHtml('inline_css'))) { ?>
<style>
<?php echo $this->getHtml('inline_css') ?>
</style>
<?php } ?>
</head>