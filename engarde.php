<?php
require_once('engarde/player.php');

require_once('engarde/phase.nogame.php');
require_once('engarde/phase.setup.php');
require_once('engarde/phase.offence.php');
require_once('engarde/phase.defence.php');
require_once('engarde/phase.newRound.php');
require_once('engarde/phase.endRound.php');

require_once('generic/deck.base.php');
require_once('engarde/deck.engarde.php');

class engarde implements pluginInterface {
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
  var $startPlayer;

  var $deck;
  var $discarded;
  var $score;

  function init($config, $socket) {
    $this->config = $config;
    $this->socket = $socket;
    $this->channel = '#PlayEnGarde';
    $this->game = 'En Garde';
    $this->started = false;

    $this->phases = array();
    $this->phases['nogame'] = new phaseEnGardeNoGame($this);
    $this->phases['setup'] = new phaseEnGardeSetup($this);
    $this->phases['offence'] = new phaseEnGardeOffence($this);
    $this->phases['defence'] = new phaseEnGardeDefence($this);
    $this->phases['newRound'] = new phaseEnGardeNewRound($this);
    $this->phases['endRound'] = new phaseEnGardeEndRound($this);

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
    if(!(isset($tmp[1]))) return;
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
      $this->mChan($from.", please wait your turn to $cmd.");
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
    $this->nUser($from, "!rules - Show's the rules for En Garde.");
    $this->nUser($from, "!start [points] - Start a new game of En Garde, first to [points]. If no points is specified, the game will play to 5.");
    $this->nUser($from, "!join - Join a game.");
    $this->nUser($from, "!part - Part a game.");
    $this->nUser($from, "!a <card> - Advance forward <card> spaces. Eg. '!a 4' will move your fencer 4 spaces toward your opponent.");
    $this->nUser($from, "!r <card> - Retreat <card> spaces. Eg. '!r 3' will move your fencer 3 spaces away from your opponent.");
    $this->nUser($from, "!da <card> [<card> <card> ...] - Direct attack your opponent. Only one card is required. All cards must be of the same value, equal to the distance from your opponent. Eg '!da 2 2 2' will attack your opponent 2 spaces away with 3 cards.");
    $this->nUser($from, "!aa <card> <card> [<card> <card> ...] - Advance and attack your opponent. The first card will move forward, then all following cards will attack from that distance. All attack cards must be of the same value, equal to the distance from your opponent after the movement. Eg '!da 1 5 5' will move forward one space, then attack your opponent 5 spaces away with 2 cards.");
    $this->nUser($from, "!p - Parry an advance and attack. A direct attack will be parried automatically if possible.");
  }
  function cmdrules($from, $args) {
    $this->nUser($from, "En Garde is an IRC implementation of the game En Garde!");
    $this->nUser($from, "The rules can be found online at https://boardgamegeek.com/filepage/73023/en-garde-advanced-rules-reference");
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
