<?php
class phaseBibliosGift {
  var $r;
  var $desc;

  var $cards;
  var $card;
  var $hand;
  var $auction;
  var $table;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Gift Phase';
  }
  function init() {
    $this->r->activePlayer = $this->r->activePlayer->left;
    $this->r->currentPlayer = $this->r->activePlayer;
    if($this->r->startPlayer == null) $this->r->startPlayer = $this->r->activePlayer;
    $this->r->mChan($this->r->currentPlayer->nick." is the active player...");
    $this->hand = null;
    $this->auction = null;
    $this->cards = count($this->r->players) + 1;
    $this->table = $this->cards - 2;
    $this->draw();
  }
  function draw() {
    $options = array();
    if($this->hand == null) $options[] = 'in your !hand';
    if($this->table > 0) $options[] = 'on the !table';
    if($this->auction == null) $options[] = 'in the !auction';
    $optionCount = count($options);
    if($optionCount > 1) {
      $last = 'or '.array_pop($options);
      $options[] = $last;
    }
    else if($optionCount == 0) {
      $this->r->auction->addCard($this->auction);
      if($this->hand->type == 'church') {
        $this->r->phases['church']->card = $this->hand;
        $this->r->phases['church']->returnPhase = 'draft';
        $this->r->setPhase('church');
        return;
      }
      $this->r->currentPlayer->hand[] = $this->hand;
      $this->r->setPhase('draft');
      return;
    }
    $this->card = $this->r->deck->draw();
    $this->r->nUser($this->r->currentPlayer->nick, "You have drawn ".$this->card->display().". Please choose to put the card ".implode(', ', $options).".");
  }
  function cmdh($from, $args) {
    $this->cmdhand($from, $args);
  }
  function cmdt($from, $args) {
    $this->cmdtable($from, $args);
  }
  function cmda($from, $args) {
    $this->cmdauction($from, $args);
  }
  function cmdhand($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'play a card'))) return;
    if($this->hand != null) {
      $this->r->mChan($this->r->currentPlayer->nick.": You've already added a card to your hand.");
      return;
    }
    $this->hand = $this->card;
    $this->r->mChan($this->r->currentPlayer->nick." has added a card to their hand.");
    $this->draw();
  }
  function cmdauction($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'play a card'))) return;
    if($this->auction != null) {
      $this->r->mChan($this->r->currentPlayer->nick.": You've already added a card to the auction.");
      return;
    }
    $this->auction = $this->card;
    $this->r->mChan($this->r->currentPlayer->nick." has added a card to the auction.");
    $this->draw();
  }
  function cmdtable($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'play a card'))) return;
    if($this->table == 0) {
      $this->r->mChan($this->r->currentPlayer->nick.": You've already added all the cards you can to the table.");
      return;
    }
    $this->table--;
    $letters = array('A', 'B', 'C', 'D');
    $letter = count($this->r->table);
    $this->r->table[$letters[$letter]] = $this->card;
    $this->r->mChan($this->r->currentPlayer->nick." has added a card to the table.");
    $this->draw();
  }
}
?>
