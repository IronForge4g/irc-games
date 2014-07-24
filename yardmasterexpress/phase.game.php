<?php
class phaseYardmasterExpressGame {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Playing a card';
  }
  function init() {
    $trainLength = count($this->r->currentPlayer->train);
    if($trainLength == $this->r->gameEnd) {
      $this->r->setPhase('end');
      return;
    }
    $draw = $this->r->deck->draw();
    $this->r->hand[] = $draw;
    shuffle($this->r->hand);
    $letters = array('A', 'B', 'C', 'D', 'E', 'F');
    $newHand = array();
    $l = 0;
    $drawn = '';
    foreach($this->r->hand as $card) {
      $letter = $letters[$l++];
      $newHand[$letter] = $card;
      if($card == $draw) $drawn = $letter;
    }
    $this->r->hand = $newHand;
    $roundsLeft = $this->r->gameEnd - $trainLength;
    if($roundsLeft == 1) $this->r->nUser($this->r->currentPlayer->nick, "Your hand is ".$this->r->displayHand($drawn).". $drawn is the card you just picked up. You have to add {$roundsLeft} car to your train still. THIS IS YOUR LAST ROUND!");
    else $this->r->nUser($this->r->currentPlayer->nick, "Your hand is ".$this->r->displayHand($drawn).". $drawn is the card you just picked up. You have to add {$roundsLeft} cars to your train still.");
    if($this->r->autoScore) $this->r->mChan($this->r->currentPlayer->nick.", you're up. Please !play or !wild a card to add it to your train.");
    else $this->r->mChan($this->r->currentPlayer->nick.", you're up. Your train is: ".$this->r->currentPlayer->displayTrain().'. Please !play or !wild a card to add it to your train.');
  }
  function cmdpa($from, $args) {
    $this->cmdplay($from, array('a'));
  }
  function cmdpb($from, $args) {
    $this->cmdplay($from, array('b'));
  }
  function cmdpc($from, $args) {
    $this->cmdplay($from, array('c'));
  }
  function cmdpd($from, $args) {
    $this->cmdplay($from, array('d'));
  }
  function cmdpe($from, $args) {
    $this->cmdplay($from, array('e'));
  }
  function cmdpf($from, $args) {
    $this->cmdplay($from, array('f'));
  }
  function cmdp($from, $args) {
    $this->cmdplay($from, $args);
  }
  function cmdplay($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'play a card'))) return;
    if(!($this->r->checkArgs($from, $args, 1))) return;
    $card = strtoupper($args[0]);
    if(!(isset($this->r->hand[$card]))) {
      $this->r->mChan($this->r->currentPlayer->nick.": Please specify a valid card to play.");
      return;
    }
    $trainLength = count($this->r->currentPlayer->train);
    if($trainLength > 0) {
      $lastCar = $this->r->currentPlayer->train[$trainLength - 1];
      if($lastCar->rightColor != 'Wild') {
        if($lastCar->rightColor != $this->r->hand[$card]->leftColor && $lastCar->rightNumber != $this->r->hand[$card]->leftNumber) {
          $this->r->mChan($this->r->currentPlayer->nick.": Unfortunately that card can't be added to your current train.");
          return;
        }
      }
    }
    $this->r->currentPlayer->train[] = $this->r->hand[$card];
    $this->r->mChan($this->r->currentPlayer->nick." has added to their train: ".$this->r->currentPlayer->displayTrain().".");
    unset($this->r->hand[$card]);
    if($this->r->autoScore) $this->r->score();
    $this->r->currentPlayer = $this->r->currentPlayer->left;
    $this->r->setPhase('game');
  }
  function cmdwa($from, $args) {
    $this->cmdwild($from, array('a'));
  }
  function cmdwb($from, $args) {
    $this->cmdwild($from, array('b'));
  }
  function cmdwc($from, $args) {
    $this->cmdwild($from, array('c'));
  }
  function cmdwd($from, $args) {
    $this->cmdwild($from, array('d'));
  }
  function cmdwe($from, $args) {
    $this->cmdwild($from, array('e'));
  }
  function cmdwf($from, $args) {
    $this->cmdwild($from, array('f'));
  }
  function cmdw($from, $args) {
    $this->cmdwild($from, $args);
  }
  function cmdwild($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'play a card'))) return;
    if(!($this->r->checkArgs($from, $args, 1))) return;
    $card = strtoupper($args[0]);
    if(!(isset($this->r->hand[$card]))) {
      $this->r->mChan($this->r->currentPlayer->nick.": Please specify a valid card to play.");
      return;
    }
    $this->r->currentPlayer->train[] = $this->r->wild;
    $this->r->mChan($this->r->currentPlayer->nick." adds to their train: ".$this->r->currentPlayer->displayTrain().".");
    unset($this->r->hand[$card]);
    if($this->r->autoScore) $this->r->score();
    $this->r->currentPlayer = $this->r->currentPlayer->left;
    $this->r->setPhase('game');
  }
}
?>
