<?php
$this->autoload(array('nquery','organize'));
$nubquery	=	nquery();	

if(!$nubquery) 
	return;

$nProcToken	=	$this->getHelper('nToken')->setMultiToken('nProcessor','pagination');

if(empty($dropdowns)) {
	$dropdowns = $this->toArray($this->getDropDowns($this->getTableName()));
}

if(!function_exists("BuildTableRows")) {
	function BuildTableRows($table,$values = array(),$columns = array(),$settings = array(),$dropdowns = array(),$headerrow = false)
		{
			$regBuild	=	$this->getFunction('table_prefs');
			$nubquery	=	nquery();
			
			if(!empty($columns)) {
				foreach($columns as $column) {
					$name	=	$column;
					$type	=	(!empty($settings[$column]['column_type']))? $settings[$column]['column_type']: "text";
					$type	=	(preg_match('/^nul/i',$type) && strlen($type) <= 4)? "text": $type;
					
					if(isset($regBuild[$table])) {
						$type	=	(in_array($name,$regBuild[$table]))? $type : 'fullhide';
					}
					
					$size	=	(!empty($settings[$column]['size']))? $settings[$column]['size']:""; ?>
				<td style=" <?php if($type == 'fullhide') echo 'display: none;'; if($values !== 'head') { ?> padding: 5px;<?php } else { ?>background-color: #333; color: #FFF;<?php } ?> text-align: center;">
<?php					if($headerrow) {
?>						<div style="padding: 5px 10px; font-size: 12px; white-space: nowrap; <?php if($type == 'fullhide') echo 'display: none;'; ?>">
						<?php echo str_replace("_"," ",strtoupper($name)); ?>
					</div>
<?php					}
				
					if($headerrow) {
?>						<div style="padding: 5px; background-color: #333;<?php if($type == 'fullhide') echo 'display: none;'; ?>">
<?php 					}
					include(NBR_RENDER_LIB.DS.'assets'.DS.'form.inputs'.DS.$type.".php");
					if($headerrow) {
?>						</div>
<?php 					}
?>					</td>
<?php				}
			}
		}
}

$this->autoload(array('custom_table_layout','organize'));
// Select items form builder options
$get_data	=	$nubquery	->select(array('column_name','column_type','size','restriction'),true)
							->from("form_builder")
							->wherein("column_name",$this->toArray($SearchEngine->columns))
							->getResults();

$select	=	$this->organizeByKey($get_data,'column_name');
// Check if this table has a custom table layout
$temp	=	custom_table_layout($SearchEngine->data->table,$this->toArray($SearchEngine->data->results),$SearchEngine->columns,$select,$dropdowns);

if(!$temp) {
?>
<style>
.form-builder	{
border: 1px solid #666;
}
.form-builder td	{
cursor: default;
vertical-align: top;
}
tr.form-builder-head td	{
background-color: #666;
color: #FFF;
}
tr.odd-table-rows td {
background-color: #CCC;
background: linear-gradient(#EBEBEB,#CCC);
border-bottom: 1px solid #888;
}
tr.even-table-rows td {
background-color: #EBEBEB;
background: linear-gradient(#EBEBEB,#CCC);
border-bottom: 1px solid #666;
}

tr.odd-table-rows:hover td,
tr.even-table-rows:hover td {
background: linear-gradient(rgba(0,0,0,0),rgba(0,0,0,0));
background-color: #FFF;
}
</style>
<div style="padding: 10px 20px; background-color: rgba(0,0,0,0.85); color: #FFF; text-shadow: 1px 1px 3px #000; margin: 0 auto 20px auto; display: inline-block; cursor: default; text-align: center; position: absolute; left: 0; right: 0; max-height: 1100px">Viewing: <?php echo ucwords(str_replace("_"," ",$SearchEngine->data->table)); ?></div>
<div style="padding-top: 60px;">
<table cellpadding="0" cellspacing="0" border="0" class="form-builder">
<?php
	$useTable	=	(!empty($SearchEngine->data->table))? $SearchEngine->data->table : $this->getTableName();
	
	if(!empty($SearchEngine->columns)) {
?>
	<form method="POST" enctype="multipart/form-data">
		<input type="hidden" name="requestTable" value="<?php echo $useTable; ?>" />
		<input type="hidden" name="token[nProcessor]" value="<?php echo $nProcToken; ?>" />
		<tr class="form-builder-head">
			<?php BuildTableRows($useTable,array(),$this->toArray($SearchEngine->columns),$select,$dropdowns,true); ?>
			<td style="background-color: #333; color: #FFF;">
			</td>
			<td style="background-color: #333; color: #FFF;">
				<div style="background-color: #333; color: #FFF; padding: 5px 10px; font-size: 12px;">FUNCTION</div>
				<div style="background-color: #333;" class="padding-5">
					<input type="hidden" name="add" value="add" />
					<div class="formButton"><input disabled="disabled" type="submit" value="ADD" /></div>
				</div>
			</td>
		</tr>
	</form>
<?php	if(isset($SearchEngine->data->results) && $SearchEngine->data->results !== 0) {
		$SearchEngine->data->results	=	$this->toArray($SearchEngine->data->results);
		$sCount	=	count($SearchEngine->data->results);
		for($i = 0; $i < $sCount; $i++) { 
?>		<form method="POST" enctype="multipart/form-data">
		<input type="hidden" name="requestTable" value="<?php echo $useTable; ?>" />
		<input type="hidden" name="token[nProcessor]" value="<?php echo $nProcToken; ?>" />
	<tr class="<?php if(($i % 2) == 0) echo "even"; else echo "odd"; ?>-table-rows">
		<?php BuildTableRows($useTable,$SearchEngine->data->results[$i],$SearchEngine->columns,$select,$dropdowns); ?>
		<td>
			<div style=" font-size: 10px;">DELETE?</div>
			<input type="checkbox" name="delete" />
		</td>
		<td class="padding-5">
			<input type="hidden" name="update" value="update" />
			<div class="formButton"><input disabled="disabled" type="submit" value="UPDATE" /></div>
		</td>
	</tr>
	</form>
<?php 			}
		}
	}
?>
</table>
<?php 	if($SearchEngine->data->total == 0 && isset($_GET['search'])) { ?>0 results found for "<?php echo $SearchEngine->data->search; ?>"<?php }
}
?>	</div>