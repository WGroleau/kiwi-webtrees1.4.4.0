<?php
// Controller for the search page
//
// webtrees: Web based Family History software
// Copyright (C) 2013 webtrees development team.
//
// Derived from PhpGedView
// Copyright (C) 2002 to 2009 PGV Development Team.  All rights reserved.
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//
// $Id$

if (!defined('WT_WEBTREES')) {
	header('HTTP/1.0 403 Forbidden');
	exit;
}

class WT_Controller_Search extends WT_Controller_Page {
	public $action;
	// TODO: decide if these variables are public/private/protected (or unused)
	var $isPostBack = false;
	var $srfams;
	var $srindi;
	var $srnote;
	var $srsour;
	var $resultsPageNum = 0;
	var $resultsPerPage = 50;
	var $totalResults = -1;
	var $totalGeneralResults = -1;
	var $indiResultsPrinted = -1;
	var $famResultsPrinted = -1;
	var $srcResultsPrinted = -1;
	var $query;
	var $myquery = "";
	var $soundex = "DaitchM";
	var $subaction = "";
	var $nameprt = "";
	var $showasso = "off";
	var $name="";
	var $myname;
	var $birthdate="";
	var $mybirthdate;
	var $birthplace="";
	var $mybirthplace;
	var $deathdate="";
	var $mydeathdate;
	var $deathplace="";
	var $mydeathplace;
	var $gender="";
	var $mygender;
	var $firstname="";
	var $lastname="";
	var $place="";
	var $year="";
	var $sgeds = array ();
	var $myindilist = array ();
	var $mysourcelist = array ();
	var $myfamlist = array ();
	var $mynotelist = array ();
	var $inputFieldNames = array ();
	var $replace = false;
	var $replaceNames = false;
	var $replacePlaces = false;
	var $replaceAll = false;
	var $replacePlacesWord = false;
	var $printplace = array();

	function __construct() {
		parent::__construct();

		// action comes from $_GET (menus) or $_POST (form submission)
		$this->action=safe_REQUEST($_REQUEST, 'action', array('advanced', 'general', 'soundex', 'replace'), 'general');

		$topsearch=safe_POST_bool('topsearch');

		if ($topsearch) {
			$this->isPostBack = true;
			$this->srfams = 'yes';
			$this->srindi = 'yes';
			$this->srsour = 'yes';
			$this->srnote = 'yes';
		}

		// Get the query and remove slashes
		if (isset ($_REQUEST["query"])) {
			// Reset the "Search" text from the page header
			if (strlen($_REQUEST["query"])<2) {
				$this->query="";
				$this->myquery="";
			} else {
				$this->query = $_REQUEST["query"];
				$this->myquery = htmlspecialchars($this->query);
			}
		}
		if (isset ($_REQUEST["replace"])) {
			$this->replace = $_REQUEST["replace"];

			if (isset($_REQUEST["replaceNames"])) $this->replaceNames = true;
			if (isset($_REQUEST["replacePlaces"])) $this->replacePlaces = true;
			if (isset($_REQUEST["replacePlacesWord"])) $this->replacePlacesWord = true;
			if (isset($_REQUEST["replaceAll"])) $this->replaceAll = true;
		}

		// TODO: fetch each variable independently, using appropriate validation
		// Aquire all the variables values from the $_REQUEST
		$varNames = array ("isPostBack", "srfams", "srindi", "srsour", "srnote", "view", "soundex", "subaction", "nameprt", "showasso", "resultsPageNum", "resultsPerPage", "totalResults", "totalGeneralResults", "indiResultsPrinted", "famResultsPrinted", "srcResultsPrinted", "myindilist", "mysourcelist", "mynotelist", "myfamlist");
		$this->setRequestValues($varNames);

		if (!$this->isPostBack) {
			// Enable the default gedcom for search
			$str = str_replace(array (".", "-", " "), array ("_", "_", "_"), WT_GEDCOM);
			$_REQUEST["$str"] = $str;
		}

		// Retrieve the gedcoms to search in
		if (count(WT_Tree::getAll())>1 && WT_Site::preference('ALLOW_CHANGE_GEDCOM')) {
			foreach (WT_Tree::getAll() as $tree) {
				$str = str_replace(array (".", "-", " "), array ("_", "_", "_"), $tree->tree_name);
				if (isset ($_REQUEST["$str"]) || $topsearch) {
					$this->sgeds[$tree->tree_id] = $tree->tree_name;
					$_REQUEST["$str"] = 'yes';
				}
			}
		} else {
			$this->sgeds[WT_GED_ID] = WT_GEDCOM;
		}

		// vars use for soundex search
		if (!empty ($_REQUEST["firstname"])) {
			$this->firstname = $_REQUEST["firstname"];
		} else {
			$this->firstname="";
		}
		if (!empty ($_REQUEST["lastname"])) {
			$this->lastname = $_REQUEST["lastname"];
		} else {
			$this->lastname="";
		}
		if (!empty ($_REQUEST["place2"])) {
			$this->place = $_REQUEST["place2"];
		} else {
			$this->place="";
		}
		if (!empty ($_REQUEST["year"])) {
			$this->year = $_REQUEST["year"];
		} else {
			$this->year="";
		}
		// Set the search result titles for soundex searches
		if ($this->firstname || $this->lastname || $this->place) {
			$this->myquery=htmlspecialchars(implode(' ', array($this->firstname, $this->lastname, $this->place)));
		};

		if (!empty ($_REQUEST["name"])) {
			$this->name = $_REQUEST["name"];
			$this->myname = $this->name;
		} else {
			$this->name="";
			$this->myname = "";
		}
		if (!empty ($_REQUEST["birthdate"])) {
			$this->birthdate = $_REQUEST["birthdate"];
			$this->mybirthdate = $this->birthdate;
		} else {
			$this->birthdate="";
			$this->mybirthdate = "";
		}
		if (!empty ($_REQUEST["birthplace"])) {
			$this->birthplace = $_REQUEST["birthplace"];
			$this->mybirthplace = $this->birthplace;
		} else {
			$this->birthplace="";
			$this->mybirthplace = "";
		}
		if (!empty ($_REQUEST["deathdate"])) {
			$this->deathdate = $_REQUEST["deathdate"];
			$this->mydeathdate = $this->deathdate;
		} else {
			$this->deathdate="";
			$this->mydeathdate = "";
		}
		if (!empty ($_REQUEST["deathplace"])) {
			$this->deathplace = $_REQUEST["deathplace"];
			$this->mydeathplace = $this->deathplace;
		} else {
			$this->deathplace="";
			$this->mydeathplace = "";
		}
		if (!empty ($_REQUEST["gender"])) {
			$this->gender = $_REQUEST["gender"];
			$this->mygender = $this->gender;
		} else {
			$this->gender="";
			$this->mygender = "";
		}

		$this->inputFieldNames[] = "action";
		$this->inputFieldNames[] = "isPostBack";
		$this->inputFieldNames[] = "resultsPerPage";
		$this->inputFieldNames[] = "query";
		$this->inputFieldNames[] = "srindi";
		$this->inputFieldNames[] = "srfams";
		$this->inputFieldNames[] = "srsour";
		$this->inputFieldNames[] = "srnote";
		$this->inputFieldNames[] = "showasso";
		$this->inputFieldNames[] = "firstname";
		$this->inputFieldNames[] = "lastname";
		$this->inputFieldNames[] = "place";
		$this->inputFieldNames[] = "year";
		$this->inputFieldNames[] = "soundex";
		$this->inputFieldNames[] = "nameprt";
		$this->inputFieldNames[] = "subaction";
		$this->inputFieldNames[] = "name";
		$this->inputFieldNames[] = "birthdate";
		$this->inputFieldNames[] = "birthplace";
		$this->inputFieldNames[] = "deathdate";
		$this->inputFieldNames[] = "deathplace";
		$this->inputFieldNames[] = "gender";

		// Get the search results based on the action
		if ($topsearch) {
			$this->TopSearch();
		}
		// If we want to show associated persons, build the list
		switch ($this->action) {
		case 'general':
			$this->setPageTitle(WT_I18N::translate('General search'));
			$this->GeneralSearch();
			break;
		case 'soundex':
			$this->setPageTitle(WT_I18N::translate('Phonetic search'));
			$this->SoundexSearch();
			break;
		case 'replace':
			$this->setPageTitle(WT_I18N::translate('Search and replace'));
			$this->SearchAndReplace();
			return;
		}
	}

	/**
	 * setRequestValues - Checks if the variable names ($varNames) are in
	 * the $_REQUEST and if so assigns their values to
	 * $this based on the variable name ($this->$varName).
	 *
	 * @param array $varNames - Array of variable names(strings).
	 */
	function setRequestValues($varNames) {
		foreach ($varNames as $key => $varName) {
			if (isset ($_REQUEST[$varName])) {
				if ($varName=='action' && $_REQUEST[$varName]=='replace' && !WT_USER_CAN_EDIT) {
					$this->action='general';
					continue;
				}
				$this->$varName = $_REQUEST[$varName];
			}
		}
	}

	/**
	 * Handles searches entered in the top search box in the themes and
	 * prepares the search to do a general search on indi's, fams, and sources.
	 */
	function TopSearch() {
		// first set some required variables. Search only in current gedcom, only in indi's.
		$this->srindi = "yes";

		// Enable the default gedcom for search
		$str = str_replace(array (".", "-", " "), array ("_", "_", "_"), WT_GEDCOM);
		$_REQUEST["$str"] = "yes";

		// Then see if an ID is typed in. If so, we might want to jump there.
		if (isset ($this->query)) {
			$record=WT_GedcomRecord::getInstance($this->query);
			if ($record && $record->canDisplayDetails()) {
				header('Location: '.WT_SERVER_NAME.WT_SCRIPT_PATH.$record->getRawUrl());
				exit;
			}
		}
	}

	/**
	 * Gathers results for a general search
	 */
	function GeneralSearch() {
		global $GEDCOM;
		$oldged = $GEDCOM;

		// Split search terms into an array
		$query_terms=array();
		$query=$this->query;
		// Words in double quotes stay together
		while (preg_match('/"([^"]+)"/', $query, $match)) {
			$query_terms[]=trim($match[1]);
			$query=str_replace($match[0], '', $query);
		}
		// Other words get treated separately
		while (preg_match('/[\S]+/', $query, $match)) {
			$query_terms[]=trim($match[0]);
			$query=str_replace($match[0], '', $query);
		}

		//-- perform the search
		if ($query_terms && $this->sgeds) {
			// Write a log entry
			$logstring = "Type: General\nQuery: ".$this->query;
			AddToSearchlog($logstring, $this->sgeds);

			// Search the indi's
			if (isset ($this->srindi)) {
				$this->myindilist=search_indis($query_terms, array_keys($this->sgeds), 'AND');
			} else {
				$this->myindilist=array();
			}

			// Search the fams
			if (isset ($this->srfams)) {
				$this->myfamlist=array_merge(
					search_fams($query_terms, array_keys($this->sgeds), 'AND'),
					search_fams_names($query_terms, array_keys($this->sgeds), 'AND')
				);
				$this->myfamlist=array_unique($this->myfamlist);
			} else {
				$this->myfamlist=array();
			}

			// Search the sources
			if (isset ($this->srsour)) {
				if (!empty ($this->query))
				$this->mysourcelist=search_sources($query_terms, array_keys($this->sgeds), 'AND');
			} else {
				$this->mysourcelist=array();
			}

			// Search the notes
			if (isset ($this->srnote)) {
				if (!empty ($this->query))
				$this->mynotelist=search_notes($query_terms, array_keys($this->sgeds), 'AND');
			} else {
				$this->mynotelist=array();
			}

			// If only 1 item is returned, automatically forward to that item
			// If ID cannot be displayed, continue to the search page.
			if (count($this->myindilist)==1 && !$this->myfamlist && !$this->mysourcelist && !$this->mynotelist) {
				$indi=$this->myindilist[0];
				if ($indi->canDisplayName()) {
					Zend_Session::writeClose();
					header('Location: '.WT_SERVER_NAME.WT_SCRIPT_PATH.$indi->getRawUrl());
					exit;
				}
			}
			if (!$this->myindilist && count($this->myfamlist)==1 && !$this->mysourcelist && !$this->mynotelist) {
				$fam=$this->myfamlist[0];
				if ($fam->canDisplayName()) {
					Zend_Session::writeClose();
					header('Location: '.WT_SERVER_NAME.WT_SCRIPT_PATH.$fam->getRawUrl());
					exit;
				}
			}
			if (!$this->myindilist && !$this->myfamlist && count($this->mysourcelist)==1 && !$this->mynotelist) {
				$sour=$this->mysourcelist[0];
				if ($sour->canDisplayName()) {
					Zend_Session::writeClose();
					header('Location: '.WT_SERVER_NAME.WT_SCRIPT_PATH.$sour->getRawUrl());
					exit;
				}
			}
			if (!$this->myindilist && !$this->myfamlist && !$this->mysourcelist && count($this->mynotelist)==1) {
				$note=$this->mynotelist[0];
				if ($note->canDisplayName()) {
					Zend_Session::writeClose();
					header('Location: '.WT_SERVER_NAME.WT_SCRIPT_PATH.$note->getRawUrl());
					exit;
				}
			}
		}
	}

	/**
	 *  Preforms a search and replace
	 */
	function SearchAndReplace()
	{
		global $GEDCOM, $manual_save, $STANDARD_NAME_FACTS, $ADVANCED_NAME_FACTS;

		$this->sgeds = array(WT_GED_ID=>WT_GEDCOM);
		$this->srindi = "yes";
		$this->srfams = "yes";
		$this->srsour = "yes";
		$this->srnote = "yes";
		$oldquery = $this->query;
		$this->GeneralSearch();

		//-- don't try to make any changes if nothing was found
		if (!$this->myindilist && !$this->myfamlist && !$this->mysourcelist && !$this->mynotelist) {
			return;
		}

		AddToLog("Search And Replace old:".$oldquery." new:".$this->replace, 'edit');
		$manual_save = true;
		// Include edit functions.
		require_once WT_ROOT.'includes/functions/functions_edit.php';
		// These contain the search query and the replace string
		// $this->replace;
		// $this->query;

		// These contain the search results
		// We need to iterate through them and do the replaces
		//$this->myindilist;
		$adv_name_tags = preg_split("/[\s,;: ]+/", $ADVANCED_NAME_FACTS);
		$name_tags = array_unique(array_merge($STANDARD_NAME_FACTS, $adv_name_tags));
		$name_tags[] = "_MARNM";
		foreach ($this->myindilist as $id=>$individual) {
			$indirec=find_gedcom_record($individual->getXref(), WT_GED_ID, true);
			$oldRecord = $indirec;
			$newRecord = $indirec;
			if ($this->replaceAll) {
				$newRecord = preg_replace("~".$oldquery."~i", $this->replace, $newRecord);
			} else {
				if ($this->replaceNames) {
					foreach ($name_tags as $f=>$tag) {
						$newRecord = preg_replace("~(\d) ".$tag." (.*)".$oldquery."(.*)~i", "$1 ".$tag." $2".$this->replace."$3", $newRecord);
					}
				}
				if ($this->replacePlaces) {
					if ($this->replacePlacesWord) $newRecord = preg_replace('~(\d) PLAC (.*)([,\W\s])'.$oldquery.'([,\W\s])~i', "$1 PLAC $2$3".$this->replace."$4",$newRecord);
					else $newRecord = preg_replace("~(\d) PLAC (.*)".$oldquery."(.*)~i", "$1 PLAC $2".$this->replace."$3",$newRecord);
				}
			}
			//-- if the record changed replace the record otherwise remove it from the search results
			if ($newRecord != $oldRecord) {
				replace_gedrec($individual->getXref(), WT_GED_ID, $newRecord);
			} else {
				unset($this->myindilist[$id]);
			}
		}

		foreach ($this->myfamlist as $id=>$family) {
			$indirec=find_gedcom_record($family->getXref(), WT_GED_ID, true);
			$oldRecord = $indirec;
			$newRecord = $indirec;

			if ($this->replaceAll) {
				$newRecord = preg_replace("~".$oldquery."~i", $this->replace, $newRecord);
			}
			else {
				if ($this->replacePlaces) {
					if ($this->replacePlacesWord) $newRecord = preg_replace('~(\d) PLAC (.*)([,\W\s])'.$oldquery.'([,\W\s])~i', "$1 PLAC $2$3".$this->replace."$4",$newRecord);
					else $newRecord = preg_replace("~(\d) PLAC (.*)".$oldquery."(.*)~i", "$1 PLAC $2".$this->replace."$3",$newRecord);
				}
			}
			//-- if the record changed replace the record otherwise remove it from the search results
			if ($newRecord != $oldRecord) {
				replace_gedrec($family->getXref(), WT_GED_ID, $newRecord);
			} else {
				unset($this->myfamlist[$id]);
			}
		}

		foreach ($this->mysourcelist as $id=>$source) {
			$indirec=find_gedcom_record($source->getXref(), WT_GED_ID, true);
			$oldRecord = $indirec;
			$newRecord = $indirec;

			if ($this->replaceAll) {
				$newRecord = preg_replace("~".$oldquery."~i", $this->replace, $newRecord);
			} else {
				if ($this->replaceNames) {
					$newRecord = preg_replace("~(\d) TITL (.*)".$oldquery."(.*)~i", "$1 TITL $2".$this->replace."$3", $newRecord);
					$newRecord = preg_replace("~(\d) ABBR (.*)".$oldquery."(.*)~i", "$1 ABBR $2".$this->replace."$3", $newRecord);
				}
				if ($this->replacePlaces) {
					if ($this->replacePlacesWord) $newRecord = preg_replace('~(\d) PLAC (.*)([,\W\s])'.$oldquery.'([,\W\s])~i', "$1 PLAC $2$3".$this->replace."$4",$newRecord);
					else $newRecord = preg_replace("~(\d) PLAC (.*)".$oldquery."(.*)~i", "$1 PLAC $2".$this->replace."$3",$newRecord);
				}
			}
			//-- if the record changed replace the record otherwise remove it from the search results
			if ($newRecord != $oldRecord) {
				replace_gedrec($source->getXref(), WT_GED_ID, $newRecord);
			} else {
				unset($this->mysourcelist[$id]);
			}
		}

		foreach ($this->mynotelist as $id=>$note) {
			$indirec=find_gedcom_record($note->getXref(), WT_GED_ID, true);
			$oldRecord = $indirec;
			$newRecord = $indirec;

			if ($this->replaceAll) {
				$newRecord = preg_replace("~".$oldquery."~i", $this->replace, $newRecord);
			}
			//-- if the record changed replace the record otherwise remove it from the search results
			if ($newRecord != $oldRecord) {
				replace_gedrec($note->getXref(), WT_GED_ID, $newRecord);
			} else {
				unset($this->mynotelist[$id]);
			}
		}
	}

	/**
	 *  Gathers results for a soundex search
	 *
	 *  TODO
	 *  ====
	 *  Does not search on the selected gedcoms, searches on all the gedcoms
	 *  Does not work on first names, instead of the code, value array is used in the search
	 *  Returns all the names even when Names with hit selected
	 *  Does not sort results by first name
	 *  Does not work on separate double word surnames
	 *  Does not work on duplicate code values of the searched text and does not give the correct code
	 *     Cohen should give DM codes 556000, 456000, 460000 and 560000, in 4.1 we search only on 560000??
	 *
	 *  The names' Soundex SQL table contains all the soundex values twice
	 *  The places table contains only one value
	 *
	 *  The code should be improved - see RFE
	 *
	 */
	function SoundexSearch() {
		if (((!empty ($this->lastname)) || (!empty ($this->firstname)) || (!empty ($this->place))) && (count($this->sgeds) > 0)) {
			$logstring = "Type: Soundex\n";
			if (!empty ($this->lastname))
			$logstring .= "Last name: ".$this->lastname."\n";
			if (!empty ($this->firstname))
			$logstring .= "First name: ".$this->firstname."\n";
			if (!empty ($this->place))
			$logstring .= "Place: ".$this->place."\n";
			if (!empty ($this->year))
			$logstring .= "Year: ".$this->year."\n";
			AddToSearchlog($logstring, $this->sgeds);

			if ($this->sgeds) {
				$this->myindilist=search_indis_soundex($this->soundex, $this->lastname, $this->firstname, $this->place, array_keys($this->sgeds));
			} else {
				$this->myindilist=array();
			}
		}

		// Now we have the final list of indi's to be printed.
		// We may add the assos at this point.

		if ($this->showasso == "on") {
			foreach ($this->myindilist as $indi) {
				foreach (fetch_linked_indi($indi->getXref(), 'ASSO', $indi->getGedId()) as $asso) {
					$this->myindilist[]=$asso;
				}
				foreach (fetch_linked_fam($indi->getXref(), 'ASSO', $indi->getGedId()) as $asso) {
					$this->myfamlist[]=$asso;
				}
			}
		}

		//-- if only 1 item is returned, automatically forward to that item
		if (count($this->myindilist)==1 && $this->action!="replace") {
			$indi=$this->myindilist[0];
			header('Location: '.WT_SERVER_NAME.WT_SCRIPT_PATH.$indi->getRawUrl());
			exit;
		}
		usort($this->myindilist, array('WT_GedcomRecord', 'Compare'));
		usort($this->myfamlist, array('WT_GedcomRecord', 'Compare'));
	}

	function printResults() {
		require_once WT_ROOT.'includes/functions/functions_print_lists.php';
		global $GEDCOM;

		$somethingPrinted = false; // whether anything printed
		// ---- section to search and display results on a general keyword search
		if ($this->action=="general" || $this->action=="soundex" || $this->action=="replace") {
			if ($this->myindilist || $this->myfamlist || $this->mysourcelist || $this->mynotelist) {
				$this->addInlineJavascript('jQuery("#search-result-tabs").tabs();');
				$this->addInlineJavascript('jQuery("#search-result-tabs").css("visibility", "visible");');
				$this->addInlineJavascript('jQuery(".loading-image").css("display", "none");');
				echo '<br>';
				echo '<div class="loading-image">&nbsp;</div>';
				echo '<div id="search-result-tabs"><ul>';
				if ($this->myindilist) {echo '<li><a href="#searchAccordion-indi"><span id="indisource">', WT_I18N::translate('Individuals'), '</span></a></li>';}
				if ($this->myfamlist) {echo '<li><a href="#searchAccordion-fam"><span id="famsource">', WT_I18N::translate('Families'), '</span></a></li>';}
				if ($this->mysourcelist) {echo '<li><a href="#searchAccordion-source"><span id="mediasource">', WT_I18N::translate('Sources'), '</span></a></li>';}
				if ($this->mynotelist) {echo '<li><a href="#searchAccordion-note"><span id="notesource">', WT_I18N::translate('Notes'), '</span></a></li>';}
				echo '</ul>';

				// individual results
				echo '<div id="searchAccordion-indi">';
				// Split individuals by tree
				$trees=WT_Tree::getAll();
				foreach ($this->sgeds as $ged_id=>$gedcom) {
					$datalist = array();
					foreach ($this->myindilist as $individual) {
						if ($individual->getGedId()==$ged_id) {
							$datalist[]=$individual;
						}
					}
					if ($datalist) {
						$somethingPrinted = true;
						usort($datalist, array('WT_GedcomRecord', 'Compare'));
						$GEDCOM=$gedcom;
						load_gedcom_settings($ged_id);
						echo '<h3 class="indi-acc-header"><a href="#"><span class="search_item" dir="auto">', $this->myquery, '</span> @ <span>', $trees[$ged_id]->tree_title_html, '</span></a></h3>
							<div class="indi-acc_content">',
							format_indi_table($datalist);
						echo '</div>';//indi-acc_content
					}
				}
				echo '</div>';//#searchAccordion-indi
				$this->addInlineJavascript('jQuery("#searchAccordion-indi").accordion({active:0, heightStyle: "content", collapsible: true, icons:{ "header": "ui-icon-triangle-1-s", "headerSelected": "ui-icon-triangle-1-n" }});');

				// family results
				echo '<div id="searchAccordion-fam">';
				// Split families by gedcom
				foreach ($this->sgeds as $ged_id=>$gedcom) {
					$datalist = array();
					foreach ($this->myfamlist as $family) {
						if ($family->getGedId()==$ged_id) {
							$datalist[]=$family;
						}
					}
					if ($datalist) {
						$somethingPrinted = true;
						usort($datalist, array('WT_GedcomRecord', 'Compare'));
						$GEDCOM=$gedcom;
						load_gedcom_settings($ged_id);
						echo '<h3 class="fam-acc-header"><a href="#"><span class="search_item" dir="auto">', $this->myquery, '</span> @ <span>', $trees[$ged_id]->tree_title_html, '</span></a></h3>
							<div class="fam-acc_content">',
							format_fam_table($datalist);
						echo '</div>';//fam-acc_content
					}
				}
				echo '</div>';//#searchAccordion-fam
				$this->addInlineJavascript('jQuery("#searchAccordion-fam").accordion({active:0, heightStyle: "content", collapsible: true, icons:{ "header": "ui-icon-triangle-1-s", "headerSelected": "ui-icon-triangle-1-n" }});');

				// source results
				echo '<div id="searchAccordion-source">';
				// Split sources by gedcom
				foreach ($this->sgeds as $ged_id=>$gedcom) {
					$datalist = array();
					foreach ($this->mysourcelist as $source) {
						if ($source->getGedId()==$ged_id) {
							$datalist[]=$source;
						}
					}
					if ($datalist) {
						$somethingPrinted = true;
						usort($datalist, array('WT_GedcomRecord', 'Compare'));
						$GEDCOM=$gedcom;
						load_gedcom_settings($ged_id);
						echo '<h3 class="source-acc-header"><a href="#"><span class="search_item" dir="auto">', $this->myquery, '</span> @ <span>', $trees[$ged_id]->tree_title_html, '</span></a></h3>
							<div class="source-acc_content">',
							format_sour_table($datalist);
						echo '</div>';//fam-acc_content
					}
				}
				echo '</div>';//#searchAccordion-source
				$this->addInlineJavascript('jQuery("#searchAccordion-source").accordion({active:0, heightStyle: "content", collapsible: true, icons:{ "header": "ui-icon-triangle-1-s", "headerSelected": "ui-icon-triangle-1-n" }});');

				// note results
				echo '<div id="searchAccordion-note">';
				// Split notes by gedcom
				foreach ($this->sgeds as $ged_id=>$gedcom) {
					$datalist = array();
					foreach ($this->mynotelist as $note) {
						if ($note->getGedId()==$ged_id) {
							$datalist[]=$note;
						}
					}
					if ($datalist) {
						$somethingPrinted = true;
						usort($datalist, array('WT_GedcomRecord', 'Compare'));
						$GEDCOM=$gedcom;
						load_gedcom_settings($ged_id);
						echo '<h3 class="note-acc-header"><a href="#"><span class="search_item" dir="auto">', $this->myquery, '</span> @ <span>', $trees[$ged_id]->tree_title_html, '</span></a></h3>
							<div class="note-acc_content">',
							format_note_table($datalist);
						echo '</div>';//note-acc_content
					}
				}
				echo '</div>';//#searchAccordion-note
				$this->addInlineJavascript('jQuery("#searchAccordion-note").accordion({active:0, heightStyle: "content", collapsible: true, icons:{ "header": "ui-icon-triangle-1-s", "headerSelected": "ui-icon-triangle-1-n" }});');

				$GEDCOM=WT_GEDCOM;
				load_gedcom_settings(WT_GED_ID);
				echo '</div>'; //#search-result-tabs
			} elseif (isset ($this->query)) {
				echo '<br><div class="warning center"><em>'.WT_I18N::translate('No results found.').'</em><br>';
				if (!isset ($this->srindi) && !isset ($this->srfams) && !isset ($this->srsour) && !isset ($this->srnote)) {
					echo '<em>'.WT_I18N::translate('Be sure to select an option to search for.').'</em><br>';
				}
				echo '</div>';
			}
		}
		return $somethingPrinted; // whether anything printed
	}
}
