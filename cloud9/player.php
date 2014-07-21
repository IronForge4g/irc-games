<?php
class cloud9Player {
  var $r;
  var $nick;
  var $left;
  var $right;

  var $points;
  var $hand;
  var $jumped;

  function __construct($root, $nick) {
    $this->r = $root;
    $this->nick = $nick;
    $this->left = null;
    $this->right = null;
    $this->points = 0;
    $this->hand = null;
    $this->jumped = null;
  }
  function init() {
  }
}
?>
