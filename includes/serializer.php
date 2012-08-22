<?php header("Content-type: text/plain", TRUE); ?>
<?php

/**
 * Clansuite Gameserver Query
 * Jens-André Koch © 2005 - onwards
 *
 * This file is part of "Clansuite Gameserver Query".
 *
 * License: GNU/LGPL 2.1+
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation; either version 2.1 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

include_once '../csQuery.php';

$gameserver=csQuery::createInstance($_GET["protocol"], $_GET["host"], $_GET["queryport"]);

if (!$gameserver) {
  echo serialize($gameserver);
  exit(0);
}

$gameserver->query_server(TRUE, TRUE);

echo $gameserver->serialize();
