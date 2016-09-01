<?php
class phaseOneNightRevolutionNoGame {
  var $r;
  var $desc;

  var $minPlayers;
  var $maxPlayers;
  var $loaded;
  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Waiting for Players';
    $this->minPlayers = 3;
    $this->maxPlayers = 10;
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
    $this->r->players[$from] = new oneNightRevolutionPlayer($this->r, $from);
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
    if(!(isset($args[0]))) {
      $default = array(
        3 => 'Rea,I,T',
        4 => 'Rea,I,T,S',
        5 => 'Rea,I,T,S,O',
        6 => 'Rea,I,I,T,S,O',
        7 => 'Rea,I,I,T,S,S,O',
        8 => 'Rea,I,I,T,S,S,O,O',
        9 => 'Rea,I,I,T,T,S,S,O,O',
        10 => 'Rea,Rea,I,I,T,T,S,S,O,O'
      );
      $specs = $default[$playerCount];
    } else $specs = $args[0];
    $specsa = explode(',', $specs);
    $checkNumber = count($specsa);
    if($checkNumber != $playerCount) {
      $this->r->mChan("$nick: $specs is not a valid specialist list. ".$this->r->plural($checkNumber, 'specialist')." ".$this->r->pluralWord($checkNumber, 'was', 'were')." found, but $playerCount are required.");
      return;
    }
    $this->r->table = array();
    foreach($specsa as $spec) {
      $tSpec = $this->r->findSpec($spec);
      if($tSpec == null) {
        $this->r->mChan("$nick: $spec is not a valid specialist list. In particular, $spec is not found.");
        return;
      }
      $this->r->table[] = $tSpec;
    }
    $this->r->setPhase('setup');
  }
}
?>
