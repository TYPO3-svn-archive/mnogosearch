<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Dmitry Dulepov <dmitry@typo3.org>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(dirname(__FILE__) . '/class.tx_mnogosearch_results.php');

define('UDM_ENABLED', 1);
define('UDM_DISABLED', 0);

/**
 * Plugin '[mnoGoSearch] Search form' for the 'mnogosearch' extension.
 *
 * @author	Dmitry Dulepov <dmitry@typo3.org>
 * @package	TYPO3
 * @subpackage	tx_mnogosearch
 */
class tx_mnogosearch_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_mnogosearch_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_mnogosearch_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'mnogosearch';	// The extension key.
	var $renderer = false;
	var $udmApiVersion;
	var $highlightParts = array('', '');
	var $sysconf;

	function init() {
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		//$this->pi_USER_INT_obj=1;	// Configuring so caching is not expected. This value means that no cHash params are ever set. We do this, because it's a USER_INT object!
		$this->pi_initPIflexForm();
		$this->pi_checkCHash = false;

		// Check mnoGoSearch plugin
		if (!extension_loaded('mnogosearch')) {
			return $this->pi_getLL('no.mnogosearch');
		}
		if (($this->udmApiVersion = Udm_Api_Version()) < 30204) {
			return $this->pi_getLL('mnogosearch.too.old');
		}

		// Get configuration
		$this->sysconf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mnogosearch']);

		$renderer = intval($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'field_templateMode', 'sTmpl'));
		$renderer_fileref = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_mnogosearch/pi1/class.tx_mnogosearch_pi1']['renderers'][$renderer];
		$this->renderer = t3lib_div::getUserObj($renderer_fileref);
		if (!$this->renderer->init($this)) {
			return $this->pi_getLL('cannot.create.renderer');
		}
	}

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content, $conf)	{
		$this->conf = $conf;

		if ('' != ($error = $this->init())) {
			return $this->pi_wrapInBaseClass($error);
		}

		switch (intval($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'field_mode'))) {
			case 0:
				$content = $this->renderer->render_simpleSearchForm();
				break;
			case 1:
				$content = $this->renderer->render_searchForm();
				break;
			case 2:
				$content = '';
				if ($this->piVars['q'] || $this->sysconf['testTemplateMode']) {
					$result = ($this->sysconf['testTemplateMode'] ? $this->getTestResults() : $this->search());
					if (is_string($result)) {
						$content = $result;
					}
					else {
						$content = $this->renderer->render_searchResults($result);
					}
				}
				break;
		}

		return $this->pi_wrapInBaseClass($content);
	}

	/**
	 * Searches for a query phrase
	 *
	 * @return	mixed	Returns found records or string in case of error
	 */
	function search() {

		// Allocate and setup agent
		$udmAgent = ($this->udmApiVersion >= 30211 ?
						Udm_Alloc_Agent_Array(array($this->sysconf['dbaddr'])) :
						Udm_Alloc_Agent($this->sysconf['dbaddr']));
		if (!$udmAgent) {
			return $this->pi_getLL('agent.alloc.failure');
		}
		if ('' != ($error = $this->setUpAgent($udmAgent))) {
		    Udm_Free_Agent($udmAgent);
			return $error;
		}

		// Search
		$res = Udm_Find($udmAgent, $this->piVars['q']);
		if (!$res) {
			$error = sprintf($this->pi_getLL('mnogosearch.error'), Udm_ErrNo($udmAgent), Udm_Error($udmAgent));
		    Udm_Free_Agent($udmAgent);
			return $error;
		}
		// Process search results
		$results = new tx_mnogosearch_results;
		$results->init($udmAgent, $res, $this);

		// Do not call Udm_Free_Ispell_data(), otherwise Udm_Free_Agent() will die!
	    Udm_Free_Agent($udmAgent);

	    return $results;
	}

	/**
	 * Sets up mnoGoSearch agent
	 *
	 * @param	resource	$udmAgent	Agent configuration
	 * @return	string	Empty if no error
	 */
	function setUpAgent(&$udmAgent) {
		$val = intval($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'field_resultsPerPage'));
		Udm_Set_Agent_Param($udmAgent, UDM_PARAM_PAGE_SIZE, $val ? $val : 20);
		$this->piVars['page'] = max(0, intval($this->piVars['page']));
		Udm_Set_Agent_Param($udmAgent, UDM_PARAM_PAGE_NUM, $this->piVars['page']);
        $options = intval($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'field_options'));
        Udm_Set_Agent_Param($udmAgent, UDM_PARAM_TRACK_MODE, ($options & 1 ? UDM_ENABLED : UDM_DISABLED));
		Udm_Set_Agent_Param($udmAgent, UDM_PARAM_CACHE_MODE, ($options & 2 ? UDM_ENABLED : UDM_DISABLED));
        Udm_Set_Agent_Param($udmAgent, UDM_PARAM_ISPELL_PREFIXES, ($options & 4 ? UDM_ENABLED : UDM_DISABLED));
        Udm_Set_Agent_Param($udmAgent, UDM_PARAM_CROSS_WORDS, ($options & 8 ? UDM_ENABLED : UDM_DISABLED));

		// LocalCharset
		if ($this->sysconf['LocalCharset'] != '') {
			if (!Udm_Check_Charset($udmAgent, $this->sysconf['LocalCharset'])) {
				return sprintf($this->pi_getLL('bad.local.charset'), $this->sysconf['LocalCharset']);
			}
			Udm_Set_Agent_Param($udmAgent, UDM_PARAM_CHARSET, $this->sysconf['LocalCharset']);
		}
		else {
			Udm_Set_Agent_Param($udmAgent, UDM_PARAM_CHARSET, 'utf-8');
		}

		// BrowserCharset
		$browserCharset = ($GLOBALS['TSFE']->config['config']['renderCharset'] ?
				$GLOBALS['TSFE']->config['config']['renderCharset'] :
					($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] ?
						$GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] :
							($this->sysconf['BrowserCharset'] ? $this->sysconf['BrowserCharset'] : 'utf-8'
							)
					)
				);
		if (!Udm_Check_Charset($udmAgent, $browserCharset)) {
			return sprintf($this->pi_getLL('bad.browser.charset'), $browserCharset);
		}
		Udm_Set_Agent_Param($udmAgent, UDM_PARAM_BROWSER_CHARSET, $browserCharset);

		// Highlight
		if ($this->conf['excerptHighlight']) {
			$this->highlightParts = t3lib_div::trimExplode('|', $this->conf['excerptHighlight']);
			if (count($this->highlightParts) == 2) {
				Udm_Set_Agent_Param($udmAgent, UDM_PARAM_HLBEG, $this->highlightParts[0]);
				Udm_Set_Agent_Param($udmAgent, UDM_PARAM_HLEND, $this->highlightParts[1]);
			}
		}

		$q_string = '';
		foreach ($this->piVars as $key => $val) {
			$q_string .= '&' . $key . '=' . urlencode($val);
		}
		$q_string = substr($q_string, 1);

		Udm_Set_Agent_Param($udmAgent, UDM_PARAM_QSTRING, $q_string);

        Udm_Set_Agent_Param($udmAgent, UDM_PARAM_REMOTE_ADDR, t3lib_div::getIndpEnv('REMOTE_ADDR'));
		Udm_Set_Agent_Param($udmAgent, UDM_PARAM_QUERY, $this->piVars['q']);
        if ($this->udmApiVersion >= 30207) {
        	Udm_Set_Agent_Param($udmAgent, UDM_PARAM_GROUPBYSITE, UDM_DISABLED);//($options & 16 ? UDM_ENABLED : UDM_DISABLED));
        }
        Udm_Set_Agent_Param($udmAgent, UDM_PARAM_DETECT_CLONES, ($options & 32 ? UDM_ENABLED : UDM_DISABLED));
        Udm_Set_Agent_Param($udmAgent, UDM_PARAM_SEARCH_MODE, UDM_MODE_ALL);	// TODO
//        Udm_Set_Agent_Param($udmAgent, UDM_PARAM_PHRASE_MODE, ($options & 64 ? UDM_ENABLED : UDM_DISABLED));
        Udm_Set_Agent_Param($udmAgent, UDM_PARAM_WORD_MATCH, UDM_MATCH_WORD);
        $val = intval($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'field_minWordLen'));
        Udm_Set_Agent_Param($udmAgent, UDM_PARAM_MIN_WORD_LEN, $val ? $val : 3);
        $val = intval($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'field_maxWordLen'));
        Udm_Set_Agent_Param($udmAgent, UDM_PARAM_MAX_WORD_LEN, $val ? $val : 32);
		Udm_Set_Agent_Param($udmAgent, UDM_PARAM_VARDIR, $this->sysconf['mnoGoSearchPath'] . '/var');
        $val = intval($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'field_excerptSize'));
		Udm_Set_Agent_Param($udmAgent, UDM_PARAM_WEIGHT_FACTOR, 2221);
	   	Udm_Set_Agent_Param_Ex($udmAgent, 'ExcerptSize', $val ? $val : 1024);
        $val = intval($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'field_excerptPadding'));
		Udm_Set_Agent_Param_Ex($udmAgent, 'ExcerptPadding', $val ? $val : 50);
		Udm_Set_Agent_Param_Ex($udmAgent, 'suggest', ($options & 128 ? 'yes' : 'no'));
		//Udm_Set_Agent_Param_Ex($udmAgent, 'sp', '1');

		if ($this->udmApiVersion >= 30214) {
			$val = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'field_sortOrder');
			Udm_Set_Agent_Param($udmAgent, UDM_PARAM_SORT_ORDER, $val ? $val : 'RPD'); // or DRP
		}

		if ($options & 4) {
			$this->loadIspellData($udmAgent);
		}
		if ($this->udmApiVersion >= 30215) {
			Udm_Parse_Query_String($udmAgent, $q_string);
		}
		$error = Udm_Error($udmAgent);
		return $error ? sprintf($this->pi_getLL('mnogosearch.error'), Udm_ErrNo($udmAgent), $error) : '';
	}

	/**
	 * Loads Ispell data from external files
	 *
	 * @param	resource	$udmAgent	Agent
	 */
	function loadIspellData(&$udmAgent) {
		$extraConfig = $this->loadExtraConfig();

		if (!($affix_files = $extraConfig['affix'])) {
			return;
		}

		Udm_Set_Agent_Param($udmAgent, UDM_PARAM_ISPELL_PREFIXES, UDM_ENABLED);

		if (!is_array($affix_files)) {
			$this->loadSpellFile($udmAgent, UDM_ISPELL_TYPE_AFFIX, $affix_files, 0);
		}
		else {
			foreach ($affix_files as $affix_file) {
				$this->loadSpellFile($udmAgent, UDM_ISPELL_TYPE_AFFIX, $affix_file, 0);
			}
		}
		if (!($spell_files = $extraConfig['spell'])) {
			return;
		}
		if (!is_array($spell_files)) {
			$this->loadSpellFile($udmAgent, UDM_ISPELL_TYPE_SPELL, $spell_files, 1);
		}
		else {
			$count = count($spell_files);
			foreach ($spell_files as $spell_file) {
				$this->loadSpellFile($udmAgent, UDM_ISPELL_TYPE_SPELL, $spell_file, (--$count) == 0 ? 1 : 0);
			}
		}
	}

	/**
	 * Loads a single Spell file
	 *
	 * @param	resource	$udmAgent	Agent
	 * @param	int	$fileType	File type
	 * @param	string	$data	Spell file information (see mnoGoSearch docs). Example: <code>en us-ascii /etc/ispell/english.aff</code>
	 * @param	int	$sort	1, if sorting should happen
	 */
	function loadSpellFile(&$udmAgent, $fileType, $data, $sort) {
		list($file, $charset, $lang) = array_reverse(t3lib_div::trimExplode(' ', $data, 1));
		Udm_Load_Ispell_Data($udmAgent, $fileType, $lang, $charset, $file, $sort);
//print_r(array($lang, $charset, $file, $sort, Udm_Error($udmAgent))) . '<br />';
	}

	/**
	 * Loads extra configuration
	 *
	 * @return	array	Key/value pair for configuration
	 */
	function loadExtraConfig() {
		$result = array();
		if ($this->sysconf['IncludeFile']) {
			$lines = @file($this->sysconf['IncludeFile']);
			if (is_array($lines)) {
				foreach ($lines as $line) {
					$line = trim($line);
					if (substr($line, 0, 1) != '#') {
						$parts = explode(' ', $line, 2);
						if (count($parts) == 2) {
							$key = strtolower(trim($parts[0]));
							if (isset($result[$key]) && !is_array($result[$key])) {
								$result[$key] = array($result[$key]);
								$result[$key][] = $parts[1];
							}
							else {
								$result[$key] = $parts[1];
							}
						}
					}
				}
			}
		}
		return $result;
	}

	/**
	 * Creates fake test results.
	 *
	 * @return	tx_mnogosearch_results	Generated results
	 */
	function getTestResults() {
		$result = new tx_mnogosearch_results();
		$result->initTest($this);
		return $result;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mnogosearch/pi1/class.tx_mnogosearch_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mnogosearch/pi1/class.tx_mnogosearch_pi1.php']);
}

?>