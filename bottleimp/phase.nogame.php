<?php
class phaseBottleImpNoGame {
  var $r;
  var $desc;

  var $minPlayers;
  var $maxPlayers;
  var $loaded;
  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Waiting for Players';
    $this->minPlayers = 3;
    $this->maxPlayers = 4;
    $this->loaded = false;
  }
  function init() {
    $this->r->started = false;
    $this->r->players = array();
    $this->r->playerMap = array();
    $this->r->currentPlayer = null;

    if(!($this->loaded)) $this->loaded = true;
    else $this->r->mChan("A new game can now begin.");
  }
  function cmdjoin($from, $args) {
    if(isset($this->r->players[$from])) {
      $this->r->mChan("$from: You have already joined the current game.");
      return;
    }
    $playerCount = count($this->r->players);
    if($playerCount >= $this->maxPlayers) {
      $this->r->mChan("$from: Sorry, maximum number of players ({$this->maxPlayers}) has been reached.");
      return;
    }
    $this->r->players[$from] = new bottleImpPlayer($this->r, $from);
    $this->r->playerMap[strtolower($from)] = $this->r->players[$from];
    $this->r->mChan("$from: Thank you for joining. Current players are now: ".$this->r->playerList().".");
  }
  function cmdpart($from, $args) {
    if(!(isset($this->r->players[$from]))) {
      $this->r->mChan("$from: You are not in the current game.");
      return;
    }
    unset($this->r->players[$from]);
    $playerCount = count($this->r->players);
    if($playerCount > 0) {
      $this->r->mChan("$from: Sorry you have changed your mind. Current players are now: ".$this->r->playerList().".");
    } else {
      $this->r->mChan("$from: Sorry you have changed your mind.");
    }
  }
  function cmdstart($from, $args) {
    if(!(isset($this->r->players[$from]))) {
      $this->r->mChan("$from: You must be in the current game to start it.");
      return;
    }
    $playerCount = count($this->r->players);
    if($playerCount < $this->minPlayers) {
      $this->r->mChan("$from: Sorry, only $playerCount player(s) have joined. {$this->r->game} requires {$this->minPlayers}-{$this->maxPlayers} players to start a game. Current players are: ".$this->r->playerList().".");
      return;
    }
    if(!($this->r->checkArgs($from, $args, 1))) return;
    $number = $args[0];
    $checkNumber = preg_replace("#[^0-9]+#", "", $number);
    if($checkNumber != $number || $number > 1000) {
      $this->r->mChan("$nick: $number is not a valid ending score. Please choose a value less than 1001.");
      return;
    }
    $this->r->endScore = $number;
    $this->r->setPhase('setup');
  }
}
?>
