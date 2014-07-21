<?php
require_once('hattari\player.php');

require_once('hattari\phase.nogame.php');
require_once('hattari\phase.setup.php');
require_once('hattari\phase.first.php');
require_once('hattari\phase.swap.php');
require_once('hattari\phase.accuse.php');
require_once('hattari\phase.end.php');

class hattari implements pluginInterface {
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
  var $firstPlayer;
  var $currentPlayer;

  var $suspects;
  var $victim;
  var $tampered;
  var $unknown;

  var $accused;

  function init($config, $socket) {
    $this->config = $config;
    $this->socket = $socket;
    $this->channel = '#PlayHattari';
    $this->game = 'Hattari';
    $this->started = false;

    $this->phases = array();
    $this->phases['nogame'] = new phaseHattariNoGame($this);
    $this->phases['setup'] = new phaseHattariSetup($this);
    $this->phases['first'] = new phaseHattariFirst($this);
    $this->phases['swap'] = new phaseHattariSwap($this);
    $this->phases['accuse'] = new phaseHattariAccuse($this);
    $this->phases['end'] = new phaseHattariEnd($this);

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
  function cmdhelp($from, $args) {
    $this->nUser($from, "!rules - Show's the rules for Hattari.");
    $this->nUser($from, "!start - Start a new game of Hattari.");
    $this->nUser($from, "!join - Join a game.");
    $this->nUser($from, "!part - Part a game.");
    $this->nUser($from, "!interrogate | !i - Interrogate suspects.");
    $this->nUser($from, "!swap | !s - Swap a suspect with the victim.");
    $this->nUser($from, "!pass | !p - Skip swapping a suspect.");
    $this->nUser($from, "!accuse | !a - Accuse a suspect of being the murderer.");
  }
  function cmdrules($from, $args) {
    $this->nUser($from, "Hattari is an IRC implementation of the game Hattari!");
    $this->nUser($from, "The rules can be found online at http://boardgamegeek.com/filepage/91068/english-rules-oink-games");
    $this->nUser($from, "Or possibly a better version at https://www.dropbox.com/s/w3a9knii7ax2pxi/Hattari.pdf");
  }
  function cmdboard($from, $args) {
    $this->board();
  }
  function board($reveal = false) {
    $tamperedA = '';
    $tamperedB = '';
    $tamperedC = '';
    $unknownA = '';
    $unknownB = '';
    $unknownC = '';
    if($this->tampered == 'A') $tamperedA = ' (Tampered)';
    else if($this->tampered == 'B') $tamperedB = ' (Tampered)';
    else if($this->tampered == 'C') $tamperedC = ' (Tampered)';
    if($this->unknown == 'A') $unknownA = ' (Unknown)';
    else if($this->unknown == 'B') $unknownB = ' (Unknown)';
    else if($this->unknown == 'C') $unknownC = ' (Unknown)';
    $accusedA = 'Accused By: '.implode(', ', $this->accused['A']);
    $accusedB = 'Accused By: '.implode(', ', $this->accused['B']);
    $accusedC = 'Accused By: '.implode(', ', $this->accused['C']);
    if($accusedA == 'Accused By: ') $accusedA = '';
    if($accusedB == 'Accused By: ') $accusedB = '';
    if($accusedC == 'Accused By: ') $accusedC = '';
    $revealA = '';
    $revealB = '';
    $revealC = '';
    $punct = '?';
    if($reveal) {
      $revealA = " was ".$this->suspects['A'];
      $revealB = " was ".$this->suspects['B'];
      $revealC = " was ".$this->suspects['C'];
      $punct = '.';
    }
    $this->mChan("Suspect A{$revealA}{$tamperedA}{$unknownA}{$punct} {$accusedA}");
    $this->mChan("Suspect B{$revealB}{$tamperedB}{$unknownB}{$punct} {$accusedB}");
    $this->mChan("Suspect C{$revealC}{$tamperedC}{$unknownC}{$punct} {$accusedC}");
    if($reveal) $this->mChan("The victim was {$this->victim}.");
    $display = array();
    foreach($this->players as $nick => $player) {
      $display[] = "$nick has {$player->chips} tokens left (with {$player->failed} black marks)";
    }
    $this->mChan(implode(', ', $display));
  }
  function findSuspect($letter) {
    $letters = array('a' => 'A', 'b' => 'B', 'c' => 'C');
    if($letter == '') return null;
    $l = strtolower($letter{0});
    if(isset($letters[$l])) return $letters[$l];
    return null;
  }
}
?>
