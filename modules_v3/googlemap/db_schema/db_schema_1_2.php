<?php
// Update the GM module database schema from version 1 to version 2
//
// Version 0: empty database
// Version 1: create the tables, as per PGV 4.2.1
// Version 2: update the tables to support streetview
//
// The script should assume that it can be interrupted at
// any point, and be able to continue by re-running the script.
// Fatal errors, however, should be allowed to throw exceptions,
// which will be caught by the framework.
// It shouldn't do anything that might take more than a few
// seconds, for systems with low timeout values.
//
// webtrees: Web based Family History software
// Copyright (C) 2011 webtrees development team.
//
// Derived from PhpGedView
// Copyright (C) 2009 Greg Roach
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
// $Id: db_schema_1_2.php 14468 2012-10-27 19:33:55Z greg $

if (!defined('WT_WEBTREES')) {
	header('HTTP/1.0 403 Forbidden');
	exit;
}

// Create all of the tables needed for this module
try {
	WT_DB::exec(
		"ALTER TABLE `##placelocation` ADD (".
		" pl_media      VARCHAR(60)     NULL,".
		" sv_long       FLOAT           NOT NULL DEFAULT 0,".
		" sv_lati       FLOAT           NOT NULL DEFAULT 0,".
		" sv_bearing    FLOAT           NOT NULL DEFAULT 0,".
		" sv_elevation  FLOAT           NOT NULL DEFAULT 0,".
		" sv_zoom       FLOAT           NOT NULL DEFAULT 1".
		")"
	);
} catch (PDOException $ex) {
	// Already done this?
}

// Update the version to indicate success
WT_Site::preference($schema_name, $next_version);
