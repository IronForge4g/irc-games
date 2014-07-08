<?php
class phaseFauxCureRound2 {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Returning Cards';
  }
  function init() {
    if($this->r->currentPlayer->returned) {
      $this->r->setPhase('round3');
      return;
    }
    if(count($this->r->currentPlayer->gift) == 0) {
      $this->r->mChan($this->r->currentPlayer->nick." has no cards to return.");
      $this->r->currentPlayer = $this->r->currentPlayer->left;
      $this->r->setPhase('round2');
      return;
    }
    $players = array_keys($this->r->currentPlayer->gift);
    if(count($players) == 1) {
      $this->r->mChan($this->r->currentPlayer->nick.": You have a card from ".implode(', ', $players).". Please !return any card you wish. (eg. !return ".implode(' ', $players).", or just !return with no players to keep them all.)");
    } else {
      $this->r->mChan($this->r->currentPlayer->nick.": You have cards from ".implode(', ', $players).". Please !return any cards you wish. (eg. !return ".implode(' ', $players).", or just !return with no players to keep them all.)");
    }
  }
  function cmdreturn($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'Returning Cards'))) return;
    $returns = array();
    foreach($args as $arg) {
      $player = $this->r->findPlayer($arg);
      if($player == null) {
        $this->r->mChan("$from: $arg is not a valid player.");
        return;
      }
      $returns[] = $player->nick;
    }
    if(count($returns) == 0) {
      $this->r->mChan("$from chooses to keep all their cards.");
    } else if(count($returns) == 1) {
      $this->r->mChan("$from gives ".implode(', ', $returns)." their card back.");
    } else {
      $this->r->mChan("$from gives ".implode(', ', $returns)." their cards back.");
    }
    foreach($returns as $return) {
      $player = $this->r->findPlayer($return);
      $player->keep[] = $this->r->gift[$return];
      unset($this->r->currentPlayer->gift[$return]);
    }
    foreach($this->r->currentPlayer->gift as $nick => $gift) {
      $this->r->currentPlayer->keep[] = $gift;
    }
    $this->r->currentPlayer->returned = true;
    $this->r->currentPlayer = $this->r->currentPlayer->left;
    $this->r->setPhase('round2');
  }
}
?>
