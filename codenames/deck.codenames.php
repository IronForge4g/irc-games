<?php
class codenameCard {
  var $r;
  var $code;
  var $color;
  var $revealed;
  function __construct($root, $code, $color) {
    $this->r = $root;
    $this->code = $code;
    $this->color = $color;
    $this->revealed = false;
  }
  function cWord() {
    return $this->r->colorText($this->code, $this->color);
  }
}
class codenamesDeck extends deck {
  function __construct($root) {
    $this->r = $root;
    $this->deck = array();
    $this->discard = array();
    $this->cards = array(
    );
    $this->deck = $this->cards;
  }
}
?>
