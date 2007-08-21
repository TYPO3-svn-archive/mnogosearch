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

require_once(dirname(__FILE__) . '/class.tx_mnogosearch_renderer.php');

/**
 * Base renderer for mnoGoSearch plugin
 *
 * @author	Dmitry Dulepov <dmitry@typo3.org>
 * @package	TYPO3
 * @subpackage	tx_mnogosearch
 */
class tx_mnogosearch_renderer_mtb extends tx_mnogosearch_renderer {

	var $templateCode;

	function init(&$pObj) {
		if ($result = parent::init($pObj)) {
			$templateFile = $pObj->pi_getFFvalue($pObj->cObj->data['pi_flexform'], 'field_templateFile', 'sTmpl');
			$this->templateCode = $pObj->cObj->fileResource($templateFile);

			// Add header parts if there are any
			$headerParts = $this->pObj->cObj->getSubpart($this->templateCode, '###HEAD_ADDITIONS###');
			if ($headerParts) {
				$headerParts = $pObj->cObj->substituteMarker($headerParts, '###THIS_PATH###', dirname($GLOBALS['TSFE']->tmpl->getFileName($templateFile)));
				$GLOBALS['TSFE']->additionalHeaderData['EXT:mnoGoSearch'] = $headerParts;
			}
		}
		return $result;
	}

	function render_simpleSearchForm() {
		$result = '';

		$template = $this->pObj->cObj->getSubpart($this->templateCode, '###SHORT_SEARCH_FORM###');
		return $result;
	}

	function render_searchForm() {
		return 'Function render_searchForm is not implemented yet';
	}

	function render_searchResults(&$results) {
		$result = '';

		$template = $this->pObj->cObj->getSubpart($this->templateCode, '###SEARCH_RESULTS###');

		$rpp = intval($this->pObj->pi_getFFvalue($this->pObj->cObj->data['pi_flexform'], 'field_resultsPerPage'));
		if (!$rpp) {
			$rpp = 20;
		}
		$curPage = intval($results->firstDoc/$rpp);

		$result = $this->pObj->cObj->substituteMarkerArray($template, array(
				'###SEARCH_RESULTS_TERMS###' => htmlspecialchars($this->pObj->piVars['q']),
				'###SEARCH_RESULTS_STATISTICS###' => htmlspecialchars($results->wordInfo),
				'###SEARCH_RESULTS_TIME###' => sprintf('%.3f', $results->searchTime),
				'###SEARCH_RESULTS_FIRST###' => $results->firstDoc,
				'###SEARCH_RESULTS_LAST###' => $results->lastDoc,
				'###SEARCH_RESULTS_TOTAL###' => $result->totalResults,
				'###SEARCH_RESULTS_CURRENT_PAGE###' => $curPage + 1,
				'###SEARCH_RESULTS_PAGE_TOTAL###' => intval($results->totalResults/$rpp) + ($results->totalResults % $rpp ? 1 : 0),
				));
		return $result;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mnogosearch/pi1/renderers/class.tx_mnogosearch_renderer_mtb.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mnogosearch/pi1/renderers/class.tx_mnogosearch_renderer_mtb.php']);
}

?>