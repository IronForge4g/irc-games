<?php
class camelUpCamel {
  var $r;
  var $color;
  var $below;
  var $above;
  var $position;
  var $placed;

  function __construct($root, $color, $position) {
    $this->r = $root;
    $this->color = $color;
    $this->below = null;
    $this->above = null;
    $this->position = $position;
    $this->placed = false;
  }
  function init() {
  }
  function height() {
    $height = 0;
    $below = $this->below;
    while($below != null) {
      $height++;
      $below = $below->below;
    }
    return $height;
  }
}
?>
