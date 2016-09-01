<?php
class secretHitlerPlayer {
  var $r;
  var $nick;
  var $left;
  var $right;
  var $msgType;
  var $ending;

  var $team;
  var $hitler;
  var $voted;
  var $hand;

  function __construct($root, $nick) {
    $this->r = $root;
    $this->nick = $nick;
    $this->msgType = 'notice';
    $this->left = null;
    $this->right = null;
    $this->ending = null;

    $this->team = null;
    $this->hitler = false;
  }
  function init() {
  }
}
?>
