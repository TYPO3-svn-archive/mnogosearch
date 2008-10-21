<?php

########################################################################
# Extension Manager/Repository config file for ext: "mnogosearch"
#
# Auto generated 09-10-2008 17:08
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
	'dependencies' => '',
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
	'version' => '1.1.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '4.1.1-0.0.0',
			'php' => '5.1.0-0.0.0',
			'pagebrowse' => '',
		),
		'conflicts' => array(
			'tstidy' => '',
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:31:{s:9:"ChangeLog";s:4:"af04";s:35:"class.tx_mnogosearch_cms_layout.php";s:4:"a98d";s:33:"class.tx_mnogosearch_postproc.php";s:4:"fc25";s:32:"class.tx_mnogosearch_tcemain.php";s:4:"2edb";s:21:"ext_conf_template.txt";s:4:"6583";s:12:"ext_icon.gif";s:4:"208b";s:17:"ext_localconf.php";s:4:"3b6e";s:14:"ext_tables.php";s:4:"f43a";s:14:"ext_tables.sql";s:4:"19e2";s:35:"icon_tx_mnogosearch_indexconfig.gif";s:4:"475a";s:16:"locallang_db.xml";s:4:"540b";s:7:"tca.php";s:4:"fb72";s:23:"cli/cli_mnogosearch.php";s:4:"34a6";s:14:"doc/manual.sxw";s:4:"048d";s:32:"pi1/class.tx_mnogosearch_pi1.php";s:4:"279d";s:36:"pi1/class.tx_mnogosearch_results.php";s:4:"d2ef";s:27:"pi1/ds_long_search_form.xml";s:4:"7d2b";s:25:"pi1/ds_search_results.xml";s:4:"1cdc";s:28:"pi1/ds_short_search_form.xml";s:4:"d825";s:19:"pi1/flexform_ds.xml";s:4:"d56e";s:17:"pi1/locallang.xml";s:4:"62e8";s:47:"pi1/renderers/class.tx_mnogosearch_renderer.php";s:4:"4261";s:51:"pi1/renderers/class.tx_mnogosearch_renderer_mtb.php";s:4:"03d0";s:59:"pi1/renderers/class.tx_mnogosearch_renderer_templavoila.php";s:4:"74cb";s:29:"pi1/templates/search_form.css";s:4:"d41d";s:30:"pi1/templates/search_form.html";s:4:"e6ff";s:20:"pi1/templates/tv.css";s:4:"c9f7";s:21:"pi1/templates/tv.html";s:4:"fe31";s:29:"pi1/templates/tv_exported.xml";s:4:"77c0";s:32:"static/mnoGoSearch/constants.txt";s:4:"29b4";s:28:"static/mnoGoSearch/setup.txt";s:4:"165f";}',
	'suggests' => array(
	),
);

?>