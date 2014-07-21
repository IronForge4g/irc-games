<?php
class phaseCloud9Solo {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Solo';
  }
  function init() {
    $this->r->required = null;
    $this->r->nUser($this->r->currentPlayer->nick, "Your hand: ".implode(', ', $this->r->currentPlayer->hand).".");
    $this->r->mChan($this->r->currentPlayer->nick.", you're flying solo with ".count($this->r->currentPlayer->hand)." skills. You are at Cloud #{$this->r->currentCloud}. Jumping from this cloud is worth ".$this->r->points($this->r->cloudPoints[$this->r->currentCloud]).". Do you want to !jump or !stay?");
  }
  function cmdjump($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'solo'))) return;
    if($this->r->required != null) {
      $this->r->mChan("Sorry $from, you've already chosen to stay.");
      return;
    }
    $player = $this->r->findPlayer($from);
    $points = $this->r->cloudPoints[$this->r->currentCloud];
    $player->points += $points;
    $player->jumped = true;
    $this->r->currentPlayer = $this->r->currentPlayer->left;
    $this->r->mChan("$from has jumped from the balloon gaining ".$this->r->points($points).".");
    $this->r->setPhase('endclimb');
  }
  function cmdstay($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'solo'))) return;
    if($this->r->required != null) {
      $this->r->mChan("Sorry $from, you've already chosen to stay.");
      return;
    }
    $this->r->rollDice();
    $this->r->setPhase('endcloud');
  }
}
?>
