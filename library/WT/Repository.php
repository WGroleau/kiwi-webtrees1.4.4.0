<?php
// Class file for a Repository (REPO) object
//
// webtrees: Web based Family History software
// Copyright (C) 2011 webtrees development team.
//
// Derived from PhpGedView
// Copyright (C) 2002 to 2009  PGV Development Team.  All rights reserved.
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
// @version $Id$

if (!defined('WT_WEBTREES')) {
	header('HTTP/1.0 403 Forbidden');
	exit;
}

class WT_Repository extends WT_GedcomRecord {
	// Fetch the record from the database
	protected static function fetchGedcomRecord($xref, $ged_id) {
		static $statement=null;

		if ($statement===null) {
			$statement=WT_DB::prepare(
				"SELECT o_type AS type, o_id AS xref, o_file AS ged_id, o_gedcom AS gedrec ".
				"FROM `##other` WHERE o_id=? AND o_file=? AND o_type='REPO'"
			);
		}
		return $statement->execute(array($xref, $ged_id))->fetchOneRow(PDO::FETCH_ASSOC);
	}
	
	// Generate a URL to this record, suitable for use in HTML
	public function getHtmlUrl() {
		return parent::_getLinkUrl('repo.php?rid=', '&amp;');
	}

	// Generate a URL to this record, suitable for use in javascript, HTTP headers, etc.
	public function getRawUrl() {
		return parent::_getLinkUrl('repo.php?rid=', '&');
	}

	// Generate a private version of this record
	protected function createPrivateGedcomRecord($access_level) {
		return "0 @".$this->xref."@ REPO\n1 NAME ".WT_I18N::translate('Private');
	}

	// Get an array of structures containing all the names in the record
	public function getAllNames() {
		return parent::_getAllNames('NAME', 1);
	}
}
