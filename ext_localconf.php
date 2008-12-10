<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

t3lib_extMgm::addPItoST43($_EXTKEY,'pi1/class.tx_mnogosearch_pi1.php', '_pi1', 'list_type', 0);

// Register hook only if our header is present
if (TYPO3_MODE == 'FE' && $_SERVER['HTTP_X_TYPO3_MNOGOSEARCH'] == md5('mnogosearch' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output']['mnogosearch'] = 'EXT:mnogosearch/class.tx_mnogosearch_postproc.php:tx_mnogosearch_postproc->contentPostProcOutput';

}
// Hook for tt_news to set page modification date correctly. This must run outside of the above block because page can be cached and it will not be executed when necessary
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['extraItemMarkerHook'][$_EXTKEY] = 'EXT:' . $_EXTKEY . '/hooks/class.tx_mnogosearch_ttnews.php:&tx_mnogosearch_ttnews';

if (TYPO3_MODE == 'BE') {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['mnogosearch'] = 'EXT:mnogosearch/class.tx_mnogosearch_tcemain.php:tx_mnogosearch_tcemain';
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['mnogosearch'] = 'EXT:mnogosearch/class.tx_mnogosearch_tcemain.php:tx_mnogosearch_tcemain';

	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info']['mnogosearch_pi1'][] = 'EXT:mnogosearch/class.tx_mnogosearch_cms_layout.php:tx_mnogosearch_cms_layout->getExtensionSummary';
}

$TYPO3_CONF_VARS['SC_OPTIONS']['GLOBAL']['cliKeys'][$_EXTKEY] = array('EXT:' . $_EXTKEY . '/cli/cli_mnogosearch.php', '_CLI_mnogosearch');

?>