<?php
class theGamePlayer {
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
  function has($card) {
    foreach($this->hand as $key => $val) {
      if($val == $card) return $key;
    }
    return false;
  }
}
?>
