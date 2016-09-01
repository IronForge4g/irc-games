<?php
require_once('thegame/player.php');

require_once('thegame/phase.nogame.php');
require_once('thegame/phase.setup.php');
require_once('thegame/phase.game.php');
require_once('thegame/phase.end.php');

require_once('generic/deck.base.php');
require_once('thegame/deck.game.php');

class thegame implements pluginInterface {
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
  var $solo;

  var $gameDeck;
  var $piles;

  function init($config, $socket) {
    $this->config = $config;
    $this->socket = $socket;
    $this->channel = '#PlayTheGame';
    $this->game = 'The Game';
    $this->started = false;

    $this->phases = array();
    $this->phases['nogame'] = new phaseTheGameNoGame($this);
    $this->phases['setup'] = new phaseTheGameSetup($this);
    $this->phases['game'] = new phaseTheGameGame($this);
    $this->phases['end'] = new phaseTheGameEnd($this);

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
    $this->nUser($from, "!rules - Show's the rules for The Game.");
    $this->nUser($from, "!start - Start a new game of The Game.");
    $this->nUser($from, "!join - Join a game.");
    $this->nUser($from, "!part - Part a game.");
    $this->nUser($from, "!(p)lay <pile> <card> - Play a card to a pile.");
    $this->nUser($from, "!(e)nd - End your turn.");
  }
  function cmdrules($from, $args) {
    $this->nUser($from, "The Game is an IRC implementation of the game The Game! It's a co-op game, so stop playing badly mikelietz.");
    $this->nUser($from, "The Game starts with 4 piles, two with the 1 card, and two with the 100 card.");
    $this->nUser($from, "On your turn, you will play a minimum of two cards from your hand onto the piles. Both cards can be played to the same pile, or separate piles.");
    $this->nUser($from, "The piles starting with 1, must be played in ascending order (so each successive card added must be higher.) The piles starting with 100 are played in descending order.");
    $this->nUser($from, "In the event you can play a card exactly 10 lower than a card on the ascending pile, you can play it to decrease the current card. The opposite is true for the descending piles, letting you play a card valued 10 higher.");
    $this->nUser($from, "The game ends when all cards have been played, or if the current player can't play the minimum number of cards.");
    $this->nUser($from, "When the draw deck is empty, the minimum cards per turn becomes 1.");
    $this->nUser($from, "If none of that made any sense, please ask someone in #boardgames for a learning game.");
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
