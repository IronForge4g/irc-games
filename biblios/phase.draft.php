<?php
class phaseBibliosDraft {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Draft Phase';
  }
  function init() {
    $this->r->currentPlayer = $this->r->currentPlayer->left;
    $options = array();
    foreach($this->r->table as $letter => $card) {
      $options[] = "{$letter}. ".$card->display();
    }
    $this->r->mChan($this->r->currentPlayer->nick." please !draft a card: ".implode(', ', $options).".");
  }
  function cmdd($from, $args) {
    $this->cmddraft($from, $args);
  }
  function cmddraft($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'draft a card'))) return;
    if(!($this->r->checkArgs($from, $args, 1))) return;
    $letter = strtoupper($args[0]{0});
    if(!(isset($this->r->table[$letter]))) {
      $this->r->mChan($this->r->currentPlayer->nick.": {$args[0]} is not a valid card to draft.");
      return;
    }
    $card = $this->r->table[$letter];
    unset($this->r->table[$letter]);
    if($card->type == 'church') {
      $this->r->phases['church']->card = $card;
      if(count($this->r->table) > 0) $this->r->phases['church']->returnPhase = 'draft';
      else if($this->r->deck->count() == 0) {
        $this->r->phases['auction']->card = null;
        $this->r->phases['church']->returnPhase = 'auction';
      }
      else $this->r->phases['church']->returnPhase = 'gift';
      $this->r->setPhase('church');
    } else {
      $this->r->currentPlayer->hand[] = $card;
      $this->r->mChan($this->r->currentPlayer->nick." has added ".$card->display()." to their hand.");
      if(count($this->r->table) > 0) $this->r->setPhase('draft');
      else if($this->r->deck->count() == 0) {
        $this->r->phases['auction']->card = null;
        $this->r->setPhase('auction');
      }
      else $this->r->setPhase('gift');
    }
  }
}
?>
