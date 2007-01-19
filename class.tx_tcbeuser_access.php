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
 * methods for access checks
 * $Id: class.tx_tcbeuser_access.php,v 1.1 2006/08/14 08: 02: 16 dkd-renner Exp
 * $
 *
 * @author Ingo Renner <ingo.renner@dkd.de>
 */ 
class tx_tcbeuser_access {
	
	function getFakePageInfo() {
		#['perms_userid']
	}
	
	function readPageAccess($conf, $exitOnError)	{
/*
debug(
	array(
		'be user class methods' => get_class_methods($GLOBALS['BE_USER']),
		'be user groupData' => $GLOBALS['BE_USER']->groupData['modules']	
		'conf' => $conf,
	),
	__FILE__.': '.__LINE__
);
*/	
		$access = false;
		
		if(t3lib_userAuthGroup::modAccess($conf, $exitOnError)) {
			$access = true;
		}
		
		return $access;
		
//		if ((string)$id!='')	{
//			$id = intval($id);
//			if (!$id)	{
//				if ($GLOBALS['BE_USER']->isAdmin())	{
//					$path = '/';
//					$pageinfo['_thePath'] = $path;
//					return $pageinfo;
//				}
//			} else {
//				$pageinfo = t3lib_BEfunc::getRecord('pages',$id,'*',($perms_clause ? ' AND '.$perms_clause : ''));
//				if ($pageinfo['uid'] && $GLOBALS['BE_USER']->isInWebMount($id,$perms_clause))	{
//					t3lib_BEfunc::workspaceOL('pages', $pageinfo);
//					t3lib_BEfunc::fixVersioningPid('pages', $pageinfo);
//					list($pageinfo['_thePath'],$pageinfo['_thePathFull']) = t3lib_BEfunc::getRecordPath(intval($pageinfo['uid']), $perms_clause, 15, 1000);
//					return $pageinfo;
//				}
//			}
//		}
		return false;
	}	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tc_beuser/class.tx_tcbeuser_access.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tc_beuser/class.tx_tcbeuser_access.php']);
}

?>
