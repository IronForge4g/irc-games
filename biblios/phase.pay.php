<?php
class phaseBibliosPay {
  var $r;
  var $desc;

  var $card;
  var $cost;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Pay Phase';
  }
  function init() {
    $this->r->currentPlayer->shuffleHand();
    $this->r->currentPlayer->displayHand(true);
  }
  function cmdp($from, $args) {
    $this->cmdpay($from, $args);
  }
  function cmdpay($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'play a card'))) return;
    if($this->card->type == 'gold') {
      $cards = array();
      foreach($args as $arg) {
        $letter = strtoupper($arg{0});
        $cardKey = ord($letter) - 65;
        if(isset($this->r->currentPlayer->hand[$cardKey])) $cards[$letter] = $cardKey;
      }
      if(count($cards) != $this->cost) {
        $this->r->mChan("$from: Your bid for this card was {$this->cost}. Please choose that many cards to pay with from your hand.");
        return;
      }
    } else {
      $cards = array();
      $paid = 0;
      foreach($args as $arg) {
        $letter = strtoupper($arg{0});
        $cardKey = ord($letter) - 65;
        if(isset($this->r->currentPlayer->hand[$cardKey])) {
          if($this->r->currentPlayer->hand[$cardKey]->type == 'gold') {
            $cards[$letter] = $cardKey;
            $paid += $this->r->currentPlayer->hand[$cardKey]->number;
          }
        }
      }
      if($paid < $this->cost) {
        $this->r->mChan("$from: Your bid for this card was {$this->cost}. Please choose enough gold to pay with from your hand.");
        return;
      }
    }
    $discard = array();
    foreach($cards as $letter => $key) {
      $discard[] = $this->r->currentPlayer->hand[$key]->display();
      unset($this->r->currentPlayer->hand[$key]);
    }
    $this->r->mChan("$from has discarded: ".implode(', ', $discard).".");
    if($this->card->type == 'church') {
      $this->r->phases['church']->card = $this->card;
      $this->r->phases['church']->returnPhase = 'auction';
      $this->r->setPhase('church');
      return;
    }
    $this->r->currentPlayer->hand[] = $this->card;
    $this->r->setPhase('auction');
  }
}
?>
