<?php
if(!$this->isAjaxRequest()) {
	echo self::call('nTemplate')->getDoc('page.head.php');
?>
<!-- START BODY -->
<body class="nbr">
<?php
}
// Render will take array and implode it
$opts	=	array(
				NBR_CORE,
				'plugins',
				'resetUserPassword',
				'html',
				'reset_password.php'
			);
// Render link
echo $this->render($opts);
if(!$this->isAjaxRequest()) {
	echo $this->getDoc('page.foot.php').PHP_EOL;
?>
</body>
</html>
<?php 
}