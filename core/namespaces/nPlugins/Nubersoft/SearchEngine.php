<?php
namespace nPlugins\Nubersoft;

class SearchEngine extends \Nubersoft\nApp
	{
		public		$numrows,
					$totalpages,
					$currentpage,
					$results,
					$s_count,
					$stats,
					$table_permission,
					$columns;
		
	//	protected	$permissions;
		protected	$table,
					$sql,
					$sql_mod,
					$maxcount,
					$limit,
					$nubquery,
					$spread,
					$searchword,
					$admintoggle,
					$constr_string,
					$constr_array,
					$columns_allowed,
					$constraints,
					$forceConstr,
					$selectArr;
		
		public	function __construct($table = false, $admintoggle = false)
			{
				$this->table		=	(empty($table))? $this->getDefaultTable() : $table;
				$this->table		=	$this->safe()->sanitize($this->table);
				$this->admintoggle	=	($admintoggle && $this->isAdmin());

				return parent::__construct();
			}
		
		public	function setAttr($name,$value)
			{
				self::$settings[$name] = $value;
			}
		
		public	function filterColumns($compare = array(), $addlist = false)
			{
				if(is_array($compare)) {
					# List all columns skip searching in
					$filter[]	=	'core_setting';
					$filter[]	=	'ID';
					$filter[]	=	'page_live';
					$filter[]	=	'page_order';
					$filter[]	=	'password';
					$filter[]	=	'timestamp';
					$filter[]	=	'usergroup';
					$filter[]	=	'user_status';
					
					# If an extra array is added, push to end.
					if(is_array($addlist))
						$filter	=	array_merge($filter,$addlist);
					
					$filter		=	array_unique($filter);
					return array_diff($compare,$filter);
				}
				else
					return array();
			}
		
		public	function addConstraints($array)
			{
				if(!is_array($array))
					return $this;
				
				$this->constraints	=	$array;
				
				return $this;
			}
		
		public	function fetch($settings = array())
			{
				$method		=	strtolower($this->setKeyValue($settings,'method','get'));
				$def_limit	=	$this->setKeyValue($settings,'limit',10);
				$spread		=	$this->setKeyValue($settings,'spread',4);
				$sort		=	$this->setKeyValue($settings,'sort','ASC');
				$orderby	=	$this->setKeyValue($settings,'order','ID');
				
				$this->stats['max_range']	=	(isset($settings['max_range']))? $settings['max_range'] : array(5,10,20,50);
				
				if(!empty($settings['columns']))
					$this->columns_allowed	=	(!is_array($settings['columns']))? explode(",",$settings['columns']) : $settings['columns'];
				
				$this->columns_allowed	=	(!isset($this->columns_allowed))? "*":$this->columns_allowed;
				
				if(!empty($settings['select']) && is_array($settings['select']))
					$this->selectArr	=	$settings['select'];
				else
					$this->selectArr	=	$this->columns_allowed; 
				
				if(empty($this->constraints))
					# If admin allowed, and user is admin
					$this->constraints	=	($this->admintoggle)? false : array('page_live'=>'on');
	
				$this->stats['table']	=	$this->table;
				
				# Reset all counts
				$this->numrows	=	0;
				$this->results	=	0;
				$this->columns	=	0;
				$this->s_count	=	0;
				$this->spread	=	$spread;
				
				# If the query engine is available, go at it.
				if($this->siteValid()) {
					# Method to do search by
					if($method == 'post')
						$req	=	$this->toArray($this->getPost());
					elseif($method == 'request')
						$req	=	$this->toArray($this->getRequest());
					else
						$req	=	$this->toArray($this->getGet());
					
					# Limit count
					$this->limit			=	(isset($req['max']) && is_numeric($req['max']))? $req['max']: $def_limit;
					# Save search word
					$this->searchword		=	(isset($req['search']))? $req['search']:false;
					# Retrieve columns to search from
					$this->columns	=	(!is_array($this->columns_allowed))? $this->getColumns($this->stats['table']) : $this->columns_allowed;
					
					if(!empty($this->constraints)) {
						foreach($this->constraints as $col => $val) {
							$const[]	=	$col." = '".$val."'";
						}
					}
					
					# This converts possible entries to an array instead of an object
					$this->columns			=	$this->toArray($this->columns);
					$this->constr_string	=	(!empty($const))? 'AND '.implode(" AND ",$const) : "";
					$this->constr_array		=	$this->constraints;
					$c_query				=	$this->nQuery();
					# Count total rows with search result
					if(!empty($this->searchword)) {
						$c_query	->select("COUNT(*) as count")
									->from($this->table)
									->like(array('columns'=>$this->filterColumns($this->columns),'like'=>$this->searchword),false,true);
						
						if(!empty($const))		
							$c_query->addCustom($this->constr_string);
						
						$count	=	$c_query->fetch();
					}
					else {
						try {
							$c_query	->select("COUNT(*) as count")
										->from($this->table);
							
							if(!empty($const))
								$c_query->where($this->constraints);
						
							$count	=	$c_query->fetch();
						}
						catch (\Exception $e) {
							echo printpre();
						}
					}
					# Assign total rows found
					$this->numrows	=	$count[0]['count'];
					# Retrieve arrays
					$this->calculateVals($req,$orderby,$sort);
				}
				
				$this->stats	=	(!isset($this->stats))? false:$this->stats;
				
				return $this;
			}
		
		public	function getStats()
			{
				$data	=	array(
					'data' => (($this->stats)? $this->stats : array()),
					'columns' => ((!empty($this->columns))? $this->columns : false)
				);
				
				return $this->toArray($data);
			}
		/*
		**	@description	There is an constraint that is by default set to false. It forces a "WHERE"
		**					clause into the sql. If admin, the constraints are ignored. This overrides the
		**					constraint to be used regardless of admin status
		*/
		public	function forceConstraint($force = true)
			{
				$this->forceConstr	=	$force;
				return $this;
			}
		
		protected	function calculateVals($req,$orderB = 'ID',$orderH = 'ASC')
			{
				if($this->numrows > 0) {
					$formula			=	$this->numrows / $this->limit;
					$this->totalpages	=	ceil($formula);
					# get the current page or set a default
					$this->currentpage	=	(isset($req['current']) && is_numeric($req['current']))? (int) $req['current']: 1;
					
					# if current page is greater than total pages...
					if ($this->currentpage > $this->totalpages) {
						# set current page to last page
						$this->currentpage = $this->totalpages;
					}
					# if current page is less than first page...
					if ($this->currentpage < 1) {
					   # set current page to first page
					   $this->currentpage = 1;
					}
					
					# the offset of the list, based on current page 
					$page 		=	($this->currentpage - 1) * $this->limit;
					# Create base query
					$searcher	=	$this->nQuery()
										->select($this->selectArr)
										->from($this->table);
					
					$_isAdmin	=	$this->isAdmin();
					
					# Set the search for the table
					if(isset($req['search'])) {
						# Add common like addition
						$searcher->like(array('columns'=>$this->filterColumns($this->columns),'like'=>$req['search']),false,true);
						# If not admin, add the page_live restriction
						if(!$_isAdmin || !empty($this->forceConstr))
							$searcher->addCustom($this->constr_string);
					}
					else {
						# If not admin, add restriction
						if(!$_isAdmin || !empty($this->forceConstr))
							$searcher->where($this->constr_array);
					}
					# Add limit and order by
					$searcher->orderBy(array($orderB=>$orderH))->limit($this->limit,$page);
					# Assign results
					$searched			=	$searcher->fetch();
					# After search, results array
					$this->results		=	($searched != 0)? $searched : array();
					# After search, row count
					$this->s_count		=	(!empty($this->results))? count($searched) : 0;
				}
				
				$this->stats['query'][]			=	$this->setKeyValue($req,'search','',"search=".$this->setKeyValue($req,'search',false));
				$this->stats['query'][]			=	"requestTable=".urlencode($this->safe()->sanitize($this->stats['table']));
				$this->stats['query'][]			=	$this->setKeyValue($req,'max','',"max=".$this->setKeyValue($req,'max',false));
				$this->stats['query']			=	trim(implode("&",$this->stats['query']),"&");
				# Total pages containing results list
				$this->stats['total']			=	$this->setKeyValue($this,'totalpages',0);
				# Max amount per page
				$this->stats['limit']			=	$this->setKeyValue($this,'limit',0);
				# Current Page
				$this->stats['current']			=	$this->setKeyValue($this,'currentpage',0);
				# Total row count
				$this->stats['count']			=	$this->setKeyValue($this,'s_count',0);
				$this->stats['total_found']		=	$this->numrows;
				# Results
				$this->stats['results']			=	$this->setKeyValue($this,'results',0);
				# Next page + $previous page
				$next 							=	($this->stats['current'] + 1);
				$previous						=	($this->stats['current'] - 1);
				# Assign Next
				$this->stats['next']			=	($next > $this->stats['total'])? false:$next;
				# Assign Previous
				$this->stats['previous']		=	($previous <= 0)? 1:$previous;
				# Last							
				$this->stats['last']			=	$this->stats['total'];
				$this->stats['last_link']		=	$this->createQueryString(array("currentpage"),$_GET);
				# Make range for pagination
				$low							=	(int) ($this->stats['current'] - $this->spread);
				$high							=	($this->stats['current'] + $this->spread);
				
				$difference['low']				=	($low <= (int) 0)? str_replace("-","",$low): 0;
				$difference['high']				=	($high >= $this->totalpages)? ($high - $this->totalpages): 0;
				$range['low']					=	($low <= 0)? 1 : ((int) $low - (int) $difference['high']);
				$addhigh						=	((int) $high + (int) $difference['low']);
				$range['high']					=	($addhigh >= $this->totalpages)? $this->totalpages : $addhigh;
				
				if($range['low'] <= 0)
					$range['low']				=	1;
				
				$this->stats['range']			=	($this->totalpages != 0)? range($range['low'],$range['high']):false;
				$this->stats['search']			=	$this->searchword;
			}
	}