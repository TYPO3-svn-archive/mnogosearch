<?php

########################################################################
# Extension Manager/Repository config file for ext: "mnogosearch"
#
# Auto generated 29-10-2008 09:57
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'mnoGoSearch',
	'description' => 'Web site search engine',
	'category' => 'plugin',
	'author' => 'Dmitry Dulepov',
	'author_email' => 'dmitry@typo3.org',
	'shy' => '',
	'dependencies' => 'pagebrowse,pagepath',
	'conflicts' => 'tstidy',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => 'typo3temp/mnogosearch/var',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => 'SIA "ACCIO"',
	'version' => '2.0.2',
	'constraints' => array(
		'depends' => array(
			'typo3' => '4.1.1-4.99.99',
			'php' => '5.1.0-100.0.0',
			'pagebrowse' => '0.5.3-100.0.0',
			'pagepath' => '',
		),
		'conflicts' => array(
			'tstidy' => '',
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:25:{s:9:"ChangeLog";s:4:"dc4a";s:35:"class.tx_mnogosearch_cms_layout.php";s:4:"2e50";s:33:"class.tx_mnogosearch_postproc.php";s:4:"717f";s:32:"class.tx_mnogosearch_tcemain.php";s:4:"fe18";s:21:"ext_conf_template.txt";s:4:"e07c";s:12:"ext_icon.gif";s:4:"208b";s:17:"ext_localconf.php";s:4:"01c9";s:14:"ext_tables.php";s:4:"80b0";s:14:"ext_tables.sql";s:4:"5a5a";s:35:"icon_tx_mnogosearch_indexconfig.gif";s:4:"475a";s:16:"locallang_db.xml";s:4:"9a61";s:7:"tca.php";s:4:"fb72";s:23:"cli/cli_mnogosearch.php";s:4:"8f9b";s:14:"doc/manual.sxw";s:4:"048d";s:44:"model/class.tx_mnogosearch_model_results.php";s:4:"8582";s:32:"pi1/class.tx_mnogosearch_pi1.php";s:4:"1079";s:19:"pi1/flexform_ds.xml";s:4:"dfa0";s:17:"pi1/locallang.xml";s:4:"c89c";s:20:"resources/scripts.js";s:4:"1355";s:20:"resources/styles.css";s:4:"f716";s:23:"resources/template.html";s:4:"a765";s:30:"resources/images/relevance.gif";s:4:"5e01";s:32:"static/mnoGoSearch/constants.txt";s:4:"5927";s:28:"static/mnoGoSearch/setup.txt";s:4:"e35c";s:34:"view/class.tx_mnogosearch_view.php";s:4:"a0bc";}',
	'suggests' => array(
	),
);

?>