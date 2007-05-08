<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE == 'BE')	{
	$extPath = t3lib_extMgm::extPath($_EXTKEY);
	
		// add module before 'Help'
	if (!isset($TBE_MODULES['txtcbeuserM1']))	{
		$temp_TBE_MODULES = array();
		foreach($TBE_MODULES as $key => $val) {
			if ($key == 'help') {
				$temp_TBE_MODULES['txtcbeuserM1'] = '';
				$temp_TBE_MODULES[$key] = $val;
			} else {
				$temp_TBE_MODULES[$key] = $val;
			}
		}

		$TBE_MODULES = $temp_TBE_MODULES;
	}	
	
	t3lib_extMgm::addModule('txtcbeuserM1', '', '', $extPath.'mod1/');
	t3lib_extMgm::addModule('txtcbeuserM1', 'txtcbeuserM2', 'bottom', $extPath.'mod2/');
	t3lib_extMgm::addModule('txtcbeuserM1', 'txtcbeuserM3', 'bottom', $extPath.'mod3/');
	t3lib_extMgm::addModule('txtcbeuserM1', 'txtcbeuserM5', 'bottom', $extPath.'mod5/');
	t3lib_extMgm::addModule('txtcbeuserM1', 'txtcbeuserM4', 'bottom', $extPath.'mod4/');	

		// enabling regular BE users to edit BE users, goups and filemounts
	$GLOBALS['TCA']['be_users']['ctrl']['adminOnly'] = 0;
	$GLOBALS['TCA']['be_groups']['ctrl']['adminOnly'] = 0;
	$GLOBALS['TCA']['sys_filemounts']['ctrl']['adminOnly'] = 0;
	
//wizard for the password generator
$wizConfig = array(
	'type' => 'userFunc',
	'userFunc' => 'EXT:tc_beuser/class.tx_tcbeuser_pwd_wizard.php:tx_tcbeuser_pwd_wizard->main',
	'params' => array('type' => 'password')
);
$confField = 'tx_tcbeuser';	
t3lib_div::loadTCA('be_users');
$TCA['be_users']['columns']['password']['config']['wizards'][$confField] = $wizConfig;
$TCA['be_users']['columns']['usergroup']['config']['itemsProcFunc'] = 'tx_tcbeuser_config->getGroupsID';
$TCA['be_groups']['columns']['subgroup']['config']['itemsProcFunc'] = 'tx_tcbeuser_config->getGroupsID';
}

$tempCol = array(
		'members' => array(
			'label' => 'User',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'be_users',
				'foreign_table_where' => 'ORDER BY username ASC',
				'size' => '10',
				'maxitems' => 100,
				'iconsInOptionTags' => 1,
			)
		)
	);
t3lib_div::loadTCA("be_groups");
t3lib_extMgm::addTCAcolumns("be_groups",$tempCol,1);

unset ($TCA['be_users']['columns']['usergroup']['config']['wizards']);

$TCA['be_users']['columns']['usergroup']['config']['size'] = '10';
?>