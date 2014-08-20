<?php
class bibliosPlayer {
  var $r;
  var $nick;
  var $left;
  var $right;
  var $msgType;

  var $hand;

  function __construct($root, $nick) {
    $this->r = $root;
    $this->nick = $nick;
    $this->msgType = 'notice';
    $this->left = null;
    $this->right = null;
    $this->hand = null;
  }
  function init() {
  }
  function shuffleHand() {
    shuffle($this->hand);
  }
  function displayHand($showLetters = false) {
    $letters = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
    $display = array();
    $counts = array('Red' => 0, 'Orange' => 0, 'Green' => 0, 'Blue' => 0, 'Purple' => 0, 'Gold' => 0);
    $countDisplay = array();
    $displayLetter = 0;
    foreach($this->hand as $card) {
      $letter = $letters[$displayLetter++];
      $display[] = ($showLetters ? "$letter. " : '').$card->display();
      if($card->type == 'resource') {
        $counts[$card->color] += $card->number;
      } else {
        $counts['Gold'] += $card->number;
      }
    }
    sort($display);
    foreach($counts as $key => $val) {
      $countDisplay[] = "$key: $val";
    }
    $this->r->nUser($this->nick, "Cards: ".implode(', ', $display).".");
    $this->r->nUser($this->nick, "Count: ".implode(', ', $countDisplay).".");
  }
  function displayColors() {
    $letters = array('Red' => 99, 'Orange' => 99, 'Green' => 99, 'Blue' => 99, 'Purple' => 99, 'Gold' => 99);
    $counts = array('Red' => 0, 'Orange' => 0, 'Green' => 0, 'Blue' => 0, 'Purple' => 0, 'Gold' => 0);
    $countDisplay = array();
    foreach($this->hand as $card) {
      if($card->type == 'resource') {
        $counts[$card->color] += $card->number;
        if(ord($card->letter) < $letters[$card->color]) $letters[$card->color] = ord($card->letter);
      } else if ($card->type == 'gold') {
        $counts['Gold'] += $card->number;
      }
    }
    foreach($counts as $key => $val) {
      $best = '';
      if($letters[$key] < 99) $best = ' ('.chr($letters[$key]).')';
      $countDisplay[] = "$key: {$val}{$best}";
    }
    $this->r->mChan($this->nick." had ".implode(', ', $countDisplay).".");
    return $counts;
  }
  function bestLetter($color) {
    $letter = 999;
    foreach($this->hand as $card) {
      if($card->type == 'resource') {
        if($card->color == $color)  {
          $tLetter = ord($card->letter);
          if($tLetter < $letter) $letter = $tLetter;
        }
      }
    }
    return $letter;
  }
  function cards() {
    return count($this->hand);
  }
  function gold() {
    $gold = 0;
    foreach($this->hand as $card) {
      if($card->type == 'gold') $gold += $card->number;
    }
    return $gold;
  }
}
?>
