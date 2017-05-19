<?php
namespace nPlugins\Nubersoft\Permissions;

class Observer extends \Nubersoft\nApp
	{
		public	function listen()
			{
				if(!$this->isAdmin())
					continue;
				
				$GET		=	json_decode($this->safe()->decOpenSsl(urldecode(urlencode($this->getGet('info')))),true);		
				$ip			=	$GET['ip'];
				$user		=	$GET['username'];
				$is_banned	=	$this->getBannedStatus($ip,$user);
				$nQuery		=	$this->nQuery();
				$getDistId	=	$nQuery->query("select `dist_id` from users where `username` = :0",array($user))->getResults(1);
				if(empty($this->getGet('subaction'))) {			
					if(array_sum($is_banned) > 0) {
					
					}
					else {
						
						$nQuery->query("INSERT INTO `api_mxi_banned_ips` (`unique_id`,`dist_id`,`username`,`ip_address`) VALUES('".$this->fetchUniqueId()."',:0,:1,:2)",array($getDistId['dist_id'],$user,$ip));
					}
					
					$nQuery->query("UPDATE `users` set `user_status` = 'off' WHERE `username` = :0",array($user));
				}
				else {
					
					$sql	=	"DELETE FROM `api_mxi_banned_ips` WHERE `dist_id` = :0 OR `username` = :1 OR `ip_address` = :2";
					$nQuery->query($sql,array($getDistId['dist_id'],$user,$ip));
					$nQuery->query("UPDATE `users` set `user_status` = 'on' WHERE `username` = :0",array($user));
				}
				# Remove get string
				$this->getHelper('nRouter')->addRedirect($this->adminUrl('?requestTable=order_transactions'));
			
			}
		
		public	function getBannedStatus($username,$ip)
			{
				$ipBanSql	=	"SELECT COUNT(*) as count from `api_mxi_banned_ips` where `dist_id` =:0 OR `username`= :1";
				$count		=	$this->nQuery()->query($ipBanSql,array($username,$username))->getResults(true);
				$userCnt	=	$count['count'];
				
				$ipBanSql	=	"SELECT COUNT(*) as count from `api_mxi_banned_ips` where `ip_address` = :0";
				$count		=	$this->nQuery()->query($ipBanSql,array($ip))->getResults(true);
				
				$ipCnt		=	$count['count'];
				
				return array(
					'ip'=>$ipCnt,
					'user'=>$userCnt
				);
			}
	}