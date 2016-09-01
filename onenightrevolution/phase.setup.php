<?php
class phaseOneNightRevolutionSetup {
  var $r;
  var $desc;

  var $informants;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Setting up Game';
  }
  function init() {
    $this->informants = array();
    $this->informantNames = array();
    $this->setupBase();
    $this->setupPlayers();
    $this->r->mChan("The game is now starting. The player order is: ".$this->r->playerList().". This will loop back around, so the last player is also beside the first one.");
    $this->r->mChan("The specialists in this game are: ".implode(', ', $this->r->table).".");
    $informants = implode(', ', $this->informants);
    foreach($this->r->players as $nick => $player) {
      if ($player->team == 'Informant' && $player->specialist->name != 'Blind Informant') {
        $this->r->nUser($nick, "The Informants around the table are: {$informants}");
      }
    }
    $this->r->setPhase('night');
  }
  function setupBase() {
    $this->r->started = true;
    $playerCount = count($this->r->players);
    $this->r->specDeck = new specDeck($this->r, $this->r->table);
    $this->r->teamDeck = new teamDeck($this->r, $playerCount);
    $this->r->tableCards = array();
    $this->r->tableCards[] = $this->r->teamDeck->draw();
    $this->r->tableCards[] = $this->r->teamDeck->draw();
    $this->r->tableCards[] = $this->r->teamDeck->draw();
    $this->r->tableCardsRevealed[] = false;
    $this->r->tableCardsRevealed[] = false;
    $this->r->tableCardsRevealed[] = false;
    $this->r->called = 0;
  }
  function setupPlayers() {
    $first = null;
    $last = null;
    $playerCount = count($this->r->players);
    $nicks = array_keys($this->r->players);
    shuffle($nicks);
    $new = array();
    foreach($nicks as $nick) $new[$nick] = $this->r->players[$nick];
    $this->r->players = $new;
    foreach($this->r->players as $nick => $player) {
      $player->specialist = $this->r->specDeck->draw();
      $player->specialist->player = $player;
      $player->team = $player->initialTeam = $this->r->teamDeck->draw();
      if($player->team == 'Informant') {
        if($player->specialist->name == 'Blind Informant')
          $this->informants[$nick] = $player->nick. '(Blind Informant)';
        else
          $this->informants[$nick] = $player->nick;
      }
      $this->r->nUser($nick, "You are on the {$player->initialTeam} side and you are a specialist in '{$player->specialist->name}'.");
      $player->vote = $player;
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
    $this->r->controller = $first;
    $this->r->currentPlayer = $this->r->controller;
  }
}
?>
