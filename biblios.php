<?php
require_once('biblios/player.php');

require_once('biblios/phase.nogame.php');
require_once('biblios/phase.setup.php');
require_once('biblios/phase.gift.php');
require_once('biblios/phase.draft.php');
require_once('biblios/phase.auction.php');
require_once('biblios/phase.church.php');
require_once('biblios/phase.pay.php');
require_once('biblios/phase.end.php');

require_once('generic/deck.base.php');
require_once('biblios/deck.biblios.php');

class biblios implements pluginInterface {
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

  var $deck;
  var $table;
  var $auction;
  var $activePlayer;
  var $startPlayer;
  var $dice;

  function init($config, $socket) {
    $this->config = $config;
    $this->socket = $socket;
    $this->channel = '#PlayBiblios';
    $this->game = 'Biblios';
    $this->started = false;

    $this->phases = array();
    $this->phases['nogame'] = new phaseBibliosNoGame($this);
    $this->phases['setup'] = new phaseBibliosSetup($this);
    $this->phases['gift'] = new phaseBibliosGift($this);
    $this->phases['draft'] = new phaseBibliosDraft($this);
    $this->phases['auction'] = new phaseBibliosAuction($this);
    $this->phases['church'] = new phaseBibliosChurch($this);
    $this->phases['pay'] = new phaseBibliosPay($this);
    $this->phases['end'] = new phaseBibliosEnd($this);

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
  function findColor($color) {
    $colors = array('r' => 'Red', 'o' => 'Orange', 'g' => 'Green', 'b' => 'Blue', 'p' => 'Purple');
    if($color == '') return null;
    $c = strtolower($color{0});
    if(isset($colors[$c])) return $colors[$c];
    return null;
  }
  function points($c) {
    if($c == 0) return "no points";
    else if($c == 1) return "1 point";
    else return "$c points";
  }
  function cmdhelp($from, $args) {
    $this->nUser($from, "!rules - Shows the rules for Biblios.");
    $this->nUser($from, "!start - Start a new game of Biblios.");
    $this->nUser($from, "!join - Join a game.");
    $this->nUser($from, "!part - Part a game.");
    $this->nUser($from, "!notice - Bot will send notices for your hand. (Default)");
    $this->nUser($from, "!msg - Bot will send messages for your hand. (Must be done every game after !join)");
    $this->nUser($from, "!me - Will show your current hand."); 
    $this->nUser($from, "!board - Will show the deck and dice status."); 
  }
  function cmdrules($from, $args) {
    $this->nUser($from, "Biblios is an IRC implementation of the game Biblios!");
    $this->nUser($from, "The rules can be found online at http://www.doctorfinns.com/Rules/BibliosRules.pdf");
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
  function cmdme($from, $args) {
    if(!($this->started)) return;
    $player = $this->findPlayer($from);
    if($player != null) $player->displayHand();
  }
  function cmdboard($from, $args) {
    if(!($this->started)) return;
    $colors = array('Red', 'Orange', 'Green', 'Blue', 'Purple');
    $diceDisplay = array();
    foreach($colors as $color) {
      $diceDisplay[] = "$color: ".$this->dice[$color];
    }
    $this->mChan("Draw Deck: ".$this->deck->count().", Auction Deck: ".$this->auction->count().".");
    $this->mChan("The dice are: ".implode(', ', $diceDisplay).".");
  }
}
?>
