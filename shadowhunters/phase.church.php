<?php
class phaseChurch {
  var $r;
  var $desc;

  var $card;
  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Church';
  }
  function init() {
    $this->r->mChan($this->r->currentPlayer->nick.": You have ended up at the Church. Please !draw a card, or !pass this action.");
    $this->card = null;
  }
  function cmddraw($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'Church Card'))) return;
    if($this->card != null) {
      $this->r->mChan($from.": A card has already been drawn.");
      return;
    }
    $this->card = $this->r->churchDeck->draw();
    if(method_exists($this->card, 'auto')) {
      $auto = $this->card->auto($this->r->currentPlayer, array());
      if($auto) {
        $this->r->mChan($this->r->currentPlayer->nick." has drawn {$this->card->name}: '{$this->card->cardText}'");
        $this->churchDeck->discard($this->card);
        $this->r->setPhase('attack');
        return;
      }
    } 
    if(method_exists($this->card, 'action')) {
      $this->r->mChan($this->r->currentPlayer->nick." has drawn {$this->card->name}: '{$this->card->cardText}' {$this->card->cmdText}");
    }
    if(!(method_exists($this->card, 'auto')) && !(method_exists($this->card, 'action'))) {
      $this->r->mChan($this->r->currentPlayer->nick." has drawn {$this->card->name}: '{$this->card->cardText}', adding it to their equipment.");
      $this->r->currentPlayer->equipment[] = $this->card;
      $this->r->currentPlayer->equipment();
      $this->r->setPhase('attack');
    }
  }
  function cmdcard($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'Church Card'))) return;
    if($this->card == null) {
      $this->r->mChan($from.": No card has been drawn yet. Please draw a card.");
      return;
    }
    $success = $this->card->action($this->r->currentPlayer, $args);
    if($success) {
      $this->churchDeck->discard($this->card);
      $this->r->setPhase('attack');
    }
  }
  function cmdpass($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'Church Card'))) return;
    if($this->card != null) {
      $this->r->mChan($from.": A card has already been drawn.");
      return;
    }
    $this->r->setPhase('attack');
  }
}
?>
