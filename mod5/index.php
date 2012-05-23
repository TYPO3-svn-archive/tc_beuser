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
$LANG->includeLLFile('EXT:tc_beuser/mod5/locallang.xml');
$LANG->includeLLFile('EXT:lang/locallang_alt_doc.xml');
require_once(PATH_t3lib.'class.t3lib_scbase.php');
require_once(PATH_t3lib.'class.t3lib_tceforms.php');
require_once(PATH_t3lib.'class.t3lib_transferdata.php');
require_once(PATH_t3lib.'class.t3lib_tcemain.php');
require_once($extPath.'class.tx_tcbeuser_editform.php');

$BE_USER->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.
	// DEFAULT initialization of a module [END]



/**
 * Module 'User / Group Overview' for the 'tc_beuser' extension.
 *
 * @author	Ingo Renner <ingo.renner@dkd.de>
 * @package	TYPO3
 * @subpackage	tx_tcbeuser
 */
class  tx_tcbeuser_module5 extends t3lib_SCbase {
	var $content;
	var $doc;
	var $jsCode;
	var $MOD_MENU = array();
	var $MOD_SETTINGS = array();
	var $pageinfo;

	function main() {
		$this->init();

		// The page will show only if there is a valid page and if this page may be viewed by the user
		#$this->pageinfo = tx_tcbeuser_access::readPageAccess();
		#$access = is_array($this->pageinfo) ? 1 : 0;

		//TODO more access check!?
		$access = $GLOBALS['BE_USER']->modAccess($this->MCONF, true);

		if ($access || $GLOBALS['BE_USER']->isAdmin()) {

			$title = $GLOBALS['LANG']->getLL('title');
			$menu  = t3lib_BEfunc::getFuncMenu(
				$this->id,
				'SET[function]',
				$this->MOD_SETTINGS['function'],
				$this->MOD_MENU['function']
			);

			$moduleContent = $this->moduleContent();

				// all necessary JS code needs to be set before this line!
			$this->tceforms = t3lib_div::makeInstance('t3lib_TCEforms');
			$this->tceforms->backPath = $GLOBALS['BACK_PATH'];
			$this->doc->JScode = $this->tceforms->JSbottom('editform');
			$this->doc->JScode .= $this->doc->wrapScriptTags($this->jsCode);

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
	}

	/**
	 * First initialization.
	 *
	 * @return	void
	 */
	function preInit()	{
		global $BE_USER;

			// Setting GPvars:
		$this->editconf = t3lib_div::_GP('edit');
		$this->defVals = t3lib_div::_GP('defVals');
		$this->overrideVals = t3lib_div::_GP('overrideVals');
		$this->columnsOnly = t3lib_div::_GP('columnsOnly');
		$this->returnUrl = t3lib_div::_GP('returnUrl');
		$this->closeDoc = t3lib_div::_GP('closeDoc');
		$this->doSave = t3lib_div::_GP('doSave');
		$this->returnEditConf = t3lib_div::_GP('returnEditConf');

			// Setting override values as default if defVals does not exist.
		if (!is_array($this->defVals) && is_array($this->overrideVals))	{
			$this->defVals = $this->overrideVals;
		}

			// Setting return URL
		$this->retUrl = $this->returnUrl ? $this->returnUrl : 'index.php?SET[function]=1';

			// Make R_URL (request url) based on input GETvars:
		$this->R_URL_parts = parse_url(t3lib_div::getIndpEnv('REQUEST_URI'));
		$this->R_URL_getvars = t3lib_div::_GET();
		$this->R_URL_getvars['edit'] = $this->editconf;

		if ($this->closeDoc > 0 )	{
			$this->closeDocument();
		}
	}

	/**
	 * Detects, if a save command has been triggered.
	 *
	 * @return	boolean		True, then save the document (data submitted)
	 */
	function doProcessData()	{
		$out = $this->doSave || isset($_POST['_savedok_x']) || isset($_POST['_saveandclosedok_x']) || isset($_POST['_savedokview_x']) || isset($_POST['_savedoknew_x']);
		return $out;
	}

	/**
	 * Do processing of data, submitting it to TCEmain.
	 *
	 * @return	void
	 */
	function processData()	{
		global $BE_USER,$TYPO3_CONF_VARS;

		if($BE_USER->user['admin'] != 1){
			//make fake Admin
			tx_tcbeuser_config::fakeAdmin();
			$fakeAdmin = 1;
		}

			// GPvars specifically for processing:
		$this->data = t3lib_div::_GP('data');
		$this->cmd = t3lib_div::_GP('cmd')?t3lib_div::_GP('cmd'):array();
		$this->disableRTE = t3lib_div::_GP('_disableRTE');

			//set path to be relative to fileadmin
		if (is_array($this->data)){
			$table = array_keys($this->data);
			$uid = array_keys($this->data[$table[0]]);
			if(!is_numeric($uid)){
				$this->data[$table[0]][$uid[0]]['base']=1;

				//check the new path
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'*',
					$this->table,
					'path = '.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->data[$table[0]][$uid[0]]['path'],$this->table)
				);

				$pathExists = false;
				if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0){
					while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)){
						if($row['uid'] != $uid[0])
							$pathExists = true;
					}
				}
			}
		}
		if ($pathExists) {
			$this->error[] = array('error',$GLOBALS['LANG']->getLL('error-path'));
		} else {
				// See tce_db.php for relevate options here:
				// Only options related to $this->data submission are included here.
			$tce = t3lib_div::makeInstance('t3lib_TCEmain');
			$tce->stripslashes_values=0;

				// Setting default values specific for the user:
			$TCAdefaultOverride = $BE_USER->getTSConfigProp('TCAdefaults');
			if (is_array($TCAdefaultOverride))	{
				$tce->setDefaultsFromUserTS($TCAdefaultOverride);
			}

				// Setting internal vars:
			if ($BE_USER->uc['neverHideAtCopy'])	{	$tce->neverHideAtCopy = 1;	}
			$tce->debug=0;
			$tce->disableRTE = $this->disableRTE;

				// Loading TCEmain with data:
			$tce->start($this->data,$this->cmd);
			if (is_array($this->mirror))	{	$tce->setMirror($this->mirror);	}

				// If pages are being edited, we set an instruction about updating the page tree after this operation.
			if (isset($this->data['pages']))	{
				t3lib_BEfunc::setUpdateSignal('updatePageTree');
			}


				// Checking referer / executing
			$refInfo=parse_url(t3lib_div::getIndpEnv('HTTP_REFERER'));
			$httpHost = t3lib_div::getIndpEnv('TYPO3_HOST_ONLY');
			if ($httpHost!=$refInfo['host'] && $this->vC!=$BE_USER->veriCode() && !$TYPO3_CONF_VARS['SYS']['doNotCheckReferer'])	{
				$tce->log('',0,0,0,1,"Referer host '%s' and server host '%s' did not match and veriCode was not valid either!",1,array($refInfo['host'],$httpHost));
				debug('Error: Referer host did not match with server host.');
			} else {

					// Perform the saving operation with TCEmain:
				$tce->process_uploads($_FILES);
				$tce->process_datamap();
				$tce->process_cmdmap();

					// If there was saved any new items, load them:
				if (count($tce->substNEWwithIDs_table))	{

						// Resetting editconf:
					$this->editconf = array();

						// Traverse all new records and forge the content of ->editconf so we can continue to EDIT these records!
					foreach($tce->substNEWwithIDs_table as $nKey => $nTable)	{
						$editId = $tce->substNEWwithIDs[$nKey];
							// translate new id to the workspace version:
						if ($versionRec = t3lib_BEfunc::getWorkspaceVersionOfRecord($GLOBALS['BE_USER']->workspace, $nTable, $editId,'uid'))	{
							$editId = $versionRec['uid'];
						}

						$this->editconf[$nTable][$editId]='edit';
						if ($nTable=='pages' && $this->retUrl!='dummy.php' && $this->returnNewPageId)	{
							$this->retUrl.='&id='.$tce->substNEWwithIDs[$nKey];
						}
					}
				}

					// See if any records was auto-created as new versions?
				if (count($tce->autoVersionIdMap))	{
					$this->fixWSversioningInEditConf($tce->autoVersionIdMap);
				}

					// If a document is saved and a new one is created right after.
				if (isset($_POST['_savedoknew_x']) && is_array($this->editconf))	{

						// Finding the current table:
					reset($this->editconf);
					$nTable=key($this->editconf);

						// Finding the first id, getting the records pid+uid
					reset($this->editconf[$nTable]);
					$nUid=key($this->editconf[$nTable]);
					$nRec = t3lib_BEfunc::getRecord($nTable,$nUid,'pid,uid');

						// Setting a blank editconf array for a new record:
					$this->editconf=array();
					if ($this->getNewIconMode($nTable)=='top')	{
						$this->editconf[$nTable][$nRec['pid']]='new';
					} else {
						$this->editconf[$nTable][-$nRec['uid']]='new';
					}
				}

				$tce->printLogErrorMessages(
					isset($_POST['_saveandclosedok_x']) ?
					$this->retUrl :
					$this->R_URL_parts['path'].'?'.t3lib_div::implodeArrayForUrl('',$this->R_URL_getvars)	// popView will not be invoked here, because the information from the submit button for save/view will be lost .... But does it matter if there is an error anyways?
				);
			}
			if (isset($_POST['_saveandclosedok_x']) || $this->closeDoc<0)	{	//  || count($tce->substNEWwithIDs)... If any new items has been save, the document is CLOSED because if not, we just get that element re-listed as new. And we don't want that!
				$this->closeDocument(abs($this->closeDoc));
			}
		}

		if($fakeAdmin){
			tx_tcbeuser_config::removeFakeAdmin();
		}
	}


	/**
	 * close the document and send to the previous page
	 */
	function closeDocument(){
		if($this->retUrl == 'dummy.php'){
			$this->retUrl = 'index.php?SET[function]=1';
		}
		$retUrl = explode('/', $this->retUrl);
		$this->retUrl = $retUrl[count($retUrl)-1];
		Header('Location: '.$this->retUrl);
		exit;
	}

	function init() {
		parent::init();

		$this->doc = t3lib_div::makeInstance('bigDoc');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->docType  = 'xhtml_trans';
//		$this->doc->form = '<form action="" method="post">';
		$this->doc->form = '<form action="'.htmlspecialchars($this->R_URI).'" method="post" enctype="'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'].'" name="editform" onsubmit="return TBE_EDITOR_checkSubmit(1);">';
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
		';

		$this->id = 0;
		$this->search_field = t3lib_div::_GP('search_field');
		$this->pointer = t3lib_div::intInRange(
			t3lib_div::_GP('pointer'),
			0,
			100000
		);
		$this->table = 'sys_filemounts';

		$SET = t3lib_div::_GET('SET');
		if($SET['function'] == 'action'){
			$this->MOD_SETTINGS['function'] = $SET['function'];
		}

//			// if going to edit a record, a menu item is dynamicaly added to
//			// the dropdown which is otherwise not visible
//		$SET = t3lib_div::_GET('SET');
//		if(isset($SET['function']) && $SET['function'] == 'edit') {
//			$this->MOD_SETTINGS['function'] = $SET['function'];
//			$this->MOD_MENU['function']['edit'] = $GLOBALS['LANG']->getLL('edit-group');
//			$this->doc->form = '<form action="'.htmlspecialchars($this->R_URI).'" method="post" enctype="'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'].'" name="editform" onsubmit="return TBE_EDITOR_checkSubmit(1);">';
//			$this->editconf = t3lib_div::_GET('edit');
//		}
	}

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return	void
	 */
	function menuConfig()	{
		$this->MOD_MENU = array (
			'function' => array (
				'1' => $GLOBALS['LANG']->getLL('list-filemounts'),
				'2' => $GLOBALS['LANG']->getLL('create-filemount'),
			),
//			'hideDeactivatedUsers' => '0'
		);
		parent::menuConfig();
	}

	/**
	 * Generates the module content
	 *
	 * @return	void
	 */
	function moduleContent() {
		$content = '';

		if(!empty($this->editconf)){
			$this->MOD_SETTINGS['function'] = 'edit';
		}

		switch((string)$this->MOD_SETTINGS['function'])	{
			case '1':
					// list Filemounts
				$content .= $this->doc->section(
					'',
					$this->getFilemountList()
				);
			break;
			case '2':
					// create new Filemount
				$data = t3lib_div::_GP('data');
				$dataKey = is_array($data) ? array_keys($data[$this->table]): array();
				if(is_numeric($dataKey[0])){
					$this->editconf = array($this->table => array($dataKey[0] => 'edit'));
				}else{ // create new user
					$this->editconf = array($this->table => array(0=>'new'));
				}
				$content .= $this->doc->section(
					'',
					$this->getFilemountEdit()
				);
			break;
			case 'edit':
					// edit Filemount
				#$param = t3lib_div::_GET('edit');
				#$beuserUid = array_search('edit', $param['be_users']);

				$content .= $this->doc->section(
					'',
					$this->getFilemountEdit()
				);

			break;
			case 'action':
				$this->processData();
				Header('Location: '.t3lib_div::locationHeaderUrl(t3lib_div::_GP('redirect')));
			break;
		}

		return $content;
	}

	function printContent()	{
		$this->content .= $this->doc->endPage();
		echo $this->content;
	}

	function getFilemountList() {
		$content = '';

		$dblist = t3lib_div::makeInstance('tx_tcbeuser_recordList');
		$dblist->backPath = $this->doc->backPath;
		$dblist->script = t3lib_div::linkThisScript();
		$dblist->alternateBgColors = true;
		$dblist->calcPerms = $GLOBALS['BE_USER']->calcPerms($this->pageinfo);
		$dblist->showFields = array('title', 'path');
		$dblist->disableControls = array('edit' => true, 'detail' => true, 'import' => true);

		$dblist->start(0, $this->table, $this->pointer, $this->search_field);

			// default sorting, needs to be set after $dblist->start()
		$sort = t3lib_div::_GET('sortField');
		if(is_null($sort)) {
			$dblist->sortField = 'title';
		}
		$dblist->generateList();
		$content .= $dblist->HTMLcode;

			// make new user link
		$content .= '<!--
						Link for creating a new record:
					-->
		<div id="typo3-newRecordLink">
		<a href="index.php?SET[function]=2">'.
		'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/new_el.gif','width="11" height="12"').' alt="'.$GLOBALS['LANG']->getLL('create-filemount').'" />'.
		$GLOBALS['LANG']->getLL('create-filemount').
		'</a>';

		$this->jsCode .= $this->doc->redirectUrls($dblist->listURL())."\n";

		return $content;
	}

	function getFilemountEdit() {
		$content = '';

		//show warning
		$this->error[] = array(
			'warning',
			$GLOBALS['LANG']->getLL('filemount-msg')
		);

		$this->tceforms = t3lib_div::makeInstance('t3lib_TCEforms');
		$this->tceforms->backPath = $this->doc->backPath;
		$this->tceforms->initDefaultBEMode();
		$this->tceforms->doSaveFieldName = 'doSave';
		$this->tceforms->localizationMode = t3lib_div::inList('text,media',$this->localizationMode) ? $this->localizationMode : '';	// text,media is keywords defined in TYPO3 Core API..., see "l10n_cat"
		$this->tceforms->returnUrl = $this->R_URI;
		$this->tceforms->disableRTE = true; // not needed anyway, might speed things up

			// Setting external variables:
		#if ($BE_USER->uc['edit_showFieldHelp']!='text')	$this->tceforms->edit_showFieldHelp='text';

			// Creating the editing form, wrap it with buttons, document selector etc.
		$this->editForm = t3lib_div::makeInstance('tx_tcbeuser_editform');
		$this->editForm->tceforms = &$this->tceforms;
		$this->editForm->columnsOnly = 'title,path';
		$this->editForm->editconf = $this->editconf;
		$this->editForm->error = $this->error;
		$this->editForm->inputData = $this->data;

		$editForm = $this->editForm->makeEditForm();//.$GLOBALS['LANG']->getLL('filemount-msg').'<br /><br />';
		$this->viewId = $this->editForm->viewId;

		if ($editForm)	{ // ingo.renner@dkd.de
			reset($this->editForm->elementsData);
			$this->firstEl = current($this->editForm->elementsData);

			if ($this->viewId)	{
					// Module configuration:
				$this->modTSconfig = t3lib_BEfunc::getModTSconfig($this->viewId,'mod.xMOD_alt_doc');
			} else $this->modTSconfig=array();

			$panel  = $this->makeButtonPanel();
			$formContent = $this->compileForm($panel,'','',$editForm);

			$content .= $this->tceforms->printNeededJSFunctions_top().
								$formContent.
								$this->tceforms->printNeededJSFunctions();
			#$this->tceformMessages();
		}

		return $content;
	}

	/**
	 * ingo.renner@dkd.de: from alt_doc.php, modified
	 *
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return	string		HTML code, comprised of images linked to various actions.
	 */
	function makeButtonPanel()	{
		global $TCA,$LANG;

		$panel='';

			// Render SAVE type buttons:
			// The action of each button is decided by its name attribute. (See doProcessData())
		if (!$this->errorC && !$TCA[$this->firstEl['table']]['ctrl']['readOnly'])	{

				// SAVE button:
			$panel.= '<input type="image" class="c-inputButton" name="_savedok"'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/savedok.gif','').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:rm.saveDoc',1).'" />';

				// SAVE / CLOSE
			$panel.= '<input type="image" class="c-inputButton" name="_saveandclosedok"'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/saveandclosedok.gif','').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:rm.saveCloseDoc',1).'" />';
		}

			// CLOSE button:
		$panel.= '<a href="#" onclick="document.editform.closeDoc.value=1; document.editform.submit(); return false;">'.
				'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/closedok.gif','width="21" height="16"').' class="c-inputButton" title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:rm.closeDoc',1).'" alt="" />'.
				'</a>';

			// UNDO buttons:
		if (!$this->errorC && !$TCA[$this->firstEl['table']]['ctrl']['readOnly'] && count($this->elementsData)==1)	{
			if ($this->firstEl['cmd']!='new' && t3lib_div::testInt($this->firstEl['uid']))	{

					// Undo:
				$undoButton = 0;
				$undoRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tstamp', 'sys_history', 'tablename='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->firstEl['table'], 'sys_history').' AND recuid='.intval($this->firstEl['uid']), '', 'tstamp DESC', '1');
				if ($undoButtonR = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($undoRes))	{
					$undoButton = 1;
				}
				if ($undoButton) {
					$aOnClick = 'window.location.href=\'show_rechis.php?element='.rawurlencode($this->firstEl['table'].':'.$this->firstEl['uid']).'&revert=ALL_FIELDS&sumUp=-1&returnUrl='.rawurlencode($this->R_URI).'\'; return false;';
					$panel.= '<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.
							'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/undo.gif','width="21" height="16"').' class="c-inputButton" title="'.htmlspecialchars(sprintf($LANG->getLL('undoLastChange'),t3lib_BEfunc::calcAge(time()-$undoButtonR['tstamp'],$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.minutesHoursDaysYears')))).'" alt="" />'.
							'</a>';
				}

					// If only SOME fields are shown in the form, this will link the user to the FULL form:
				if ($this->columnsOnly)	{
					$panel.= '<a href="'.htmlspecialchars($this->R_URI.'&columnsOnly=').'">'.
							'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/edit2.gif','width="11" height="12"').' class="c-inputButton" title="'.$LANG->getLL('editWholeRecord',1).'" alt="" />'.
							'</a>';
				}
			}
		}
		return $panel;
	}

	/**
	 * Put together the various elements (buttons, selectors, form) into a table
	 *
	 * @param	string		The button panel HTML
	 * @param	string		Document selector HTML
	 * @param	string		Clear-cache menu HTML
	 * @param	string		HTML form.
	 * @param	string		Language selector HTML for localization
	 * @return	string		Composite HTML
	 */
	function compileForm($panel,$docSel,$cMenu,$editForm, $langSelector='')	{
		global $LANG;


		$formContent='';
		$formContent.='

			<!--
			 	Header of the editing page.
				Contains the buttons for saving/closing, the document selector and menu selector.
				Shows the path of the editing operation as well.
			-->
			<table border="0" cellpadding="0" cellspacing="1" width="470" id="typo3-altdoc-header">
				<tr>
					<td nowrap="nowrap" valign="top">'.$panel.'</td>
					<td nowrap="nowrap" valign="top" align="right">'.$docSel.$cMenu.'</td>
				</tr>';

		if ($langSelector) {
			$langSelector ='<div id="typo3-altdoc-lang-selector">'.$langSelector.'</div>';
		}
		$pagePath = '<div id="typo3-altdoc-page-path">'.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.path',1).': '.htmlspecialchars($this->generalPathOfForm).'</div>';

		$formContent.='
				<tr>
					<td colspan="2"><div id="typo3-altdoc-header-info-options">'.$pagePath.$langSelector.'<div></td>
				</tr>
			</table>




			<!--
			 	EDITING FORM:
			-->

			'.$editForm.'



			<!--
			 	Saving buttons (same as in top)
			-->

			'.$panel.
			'<input type="hidden" name="returnUrl" value="'.htmlspecialchars($this->retUrl).'" />
			<input type="hidden" name="viewUrl" value="'.htmlspecialchars($this->viewUrl).'" />';

		if ($this->returnNewPageId)	{
			$formContent.='<input type="hidden" name="returnNewPageId" value="1" />';
		}
		$formContent.='<input type="hidden" name="popViewId" value="'.htmlspecialchars($this->viewId).'" />';
		if ($this->viewId_addParams) {
			$formContent.='<input type="hidden" name="popViewId_addParams" value="'.htmlspecialchars($this->viewId_addParams).'" />';
		}
		$formContent.='<input type="hidden" name="closeDoc" value="0" />';
		$formContent.='<input type="hidden" name="doSave" value="0" />';
		$formContent.='<input type="hidden" name="_serialNumber" value="'.md5(microtime()).'" />';
		$formContent.='<input type="hidden" name="_disableRTE" value="'.$this->tceforms->disableRTE.'" />';

		return $formContent;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tc_beuser/mod5/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tc_beuser/mod5/index.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('tx_tcbeuser_module5');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->preInit();
if ($SOBE->doProcessData())	{		// Checks, if a save button has been clicked (or the doSave variable is sent)
	$SOBE->processData();
}

$SOBE->main();
$SOBE->printContent();

?>