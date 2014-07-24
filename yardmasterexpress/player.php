<?php
class yardmasterExpressPlayer {
  var $r;
  var $nick;
  var $left;
  var $right;
  var $msgType;

  var $train;

  function __construct($root, $nick) {
    $this->r = $root;
    $this->nick = $nick;
    $this->msgType = 'notice';
    $this->left = null;
    $this->right = null;
    $this->train = null;
  }
  function init() {
  }
  function displayTrain() {
    $display = array();
    if(count($this->train) == 0) return '<empty>';
    foreach($this->train as $car) {
      $display[] = $car->display();
    }
    return implode(chr(3).'15,01 . '.chr(15), $display);
  }
}
?>
