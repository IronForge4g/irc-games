<?php
class phaseCloud9Cloud {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Climbing';
  }
  function init() {
    if($this->r->votingPlayer == $this->r->currentPlayer) {
      $this->r->setPhase('endcloud');
      return;
    }
    $this->r->nUser($this->r->votingPlayer->nick, "Your hand: ".implode(', ', $this->r->votingPlayer->hand).".");
    $this->r->mChan($this->r->votingPlayer->nick.", you're up. Do you want to !jump or !stay?");
  }
  function cmdjump($from, $args) {
    if(!($this->r->checkVotingPlayer($from, 'vote'))) return;
    $player = $this->r->findPlayer($from);
    $points = $this->r->cloudPoints[$this->r->currentCloud];
    $player->points += $points;
    $player->jumped = true;
    $this->r->mChan("$from has jumped from the balloon gaining ".$this->r->points($points).".");
    $first = $player->left;
    while($first != $this->r->currentPlayer) {
      if($first->jumped) $first = $first->left;
      else break;
    }
    $this->r->votingPlayer = $first;
    $this->r->setPhase('cloud');
  }
  function cmdstay($from, $args) {
    if(!($this->r->checkVotingPlayer($from, 'vote'))) return;
    $player = $this->r->findPlayer($from);
    $this->r->mChan("$from has stayed in the balloon.");
    $first = $player->left;
    while($first != $this->r->currentPlayer) {
      if($first->jumped) $first = $first->left;
      else break;
    }
    $this->r->votingPlayer = $first;
    $this->r->setPhase('cloud');
  }
}
?>
