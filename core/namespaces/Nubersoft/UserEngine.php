<?php
namespace Nubersoft;

class	UserEngine extends \Nubersoft\nFunctions
	{
		public	function loginUser($array = array())
			{
				$nApp		=	nApp::call();
				$username	=	(!empty($array['username']))? $array['username'] : 'guest'.mt_rand().date('YmdHis');
				$usergroup	=	(!empty($array['usergroup']))? $nApp->convertUserGroup($array['usergroup']) : NBR_WEB;
				$fName		=	(!empty($array['first_name']))? $array['first_name'] : 'Guest';
				$lName		=	(!empty($array['last_name']))? $array['last_name'] : 'User';
					
				foreach($array as $key => $value) {
					$settings[$key]	=	$value;
				}
				
				$settings['usergroup']	=	$usergroup;
				$settings['username']	=	$username;
				$settings['first_name']	=	$fName;
				$settings['last_name']	=	$lName;
				// Get the session engine
				$nSession				=	nApp::call('nSessioner');
				// Make the array a session
				$nSession->makeSession($settings);
			}
		
		public	function logInUserWithCreds($username = false,$password = false)
			{
				if(empty($username) || empty($password))
					return false;
					
				$result	=	$this->getUser($username);
				
				if($result == 0)
					return false;
				
				// Check password_hash algo
				$PasswordEngine	=	PasswordGenerator::Engine(PasswordGenerator::USE_DEFAULT);
				$validate		=	$PasswordEngine	->setUser($username)
													->verifyPassword($password,$result['password'])
													->valid;
				// If false, try with bcrypt
				if(!$validate) {
					$PasswordEngine	=	PasswordGenerator::Engine(PasswordGenerator::BCRYPT);
					$validate		=	$PasswordEngine	->setUser($username)
														->verifyPassword($password,$result['password'])
														->valid;
				}
			}
			
		public	function getUser($username,$all = true)
			{
				if(empty($username))
					return 0;
					
				return nApp::call()->nQuery()
						->query("select * from `users` where `username` = :0".((!$all)? " AND `user_status` = 'on'":''),array($username))
						->getResults(true);
			}
		
		public	function allowIf($usergroup = 3)
			{
				if(is_string($usergroup))
					$usergroup	=	nApp::call()->convertUserGroup($usergroup);
					
				if(!is_numeric($usergroup))
					return false;
				
				return ($this->getUser($usergroup) <= $usergroup);
			}
		
		public	function isAdmin($username = false)
			{
				$nApp		=	nApp::call();
				
				if(!empty($username)) {
					$user	=	$this->getUser($username);
					if($user == 0)
						return false;
					
					$usergroup	=	$nApp->convertUserGroup($user['usergroup']);
				}
				else
					$usergroup	=	(!empty($nApp->getDataNode('_SESSION')->usergroup))? $nApp->getDataNode('_SESSION')->usergroup : false;
				
				if(!is_numeric($usergroup)) {
					if(is_string($usergroup))
						$usergroup	=	$nApp->convertUserGroup($usergroup);
					
					if(!is_numeric($usergroup))
						return false;
				}
				
				return $this->groupIsAdmin($usergroup);
			}
		
		public	function groupIsAdmin($usergroup)
			{
				if(!defined('NBR_ADMIN')) {
					if(is_file($inc = NBR_CLIENT_SETTINGS.DS.'usergroups.php'))
						include_once($inc);
					else
						include_once(NBR_SETTINGS.DS.'usergroups.php');
				}
				
				return	($usergroup <= NBR_ADMIN);
			}
		
		public	function isLoggedin($username = false)
			{
				$nApp	=	nApp::call();
				
				if(!empty($username))
					return ($nApp->getSession('username') == $username);
				
				return (!empty($nApp->getSession('username')));
			}
		
		public	function isLoggedInNotAdmin($username = false)
			{
				return ($this->isLoggedin($username) && !$this->isAdmin($username));
			}
		
		public	function hasAdminAccounts()
			{
				$sql	=	"SELECT
								COUNT(*) as count
								FROM `users`
								WHERE `usergroup` = 'NBR_SUPERUSER'
									OR
								`usergroup` = 'NBR_ADMIN'";
				
				$nApp	=	nApp::call();
				$nQuery	=	$nApp->nQuery();
				$count	=	$nQuery
					->query($sql)
					->getResults(true);
				
				return ($count['count'] >= 1);
			}
	}