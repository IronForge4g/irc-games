<?php
class phaseFauxCureRound3 {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Cures and Poisons';
  }
  var $cures;
  function init() {
    $this->cures = 0;
    if($this->r->currentPlayer->hand == null) {
      $this->r->setPhase('end');
      return;
    }
    $this->r->currentPlayer->hand = null;
    if(count($this->r->currentPlayer->keep) == 0) {
      $this->r->mChan($this->r->currentPlayer->nick." has no cures or poisons.");
      $this->r->currentPlayer = $this->r->currentPlayer->left;
      $this->r->setPhase('round3');
      return;
    }
    $this->r->mChan($this->r->currentPlayer->nick.": You got stuck with: ".implode(', ', $this->r->currentPlayer->keep).".");
    $this->cures = 0;
    foreach($this->r->currentPlayer->keep as $keep) {
      if($keep == 'Poison') $this->r->currentPlayer->poison++;
      if($keep == 'Cure') $this->cures++;
    }
    if($this->cures >= $this->r->currentPlayer->poison) {
      $this->cures -= $this->r->currentPlayer->poison;
      $this->r->currentPlayer->poison = 0;
      if($this->cures == 1) {
        $this->r->mChan($this->r->currentPlayer->nick.": You have 1 extra cure to use. Please !poison whichever player you would like.");
      } else if ($this->cures > 1) {
        $this->r->mChan($this->r->currentPlayer->nick.": You have {$this->cures} extra cures to use. Please !poison whichever players you would like.");
      } else {
        $this->r->mChan($this->r->currentPlayer->nick." takes no poison, but has no extra cures.");
        $this->r->currentPlayer = $this->r->currentPlayer->left;
        $this->r->setPhase('round3');
        return;
      }
      $this->r->mChan("Players: ".$this->r->playerList('score').'.');
    }
    else {
      $this->r->currentPlayer->poison -= $this->cures;
      $this->r->mChan($this->r->currentPlayer->nick." now has {$this->r->currentPlayer->poison} poison.");
      $this->r->currentPlayer = $this->r->currentPlayer->left;
      $this->r->setPhase('round3');
      return;
    }
  }
  function cmdpoison($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'Cures and Poisons'))) return;
    $poisons = array();
    $cures = 0;
    foreach($args as $arg) {
      $player = $this->r->findPlayer($arg);
      if($player == null) {
        $this->r->mChan("$from: $arg is not a valid player.");
        return;
      }
      $poisons[] = $player;
      $cures++;
    }
    if($cures > $this->cures) {
      $this->r->mChan("$from: You don't have that many extra cures to use.");
      return;
    }
    foreach($poisons as $poison) {
      $poison->poison++;
      $this->cures--;
    }
    if($this->cures == 1) {
      $this->r->mChan($this->r->currentPlayer->nick.": You have 1 extra cure to use. Please !poison whichever player you would like.");
    } else if ($this->cures > 1) {
      $this->r->mChan($this->r->currentPlayer->nick.": You have {$this->cures} extra cures to use. Please !poison whichever players you would like.");
    } else {
      $this->r->currentPlayer = $this->r->currentPlayer->left;
      $this->r->setPhase('round3');
      return;
    }
  }
}
?>
