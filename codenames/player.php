<?php
class codenamesPlayer {
  var $r;
  var $nick;
  var $left;
  var $right;
  var $msgType;

  var $color;
  var $spymaster;
  var $ending;

  function __construct($root, $nick) {
    $this->r = $root;
    $this->nick = $nick;
    $this->msgType = 'notice';
    $this->left = null;
    $this->right = null;
    $this->color = null;
    $this->spymaster = null;
    $this->ending = null;
  }
  function init() {
  }
  function cNick() {
    return $this->r->colorText($this->nick, $this->color);
  }
}
?>
