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

	/** Template file content */
	protected $templateCode;

	/**
	 * Initializes the renderer
	 *
	 * @param	tx_mnogosearch_pi1	$pObj	Calling object
	 * @return	boolean	true on success
	 */
	public function init(tx_mnogosearch_pi1 &$pObj) {
		/* @var $pObj tx_mnogosearch_pi1 */
		if (($result = parent::init($pObj))) {
			$templateFile = $pObj->pi_getFFvalue($pObj->cObj->data['pi_flexform'], 'field_templateFile', 'sTmpl');
			if (!$templateFile) {
				$templateFile = $pObj->conf['templateFile'];
			}
			$this->templateCode = $pObj->cObj->fileResource($templateFile);

			// Add header parts if there are any
			$headerParts = $this->pObj->cObj->getSubpart($this->templateCode, '###HEADER_ADDITIONS###');
			$key = $pObj->extKey . '_' . md5($headerParts);
			if ($headerParts && !isset($GLOBALS['TSFE']->additionalHeaderData[$key])) {
				$headerParts = $pObj->cObj->substituteMarker($headerParts, '###SITE_REL_PATH###', t3lib_extMgm::siteRelPath($pObj->extKey));
				$GLOBALS['TSFE']->additionalHeaderData[$key] = $headerParts;
			}
		}
		return $result;
	}

	/**
	 * Renders simple search form
	 *
	 * @return	string	Generated HTML
	 */
	public function render_simpleSearchForm() {
		$template = $this->pObj->cObj->getSubpart($this->templateCode, '###SHORT_SEARCH_FORM###');
		$result = $this->pObj->cObj->substituteMarkerArray($template, array(
			'###SHORT_SEARCH_FORM_ACTION###' => $this->pObj->pi_getPageLink(intval($this->pObj->pi_getFFvalue($this->pObj->cObj->data['pi_flexform'], 'field_resultsPage'))),
			'###SHORT_SEARCH_FORM_VALUE###' => htmlspecialchars($this->pObj->piVars['q']),
			'###TEXT_SEARCH###' => $this->pObj->pi_getLL('text_submit_short'),
			));
		return $result;
	}

	/**
	 * Renders normal search form
	 *
	 * @return	string	Generated HTML
	 */
	public function render_searchForm() {
		$template = $this->pObj->cObj->getSubpart($this->templateCode, '###LONG_SEARCH_FORM###');
		$result = $this->pObj->cObj->substituteMarkerArray($template, array(
			'###LONG_SEARCH_FORM_ACTION###' => $this->pObj->pi_getPageLink(intval($this->pObj->pi_getFFvalue($this->pObj->cObj->data['pi_flexform'], 'field_resultsPage'))),
			'###LONG_SEARCH_FORM_VALUE###' => htmlspecialchars($this->pObj->piVars['q']),
			'###TEXT_SEARCH###' => $this->pObj->pi_getLL('text_submit_long'),
		));
		return $result;
	}

	/**
	 * Renders search results
	 *
	 * @see	tx_mnogosearch_renderer::render_searchResults()
	 * @param	tx_mnogosearch_results	$results
	 * @return	string	Generated content
	 */
	public function render_searchResults(&$results) {
		/* @var $results tx_mnogosearch_results */
		// Setup variables
		$result = '';
		$rpp = intval($this->pObj->pi_getFFvalue($this->pObj->cObj->data['pi_flexform'], 'field_resultsPerPage'));
		if (!$rpp) {
			$rpp = 20;
		}
		$page = intval($results->firstDoc/$rpp);
		$numPages = ($results->totalResults/$rpp + (($results->totalResults % $rpp) == 0 ? 0 : 1));

		// Get template for this function
		$template = $this->pObj->cObj->getSubpart($this->templateCode, '###SEARCH_RESULTS###');

		// Results
		if ($results->numRows == 0) {
			$resultTemplate = $this->pObj->cObj->getSubpart($this->templateCode, '###SEARCH_RESULTS_EMPTY###');
			$resultList = $this->pObj->cObj->substituteMarker($resultTemplate, '###TEXT_NOTHING_FOUND###', $pObj->pi_getLL('text_nothing_found'));
		}
		else {
			$resultTemplate = $this->pObj->cObj->getSubpart($this->templateCode, '###SEARCH_RESULTS_RESULT###');
			$linksTemplate = $this->pObj->cObj->getSubpart($resultTemplate, '###SEARCH_RESULTS_RESULT_ALT_LINK###');
			$resultList = ''; $i = 0;
			foreach ($results->results as $result) {
				/* @var tx_mnogosearch_result $result */
				// Basic fields
				$t = $this->pObj->cObj->substituteMarkerArray($resultTemplate, array(
						'###SEARCH_RESULTS_RESULT_NUMBER###' => $results->firstDoc + ($i++),
						'###SEARCH_RESULTS_RESULT_URL###' => $result->url,
						'###SEARCH_RESULTS_RESULT_TITLE###' => $result->title,	// todo: htmlspecialchars?
						'###SEARCH_RESULTS_RESULT_RELEVANCY###' => sprintf('%.2f', $result->rating),
						'###SEARCH_RESULTS_RESULT_EXCERPT###' => $result->excerpt,
						'###TEXT_ADDITIONAL_RESULTS###' => $this->pObj->pi_getLL('text_additional_results'),
						'###UNIQID###' => uniqid('c'),
					));
				// Make links
				$links = '';
				foreach ($result->clones as $r) {
					$links .= $this->pObj->cObj->substituteMarkerArray($linksTemplate, array(
						'###SEARCH_RESULTS_RESULT_ALT_LINK_URL###' => $r->url,
						'###SEARCH_RESULTS_RESULT_ALT_LINK_TITLE###' => $r->url,
						));
				}
				if ($links != '') {
					$resultList .= $this->pObj->cObj->substituteSubpart($t, '###SEARCH_RESULTS_RESULT_ALT_LINK###', $links);
				}
				else {
					$resultList .= $this->pObj->cObj->substituteSubpart($t, '###SEARCH_RESULTS_RESULT_LINKS###', '');
				}
			}
			// Wrap
			$resultTemplate = $this->pObj->cObj->getSubpart($this->templateCode, '###SEARCH_RESULTS_CONTENT###');
			$resultList = $this->pObj->cObj->substituteSubpart($resultTemplate, '###SEARCH_RESULTS_RESULT###', $resultList);
			$resultList = $this->pObj->cObj->substituteMarker($resultList, '###SEARCH_RESULTS_FIRST###', $results->firstDoc);
		}

		// Pager
		$pager = '';
		if ($results->totalResults > $rpp) {
			$pagerTemplate = $this->pObj->cObj->getSubpart($template, '###SEARCH_RESULTS_PAGER###');
			$prevLink = $nextLink = '';
			if ($page > 0) {
				$pageTemplate = $this->pObj->cObj->getSubpart($pagerTemplate, '###SEARCH_RESULTS_PAGER_PREV###');
				$prevLink = $this->pObj->cObj->substituteMarker($pageTemplate, '###SEARCH_RESULTS_PAGER_PREV_LINK###', $this->getLink($page - 1));
			}
			if ($results->lastDoc < $results->totalResults) {
				$pageTemplate = $this->pObj->cObj->getSubpart($pagerTemplate, '###SEARCH_RESULTS_PAGER_NEXT###');
				$nextLink = $this->pObj->cObj->substituteMarker($pageTemplate, '###SEARCH_RESULTS_PAGER_NEXT_LINK###', $this->getLink($page + 1));
			}
			// Put all together
			$pager = $this->pObj->cObj->substituteMarker($pagerTemplate, '###SEARCH_RESULTS_PAGER_CURRENT_PAGE###', $page + 1);
			$pager = $this->pObj->cObj->substituteSubpart($pager, '###SEARCH_RESULTS_PAGER_PREV###', $prevLink);
			$pager = $this->pObj->cObj->substituteSubpart($pager, '###SEARCH_RESULTS_PAGER_NEXT###', $nextLink);
		}

		// Put all together
		$totalPages = intval($results->totalResults/$rpp) + ($results->totalResults % $rpp ? 1 : 0);
		$wordInfo = str_replace(' / ', '/', $results->wordInfo);
		$wordInfo = str_replace(' :', ':', $wordInfo);
		// TODO stdWrap numbers inside $wordInfo
		$timeStr = sprintf($this->pObj->conf['time_format'], $results->searchTime);
		$content = $this->pObj->cObj->substituteMarkerArray($template, array(
				// Older markers (partially for compatibility)
				'###SEARCH_RESULTS_TERMS###' => htmlspecialchars($this->pObj->piVars['q']),
				'###SEARCH_RESULTS_STATISTICS###' => htmlspecialchars($wordInfo),
				'###SEARCH_RESULTS_TIME###' => $timeStr,
				'###SEARCH_RESULTS_FIRST###' => $results->firstDoc,
				'###SEARCH_RESULTS_LAST###' => $results->lastDoc,
				'###SEARCH_RESULTS_TOTAL###' => $results->totalResults,
				'###SEARCH_RESULTS_CURRENT_PAGE###' => $page + 1,
				'###SEARCH_RESULTS_PAGE_TOTAL###' => $totalPages,
				// Newer markers
				'###TEXT_SEARCH_TEXT###' => $this->pObj->pi_getLL('text_search_text'),
				'###TEXT_SEARCH_RESULTS###' => $this->pObj->pi_getLL('text_search_results'),
				'###SEARCH_TOOK###' => sprintf($this->pObj->pi_getLL('search_took'),
											$this->pObj->cObj->stdWrap($timeStr, $this->pObj->conf['number_stdWrap.'])),
				'###SEARCH_TOOK_SHORT###' => sprintf($this->pObj->pi_getLL('search_took_short'),
											$this->pObj->cObj->stdWrap($timeStr, $this->pObj->conf['number_stdWrap.'])),
				'###RESULT_RANGE###' => sprintf($this->pObj->pi_getLL('result_range'),
											$this->pObj->cObj->stdWrap($results->firstDoc, $this->pObj->conf['number_stdWrap.']),
											$this->pObj->cObj->stdWrap($results->lastDoc, $this->pObj->conf['number_stdWrap.']),
											$this->pObj->cObj->stdWrap($results->totalResults, $this->pObj->conf['number_stdWrap.'])
											),
				'###PAGE_RANGE###' => sprintf($this->pObj->pi_getLL('page_range'),
											$this->pObj->cObj->stdWrap($page + 1, $this->pObj->conf['number_stdWrap.']),
											$this->pObj->cObj->stdWrap($totalPages, $this->pObj->conf['number_stdWrap.'])
											),
				'###PAGE_BROWSER###' => $this->getPageBrowser($numPages),
			));
		$content = $this->pObj->cObj->substituteSubpart($content, '###SEARCH_RESULTS_CONTENT###', $resultList);
		$content = $this->pObj->cObj->substituteSubpart($content, '###SEARCH_RESULTS_EMPTY###', '');
		$content = $this->pObj->cObj->substituteSubpart($content, '###SEARCH_RESULTS_PAGER###', $pager);
		return $content;
	}

	function getPageBrowser($numberOfPages) {
		$pageBrowserKind = $this->pObj->conf['pageBrowser'];
		$pageBrowserConfig = (array)$this->pObj->conf['pageBrowser.'];
		$pageBrowserConfig += array(
			'pageParameterName' => $this->pObj->prefixId . '|page',
			'numberOfPages' => $numberOfPages,
		);
		// Get page browser
		$cObj = t3lib_div::makeInstance('tslib_cObj');
		/* @var $cObj tslib_cObj */
		$cObj->start(array(), '');
		return $cObj->cObjGetSingle($pageBrowserKind, $pageBrowserConfig);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mnogosearch/pi1/renderers/class.tx_mnogosearch_renderer_mtb.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mnogosearch/pi1/renderers/class.tx_mnogosearch_renderer_mtb.php']);
}

?>