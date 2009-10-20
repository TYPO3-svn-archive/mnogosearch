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
 *
 * $Id$
 */

/**
 * Content post-processing functions.
 *
 * @author	Dmitry Dulepov <dmitry@typo3.org>
 * @package	TYPO3
 * @subpackage	tx_mnogosearch
 */
class tx_mnogosearch_tsfepostproc {

	/**
	 * Check for <code>If-modified-since</code> header and creates <code>304 Not Modified</code> response of necessary
	 *
	 * @param	array	$params	Unused
	 * @param	object	$pObj	Reference to TSFE
	 */
	function contentPostProcOutput(&$params, &$pObj) {
		/* @var $pObj tslib_fe */
		if (!intval($pObj->config['config']['tx_mnogosearch_enable']) || $pObj->type != 0) {
			return;
		}

		// Only if no login user and page is searchable!
		if (!$pObj->page['no_search'] && !is_array($pObj->fe_user->user) && !count($pObj->fe_user->groupData['uid'])) {

			if ($pObj->content) {
				if (strpos($pObj->content, '<!--TYPO3SEARCH_begin-->')) {
					// Remove parts that should not be indexed
					$pObj->content = $this->processContent($pObj->content);
				}
				// Replace title if necessary
				if (!$pObj->config['config']['tx_mnogosearch_keepSiteTitle']) {
					$title = ($pObj->indexedDocTitle ? $pObj->indexedDocTitle :
								($pObj->altPageTitle ? $pObj->altPageTitle : $pObj->page['title']));
					$pObj->content = preg_replace('/<title>[^<]*<\/title>/', '<title>' . htmlspecialchars($title) . '</title>', $pObj->content);
				}
				// Last-modified is necessary to show it in search results. TYPO3
				// never sends "Not modified" response, so we are safe for
				// "If-modified-since" requests.
				$this->addLastModified($pObj);
			}
		}
		else {
			// No search!
			$metaTag = '<meta name="robots" content="noindex,nofollow" />';
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
	}

	/**
	 * Processes content by removing everything except links from parts of
	 * the content that should not be searched.
	 *
	 * @param	string	$html	HTML to process
	 * @return	string	Processing content
	 */
	protected function processContent($html) {
		list($part1, $content, $part2) = preg_split('/<\/?body[^>]*>/', $html);

		$process = true;
		$result = '';
		$regexp = '/(<!--TYPO3SEARCH_(?:begin|end)-->)/';
		$blocks = preg_split($regexp, $content, -1, PREG_SPLIT_DELIM_CAPTURE);

		foreach ($blocks as $block) {
			if ($block == '<!--TYPO3SEARCH_begin-->') {
				$process = false;
			}
			elseif ($block == '<!--TYPO3SEARCH_end-->') {
				$process = true;
			}
			elseif ($process) {
				$result .= $this->processBlock($block);
			}
			else {
				$result .= $block;
			}
		}
		return $part1 . '<body>' . $result . '</body>' . $part2;
	}

	/**
	 * Processes a single block of text, removes everything execept links.
	 *
	 * @param	string	$block	Content part
	 * @return	string	Processed block
	 */
	protected function processBlock($block) {
		$regexp = '/(<a[^>]+href=[^>]+>)/';
		$matches = array();
		$result = '';
		if (preg_match_all($regexp, $block, $matches)) {
			foreach ($matches[0] as $match) {
				$result .= $match . ' </a>';
			}
		}
		return $result;
	}

	/**
	 * Adds the "Last-modified" header to the page
	 *
	 * @param	tslib_fe $pObj	Parent object
	 * @return 	void
	 */
	protected function addLastModified(tslib_fe $pObj) {
		// See if we have this header already
		$headers = headers_list();
		foreach ($headers as $header) {
			if (stripos($header, 'Last-modified:') === 0) {
				$parts = t3lib_div::trimExplode(':', $header, true, 2);
				if (count($parts == 2)) {
					$time = strtotime($parts[1]);
					if ($time >= time() - 300) {
						// The page was just generated. Do not trust this header!
						break;
					}
				}
				return;
			}
		}
		$time = (($pObj->register['SYS_LASTCHANGED'] < time() - 300) ?
			$pObj->register['SYS_LASTCHANGED'] : $GLOBALS['TSFE']->page['tstamp']);
		header('Last-modified: ' . gmdate('D, d M Y H:i:s T', $time));
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mnogosearch/hooks/class.tx_mnogosearch_tsfepostproc.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mnogosearch/hooks/class.tx_mnogosearch_tsfepostproc.php']);
}

?>