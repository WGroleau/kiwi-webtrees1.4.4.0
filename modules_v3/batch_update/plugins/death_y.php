<?php
// Batch Update plugin for phpGedView - add missing 1 BIRT/DEAT Y
//
// webtrees: Web based Family History software
// Copyright (C) 2011 webtrees development team.
//
// Derived from PhpGedView
// Copyright (C) 2008 Greg Roach.  All rights reserved.
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

class death_y_bu_plugin extends base_plugin {
	static function getName() {
		return WT_I18N::translate('Add missing death records');
	}

	static function getDescription() {
		return WT_I18N::translate('You can speed up the privacy calculations by adding a death record to individuals whose death can be inferred from other dates, but who do not have a record of death, burial, cremation, etc.');
	}

	static function doesRecordNeedUpdate($xref, $gedrec) {
		return !preg_match('/\n1 ('.WT_EVENTS_DEAT.')/', $gedrec) && WT_Person::getInstance($xref)->isDead();
	}

	static function updateRecord($xref, $gedrec) {
		return $gedrec."\n1 DEAT Y";
	}
}
