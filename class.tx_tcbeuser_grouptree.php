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

require_once (PATH_t3lib.'class.t3lib_treeview.php');

/**
 * class.tx_tcbeuser_grouptree.php
 *
 * DESCRIPTION HERE
 * $Id$
 *
 * @author Ingo Renner <ingo.renner@dkd.de>
 */
class tx_tcbeuser_groupTree extends t3lib_treeView {
	var $fieldArray = array('uid', 'title');
	var $defaultList = 'uid,title';

	/**
	 * Init function
	 * REMEMBER to feed a $clause which will filter out non-readable pages!
	 *
	 * @param	string		Part of where query which will filter out non-readable pages.
	 * @return	void
	 */
	function init($clause='')	{
		parent::init(' AND deleted=0 '.$clause, 'title');

		$this->table    = 'be_groups';
		$this->treeName = 'groups';
	}
	
	/**
	 * recursivly builds a data array from a root $id which is than used to
	 * build a tree from it.
	 * 
	 * @param	integer	$id: the root id from where to start
	 * @return	array	hierarical array with tree data
	 */
	function buildTree($id) {
		$tree = array();
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid, title, subgroup',
			'be_groups',
			'deleted = 0 AND uid = '.$id
		);
		
		$row         = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		$tree[$id]   = $row;	
					
		if($row['subgroup']) {
			$subGroups = t3lib_div::intExplode(',', $row['subgroup']);
			foreach($subGroups as $newGroupId) {
				$row[$this->subLevelID][$newGroupId] = $this->buildTree($newGroupId);
			}
			return $tree[$id] = $row;
		} else {
			return $row;
		}
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tc_beuser/class.tx_tcbeuser_grouptree.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tc_beuser/class.tx_tcbeuser_grouptree.php']);
}

?>
