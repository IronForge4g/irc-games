<?php
class playerFauxCure {
  var $r;
  var $nick;
  var $left;
  var $right;

  var $poison;
  var $hand;
  var $gift;
  var $keep;
  var $returned;

  function __construct($root, $nick) {
    $this->r = $root;
    $this->nick = $nick;
    $this->left = null;
    $this->right = null;
    $this->poison = 0;
    $this->hand = null;
    $this->gift = array();
    $this->keep = array();
    $this->returned = false;
  }
  function init() {
  }
 }
?>
