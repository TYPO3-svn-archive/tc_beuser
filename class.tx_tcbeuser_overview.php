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

$extPath = t3lib_extMgm::extPath('tc_beuser');
require_once($extPath.'class.tx_tcbeuser_grouptree.php');
require_once($extPath.'class.tx_tcbeuser_recordlist.php');
require_once(PATH_t3lib.'class.t3lib_iconworks.php');
require_once(PATH_t3lib.'class.t3lib_tsparser.php');
require_once(PATH_t3lib.'class.t3lib_tceforms.php');
require_once(PATH_t3lib.'class.t3lib_userauthgroup.php');
require_once(PATH_t3lib.'class.t3lib_loadmodules.php');
require_once(PATH_typo3.'template.php');
$LANG->includeLLFile('EXT:tc_beuser/mod4/locallang.xml');
/**
 * class.tx_tcbeuser_overview.php
 *
 * DESCRIPTION HERE
 * $Id$
 *
 * @author Ingo Renner <ingo.renner@dkd.de>
 */ 
class tx_tcbeuser_overview {
	
	var $row;
	/**
	 * @var array	$availableMethods a list of methods, that are directly available ( ~ the interface)
	 */
	var $availableMethods = array(
		'renderColFilemounts',
		'renderColWebmounts',
		'renderColPagetypes',
		'renderColSelecttables',
		'renderColModifytables',
		'renderColNonexcludefields',
		'renderColExplicitallowdeny',
		'renderColLimittolanguages',
		'renderColWorkspaceperms',
		'renderColWorkspacememship',
		'renderColDescription',
		'renderColModules',
		'renderColTsconfig',
		'renderColTsconfighl',
		'renderColMembers',
	);


	/**
	 * method dispatcher
	 * checks input vars and returns result of desired method if available
	 * 
	 * @param	string	$method: defines what to return
	 * @param	int	$groupId
	 * @param	bool	$open
	 * @param	string	$backPath
	 * @return	string
	 */
	function handleMethod ( $method, $groupId, $open=false, $backPath='' )	{
		$content = '';
		$method = trim(strval($method));
		$groupId = intval($groupId);
		$open = (bool) $open;
		
		if ( in_array( $method, $this->availableMethods ) )	{
			$content = $this->$method( $groupId, $open, $backPath );
		}
		
		return $content;
	}


	function getTable($row, $setCols) {
		$content = '';
		$this->row = $row;

		$out = $this->renderListHeader($setCols);
		
		$cc = 0;
		$groups = t3lib_div::intExplode(',', $row['usergroup']);
		foreach($groups as $groupId) {
			if ($groupId != 0){			
				$tree = $this->getGroupTree($groupId);
				
				foreach($tree as $row)	{				
					$tCells = $this->renderListRow($setCols, $row, ($cc%2 ? 'db_list_alt':''));	
					
					$out .= '
<tr>
	'.implode('',$tCells).'
</tr>';
					$cc++;			
				}
			} else {
				return '<br /><br />'.$GLOBALS['LANG']->sL('LLL:EXT:tc_beuser/mod3/locallang.xml:not-found').'<br />';
			}
		}
				
		$content .= '<table border="0" cellpadding="0" cellspacing="0" class="typo3-dblist">
					'.$out.'
				</table>'."\n";
		
		return $content;
	}
	
	/**
	 * only used for group view
	 */
	function getTableGroup($row, $setCols) {
		$content = '';
		$this->row = $row;

		$out = $this->renderListHeader($setCols);
		
		$cc = 0;
		$groups = t3lib_div::intExplode(',', $row['uid']);
		foreach($groups as $groupId) {			
			$tree = $this->getGroupTree($groupId);
			
			foreach($tree as $row)	{				
				$tCells = $this->renderListRow($setCols, $row, ($cc%2 ? 'db_list_alt':''));	
				
				$out .= '
<tr>
	'.implode('',$tCells).'
</tr>';
				$cc++;			
			}			
		}
				
		$content .= '<table border="0" cellpadding="0" cellspacing="0" class="typo3-dblist">
					'.$out.'
				</table>'."\n";
		
		return $content;
	}
	
	function renderListHeader($setCols) {
		$content = '';
		
		$content .= '
			<tr>
				<td class="c-headLineTable" colspan="'.(count($setCols) + 2).'">&nbsp;</td>
			</tr>'."\n";
			
		$content .= '<tr>'."\n";
		
			// always show groups and Id
		#$label = $GLOBALS['LANG']->sL('LLL:EXT:tc_beuser/mod4/locallang.xml:showCol-groups', 1);
		$label = $GLOBALS['LANG']->getLL('showCol-groups');
		$content .= $this->wrapTd($label.':', 'class="c-headLine"');
		$content .= $this->wrapTd('ID:', 'class="c-headLine"');
		
		if(count($setCols)) {
			foreach($setCols as $col => $set) {
				switch($col) {
					case 'members':
						$label = $GLOBALS['LANG']->getLL('showCol-members');
						$content .= $this->wrapTd($label.':', 'class="c-headLine"');
						break;
					case 'filemounts':
						$label = $GLOBALS['LANG']->getLL('showCol-filemounts');
						$content .= $this->wrapTd($label.':', 'class="c-headLine"');
						break;
					case 'webmounts':
						$label = $GLOBALS['LANG']->getLL('showCol-webmounts');
						$content .= $this->wrapTd($label.':', 'class="c-headLine"');
						break;
					case 'pagetypes':
						$label = $GLOBALS['LANG']->getLL('showCol-pagetypes');
						$content .= $this->wrapTd($label.':', 'class="c-headLine"');
						break;
					case 'selecttables':
						$label = $GLOBALS['LANG']->getLL('showCol-selecttables');
						$content .= $this->wrapTd($label.':', 'class="c-headLine"');
						break;
					case 'modifytables':
						$label = $GLOBALS['LANG']->getLL('showCol-modifytables');
						$content .= $this->wrapTd($label.':', 'class="c-headLine"');
						break;
					case 'nonexcludefields':
						$label = $GLOBALS['LANG']->getLL('showCol-nonexcludefields');
						$content .= $this->wrapTd($label.':', 'class="c-headLine"');
						break;
					case 'explicitallowdeny':
						$label = $GLOBALS['LANG']->getLL('showCol-explicitallowdeny');
						$content .= $this->wrapTd($label.':', 'class="c-headLine"');
						break;
					case 'limittolanguages':
						$label = $GLOBALS['LANG']->getLL('showCol-limittolanguages');
						$content .= $this->wrapTd($label.':', 'class="c-headLine"');
						break;
					case 'workspaceperms':
						$label = $GLOBALS['LANG']->getLL('showCol-workspaceperms');
						$content .= $this->wrapTd($label.':', 'class="c-headLine"');
						break;
					case 'workspacememship':
						$label = $GLOBALS['LANG']->getLL('showCol-workspacememship');
						$content .= $this->wrapTd($label.':', 'class="c-headLine"');
						break;
					case 'description':
						$label = $GLOBALS['LANG']->getLL('showCol-description');
						$content .= $this->wrapTd($label.':', 'class="c-headLine"');
						break;
					case 'modules':
						$label = $GLOBALS['LANG']->getLL('showCol-modules');
						$content .= $this->wrapTd($label.':', 'class="c-headLine"');
						break;
					case 'tsconfig':
						$label = $GLOBALS['LANG']->getLL('showCol-tsconfig');
						$content .= $this->wrapTd($label.':', 'class="c-headLine"');
						break;
					case 'tsconfighl':
						$label = $GLOBALS['LANG']->getLL('showCol-tsconfighl');
						$content .= $this->wrapTd($label.':', 'class="c-headLine"');
						break;
				}
			}
		}
		$content .= '</tr>'."\n";
		
		return $content;
	}
	
	function renderListRow($setCols, $treeRow, $class) {
		$tCells = array();
		
			// title:
		$rowTitle = $treeRow['HTML'].' '.htmlspecialchars($treeRow['row']['title']);
		$tCells[] = $this->wrapTd($rowTitle, 'nowrap="nowrap"', $class);
			// id
		$tCells[] = $this->wrapTd($treeRow['row']['uid'], 'nowrap="nowrap"', $class);
		
		if(count($setCols)) {
			foreach($setCols as $colName => $set) {
				$td = call_user_func(
					array(
						&$this, 
						'renderCol'.ucfirst($colName)
					),
					$treeRow['row']['uid'],
					''
				);
				
				$tCells[] = $this->wrapTd($td, 'id="'.mt_rand().'" nowrap="nowrap"', $class);
			}
		}		
		
		return $tCells;
	}

	function renderColFilemounts($groupId, $open = false, $backPath = '') {
		$content  = '';
		$backPath = $backPath ? $backPath : $GLOBALS['SOBE']->doc->backPath;
		$title    = $GLOBALS['LANG']->getLL('showCol-filemounts');		
		$icon     = '<img'.t3lib_iconWorks::skinImg($backPath, 'gfx/ol/'.($open?'minus':'plus').'bullet.gif','width="18" height="16"').' alt="" />';

		$this->table = 'sys_filemounts';
		$this->backPath = $backPath;
		if($open) {			
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'file_mountpoints',
				'be_groups',
				'uid = '.$groupId
			);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			
			$fileMounts = t3lib_div::intExplode(',', $row['file_mountpoints']);
			$items = array();			
			if(is_array($fileMounts) && $fileMounts[0] != 0) {
				$content .= '<br />';
				foreach($fileMounts as $fm) {
					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'*',
						$this->table,
						'uid = '.$fm
					);
					$filemount = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
					$filemountId = $filemount['uid'];
					
					$fmIcon = t3lib_iconWorks::getIconImage(
						$this->table, 
						$filemount,
						$backPath
					);
					
					$items[] = '<tr><td>'.$fmIcon.$filemount['title'].'</td><td>'.$this->makeUserControl($filemount).'</td></tr>'."\n";
				}
			}
			$content .= '<table>'.implode('',$items).'</table>';
		}
		$toggle = '<span onclick="updateData(this, \'renderColFilemounts\', '
			.$groupId.', '
			.($open?'0':'1')
			.');" style="cursor: pointer;">'
			. $icon . $title 
			.'</span>';
		
		return $toggle . $content;

	}
	
	function renderColWebmounts($groupId, $open = false, $backPath = '') {
		$content  = '';
		$backPath = $backPath ? $backPath : $GLOBALS['SOBE']->doc->backPath;
		$title    = $GLOBALS['LANG']->getLL('showCol-webmounts');		
		$icon     = '<img'.t3lib_iconWorks::skinImg($backPath, 'gfx/ol/'.($open?'minus':'plus').'bullet.gif','width="18" height="16"').' alt="" />';
		
		if($open) {			
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'db_mountpoints',
				'be_groups',
				'uid = '.$groupId
			);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			
			$webMounts = t3lib_div::intExplode(',', $row['db_mountpoints']);			
			if(is_array($webMounts) && $webMounts[0] != 0) {
				$content .= '<br />';
				foreach($webMounts as $wm) {
					$webmount = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
						'uid, title, nav_hide, doktype, module',
						'pages',
						'uid = '.$wm
					);
					$webmount = $webmount[0];
					
					$wmIcon = t3lib_iconWorks::getIconImage(
						'pages', 
						$webmount,
						$backPath,
						' title="id='.$webmount['uid'].'"'
					);
					
					$content .= $wmIcon.$webmount['title'].'<br />'."\n";
				}
			}
		}
		
		$toggle = '<span onclick="updateData(this, \'renderColWebmounts\', '
			.$groupId.', '
			.($open?'0':'1')
			.');" style="cursor: pointer;">'
			. $icon . $title 
			.'</span>';
		
		return $toggle . $content;
	}
	
	function renderColPagetypes($groupId, $open = false, $backPath = '') {
		$content  = '';
		$backPath = $backPath ? $backPath : $GLOBALS['SOBE']->doc->backPath;
		$title    = $GLOBALS['LANG']->getLL('showCol-pagetypes');		
		$icon     = '<img'.t3lib_iconWorks::skinImg($backPath, 'gfx/ol/'.($open?'minus':'plus').'bullet.gif','width="18" height="16"').' alt="" />';
		
		if($open) {	
			$content .= '<br />';
			
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'pagetypes_select',
				'be_groups',
				'uid = '.$groupId
			);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			
			$pageTypes = explode(',', $row['pagetypes_select']);
			reset($pageTypes);
			while(list($kk,$vv) = each($pageTypes))	{
				if(!empty($vv)) {
					$ptIcon = t3lib_iconWorks::getIconImage(
						'pages', 
						array('doktype' => $vv),
						$backPath,
						' title="doktype='.$vv.'"'
					);
					
					$content .= $ptIcon . $GLOBALS['LANG']->sL(t3lib_BEfunc::getLabelFromItemlist('pages','doktype',$vv));
					$content .= '<br />'."\n";
				}
			}
		}
		
		$toggle = '<span onclick="updateData(this, \'renderColPagetypes\', '
			.$groupId.', '
			.($open?'0':'1')
			.');" style="cursor: pointer;">'
			. $icon . $title 
			.'</span>';
		
		return $toggle . $content;
	}
	
	function renderColSelecttables($groupId, $open = false, $backPath = '') {
		$content  = '';
		$backPath = $backPath ? $backPath : $GLOBALS['SOBE']->doc->backPath;
		$title    = $GLOBALS['LANG']->getLL('showCol-selecttables');		
		$icon     = '<img'.t3lib_iconWorks::skinImg($backPath, 'gfx/ol/'.($open?'minus':'plus').'bullet.gif','width="18" height="16"').' alt="" />';
		
		if($open) {
			$content .= '<br />';
			
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'tables_select',
				'be_groups',
				'uid = '.$groupId
			);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$tablesSelect = explode(',', $row['tables_select']);
			reset($tablesSelect);
			while(list($kk,$vv) = each($tablesSelect))	{
				if(!empty($vv)) {
					$ptIcon = t3lib_iconWorks::getIconImage(
						$vv, 
						'',
						$backPath,
						' title="table='.$vv.'"'
					);
					$tableTitle = $GLOBALS['TCA'][$vv]['ctrl']['title'];
					$content .= $ptIcon . $GLOBALS['LANG']->sL($tableTitle);
					$content .= '<br />'."\n";
				}
			}
		}
		
		$toggle = '<span onclick="updateData(this, \'renderColSelecttables\', '
			.$groupId.', '
			.($open?'0':'1')
			.');" style="cursor: pointer;">'
			. $icon . $title 
			.'</span>';
		
		return $toggle . $content;
	}
	
	function renderColModifytables($groupId, $open = false, $backPath = '') {
		$content  = '';
		$backPath = $backPath ? $backPath : $GLOBALS['SOBE']->doc->backPath;
		$title    = $GLOBALS['LANG']->getLL('showCol-modifytables');		
		$icon     = '<img'.t3lib_iconWorks::skinImg($backPath, 'gfx/ol/'.($open?'minus':'plus').'bullet.gif','width="18" height="16"').' alt="" />';
		
		if($open) {
			$content .= '<br />';
			
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'tables_modify',
				'be_groups',
				'uid = '.$groupId
			);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$tablesModify = explode(',', $row['tables_modify']);
			reset($tablesModify);
			while(list($kk,$vv) = each($tablesModify))	{
				if(!empty($vv)) {
					$ptIcon = t3lib_iconWorks::getIconImage(
						$vv, 
						'',
						$backPath,
						' title="table='.$vv.'"'
					);
					$tableTitle = $GLOBALS['TCA'][$vv]['ctrl']['title'];
					$content .= $ptIcon . $GLOBALS['LANG']->sL($tableTitle);
					$content .= '<br />'."\n";
				}
			}
		}
		
		$toggle = '<span onclick="updateData(this, \'renderColModifytables\', '
			.$groupId.', '
			.($open?'0':'1')
			.');" style="cursor: pointer;">'
			. $icon . $title 
			.'</span>';
		
		return $toggle . $content;
	}
	
	function renderColNonexcludefields($groupId, $open = false, $backPath = '') {
		$content  = '';
		$backPath = $backPath ? $backPath : $GLOBALS['SOBE']->doc->backPath;
		$title    = $GLOBALS['LANG']->getLL('showCol-nonexcludefields');		
		$icon     = '<img'.t3lib_iconWorks::skinImg($backPath, 'gfx/ol/'.($open?'minus':'plus').'bullet.gif','width="18" height="16"').' alt="" />';
		
		if($open) {
			$content .= '<br />';
			
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'non_exclude_fields',
				'be_groups',
				'uid = '.$groupId
			);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$non_exclude_fields = explode(',', $row['non_exclude_fields']);
			reset($non_exclude_fields);
			while(list($kk,$vv) = each($non_exclude_fields))	{
				if(!empty($vv)) {
					$data = explode(':',$vv);
					$tableTitle = $GLOBALS['TCA'][$data[0]]['ctrl']['title'];
					$fieldTitle = $GLOBALS['TCA'][$data[0]]['columns'][$data[1]]['label'];
					$content .= $GLOBALS['LANG']->sL($tableTitle).': '.rtrim($GLOBALS['LANG']->sL($fieldTitle),':');
					$content .= '<br />'."\n";
				}
			}
		}
		
		$toggle = '<span onclick="updateData(this, \'renderColNonexcludefields\', '
			.$groupId.', '
			.($open?'0':'1')
			.');" style="cursor: pointer;">'
			. $icon . $title 
			.'</span>';
		
		return $toggle . $content;
	}
	
	function renderColExplicitallowdeny($groupId, $open = false, $backPath = '') {
		$content  = '';
		$backPath = $backPath ? $backPath : $GLOBALS['SOBE']->doc->backPath;
		$title    = $GLOBALS['LANG']->getLL('showCol-explicitallowdeny');		
		$icon     = '<img'.t3lib_iconWorks::skinImg($backPath, 'gfx/ol/'.($open?'minus':'plus').'bullet.gif','width="18" height="16"').' alt="" />';
		
		$adLabel = array(
			'ALLOW' => $GLOBALS['LANG']->sl('LLL:EXT:lang/locallang_core.xml:labels.allow'),
			'DENY' => $GLOBALS['LANG']->sl('LLL:EXT:lang/locallang_core.xml:labels.deny'),
		);
		
		$iconsPath = array(
			'ALLOW' => '../'.TYPO3_mainDir.t3lib_iconWorks::skinImg($this->backPath,'gfx/icon_ok2.gif','',1),
			'DENY' => '../'.TYPO3_mainDir.t3lib_iconWorks::skinImg($this->backPath,'gfx/icon_fatalerror.gif','',1),
		);
		
		if($open) {
			$content .= '<br />';
			
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'explicit_allowdeny',
				'be_groups',
				'uid = '.$groupId
			);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			if(!empty($row['explicit_allowdeny'])){
				$explicit_allowdeny = explode(',', $row['explicit_allowdeny']);
				reset($explicit_allowdeny);
				$data = '';
				foreach($explicit_allowdeny as $val){
					$dataParts = explode(':',$val);
					t3lib_div::loadTCA($dataParts[0]);
					$items = $GLOBALS['TCA'][$dataParts[0]]['columns'][$dataParts[1]]['config']['items'];
					foreach($items as $val){
						if ($val[1] == $dataParts[2]){
							$imageInfo = t3lib_TCEforms::getIcon($iconsPath[$dataParts['3']]);
							$imageInfo[0] = str_replace('../typo3',$backPath,$imageInfo[0]);
							$data .= '<img src ="'.$imageInfo[0].'" '.$imageInfo[1][3].'/>'.
								' ['.$adLabel[$dataParts['3']].'] '.
								$GLOBALS['LANG']->sl($val[0]).'<br />';
						}
					}
				}
			}
			$content .= $data .'<br />';
		}
		
		$toggle = '<span onclick="updateData(this, \'renderColExplicitallowdeny\', '
			.$groupId.', '
			.($open?'0':'1')
			.');" style="cursor: pointer;">'
			. $icon . $title 
			.'</span>';
		
		return $toggle . $content;
	}
	
	function renderColLimittolanguages($groupId, $open = false, $backPath = '') {
		$content  = '';
		$backPath = $backPath ? $backPath : $GLOBALS['SOBE']->doc->backPath;
		$title    = $GLOBALS['LANG']->getLL('showCol-limittolanguages');		
		$icon     = '<img'.t3lib_iconWorks::skinImg($backPath, 'gfx/ol/'.($open?'minus':'plus').'bullet.gif','width="18" height="16"').' alt="" />';
		
		if($open) {
			$content .= '<br />';
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'allowed_languages',
				'be_groups',
				'uid = '.$groupId
			);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$allowed_languages = explode(',', $row['allowed_languages']);
			reset($allowed_languages);
			$availLang = t3lib_BEfunc::getSystemLanguages();
			foreach($allowed_languages as $langId){
				foreach($availLang as $availLangInfo){
					if($availLangInfo[1] == $langId){
						$dataIcon = array();
						if(isset($availLangInfo[2])){
							$dataIcon = t3lib_TCEforms::getIcon($availLangInfo[2]);
						}
						if(empty($dataIcon)){
							$dataIcon[0]='clear.gif';
						}
						$data .= '<img src="'.$backPath.$dataIcon[0].'" '.$dataIcon[1][3].'/> '.
							$availLangInfo[0].'<br />';
					}
				}
			}
			$content .= $data .'<br />';
		}
		
		$toggle = '<span onclick="updateData(this, \'renderColLimittolanguages\', '
			.$groupId.', '
			.($open?'0':'1')
			.');" style="cursor: pointer;">'
			. $icon . $title 
			.'</span>';
		
		return $toggle . $content;
	}
	
	function renderColWorkspaceperms($groupId, $open = false, $backPath = '') {
		$content  = '';
		$backPath = $backPath ? $backPath : $GLOBALS['SOBE']->doc->backPath;
		$title    = $GLOBALS['LANG']->getLL('showCol-workspaceperms');		
		$icon     = '<img'.t3lib_iconWorks::skinImg($backPath, 'gfx/ol/'.($open?'minus':'plus').'bullet.gif','width="18" height="16"').' alt="" />';
		
		if($open) {
			$content .= '<br />';
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'workspace_perms',
				'be_groups',
				'uid = '.$groupId
			);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			//t3lib_div::loadTCA()
			$permissions = floatval($row['workspace_perms']);
			$items = $GLOBALS['TCA']['be_groups']['columns']['workspace_perms']['config']['items'];
			$check = array();
			foreach($items as $key => $val){
				if($permissions & pow(2,$key)){
					$check[] = $GLOBALS['LANG']->sL($val[0]);
				}
			}
			$content .= implode('<br />',$check);
		}
		$toggle = '<span onclick="updateData(this, \'renderColWorkspaceperms\', '
			.$groupId.', '
			.($open?'0':'1')
			.');" style="cursor: pointer;">'
			. $icon . $title 
			.'</span>';
		
		return $toggle . $content;
	}
	
	function renderColWorkspacememship($groupId, $open = false, $backPath = '') {
		$content  = '';
		$backPath = $backPath ? $backPath : $GLOBALS['SOBE']->doc->backPath;
		$title    = $GLOBALS['LANG']->getLL('showCol-workspacememship');		
		$icon     = '<img'.t3lib_iconWorks::skinImg($backPath, 'gfx/ol/'.($open?'minus':'plus').'bullet.gif','width="18" height="16"').' alt="" />';
		
		if($open) {
			$content .= '<br />';
			$userAuthGroup = t3lib_div::makeInstance('t3lib_userAuthGroup');
				//get workspace perms
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
							'workspace_perms',
							'be_groups',
							'uid = '.$groupId
						);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$userAuthGroup->groupData['workspace_perms'] = $row['workspace_perms'];
		
				// Create accessible workspace arrays:
			$options = array();
			if ($userAuthGroup->checkWorkspace(array('uid' => 0)))	{
				$options[0] = '0: [LIVE]';
			}
			if ($userAuthGroup->checkWorkspace(array('uid' => -1)))	{
				$options[-1] = '-1: [Default Draft]';
			}
				// Add custom workspaces (selecting all, filtering by BE_USER check):
			$workspaces = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,title,adminusers,members,reviewers,db_mountpoints','sys_workspace','pid=0'.t3lib_BEfunc::deleteClause('sys_workspace'),'','title');
			if (count($workspaces))	{
				foreach ($workspaces as $rec)	{
					if ($userAuthGroup->checkWorkspace($rec))	{
						$options[$rec['uid']] = $rec['uid'].': '.$rec['title'];
	
							// Check if all mount points are accessible, otherwise show error:
						if (trim($rec['db_mountpoints'])!=='')	{
							$mountPoints = t3lib_div::intExplode(',',$userAuthGroup->workspaceRec['db_mountpoints'],1);
							foreach($mountPoints as $mpId)	{
								if (!$userAuthGroup->isInWebMount($mpId,'1=1'))	{
									$options[$rec['uid']].= '<br> \- WARNING: Workspace Webmount page id "'.$mpId.'" not accessible!';
								}
							}
						}
					}
				}
			}
			$content .= implode('<br />', $options);
		}
		
		$toggle = '<span onclick="updateData(this, \'renderColWorkspacememship\', '
			.$groupId.', '
			.($open?'0':'1')
			.');" style="cursor: pointer;">'
			. $icon . $title 
			.'</span>';
		
		return $toggle . $content;
	}
	
	function renderColDescription($groupId, $open = false, $backPath = '') {
		$content  = '';
		$backPath = $backPath ? $backPath : $GLOBALS['SOBE']->doc->backPath;
		$title    = $GLOBALS['LANG']->getLL('showCol-description');		
		$icon     = '<img'.t3lib_iconWorks::skinImg($backPath, 'gfx/ol/'.($open?'minus':'plus').'bullet.gif','width="18" height="16"').' alt="" />';
		
		if($open) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'description',
				'be_groups',
				'uid = '.$groupId
			);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$content .= '<br />';
			
			$content .= '<pre>'.$row['description'].'</pre><br />'."\n";
		}
		
		$toggle = '<span onclick="updateData(this, \'renderColDescription\', '
			.$groupId.', '
			.($open?'0':'1')
			.');" style="cursor: pointer;">'
			. $icon . $title 
			.'</span>';
		
		return $toggle . $content;
	}
	
	function renderColModules($groupId, $open = false, $backPath = '') {
		$content  = '';
		$backPath = $backPath ? $backPath : $GLOBALS['SOBE']->doc->backPath;
		$title    = $GLOBALS['LANG']->getLL('showCol-modules');		
		$icon     = '<img'.t3lib_iconWorks::skinImg($backPath, 'gfx/ol/'.($open?'minus':'plus').'bullet.gif','width="18" height="16"').' alt="" />';
		
		if($open) {
			$content .='<br />'; 
			$tceForms = t3lib_div::makeInstance('t3lib_TCEforms');
			$tceFoms->backPath = $backPath;
			$TCAconf = $GLOBALS['TCA']['be_groups']['columns']['groupMods'];
			$table = 'be_groups';
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				$table,
				'uid = '.$groupId
			);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$allMods = $tceForms->addSelectOptionsToItemArray($tceForms->initItemArray($TCAconf),$TCAconf,$tceForms->setTSconfig($table,$row),'groupMods');

			$items = array();
			foreach($allMods as $id => $modsInfo){
				if(t3lib_div::inList($row['groupMods'],$modsInfo[1])){
					$modIcon = t3lib_TCEforms::getIcon($modsInfo[2]);
					$items[] = '<img src="'.$backPath.$modIcon[0].'" '.$modIcon[1][3].'/> '.$modsInfo[0];
				}
			}
			$content .= implode('<br />',$items);
		}
		
		$toggle = '<span onclick="updateData(this, \'renderColModules\', '
			.$groupId.', '
			.($open?'0':'1')
			.');" style="cursor: pointer;">'
			. $icon . $title 
			.'</span>';
		
		return $toggle . $content;
	}
	
	function renderColTsconfig($groupId, $open = false, $backPath = '') {
		$content  = '';
		$backPath = $backPath ? $backPath : $GLOBALS['SOBE']->doc->backPath;
		$title    = $GLOBALS['LANG']->getLL('showCol-tsconfig');		
		$icon     = '<img'.t3lib_iconWorks::skinImg($backPath, 'gfx/ol/'.($open?'minus':'plus').'bullet.gif','width="18" height="16"').' alt="" />';
		
		if($open) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'TSconfig',
				'be_groups',
				'uid = '.$groupId
			);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			
			$TSconfig = t3lib_div::intExplode(',', $row['TSconfig']);
			$content .= '<pre>'.$row['TSconfig'].'</pre><br />'."\n";
		}
		
		$toggle = '<span onclick="updateData(this, \'renderColTsconfig\', '
			.$groupId.', '
			.($open?'0':'1')
			.');" style="cursor: pointer;">'
			. $icon . $title 
			.'</span>';
		
		return $toggle . $content;
	}
	
	function renderColTsconfighl($groupId, $open = false, $backPath = '') {
		$content  = '';
		$backPath = $backPath ? $backPath : $GLOBALS['SOBE']->doc->backPath;
		$title    = $GLOBALS['LANG']->getLL('showCol-tsconfighl');		
		$icon     = '<img'.t3lib_iconWorks::skinImg($backPath, 'gfx/ol/'.($open?'minus':'plus').'bullet.gif','width="18" height="16"').' alt="" />';
		
		if($open) {
			$tsparser = t3lib_div::makeInstance('t3lib_TSparser');
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'TSconfig',
				'be_groups',
				'uid = '.$groupId
			);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$content = $tsparser->doSyntaxHighlight($row['TSconfig'],'',1);
		}
		
		$toggle = '<span onclick="updateData(this, \'renderColTsconfighl\', '
			.$groupId.', '
			.($open?'0':'1')
			.');" style="cursor: pointer;">'
			. $icon . $title 
			.'</span>';
		
		return $toggle . $content;
	}
	
	function renderColMembers($groupId, $open = false, $backPath = '') {
		$content  = '';
		$backPath = $backPath ? $backPath : $GLOBALS['SOBE']->doc->backPath;
		$title    = $GLOBALS['LANG']->getLL('showCol-members');		
		$icon     = '<img'.t3lib_iconWorks::skinImg($backPath, 'gfx/ol/'.($open?'minus':'plus').'bullet.gif','width="18" height="16"').' alt="" />';
		
		$this->backPath = $backPath;
		$this->table = 'be_users';
		if($open) {
			$content .= '<br />';
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				'be_users',
				'usergroup like '.$GLOBALS['TYPO3_DB']->fullQuoteStr('%'.$groupId.'%','be_users').t3lib_befunc::deleteClause('be_users')
			);
			$members = array();
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)){
				if (t3lib_div::inList($row['usergroup'],$groupId)){
					//$members[] = $row;
					$fmIcon = t3lib_iconWorks::getIconImage(
						'be_users', 
						$row,
						$backPath
					);
					$members[] = '<tr><td>'.$fmIcon.' '.$row['realName'].' ('.$row['username'].')</td><td>'.$this->makeUserControl($row).'</td></tr>';
				}
			}
			$content .= '<table>'.implode('', $members).'</table>';
		}
		
		$toggle = '<span onclick="updateData(this, \'renderColMembers\', '
			.$groupId.', '
			.($open?'0':'1')
			.');" style="cursor: pointer;">'
			. $icon . $title 
			.'</span>';
		
		return $toggle . $content;
	}

	/**
	 * from mod4/index.php
	 */
	function editOnClick($params,$backPath='',$requestUri='')	{
		$retUrl = 'returnUrl='.($requestUri==-1?"'+T3_THIS_LOCATION+'":rawurlencode($requestUri?$requestUri:t3lib_div::getIndpEnv('REQUEST_URI')));
		return "window.location.href='".$backPath."index.php?".$retUrl.$params."'; return false;";
	}
	
	function makeUserControl($userRecord) {
		$doc = t3lib_div::makeInstance('template');
		$doc->backPath = $this->backPath;
		
		$this->calcPerms = $GLOBALS['BE_USER']->calcPerms($this->pageinfo);
		$permsEdit = $this->calcPerms&16;

		if($this->table == 'be_users' && $permsEdit){
				// edit
			$control = '<a href="#" onclick="'.htmlspecialchars(
				$this->editOnClick(
					'&edit['.$this->table.']['.$userRecord['uid'].']=edit&SET[function]=edit',
					'../mod2/',
					t3lib_div::getIndpEnv('REQUEST_URI')
				)
			).'"><img'.t3lib_iconWorks::skinImg(
				$this->backPath,
				'gfx/edit2.gif',
				'width="11" height="12"'
			).' title="edit" alt="" /></a>'.chr(10);
		}
		
			//info
		$control .= '<a href="#" onclick="'.htmlspecialchars('top.launchView(\''.$this->table.'\', \''.$userRecord['uid'].'\'); return false;').'">'.
			'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/zoom2.gif','width="12" height="12"').' title="" alt="" />'.
			'</a>'.chr(10);
			
			// hide/unhide
		$hiddenField = $GLOBALS['TCA'][$this->table]['ctrl']['enablecolumns']['disabled'];
		if ($permsEdit){
			if ($userRecord[$hiddenField])	{
				$params = '&data['.$this->table.']['.$userRecord['uid'].']['.$hiddenField.']=0';
				$control .='<a href="#" onclick="'.htmlspecialchars('return jumpToUrl(\''.$doc->issueCommand($params,-1).'\');').'">'.
						'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/button_unhide.gif','width="11" height="10"').' title="unhide" alt="" />'.
						'</a>'.chr(10);
			} else {
				$params = '&data['.$this->table.']['.$userRecord['uid'].']['.$hiddenField.']=1';
				$control .= '<a href="#" onclick="'.htmlspecialchars('return jumpToUrl(\''.$doc->issueCommand($params,-1).'\');').'">'.
						'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/button_hide.gif','width="11" height="10"').' title="hide" alt="" />'.
						'</a>'.chr(10);
			}
		}
		
			// delete
		if($permsEdit){
			$params = '&cmd['.$this->table.']['.$userRecord['uid'].'][delete]=1';
			$control .= '<a href="#" onclick="'.htmlspecialchars('if (confirm('.
				$GLOBALS['LANG']->JScharCode(
					$GLOBALS['LANG']->getLL('deleteWarning')
						.t3lib_BEfunc::referenceCount(
							$this->table,
							$userRecord['uid'],
							' (There are %s reference(s) to this record!)'
						)
					).')) {jumpToUrl(\''.$doc->issueCommand($params,-1).'\');} return false;'
				).'">'.
				'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/garbage.gif','width="11" height="12"').' title="'.$GLOBALS['LANG']->getLL('delete',1).'" alt="" />'.
				'</a>'.chr(10);
		}

			// swith user / switch user back
		if($this->table == 'be_users' && $permsEdit && $GLOBALS['BE_USER']->isAdmin() ){
			if ($userRecord[$hiddenField]){
				$control .= '<img '.t3lib_iconWorks::skinImg($this->backPath,'gfx/su.gif').' border="0" align="top" title="'.htmlspecialchars('Switch user to: '.$userRecord['username']).' [change-to mode]" alt="" />'.
						'<img '.t3lib_iconWorks::skinImg($this->backPath,'gfx/su_back.gif').' border="0" align="top" title="'.htmlspecialchars('Switch user to: '.$userRecord['username']).' [switch-back mode]" alt="" />'
						.chr(10).chr(10);
			} else {
				$control .= '<a href="'.t3lib_div::linkThisScript(array('SwitchUser'=>$userRecord['uid'])).'" target="_top"><img '.t3lib_iconWorks::skinImg($this->backPath,'gfx/su.gif').' border="0" align="top" title="'.htmlspecialchars('Switch user to: '.$userRecord['username']).' [change-to mode]" alt="" /></a>'.
						'<a href="'.t3lib_div::linkThisScript(array('SwitchUser'=>$userRecord['uid'], 'switchBackUser' => 1)).'" target="_top"><img '.t3lib_iconWorks::skinImg($this->backPath,'gfx/su_back.gif').' border="0" align="top" title="'.htmlspecialchars('Switch user to: '.$userRecord['username']).' [switch-back mode]" alt="" /></a>'
						.chr(10).chr(10);
			}

		}
	
		return $control;	
	}
	
	function getGroupTree($groupId) {
		
		$treeStartingPoint  = $groupId;
		$treeStartingRecord = t3lib_BEfunc::getRecord('be_groups', $treeStartingPoint);
		$depth = 10;
		
			// Initialize tree object:
		$tree = t3lib_div::makeInstance('tx_tcbeuser_groupTree');
		$tree->init('');
		$tree->expandAll = true;
		
			// Creating top icon; the main group
		$HTML = t3lib_iconWorks::getIconImage('be_groups', $treeStartingRecord, $GLOBALS['BACK_PATH'],'align="top"');
		$tree->tree[] = array(
			'row' => $treeStartingRecord,
			'HTML' => $HTML
		);
		
		$dataTree = array();
		$dataTree[$groupId] = $tree->buildTree($groupId);
	
		$tree->setDataFromArray($dataTree);	
		
			// Create the tree from starting point:
		if ($depth > 0)	{
			$tree->getTree($treeStartingPoint, $depth);
		}
			
		return $tree->tree;
	}
	
	function wrapTd($str, $tdParams = '', $class = '', $style = '') {
		return "\t".'<td'
			.($tdParams ? ' '.$tdParams : '')
			.($class ? ' class="'.$class.'"' : '')
			.' style="vertical-align: top;'.($style ? ' '.$style : '').'">'.$str.'</td>'."\n";
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tc_beuser/class.tx_tcbeuser_overview.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tc_beuser/class.tx_tcbeuser_overview.php']);
}

?>
