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

	var $sysconf = array();

	function tx_mnogosearch_tcemain() {
		$this->sysconf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mnogosearch']);
	}

	function __construct() {
		$this->tx_mnogosearch_tcemain();
	}

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
			list($tscPID) = t3lib_BEfunc::getTSCpid($table, $id, $pid);
			$TSConfig = $pObj->getTCEMAIN_TSconfig($tscPID);
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
				$this->log('Page id=' . $pid . ', sysconf dump', $this->sysconf);
				$path = '';
				if ($this->sysconf['enableFEcheck']) {
					$path = $this->createPathUsingFE($pid);
					$this->log('createPathUsingFE (pid=' . $pid . ') returns \'' . $path . '\'');
				}
				if (!$path) {
					$path = $this->createDefaultPath($pid);
					$this->log('createDefaultPath (pid=' . $pid . ') returns \'' . $path . '\'');
				}
				if ($path) {
					// Check that this is one of indexed pages!
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
		list($rec) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('COUNT(*) AS t', 'tx_mnogosearch_urllog', 'tx_mnogosearch_pid=' . intval($pid));
		return ($rec['t'] > 0);
	}

	/**
	 * Creates default path to page
	 *
	 * @param	int	$pid	Page UID
	 * @return	string	Path (empty if not found)
	 */
	function createDefaultPath($pid) {
		$full_path = t3lib_div::getIndpEnv('TYPO3_SITE_URL');
		$parts = parse_url($full_path);
		$path = $this->prefixWithDomainName($parts['path'] . 'index.php?id=' . $pid, $pid);
		$this->log('createDefaultPath', array('path' => $path));
		return $path;
	}

	/**
	 * Creates path by simulating FE environment. Idea credits go to vara_feurlfrombe.
	 *
	 * @param	int	$pid	Page UID
	 * @return	string	URL
	 */
	function createPathUsingFE($pid) {
		require_once(PATH_site.'typo3/sysext/cms/tslib/class.tslib_fe.php');
		require_once(PATH_site.'t3lib/class.t3lib_userauth.php');
		require_once(PATH_site.'typo3/sysext/cms/tslib/class.tslib_feuserauth.php');
		require_once(PATH_site.'t3lib/class.t3lib_cs.php');
		require_once(PATH_site.'typo3/sysext/cms/tslib/class.tslib_content.php') ;
		require_once(PATH_site.'t3lib/class.t3lib_tstemplate.php');
		require_once(PATH_site.'t3lib/class.t3lib_page.php');
		require_once(PATH_site.'t3lib/class.t3lib_timetrack.php');

		// Finds the TSFE classname
		$TSFEclassName = t3lib_div::makeInstanceClassName('tslib_fe');

		// Create the TSFE class.
		$this->log('CP1');
		$TYPO3_CONF_VARS = $GLOBALS['TYPO3_CONF_VARS'];
		unset($TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkAlternativeIdMethods-PostProc']);
		$GLOBALS['TSFE'] = new $TSFEclassName($TYPO3_CONF_VARS, $pid, '0', 0, '','','','');

		$this->log('CP2');
		$temp_TTclassName = t3lib_div::makeInstanceClassName('t3lib_timeTrack');
		$GLOBALS['TT'] = new $temp_TTclassName();
		$this->log('CP3');
		$GLOBALS['TT']->start();
		$this->log('CP4');

		$GLOBALS['TSFE']->config['config']['language']='default';

		// Fire all the required function to get the typo3 FE all set up.
		$GLOBALS['TSFE']->id = $pid;
		$this->log('CP5');
		$GLOBALS['TSFE']->connectToMySQL();

		// Prevent mysql debug messages from messing up the output
		$sqlDebug = $GLOBALS['TYPO3_DB']->debugOutput;
		$GLOBALS['TYPO3_DB']->debugOutput = false;

		$this->log('CP6');
		$GLOBALS['TSFE']->initLLVars();
		$this->log('CP7');
		$GLOBALS['TSFE']->initFEuser();

		// Look up the page
		$this->log('CP8');
		$GLOBALS['TSFE']->sys_page = t3lib_div::makeInstance('t3lib_pageSelect');
		$this->log('CP9');
		$GLOBALS['TSFE']->sys_page->init($GLOBALS['TSFE']->showHiddenPage);

		// If the page is not found (if the page is a sysfolder, etc), then return no URL, preventing any further processing which would result in an error page.
		$this->log('CP10');
		$page = $GLOBALS['TSFE']->sys_page->getPage($pid);

		if (count($page) == 0) {
			$GLOBALS['TYPO3_DB']->debugOutput = $sqlDebug;
			return '';
		}

		// If the page is a shortcut, look up the page to which the shortcut references, and do the same check as above.
		if ($page['doktype']==4 && count($GLOBALS['TSFE']->getPageShortcut($page['shortcut'],$page['shortcut_mode'],$page['uid'])) == 0) {
			$GLOBALS['TYPO3_DB']->debugOutput = $sqlDebug;
			return '';
		}

		// Spacer pages and sysfolders result in a page not found page too...
		if ($page['doktype'] == 199 || $page['doktype'] == 254) {
			$GLOBALS['TYPO3_DB']->debugOutput = $sqlDebug;
			return '';
		}

		$this->log('CP11');
		$GLOBALS['TSFE']->getPageAndRootline();
		$this->log('CP12');
		$GLOBALS['TSFE']->initTemplate();
		$this->log('CP13');
		$GLOBALS['TSFE']->forceTemplateParsing = 1;

		// Find the root template
		$this->log('CP14');
		$GLOBALS['TSFE']->tmpl->start($GLOBALS['TSFE']->rootLine);

		// Fill the pSetup from the same variables from the same location as where tslib_fe->getConfigArray will get them, so they can be checked before this function is called
		$GLOBALS['TSFE']->sPre = $GLOBALS['TSFE']->tmpl->setup['types.'][$GLOBALS['TSFE']->type];	 // toplevel - objArrayName
		$GLOBALS['TSFE']->pSetup = $GLOBALS['TSFE']->tmpl->setup[$GLOBALS['TSFE']->sPre.'.'];

		// If there is no root template found, there is no point in continuing which would result in a 'template not found' page and then call exit php. Then there would be no clickmenu at all.
		// And the same applies if pSetup is empty, which would result in a "The page is not configured" message.
		if (!$GLOBALS['TSFE']->tmpl->loaded || ($GLOBALS['TSFE']->tmpl->loaded && !$GLOBALS['TSFE']->pSetup)) {
			$GLOBALS['TYPO3_DB']->debugOutput = $sqlDebug;
			return '';
		}

		$this->log('CP15');
		$GLOBALS['TSFE']->getConfigArray();

		$this->log('CP16');
		$GLOBALS['TSFE']->inituserGroups();
		$this->log('CP17');
		$GLOBALS['TSFE']->connectToDB();
		$this->log('CP18');
		// Must prevent any redirects, etc
		$GLOBALS['TSFE']->determineId();

		$this->log('CP19');
		$tempcObj= t3lib_div::makeInstance('tslib_cObj');
		/* @var $tempcObj tslib_cObj */
		$this->log('CP20');
		$tempcObj->start(array(),'');
		$this->log('CP21');
		$url = $GLOBALS['TSFE']->tmpl->setup['config.']['baseURL'] . $tempcObj->typoLink_URL(array(
			'parameter' => $pid
		));
		$GLOBALS['TYPO3_DB']->debugOutput = $sqlDebug;
		return $url;
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
		if ($this->sysconf['debugLog'] == 'file') {
			$fd = fopen(PATH_site . 'fileadmin/mnogosearch.log', 'a');
			flock($fd, LOCK_EX);
			fprintf($fd, '[%s] "%s"' . ($rec ? ', data: ' . chr(10) . '%s' . chr(10) : '') . chr(10), date('d-m-Y H:i:s'), $msg, print_r($rec, true));
			flock($fd, LOCK_UN);
			fclose($fd);
		}
		elseif ($this->sysconf['debugLog'] == 'devlog') {
			t3lib_div::devLog($msg, 'mnogosearch', 0, $rec);
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mnogosearch/class.tx_mnogosearch_tcemain.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mnogosearch/class.tx_mnogosearch_tcemain.php']);
}

?>