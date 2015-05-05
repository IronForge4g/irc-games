<?php
class bottleImpPlayer {
  var $r;
  var $nick;
  var $left;
  var $right;
  var $msgType;
  var $passLeft;
  var $passRight;
  var $passImp;

  var $hand;
  var $tricks;
  var $score;

  function __construct($root, $nick) {
    $this->r = $root;
    $this->nick = $nick;
    $this->msgType = 'notice';
    $this->left = null;
    $this->right = null;
    $this->passLeft = null;
    $this->passRight = null;
    $this->passImp = null;
    $this->hand = null;
    $this->score = 0;
  }
  function init() {
  }
  function displayHand($letters = false) {
    $display = array();
    if(count($this->hand) == 0) return '<empty>';
    asort($this->hand);
    foreach($this->hand as $letter => $card) {
      if($letters) $display[] = $letter . '. ' . $card->display;
      else $display[] = $card->display;
    }
    return implode(', ', $display);
  }
  function hasSuit($suit) {
    foreach($this->hand as $card) {
      if($card->color == $suit) return true;
    }
    return false;
  }
}
?>
