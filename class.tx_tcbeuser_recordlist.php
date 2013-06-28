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

require_once(PATH_t3lib.'class.t3lib_recordlist.php');
require_once($BACK_PATH.'class.db_list.inc');
require_once($BACK_PATH.'class.db_list_extra.inc');
require_once('class.tx_tcbeuser_config.php');

/**
 * class for listing DB tables in tc_beuser
 * $Id$
 *
 * @author Ingo Renner <ingo.renner@dkd.de>
 */
class tx_tcbeuser_recordList extends localRecordList {

	var $showFields;
	var $userMainGroupOnly = false;
	var $hideDisabledRecords = false;
	/**
	 * @var	array	$disableControls: disable particular features (=icons)
	 */
	var $disableControls = array(
		'detail' => false,	// disable detail view
		'import'=> false,	// disable import feature
		'edit' => false,	// disable editing
		'hide' => false,	// disable hiding
		'delete' => false,	// disable deleting
	);

	/**
	 * Traverses the table(s) to be listed and renders the output code for each:
	 * The HTML is accumulated in $this->HTMLcode
	 * Finishes off with a stopper-gif
	 *
	 * @return	void
	 */
	function generateList()	{
		global $TCA;

			// Set page record in header
		$this->pageRecord = t3lib_BEfunc::getRecordWSOL('pages',$this->id);

			// Traverse the TCA table array:
		reset($TCA);
		while (list($tableName)=each($TCA))	{

				// Checking if the table should be rendered:
			if ((!$this->table || $tableName==$this->table) && (!$this->tableList || t3lib_div::inList($this->tableList,$tableName)) && $GLOBALS['BE_USER']->check('tables_select',$tableName))	{		// Checks that we see only permitted/requested tables:

					// Load full table definitions:
				t3lib_div::loadTCA($tableName);

					// Hide tables which are configured via TSConfig not to be shown (also works for admins):
				if (t3lib_div::inList($this->hideTables, $tableName))	continue;

					// iLimit is set depending on whether we're in single- or multi-table mode
				if ($this->table)	{
					$this->iLimit=(isset($TCA[$tableName]['interface']['maxSingleDBListItems'])?intval($TCA[$tableName]['interface']['maxSingleDBListItems']):$this->itemsLimitSingleTable);
				} else {
					$this->iLimit=(isset($TCA[$tableName]['interface']['maxDBListItems'])?intval($TCA[$tableName]['interface']['maxDBListItems']):$this->itemsLimitPerTable);
				}
				if ($this->showLimit)	$this->iLimit = $this->showLimit;

					// Setting fields to select:
				if ($this->allFields)	{
					$fields = $this->makeFieldList($tableName);
					$fields[]='tstamp';
					$fields[]='crdate';
					$fields[]='_PATH_';
					$fields[]='_CONTROL_';
					if (is_array($this->setFields[$tableName]))	{
						$fields = array_intersect($fields,$this->setFields[$tableName]);
					} else {
						$fields = array();
					}
				} elseif(is_array($this->showFields)) {
					$fields = $this->showFields;
				} else {
					$fields = array();
				}

					// Find ID to use (might be different for "versioning_followPages" tables)
				if (intval($this->searchLevels)==0)	{
					if ($TCA[$tableName]['ctrl']['versioning_followPages'] && $this->pageRecord['_ORIG_pid']==-1 && $this->pageRecord['t3ver_swapmode']==0)	{
						$this->pidSelect = 'pid='.intval($this->pageRecord['_ORIG_uid']);
					} else {
						$this->pidSelect = 'pid='.intval($this->id);
					}
				}
					// Finally, render the list:
				$this->HTMLcode.=$this->getTable($tableName, $this->id, implode(',',$fields));
			}
		}
	}

	/**
	 * Creates the listing of records from a single table
	 *
	 * @param	string		Table name
	 * @param	integer		Page id
	 * @param	string		List of fields to show in the listing. Pseudo fields will be added including the record header.
	 * @return	string		HTML table with the listing for the record.
	 */
	function getTable($table,$id,$rowlist)	{
		global $TCA;

			// Loading all TCA details for this table:
		t3lib_div::loadTCA($table);

			// Init
		$addWhere = '';
		$titleCol = $TCA[$table]['ctrl']['label'];
		$thumbsCol = $TCA[$table]['ctrl']['thumbnail'];
		$l10nEnabled = $TCA[$table]['ctrl']['languageField'] && $TCA[$table]['ctrl']['transOrigPointerField'] && !$TCA[$table]['ctrl']['transOrigPointerTable'];

			// Cleaning rowlist for duplicates and place the $titleCol as the first column always!
		$this->fieldArray=array();

			// ingo.renner@dkd.de
		#$this->fieldArray[] = $titleCol;	// Add title column
		$this->fieldArray = explode(',', $rowlist);

		if ($this->localizationView && $l10nEnabled)	{
			$this->fieldArray[] = '_LOCALIZATION_';
			$this->fieldArray[] = '_LOCALIZATION_b';
			$addWhere.=' AND '.$TCA[$table]['ctrl']['languageField'].'<=0';
		}
		if (!t3lib_div::inList($rowlist,'_CONTROL_'))	{
			$this->fieldArray[] = '_CONTROL_';
		}
		if ($this->showClipboard)	{
			$this->fieldArray[] = '_CLIPBOARD_';
		}
		if (!$this->dontShowClipControlPanels)	{
			$this->fieldArray[]='_REF_';
		}
		if ($this->searchLevels)	{
			$this->fieldArray[]='_PATH_';
		}
			// Cleaning up:
		$this->fieldArray=array_unique(array_merge($this->fieldArray,t3lib_div::trimExplode(',',$rowlist,1)));
		if ($this->noControlPanels)	{
			$tempArray = array_flip($this->fieldArray);
			unset($tempArray['_CONTROL_']);
			unset($tempArray['_CLIPBOARD_']);
			$this->fieldArray = array_keys($tempArray);
		}

			// Creating the list of fields to include in the SQL query:
		$selectFields = $this->fieldArray;
		$selectFields[] = 'uid';
		$selectFields[] = 'pid';
		if ($thumbsCol)	$selectFields[] = $thumbsCol;	// adding column for thumbnails
		if ($table=='pages')	{
			if (t3lib_extMgm::isLoaded('cms'))	{
				$selectFields[] = 'module';
				$selectFields[] = 'extendToSubpages';
			}
			$selectFields[] = 'doktype';
		}
		if (is_array($TCA[$table]['ctrl']['enablecolumns']))	{
			$selectFields = array_merge($selectFields,$TCA[$table]['ctrl']['enablecolumns']);
		}
		if ($TCA[$table]['ctrl']['type'])	{
			$selectFields[] = $TCA[$table]['ctrl']['type'];
		}
		if ($TCA[$table]['ctrl']['typeicon_column'])	{
			$selectFields[] = $TCA[$table]['ctrl']['typeicon_column'];
		}
		if ($TCA[$table]['ctrl']['versioningWS'])	{
			$selectFields[] = 't3ver_id';
			$selectFields[] = 't3ver_state';
			$selectFields[] = 't3ver_wsid';
			$selectFields[] = 't3ver_swapmode';		// Filtered out when pages in makeFieldList()
		}
		if ($l10nEnabled)	{
			$selectFields[] = $TCA[$table]['ctrl']['languageField'];
			$selectFields[] = $TCA[$table]['ctrl']['transOrigPointerField'];
		}
		if ($TCA[$table]['ctrl']['label_alt'])	{
			$selectFields = array_merge($selectFields,t3lib_div::trimExplode(',',$TCA[$table]['ctrl']['label_alt'],1));
		}
		$selectFields = array_unique($selectFields);		// Unique list!
		$selectFields = array_intersect($selectFields,$this->makeFieldList($table,1));		// Making sure that the fields in the field-list ARE in the field-list from TCA!
		$selFieldList = implode(',',$selectFields);		// implode it into a list of fields for the SQL-statement.

			// ingo.renner@dkd.de
		if($this->hideDisabledRecords) {
			$addWhere .= ' AND '.$table.'.'
				.$GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled']
				.' = 0';
		}

			//ingo.renner@dkd.de
		if($GLOBALS['BE_USER']->user['admin'] == '0' && $table == 'be_users') {
			$addWhere .= ' AND admin = 0';
			$addWhere .= ' AND username NOT LIKE ("_cli%") ';
		}

		//dkd-kartolo
		//mod2, exclude fe_user which is also be_user
		if($table == 'fe_users'){
			$addWhere .= ' AND username not in '.$this->excludeBE;
		}

		//dkd-kartolo
		//mod3, config dontShowPrefix
		if($table == 'be_groups' && $GLOBALS['BE_USER']->user['admin']!= '1'){
			$groupID = implode(',',tx_tcbeuser_config::showGroupID());
			if(!empty($groupID)){
				$addWhere .= ' AND uid in ('.$groupID.')';
			} else {
				$addWhere .= ' AND uid not in ('.tx_tcbeuser_config::getAllGroupsID().')';
			}
		}

			// Create the SQL query for selecting the elements in the listing:
		$queryParts = $this->makeQueryArray($table, $id,$addWhere,$selFieldList);	// (API function from class.db_list.inc)
		$this->setTotalItems($queryParts);		// Finding the total amount of records on the page (API function from class.db_list.inc)

			// Init:
		$dbCount = 0;
		$out = '';

			// If the count query returned any number of records, we perform the real query, selecting records.
		if ($this->totalItems)	{
			$result = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryParts);
			$dbCount = $GLOBALS['TYPO3_DB']->sql_num_rows($result);
		}

		$LOISmode = $this->listOnlyInSingleTableMode && !$this->table;

			// If any records was selected, render the list:
		if ($dbCount)	{

				// Half line is drawn between tables:
			if (!$LOISmode)	{
				$theData = Array();
				if (!$this->table && !$rowlist)	{
					$theData[$titleCol] = '<img src="clear.gif" width="'.($GLOBALS['SOBE']->MOD_SETTINGS['bigControlPanel']?'230':'350').'" height="1" alt="" />';
					if (in_array('_CONTROL_',$this->fieldArray))	$theData['_CONTROL_']='';
					if (in_array('_CLIPBOARD_',$this->fieldArray))	$theData['_CLIPBOARD_']='';
				}
				$out .= $this->addelement(0,'',$theData,'class="c-table-row-spacer"',$this->leftMargin);
			}

				// Header line is drawn
			$theData = array();
			if ($this->disableSingleTableView)	{
				$theData[$titleCol] = '<span class="c-table">'.$GLOBALS['LANG']->sL($TCA[$table]['ctrl']['title'],1).'</span> ('.$this->totalItems.')';
			} else {
				$theData[$titleCol] = $this->linkWrapTable($table,'<span class="c-table">'.$GLOBALS['LANG']->sL($TCA[$table]['ctrl']['title'],1).'</span> ('.$this->totalItems.') ');
			}
				// CSH:
			$theData[$titleCol].= t3lib_BEfunc::cshItem($table,'',$this->backPath,'',FALSE,'margin-bottom:0px; white-space: normal;');

				// ingo.renner@dkd.de - moving the table title to the first column
			$theData[$this->showFields[0]] = $theData[$titleCol];

			if($this->showFields[0] != $titleCol){
				unset($theData[$titleCol]);
			}

			if ($LOISmode)	{
				$out.='
					<tr>
						<td class="t3-row-header" style="width:95%;">'.$theData[$titleCol].'</td>
					</tr>';

				if ($GLOBALS['BE_USER']->uc["edit_showFieldHelp"])	{
					$GLOBALS['LANG']->loadSingleTableDescription($table);
					if (isset($GLOBALS['TCA_DESCR'][$table]['columns']['']))	{
						$onClick = 'vHWin=window.open(\'view_help.php?tfID='.$table.'.\',\'viewFieldHelp\',\'height=400,width=600,status=0,menubar=0,scrollbars=1\');vHWin.focus();return false;';
						$out.='
					<tr>
						<td class="c-tableDescription">'.t3lib_BEfunc::helpTextIcon($table,'',$this->backPath,TRUE).$GLOBALS['TCA_DESCR'][$table]['columns']['']['description'].'</td>
					</tr>';
					}
				}
			} else {
				$theUpIcon = ($table=='pages'&&$this->id&&isset($this->pageRow['pid'])) ? '<a href="'.htmlspecialchars($this->listURL($this->pageRow['pid'])).'" onclick="setHighlight('.$this->pageRow['pid'].')"><img'.t3lib_iconWorks::skinImg('','gfx/i/pages_up.gif','width="18" height="16"').' title="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.upOneLevel',1).'" alt="" /></a>':'';
				$out .= $this->addelement(
					1,
					$theUpIcon,
					$theData,
					' class="t3-row-header"',
					''
				);
			}

			if (!$LOISmode)	{
					// Fixing a order table for sortby tables
				$this->currentTable = array();
				$currentIdList = array();
				$doSort = ($TCA[$table]['ctrl']['sortby'] && !$this->sortField);

				$prevUid = 0;
				$prevPrevUid = 0;
				$accRows = array();	// Accumulate rows here
				while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))	{
					$accRows[] = $row;
					$currentIdList[] = $row['uid'];
					if ($doSort)	{
						if ($prevUid)	{
							$this->currentTable['prev'][$row['uid']] = $prevPrevUid;
							$this->currentTable['next'][$prevUid] = '-'.$row['uid'];
							$this->currentTable['prevUid'][$row['uid']] = $prevUid;
						}
						$prevPrevUid = isset($this->currentTable['prev'][$row['uid']]) ? -$prevUid : $row['pid'];
						$prevUid=$row['uid'];
					}
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($result);

					// CSV initiated
				if ($this->csvOutput) $this->initCSV();

					// Render items:
				$this->CBnames=array();
				$this->duplicateStack=array();
				$this->eCounter=$this->firstElementNumber;

				$iOut = '';
				$cc = 0;
				foreach($accRows as $row)	{

						// Forward/Backwards navigation links:
					list($flag,$code) = $this->fwd_rwd_nav($table);
					$iOut.=$code;

						// If render item, increment counter and call function
					if ($flag)	{
						$cc++;
						$iOut.= $this->renderListRow($table,$row,$cc,$titleCol,$thumbsCol);

							// If localization view is enabled it means that the selected records are either default or All language and here we will not select translations which point to the main record:
						if ($this->localizationView && $l10nEnabled)	{

								// Look for translations of this record:
							$translations = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
								$selFieldList,
								$table,
								'pid='.$row['pid'].
									' AND '.$TCA[$table]['ctrl']['languageField'].'>0'.
									' AND '.$TCA[$table]['ctrl']['transOrigPointerField'].'='.intval($row['uid']).
									t3lib_BEfunc::deleteClause($table).
									t3lib_BEfunc::versioningPlaceholderClause($table)
							);

								// For each available translation, render the record:
							if (is_array($translations)) {
								foreach($translations as $lRow)	{
									if ($GLOBALS['BE_USER']->checkLanguageAccess($lRow[$TCA[$table]['ctrl']['languageField']]))	{
										$iOut.=$this->renderListRow($table,$lRow,$cc,$titleCol,$thumbsCol,18);
									}
								}
							}
						}
					}

						// Counter of total rows incremented:
					$this->eCounter++;
				}

					// The header row for the table is now created:
				$out.=$this->renderListHeader($table,$currentIdList);
			}

				// The list of records is added after the header:
			$out.=$iOut;

				// ... and it is all wrapped in a table:
			$out='



			<!--
				DB listing of elements:	"'.htmlspecialchars($table).'"
			-->
				<table border="0" cellpadding="0" cellspacing="0" class="typo3-dblist'.($LOISmode?' typo3-dblist-overview':'').'">
					'.$out.'
				</table>';

				// Output csv if...
			if ($this->csvOutput)	$this->outputCSV($table);	// This ends the page with exit.
		}

			// Return content:
		return $out;
	}

	/**
	 * Rendering the header row for a table
	 *
	 * @param	string		Table name
	 * @param	array		Array of the currectly displayed uids of the table
	 * @return	string		Header table row
	 * @access private
	 * @see getTable()
	 */
	function renderListHeader($table,$currentIdList)	{
		global $TCA, $LANG;

			// Init:
		$theData = array();

			// Traverse the fields:
		foreach($this->fieldArray as $fCol)	{

				// Calculate users permissions to edit records in the table:
			$permsEdit = $this->calcPerms & ($table=='pages'?2:16);

			switch((string)$fCol)	{
				case '_PATH_':			// Path
					$theData[$fCol] = '<i>['.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels._PATH_',1).']</i>';
				break;
				case '_REF_':			// References
					// ingo.renner@dkd.de
					// removed as not needed
					#$theData[$fCol] = '<i>['.$LANG->sL('LLL:EXT:lang/locallang_mod_file_list.xml:c__REF_',1).']</i>';
				break;
				case '_LOCALIZATION_':			// Path
					$theData[$fCol] = '<i>['.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels._LOCALIZATION_',1).']</i>';
				break;
				case '_LOCALIZATION_b':			// Path
					$theData[$fCol] = $LANG->getLL('Localize',1);
				break;
				case '_CLIPBOARD_':		// Clipboard:
					$cells=array();

						// If there are elements on the clipboard for this table, then display the "paste into" icon:
					$elFromTable = $this->clipObj->elFromTable($table);
					if (count($elFromTable))	{
						$cells[]='<a href="'.htmlspecialchars($this->clipObj->pasteUrl($table,$this->id)).'" onclick="'.htmlspecialchars('return '.$this->clipObj->confirmMsg('pages',$this->pageRow,'into',$elFromTable)).'">'.
								'<img'.t3lib_iconWorks::skinImg('','gfx/clip_pasteafter.gif','width="12" height="12"').' title="'.$LANG->getLL('clip_paste',1).'" alt="" />'.
								'</a>';
					}

						// If the numeric clipboard pads are enabled, display the control icons for that:
					if ($this->clipObj->current!='normal')	{

							// The "select" link:
						$cells[]=$this->linkClipboardHeaderIcon('<img'.t3lib_iconWorks::skinImg('','gfx/clip_copy.gif','width="12" height="12"').' title="'.$LANG->getLL('clip_selectMarked',1).'" alt="" />',$table,'setCB');

							// The "edit marked" link:
						$editIdList = implode(',',$currentIdList);
						$editIdList = "'+editList('".$table."','".$editIdList."')+'";
						$params='&edit['.$table.']['.$editIdList.']=edit&disHelp=1';
						$cells[]='<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick($params,'',-1)).'">'.
								'<img'.t3lib_iconWorks::skinImg('','gfx/edit2.gif','width="11" height="12"').' title="'.$LANG->getLL('clip_editMarked',1).'" alt="" />'.
								'</a>';

							// The "Delete marked" link:
						$cells[]=$this->linkClipboardHeaderIcon('<img'.t3lib_iconWorks::skinImg('','gfx/garbage.gif','width="11" height="12"').' title="'.$LANG->getLL('clip_deleteMarked',1).'" alt="" />',$table,'delete',sprintf($LANG->getLL('clip_deleteMarkedWarning'),$LANG->sL($TCA[$table]['ctrl']['title'])));

							// The "Select all" link:
						$cells[]='<a href="#" onclick="'.htmlspecialchars('checkOffCB(\''.implode(',',$this->CBnames).'\'); return false;').'">'.
								'<img'.t3lib_iconWorks::skinImg('','gfx/clip_select.gif','width="12" height="12"').' title="'.$LANG->getLL('clip_markRecords',1).'" alt="" />'.
								'</a>';
					} else {
						$cells[]='';
					}
					$theData[$fCol]=implode('',$cells);
				break;
				case '_CONTROL_':		// Control panel:
					// ingo.renner@dkd.de
					// removed as not needed
				break;
				default:			// Regular fields header:
					$theData[$fCol]='';
					if ($this->table && is_array($currentIdList))	{

							// If the numeric clipboard pads are selected, show duplicate sorting link:
						if ($this->clipNumPane()) {
							$theData[$fCol].='<a href="'.htmlspecialchars($this->listURL('',-1).'&duplicateField='.$fCol).'">'.
											'<img'.t3lib_iconWorks::skinImg('','gfx/select_duplicates.gif','width="11" height="11"').' title="'.$LANG->getLL('clip_duplicates',1).'" alt="" />'.
											'</a>';
						}
					}
					$theData[$fCol].=$this->addSortLink($LANG->sL(t3lib_BEfunc::getItemLabel($table,$fCol,'<i>[|]</i>')),$fCol,$table);
				break;
			}
		}
			// Create and return header table row:
		return $this->addelement(1,'',$theData,' class="c-headLine"','');
	}

	/**
	 * Rendering a single row for the list
	 *
	 * @param	string		Table name
	 * @param	array		Current record
	 * @param	integer		Counter, counting for each time an element is rendered (used for alternating colors)
	 * @param	string		Table field (column) where header value is found
	 * @param	string		Table field (column) where (possible) thumbnails can be found
	 * @param	integer		Indent from left.
	 * @return	string		Table row for the element
	 * @access private
	 * @see getTable()
	 */
	function renderListRow($table,$row,$cc,$titleCol,$thumbsCol,$indent=0)	{
		$iOut = '';

		if (strlen($this->searchString))	{	// If in search mode, make sure the preview will show the correct page
			$id_orig = $this->id;
			$this->id = $row['pid'];
		}

			// In offline workspace, look for alternative record:
		t3lib_BEfunc::workspaceOL($table, $row, $GLOBALS['BE_USER']->workspace);

			// Background color, if any:
		$row_bgColor=
			$this->alternateBgColors ?
			(($cc%2)?'' :' class="db_list_alt"') :
			'';

			// Overriding with versions background color if any:
		$row_bgColor = $row['_CSSCLASS'] ? ' class="'.$row['_CSSCLASS'].'"' : $row_bgColor;

		$row_bgColor = 'class="db_list_normal"';

			// Initialization
		$alttext = t3lib_BEfunc::getRecordIconAltText($row,$table);
		$recTitle = t3lib_BEfunc::getRecordTitle($table,$row);

			// Incr. counter.
		$this->counter++;

			// The icon with link
		$this->clickMenuEnabled = 0;
		$iconImg = t3lib_iconWorks::getIconImage($table,$row,$this->backPath,'title="'.htmlspecialchars($alttext).'"'.($indent ? ' style="margin-left: '.$indent.'px;"' : ''));
		$theIcon = $this->clickMenuEnabled ? $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon($iconImg,$table,$row['uid']) : $iconImg;

			// Preparing and getting the data-array
		$theData = array();
		foreach($this->fieldArray as $fCol)	{
			if ($fCol==$titleCol)	{
				if ($GLOBALS['TCA'][$table]['ctrl']['label_alt'] && ($GLOBALS['TCA'][$table]['ctrl']['label_alt_force'] || !strcmp($row[$fCol],'')))	{
					$altFields=t3lib_div::trimExplode(',',$GLOBALS['TCA'][$table]['ctrl']['label_alt'],1);
					$tA=array();
					if ($row[$fCol])	{ $tA[]=$row[$fCol]; }
					while(list(,$fN)=each($altFields))	{
						$t = t3lib_BEfunc::getProcessedValueExtra($table,$fN,$row[$fN],$GLOBALS['BE_USER']->uc['titleLen'],$row['uid']);
						if($t)	{ $tA[] = $t; }
					}
					if ($GLOBALS['TCA'][$table]['ctrl']['label_alt_force'])	{ $t=implode(', ',$tA); }
					if ($t)	{ $recTitle = $t; }
				} else {
					$recTitle = t3lib_BEfunc::getProcessedValueExtra($table,$fCol,$row[$fCol],$GLOBALS['BE_USER']->uc['titleLen'],$row['uid']);
				}
				$theData[$fCol] = $this->linkWrapItems($table,$row['uid'],$recTitle,$row);
			} elseif ($fCol=='pid') {
				$theData[$fCol]=$row[$fCol];
			} elseif ($fCol=='_PATH_') {
				$theData[$fCol]=$this->recPath($row['pid']);
			} elseif ($fCol=='_REF_') {
//				$theData[$fCol]=$this->makeRef($table,$row['uid']);
			} elseif ($fCol=='_CONTROL_') {
				$theData[$fCol]=$this->makeControl($table,$row);
			} elseif ($fCol=='_CLIPBOARD_') {
				$theData[$fCol]=$this->makeClip($table,$row);
			} elseif ($fCol=='_LOCALIZATION_') {
				list($lC1, $lC2) = $this->makeLocalizationPanel($table,$row);
				$theData[$fCol] = $lC1;
				$theData[$fCol.'b'] = $lC2;
			} elseif ($fCol=='_LOCALIZATION_b') {
				// Do nothing, has been done above.
			} elseif($this->userMainGroupOnly && $table == 'be_users' && $fCol == 'usergroup') {
					// ingo.renner@dkd.de
				$theData[$fCol] = htmlspecialchars($this->getUserMainGroup($row[$fCol]));
			} else {
				$theData[$fCol] = $this->linkUrlMail(htmlspecialchars(t3lib_BEfunc::getProcessedValueExtra($table,$fCol,$row[$fCol],100,$row['uid'])),$row[$fCol]);
			}
		}

		if (strlen($this->searchString))	{	// Reset the ID if it was overwritten
			$this->id = $id_orig;
		}

			// Add row to CSV list:
		if ($this->csvOutput) $this->addToCSV($row,$table);

			// Create element in table cells:
		$iOut.=$this->addelement(1,$theIcon,$theData,$row_bgColor);

			// Render thumbsnails if a thumbnail column exists and there is content in it:
		if ($this->thumbs && trim($row[$thumbsCol]))	{
			$iOut.=$this->addelement(4,'', Array($titleCol=>$this->thumbCode($row,$table,$thumbsCol)),$row_bgColor);
		}

			// Finally, return table row element:
		return $iOut;
	}

	function getUserMainGroup($allGroups) {
		$allGroups = explode(',', $allGroups);
		if(!empty($allGroups[0])) {
			$mainGroup = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'title',
				'be_groups',
				'uid = '.$allGroups[0]
			);
		}

		return $mainGroup[0]['title'];
	}

	/**
	 * Creates the search box
	 *
	 * @param	boolean		If true, the search box is wrapped in its own form-tags
	 * @return	string		HTML for the search box
	 */
	function getSearchBox($formFields=1, $label)	{

			// Setting form-elements, if applicable:
		$formElements=array('','');
		if ($formFields)	{
			$formElements=array('<form action="'.htmlspecialchars($this->listURL()).'" method="post">','</form>');
		}

			// Table with the search box:
		$content = '
			'.$formElements[0].'

				<!--
					Search box:
				-->
				<table border="0" cellpadding="0" cellspacing="0" class="bgColor4" id="typo3-dblist-search">
					<tr>
						<td>'.$label.' <input type="text" name="search_field" value="'.htmlspecialchars($this->searchString).'"'.$GLOBALS['TBE_TEMPLATE']->formWidth(10).' /></td>
						<td>&nbsp;<input type="submit" name="search" value="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.search',1).'" /></td>
					</tr>
				</table>
			'.$formElements[1];

		return $content;
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
	 * Creates the control panel for a single record in the listing.
	 *
	 * @param	string		The table
	 * @param	array		The record for which to make the control panel.
	 * @return	string		HTML table with the control panel (unless disabled)
	 */
	function makeControl($table,$row)	{
		global $TCA, $LANG, $SOBE;
		if ($this->dontShowClipControlPanels)	return '';

			// Initialize:
		t3lib_div::loadTCA($table);
		$cells=array();

			// If the listed table is 'pages' we have to request the permission settings for each page:
		if ($table=='pages')	{
			$localCalcPerms = $GLOBALS['BE_USER']->calcPerms(t3lib_BEfunc::getRecord('pages',$row['uid']));
		}

			// This expresses the edit permissions for this particular element:
		$permsEdit = ($table=='pages' && ($localCalcPerms&2)) || ($table!='pages' && ($this->calcPerms&16));

			// "Edit" link: ( Only if permissions to edit the page-record of the content of the parent page ($this->id)
		if ($permsEdit && !$this->disableControls['edit'])	{
			$params='&edit['.$table.']['.$row['uid'].']=edit&SET[function]=edit';
			$cells[]='<a href="#" onclick="'.htmlspecialchars($this->editOnClick($params,'',-1)).'">'.
					'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/edit2'.(!$TCA[$table]['ctrl']['readOnly']?'':'_d').'.gif','width="11" height="12"').' title="'.$LANG->getLL('edit',1).'" alt="" />'.
					'</a>';
		}

			//dkd-kartolo
			//show magnifier (mod4)
		if(!$this->disableControls['detail']){
			$scriptname=str_replace(array('mod2','mod3'),'mod4',t3lib_div::getIndpEnv('SCRIPT_NAME'));

			$cells[]='<a href="#" onclick="window.location.href=\''.$scriptname.'?'.$this->analyzeParam.'='.$row['uid'].'\'">'.
				'<img '.t3lib_iconWorks::skinImg($this->backPath,'gfx/zoom.gif','width="12" height="12"').'title="'.$this->analyzeLabel.'" alt="" />'.
				'</a>';
		}

			//dkd-kartolo
			//show import fe user icon
		if(!$this->disableControls['import']){
			$scriptname = t3lib_div::getIndpEnv('SCRIPT_NAME');
			$params = '&SET[function]=import&feID='.$row['uid'];
			$cells[]='<a href="#" onclick="'.htmlspecialchars($this->editOnClick($params,'',-1)).'">'.
				'<img '.t3lib_iconWorks::skinImg($this->backPath,'gfx/edit2.gif','width="12" height="12"').'title="'.$LANG->getLL('import',1).'" alt="" />'.
				'</a>';
		}

			// If the extended control panel is enabled OR if we are seeing a single table:
		if ($SOBE->MOD_SETTINGS['bigControlPanel'] || $this->table)	{

				// "Info": (All records)
			$cells[]='<a href="#" onclick="'.htmlspecialchars('top.launchView(\''.$table.'\', \''.$row['uid'].'\'); return false;').'">'.
					'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/zoom2.gif','width="12" height="12"').' title="'.$LANG->getLL('showInfo',1).'" alt="" />'.
					'</a>';

				// If the table is NOT a read-only table, then show these links:
			if (!$TCA[$table]['ctrl']['readOnly'])	{

					// "Hide/Unhide" links:
				$hiddenField = $TCA[$table]['ctrl']['enablecolumns']['disabled'];
				if ($permsEdit && $hiddenField && $TCA[$table]['columns'][$hiddenField] && (!$TCA[$table]['columns'][$hiddenField]['exclude'] || $GLOBALS['BE_USER']->check('non_exclude_fields',$table.':'.$hiddenField)) && !$this->disableControls['hide'])	{
					if ($row[$hiddenField])	{
						$params='?data['.$table.']['.$row['uid'].']['.$hiddenField.']=0&SET[function]=action';
						$cells[]='<a href="#" onclick="'.htmlspecialchars('return jumpToUrl(\''.$this->actionOnClick($params,-1).'\');').'">'.
								'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/button_unhide.gif','width="11" height="10"').' title="'.$LANG->getLL('unHide'.($table=='pages'?'Page':''),1).'" alt="" />'.
								'</a>';
					} else {
						$params='?data['.$table.']['.$row['uid'].']['.$hiddenField.']=1&SET[function]=action';
						$cells[]='<a href="#" onclick="'.htmlspecialchars('return jumpToUrl(\''.$this->actionOnClick($params,-1).'\');').'">'.
								'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/button_hide.gif','width="11" height="10"').' title="'.$LANG->getLL('hide'.($table=='pages'?'Page':''),1).'" alt="" />'.
								'</a>';
					}
				}

					// "Delete" link:
				if (
					($table=='pages' && ($localCalcPerms&4)) || ($table!='pages' && ($this->calcPerms&16) && !$this->disableControls['delete'])
					)	{
					$params='?cmd['.$table.']['.$row['uid'].'][delete]=1&SET[function]=action';
					$cells[]='<a href="#" onclick="'.htmlspecialchars('if (confirm('.$LANG->JScharCode($LANG->getLL('deleteWarning').t3lib_BEfunc::referenceCount($table,$row['uid'],' (There are %s reference(s) to this record!)')).')) {jumpToUrl(\''.$this->actionOnClick($params,-1).'\');}').'">'.
							'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/garbage.gif','width="11" height="12"').' title="'.$LANG->getLL('delete',1).'" alt="" />'.
							'</a>';
				}
			}
		}

			// If the record is edit-locked	by another user, we will show a little warning sign:
		if ($lockInfo=t3lib_BEfunc::isRecordLocked($table,$row['uid']))	{
			$cells[]='<a href="#" onclick="'.htmlspecialchars('alert('.$LANG->JScharCode($lockInfo['msg']).');return false;').'">'.
					'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/recordlock_warning3.gif','width="17" height="12"').' title="'.htmlspecialchars($lockInfo['msg']).'" alt="" />'.
					'</a>';
		}


			// Compile items into a DIV-element:
		return '
											<!-- CONTROL PANEL: '.$table.':'.$row['uid'].' -->
											<div class="typo3-DBctrl">'.implode('',$cells).'</div>';
	}

	/**
	 * create link for the hide/unhide and delete icon.
	 * not using tce_db.php, because we need to manipulate user's permission
	 *
	 * @param	string		param with command (hide/unhide, delete) and records id
	 * @param	string		redirect link, after process the command
	 * @return	string		jumpTo URL link with redirect
	 */
	function actionOnClick($params,$requestURI=''){
		$redirect = '&redirect='.($requestURI==-1?"'+T3_THIS_LOCATION+'":rawurlencode($requestURI)).
				'&vC='.rawurlencode($GLOBALS['BE_USER']->veriCode()).
				'&prErr=1&uPT=1';
		return t3lib_div::getIndpEnv('SCRIPT_NAME').$params.$redirect;
	}

	/**
	 * Creates the URL to this script, including all relevant GPvars
	 * Fixed GPvars are id, table, imagemode, returlUrl, search_field, search_levels and showLimit
	 * The GPvars "sortField" and "sortRev" are also included UNLESS they are found in the $exclList variable.
	 *
	 * @param	string		Alternative id value. Enter blank string for the current id ($this->id)
	 * @param	string		Tablename to display. Enter "-1" for the current table.
	 * @param	string		Commalist of fields NOT to include ("sortField" or "sortRev")
	 * @return	string		URL
	 */
	function listURL($altId='',$table=-1,$exclList='')	{
		if ($this->table != 'fe_users'){
			$param = '?id='.(strcmp($altId,'')?$altId:$this->id).
					'&table='.rawurlencode($table==-1?$this->table:$table);
		} else {
			$param = '?';
		}
		return $this->script.
			$param.
			($this->thumbs?'&imagemode='.$this->thumbs:'').
			($this->returnUrl?'&returnUrl='.rawurlencode($this->returnUrl):'').
			($this->searchString?'&search_field='.rawurlencode($this->searchString):'').
			($this->searchLevels?'&search_levels='.rawurlencode($this->searchLevels):'').
			($this->showLimit?'&showLimit='.rawurlencode($this->showLimit):'').
			((!$exclList || !t3lib_div::inList($exclList,'sortField')) && $this->sortField?'&sortField='.rawurlencode($this->sortField):'').
			((!$exclList || !t3lib_div::inList($exclList,'sortRev')) && $this->sortRev?'&sortRev='.rawurlencode($this->sortRev):'')
			;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tc_beuser/class.tx_tcbeuser_recordlist.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tc_beuser/class.tx_tcbeuser_recordlist.php']);
}

?>
