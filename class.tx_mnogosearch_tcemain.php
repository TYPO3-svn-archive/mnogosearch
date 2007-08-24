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
	 * Hooks to data change procedure to watch modified data.
	 *
	 * @param	string	$status	Record status (new or update)
	 * @param	string	$table	Table name
	 * @param	integer	$id	Record ID
	 * @param	array	$fieldArray	Modified fields
	 * @param	object	$pObj	Reference to TCEmain
	 */
	function processDatamap_postProcessFieldArray($status, $table, $id, &$fieldArray, &$pObj) {
		// Only for LIVE records!
		if ($pObj->BE_USER->workspace == 0) {
			if ($table == 'pages') {
				if (!t3lib_div::testInt($id)) {
					$this->log('Table is \'pages\' and id is not int');
					return;
				}
				$pid = $id;
			}
			else {
				$pid = $fieldArray['pid'];
				if ($pid) {
					// search for pid
					if (strstr($pid, 'NEW')) {
						$this->log('$pid is \'NEW...\'', $pid);
						if ($pid{0} === '-') {
							$negFlag = -1; $pid = substr($pid, 1);
						}
						else {
							$negFlag = 1;
						}
						if (isset($pObj->substNEWwithIDs[$pid])) {
							$pid = intval($negFlag*$pObj->substNEWwithIDs[$pid]);
						} else {
							$this->log('$fieldArray[\'pid\'] is \'NEW...\' but not in substNEWwithIDs, aborting');
							return;
						}	// If not found in the substArray we must stop the process...
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
			$this->log('processDatamap_postProcessFieldArray', array(
							'status' => $status,
							'table' => $table,
							'id' => $id,
							'fieldArray' => $fieldArray,
							'pid' => $pid));
			if ($pid) {
				$this->processPid($pid, $pObj);
			}
			// Process any other page that may display results. This is important for
			// records that reside in sysfolder but shown on other pages. Normally
			// in this case cache for displaying page is cleared automatically.
			// So we just put such pages into list for reindexing
			list($tscPID) = t3lib_BEfunc::getTSCpid($table,$uid,'');
			$TSConfig = $this->getTCEMAIN_TSconfig($tscPID);
			if ($TSConfig['clearCacheCmd'])	{
				$pidList = array_unique(t3lib_div::trimExplode(',', $TSConfig['clearCacheCmd'], 1));
				foreach($pidList as $pid) {
					$pid = intval($pid);
					if ($pid) {
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
	function processPid($pid, &$pObj) {
		$this->log('Page id=' . $pid);
		if (!$this->pageAlreadyInLog($pid)) {
			// Check that page is not hidden and regular page
			if ($this->canIndexPage($pid)) {
				// Attempt to find page path
				$sysconf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mnogosearch']);
				$this->log('Page id=' . $pid . ', sysconf dump', $sysconf);
				$path = '';
				if ($sysconf['enableFEcheck'] != 1) {
					$path = $this->findPathByRealUrl($pid);
					$this->log('findPathByRealUrl (pid=' . $pid . ') returns \'' . $path . '\'');
				}
				if (!$path && $sysconf['enableFEcheck']) {
					$path = $this->createPathUsingFE($pid);
					$this->log('createPathUsingFE (pid=' . $pid . ') returns \'' . $path . '\'');
				}
				if (!$path) {
					$path = $this->createDefaultPath($pid);
					$this->log('createDefaultPath (pid=' . $pid . ') returns \'' . $path . '\'');
				}
				if ($path) {
					// TODO Check that this is one of indexed pages!
					$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
						'COUNT(*) AS counter', 'tx_mnogosearch_indexconfig',
						'INSTR(' . $GLOBALS['TYPO3_DB']->fullQuoteStr($path, 'tx_mnogosearch_indexconfig') . ',tx_mnogosearch_url) > 0' .
						t3lib_BEfunc::deleteClause('tx_mnogosearch_indexconfig')
					);
					$this->log('index conf check (pid=' . $pid . ')', $rows);
					if ($rows[0]['counter'] > 0) {
							// Insert into log
						$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_mnogosearch_urllog', array(
									'pid' => 0,
									'tstamp' => time(),
									'crdate' => time(),
									'cruser_id' => $pObj->userid,
									'tx_mnogosearch_url' => $path,
									'tx_mnogosearch_pid' => $pid
								)
							);
					}
				}
			}
			else {
				$this->log('Page id=' . $pid . ' cannot be indexed');
			}
		}
		else {
			$this->log('Page id=' . $pid . ' is already in log');
		}
	}

	/**
	 * Checks if page is already in log table.
	 *
	 * @param	int	$pid Page UID
	 * @return	boolean	<code>true</code> if page is in the table
	 */
	function pageAlreadyInLog($pid) {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tx_mnogosearch_pid', 'tx_mnogosearch_urllog', 'tx_mnogosearch_pid=' . intval($pid));
		$ar = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		return ($ar !== false);
	}

	/**
	 * Attempts to find path by examining RealURL caches.
	 *
	 * @param	int	$pid	Page UID
	 * @return	string	Path (empty if not found)
	 */
	function findPathByRealUrl($pid) {
		$result = '';
		if (t3lib_extMgm::isLoaded('realurl')) {
			$sysconf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mnogosearch']);
			$cacheList = t3lib_div::trimExplode(',', $sysconf['realUrlLookup']);
			foreach ($cacheList as $cache) {
				switch ($cache) {
					case 'path':
						$pid_field = 'page_id';
						$rootpage_field = 'rootpage_id';
						$path_field = 'pagepath';
						$table = 'tx_realurl_pathcache';
						break;
					case 'encode':
						$pid_field = 'page_id';
						$rootpage_field = '';
						$path_field = 'content';
						$table = 'tx_realurl_urlencodecache';
						break;
					case 'decode':
						$pid_field = 'page_id';
						$rootpage_field = 'rootpage_id';
						$path_field = 'spurl';
						$table = 'tx_realurl_urldecodecache';
						break;
					default:
						// unknown!
						continue;
				}
				// Get entries
				$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					$path_field . ($rootpage_field ? ',' . $rootpage_field : '') . ',LENGTH(' . $path_field . ') AS t',
					$table, $pid_field . '=' . $pid . ' AND ' . $path_field . '<>\'\'', '', 't', 1);
				if (count($rows)) {
					$path = $rows[0][$path_field];
					if ($path) {
						$rootpage_id = ($rootpage_field ? $rows[0][$rootpage_field] : 0);
					}
					$result = $this->prefixWithDomainName($path, $pid, $rootpage_id);
				}
				if ($result) {
					break;
				}
			}
		}
		return trim($result);
	}

	/**
	 * Creates default path to page
	 *
	 * @param	int	$pid	Page UID
	 * @return	string	Path (empty if not found)
	 */
	function createDefaultPath($pid, $force = false) {
		if ($force || !t3lib_extMgm::isLoaded('realurl')) {
			$full_path = t3lib_div::getIndpEnv('TYPO3_SITE_URL');
			$parts = parse_url($full_path);
			$path = $this->prefixWithDomainName($parts['path'] . 'index.php?id=' . $pid, $pid);
			$this->log('createDefaultPath', array('path' => $path, 'force' => $force));
			return $path;
		}
		return '';
	}

	function createPathUsingFE($pid) {
		$path = $this->createDefaultPath($pid, true);
		$path .= '&type=231';
		$this->log('createPathUsingFE', array('path' => $path));
		$result = trim(t3lib_div::getURL($path));
		return ($result && preg_match('/^https?:\/\//', $result) ? $result : '');
	}

	/**
	 * Prefixes given path with scheme and domain name. If domain name is not found, return empty path.
	 *
	 * @param	string	$path	Path to prefix
	 * @param	int	$pid	Page UID
	 * @param	int	$rootpage_id	Root page UID for given page
	 * @return	string	Prefixed path or empty string of domain was not found
	 */
	function prefixWithDomainName($path, $pid, $rootpage_id = '') {
		$domain = '';
		$this->log('prefixWithDomainName', array('TYPO3_SITE_URL' => t3lib_div::getIndpEnv('TYPO3_SITE_URL')));
		if ($rootpage_id) {
			// Search for domain record
			$domain_rec = t3lib_BEfunc::getRecordsByField('sys_domain','pid',$rootpage_id,' AND redirectTo=\'\' AND hidden=0', '', 'sorting');
			if (is_array($domain_rec) && count($domain_rec)) {
				$domain = ereg_replace('\/$', '', $domain_rec[0]['domainName']);
			}
		}
		if (!$domain) {
			// Have to go through rootline
			$domain = t3lib_BEfunc::firstDomainRecord(t3lib_BEfunc::BEgetRootLine($pid));
		}
		if ($domain) {
			$result = (t3lib_div::getIndpEnv('TYPO3_SSL') ? 'https://' : 'http://') . $domain . ($path{0} != '/' ? '/' : '') . $path;
		}
		else {
			$result = t3lib_div::getIndpEnv('TYPO3_SITE_URL') . ($path{0} == '/' ? substr($path, 1) : $path);
		}
		return $result;
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
		$fd = fopen(PATH_site . 'fileadmin/mnogosearch.log', 'a');
		flock($fd, LOCK_EX);
		fprintf($fd, '[%s] "%s"' . ($rec ? ', data: ' . chr(10) . '%s' . chr(10) : '') . chr(10), date('d-m-Y H:i:s'), $msg, print_r($rec, true));
		flock($fd, LOCK_UN);
		fclose($fd);
		//t3lib_div::devLog($msg, 'mnogosearch', 0, $rec);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mnogosearch/class.tx_mnogosearch_tcemain.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mnogosearch/class.tx_mnogosearch_tcemain.php']);
}

?>