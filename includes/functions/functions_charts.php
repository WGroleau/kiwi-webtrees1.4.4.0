<?php
// Functions used for charts
//
// webtrees: Web based Family History software
// Copyright (C) 2012 webtrees development team.
//
// Derived from PhpGedView
// Copyright (C) 2002 to 2010  PGV Development Team.  All rights reserved.
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

/**
 * print a table cell with sosa number
 *
 * @param int $sosa
 * @param string $pid optional pid
 * @param string $arrowDirection   direction of link arrow
 */
function print_sosa_number($sosa, $pid = "", $arrowDirection = "up") {
	global $pbwidth, $pbheight;

	if (substr($sosa,-1,1)==".") {
		$personLabel = substr($sosa,0,-1);
	} else {
		$personLabel = $sosa;
	}
	if ($arrowDirection=="blank") {
		$visibility = "hidden";
	} else {
		$visibility = "normal";
	}
	echo "<td class=\"subheaders center\" style=\"vertical-align: middle; text-indent: 0px; margin-top: 0px; white-space: nowrap; visibility: ", $visibility, ";\">";
	echo $personLabel;
	if ($sosa != "1" && $pid != "") {
		if ($arrowDirection=="left") {
			$dir = 0;
		} elseif ($arrowDirection=="right") {
			$dir = 1;
		} elseif ($arrowDirection== "down") {
			$dir = 3;
		} else {
			$dir = 2; // either 'blank' or 'up'
		}
		echo '<br>';
		print_url_arrow($pid, '#'.$pid, $pid, $dir);
	}
	echo '</td>';
}

/**
 * print the parents table for a family
 *
 * @param string $famid family gedcom ID
 * @param int $sosa optional child sosa number
 * @param string $label optional indi label (descendancy booklet)
 * @param string $parid optional parent ID (descendancy booklet)
 * @param string $gparid optional gd-parent ID (descendancy booklet)
 */
function print_family_parents($famid, $sosa=0, $label='', $parid='', $gparid='', $personcount=1) {
	global $pbwidth, $pbheight, $WT_IMAGES, $GEDCOM;
	$ged_id=get_id_from_gedcom($GEDCOM);

	$family = WT_Family::getInstance($famid);
	if (is_null($family)) return;

	$husb = $family->getHusband();
	if (is_null($husb)) $husb = new WT_Person('');
	$wife = $family->getWife();
	if (is_null($wife)) $wife = new WT_Person('');

	if (!is_null($husb)) {
		$tempID = $husb->getXref();
		if (!empty($tempID)) echo "<a name=\"{$tempID}\"></a>";
	}
	if (!is_null($wife)) {
		$tempID = $wife->getXref();
		if (!empty($tempID)) echo "<a name=\"{$tempID}\"></a>";
	}
	if ($sosa != 0) {
		echo '<p class="name_head">', $family->getFullName(), '</p>';
	}
	// -- get the new record and parents if in editing show changes mode
	if (find_gedcom_record($famid, $ged_id) != find_gedcom_record($famid, $ged_id, WT_USER_CAN_EDIT)) {
		$newrec = find_gedcom_record($famid, $ged_id, true);
		$newparents = find_parents_in_record($newrec);
	}

	/**
	 * husband side
	 */
	echo "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\"><tr><td rowspan=\"2\">";
	echo "<table style=\"width: " . ($pbwidth) . "px; height: " . $pbheight . "px;\" border=\"0\"><tr>";
	if ($parid) {
		if ($husb->getXref()==$parid) print_sosa_number($label);
		else print_sosa_number($label, "", "blank");
	}
	else if ($sosa > 0) print_sosa_number($sosa * 2);
	if (isset($newparents) && $husb->getXref() != $newparents["HUSB"]) {
		echo "<td valign=\"top\" class=\"facts_valueblue\">";
		print_pedigree_person(WT_Person::getInstance($newparents['HUSB']), 1, 2, $personcount);
	} else {
		echo "<td valign=\"top\">";
		print_pedigree_person($husb, 1, 2, $personcount);
	}
	echo "</td></tr></table>";
	echo "</td>";
	// husband's parents
	$hfams = $husb->getChildFamilies();
	$hparents = false;
	$upfamid = "";

	if ($hfams || $sosa) {
		echo "<td rowspan=\"2\"><img src=\"".$WT_IMAGES["hline"]."\" alt=\"\"></td><td rowspan=\"2\"><img src=\"".$WT_IMAGES["vline"]."\" width=\"3\" height=\"" . ($pbheight+9) . "\" alt=\"\"></td>";
		echo "<td><img class=\"line5\" src=\"".$WT_IMAGES["hline"]."\" alt=\"\"></td><td>";
		$hparents = false;
		foreach ($hfams as $hfamily) {
			$hparents = find_parents_in_record($hfamily->getGedcomRecord());
			$upfamid = $hfamily->getXref();
			break;
		}
		if ($hparents || $sosa) {
			// husband's father
			echo "<table style=\"width: " . ($pbwidth) . "px; height: " . $pbheight . "px;\" border=\"0\"><tr>";
			if ($sosa > 0) print_sosa_number($sosa * 4, $hparents['HUSB'], "down");
			if (!empty($gparid) && $hparents['HUSB']==$gparid) print_sosa_number(trim(substr($label,0,-3),".").".");
			echo "<td valign=\"top\">";
			print_pedigree_person(WT_Person::getInstance($hparents['HUSB']), 1, 4, $personcount);
			echo "</td></tr></table>";
		}
		echo "</td>";
	}
	if (!empty($upfamid) && ($sosa!=-1)) {
		echo '<td valign="middle" rowspan="2">';
		print_url_arrow($upfamid, ($sosa==0 ? '?famid='.$upfamid.'&amp;ged='.WT_GEDURL : '#'.$upfamid), $upfamid, 1);
		echo '</td>';
	}
	if ($hparents || $sosa) {
		// husband's mother
		echo "</tr><tr><td><img src=\"".$WT_IMAGES["hline"]."\" alt=\"\"></td><td>";
		echo "<table style=\"width: " . ($pbwidth) . "px; height: " . $pbheight . "px;\" border=\"0\"><tr>";
		if ($sosa > 0) print_sosa_number($sosa * 4 + 1, $hparents['WIFE'], "down");
		if (!empty($gparid) && $hparents['WIFE']==$gparid) print_sosa_number(trim(substr($label,0,-3),".").".");
		echo "<td valign=\"top\">";
		print_pedigree_person(WT_Person::getInstance($hparents['WIFE']), 1, 5, $personcount);
		echo "</td></tr></table>";
		echo "</td>";
	}
	echo "</tr></table>";
	if ($sosa!=0) {
		echo '<a href="', $family->getHtmlUrl(), '" class="details1">';
		echo str_repeat("&nbsp;", 10);
		$marriage = $family->getMarriage();
		if ($marriage->canShow()) {
			$marriage->print_simple_fact();
		}
		echo "</a>";
	}
	else echo "<br>";

	/**
	 * wife side
	 */
	echo "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\"><tr><td rowspan=\"2\">";
	echo "<table style=\"width: " . ($pbwidth) . "px; height: " . $pbheight . "px;\"><tr>";
	if ($parid) {
		if ($wife->getXref()==$parid) print_sosa_number($label);
		else print_sosa_number($label, "", "blank");
	}
	else if ($sosa > 0) print_sosa_number($sosa * 2 + 1);
	if (isset($newparents) && $wife->getXref() != $newparents["WIFE"]) {
		echo "<td valign=\"top\" class=\"facts_valueblue\">";
		print_pedigree_person(WT_Person::getInstance($newparents['WIFE']), 1, 3, $personcount);
	} else {
		echo "<td valign=\"top\">";
		print_pedigree_person($wife, 1, 3, $personcount);
	}
	echo "</td></tr></table>";
	echo "</td>";
	// wife's parents
	$hfams = $wife->getChildFamilies();
	$hparents = false;
	$upfamid = "";
	
	if ($hfams || $sosa) {
		echo "<td rowspan=\"2\"><img src=\"".$WT_IMAGES["hline"]."\" alt=\"\"></td><td rowspan=\"2\"><img src=\"".$WT_IMAGES["vline"]."\" width=\"3\" height=\"" . ($pbheight+9) . "\" alt=\"\"></td>";
		echo "<td><img class=\"line5\" src=\"".$WT_IMAGES["hline"]."\" alt=\"\"></td><td>";
		$j = 0;
		foreach ($hfams as $hfamily) {
			$hparents = find_parents_in_record($hfamily->getGedcomRecord());
			$upfamid = $hfamily->getXref();
			break;
		}
		if ($hparents || $sosa) {
			// wife's father
			echo "<table style=\"width: " . ($pbwidth) . "px; height: " . $pbheight . "px;\"><tr>";
			if ($sosa > 0) print_sosa_number($sosa * 4 + 2, $hparents['HUSB'], "down");
			if (!empty($gparid) && $hparents['HUSB']==$gparid) print_sosa_number(trim(substr($label,0,-3),".").".");
			echo "<td valign=\"top\">";
			print_pedigree_person(WT_Person::getInstance($hparents['HUSB']), 1, 6, $personcount);
			echo "</td></tr></table>";
		}
		echo "</td>";
	}
	if (!empty($upfamid) && ($sosa!=-1)) {
		echo '<td valign="middle" rowspan="2">';
		print_url_arrow($upfamid, ($sosa==0 ? '?famid='.$upfamid.'&amp;ged='.WT_GEDURL : '#'.$upfamid), $upfamid, 1);
		echo '</td>';
	}
	if ($hparents || $sosa) {
		// wife's mother
		echo "</tr><tr><td><img src=\"".$WT_IMAGES["hline"]."\" alt=\"\"></td><td>";
		echo "<table style=\"width: " . ($pbwidth) . "px; height: " . $pbheight . "px;\"><tr>";
		if ($sosa > 0) print_sosa_number($sosa * 4 + 3, $hparents['WIFE'], "down");
		if (!empty($gparid) && $hparents['WIFE']==$gparid) print_sosa_number(trim(substr($label,0,-3),".").".");
		echo "<td valign=\"top\">";
		print_pedigree_person(WT_Person::getInstance($hparents['WIFE']), 1, 7, $personcount);
		echo "</td></tr></table>";
		echo "</td>";
	}
	echo "</tr></table>";
}

/**
 * print the children table for a family
 *
 * @param string $famid family gedcom ID
 * @param string $childid optional child ID
 * @param int $sosa optional child sosa number
 * @param string $label optional indi label (descendancy booklet)
 */
function print_family_children($famid, $childid = "", $sosa = 0, $label="", $personcount="1") {
	global $bwidth, $bheight, $pbwidth, $pbheight, $cbheight, $cbwidth, $show_cousins, $WT_IMAGES, $GEDCOM, $TEXT_DIRECTION;

	$family=WT_Family::getInstance($famid);
	$children=array();
	foreach ($family->getChildren() as $child) {
		$children[]=$child->getXref();
	}
	$numchil=$family->getNumberOfChildren();
	echo "<table border=\"0\" cellpadding=\"0\" cellspacing=\"2\"><tr>";
	if ($sosa>0) echo "<td></td>";
	echo "<td><span class=\"subheaders\">";
	if ($numchil==0) {
		echo WT_I18N::translate('No children');
	} else {
		echo /* I18N: This is a title, so needs suitable capitalisation */ WT_I18N::plural('%d Child', '%d Children', $numchil, $numchil);
	}
	echo '</span>';

	if ($sosa==0 && WT_USER_CAN_EDIT) {
		echo '<br>';
		echo "<a href=\"#\" onclick=\"return addnewchild('$famid','');\">" . WT_I18N::translate('Add a child to this family') . "</a>";
		echo ' <a class="icon-sex_m_15x15" href="#" onclick="return addnewchild(\'', $famid, '\',\'M\');" title="',WT_I18N::translate('son'), '"></a>';
		echo ' <a class="icon-sex_f_15x15" href="#" onclick="return addnewchild(\'', $famid, '\',\'F\');" title="',WT_I18N::translate('daughter'), '"></a>';
		echo '<br><br>';
	}
	echo '</td>';
	if ($sosa>0) {
		echo '<td></td><td></td>';
	}
	echo '</tr>';

	$newchildren = array();
	$oldchildren = array();
	if (WT_USER_CAN_EDIT || WT_USER_CAN_ACCEPT) {
		$newrec = find_gedcom_record($famid, WT_GED_ID, true);
		$ct = preg_match_all("/1 CHIL @(.*)@/", $newrec, $match, PREG_SET_ORDER);
		if ($ct > 0) {
			$oldchil = array();
			for ($i = 0; $i < $ct; $i++) {
				if (!in_array($match[$i][1], $children)) $newchildren[] = $match[$i][1];
				else $oldchil[] = $match[$i][1];
			}
			foreach ($children as $indexval => $chil) {
				if (!in_array($chil, $oldchil)) $oldchildren[] = $chil;
			}
			//-- if there are no old or new children then the children were reordered
			if ((count($newchildren)==0) && (count($oldchildren)==0)) {
				$children = array();
				for ($i = 0; $i < $ct; $i++) {
					$children[] = $match[$i][1];
				}
			}
		}
	}
	$nchi=1;
	if ((count($children) > 0) || (count($newchildren) > 0) || (count($oldchildren) > 0)) {
		foreach ($children as $indexval => $chil) {
			if (!in_array($chil, $oldchildren)) {
				echo "<tr>";
				if ($sosa != 0) {
					if ($chil == $childid) {
						print_sosa_number($sosa, $childid);
					} elseif (empty($label)) {
						print_sosa_number("");
					} else {
						print_sosa_number($label.($nchi++).".");
					}
				}
				echo "<td valign=\"middle\" >";
				print_pedigree_person(WT_Person::getInstance($chil), 1, 8, $personcount);
				$personcount++;
				echo "</td>";
				if ($sosa != 0) {
					// loop for all families where current child is a spouse
					$famids = WT_Person::getInstance($chil)->getSpouseFamilies();
					
					
					$maxfam = count($famids)-1;
					for ($f=0; $f<=$maxfam; $f++) {
						$famid_child = $famids[$f]->getXref();
						$parents = find_parents($famid_child);
						if ($parents["HUSB"] == $chil) $spouse = $parents["WIFE"];
						else $spouse =  $parents["HUSB"];
						// multiple marriages
						if ($f>0) {
							echo "</tr><tr><td>&nbsp;</td>";
							echo "<td valign=\"top\"";
							if ($TEXT_DIRECTION == "rtl") echo " align=\"left\">";
							else echo " align=\"right\">";
							//if ($f==$maxfam) echo "<img height=\"50%\"";
							//else echo "<img height=\"100%\"";
							
							//find out how many cousins there are to establish vertical line on second families
							$family=WT_Family::getInstance($famid_child);
							$fchildren=$family->getChildren();
							$kids = count($fchildren);
							$PBheight = $bheight;
							$Pheader = ($cbheight*$kids)-$bheight;
							$PBadj = 6;	// default
							if ($show_cousins>0) {
								if (($cbheight * $kids) > $bheight) {
									$PBadj = ($Pheader/2+$kids*4.5);
								} 
							}

							if ($PBadj<0) $PBadj=0;
							if ($f==$maxfam) echo "<img height=\"".( (($bheight/2))+$PBadj)."px\"";
							else echo "<img height=\"".$pbheight."px\"";
							echo " width=\"3\" src=\"".$WT_IMAGES["vline"]."\" alt=\"\">";
							echo "</td>";
						}
						echo "<td class=\"details1\" valign=\"middle\" align=\"center\">";
						$famrec = find_family_record($famid_child, WT_GED_ID);
						$marrec = get_sub_record(1, "1 MARR", $famrec);
						$divrec = get_sub_record(1, "1 DIV",  $famrec);
						if (canDisplayFact($famid_child, WT_GED_ID, $marrec)) {
							// marriage date
							$ct = preg_match("/2 DATE.*(\d\d\d\d)/", $marrec, $match);
							if ($ct>0) echo "<span class=\"date\">".trim($match[1])."</span>";
							// divorce date
							$ct = preg_match("/2 DATE.*(\d\d\d\d)/", $divrec, $match);
							if ($ct>0) echo "-<span class=\"date\">".trim($match[1])."</span>";
						}
						echo "<br><img width=\"100%\" class=\"line5\" height=\"3\" src=\"".$WT_IMAGES["hline"]."\" alt=\"\">";
						// family link
						if ($famid_child) {
							$family_child = WT_Family::getInstance($famid_child);
							if ($family_child) {
								echo "<br>";
								echo '<a class="details1" href="', $family_child->getHtmlUrl(), '">';
								// TODO: shouldn't there be something inside this <a></a>
								echo "</a>";
							}
						}
						echo "</td>";
						// spouse information
						echo "<td style=\"vertical-align: center;";
						if (!empty($divrec)) echo " filter:alpha(opacity=40);opacity:0.4;\">";
						else echo "\">";
						print_pedigree_person(WT_Person::getInstance($spouse), 1, 9, $personcount);
						$personcount++;
						echo "</td>";
						// cousins
						if ($show_cousins) {
							print_cousins($famid_child, $personcount);
							$personcount++;
						}
					}
				}
				echo "</tr>";
			}
		}
		foreach ($newchildren as $indexval => $chil) {
			echo "<tr >";
			echo "<td valign=\"top\" class=\"facts_valueblue\" style=\"width: " . ($pbwidth) . "px; height: " . $pbheight . "px;\">";
			print_pedigree_person(WT_Person::getInstance($chil), 1, 0, $personcount);
			$personcount++;
			echo "</td></tr>";
		}
		foreach ($oldchildren as $indexval => $chil) {
			echo "<tr >";
			echo "<td valign=\"top\" class=\"facts_valuered\" style=\"width: " . ($pbwidth) . "px; height: " . $pbheight . "px;\">";
			print_pedigree_person(WT_Person::getInstance($chil), 1, 0, $personcount);
			$personcount++;
			echo "</td></tr>";
		}
		// message 'no children' except for sosa
	} elseif ($sosa<1) {
		if (preg_match('/\n1 NCHI (\d+)/', $family->getGedcomRecord(), $match) && $match[1]==0) {
			echo '<tr><td><i class="icon-childless"></i> '.WT_I18N::translate('This family remained childless').'</td></tr>';
		}
	} else {
		echo "<tr>";
		print_sosa_number($sosa, WT_Person::getInstance($chil));
		echo "<td valign=\"top\">";
		print_pedigree_person(WT_Person::getInstance($childid), 1, 0, $personcount);
		$personcount++;
		echo "</td></tr>";
	}
	echo "</table><br>";
}

/**
 * print a family with Sosa-Stradonitz numbering system
 * ($rootid=1, father=2, mother=3 ...)
 *
 * @param string $famid family gedcom ID
 * @param string $childid tree root ID
 * @param string $sosa starting sosa number
 * @param string $label optional indi label (descendancy booklet)
 * @param string $parid optional parent ID (descendancy booklet)
 * @param string $gparid optional gd-parent ID (descendancy booklet)
 */
function print_sosa_family($famid, $childid, $sosa, $label="", $parid="", $gparid="", $personcount="1") {
	global $pbwidth, $pbheight;

	echo "<hr>";
	echo "<p style='page-break-before:always'>";
	if (!empty($famid)) echo "<a name=\"{$famid}\"></a>";
	print_family_parents($famid, $sosa, $label, $parid, $gparid, $personcount);
	$personcount++;
	echo "<br>";
	echo "<table width=\"95%\"><tr><td valign=\"top\" style=\"width: " . ($pbwidth) . "px;\">";
	print_family_children($famid, $childid, $sosa, $label, $personcount);
	echo "</td></tr></table>";
	echo "<br>";
}

/**
 * creates an array with all of the individual ids to be displayed on an ascendancy chart
 *
 * the id in position 1 is the root person.  The other positions are filled according to the following algorithm
 * if an individual is at position $i then individual $i's father will occupy position ($i*2) and $i's mother
 * will occupy ($i*2)+1
 *
 * @param string $rootid
 * @return array $treeid
 */
function ancestry_array($rootid, $maxgen=0) {
	global $PEDIGREE_GENERATIONS;
	// -- maximum size of the id array
	if ($maxgen==0) $maxgen = $PEDIGREE_GENERATIONS;
	$treesize = pow(2, ($maxgen));

	$treeid = array();
	$treeid[0] = "";
	$treeid[1] = $rootid;
	// -- fill in the id array
	for ($i = 1; $i < ($treesize / 2); $i++) {
		$treeid[($i * 2)] = false; // -- father
		$treeid[($i * 2) + 1] = false; // -- mother
		if (!empty($treeid[$i])) {
			$person = WT_Person::getInstance($treeid[$i]);
			$family = $person->getPrimaryChildFamily();
			if ($family) {
				if ($family->getHusband()) {
					$treeid[$i*2]=$family->getHusband()->getXref();
				}
				if ($family->getWife()) {
					$treeid[$i*2+1]=$family->getWife()->getXref();
				}
			}
		}
	}
	return $treeid;
}

/**
 * print an arrow to a new url
 *
 * @param string $id Id used for arrow img name (must be unique on the page)
 * @param string $url target url
 * @param string $label arrow label
 * @param string $dir arrow direction 0=left 1=right 2=up 3=down (default=2)
 */
function print_url_arrow($id, $url, $label, $dir=2) {
	global $TEXT_DIRECTION;

	if ($id=="" || $url=="") return;

	// arrow direction
	$adir=$dir;
	if ($TEXT_DIRECTION=="rtl" && $dir==0) $adir=1;
	if ($TEXT_DIRECTION=="rtl" && $dir==1) $adir=0;


	// arrow style     0         1         2         3
	$array_style=array("icon-larrow", "icon-rarrow", "icon-uarrow", "icon-darrow");
	$astyle=$array_style[$adir];

	// Labels include people's names, which may contain markup
	echo '<a href="'.$url.'" title="'.strip_tags($label).'" class="'.$astyle.'"></a>';
}

/**
 * builds and returns sosa relationship name in the active language
 *
 * @param string $sosa sosa number
 */
function get_sosa_name($sosa) {
	$path='';
	while ($sosa>1) {
		if ($sosa%2==1) {
			$sosa-=1;
			$path = 'mot' . $path;
		} else {
			$path = 'fat' . $path;
		}
		$sosa/=2;
	}
	return get_relationship_name_from_path($path, null, null);
}

/**
 * print cousins list
 *
 * @param string $famid family ID
 */
function print_cousins($famid, $personcount=1) {
	global $show_full, $bheight, $bwidth, $cbheight, $cbwidth, $WT_IMAGES, $TEXT_DIRECTION, $GEDCOM;

	$ged_id=get_id_from_gedcom($GEDCOM);
	$family=WT_Family::getInstance($famid);
	$fchildren=$family->getChildren();

	$kids = count($fchildren);
	$save_show_full = $show_full;
	$sbheight = $bheight;
	$sbwidth = $bwidth;
	if ($save_show_full) {
		$bheight = $cbheight;
		$bwidth  = $cbwidth;
	}  
	
	$show_full = false;
	echo '<td valign="middle" height="100%">';
	if ($kids) {
		echo '<table cellspacing="0" cellpadding="0" border="0" ><tr valign="middle">';
		if ($kids>1) echo '<td rowspan="', $kids, '" valign="middle" align="right"><img width="3px" height="', (($bheight+9)*($kids-1)), 'px" src="', $WT_IMAGES["vline"], '" alt=""></td>';
		$ctkids = count($fchildren);
		$i = 1;
		foreach ($fchildren as $fchil) {
			if ($i==1) {
			echo '<td><img width="10px" height="3px" align="top" style="padding-';
		} else {
			echo '<td><img width="10px" height="3px" style="padding-';
		}
			if ($TEXT_DIRECTION=='ltr') echo 'right';
			else echo 'left';
			echo ': 2px;" src="', $WT_IMAGES["hline"], '" alt=""></td><td>';
			print_pedigree_person($fchil, 1 , 0, $personcount);
			$personcount++;
			echo '</td></tr>';
			if ($i < $ctkids) {
				echo '<tr>';
				$i++;
			}
		}
		echo '</table>';
	} else {
		// If there is known that there are no children (as opposed to no known children)
		if (preg_match('/\n1 NCHI (\d+)/', $family->getGedcomRecord(), $match) && $match[1]==0) {
			echo ' <i class="icon-childless" title="', WT_I18N::translate('This family remained childless'), '"></i>';
		}
	}
	$show_full = $save_show_full;
	if ($save_show_full) {
		$bheight = $sbheight;
		$bwidth  = $sbwidth;
	}
	echo '</td>';
}
