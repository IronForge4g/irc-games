<?php
class phaseHermit {
  var $r;
  var $desc;

  var $card;
  var $target;
  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Hermits Cabin';
  }
  function init() {
    $this->r->mChan($this->r->currentPlayer->nick.": You have ended up at the Hermits Cabin. Please !draw a card, or !pass this action.");
    $this->card = null;
    $this->target = null;
  }
  function cmddraw($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'Hermit Card'))) return;
    if($this->card != null) {
      $this->r->mChan($from.": A card has already been drawn.");
      return;
    }
    $this->card = $this->r->hermitDeck->draw();
    $this->r->nUser($this->r->currentPlayer->nick, "You have drawn {$this->card->name}: '{$this->card->cardText}' Who would you like to !give it to?");
  }
  function cmdpass($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'Hermit Card'))) return;
    if($this->card != null) {
      $this->r->mChan($from.": A card has already been drawn.");
      return;
    }
    $this->r->setPhase('attack');
  }
  function cmdgive($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'Hermit Card'))) return;
    if($this->card == null) {
      $this->r->mChan($from.": Please draw a card before trying to give it away.");
      return;
    }
    if(!($this->r->checkArgs($from, $args, 1))) return;
    if($this->target != null) {
      $this->r->mChan($from.": A target ({$this->target->nick}) for the card has already been chosen.");
      return;
    } 
    $target = $args[0];
    if(!($this->r->validTarget($target))) {
      $this->r->mChan($from.": $target is not a valid player to give this card to.");
      return;
    }
    $targetPlayer = $this->r->players[$target];
    if(!($targetPlayer->alive)) {
      $this->r->mChan($from.": $target is not a valid player to give this card to.");
      return;
    }
    $this->target = $targetPlayer;
    $this->validTarget(true);
    $this->r->mChan("$from has given a hermit card to {$this->target->nick}. {$this->target->nick}, what do you do with it?");
  }
  function cmdcard($from, $args) {
    if($this->target == null) {
      $this->r->mChan($from.": No target has been chosen yet. Please wait for the target to be named.");
      return;
    }
    if($from != $this->target->nick) {
      $this->r->mChan($from.": Sorry, but you were not the target of this card.");
      return;
    }
    $validTarget = $this->validTarget();
    if(!(in_array('card', $validTarget))) {
      $this->r->mChan($from.": Sorry, but cheating is not allowed. Please choose !nothing, and feel shame for your attempt at ruining the game.");
      return;
    }
    $this->card->action($this->target, $args);
    $this->r->hermitDeck->discard($this->card);
    $this->r->setPhase('attack');
  }
  function cmdnothing($from, $args) {
    if($this->target == null) {
      $this->r->mChan($from.": No target has been chosen yet. Please wait for the target to be named.");
      return;
    }
    if($from != $this->target->nick) {
      $this->r->mChan($from.": Sorry, but you were not the target of this card.");
      return;
    }
    $validTarget = $this->validTarget();
    if(!(in_array('nothing', $validTarget))) {
      $this->r->mChan($from.": Sorry, but cheating is not allowed. Please choose !card, and feel shame for your attempt at ruining the game.");
      return;
    }
    $this->r->mChan($from." has chosen nothing happens.");
    $this->r->hermitDeck->discard($this->card);
    $this->r->setPhase('attack');
  }
  function validTarget($notice = false) {
    if($this->target->character->name == 'Unknown') {
      if($this->card->name == 'Prediction') {
        if($notice) $this->r->nUser($this->target->nick, "You have been given {$this->card->name}: '{$this->card->cardText}' There is no avoiding this card, please !card to follow the actions.");
        return array('card');
      } else {
        if($notice) $this->r->nUser($this->target->nick, "You have been given {$this->card->name}: '{$this->card->cardText}' As the Unknown, you may choose !card to follow the card actions, or !nothing to have nothing happen.");
        return array('card', 'nothing');
      }
    }
    if(in_array($this->target->character->team, $this->card->targets)) {
      if($notice) $this->r->nUser($this->target->nick, "You have been given {$this->card->name}: '{$this->card->cardText}' There is no avoiding this card, please !card to follow the actions.");
      return array('card');
    } else if(in_array($this->target->character->name, $this->card->targets)) {
      if($notice) $this->r->nUser($this->target->nick, "You have been given {$this->card->name}: '{$this->card->cardText}' There is no avoiding this card, please !card to follow the actions.");
      return array('card');
    } else {
      if($notice) $this->r->nUser($this->target->nick, "You have been given {$this->card->name}: '{$this->card->cardText}' This card does not apply to you, please !nothing to have nothing happen.");
      return array('nothing');
    }
  }
}
?>
