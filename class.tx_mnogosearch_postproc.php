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
 * Content post-processing functions.
 *
 * @author	Dmitry Dulepov <dmitry@typo3.org>
 * @package	TYPO3
 * @subpackage	tx_mnogosearch
 */
class tx_mnogosearch_postproc {

	/**
	 * Inserts tags to disable indexing for mnoGoSearch for &lt;body&gt; to &lt;/body&gt;.
	 * Later <code>contentPostProcCached</code> will process content to insert additional
	 * tags where necessary.
	 *
	 * @param	array	$params	Unused
	 * @param	object	$pObj	Reference to TSFE
	 */
	function contentPostProcAll(&$params, &$pObj) {
		if (!intval($GLOBALS['TSFE']->config['config']['tx_mnogosearch_enable']) || $GLOBALS['TSFE']->type != 0) {
			return;
		}
		$parts = preg_split('/(<\/?body\s?[^>]*>)/ims', $pObj->content, -1, PREG_SPLIT_DELIM_CAPTURE);
		if (count($parts) == 5) {
			$pObj->content = $parts[0] . $parts[1] . '<!--UdmComment-->' . $parts[2] .
						'<!--/UdmComment-->' . $parts[3] . $parts[4];
		}
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_mnogosearch_postproc']['indexed'] = false;
	}

	/**
	 * Inserts mnoGoSearch tags before and after TYPO3 search tags if latter exists or
	 * removes existing mnoGoSearch tags if TYPO3 tags do not exist
	 * tags where necessary.
	 *
	 * @param	array	$params	Unused
	 * @param	object	$pObj	Reference to TSFE
	 */
	function contentPostProcCached(&$params, &$pObj) {
		if (!intval($GLOBALS['TSFE']->config['config']['tx_mnogosearch_enable']) || $GLOBALS['TSFE']->type != 0) {
			return;
		}
		// Only if no login user!
		if (!is_array($pObj->fe_user->user) && !count($pObj->fe_user->groupData['uid'])) {
			$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_mnogosearch_postproc']['indexed'] = true;
			if (strpos($pObj->content, '<!--TYPO3SEARCH_begin-->'))  {
				// Has search tags
				$pObj->content = preg_replace('/<!--TYPO3SEARCH_begin-->/ims', '<!--/UdmComment--><!--TYPO3SEARCH_begin-->', $pObj->content);
				$pObj->content = preg_replace('/<!--TYPO3SEARCH_end-->/ims', '<!--TYPO3SEARCH_end--><!--UdmComment-->', $pObj->content);
			}
			else {
				// No search tags, enable search for the whole content
				$pObj->content = preg_replace('/<!--\/?UdmComment-->/ims', '', $pObj->content);
			}
		}
	}

	/**
	 * Check for <code>If-modified-since</code> header and creates <code>304 Not Modified</code> response of necessary
	 *
	 * @param	array	$params	Unused
	 * @param	object	$pObj	Reference to TSFE
	 */
	function contentPostProcOutput(&$params, &$pObj) {
		if (!intval($GLOBALS['TSFE']->config['config']['tx_mnogosearch_enable']) || $GLOBALS['TSFE']->type != 0) {
			return;
		}
		if ($pObj->register['SYS_LASTCHANGED'] && $_SERVER['HTTP_IF_MODIFIED_SINCE']) {
			$time = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
			if ($time >= $pObj->register['SYS_LASTCHANGED']) {
				header('HTTP/1.1 304 Not Modified');
				$pObj->content = '';
			}
		}
	}

	/**
	 * Sets <code>&lt;meta name="robots" content="..." /&gt;</code> according to caching results.
	 * If there were no caching, sets tag's <code>content</code> attribute to <code>noindex,follow</code>.
	 *
	 * @param	object	$pObj	Reference to TSFE
	 */
	function hook_indexContent(&$pObj) {
		if (!intval($GLOBALS['TSFE']->config['config']['tx_mnogosearch_enable']) || $GLOBALS['TSFE']->type != 0) {
			return;
		}
		if (!$GLOBALS['TSFE']->config['config']['disableAllHeaderCode']) {
			// TODO no_search, fe_login_mode flags for 'pages' table! See canIndexTable in hook.
			if (!$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_mnogosearch_postproc']['indexed']) {
				$metaTag = '<meta name="robots" content="noindex,follow" />';
				// Check for <meta> tags
				if (preg_match('/<meta\s[^>]*name="robots"[^>]*>/ims', $pObj->content)) {
					$pObj->content = preg_replace('/<meta\s[^>]*name="robots"[^>]*>/ims', $metaTag, $pObj->content);
				}
				else {
					$pos = stripos($pObj->content, '</head>');
					if ($pos > 0) {
						$pObj->content = substr_replace($pObj->content, $metaTag . chr(10), $pos, 0);
					}
				}
				// Remove mnoGoSearch tags
				$pObj->content = preg_replace('/<!--\/?UdmComment-->/ims', '', $pObj->content);
			}
			else {
				if (!$pObj->config['config']['sendCacheHeaders'] && $pObj->register['SYS_LASTCHANGED']) {
					header('Last-modified: ' . gmdate('D, d M Y H:i:s T', $pObj->register['SYS_LASTCHANGED']));
				}
			}
		}
		unset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_mnogosearch_postproc']);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mnogosearch/class.tx_mnogosearch_postproc.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mnogosearch/class.tx_mnogosearch_postproc.php']);
}

?>