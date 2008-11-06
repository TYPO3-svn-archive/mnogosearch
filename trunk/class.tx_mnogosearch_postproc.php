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
class tx_mnogosearch_postproc {

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
				// Add our comments
				$parts = preg_split('/(<\/?body\s?[^>]*>)/ims', $pObj->content, -1, PREG_SPLIT_DELIM_CAPTURE);
				if (count($parts) == 5) {
					$pObj->content = $parts[0] . $parts[1] . '<!--UdmComment-->' . $parts[2] .
								'<!--/UdmComment-->' . $parts[3] . $parts[4];
				}

				if (!$pObj->config['config']['tx_mnogosearch_keepSiteTitle']) {
					// Replace title
					$title = ($pObj->indexedDocTitle ? $pObj->indexedDocTitle :
								($pObj->altPageTitle ? $pObj->altPageTitle : $pObj->page['title']));
					$pObj->content = preg_replace('/<title>(.*?)<\/title>/', '<title>' . htmlspecialchars($title) . '</title>', $pObj->content);
				}

				// Respect TYPO3SEARCH_xxx
				if (strpos($pObj->content, '<!--TYPO3SEARCH_begin-->'))  {
					// Has search tags
					$pObj->content = str_replace('<!--TYPO3SEARCH_begin-->', '<!--/UdmComment--><!--TYPO3SEARCH_begin-->', $pObj->content);
					$pObj->content = str_replace('<!--TYPO3SEARCH_end-->', '<!--TYPO3SEARCH_end--><!--UdmComment-->', $pObj->content);
				}
				else {
					// No search tags, enable search for the whole content
					$pObj->content = preg_replace('/<!--\/?UdmComment-->/ims', '', $pObj->content);
				}
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
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mnogosearch/class.tx_mnogosearch_postproc.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mnogosearch/class.tx_mnogosearch_postproc.php']);
}

?>