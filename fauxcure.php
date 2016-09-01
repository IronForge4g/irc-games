<?php
require_once('fauxcure/player.php');

require_once('fauxcure/phase.nogame.php');
require_once('fauxcure/phase.setup.php');
require_once('fauxcure/phase.round1.php');
require_once('fauxcure/phase.round2.php');
require_once('fauxcure/phase.round3.php');
require_once('fauxcure/phase.end.php');

class fauxcure implements pluginInterface {
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

  function init($config, $socket) {
    $this->config = $config;
    $this->socket = $socket;
    $this->channel = '#PlayFauxCure';
    $this->game = 'Faux Cure';
    $this->started = false;

    $this->phases = array();
    $this->phases['nogame'] = new phaseFauxCureNoGame($this);
    $this->phases['setup'] = new phaseFauxCureSetup($this);
    $this->phases['round1'] = new phaseFauxCureRound1($this);
    $this->phases['round2'] = new phaseFauxCureRound2($this);
    $this->phases['round3'] = new phaseFauxCureRound3($this);
    $this->phases['end'] = new phaseFauxCureEnd($this);

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
    sendNotice($this->socket, $nick, $message);
  }
  function playerList($vals = '') {
    if($vals == '') {
    $players = array_keys($this->players);
    return implode(", ", $players);
    }
    if($vals == 'score') {
      $display = array();
      foreach($this->players as $nick => $player) {
        $gifts = count($player->gift);
        if($gifts == 1) $gifts = "1 card";
        else $gifts = "$gifts cards";
        $display[] = "$nick ({$player->poison} poison, $gifts)";
      }
      return implode(', ', $display);
    }
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
  function validTarget($nick) {
    if(isset($this->players[$nick])) return true;
    return false;
  }
  function findPlayer($nick) {
    if(!(isset($this->playerMap[strtolower($nick)]))) return null;
    return $this->playerMap[strtolower($nick)];
  }
  function cmdrules($from, $args) {
    $this->nUser($from, "The rules for Faux Cure can be found online at http://pastebin.com/QaSMNRwe");
  }
}
?>
