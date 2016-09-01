<?php
class phaseEnGardeNewRound {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Setting up New Round';
  }
  function init() {
    $score = array();
    $winner = null;
    foreach($this->r->players as $nick => $player) {
      $score[] = $nick.' ('.$player->score.')';
      if($player->score >= $this->r->score) $winner = $player;
      $player->hand = null;
      if($player->side == 1) $player->position = 1;
      else $player->position = 23;
    }
    $this->r->mChan('Current Score: '.implode(', ', $score));
    if($winner != null) {
      $this->r->mChan("{$winner->nick} has won the game!");
      $this->r->setPhase('nogame');
      return;
    }
    $this->r->deck = new enGardeDeck($this->r);
    $this->r->discarded = array();
    foreach($this->r->players as $nick => $player) $player->draw();
    $this->r->mChan("A new round will now begin.");
    $this->r->startPlayer = $this->r->startPlayer->left;
    $this->r->currentPlayer = $this->r->startPlayer;
    $this->r->setPhase('offence');
  }
}
?>
