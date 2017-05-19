<?php
function pagination_initialize($nApp,$settings = false)
	{
		// Set default Table search
		$table				=	(!isset($settings['table']))? 'components' : $settings['table'];
		// Set if admin can only check this table
		$admin				=	(isset($settings['admin']) && ($settings['admin'] == 'true' || $settings['admin'] === true || $settings['admin'] == 1 || $settings['admin'] == '1'));
		$constraints		=	(isset($settings['constraints']))? $settings['constraints'] : false;
		// Set the amount of before and after pages relative to current
		// Example: Set to two, would be two max numbers before and after (3 being current):   << 1 2 3 4 5 >>
		$spread				=	(!empty($settings['spread']))? (is_numeric($settings['spread']))?$settings['spread']:4:4;
		// Create instance of Search Engine
		$search				=	new \nPlugins\Nubersoft\SearchEngine($table,$admin);
		if($constraints) {
			$search->addConstraints($constraints);
		}
		// Apply settings
		$array				=	$search->fetch($settings);
		// Save data to arrays
		$SearchEngine['data']		=	($array->stats)? $array->stats : array();
		$SearchEngine['columns']	=	(!empty($array->columns))? $array->columns : false;
		// Create instance of global variablizer
		// Save settings to global static
		$nApp->saveSetting('pagination',$SearchEngine);
		
		if($nApp->checkEmpty($settings,'return',true))
			return	$SearchEngine;
	}