<?php
require_once('deadmanschest/player.php');

require_once('deadmanschest/phase.nogame.php');
require_once('deadmanschest/phase.setup.php');
require_once('deadmanschest/phase.game.php');
require_once('deadmanschest/phase.end.php');

class deadmanschest implements pluginInterface {
  var $config;
  var $socket;
  var $channel;
  var $game;
  var $started;

  var $players;
  var $playerMap;
  var $phases;
  var $phase; 
  var $currentPlayer;

  var $bids;

  function init($config, $socket) {
    $this->config = $config;
    $this->socket = $socket;
    $this->channel = '#PlayDeadMansChest';
    $this->game = 'Dead Mans Chest';
    $this->started = false;

    $this->phases = array();
    $this->phases['nogame'] = new phaseDeadMansChestNoGame($this);
    $this->phases['setup'] = new phaseDeadMansChestSetup($this);
    $this->phases['game'] = new phaseDeadMansChestGame($this);
    $this->phases['end'] = new phaseDeadMansChestEnd($this);

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
    $player = $this->findPlayer($nick);
    if($player == null) sendNotice($this->socket, $nick, $message);
    else {
      if($player->msgType == 'msg') sendMessage($this->socket, $nick, $message);
      else sendNotice($this->socket, $nick, $message);
    }
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
      $this->mChan($from, "Please wait your turn to $cmd.");
      return false;
    }
    return true;
  }
  function checkArgs($from, $args, $min, $max = -1) {
    if($max == -1) $max = $min;
    $argc = count($args);
    if($argc < $min || $argc > $max) {
      if($min == $max) $this->mChan("That command only takes $min argument(s). Please try again.");
      else $this->mChan("$from: That command requires $min-$max arguments. Please try again.");
      return false;
    }
    return true;
  }
  function findPlayer($nick) {
    if(!(isset($this->playerMap[strtolower($nick)]))) return null;
    return $this->playerMap[strtolower($nick)];
  }
  function cmdhelp($from, $args) {
    $this->nUser($from, "!rules - Show's the rules for Dead Mans Chest.");
    $this->nUser($from, "!start - Start a new game of Dead Mans Chest.");
    $this->nUser($from, "!join - Join a game.");
    $this->nUser($from, "!part - Part a game.");
    $this->nUser($from, "!(b)id <bid> - Raise the bid.");
    $this->nUser($from, "!(c)all - Call another players bluff.");
    $this->nUser($from, "!(s)hake - Shake the Dead Mans Chest.");
  }
  function cmdrules($from, $args) {
    $this->nUser($from, "Dead Mans Chest is an IRC implementation of the game Dead Mans Chest!");
    $this->nUser($from, "See the rules at http://www.eggrules.com/games/games-d-l/dead-man-s-chest/");
  }
  function cmdnotice($from, $args) {
    $player = $this->findPlayer($from);
    if($player == null) return;
    $player->msgType = 'notice';
    $this->nUser("Messages will now be sent to you as a notice.");
  }
  function cmdmsg($from, $args) {
    $player = $this->findPlayer($from);
    if($player == null) return;
    $player->msgType = 'msg';
    $this->nUser("Messages will now be sent to you as private message.");
  }
  function plural($v, $word) {
    if($v == 0) return "no {$word}s";
    else if($v == 1) return "1 {$word}";
    else return "$v {$word}s";
  }
  function pluralWord($v, $singular, $plural) {
    if($v == 1) return $singular;
    return $plural;
  }
}
?>
