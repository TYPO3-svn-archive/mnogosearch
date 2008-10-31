<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

if (!function_exists('tx_mnogosearch_sortItems')) {

	function tx_mnogosearch_sortItems(array $item1, array $item2) {
		return strcmp($item1[0], $item2[0]);
	}

	/**
	 * Creates a list of tables for table selector
	 *
	 * @param	array	$params	Parameters to the function
	 * @return	void
	 */
	function tx_mnogosearch_listRecordTables(array &$params) {
		$tableNames = array_keys($GLOBALS['TYPO3_DB']->admin_get_tables());
		$tables = array();
		foreach ($tableNames as $name) {
			if ($name != 'tt_content' && preg_match('/^(tt|tx)_/', $name) &&
					isset($GLOBALS['TCA'][$name]) &&
					!$GLOBALS['TCA'][$name]['hideTable'] &&
					strpos($name, 'tx_mnogosearch_') === false) {
				$title = $GLOBALS['LANG']->sL($GLOBALS['TCA'][$name]['ctrl']['title']);
				if ($title) {
					$tables[$title] = array($title, $name, $GLOBALS['TCA'][$name]['ctrl']['iconfile']);
				}
			}
		}
		usort($tables, tx_mnogosearch_sortItems);
		$params['items'] += $tables;
	}

	/**
	 * Creates a list of tables for text fields for the current table
	 *
	 * @param	array	$params	Parameters to the function
	 * @return	void
	 */
	function tx_mnogosearch_listTableFields(array &$params) {
		$tableName = $params['row']['tx_mnogosearch_table'];
		if ($tableName != '') {
			$fieldTypeFilter = ($params['field'] == 'tx_mnogosearch_title_field' ?
					array('input') : array('input', 'text'));
			$fields = array_keys($GLOBALS['TYPO3_DB']->admin_get_fields($tableName));
			t3lib_div::loadTCA($params['row']['tx_mnogosearch_table']);
			$result = array();
			foreach ($fields as $field) {
				if (isset($GLOBALS['TCA'][$tableName]['columns'][$field]) &&
						in_array($GLOBALS['TCA'][$tableName]['columns'][$field]['config']['type'], $fieldTypeFilter)) {
					$title = trim($GLOBALS['LANG']->sL($GLOBALS['TCA'][$tableName]['columns'][$field]['label']), ':');
					$result[$title] = array($title, $field);
				}
			}
			usort($result, tx_mnogosearch_sortItems);
			$params['items'] += $result;
		}
	}
}

$TCA['tx_mnogosearch_indexconfig'] = array(
	'ctrl' => $TCA['tx_mnogosearch_indexconfig']['ctrl'],
	'columns' => array(
		'tx_mnogosearch_type' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_type',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_type.server', 0),
					array('LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_type.realm', 1),
					array('LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_type.records', 11),
				),
			)
		),
		'tx_mnogosearch_url' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_url',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'required,trim,nospace,unique',
			)
		),
		'tx_mnogosearch_method' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_method',
			'config' => array(
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
		'tx_mnogosearch_subsection' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_subsection',
			'config' => array(
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
		'tx_mnogosearch_cmptype' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_cmptype',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', -1),
					array('LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_cmptype.string', 0),
					array('LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_cmptype.regexp', 1),
				),
			)
		),
		'tx_mnogosearch_cmpoptions' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_cmpoptions',
			'config' => array(
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
			'config' => array(
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
			'config' => array(
				'type' => 'text',
				'default' => '',
			)
		),
		// Records
		'tx_mnogosearch_table' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_table',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', ''),
				),
				'itemsProcFunc' => 'tx_mnogosearch_listRecordTables',
			)
		),
		'tx_mnogosearch_title_field' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_title_field',
			'config' => array(
				'type' => 'select',
				'allowNonIdValues' => true,
				'items' => array(
					array('', ''),
				),
				'itemsProcFunc' => 'tx_mnogosearch_listTableFields',
			)
		),
		'tx_mnogosearch_body_field' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_body_field',
			'config' => array(
				'type' => 'select',
				'allowNonIdValues' => true,
				'size' => 5,
				'minitems' => 1,
				'maxitems' => 1000,
				'autoSizeMax' => 10,
				'itemsProcFunc' => 'tx_mnogosearch_listTableFields',
			)
		),
		'tx_mnogosearch_url_parameters' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_url_parameters',
			'config' => array(
				'type' => 'input',
				'eval' => 'trim',
				'default' => '&tx_extkey_pi1[showUid]={field:uid}'
			)
		),
		'tx_mnogosearch_display_pid' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_display_pid',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'pages',
				'minitems' => 1,
				'maxitems' => 1,
				'size' => 1,
				'prepend_tname' => false,
			)
		),
		'tx_mnogosearch_pid_only' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_indexconfig.tx_mnogosearch_pid_only',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'pages',
				'minitems' => 0,
				'size' => 5,
				'autoSizeMax' => 10,
				'prepend_tname' => false,
			)
		),
	),
	'types' => array(
		'0' => array('showitem' => 'tx_mnogosearch_type;;;;1-1-1,tx_mnogosearch_url;;;;3-3-3,tx_mnogosearch_method,tx_mnogosearch_subsection;;;;4-4-4,tx_mnogosearch_period, tx_mnogosearch_additional_config'),
		'1' => array('showitem' => 'tx_mnogosearch_type;;;;1-1-1,tx_mnogosearch_url;;;;3-3-3,tx_mnogosearch_method,tx_mnogosearch_period;;;;4-4-4,tx_mnogosearch_cmptype;;1;;5-5-5, tx_mnogosearch_additional_config'),
		'11' => array('showitem' => 'tx_mnogosearch_type;;;;1-1-1, tx_mnogosearch_table;;;;3-3-3, tx_mnogosearch_title_field, tx_mnogosearch_body_field, tx_mnogosearch_url_parameters, tx_mnogosearch_display_pid, tx_mnogosearch_pid_only, tx_mnogosearch_period;;;;4-4-4 tx_mnogosearch_additional_config'),
	),
	'palettes' => array(
		'1' => array('showitem' => 'tx_mnogosearch_cmpoptions')
	)
);

$TCA['tx_mnogosearch_urllog'] = array(
	'ctrl' => $TCA['tx_mnogosearch_urllog']['ctrl'],
	'columns' => array(
		'tx_mnogosearch_url' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:mnogosearch/locallang_db.xml:tx_mnogosearch_urllog.tx_mnogosearch_url',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'required,trim,nospace',
			)
		),
	),
	'types' => array(
		'0' => array('showitem' => 'tx_mnogosearch_url;;;;1-1-1'),
	),
);

?>