<?php
class enGardePlayer {
  var $r;
  var $nick;
  var $left;
  var $right;
  var $msgType;

  var $hand;
  var $position;
  var $side;
  var $score;

  function __construct($root, $nick) {
    $this->r = $root;
    $this->nick = $nick;
    $this->msgType = 'notice';
    $this->left = null;
    $this->right = null;
    $this->hand = null;
    $this->position = null;
    $this->side = null;
    $this->score = 0;
  }
  function init() {
  }
  function has($card, $count = 1) {
    $hasCount = 0;
    foreach($this->hand as $key => $val) {
      if($val == $card) $hasCount++;
    }
    return ($hasCount >= $count ? true : false);
  }
  function discard($card) {
    $discard = 99;
    foreach($this->hand as $key => $val) {
      if($val == $card) {
        $discard = $key;
        break;
      }
    }
    if($discard != 99) {
      unset($this->hand[$discard]);
      $this->r->discarded[] = $card;
      sort($this->r->discarded);
    }
  }
  function draw() {
    if($this->hand == null) $this->hand = array();
    $count = 5 - count($this->hand);
    for($n=0;$n<$count;$n++) {
      $this->hand[] = $this->r->deck->draw();
      if($this->r->deck->count() == 0) return true;
    }
    sort($this->hand);
    $this->r->nUser($this->nick, "Your hand: ". implode(', ', $this->hand));
    return false;
  }
  function distance() {
    if($this->side == 1) return $this->position - 1;
    else return 23 - $this->position;
  }
}
?>
