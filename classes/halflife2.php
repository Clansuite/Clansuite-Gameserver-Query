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

require_once csQuery_DIR . 'classes/halflife.php';

/**
 * @brief This class implements the protocol used by halflife 2
 * @author Curtis Brown <webmaster@2dementia.com>
 * @version $Rev: 4351 $
 * @todo preventing DoS with data containing no \x00's
 * @todo clean up for basic info retrieval required
 */
class halflife2 extends halflife
{

  public function query_server($getPlayers=TRUE,$getRules=TRUE)
  {
    // flushing old data if necessary
    if ($this->online) {
      $this->_init();
    }

    $command="\xFF\xFF\xFF\xFFT";
    if (!($result=$this->_sendCommand($this->address,$this->queryport,$command))) {
      return FALSE;
    }

    $i=5;// start after header

    $this->gameversion=ord($result{5});
    $this->hostport = $this->queryport;

    $basic = explode("\x00", substr($result, 6));


    // XXX: Replace old code
    $this->rules['gamedir']='';

    while ($result[$i]!="\x00") $this->servertitle.=$result[$i++];
    $i++;
    while ($result[$i]!="\x00") $this->mapname.=$result[$i++];
    $i++;
    while ($result[$i]!="\x00") $this->rules['gamedir'].=$result[$i++];
    $i++;
    while ($result[$i]!="\x00") $this->gamename.=$result[$i++];
    $i++;
    $this->rules['steamid']=ord($result{$i}) | (ord($result{$i+1})<<8);
    $i+=2;
    $this->numplayers=ord(substr($result,$i++,1));
    $this->maxplayers=ord(substr($result,$i++,1));
    $this->rules['botplayers']=ord(substr($result,$i++,1));
    $this->rules['dedicated']=($result[$i++]=='d' ? 'Yes' : 'No');
    $this->rules['server_os']=($result[$i++]=='l' ? 'Linux' : 'Windows');
    $this->password=ord(substr($result,$i++,1));
    $this->rules['secure']=($result[$i++]=='1' ? 'Yes' : 'No');

    $this->servertitle = $basic[0];
    $this->mapname = $basic[1];
    $this->rules['gamedir'] = $basic[2];
    $this->gamename = preg_replace('/[ :]/', '_', strtolower($basic[3]));

    // do rules
    $command="\xFF\xFF\xFF\xFFV";
    if (!($result=$this->_sendCommand($this->address,$this->queryport,$command))) {
      return FALSE;
    }

    $exploded_data = explode("\x00", $result);

    $z=count($exploded_data);
    for ($i=1;$i<$z;$i++) {
      switch ($exploded_data[$i++]) {
      case 'sv_password':
    $this->password=$exploded_data[$i];
    break;
      case 'deathmatch':
    if ($exploded_data[$i]=='1') $this->gametype='Deathmatch';
    break;
      case 'coop':
    if ($exploded_data[$i]=='1') $this->gametype='Cooperative';
    break;
      default:
    if (isset($exploded_data[$i-1]) && isset($exploded_data[$i])) {
      $this->rules[$exploded_data[$i-1]]=$exploded_data[$i];
    }
      }
    }

    if ($getPlayers) {
      // do players
      $command="\xFF\xFF\xFF\xFFU";
      if (!($result=$this->_sendCommand($this->address,$this->queryport,$command))) {
    return FALSE;
      }

      $this->_processPlayers($result, $this->playerFormat, 8);

      $this->playerkeys['name']=TRUE;
      $this->playerkeys['score']=TRUE;
      $this->playerkeys['time']=TRUE;
    }

    $this->online = TRUE;

    return TRUE;
  }
}
