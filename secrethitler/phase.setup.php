<?php
class phaseCodenamesSetup {
  var $r;
  var $desc;

  var $informants;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Setting up Game';
  }
  function init() {
    $this->setupBase();
    $this->setupPlayers();
    $this->r->mChan("The game is now starting.");
    $this->r->setPhase('president');
  }
  function setupBase() {
    $this->r->started = true;
    $this->r->electionTrack = 0;
    $this->r->liberalPolicies = 0;
    $this->r->liberalTrack = array('(No Policy)', '(No Policy)', '(No Policy)', '(No Policy)', '(Victory)');
    $this->r->facistPolicies = 0;
    $this->r->playerCount = $playerCount = count($this->r->players);
    if($playerCount == 5 || $playerCount == 6) $this->r->facistTrack = array('(No Policy)', '(No Policy)', '(Top 3 Cards)', '(Execute)', '(Execute & Veto)', '(Victory)');
    else if($playerCount == 7 || $playerCount == 8) $this->r->facistTrack = array('(No Policy)', '(Investigate)', '(Special Election)', '(Execute)', '(Execute & Veto)', '(Victory)');
    else if($playerCount == 9 || $playerCount == 10) $this->r->facistTrack = array('(Investigate)', '(Investigate)', '(Special Election)', '(Execute)', '(Execute & Veto)', '(Victory)');
    $this->r->deck = new secretHitlerDeck($this->r);
  }
  function setupPlayers() {
    $first = null;
    $last = null;
    $nicks = array_keys($this->r->players);
    shuffle($nicks);
    $new = array();
    $playerCount = $this->r->playerCount;
    if($playerCount == 5) $roles = array('Liberal', 'Liberal', 'Liberal', 'Facist', 'Hitler');
    else if($playerCount == 6) $roles = array('Liberal', 'Liberal', 'Liberal', 'Liberal', 'Facist', 'Hitler');
    else if($playerCount == 7) $roles = array('Liberal', 'Liberal', 'Liberal', 'Liberal', 'Facist', 'Facist', 'Hitler');
    else if($playerCount == 8) $roles = array('Liberal', 'Liberal', 'Liberal', 'Liberal', 'Liberal', 'Facist', 'Facist', 'Hitler');
    else if($playerCount == 9) $roles = array('Liberal', 'Liberal', 'Liberal', 'Liberal', 'Liberal', 'Facist', 'Facist', 'Facist', 'Hitler');
    else if($playerCount == 10) $roles = array('Liberal', 'Liberal', 'Liberal', 'Liberal', 'Liberal', 'Liberal', 'Facist', 'Facist', 'Facist', 'Hitler');
    shuffle($roles);
    foreach($nicks as $nick) $new[$nick] = $this->r->players[$nick];
    $this->r->players = $new;
    $masters = 0;
    $color = 'green';
    $hitler = '';
    $facists = array();
    foreach($this->r->players as $nick => $player) {
      $player->hitler = false;
      $role = array_shift($roles);
      if($role == 'Hitler') {
        $player->hitler = true;
        $role = 'Facist';
        $hitler = $nick;
        if($playerCount < 7) $facists[] = $nick;
      } else if($role == 'Facist') $facists[] = $nick;
      $this->r->nUser($nick, "You are on the $role side.".($player->hitler ? ' ALSO, YOU ARE HITLER!', ''));
      $player->team = $role;
      if($last == null) {
        $first = $player;
        $last = $player;
        continue;
      }
      $player->right = $last;
      $last->left = $player;
      $last = $player;
    }
    $first->right = $last;
    $last->left = $first;
    $this->r->currentPlayer = $first;
    $this->r->lastPresident = null;
    $this->r->lastChancellor = null;
    $this->r->president = $player;
    $this->r->chancellor = null;
    $mates = implode(', ', $facists);
    foreach($facists as $nick) $this->r->nUser($nick, "The facists are: $mates. $hitler IS HITLER!");
}
?>
