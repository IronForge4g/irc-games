<?php
class trainCar {
  var $r;
  var $leftColor;
  var $leftNumber;
  var $rightColor;
  var $rightNumber;
  function __construct($root, $lc, $ln, $rc, $rn) {
    $this->r = $root;
    $this->leftColor = $lc;
    $this->leftNumber = $ln;
    $this->rightColor = $rc;
    $this->rightNumber = $rn;
  }
  function display() {
    return $this->colorNum($this->leftColor, $this->leftNumber).chr(3).'15,01|'.chr(15).$this->colorNum($this->rightColor, $this->rightNumber);
  }
  function colorNum($color, $number) {
    if($color == 'Blue') return chr(3).'11,01'.$number.$color{0}.chr(15);
    else if($color == 'Red') return chr(3).'04,01'.$number.$color{0}.chr(15);
    else if($color == 'Green') return chr(3).'09,01'.$number.$color{0}.chr(15);
    else if($color == 'Yellow') return chr(3).'08,01'.$number.$color{0}.chr(15);
    else if($color == 'Purple') return chr(3).'13,01'.$number.$color{0}.chr(15);
    else if($color == 'Wild') return chr(3).'15,01'.$number.$color{0}.chr(15);
  }
}
class trainDeck extends deck {
  function __construct($root, $purple = true) {
    $this->r = $root;
    $this->deck = array();
    $this->discard = array();
    $this->cards[] = new trainCar($root, 'Blue', 2, 'Blue', 2);
    $this->cards[] = new trainCar($root, 'Blue', 2, 'Blue', 2);
    $this->cards[] = new trainCar($root, 'Red', 2, 'Red', 2);
    $this->cards[] = new trainCar($root, 'Red', 2, 'Red', 2);
    $this->cards[] = new trainCar($root, 'Green', 2, 'Green', 2);
    $this->cards[] = new trainCar($root, 'Yellow', 2, 'Yellow', 2);
    $this->cards[] = new trainCar($root, 'Yellow', 2, 'Yellow', 2);
    $this->cards[] = new trainCar($root, 'Blue', 2, 'Blue', 3);
    $this->cards[] = new trainCar($root, 'Red', 2, 'Red', 3);
    $this->cards[] = new trainCar($root, 'Green', 2, 'Green', 3);
    $this->cards[] = new trainCar($root, 'Yellow', 2, 'Yellow', 3);
    $this->cards[] = new trainCar($root, 'Red', 3, 'Green', 3);
    $this->cards[] = new trainCar($root, 'Red', 3, 'Yellow', 3);
    $this->cards[] = new trainCar($root, 'Blue', 3, 'Yellow', 3);
    $this->cards[] = new trainCar($root, 'Blue', 3, 'Red', 3);
    $this->cards[] = new trainCar($root, 'Green', 3, 'Blue', 3);
    $this->cards[] = new trainCar($root, 'Green', 3, 'Red', 3);
    $this->cards[] = new trainCar($root, 'Yellow', 3, 'Blue', 3);
    $this->cards[] = new trainCar($root, 'Yellow', 3, 'Green', 3);
    $this->cards[] = new trainCar($root, 'Blue', 4, 'Green', 4);
    $this->cards[] = new trainCar($root, 'Red', 4, 'Blue', 4);
    $this->cards[] = new trainCar($root, 'Green', 4, 'Yellow', 4);
    $this->cards[] = new trainCar($root, 'Yellow', 4, 'Red', 4);
    if($purple) {
      $this->cards[] = new trainCar($root, 'Purple', 2, 'Purple', 2);
      $this->cards[] = new trainCar($root, 'Purple', 2, 'Purple', 2);
      $this->cards[] = new trainCar($root, 'Purple', 2, 'Purple', 3);
      $this->cards[] = new trainCar($root, 'Purple', 3, 'Green', 3);
      $this->cards[] = new trainCar($root, 'Purple', 3, 'Blue', 3);
      $this->cards[] = new trainCar($root, 'Purple', 3, 'Yellow', 3);
      $this->cards[] = new trainCar($root, 'Purple', 3, 'Red', 3);
      $this->cards[] = new trainCar($root, 'Purple', 4, 'Purple', 4);
    }
    $this->deck = $this->cards;
  }
}
?>
