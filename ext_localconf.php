<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

t3lib_extMgm::addPItoST43($_EXTKEY,'pi1/class.tx_mnogosearch_pi1.php', '_pi1', 'list_type', 0);

if (TYPO3_MODE == 'FE') {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all']['mnogosearch'] = 'EXT:mnogosearch/class.tx_mnogosearch_postproc.php:tx_mnogosearch_postproc->contentPostProcAll';
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-cached']['mnogosearch'] = 'EXT:mnogosearch/class.tx_mnogosearch_postproc.php:tx_mnogosearch_postproc->contentPostProcCached';
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output']['mnogosearch'] = 'EXT:mnogosearch/class.tx_mnogosearch_postproc.php:tx_mnogosearch_postproc->contentPostProcOutput';
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageIndexing']['mnogosearch'] = 'EXT:mnogosearch/class.tx_mnogosearch_postproc.php:tx_mnogosearch_postproc';
}

if (TYPO3_MODE == 'BE') {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['mnogosearch'] = 'EXT:mnogosearch/class.tx_mnogosearch_tcemain.php:tx_mnogosearch_tcemain';
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['mnogosearch'] = 'EXT:mnogosearch/class.tx_mnogosearch_tcemain.php:tx_mnogosearch_tcemain';

	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info']['mnogosearch_pi1'][] = 'EXT:mnogosearch/class.tx_mnogosearch_cms_layout.php:tx_mnogosearch_cms_layout->getExtensionSummary';
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_mnogosearch/pi1/class.tx_mnogosearch_pi1']['renderers'] = array(
	'0'=> 'EXT:mnogosearch/pi1/renderers/class.tx_mnogosearch_renderer_mtb.php:tx_mnogosearch_renderer_mtb',
	'1'=> 'EXT:mnogosearch/pi1/renderers/class.tx_mnogosearch_renderer_templavoila.php:tx_mnogosearch_renderer_templavoila',
);

?>