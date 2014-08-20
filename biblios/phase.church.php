<?php
class phaseBibliosChurch {
  var $r;
  var $desc;

  var $card;
  var $returnPhase;
  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Church Card';
  }
  function init() {
    $options = array();
    if($this->card->direction == 'up' or $this->card->direction == 'up or down') $options[] = '!up';
    if($this->card->direction == 'down' or $this->card->direction == 'up or down') $options[] = '!down';
    $this->r->mChan($this->r->currentPlayer->nick." has drawn the church card '".$this->card->display()."'. Please choose {$this->card->dice} dice to ".implode(' or ', $options).". You can also !pass to skip this option.");
    $this->r->cmdboard($this->r->currentPlayer->nick, array());
  }
  function cmdu($from, $args) {
    $this->cmdup($from, $args);
  }
  function cmdd($from, $args) {
    $this->cmddown($from, $args);
  }
  function cmdp($from, $args) {
    $this->cmdpass($from, $args);
  }
  function cmdup($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'play a card'))) return;
    if(!($this->r->checkArgs($from, $args, $this->card->dice))) return;
    if($this->card->direction != 'up' && $this->card->direction != 'up or down') {
      $this->r->mChan($this->r->currentPlayer->nick.": !up is not an option, please use !down.");
      return;
    }
    $dice = array();
    foreach($args as $arg) {
      $color = $this->r->findColor($arg);
      if($color == null) continue;
      $dice[$color] = $color;
    }
    if(count($dice) != $this->card->dice) {
      $this->r->mChan($this->r->currentPlayer->nick.": You must specify {$this->card->dice} unique colors.");
      return;
    }
    $changed = array();
    foreach($dice as $color) {
      $this->r->dice[$color]++;
      if($this->r->dice[$color] > 6) $this->r->dice[$color] = 6;
      $changed[] = $color;
    }
    $this->r->mChan("$from has increased the dice: ".implode(', ', $changed).".");
    $this->r->setPhase($this->returnPhase);
  }
  function cmddown($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'play a card'))) return;
    if(!($this->r->checkArgs($from, $args, $this->card->dice))) return;
    if($this->card->direction != 'down' && $this->card->direction != 'up or down') {
      $this->r->mChan($this->r->currentPlayer->nick.": !down is not an option, please use !up.");
      return;
    }
    $dice = array();
    foreach($args as $arg) {
      $color = $this->r->findColor($arg);
      if($color == null) continue;
      $dice[$color] = $color;
    }
    if(count($dice) != $this->card->dice) {
      $this->r->mChan($this->r->currentPlayer->nick.": You must specify {$this->card->dice} unique colors.");
      return;
    }
    $changed = array();
    foreach($dice as $color) {
      $this->r->dice[$color]--;
      if($this->r->dice[$color] < 1) $this->r->dice[$color] = 1;
      $changed[] = $color;
    }
    $this->r->mChan("$from has decreased the dice: ".implode(', ', $changed).".");
    $this->r->setPhase($this->returnPhase);
  }
  function cmdpass($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'play a card'))) return;
    $this->r->mChan("$from has chosen to !pass on this church card.");
    $this->r->setPhase($this->returnPhase);
  }
}
?>
