<?php
require_once('bottleimp/player.php');

require_once('bottleimp/phase.nogame.php');
require_once('bottleimp/phase.setup.php');
require_once('bottleimp/phase.newRound.php');
require_once('bottleimp/phase.pass.php');
require_once('bottleimp/phase.game.php');
require_once('bottleimp/phase.end.php');

require_once('generic/deck.base.php');
require_once('bottleimp/deck.imp.php');

class bottleimp implements pluginInterface {
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
  var $dealer;

  var $deck;
  var $bottle;
  var $cursed;
  var $endScore;
  var $impHand;

  function init($config, $socket) {
    $this->config = $config;
    $this->socket = $socket;
    $this->channel = '#PlayBottleImp';
    $this->game = 'Bottle Imp';
    $this->started = false;

    $this->phases = array();
    $this->phases['nogame'] = new phaseBottleImpNoGame($this);
    $this->phases['setup'] = new phaseBottleImpSetup($this);
    $this->phases['newRound'] = new phaseBottleImpNewRound($this);
    $this->phases['pass'] = new phaseBottleImpPass($this);
    $this->phases['game'] = new phaseBottleImpGame($this);
    $this->phases['end'] = new phaseBottleImpEnd($this);

    $this->setPhase('nogame');
  }

  function tick() {

  }

  function onMessage($from, $channel, $msg) {
    if(strtolower($channel) != strtolower($this->channel)) return;
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
  function points($c) {
    if($c == 0) return "no points";
    else if($c == 1) return "1 point";
    else return "$c points";
  }
  function cmdhelp($from, $args) {
    $this->nUser($from, "!rules - Show's the rules for Bottle Imp.");
    $this->nUser($from, "!start <points> - Start a new game of Bottle Imp, with a target score of <points>.");
    $this->nUser($from, "!join - Join a game.");
    $this->nUser($from, "!part - Part a game.");
    $this->nUser($from, "!notice - Bot will send notices for your hand.");
    $this->nUser($from, "!msg - Bot will send messages for your hand.");
    $this->nUser($from, "!score - Display score.");
    $this->nUser($from, "!track - Display track.");
    $this->nUser($from, "!play <card> | !p <card> - Play a card.");
    $this->nUser($from, "!pass <player> <card> - Pass a card to another player (to your left or right).");
    $this->nUser($from, "!imp <player> <card> | !i <card> - Send a card to the imps hand.");
  }
  function cmdrules($from, $args) {
    $this->nUser($from, "Bottle Imp is an IRC implementation of the game The Bottle Imp!");
    $this->nUser($from, "The rules can be found online at https://boardgamegeek.com/filepage/45492/english-rules-flaschenteufel-bambus-spieleverlag");
    $this->nUser($from, "Or ask someone in #boardgames for a learning game!");
  }
  function cmdscores($from, $args) {
    if(!($this->started)) return;
    $this->score();
  }
  function cmdscore($from, $args) {
    if(!($this->started)) return;
    $this->score();
  }
  function cmdboard($from, $args) {
    if(!($this->started)) return;
    $this->score();
  }
  function cmdbottle($from, $args) {
    if(!($this->started)) return;
    if($this->cursed == null) $this->mChan("The bottle is selling for {$this->bottle->display} and is held by no one.");
    else $this->mChan("The bottle is selling for {$this->bottle->display} and is held by {$this->cursed->nick}.");
  }
  function score($board = true) {
    $scores = array();
    if($board) {
      if($this->cursed == null) $this->mChan("The bottle is selling for {$this->bottle->display} and is held by no one.");
      else $this->mChan("The bottle is selling for {$this->bottle->display} and is held by {$this->cursed->nick}.");
    }
    foreach($this->players as $nick => $player) {
      $scores[$nick] = $player->score;
    }
    arsort($scores);
    foreach($scores as $nick => $score) {
      $this->mChan("$nick has ".$score." / ".$this->endScore.".");
    }
  }
  function cmdtrack($from, $args) {
    $cards = array(
      1 => 'Y',
      2 => 'Y',
      3 => 'Y',
      4 => 'B',
      5 => 'Y',
      6 => 'B',
      7 => 'Y',
      8 => 'B',
      9 => 'Y',
      10 => 'B',
      11 => 'O',
      12 => 'Y',
      13 => 'B',
      14 => 'O',
      15 => 'Y',
      16 => 'O',
      17 => 'B',
      18 => 'Y',
      19 => 'W',
      20 => 'B',
      21 => 'O',
      22 => 'Y',
      23 => 'O',
      24 => 'B',
      25 => 'Y',
      26 => 'O',
      27 => 'B',
      28 => 'Y',
      29 => 'O',
      30 => 'B',
      31 => 'O',
      32 => 'B',
      33 => 'O',
      34 => 'B',
      35 => 'O',
      36 => 'O',
      37 => 'O'
    );
    $display = array();
    foreach($cards as $val => $col) {
      $colNum = '00';
      if($col == 'O') $colNum = '07';
      else if($col == 'B') $colNum = '11';
      else if($col == 'Y') $colNum = '08';
      $display[] = chr(3).$colNum.$val.$col;
    }
    $this->mChan(chr(3).'00,01Track: '.implode(chr(3).'00,', $display));
  }
  function cmdnotice($from, $args) {
    $player = $this->findPlayer($from);
    if($player == null) return;
    $player->msgType = 'notice';
    $this->mChan("Messages will now be sent to you as a notice.");
  }
  function cmdmsg($from, $args) {
    $player = $this->findPlayer($from);
    if($player == null) return;
    $player->msgType = 'msg';
    $this->mChan("Messages will now be sent to you as private message.");
  }
}
?>
