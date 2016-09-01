<?php
class phaseCodenamesNoGame {
  var $r;
  var $desc;

  var $minPlayers;
  var $maxPlayers;
  var $loaded;
  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Waiting for Players';
    $this->minPlayers = 4;
    $this->maxPlayers = 12;
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
    $this->r->players[$from] = new codenamesPlayer($this->r, $from);
    $this->r->playerMap[strtolower($from)] = $this->r->players[$from];
    $this->r->mChan("$from: Thank you for joining. Current players are now: ".$this->r->playerList().".");
  }
  function cmdpart($from, $args) {
    if(!(isset($this->r->players[$from]))) {
      $this->r->mChan("$from: You are not in the current game.");
      return;
    }
    unset($this->r->players[$from]);
    unset($this->r->playerMap[strtolower($from)]);
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
    $this->r->setPhase('setup');
  }
  function cmdkick($from, $args) {
    $player = $this->r->findPlayer($from);
    if($player == null) return;
    $target = $this->r->findPlayer($args[0]);
    if($player == null) return;
    if($player == $target) return;
    if($target == null) return;
    unset($this->r->playerMap[strtolower($target->nick)]);
    unset($this->r->players[$target->nick]);
    $this->r->mChan("$from has kicked {$args[0]} from the game. Current players are now: ".$this->r->playerList().".");
  }
}
?>
