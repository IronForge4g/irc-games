<?php
class deadDropPlayer {
  var $r;
  var $nick;
  var $left;
  var $right;
  var $msgType;

  var $hand;
  var $score;

  function __construct($root, $nick) {
    $this->r = $root;
    $this->nick = $nick;
    $this->msgType = 'notice';
    $this->left = null;
    $this->right = null;
    $this->hand = null;
    $this->score = 0;
  }
  function init() {
  }
  function displayHand() {
    shuffle($this->hand);
    $letters = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
    $display = array();
    $displayLetter = 0;
    $letterHand = array();
    shuffle($this->hand);
    foreach($this->hand as $card) {
      $letter = $letters[$displayLetter++];
      $letterHand[$letter] = $card;
    }
    asort($letterHand);
    $this->hand = $letterHand;
    foreach($this->hand as $letter => $card) {
      $display[] = "$letter. ".$card;
    }
    $this->r->nUser($this->nick, "Your Hand: ".implode(', ', $display).".");
  }
}
?>
