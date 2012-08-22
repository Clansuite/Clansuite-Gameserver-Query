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

/**
 * Clansuite Gameserver Query is a fork of the deprecated gsQuery by Jeremias Reith.
 * http://www.gsQuery.org/
 * Copyright (c) 2002-2004 Jeremias Reith <jr@terragate.net>
 * It's inspired by gameq, squery, phgstats and projectes like kquery and hlsw.
 */

if (!defined('csQuery_DIR')) {
  define('csQuery_DIR', __DIR__ . DIRECTORY_SEPARATOR);
}

/**
 * <p>The csQuery package has one class for each protocol or game.
 * This class is abstract but due to the lack of real OO
 * capabilities it cannot be declared as abstract.
 * Use the static method createInstance to create a csQuery object
 * that supports the specified protocol.</p>
 *
 * Generic usage:
 * <pre>
 *   // including csQuery
 *   include_once('path/to/csQuery/csQuery.php');
 *
 *   // create a csQuery instance
 *   $gameserver = csQuery::createInstance('gameSpy', 'myserver.com', 1234)
 *
 *   // query the server
 *   $status = $gameserver->query_server();
 *
 *   // check for success
 *   if ($status) {
 *     // process retrieved data
 *   } else {
 *     // create an error message
 *   }
 * </pre>
 */
class csQuery
{
  /**  The version of the csQuery package */
  public $version = '0.1';

  /**  ip or hostname of the server */
  public $address;

  /**  port to use for the query */
  public $queryport;

  /**
   *  status of the server
   *
   * TRUE: server online, FALSE: server offline
   */
  public $online;

  /**  the name of the game */
  public $gamename;

  /**  the port you have to connect to enter the game */
  public $hostport;

  /**  the version of the game */
  public $gameversion;

  /**  The title of the server */
  public $servertitle;

  /**  The name of the map (often corresponds with the filename of the map)*/
  public $mapname;

  /**  A more descriptive name of the map */
  public $maptitle;

  /**  The gametype */
  public $gametype;

  /**  current number of players on the server */
  public $numplayers;

  /**  maximum number of players allowed on the server */
  public $maxplayers;

  /**
   *  Wheather the game server is password protected
   *
   *  1: server is password protected<br>
   *  0: server is not password protected<br>
   * -1: unknown
   */
  public $password;

  /**  next map on the server */
  public $nextmap;

  /**
   *  players playing on the server
   * @see playerkeys
   *
   * Hash with player ids as key.
   * The containing value will be another hash with the infos of the player.
   * To access a player name use <code>players[$playerid]['name']</code>.
   * Check playerkeys to get the keys available
   */
  public $players;

  /**
   *  Hash of available player infos
   *
   * There is a key for each player info available (e.g. name, score, ping etc).
   * The value is TRUE if the info is available
   */
  public $playerkeys;

  /**  list of the team names */
  public $playerteams;

  /**  a list of all maps in cycle */
  public $maplist;

  /**
   *  Hash with all server rules
   *
   * key:   rulename<br>
   * value: rulevalue
   */
  public $rules;

  /**  Short errormessage if something goes wrong */
  public $errstr;

  /**
   *  Array with debug infos
   *
   * Stores all the send/received data
   * Format: send data => received data
   */
  public $debug;

  /**
   *  Standard constructor
   *
   * @param address address of the server to query
   * @param queryport the queryport of the server
   */
  public function csQuery($address, $queryport)
  {
    $this->address = $address;
    $this->queryport = $queryport;
    // clear publics
    $this->_init();
  }

  /**
   * Creates a new csQuery object that supports the given protocol
   *
   * This static method will create an instance of the appropriate subclass
   * for you.
   *
   * @param protocol the protocol you need
   * @param address the address of the game server
   * @param port the queryport of the game server
   * @return a csQuery object that supports the specified protocol
   */
  public function createInstance($protocol, $address, $port)
  {
        // including the required class and create an instance of it
        switch ($protocol) {
            // some aliases might be useful
            case('gsqp'):
                require_once csQuery_DIR . 'classes/gameSpy.php';

                return new gameSpy($address, $port);
            case 'ravenshield':
                require_once csQuery_DIR . 'classes/rvnshld.php';

                return new rvnshld($address, $port);


            default:
                require_once(csQuery_DIR . 'classes/' . $protocol . '.php');

                return new $protocol($address, $port);
        }
    }

  /**
   * Creates an instance out of an previously serialized string
   *
   * Use this to restore a object that has been previously serialized with
   * serialize
   *
   * @param string serialized csQuery object
   * @return the deserialized object
   */
  public function unserialize($string)
  {
    // extracting class name
    $length = strlen($string);
    for ($i=0;$i<$length;$i++) {
      if ($string[$i] == ':') {
    break;
      }
    }

    $className = substr($string, 0, $i);

    // we should be careful when using eval with supplied arguments
    if (ereg("^[A-Za-z0-9_-]+$", $className)) {
      include_once(csQuery_DIR . 'classes/'. $className .'.php');

       return unserialize(base64_decode(substr($string, $i+1)));
     } else {
      return FALSE;
    }
  }

  /**
   * Retrieves a serialized object via HTTP and deserializes it
   *
   * Useful if UDP traffic isn't allowed
   *
   * @param url the URL of the object
   * @return the deserialized object
   */
  public function unserializeFromURL($url)
  {
    require_once(csQuery_DIR . 'includes/HttpClient.class.php');

    return csQuery::unserialize(HttpClient::quickGet($url));
  }

  /**
   * Returns all supported protocols / games
   *
   * This method is static.
   * There should be no other php files in the csQuery directory
   *
   * @return An array with names of the supported protocols
   */
  public function getSupportedProtocols()
  {
    if (!$handle=opendir(csQuery_DIR)) {
       return FALSE;
    }

    $result=array();

    while (false!==($curfile=readdir($handle))) {
      if ($curfile!='csQuery.php' && $curfile!='index.php' && ereg("^(.*)\.php$", $curfile, $matches)) {
    array_push($result, $matches[1]);
      }
    }
    closedir($handle);

    return $result;
  }

  /**
   * Returns a HLSW Gamebrowser URI
   * @see http://www.hlsw.de
   *
   * The server has to be queried before in some cases
   * (to find out the game port).
   * The client needs HLSW Gamebrowser to be installed
   * to join the game with this URI
   *
   * @return A HLSW URI
   */
  public function getHLSWURI()
  {
    return 'hlsw://'. $this->gamename .'@'. $this->address .':'. $this->hostport .'/';
  }

  /**
   * Returns a native join URI
   *
   * Some games are registering an URI type to allow easy joining of games
   *
   * @return a native join URI or false if not implemented for the game
   */
  public function getNativeJoinURI()
  {
    return FALSE;
  }

  /**
   * Querys the server
   *
   * This method is abstract
   *
   * @param getPlayers wheather to retrieve player infos
   * @param getRules wheather to retrieve rules
   * @return TRUE on success
   */
  public function query_server($getPlayers=TRUE,$getRules=TRUE)
  {
    $this->errstr = 'This class cannot be used to query a server';

    return FALSE;
  }

  /**
   * Sorts the given players
   *
   * You can sort by name, score, frags, deaths, honor and time
   *
   * @param players players to sort
   * @param sortkey sort by the given key
   * @return sorted player hash
   */
  public function sortPlayers($players, $sortkey='name')
  {
    if (!sizeof($players)) {
      return array();
    }
    switch ($sortkey) {
        default:
        case 'name':
          uasort($players, array('csQuery', '_sortbyName'));
          break;
        case 'score':
          uasort($players, array('csQuery', '_sortbyScore'));
          break;
        case 'frags':
          uasort($players, array('csQuery', '_sortbyFrags'));
          break;
        case 'deaths':
          uasort($players, array('csQuery', '_sortbyDeaths'));
          break;
        case 'kills':
          uasort($players, array('csQuery', '_sortbyKills'));
          break;
        case 'time':
          uasort($players, array('csQuery', '_sortbyTime'));
          break;
    }

    return ($players);
  }

  /**
   * htmlizes the given raw string
   *
   * @param string a raw string from the gameserver that might contain special chars
   * @return a html version of the given string
   */
  public function htmlize($string)
  {
    return htmlentities($string);
  }

  /**
   * converts the raw string to ascii
   *
   * @param string a raw string from the gameserver that might contain special chars
   * @return a plain text version of the given string
   */
  public function textify($string)
  {
    return $string;
  }

  /**
   * serializes the object as string
   *
   * @return serialized object representation
   */
  public function serialize()
  {
    return $this->_getClassName() .':'. base64_encode(serialize($this));
  }

  /**
   * Creates hexdumps out of the debug info
   *
   * Pass an HexDumper instance if you want to format the dump yourself.
   * Each element of the result will contain a 2 element array with the hexdump
   * of the send data first and the dump of the received data behind it.
   *
   * @param html whether to create an html hexdump
   * @param dumper an optional preconfigured HexDumper instance
   * @return an array with hexdumps for each send command/received result
   */
  public function getDebugDumps($html=FALSE, $dumper=NULL)
  {
    require_once(csQuery_DIR . 'includes/HexDumper.class.php');

    if (!isset($dumper)) {
      $dumper = new HexDumper();
    }

    $dumps = array();
    $dumpFunction = array($dumper, $html ? 'createHTMLDump' : 'createASCIIDump');

    foreach ($this->debug as $curCommand) {
      $dumps[] = array_map($dumpFunction, $curCommand);
    }

    return $dumps;
  }

  // better idea?
  private function _sortbyName($a, $b)
  {
    return(strcasecmp($a['name'], $b['name']));
  }

  private function _sortbyScore($a, $b)
  {
    if ($a['score']==$b['score']) { return 0; } elseif ($a['score']<$b['score']) { return 1; } else { return -1; }
  }

  private function _sortbyFrags($a, $b)
  {
    if ($a['frags']==$b['frags']) { return 0; } elseif ($a['frags']<$b['frags']) { return 1; } else { return -1; }
  }

  private function _sortbyDeaths($a, $b)
  {
    if ($a['deaths']==$b['deaths']) { return 0; } elseif ($a['deaths']<$b['deaths']) { return 1; } else { return -1; }
  }

  private function _sortbyTime($a, $b)
  {
    if ($a['time']==$b['time']) { return 0; } elseif ($a['time']<$b['time']) { return 1; } else { return -1; }
  }

  private function _sortbyKills($a, $b)
  {
    if ($a['kills']==$b['kills']) { return 0; } elseif ($a['kills']<$b['kills']) { return 1; } else { return -1; }
  }

  /**
   * @internal  This method deletes all fetched data
   *
   * This method should be called if an instance is used for multiple querys
   */
  private function _init()
  {
    $this->online = FALSE;
    $this->hostport = 0;
    $this->gameversion = '';
    $this->servertitle = '';
    $this->hostport = '';
    $this->mapname = '';
    $this->maptitle = '';
    $this->gametype = '';
    $this->numplayers = 0;
    $this->maxplayers = 0;
    $this->password = -1;
    $this->nextmap='';

    $this->players = array();
    $this->playerkeys = array();
    $this->playerteams = array();
    $this->maplist = array();
    $this->rules = array();
    $this->debug = array();

    $this->errstr='';
  }

  /**
   * @internal  sends a command to a server and returns the answer
   *
   * @param address ip or hostname of the server
   * @param port port to connect to
   * @param command data to send
   * @param timeout how long to wait for data (in seconds)
   * @return the raw answser
   *
   */
  private function _sendCommand($address, $port, $command)
  {

    if (!$socket=@fsockopen('udp://'.$address, $port)) {
      $this->errstr='Cannot open a socket!';

      return FALSE;
    } else {
      socket_set_blocking($socket, true);
      // socket_set_timeout should be used here but this requires PHP >=4.3
      socket_set_timeout($socket, 0, 5000000);

      // send command
      if (fwrite($socket, $command, strlen($command))==-1) {
    fclose($socket);
    $this->errstr='Unable to write on open socket!';

    return FALSE;
      }

      $result='';
      do {
    $result .= fread($socket, 128);
    $socketstatus = socket_get_status($socket);
      } while ($socketstatus['unread_bytes']);

      fclose($socket);
      if (!isset($result)) {
    $this->debug[] = array($command, '');

    return FALSE;
      }
      $this->debug[] = array($command, $result);

      return $result;
    }
  }

  /**
   *  returns the class name of the instance
   * @return the class name of the instance
   *
   * Override this for mixed case class names and support for PHP <5
   */
  private function _getClassName()
  {
    return get_class($this);
  }

  /**
   *  Serialization handler
   * @return array of publiciable names to serialize
   */
  private function __sleep()
  {
    // do not serialize debug info to keep the result small
    return array('version',
         'address',
         'queryport',
         'gamename',
         'hostport',
         'online',
         'gameversion',
         'servertitle',
         'mapname',
         'maptitle',
         'gametype',
         'numplayers',
         'maxplayers',
         'password',
         'nextmap',
         'players',
         'playerkeys',
         'playerteams',
         'maplist',
         'rules',
         'errstr'
         );
  }
}
