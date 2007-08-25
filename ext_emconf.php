<?php

########################################################################
# Extension Manager/Repository config file for ext: "mnogosearch"
#
# Auto generated 24-08-2007 22:43
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
	'dependencies' => 'cms,lang',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => 'SIA "ACCIO"',
	'version' => '1.0.0',
	'constraints' => array(
		'depends' => array(
			'cms' => '',
			'lang' => '',
			'typo3' => '4.1.1-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:32:{s:9:"ChangeLog";s:4:"0b90";s:33:"class.tx_mnogosearch_postproc.php";s:4:"5035";s:32:"class.tx_mnogosearch_tcemain.php";s:4:"6160";s:21:"ext_conf_template.txt";s:4:"28f2";s:12:"ext_icon.gif";s:4:"208b";s:17:"ext_localconf.php";s:4:"1a48";s:14:"ext_tables.php";s:4:"2423";s:14:"ext_tables.sql";s:4:"3373";s:35:"icon_tx_mnogosearch_indexconfig.gif";s:4:"475a";s:16:"locallang_db.xml";s:4:"e768";s:7:"tca.php";s:4:"8d56";s:25:"cli/cli_mnogosearch.phpsh";s:4:"ab52";s:12:"cli/conf.php";s:4:"1cdd";s:14:"doc/manual.sxw";s:4:"da08";s:32:"pi1/class.tx_mnogosearch_pi1.php";s:4:"fb93";s:36:"pi1/class.tx_mnogosearch_results.php";s:4:"6c99";s:27:"pi1/ds_long_search_form.xml";s:4:"7d2b";s:25:"pi1/ds_search_results.xml";s:4:"1cdc";s:28:"pi1/ds_short_search_form.xml";s:4:"d825";s:19:"pi1/flexform_ds.xml";s:4:"5b72";s:17:"pi1/locallang.xml";s:4:"39fe";s:47:"pi1/renderers/class.tx_mnogosearch_renderer.php";s:4:"886b";s:51:"pi1/renderers/class.tx_mnogosearch_renderer_mtb.php";s:4:"2df6";s:59:"pi1/renderers/class.tx_mnogosearch_renderer_templavoila.php";s:4:"74cb";s:29:"pi1/templates/search_form.css";s:4:"d41d";s:30:"pi1/templates/search_form.html";s:4:"e0d3";s:20:"pi1/templates/tv.css";s:4:"c9f7";s:21:"pi1/templates/tv.html";s:4:"fe31";s:29:"pi1/templates/tv_exported.xml";s:4:"77c0";s:45:"static/css_styled_content_additions/setup.txt";s:4:"3b23";s:32:"static/mnoGoSearch/constants.txt";s:4:"d41d";s:28:"static/mnoGoSearch/setup.txt";s:4:"4d51";}',
	'suggests' => array(
	),
);

?>