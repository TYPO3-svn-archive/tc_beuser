<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Ingo Renner <ingo.renner@dkd.de>
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

	// DEFAULT initialization of a module [BEGIN]
unset($MCONF);
require_once('conf.php');
require_once($BACK_PATH.'init.php');
require_once($BACK_PATH.'template.php');
$extPath = t3lib_extMgm::extPath('tc_beuser');
require_once($extPath.'class.tx_tcbeuser_recordlist.php');
require_once($extPath.'class.tx_tcbeuser_overview.php');
$LANG->includeLLFile('EXT:tc_beuser/mod4/locallang.xml');
require_once(PATH_t3lib.'class.t3lib_scbase.php');
$BE_USER->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.
	// DEFAULT initialization of a module [END]



/**
 * Module 'User / Group Overview' for the 'tc_beuser' extension.
 *
 * @author	Ingo Renner <ingo.renner@dkd.de>
 * @package	TYPO3
 * @subpackage	tx_tcbeuser
 */
class  tx_tcbeuser_module4 extends t3lib_SCbase {
	
	var $content;
	var $doc;
	var $jsCode;
	var $MOD_MENU     = array();
	var $MOD_SETTINGS = array();
	var $pageinfo;
	var $compareFlags;
	var $be_user;
	var $be_group;
	var $table;

	function main() {
		$this->init();
	
		// The page will show only if there is a valid page and if this page may be viewed by the user
		#$this->pageinfo = tx_tcbeuser_access::readPageAccess();
		#$access = is_array($this->pageinfo) ? 1 : 0;	
		
		//TODO more access check!?
		$access = $GLOBALS['BE_USER']->modAccess($this->MCONF, true);	
	
		if ($access || $GLOBALS['BE_USER']->isAdmin()) {
			
			$this->pageinfo['_thePath'] = '/';
			
			if(t3lib_div::_GP('beUser')){
				$this->MOD_SETTINGS['function'] = 2;
			} 
			
			if(t3lib_div::_GP('beGroup')){
				$this->MOD_SETTINGS['function'] = 1;
			}
			
			if($this->MOD_SETTINGS['function'] == 1) {
				$title = $GLOBALS['LANG']->getLL('overview-groups');
			} elseif($this->MOD_SETTINGS['function'] == 2) {
				$title = $GLOBALS['LANG']->getLL('overview-users');
			}
			
			$menu  = t3lib_BEfunc::getFuncMenu(
				$this->id,
				'SET[function]',
				$this->MOD_SETTINGS['function'],
				$this->MOD_MENU['function']
			);
	
			$moduleContent = $this->moduleContent();
			
				// all necessary JS code needs to be set before this line!
			$this->doc->JScode = $this->doc->wrapScriptTags($this->jsCode);
			$this->doc->JScode .= '
					<script src="prototype.js" type="text/javascript"></script>
					<script src="ajax.js" type="text/javascript"></script>';
			
			$this->content  = '';
			$this->content .= $this->doc->startPage($title);
			$this->content .= $this->doc->spacer(5);
			$this->content .= $this->doc->section( 
				'',
				$this->doc->funcMenu(
					$this->doc->header($title),
					$menu
				)
			);
			$this->content .= $this->doc->divider(5);			
			$this->content .= $moduleContent;
			
			if ($GLOBALS['BE_USER']->mayMakeShortcut())	{
				$this->content .= $this->doc->spacer(20).
							$this->doc->section('',$this->doc->makeShortcutIcon('','',$this->MCONF['name']));
			}			
		}
		
		$GLOBALS['BE_USER']->user['admin'] = 0;
	}
	
	function init() {	
		parent::init();
		
		$this->switchUser(t3lib_div::_GP('SwitchUser'));
		
		$this->backPath = $GLOBALS['BACK_PATH'];
		
		$this->doc = t3lib_div::makeInstance('bigDoc');
		$this->doc->backPath = $this->backPath;
		$this->doc->docType  = 'xhtml_trans';
		$this->doc->form = '<form action="" method="post">';	
			// JavaScript
		$this->doc->postCode='
			<script language="javascript" type="text/javascript">
				script_ended = 1;
				if (top.fsMod) top.fsMod.recentIds["web"] = 0;
			</script>
		';
		
		$this->jsCode = '
			script_ended = 0;
			function jumpToUrl(URL)	{
				document.location = URL;
			}
			
			var T3_BACKPATH = \''.$this->doc->backPath.'\';
		';
		$this->jsCode .= $this->doc->redirectUrls(t3lib_div::linkThisScript());
		
		$this->id = 0;
		
			// update compareFlags
		if (t3lib_div::_GP('ads'))	{
			$this->compareFlags = t3lib_div::_GP('compareFlags');
			$GLOBALS['BE_USER']->pushModuleData('txtcbeuserM1_txtcbeuserM4/index.php/compare',$this->compareFlags);
		} else {
			$this->compareFlags = $GLOBALS['BE_USER']->getModuleData('txtcbeuserM1_txtcbeuserM4/index.php/compare','ses');
		}
		
			// Setting return URL
		$this->returnUrl = t3lib_div::_GP('returnUrl');
		$this->retUrl    = $this->returnUrl ? $this->returnUrl : 'dummy.php';
		
			//init user / group
		$beuser = t3lib_div::_GET('beUser');
		if($beuser) {
			$this->be_user = $beuser;
		}
		$begroup = t3lib_div::_GET('beGroup');
		if($begroup) {
			$this->be_group = $begroup;
		}
	}	
	
	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return	void
	 */
	function menuConfig()	{
		$this->MOD_MENU = array (
			'function' => array (
				'1' => $GLOBALS['LANG']->getLL('overview-groups'),
				'2' => $GLOBALS['LANG']->getLL('overview-users'),
			)
		);
		
		$groupOnly = array();
		if($this->MOD_SETTINGS['function'] == 1) { // groups
			$groupOnly['members'] = $GLOBALS['LANG']->getLL('showCol-members');
		}
		
		$groupAndUser = array(
			'filemounts'        => $GLOBALS['LANG']->getLL('showCol-filemounts'),
			'webmounts'         => $GLOBALS['LANG']->getLL('showCol-webmounts'),
			'pagetypes'         => $GLOBALS['LANG']->getLL('showCol-pagetypes'),
			'selecttables'      => $GLOBALS['LANG']->getLL('showCol-selecttables'),
			'modifytables'      => $GLOBALS['LANG']->getLL('showCol-modifytables'),
			'nonexcludefields'  => $GLOBALS['LANG']->getLL('showCol-nonexcludefields'),
			'explicitallowdeny' => $GLOBALS['LANG']->getLL('showCol-explicitallowdeny'),
			'limittolanguages'  => $GLOBALS['LANG']->getLL('showCol-limittolanguages'),
			'workspaceperms'    => $GLOBALS['LANG']->getLL('showCol-workspaceperms'),
			'workspacememship'  => $GLOBALS['LANG']->getLL('showCol-workspacememship'),
			'description'       => $GLOBALS['LANG']->getLL('showCol-description'),
			'modules'           => $GLOBALS['LANG']->getLL('showCol-modules'),
			'tsconfig'          => $GLOBALS['LANG']->getLL('showCol-tsconfig'),
			'tsconfighl'        => $GLOBALS['LANG']->getLL('showCol-tsconfighl'),
		);		
		$this->MOD_MENU['showCols'] = array_merge($groupOnly, $groupAndUser);
			
		parent::menuConfig();
	}
	
	/**
	 * Generates the module content
	 *
	 * @return	void
	 */
	function moduleContent() {
		$content = '';
				
		$debug_content = '<br /><br /><br />This is the GET/POST vars sent to the script:<br />'.
			'GET:'.t3lib_div::view_array($_GET).'<br />'.
			'POST:'.t3lib_div::view_array($_POST).'<br />'.
			'';

		switch((string)$this->MOD_SETTINGS['function'])	{
			case '1':
					// group view					
				$content .= $this->doc->section(
					'', 
					$this->getGroupView($this->be_group)
				);
//				$content .= $debug_content;
			break;
			case '2':
					// user view
				$content .= $this->doc->section(
					'', 
					$this->getUserView($this->be_user)
				);
//				$content .= $debug_content;
			break;
		}
		
		return $content;
	}
	
	function printContent()	{
		$this->content .= $this->doc->endPage();
		echo $this->content;
	}
	
	function getUserView($userUid) {
		$content = '';
		
		if($this->be_user == 0) {
				//warning - no user selected
			$content .= $GLOBALS['LANG']->getLL('select-user');
			
			$this->id = 0;
			$this->search_field = t3lib_div::_GP('search_field');
			$this->pointer = t3lib_div::intInRange(
				t3lib_div::_GP('pointer'),
				0,
				100000
			);
			$this->table = 'be_users';
			
			$dblist = t3lib_div::makeInstance('tx_tcbeuser_recordList');
			$dblist->backPath = $this->doc->backPath;
			$dblist->script = $this->MCONF['script'];
			$dblist->alternateBgColors = true;
			$dblist->userMainGroupOnly = true;
			$dblist->calcPerms = $GLOBALS['BE_USER']->calcPerms($this->pageinfo);
			$dblist->showFields = array('username', 'realName', 'usergroup');
			$dblist->disableControls = array('edit' => true, 'hide' => true, 'delete' => true, 'import' => true); 
			
			//Setup for analyze Icon
			$dblist->analyzeLabel = $GLOBALS['LANG']->sL('LLL:EXT:tc_beuser/mod2/locallang.xml:analyze',1);
			$dblist->analyzeParam = 'beUser';
			
			$dblist->start(0, $this->table, $this->pointer, $this->search_field);
			$dblist->generateList();
			
			$content .= $dblist->HTMLcode ? $dblist->HTMLcode : '<br />'.$GLOBALS['LANG']->sL('LLL:EXT:tc_beuser/mod2/locallang.xml:not-found').'<br />';
			$content .= $dblist->getSearchBox(
				false,
				$GLOBALS['LANG']->sL('LLL:EXT:tc_beuser/mod2/locallang.xml:search-user',1)
			);
		} else {
				//real content
			$this->table = 'be_users';
			$userRecord = t3lib_BEfunc::getRecord($this->table, $userUid);		
			$content .= $this->getColSelector();
			$content .= '<br />';
			$content .= $this->getUserViewHeader($userRecord);
			$userView = t3lib_div::makeInstance('tx_tcbeuser_overview');
			
			//if there is member in the compareFlags array, remove it. There is no 'member' in user view 
			unset($this->compareFlags['members']);
			$content .= $userView->getTable($userRecord, $this->compareFlags);
		}
		
		return $content;
	}
	
	function getGroupView($groupUid) {
		$content = '';
		
		if($this->be_group == 0) {
				//warning - no user selected
			$content .= $GLOBALS['LANG']->getLL('select-group');

			$this->id = 0;
			$this->search_field = t3lib_div::_GP('search_field');
			$this->pointer = t3lib_div::intInRange(
				t3lib_div::_GP('pointer'),
				0,
				100000
			);
			$this->table = 'be_groups';
			
			$dblist = t3lib_div::makeInstance('tx_tcbeuser_recordList');
			$dblist->backPath = $this->doc->backPath;
			$dblist->script = $this->MCONF['script'];
			$dblist->alternateBgColors = true;
			$dblist->userMainGroupOnly = true;
			$dblist->calcPerms = $GLOBALS['BE_USER']->calcPerms($this->pageinfo);
			$dblist->showFields = array('title');
			$dblist->disableControls = array('edit' => true, 'hide' => true, 'delete' => true, 'history' => true, 'new' => true, 'import' => true); 
			
			//Setup for analyze Icon
			$dblist->analyzeLabel = $GLOBALS['LANG']->sL('LLL:EXT:tc_beuser/mod3/locallang.xml:analyze',1);
			$dblist->analyzeParam = 'beGroup';
			
			$dblist->start(0, $this->table, $this->pointer, $this->search_field);
			$dblist->generateList();
			
			$content .= $dblist->HTMLcode ? $dblist->HTMLcode : '<br />'.$GLOBALS['LANG']->sL('LLL:EXT:tc_beuser/mod3/locallang.xml:not-found').'<br />';
			$content .= $dblist->getSearchBox(
				false,
				$GLOBALS['LANG']->sL('LLL:EXT:tc_beuser/mod3/locallang.xml:search-group',1)
			);
			
		} else {
				//real content
			$this->table = 'be_groups';
			$groupRecord = t3lib_BEfunc::getRecord($this->table, $groupUid);	
			$content .= $this->getColSelector();
			$content .= '<br />';
//			$content .= $this->getUserViewHeader($groupRecord);
		
			$userView = t3lib_div::makeInstance('tx_tcbeuser_overview');
			$content .= $userView->getTableGroup($groupRecord, $this->compareFlags);
		}
		
		return $content;
	}
	
	function getColSelector() {
		$content = '';
		$i = 0;
		
		foreach($this->MOD_MENU['showCols'] as $key => $label) {
			$content .= '<span style="display: block; float: left; width: 180px;">'
				.'<input type="checkbox" value="1" name="compareFlags['.$key.']"'.($this->compareFlags[$key]?' checked="checked"':'').' />'
				.'&nbsp;'.$label.'</span> '.chr(10);
			
			$i++;
			if($i == 4) {
				$content .= chr(10).'<br />'.chr(10);
				$i = 0;
			}
		}
		
		$content .= '<br style="clear: left;" /><br />';
		$content .= '<input type="submit" name="ads" value="Update" />';
		$content .= '<br />';
		
		return $content;
	}
	
	function getUserViewHeader($userRecord) {
		$content = '';
		
		$alttext = t3lib_BEfunc::getRecordIconAltText($userRecord, $this->table);
		$recTitle = t3lib_BEfunc::getRecordTitle($this->table, $userRecord);
		
			// icon
		$iconImg = t3lib_iconWorks::getIconImage(
			$this->table, 
			$userRecord,
			$this->backPath,
			'title="'.htmlspecialchars($alttext).'"'
		);		
			// controls
		$control = $this->makeUserControl($userRecord);

		$content .= $iconImg.' '.$recTitle.' '.$control;
		
		return $content;
	}
	
	function makeUserControl($userRecord) {
		
			// edit
		$control = '<a href="#" onclick="'.htmlspecialchars(
			$this->editOnClick(
				'&edit['.$this->table.']['.$userRecord['uid'].']=edit&SET[function]=edit',
				'../mod2/',
				t3lib_div::getIndpEnv('REQUEST_URI').'SET[function]=2'
			)
		).'"><img'.t3lib_iconWorks::skinImg(
			$this->backPath,
			'gfx/edit2.gif',
			'width="11" height="12"'
		).' title="edit" alt="" /></a>'.chr(10);
		
			//info
		$control .= '<a href="#" onclick="'.htmlspecialchars('top.launchView(\''.$this->table.'\', \''.$userRecord['uid'].'\'); return false;').'">'.
			'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/zoom2.gif','width="12" height="12"').' title="" alt="" />'.
			'</a>'.chr(10);
			
			// hide/unhide
		$hiddenField = $GLOBALS['TCA'][$this->table]['ctrl']['enablecolumns']['disabled'];
		if ($userRecord[$hiddenField])	{
			$params = '&data['.$this->table.']['.$userRecord['uid'].']['.$hiddenField.']=0';
			$control .='<a href="#" onclick="'.htmlspecialchars('return jumpToUrl(\''.$this->doc->issueCommand($params,-1).'\');').'">'.
					'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/button_unhide.gif','width="11" height="10"').' title="unhide" alt="" />'.
					'</a>'.chr(10);
		} else {
			$params = '&data['.$this->table.']['.$userRecord['uid'].']['.$hiddenField.']=1';
			$control .= '<a href="#" onclick="'.htmlspecialchars('return jumpToUrl(\''.$this->doc->issueCommand($params,-1).'\');').'">'.
					'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/button_hide.gif','width="11" height="10"').' title="hide" alt="" />'.
					'</a>'.chr(10);
		}
		
			// delete
		$params = '&cmd['.$this->table.']['.$userRecord['uid'].'][delete]=1';
		$control .= '<a href="#" onclick="'.htmlspecialchars('if (confirm('.
			$GLOBALS['LANG']->JScharCode(
				$GLOBALS['LANG']->getLL('deleteWarning')
					.t3lib_BEfunc::referenceCount(
						$this->table,
						$userRecord['uid'],
						' (There are %s reference(s) to this record!)'
					)
				).')) {jumpToUrl(\''.$this->doc->issueCommand($params,-1).'\');} return false;'
			).'">'.
			'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/garbage.gif','width="11" height="12"').' title="'.$GLOBALS['LANG']->getLL('delete',1).'" alt="" />'.
			'</a>'.chr(10);
			
			// swith user / switch user back
		if( ! $userRecord[$hiddenField] && $GLOBALS['BE_USER']->isAdmin() ){
			$control .= '<a href="'.t3lib_div::linkThisScript(array('SwitchUser'=>$userRecord['uid'])).'" target="_top"><img '.t3lib_iconWorks::skinImg($this->backPath,'gfx/su.gif').' border="0" align="top" title="'.htmlspecialchars('Switch user to: '.$userRecord['username']).' [change-to mode]" alt="" /></a>'.
						'<a href="'.t3lib_div::linkThisScript(array('SwitchUser'=>$userRecord['uid'], 'switchBackUser' => 1)).'" target="_top"><img '.t3lib_iconWorks::skinImg($this->backPath,'gfx/su_back.gif').' border="0" align="top" title="'.htmlspecialchars('Switch user to: '.$userRecord['username']).' [switch-back mode]" alt="" /></a>'
						.chr(10).chr(10);
		}
	
		return $control;	
	}
	
	/**
	 * ingo.renner@dkd.de: from t3lib_BEfunc, modified
	 * 
	 * Returns a JavaScript string (for an onClick handler) which will load the alt_doc.php script that shows the form for editing of the record(s) you have send as params.
	 * REMEMBER to always htmlspecialchar() content in href-properties to ampersands get converted to entities (XHTML requirement and XSS precaution)
	 * Usage: 35
	 *
	 * @param	string		$params is parameters sent along to alt_doc.php. This requires a much more details description which you must seek in Inside TYPO3s documentation of the alt_doc.php API. And example could be '&edit[pages][123]=edit' which will show edit form for page record 123.
	 * @param	string		$backPath must point back to the TYPO3_mainDir directory (where alt_doc.php is)
	 * @param	string		$requestUri is an optional returnUrl you can set - automatically set to REQUEST_URI.
	 * @return	string
	 * @see template::issueCommand()
	 */
	function editOnClick($params,$backPath='',$requestUri='')	{
		$retUrl = 'returnUrl='.($requestUri==-1?"'+T3_THIS_LOCATION+'":rawurlencode($requestUri?$requestUri:t3lib_div::getIndpEnv('REQUEST_URI')));
		return "window.location.href='".$backPath."index.php?".$retUrl.$params."'; return false;";
	}
	
	/**
	 * [Describe function...]
	 * ingo.renner@dkd.de: from tools/beusers
	 *
	 * @param	[type]		$switchUser: ...
	 * @return	[type]		...
	 */
	function switchUser($switchUser)	{
		$uRec=t3lib_BEfunc::getRecord('be_users',$switchUser);
		if (is_array($uRec) && $GLOBALS['BE_USER']->isAdmin())	{
			$updateData['ses_userid'] = $uRec['uid'];
				// user switchback
			if (t3lib_div::_GP('switchBackUser'))	{
				$updateData['ses_backuserid'] = intval($GLOBALS['BE_USER']->user['uid']);
			}
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('be_sessions', 'ses_id='.$GLOBALS['TYPO3_DB']->fullQuoteStr($GLOBALS['BE_USER']->id, 'be_sessions').' AND ses_name=\'be_typo_user\' AND ses_userid='.intval($GLOBALS['BE_USER']->user['uid']),$updateData);

			header('Location: '.t3lib_div::locationHeaderUrl($GLOBALS['BACK_PATH'].'index.php'.($GLOBALS['TYPO3_CONF_VARS']['BE']['interfaces']?'':'?commandLI=1')));
			exit;
		}
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tc_beuser/mod4/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tc_beuser/mod4/index.php']);
}


if(t3lib_div::_POST('ajaxCall')) {
	$method   = t3lib_div::_POST('method');
	$groupId  = t3lib_div::_POST('groupId');
	$open     = t3lib_div::_POST('open');
	$backPath = t3lib_div::_POST('backPath');
				
	$userView = t3lib_div::makeInstance('tx_tcbeuser_overview');
	$content  = $userView->handleMethod( $method, $groupId, $open, $backPath );
				
	echo $content;
} else {
	// Make instance:
	$SOBE = t3lib_div::makeInstance('tx_tcbeuser_module4');
	$SOBE->init();
	
	// Include files?
	foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);
	
	$SOBE->main();
	$SOBE->printContent();
}



?>