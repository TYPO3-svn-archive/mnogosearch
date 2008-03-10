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

class tx_mnogosearch_result {
	var	$url;					// URL
	var $title = '';			// Title
	var $contentType;			// Content type
	var	$documentSize;			// Document size
	var $popularityRank = 0;	// Rank
	var $rating = 0;			// Rating
	var $excerpt = '';			// Excerpt
	var $keywords = '';			// Keywords
	var $description = '';		// Description
	var $language = '';			// Language
	var $charset = '';			// Character set
	var $category = '';			// Category

	var $clones = array();		// Clones
}

class tx_mnogosearch_results {
	var $numRows;				// Number of rows on current page
	var $totalResults;			// Total found results
	var $searchTime;			// Search time
	var $firstDoc;				// First document
	var $lastDoc;				// Last document
	var $wordInfo;				// Information about found words
	var $wordSuggest = false;	// Suggestion information
	var $results = array();		// List of results

	function init(&$udmAgent, &$res, &$pObj) {
		$this->totalResults = Udm_Get_Res_Param($res, UDM_PARAM_FOUND);
		$this->numRows = Udm_Get_Res_Param($res, UDM_PARAM_NUM_ROWS);
		$this->wordInfo = Udm_Get_Res_Param($res, UDM_PARAM_WORDINFO_ALL);
		$this->searchTime = Udm_Get_Res_Param($res, UDM_PARAM_SEARCHTIME);
		$this->firstDoc = Udm_Get_Res_Param($res, UDM_PARAM_FIRST_DOC);
		$this->lastDoc = Udm_Get_Res_Param($res, UDM_PARAM_LAST_DOC);
		if ($this->totalResults == 0) {
			if ($pObj->udmApiVersion >= 30233 && (intval($pObj->pi_getFFvalue($pObj->cObj->data['pi_flexform'], 'field_options')) & 128)) {
				$this->wordSuggest = Udm_Get_Agent_Param_Ex($udmAgent, 'WS');
			}
		}

		// Process results
		for ($i = 0; $i < $this->numRows; $i++) {
			$result = t3lib_div::makeInstance('tx_mnogosearch_result');
			/* @var $result tx_mnogosearch_result */
			$result->popularityRank = Udm_Get_Res_Field($res, $i, UDM_FIELD_POP_RANK);
			Udm_Make_Excerpt($udmAgent, $res, $i);

			$result->url = Udm_Get_Res_Field($res, $i, UDM_FIELD_URL);
			$result->contentType = Udm_Get_Res_Field($res, $i, UDM_FIELD_CONTENT);
			$result->documentSize = Udm_Get_Res_Field($res, $i, UDM_FIELD_SIZE);
			//$ndoc=Udm_Get_Res_Field($res,$i,UDM_FIELD_ORDER);
			$result->rating = Udm_Get_Res_Field($res, $i, UDM_FIELD_RATING);
			$result->title = Udm_Get_Res_Field($res, $i, UDM_FIELD_TITLE);
  			if ($result->title == '') {
  				$result->title = basename($result->url);
  			}
  			$result->title = $this->highlight($result->title, $pObj);
  			$result->excerpt = $this->highlight(strip_tags(Udm_Get_Res_Field($res, $i, UDM_FIELD_TEXT)), $pObj);
  			$result->keywords = $this->highlight(strip_tags(Udm_Get_Res_Field($res, $i, UDM_FIELD_KEYWORDS)), $pObj);
  			$result->description = $this->highlight(strip_tags(Udm_Get_Res_Field($res, $i, UDM_FIELD_DESC)), $pObj);
  			$result->language = Udm_Get_Res_Field($res, $i, UDM_FIELD_LANG);
  			$result->charset = Udm_Get_Res_Field($res, $i, UDM_FIELD_CHARSET);
  			$result->category = Udm_Get_Res_Field($res,$i,UDM_FIELD_CATEGORY);

			// Check clones of necessary
			if ((intval($pObj->pi_getFFvalue($pObj->cObj->data['pi_flexform'], 'field_options')) & 32)) {
				if (0 == Udm_Get_Res_Field($res, $i, UDM_FIELD_ORIGINID)) {
					$urlId = Udm_Get_Res_Field($res, $i, UDM_FIELD_URLID);
					for ($j = 0; $j < $this->numRows; $j++) {
						if ($j != $i && $urlId == Udm_Get_Res_Field($res, $j, UDM_FIELD_ORIGINID)) {
							$clone = new tx_mnogosearch_result;
							$clone->url = Udm_Get_Res_Field($res, $j, UDM_FIELD_URL);
							$clone->contentType = Udm_Get_Res_Field($res, $j, UDM_FIELD_CONTENT);
	  						$clone->documentSize = Udm_Get_Res_Field($res, $j, UDM_FIELD_SIZE);
	  						//$clone->lastModified = format_lastmod(Udm_Get_Res_Field($res,$j,UDM_FIELD_MODIFIED));
	  						$result->clones[] = $clone;
						}
					}
				}
			}
			$this->results[] = $result;
		}
		Udm_Free_Res($res);
	}

	function highlight($str, &$pObj) {
		if (count($pObj->highlightParts) == 2 && $pObj->highlightParts[0] != '') {
			$str = str_replace("\2", $pObj->highlightParts[0], $str);
			$str = str_replace("\3", $pObj->highlightParts[1], $str);
			while (substr_count($str, $pObj->highlightParts[0]) > substr_count($str, $pObj->highlightParts[1])) {
				$str .= $pObj->highlightParts[1];
			}
		}

		return $str;
	}

	function initTest(&$pObj) {
		$pageSize = intval($pObj->pi_getFFvalue($pObj->cObj->data['pi_flexform'], 'field_resultsPerPage'));
		$resultsOnTheLastPage = max(1, intval($pageSize/3));
		$page = intval($pObj->piVars['page']);
		$numPages = 4;
		$foundDocs = (($page < ($numPages-1)) ? $pageSize : $resultsOnTheLastPage);
		$excerptSize = intval($pObj->pi_getFFvalue($pObj->cObj->data['pi_flexform'], 'field_excerptSize'));
		if ($pObj->conf['excerptHighlight']) {
			$pObj->highlightParts = t3lib_div::trimExplode('|', $pObj->conf['excerptHighlight']);
		}

		// fill in our own fields
		$this->totalResults = ($numPages-1) * $pageSize + $resultsOnTheLastPage;
		$this->numRows = (($page < ($numPages-1)) ? $pageSize : $resultsOnTheLastPage);
		$this->wordInfo = '"Lorem ipsum": 123, "sit amet": 456, "sed": 789';
		$this->searchTime = floatval('0.' . sprintf('%03d', rand(1, 999)));
		$this->firstDoc = $page*$pageSize + 1;
		$this->lastDoc = $this->firstDoc + $foundDocs + 1;
		$pObj->piVars['q'] = '"Lorem ipsum" || "sit amet" || sed';

		$lipsum = $this->getLoremIpsum();

		// create fake results
		for ($i = 0; $i < $foundDocs; $i++) {
			$result = t3lib_div::makeInstance('tx_mnogosearch_result');
			/* @var $result tx_mnogosearch_result */
			$result->url = '/' . uniqid(uniqid(), true);
			$result->title = ucfirst($this->getExcerpt($lipsum, rand(3, 12)));
			$result->contentType = 'text/html';
			$result->documentSize = rand(1024, 32768);
			$result->popularityRank = $this->totalResults - $this->firstDoc + 1;
			$result->rating = 0;
			$result->excerpt = $this->highlight(str_replace(
						array('Lorem ipsum', 'lorem ipsum', 'sit amet', 'sed ', 'Sed '),
						array("\2Lorem ipsum\3", "\2lorem ipsum\3", "\2sit amet\3", "\2sed\3 ", "\2Sed\3 "),
						$this->getExcerpt($lipsum, $excerptSize)), $pObj);
			$result->keywords = '';
			$result->description = '--description goes here---';
			$result->language = '';
			$result->charset = '';
			$result->category = '---category---';
			$this->results[] = $result;
		}
	}

	/**
	 * Fetches 'Lorem Ipsum' using XML feed provided by www.lipsum.org
	 *
	 * @return	array	Words of text
	 */
	function getLoremIpsum() {
		$content = t3lib_div::getURL('http://www.lipsum.com/feed/xml?what=words&amount=2048&start=false');
		$array = t3lib_div::xml2array($content);
		return is_array($array) ? explode(' ', $array['lipsum']) : array();
	}

	/**
	 * Creates a random excerpt from Lorem Ipsum array
	 *
	 * @param	array	$lipsum	Words
	 * @param	int	$size	Excerpt size
	 * @return	string	Generated excerpt
	 */
	function getExcerpt(&$lipsum, $size) {
		$offset = rand(0, count($lipsum) - $size);
		return ($offset == 0 ? '' : '...') . implode(' ', array_slice($lipsum, $offset, $size)) . '...';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mnogosearch/pi1/class.tx_mnogosearch_results.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mnogosearch/pi1/class.tx_mnogosearch_results.php']);
}

?>