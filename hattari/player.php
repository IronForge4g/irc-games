<?php
class hattariPlayer {
  var $r;
  var $nick;
  var $left;
  var $right;

  var $chips;
  var $failed;
  var $suspect;
  var $accused;

  function __construct($root, $nick) {
    $this->r = $root;
    $this->nick = $nick;
    $this->left = null;
    $this->right = null;
    $this->chips = null;
    $this->failed = null;
    $this->suspect = null;
    $this->accused = null;
  }
  function init() {
  }
}
?>
