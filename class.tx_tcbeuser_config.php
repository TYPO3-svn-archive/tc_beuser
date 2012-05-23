<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Ingo Renner (ingo.renner@dkd.de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * clas for module configuration handling
 *
 * $Id$
 *
 * @author Ingo Renner <ingo.renner@dkd.de>
 */
class tx_tcbeuser_config {

	var $config;

	static function fakeAdmin() {
		global $BE_USER;
		$BE_USER->user['admin'] = 1;
	}

	static function removeFakeAdmin(){
		global $BE_USER;
		$BE_USER->user['admin'] = 0;
	}

	static function getSubgroup($id){
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid,title,subgroup',
			'be_groups',
			'deleted = 0 AND uid ='.$id
		);
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		$uid = '';
		if($row['subgroup']){
			$subGroup = t3lib_div::intExplode(',',$row['subgroup']);
			foreach ($subGroup as $subGroupUID){
				$uid .= $subGroupUID.','.tx_tcbeuser_config::getSubgroup($subGroupUID).',';
			}
			return $uid;
		} else {
			return $row['uid'];
		}
	}

	static function allowWhereMember($TSconfig){
		$userGroup = explode (',',$GLOBALS['BE_USER']->user['usergroup']);

		$allowWhereMember = array();
		foreach($userGroup as $uid){
			$groupID = $uid.','.tx_tcbeuser_config::getSubgroup($uid);
			if (strstr($groupID,',')){
				$groupIDarray = explode(',',$groupID);
				$allowWhereMember = array_merge($allowWhereMember, array_unique($groupIDarray));
			} else {
				$allowWhereMember[] = $groupID;
			}
		}
		$allowWhereMember = t3lib_div::removeArrayEntryByValue($allowWhereMember,'');

		return $allowWhereMember;
	}

	static function allowCreated($TSconfig, $where){
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid',
			'be_groups',
			$where.' AND cruser_id = '.$GLOBALS['BE_USER']->user['uid']
		);
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0) {
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)){
				$allowCreated[] = $row['uid'];
			}
		} else {
			$allowCreated = array();
		}

		return $allowCreated;
	}

	static function allow($TSconfig, $where){
		if(isset($TSconfig['allow']) && !empty($TSconfig['allow'])){
			if($TSconfig['allow'] == 'all'){
				$addWhere = empty($showGroupID) ? '' : ' AND uid not in ('.implode(',',$showGroupID).')';
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'uid',
					'be_groups',
					$where.$addWhere
				);
				if($GLOBALS['TYPO3_DB']->sql_num_rows($res)>0){
					while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)){
						$allowID[] = $row['uid'];
					}
				}
			} elseif (strstr($TSconfig['allow'],',')) {
				$allowID = explode(',',$TSconfig['allow']);
			} else {
				$allowID = array(trim($TSconfig['allow']));
			}
		} else {
			$allowID = array();
		}
		return $allowID;
	}

	static function denyID($TSconfig,$where){
		if(isset($TSconfig['deny']) && !empty($TSconfig['deny'])){
			if(strstr($TSconfig['deny'],',')){
				$denyID = explode(',',$TSconfig['deny']);
			} else {
				$denyID = array(trim($TSconfig['deny']));
			}
		} else {
			$denyID = array();
		}
		return $denyID;
	}

	static function showPrefixID($TSconfig,$where,$mode){
		$addWhere = "";

		if(isset($TSconfig[$mode]) && !empty($TSconfig[$mode])){
			if(strstr($TSconfig[$mode],',')){
				$prefix = explode(',',$TSconfig[$mode]);
				foreach($prefix as $pre){
					$whereTemp[] = 'title like '.$GLOBALS['TYPO3_DB']->fullQuoteStr(trim($pre).'%','be_groups');
				}
				$addWhere .= ' AND ('.implode (' OR ',$whereTemp).')';
			} else {
				$addWhere .= ' AND '.'title like '.$GLOBALS['TYPO3_DB']->fullQuoteStr($TSconfig[$mode].'%','be_groups');
			}

			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid',
				'be_groups',
				$where.$addWhere
			);
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0){
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)){
					$showPrefixID[] = $row['uid'];
				}
			} else {
				$showPrefixID = array();
			}
		} else {
			$showPrefixID = array();
		}
		return $showPrefixID;
	}

	static function showGroupID(){
		global $TYPO3_CONF_VARS;
		$TSconfig = $GLOBALS['BE_USER']->userTS['tx_tcbeuser.'] ? $GLOBALS['BE_USER']->userTS['tx_tcbeuser.'] : array();
			// default value
		$TSconfig['allowCreated'] = (strlen(trim($TSconfig['allowCreated'])) > 0)? $TSconfig['allowCreated'] : '1';
		$TSconfig['allowWhereMember'] = (strlen(trim($TSconfig['allowWhereMember'])) > 0)? $TSconfig['allowWhereMember'] : '1';

		$where = 'pid = 0'.t3lib_BEfunc::deleteClause('be_groups');
//debug($TSconfig,'TS');

		if ($TYPO3_CONF_VARS['BE']['explicitADmode'] == 'explicitAllow'){
			$showGroupID = array();

				//put ID allowWhereMember
			if($TSconfig['allowWhereMember'] == 1){
				$allowWhereMember = tx_tcbeuser_config::allowWhereMember($TSconfig);
				$showGroupID = array_merge($showGroupID,$allowWhereMember);
			}

				//put ID allowCreated
			if($TSconfig['allowCreated'] == 1){
				$allowCreated = tx_tcbeuser_config::allowCreated($TSconfig,$where);
				$showGroupID = array_merge($showGroupID,$allowCreated);
			}

				//allow
			$allowID = tx_tcbeuser_config::allow($TSconfig,$where);
			$showGroupID = array_merge($showGroupID,$allowID);

				//put ID showPrefix
			$showPrefix = tx_tcbeuser_config::showPrefixID($TSconfig,$where,'showPrefix');
			$showGroupID = array_merge($showGroupID,$showPrefix);

		} else {	//explicitDeny
			$showGroupID = explode(',',tx_tcbeuser_config::getAllGroupsID());
			$denyGroupID = array();

				//put ID allowWhereMember
			if($TSconfig['allowWhereMember'] == 0){
				$allowWhereMember = tx_tcbeuser_config::allowWhereMember($TSconfig);
				$denyGroupID = array_merge($denyGroupID,$allowWhereMember);
			}

				//put ID allowCreated
			if($TSconfig['allowCreated'] == 0 ){
				$allowCreated = tx_tcbeuser_config::allowCreated($TSconfig,$where);
				$denyGroupID = array_merge($denyGroupID,$allowCreated);
			}

				//deny
			if($TSconfig['deny'] == 'all'){
				$denyGroupID = array_merge($denyGroupID, explode(',',tx_tcbeuser_config::getAllGroupsID()));
			} else {
				$denyID = tx_tcbeuser_config::denyID($TSconfig,$where);
				$denyGroupID = array_merge($denyGroupID,$denyID);
			}

				//put ID dontShowPrefix
			$dontShowPrefix = tx_tcbeuser_config::showPrefixID($TSconfig,$where,'dontShowPrefix');
			$denyGroupID = array_merge($denyGroupID,$dontShowPrefix);

				//remove $denyGroupID from $showGroupID
			$showGroupID = array_diff($showGroupID,$denyGroupID);
		}
//debug($showGroupID,'final');
		return $showGroupID;
	}

	/**
	 * manipulate the list of usergroups based on TS Config
	 */
	static function getGroupsID(&$param,&$pObj){
		if ($GLOBALS['BE_USER']->user['admin'] == '0'){
			$where = 'pid = 0 '.t3lib_BEfunc::deleteClause('be_groups');
			$groupID = implode(',',tx_tcbeuser_config::showGroupID());
			if(!empty($groupID)){
				$where .= ' AND uid in ('.$groupID.')';
			} else {
				$where .= ' AND uid not in ('.tx_tcbeuser_config::getAllGroupsID().')';
			}
		} else {
			$where = '1'.t3lib_BEfunc::deleteClause('be_groups');
		}

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'be_groups',
			$where,
			'',
			'title ASC'
		);
		$param['items'] = array();

		while($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)){
			$param['items'][]= array($pObj->sL($row['title']),$row['uid'],'');
		}
		return $param;
	}

	/**
	 * get all ID in a comma-list
	 */
	static function getAllGroupsID(){
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid',
			'be_groups',
			'1'.t3lib_BEfunc::deleteClause('be_groups')
		);
		$id = array();
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)){
			$id[] = $row['uid'];
		}
		return implode(',',$id);
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tc_beuser/class.tx_tcbeuser_config.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tc_beuser/class.tx_tcbeuser_config.php']);
}

?>
