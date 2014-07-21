<?php
class phaseCloud9StartCloud {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Climbing';
  }
  function init() {
    $first = $this->r->currentPlayer->left;
    while($first != $this->r->currentPlayer) {
      if($first->jumped) $first = $first->left;
      else break;
    }
    if($first == $this->r->currentPlayer) {
      $this->r->setPhase('solo');
      return;
    }
    $this->r->votingPlayer = $first;
    $this->r->rollDice();
    $this->r->score();
    $this->r->nUser($this->r->currentPlayer->nick, "Your hand: ".implode(', ', $this->r->currentPlayer->hand).".");
    $this->r->mChan($this->r->currentPlayer->nick." is the current pilot with ".count($this->r->currentPlayer->hand)." skills. We are at Cloud #{$this->r->currentCloud} and the skills required are ".implode(', ', $this->r->requiredSkills).". Jumping from this cloud is worth ".$this->r->points($this->r->cloudPoints[$this->r->currentCloud]).". Other players still in the balloon: ".$this->stillIn().".");
    $this->r->setPhase('cloud');
  }
  function stillIn() {
    $stillIn = array();
    $first = $this->r->currentPlayer->left;
    while($first != $this->r->currentPlayer) {
      if(!($first->jumped)) $stillIn[] = $first->nick;
      $first = $first->left;
    }
    return implode(', ', $stillIn);
  }
}
?>

