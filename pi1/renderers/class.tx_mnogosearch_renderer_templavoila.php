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
class tx_mnogosearch_renderer_templavoila extends tx_mnogosearch_renderer {

	var	$TMPLobj;			// TV markup object
	var	$TA = false;		// Template object

	function init(&$pObj) {
		if (!t3lib_extMgm::isLoaded('templavoila')) {
			return false;
		}
		if (!parent::init($pObj)) {
			return false;
		}

		// Load template object
		$field_templateObject = intval($pObj->pi_getFFvalue($pObj->cObj->data['pi_flexform'], 'field_templateObject', 'sTmpl'));
		if ($field_templateObject)	{
			$this->TMPLobj = t3lib_div::makeInstance('tx_templavoila_htmlmarkup');
			$this->TA = $this->TMPLobj->getTemplateArrayForTO(intval($field_templateObject));
			if (is_array($this->TA))	{
				$this->TMPLobj->setHeaderBodyParts($this->TMPLobj->tDat['MappingInfo_head'],$this->TMPLobj->tDat['MappingData_head_cached']);
			}
		}
		if (!is_array($this->TA)) {
			return false;
		}

		return true;
	}

	function render_simpleSearchForm() {
		$content = $this->TMPLobj->mergeDataArrayToTemplateArray(
			$this->TA,
			array(
				'field_input' => htmlspecialchars($this->pObj->piVars['q']),
				'field_action' => $this->pObj->pi_getPageLink(intval($this->pObj->pi_getFFvalue($this->pObj->cObj->data['pi_flexform'], 'field_resultsPage'))),//pi_linkTP_keepPIvars_url(array(), 0, 0, intval($this->pObj->pi_getFFvalue($pObj->cObj->data['pi_flexform'], 'field_resultsPage'))),
			)
		);
		return $content;
	}

	function render_searchForm() {
		$content = $this->TMPLobj->mergeDataArrayToTemplateArray(
			$this->TA,
			array(
				'field_input' => htmlspecialchars($this->pObj->piVars['q']),
				'field_action' => $this->pObj->pi_getPageLink(intval($this->pObj->pi_getFFvalue($this->pObj->cObj->data['pi_flexform'], 'field_resultsPage'))),//pi_linkTP_keepPIvars_url(array(), 0, 0, intval($this->pObj->pi_getFFvalue($pObj->cObj->data['pi_flexform'], 'field_resultsPage'))),
			)
		);
		return $content;
	}

	function render_searchResults(&$results) {
		// result list
		$content = ''; $i = 0;
		foreach ($results->results as $result) {
			$links = $this->TMPLobj->mergeDataArrayToTemplateArray(
				$this->TA['sub']['field_rcontainersect']['sub']['field_rcontainer']['sub']['field_lcontainersect']['sub']['field_lcontainer'], array(
					'field_link' => $result->url,
					'field_title' => $result->url,//$result->title,
				)
			);
			foreach ($result->clones as $clone) {
				$links .= $this->TMPLobj->mergeDataArrayToTemplateArray(
					$this->TA['sub']['field_rcontainersect']['sub']['field_rcontainer']['sub']['field_lcontainersect']['sub']['field_lcontainer'], array(
						'field_link' => $clone->url,
						'field_title' => $clone->url,//$clone->title,
					)
				);
			}
			$links = $this->TMPLobj->mergeDataArrayToTemplateArray(
				$this->TA['sub']['field_rcontainersect']['sub']['field_rcontainer']['sub']['field_lcontainersect'], array(
					'field_lcontainer' => $links,
				)
			);
			$content .= $this->TMPLobj->mergeDataArrayToTemplateArray(
				$this->TA['sub']['field_rcontainersect']['sub']['field_rcontainer'], array(
					'field_number' => $results->firstDoc + ($i++),
					'field_link' => $result->url,
					'field_title' => $result->title,
					'field_rel' => sprintf('%.2f', $result->rating),
					'field_excerpt' => $result->excerpt,
					'field_lcontainersect' => $links,
				)
			);
		}
		// result container
		$content = $this->TMPLobj->mergeDataArrayToTemplateArray(
			$this->TA['sub']['field_rcontainersect'], array(
				'field_rcontainer' => $content
			)
		);

		$rpp = intval($this->pObj->pi_getFFvalue($this->pObj->cObj->data['pi_flexform'], 'field_resultsPerPage'));
		if (!$rpp) {
			$rpp = 20;
		}

		// prev link
		$prevLink = '';
		if ($results->firstDoc > 1) {
			$page = intval($results->firstDoc/$rpp) - 1;
			$prevLink = $this->TMPLobj->mergeDataArrayToTemplateArray(
				$this->TA['sub']['field_prevco'], array(
					'field_link' => $this->getLink($page),
				)
			);
		}
		// next link
		$nextLink = '';
		if ($results->lastDoc < $results->totalResults) {
			$page = intval($results->firstDoc/$rpp) + 1;
			$nextLink = $this->TMPLobj->mergeDataArrayToTemplateArray(
				$this->TA['sub']['field_nextco'], array(
					'field_link' => $this->getLink($page),
				)
			);
		}
		// page links
		$pageLinks = '';
		$curPage = intval($results->firstDoc/$rpp);
		$minPage = max(0, $curPage - 5); $maxPage = min(intval(($results->totalResults - 1)/$rpp), $curPage + 5);
		for ($i = $minPage; $i <= $maxPage && ($maxPage - $minPage > 0); $i++) {
			$pageLinks .= $this->TMPLobj->mergeDataArrayToTemplateArray(
				$this->TA['sub']['field_pcontainersect']['sub']['field_pcontainer'], array(
					'field_link' => $this->getLink($i),
					'field_pagenum' => $i + 1,
				)
			);
		}
		if ($pageLinks) {
			$pageLinks = $this->TMPLobj->mergeDataArrayToTemplateArray(
				$this->TA['sub']['field_pcontainersect'], array(
					'field_pcontainer' => $pageLinks,
				)
			);
		}
		// final merge
		$content = $this->TMPLobj->mergeDataArrayToTemplateArray(
			$this->TA,
			array(
				'field_squery' => htmlspecialchars($this->pObj->piVars['q']),
				'field_swords' => htmlspecialchars($results->wordInfo),
				'field_sresults1' => $results->firstDoc,
				'field_sresultsN' => $results->lastDoc,
				'field_sresultsNum' => $results->totalResults,
				'field_sresultsTime' => sprintf('%.3f', $results->searchTime),
				'field_pnum' => $curPage + 1,
				'field_ptotal' => intval($results->totalResults/$rpp) + ($results->totalResults % $rpp ? 1 : 0),
				'field_rcontainersect' => $content,
				'field_prevco' => $prevLink,
				'field_pcontainersect' => $pageLinks,
				'field_nextco' => $nextLink,
			)
		);
		return $content;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mnogosearch/pi1/renderers/class.tx_mnogosearch_renderer_templavoila.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mnogosearch/pi1/renderers/class.tx_mnogosearch_renderer_templavoila.php']);
}

?>