<?php
class camelUpDesertTile {
  var $position;
  var $type;
}
class camelUpPlayer {
  var $r;
  var $nick;
  var $left;
  var $right;

  var $money;
  var $hand;
  var $bets;

  function __construct($root, $nick) {
    $this->r = $root;
    $this->nick = $nick;
    $this->left = null;
    $this->right = null;
    $this->hand = null;
    $this->legTiles = null;
    $this->desertTile = null;
    $this->money = 0;
    $this->bets = null;
  }
  function init() {
  }
 }
?>
