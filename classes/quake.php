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

require_once csQuery_DIR . 'csQuery.php';

/**
 * @brief Abstract class that implements quake related stuff
 * @author Jeremias Reith (jr@csQuery.org)
 * @author Narfight (narfight@lna.be)
 * @version $Rev: 4351 $
 *
 * Implements everything that all quake protocols have in common
 */
class quake extends csQuery
{

  /**
   * @brief Sends a rcon command to the game server
   *
   * @param command the command to send
   * @param rcon_pwd rcon password to authenticate with
   * @return the result of the command or FALSE on failure
   */
  public function rcon_query_server($command, $rcon_pwd)
  {
    $command="\xFF\xFF\xFF\xFF\x02rcon ".$rcon_pwd." ".$command."\x0a\x00";
    if (!($result=$this->_sendCommand($this->address,$this->queryport,$command))) {
      $this->errstr="Error sending rcon command";
      $this->debug['Command send ' . $command]='No reply received';

      return FALSE;
    } else {
      return $result;
    }
  }
}
