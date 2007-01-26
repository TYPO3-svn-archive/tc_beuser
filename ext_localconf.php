<?php

if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE == 'BE')	{
	require_once( t3lib_extMgm::extPath( $_EXTKEY, 'class.tx_tcbeuser_hooks.php' ) );
	require_once( t3lib_extMgm::extPath( $_EXTKEY, 'class.tx_tcbeuser_config.php' ) );
		//hooks non-admin be_users 
	$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['calcPerms'][] = 'tx_tcbeuser_hooks->fakeAdmin';
}

//registering hooks for be_groups form mod3
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'tx_tcbeuser_hooks';

//registering for hooks
#$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['postProcessValue'][] = 'tx_tcbeuser_hooks->befuncPostProcessValue';

?>