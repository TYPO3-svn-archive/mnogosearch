<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (1 || extension_loaded('mnogosearch')) {
	t3lib_div::loadTCA('tt_content');
	$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1'] = 'layout,select_key,pages';
	$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1'] = 'pi_flexform';
	t3lib_extMgm::addPiFlexFormValue($_EXTKEY . '_pi1', 'FILE:EXT:mnogosearch/pi1/flexform_ds.xml');

	t3lib_extMgm::addPlugin(array('LLL:EXT:mnogosearch/locallang_db.xml:tt_content.list_type_pi1', $_EXTKEY.'_pi1'),'list_type');

	t3lib_extMgm::addStaticFile($_EXTKEY,'static/mnoGoSearch/', 'mnoGoSearch: base');
	t3lib_extMgm::addStaticFile($_EXTKEY,'static/css_styled_content_additions/', 'mnoGoSearch: respect index flag');
}

$TCA['tx_mnogosearch_indexconfig'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig',
		'label'     => 'tx_mnogosearch_url',
		'type'		=> 'tx_mnogosearch_type',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'sortby' 	=> 'sorting',
		'rootLevel'	=> 1,
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'	=> t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_mnogosearch_indexconfig.gif',
		'typeicon_column'	=> 'tx_mnogosearch_type',
		'typeicons'	=> array(
			0 => 'pages_link.gif',
			1 => 'pages_catalog.gif',
		),
	)
);

$TCA['tx_mnogosearch_urllog'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_urllog',
		'label'     => 'tx_mnogosearch_url',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'rootLevel'	=> 1,
		'readOnly'	=> 1,
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'	=> 'pages.gif',
	)
);

// Adding datastructure
$GLOBALS['TBE_MODULES_EXT']['xMOD_tx_templavoila_cm1']['staticDataStructures'][]=array(
	'title' => 'LLL:EXT:mnogosearch/locallang_db.xml:ds_short_search_form',
	'path' => 'EXT:'.$_EXTKEY.'/pi1/ds_short_search_form.xml',
	'icon' => '',
	'scope' => 0,
	'fileref' => 'EXT:'.$_EXTKEY.'/pi1/templates/tv.html',
);
$GLOBALS['TBE_MODULES_EXT']['xMOD_tx_templavoila_cm1']['staticDataStructures'][]=array(
	'title' => 'LLL:EXT:mnogosearch/locallang_db.xml:ds_long_search_form',
	'path' => 'EXT:'.$_EXTKEY.'/pi1/ds_long_search_form.xml',
	'icon' => '',
	'scope' => 0,
	'fileref' => 'EXT:'.$_EXTKEY.'/pi1/templates/tv.html',
);
$GLOBALS['TBE_MODULES_EXT']['xMOD_tx_templavoila_cm1']['staticDataStructures'][]=array(
	'title' => 'LLL:EXT:mnogosearch/locallang_db.xml:ds_search_results',
	'path' => 'EXT:'.$_EXTKEY.'/pi1/ds_search_results.xml',
	'icon' => '',
	'scope' => 0,
	'fileref' => 'EXT:'.$_EXTKEY.'/pi1/templates/tv.html',
);

?>