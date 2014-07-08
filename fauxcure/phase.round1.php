<?php
class phaseFauxCureRound1 {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Passing Cards';
  }
  function init() {
    if($this->r->currentPlayer->hand == null) {
      $pHand = array('Poison', 'Poison', 'Cure', 'Cure');
      shuffle($pHand);
      $this->r->currentPlayer->hand = array('A' => $pHand[0], 'B' => $pHand[1], 'C' => $pHand[2], 'D' => $pHand[3]);
      $this->r->currentPLayer->gift = array();
      $this->r->currentPLayer->keep = array();
      $this->r->currentPlayer->returned = false;
    }
    if(count($this->r->players) == 2 && count($this->r->currentPlayer->hand) == 3) {
      $this->r->setPhase('round2');
      return;
    }
    if(count($this->r->currentPlayer->hand) == 2) {
      $this->r->setPhase('round2');
      return;
    }
    $this->r->mChan($this->r->currentPlayer->nick.": You're up! Please !pass a card to a player. (eg. !pass player card)");
    $this->r->mChan("Players: ".$this->r->playerList('score').'.');
    $this->r->nUser($this->r->currentPlayer->nick, "Your hand: ".$this->displayHand());
  }
  function displayHand() {
    $display = array();
    foreach($this->r->currentPlayer->hand as $letter => $card) {
      $display[] = "$letter. $card";
    }
    return implode(', ', $display);
  }
  function cmdpass($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'Passing Cards'))) return;
    if(!($this->r->checkArgs($from, $args, 2, 2))) return;
    $player = $this->r->findPlayer($args[0]);
    if($player == null) $player = $this->r->findPlayer($args[1]);
    if($player == null) {
      $this->r->mChan("$from: Please specify a valid player.");
      return;
    }
    if($player->nick == $from) {
      $this->r->mChan("$from: You can't pass to yourself.");
      return;
    }
    if(isset($player->gift[$from])) {
      $this->r->mChan("$from: You have already given {$player->nick} a card.");
      return;
    }
    if(count($player->gift) == 2) {
      $this->r->mChan("$from: {$player->nick} already has 2 cards given to them. Please choose another player.");
      return;
    }
    $card = null;
    if(isset($this->r->currentPlayer->hand[$args[0]])) {
      $card = $this->r->currentPlayer->hand[$args[0]];
      unset($this->r->currentPlayer->hand[$args[0]]);
    }
    else if(isset($this->r->currentPlayer->hand[$args[1]])) {
      $card = $this->r->currentPlayer->hand[$args[1]];
      unset($this->r->currentPlayer->hand[$args[1]]);
    }
    if($card == null) {
      $this->r->mChan($from.": Please select a valid card to give them.");
      return;
    }
    $player->gift[$from] = $card;
    $this->r->currentPlayer = $this->r->currentPlayer->left;
    $this->r->setPhase('round1');
  }
  function cmdgive($from, $args) {
    $this->cmdpass($from, $args);
  }
}
?>
