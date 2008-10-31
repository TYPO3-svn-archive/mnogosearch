<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Dmitry Dulepov <dmitry@typo3.org>
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

/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */


/**
 * Page and content manipulation watch.
 *
 * @author	Dmitry Dulepov <dmitry@typo3.org>
 * @package	TYPO3
 * @subpackage	tx_mnogosearch
 */
class tx_mnogosearch_tcemain {

	/**
	 * Extension configuration
	 *
	 * @var	array
	 */
	protected $sysconf = array();

	/**
	 * Creates an instance of this class
	 *
	 * @return	void
	 */
	function __construct() {
		$this->sysconf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mnogosearch']);
	}

	/**
	 * Hooks to data change procedure to watch modified data. This hook is called
	 * before data is written to the database, so all paths are unmodified paths.
	 *
	 * @param	string	$status	Record status (new or update)
	 * @param	string	$table	Table name
	 * @param	integer	$id	Record ID
	 * @param	array	$fieldArray	Modified fields
	 * @param	object	$pObj	Reference to TCEmain
	 */
	public function processDatamap_postProcessFieldArray($status, $table, $id, array $fieldArray, t3lib_TCEmain &$pObj) {
		// Only for LIVE records!
		if ($pObj->BE_USER->workspace == 0) {
			$this->storePageChanges($table, $id, $fieldArray, $pObj, true);
		}
	}

	/**
	 * Hooks to data change procedure to watch modified data. This hook is called
	 * after data is written to the database, so all paths are modified paths.
	 *
	 * @param	string	$status	Record status (new or update)
	 * @param	string	$table	Table name
	 * @param	integer	$id	Record ID
	 * @param	array	$fieldArray	Modified fields
	 * @param	object	$pObj	Reference to TCEmain
	 */
	public function processDatamap_afterDatabaseOperations($status, $table, $id, array $fieldArray, t3lib_TCEmain &$pObj) {
		// Only for LIVE records!
		if ($pObj->BE_USER->workspace == 0) {
			$this->storePageChanges($table, $id, $fieldArray, $pObj, false);
		}
	}

	/**
	 * Processes record change
	 *
	 * @param	string	$table	Table name
	 * @param	mixed	$id	ID of the record (can be also 'NEW...')
	 * @param	array	$fieldArray	Field array
	 * @param	t3lib_TCEmain	$pObj	Parent object
	 * @param 	boolean	$processClearCachePages	If true, clearCache pages are processed for indexing as well
	 * @return	void
	 */
	protected function storePageChanges($table, $id, array $fieldArray, t3lib_TCEmain &$pObj, $processClearCachePages) {
		if ($table == 'pages') {
			if (!t3lib_div::testInt($id)) {
				// Page is just created. We do not index empty pages
				return;
			}
			$pid = $id;
		}
		else {
			$pid = $fieldArray['pid'];
			if ($pid) {
				if (!t3lib_div::testInt($pid)) {
					$negate = ($pid{0} == '-');
					$pid = $pObj->substNEWwithIDs[$negate ? substr($pid, 1) : $pid];
					if (!$pid) {
						return;
					}
					if ($negate) {
						$pid = -$pid;
					}
				}
				if ($pid < 0) {
					$rec = t3lib_BEfunc::getRecord($table, -$pid, 'pid');
					$pid = $rec['pid'];
				}
			}
			else {
				$rec = t3lib_BEfunc::getRecord($table, $id, 'pid');
				$pid = $rec['pid'];
			}
		}
		if ($pid) {
			$this->processPid($pid, $pObj);
		}
		if ($processClearCachePages) {
			// Process any other page that may display results. This is important for
			// records that reside in sysfolder but shown on other pages. Normally
			// in this case cache for displaying page is cleared automatically.
			// So we just put such pages into list for reindexing
			list($tscPID) = t3lib_BEfunc::getTSCpid($table, $id, $pid);
			$TSConfig = $pObj->getTCEMAIN_TSconfig($tscPID);
			if ($TSConfig['clearCacheCmd'])	{
				$pidList = array_unique(t3lib_div::trimExplode(',', $TSConfig['clearCacheCmd'], true));
				foreach($pidList as $pid) {
					if (($pid = intval($pid))) {
						$this->processPid($pid, $pObj);
					}
				}
			}
		}
	}

	/**
	 * Finds URL for page ID and inserts it to URL table.
	 *
	 * @param	int	$pid	Page ID
	 * @param	object	$pObj	Reference to TCEmain
	 */
	protected function processPid($pid, t3lib_TCEmain &$pObj) {
		require_once(t3lib_extMgm::extPath('pagepath', 'class.tx_pagepath_api.php'));
		$pagePath = tx_pagepath_api::getPagePath($pid);
		if ($pagePath && !$this->pageAlreadyInLog($pagePath)) {
			// Check that page is not hidden and regular page
			if ($this->canIndexPage($pid)) {
				// Attempt to find page path
				// Check that this is one of indexed pages!
				$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'COUNT(*) AS counter', 'tx_mnogosearch_indexconfig',
					'INSTR(' . $GLOBALS['TYPO3_DB']->fullQuoteStr($pagePath, 'tx_mnogosearch_indexconfig') . ',tx_mnogosearch_url) > 0' .
					t3lib_BEfunc::deleteClause('tx_mnogosearch_indexconfig')
				);
				if ($rows[0]['counter'] > 0) {
						// Insert into log
					$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_mnogosearch_urllog', array(
								'pid' => 0,
								'tstamp' => time(),
								'crdate' => time(),
								'cruser_id' => $pObj->userid,
								'tx_mnogosearch_url' => $pagePath,
								'tx_mnogosearch_pid' => $pid
							)
						);
				}
			}
		}
	}

	/**
	 * Checks if page is already in log table.
	 *
	 * @param	int	$pid Page UID
	 * @return	boolean	<code>true</code> if page is in the table
	 */
	protected function pageAlreadyInLog($pagePath) {
		list($rec) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('COUNT(*) AS t',
			'tx_mnogosearch_urllog',
			'tx_mnogosearch_url=' .
				$GLOBALS['TYPO3_DB']->fullQuoteStr($pagePath, 'tx_mnogosearch_urllog'));
		return ($rec['t'] > 0);
	}

	/**
	 * Checks if page should be indexed
	 *
	 * @param	int	$pid	Page ID to check
	 * @return	boolean	<code>true</code> if page can be indexed
	 */
	function canIndexPage($pid) {
		$pageRec = t3lib_BEfunc::getRecord('pages', $pid, 'doktype,nav_hide,no_cache,no_search,fe_login_mode');
		$this->log('canIndexPage: Page id=' . $pid, $pageRec);
		return ($pageRec['doktype'] <= 2 && !$pageRec['nav_hide'] && !$pageRec['no_cache']
				&& !$pageRec['no_search'] && !$pageRec['fe_login_mode']);
	}

	/**
	 * Logs messages to dev log
	 *
	 * @param	string	$msg	Message to log
	 * @param	int	$severity	Severity
	 * @param	mixed	$rec	Additional data or <code>false</code> ifno data
	 */
	function log($msg, $rec = false) {
		if ($this->sysconf['debugLog'] == 'file') {
			$fd = fopen(PATH_site . 'fileadmin/mnogosearch.log', 'a');
			flock($fd, LOCK_EX);
			fprintf($fd, '[%s] "%s"' . ($rec ? ', data: ' . chr(10) . '%s' . chr(10) : '') . chr(10), date('d-m-Y H:i:s'), $msg, print_r($rec, true));
			flock($fd, LOCK_UN);
			fclose($fd);
		}
		elseif ($this->sysconf['debugLog'] == 'devlog') {
			if (TYPO3_DLOG) {
				t3lib_div::devLog($msg, 'mnogosearch', 0, $rec);
			}
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mnogosearch/class.tx_mnogosearch_tcemain.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mnogosearch/class.tx_mnogosearch_tcemain.php']);
}

?>