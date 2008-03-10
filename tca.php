<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA['tx_mnogosearch_indexconfig'] = array (
	'ctrl' => $TCA['tx_mnogosearch_indexconfig']['ctrl'],
	'columns' => array (
		'tx_mnogosearch_type' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_type',
			'config' => Array (
				'type' => 'select',
				'items' => array(
					array('LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_type.server', 0),
					array('LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_type.realm', 1),
				),
			)
		),
		'tx_mnogosearch_url' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_url',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'required,trim,nospace,unique',
			)
		),
		'tx_mnogosearch_method' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_method',
			'config' => Array (
				'type' => 'select',
				'items' => array(
					array('', -1),
					array('LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_method.allow', 0),
					array('LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_method.disallow', 1),
					array('LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_method.href_only', 2),
//					array('LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_method.check_only', 3),
					array('LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_method.skip', 4),
				),
			)
		),
		'tx_mnogosearch_subsection' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_subsection',
			'config' => Array (
				'type' => 'select',
				'items' => array(
					array('', -1),
					array('LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_subsection.path', 0),
					array('LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_subsection.site', 1),
					array('LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_subsection.world', 2),
					array('LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_subsection.page', 3),
				),
			)
		),
		'tx_mnogosearch_cmptype' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_cmptype',
			'config' => Array (
				'type' => 'select',
				'items' => array(
					array('', -1),
					array('LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_cmptype.string', 0),
					array('LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_cmptype.regexp', 1),
				),
			)
		),
		'tx_mnogosearch_cmpoptions' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_cmpoptions',
			'config' => Array (
				'type' => 'check',
				'items' => array(
					array('LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_cmpoptions.case', ''),
					array('LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_cmpoptions.match', ''),
				),
				'default' => 0,
				'cols' => 2,
			)
		),
		'tx_mnogosearch_period' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_period',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'trim,int',
				'checkbox' => '0',
				'default' => '0',
				'size' => 5,
			)
		),
		'tx_mnogosearch_additional_config' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_additional_config',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'trim',
				'wizards' => array(
					'_PADDING' => 2,
					'link' => array(
						'type' => 'popup',
						'title' => 'Link',
						'icon' => 'link_popup.gif',
						'script' => 'browse_links.php?mode=wizard&act=file',
						'params' => array(
							'blindLinkOptions' => 'page,url,mail,spec',
						),
						'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1',
					),
				),
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'tx_mnogosearch_type;;;;1-1-1,tx_mnogosearch_url;;;;3-3-3,tx_mnogosearch_method,tx_mnogosearch_subsection;;;;4-4-4,tx_mnogosearch_period, tx_mnogosearch_additional_config'),
		'1' => array('showitem' => 'tx_mnogosearch_type;;;;1-1-1,tx_mnogosearch_url;;;;3-3-3,tx_mnogosearch_method,,tx_mnogosearch_period;;;;4-4-4,tx_mnogosearch_cmptype;;1;;5-5-5, tx_mnogosearch_additional_config'),
	),
	'palettes' => array (
		'1' => array('showitem' => 'tx_mnogosearch_cmpoptions')
	)
);

$TCA['tx_mnogosearch_urllog'] = array (
	'ctrl' => $TCA['tx_mnogosearch_urllog']['ctrl'],
	'columns' => array (
		'tx_mnogosearch_url' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_urllog.tx_mnogosearch_url',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'required,trim,nospace',
			)
		),
		'tx_mnogosearch_pid' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_urllog.tx_mnogosearch_pid',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'pages',
				'minitems' => 1,
				'maxitems' => 1,
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'tx_mnogosearch_url;;;;1-1-1,tx_mnogosearch_pid'),
	),
);
?>