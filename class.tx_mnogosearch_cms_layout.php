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
class tx_mnogosearch_cms_layout {
	function getExtensionSummary($params, &$pObj) {
		global $LANG;

		if ($params['row']['list_type'] == 'mnogosearch_pi1') {
			$data = t3lib_div::xml2array($params['row']['pi_flexform']);
			if (is_array($data)) {
				$mode = $data['data']['sDEF']['lDEF']['field_mode']['vDEF'];
				switch ($mode) {
					case 0:
						$result = $LANG->sL('LLL:EXT:mnogosearch/locallang_db.xml:cms_layout.mode_shortform');
						break;
					case 1:
						$result = $LANG->sL('LLL:EXT:mnogosearch/locallang_db.xml:cms_layout.mode_longform');
						break;
					case 2:
						$result = $LANG->sL('LLL:EXT:mnogosearch/locallang_db.xml:cms_layout.mode_searchresults');
						break;
				}
			}
			if (!$result) {
				$result = '<img ' . t3lib_iconWorks::skinImg('../../../', 'gfx/icon_warning2.gif', 'width="18" height="16"') . ' align="absmiddle" alt="" /> ' . $LANG->sL('LLL:EXT:mnogosearch/locallang_db.xml:cms_layout.mode_none');
			}
		}
		return $result;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mnogosearch/class.tx_mnogosearch_cms_layout.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mnogosearch/class.tx_mnogosearch_cms_layout.php']);
}

?>