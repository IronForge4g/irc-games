<?php
class deadMansChestPlayer {
  var $r;
  var $nick;
  var $left;
  var $right;
  var $msgType;
  var $gems;

  function __construct($root, $nick) {
    $this->r = $root;
    $this->nick = $nick;
    $this->msgType = 'notice';
    $this->left = null;
    $this->right = null;
    $this->gems = null;
  }
  function init() {
  }
}
?>
