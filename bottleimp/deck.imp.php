<?php
class impCard {
  var $r;
  var $value;
  var $color;
  var $points;
  var $display;
  function __construct($root, $v, $c, $p = 0) {
    $this->r = $root;
    $this->value = $v;
    $this->color = $c;
    $this->points = $p;
    $this->display = $this->colorNum($c, $v, $p);
  }
  function display() {
    return $this->colorNum($this->leftColor, $this->leftNumber).chr(3).'15,01|'.chr(15).$this->colorNum($this->rightColor, $this->rightNumber);
  }
  function colorNum($color, $number, $points) {
    $display = $number.' ('.$color{0}.'/'.$points.')';
    if($color == 'Blue') return chr(3).'11,01'.$display.chr(15);
    else if($color == 'Orange') return chr(3).'07,01'.$display.chr(15);
    else if($color == 'Yellow') return chr(3).'08,01'.$display.chr(15);
    else if($color == 'White') return chr(3).'00,01'.$display.chr(15);
  }
}
class impDeck extends deck {
  function __construct($root) {
    $this->r = $root;
    $this->deck = array();
    $this->discard = array();
    $this->cards[1] = new impCard($root, 1, 'Yellow', 1);
    $this->cards[2] = new impCard($root, 2, 'Yellow', 1);
    $this->cards[3] = new impCard($root, 3, 'Yellow', 2);
    $this->cards[4] = new impCard($root, 4, 'Blue', 1);
    $this->cards[5] = new impCard($root, 5, 'Yellow', 2);
    $this->cards[6] = new impCard($root, 6, 'Blue', 1);
    $this->cards[7] = new impCard($root, 7, 'Yellow', 3);
    $this->cards[8] = new impCard($root, 8, 'Blue', 2);
    $this->cards[9] = new impCard($root, 9, 'Yellow', 3);
    $this->cards[10] = new impCard($root, 10, 'Blue', 2);
    $this->cards[11] = new impCard($root, 11, 'Orange', 1);
    $this->cards[12] = new impCard($root, 12, 'Yellow', 4);
    $this->cards[13] = new impCard($root, 13, 'Blue', 3);
    $this->cards[14] = new impCard($root, 14, 'Orange', 1);
    $this->cards[15] = new impCard($root, 15, 'Yellow', 4);
    $this->cards[16] = new impCard($root, 16, 'Orange', 2);
    $this->cards[17] = new impCard($root, 17, 'Blue', 3);
    $this->cards[18] = new impCard($root, 18, 'Yellow', 5);
    $this->cards[20] = new impCard($root, 20, 'Blue', 4);
    $this->cards[21] = new impCard($root, 21, 'Orange', 2);
    $this->cards[22] = new impCard($root, 22, 'Yellow', 5);
    $this->cards[23] = new impCard($root, 23, 'Orange', 3);
    $this->cards[24] = new impCard($root, 24, 'Blue', 4);
    $this->cards[25] = new impCard($root, 25, 'Yellow', 6);
    $this->cards[26] = new impCard($root, 26, 'Orange', 3);
    $this->cards[27] = new impCard($root, 27, 'Blue', 5);
    $this->cards[28] = new impCard($root, 28, 'Yellow', 6);
    $this->cards[29] = new impCard($root, 29, 'Orange', 4);
    $this->cards[30] = new impCard($root, 30, 'Blue', 5);
    $this->cards[31] = new impCard($root, 31, 'Orange', 4);
    $this->cards[32] = new impCard($root, 32, 'Blue', 6);
    $this->cards[33] = new impCard($root, 33, 'Orange', 5);
    $this->cards[34] = new impCard($root, 34, 'Blue', 6);
    $this->cards[35] = new impCard($root, 35, 'Orange', 5);
    $this->cards[36] = new impCard($root, 36, 'Orange', 6);
    $this->cards[37] = new impCard($root, 37, 'Orange', 6);
    $this->deck = $this->cards;
  }
}
?>
