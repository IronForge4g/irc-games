<?php
require_once('cloud9\player.php');

require_once('cloud9\phase.nogame.php');
require_once('cloud9\phase.setup.php');
require_once('cloud9\phase.startcloud.php');
require_once('cloud9\phase.cloud.php');
require_once('cloud9\phase.endcloud.php');
require_once('cloud9\phase.endclimb.php');
require_once('cloud9\phase.solo.php');

require_once('generic\deck.base.php');
require_once('cloud9\deck.color.php');

class cloud9 implements pluginInterface {
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
  var $votingPlayer;

  var $deck;
  var $cloudDice;
  var $cloudPoints;
  var $currentCloud;
  var $requiredSkills;

  function init($config, $socket) {
    $this->config = $config;
    $this->socket = $socket;
    $this->channel = '#PlayCloud9';
    $this->game = 'Cloud 9';
    $this->started = false;

    $this->phases = array();
    $this->phases['nogame'] = new phaseCloud9NoGame($this);
    $this->phases['setup'] = new phaseCloud9Setup($this);
    $this->phases['startcloud'] = new phaseCloud9StartCloud($this);
    $this->phases['cloud'] = new phaseCloud9Cloud($this);
    $this->phases['endcloud'] = new phaseCloud9EndCloud($this);
    $this->phases['endclimb'] = new phaseCloud9EndClimb($this);
    $this->phases['solo'] = new phaseCloud9Solo($this);

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
  function checkVotingPlayer($from, $cmd) {
    if($this->votingPlayer->nick != $from) {
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
  function cmdscore($from, $args) {
    if(!($this->started)) return;
    $this->score();
  }
  function score() {
    $scores = array();
    foreach($this->players as $nick => $player) {
      $scores[$nick] = $player->points;
    }
    arsort($scores);
    $display = array();
    foreach($scores as $nick => $points) {
      $display[] = "$nick has ".$this->points($points);
    }
    $this->mChan("Scores: ".implode(', ', $display).".");
  }
  function rollDice() {
    $number = $this->cloudDice[$this->currentCloud];
    $this->requiredSkills = array();
    $dice = array('Green', 'Purple', 'Red', 'Yellow', 'Blank', 'Blank');
    for($i=0;$i<$number;$i++) {
      $diceKey = array_rand($dice);
      $this->requiredSkills[] = $dice[$diceKey];
    }
    sort($this->requiredSkills);
  }
  function cmdhelp($from, $args) {
    $this->nUser($from, "!rules - Show's the rules for Cloud 9.");
    $this->nUser($from, "!start - Start a new game of Cloud 9.");
    $this->nUser($from, "!join - Join a game.");
    $this->nUser($from, "!part - Part a game.");
    $this->nUser($from, "!jump - Jump out of the balloon.");
    $this->nUser($from, "!stay - Stay in the balloon.");
    $this->nUser($from, "!pilot - Show if you can pilot the balloon safely or not.");
    $this->nUser($from, "!rainbow - Pilot with a rainbow card.");
  }
  function cmdrules($from, $args) {
    $this->nUser($from, "Cloud 9 is an IRC implementation of the game Cloud 9!");
    $this->nUser($from, "The rules can be found online at http://www.otb-games.com/wordpress/wp-content/uploads/2011/05/cloud9_rules.pdf");
  }
}
?>
