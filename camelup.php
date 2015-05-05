<?php
require_once('camelup/player.php');
require_once('camelup/camel.php');

require_once('camelup/phase.nogame.php');
require_once('camelup/phase.setup.php');
require_once('camelup/phase.startleg.php');
require_once('camelup/phase.leg.php');
require_once('camelup/phase.endleg.php');
require_once('camelup/phase.end.php');

class camelup implements pluginInterface {
  var $config;
  var $socket;
  var $channel;
  var $game;
  var $started;
  var $gameEnd;

  var $players;
  var $playerMap;
  var $phases;
  var $phase; 
  var $currentPlayer;

  var $legBets;
  var $camels;
  var $rolledDice;
  var $dice;

  var $winDeck;
  var $loseDeck;

  function init($config, $socket) {
    $this->config = $config;
    $this->socket = $socket;
    $this->channel = '#PlayCamelUp';
    $this->game = 'Camel Up';
    $this->started = false;

    $this->phases = array();
    $this->phases['nogame'] = new phaseCamelUpNoGame($this);
    $this->phases['setup'] = new phaseCamelUpSetup($this);
    $this->phases['startleg'] = new phaseCamelUpStartLeg($this);
    $this->phases['leg'] = new phaseCamelUpLeg($this);
    $this->phases['endleg'] = new phaseCamelUpEndLeg($this);
    $this->phases['end'] = new phaseCamelUpEnd($this);

    $this->setPhase('nogame');
  }

  function tick() {

  }

  function onMessage($from, $channel, $msg) {
    if($channel != $this->channel) return;
    if($msg{0} != '!') return;
    $args = explode(" ", $msg);
    $cmdRaw = array_shift($args);
    $cmd = 'cmd'.strtolower(substr($cmdRaw, 1));
    if(trim($cmd) == 'cmd') return;
    if(method_exists($this, $cmd)) {
      $this->$cmd($from, $args);
    } else if(method_exists($this->phase, $cmd)) {
      $this->phase->$cmd($from, $args);
    } else {
      $this->mChan("$from: $cmdRaw does not exist in the phase '{$this->phase->desc}'.");
    }

  }
  function onNick($from, $to) {
    if(isset($this->players[$from])) {
      $this->players[$to] = $this->players[$from];
      $this->players[$to]->nick = $to;
      unset($this->players[$from]);
    }
  }
  function onQuit($from) {

  }

  function destroy() {

  }
  function onData($data) {
    $tmp = explode(" ", trim($data));
    $from = getNick($tmp[0]);
    if(!(isset($tmp[1]))) continue;
    if($tmp[1] == 'NICK') $this->onNick($from, str_replace(":", "", $tmp[2]));
    else if($tmp[1] == 'PART' && trim(strtolower($this->channel)) == trim(strtolower($tmp[2]))) $this->onQuit($from);
    else if($tmp[1] == 'QUIT') $this->onQuit($from);
  }
  function mChan($message) {
    sendMessage($this->socket, $this->channel, $message);
  }
  function nUser($nick, $message) {
    sendNotice($this->socket, $nick, $message);
  }
  function playerList() {
    $players = array_keys($this->players);
    return implode(", ", $players);
  }
  function setPhase($phase) {
    $this->phase = $this->phases[$phase];
    $this->phase->init();
  }
  function checkCurrentPlayer($from, $cmd) {
    if($this->currentPlayer->nick != $from) {
      $this->mChan("$from: Please wait your turn to $cmd.");
      return false;
    }
    return true;
  }
  function checkArgs($from, $args, $min, $max = -1) {
    if($max == -1) $max = $min;
    $argc = count($args);
    if($argc < $min || $argc > $max) {
      if($min == $max) $this->mChan("$from: That command only takes $min argument(s). Please try again.");
      else $this->mChan("$from: That command requires $min-$max arguments. Please try again.");
      return false;
    }
    return true;
  }
  function findPlayer($nick) {
    if(!(isset($this->playerMap[strtolower($nick)]))) return null;
    return $this->playerMap[strtolower($nick)];
  }
  function cmdrules($from, $args) {
    $this->nUser($from, "The rules for Camel Up can be found online at http://www.boardgamegeek.com/filepage/102534/camel-english-rules");
  }
  function topCamel($pos) {
    foreach($this->camels as $color => $camel) {
      if($camel->position == $pos && $camel->placed) {
        $tCamel = $camel;
        while($tCamel->above != null) {
          $tCamel = $tCamel->above;
        }
        return $tCamel;
      }
    }
    return null;
  }
  function findColor($color) {
    $colors = array('r' => 'Red', 'y' => 'Yellow', 'g' => 'Green', 'b' => 'Blue', 'p' => 'Pink');
    if($color == '') return null;
    $c = strtolower($color{0});
    if(isset($colors[$c])) return $colors[$c];
    return null;
  }
  function colorText($text, $color) {
    if($color == 'White') return chr(3).'00,01'.$text.chr(15);
    else if($color == 'Black') return chr(3).'01,01'.$text.chr(15);
    else if($color == 'Red') return chr(3).'04,01'.$text.chr(15);
    else if($color == 'Yellow') return chr(3).'08,01'.$text.chr(15);
    else if($color == 'Green') return chr(3).'09,01'.$text.chr(15);
    else if($color == 'Blue') return chr(3).'11,01'.$text.chr(15);
    else if($color == 'Pink') return chr(3).'13,01'.$text.chr(15);
    else if($color == 'Mirage') return chr(3).'07,01'.$text.chr(15);
    else if($color == 'Oasis') return chr(3).'12,01'.$text.chr(15);
    return $text;
  }
  function diceDisplay() {
    $diceDisplay = array($this->colorText('Dice Rolled:', 'White'));
    foreach($this->rolledDice as $color => $die) {
      $diceDisplay[] = $this->colorText($die, $color);
    }
    $this->mChan(implode($this->colorText(' ', 'Black'), $diceDisplay));
  }
  function betsDisplay() {
    $betsDisplay = array($this->colorText('Leg Bets:', 'White'));
    foreach($this->legBets as $color => $bets) {
      if(count($bets) == 0) $betsDisplay[] = $this->colorText('X', $color);
      else $betsDisplay[] = $this->colorText($bets[0], $color);
    }
    $this->mChan(implode($this->colorText(' ', 'Black'), $betsDisplay));
  }
  function cmdboard($from, $args) {
    if(!($this->started)) return;
    $this->board();
  }
  function board() {
    $board = array();
    $maxHeight = 0;
    $maxDistance = 16;
    $camels = array();
    foreach($this->camels as $color => $camel) {
      $height = $camel->height();
      $camels[$color] = array($camel->position, $height);
      if($height > $maxHeight) $maxHeight = $height;
      if($camel->position > $maxDistance) $maxDistance = $camel->position;
    }
    for($p=1;$p<=$maxDistance;$p++) {
      $board[$p] = array();
      $board[$p]['base'] = $this->colorText("$p. W ", 'Black');
      $board[$p][0] = $this->colorText("$p. ", 'White').$this->colorText('W ', 'Black');
      for($h=1;$h<=$maxHeight;$h++) {
        $board[$p][$h] = null;
      }
    }
    foreach($camels as $color => $posArray) {
      list($pos, $height) = $posArray;
      if($height == 0) $board[$pos][$height] = $this->colorText("$pos. ", 'White').$this->colorText('W ', $color);
      else $board[$pos][$height] = $this->colorText("$pos. ", 'Black').$this->colorText('W ', $color);
    }
    foreach($this->players as $nick => $player) {
      if($player->desertTile != null) {
        $letter = $player->desertTile->type{0};
        $board[$player->desertTile->position][0] = $this->colorText($player->desertTile->position.". ", 'White').$this->colorText($letter.' ', $player->desertTile->type);
        $board[$player->desertTile->position]['base'] = $this->colorText($player->desertTile->position.". $letter ", 'Black');
      }
    }
    $this->diceDisplay();
    $this->betsDisplay();
    for($h=$maxHeight;$h>=0;$h--) {
      $line = '';
      for($p=1;$p<=$maxDistance;$p++) {
        if($board[$p][$h] == null) $line .= $board[$p]['base'];
        else $line .= $board[$p][$h];
      }
      $this->mChan($line);
    }
  }
  function cmdplayers($from, $args) {
    if(!($this->started)) return;
    $this->players();
  }
  function players() {
    foreach($this->players as $nick => $player) {
      $line = $nick.' has $'.$player->money.'.';
      if($player->desertTile != null) $line .= " They have their {$player->desertTile->type} on {$player->desertTile->position}.";
      $bets = array();
      foreach($player->bets as $bet) {
        list($color, $pts) = $bet;
        $bets[] = $this->colorText($pts, $color);
      }
      if(count($bets) > 0) {
        $line .= ' They have placed bets on: '.implode($this->colorText(', ', 'White'), $bets).'.';
      }
      $this->mChan($line);
    }
  }
  function camelOrder() {
    $order = array();
    for($i=1;$i<=20;$i++) {
      foreach($this->camels as $color => $camel) {
        if($camel->position == $i) {
          $tCamel = $camel;
          while($tCamel->below != null) {
            $tCamel = $tCamel->below;
          }
          while($tCamel->above != null) {
            $order[] = $tCamel;
            $tCamel = $tCamel->above;
          }
          $order[] = $tCamel;
          break;
        }
      }
    }
    return $order;
  }
}
?>
