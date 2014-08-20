<?php
class bibliosResourceCard {
  var $r;
  var $type;
  var $color;
  var $number;
  var $letter;
  function __construct($root, $c, $n, $l) {
    $this->r = $root;
    $this->type = 'resource';
    $this->color = $c;
    $this->number = $n;
    $this->letter = $l;
  }
  function display() {
    return "{$this->number} {$this->color} ({$this->letter})";
  }
}
class bibliosGoldCard {
  var $r;
  var $type;
  var $number;
  function __construct($root, $n) {
    $this->r = $root;
    $this->type = 'gold';
    $this->number = $n;
  }
  function display() {
    return "{$this->number} Gold";
  }
}
class bibliosChurchCard {
  var $r;
  var $type;
  var $dice;
  var $direction;
  function __construct($root, $d, $ud) {
    $this->r = $root;
    $this->type = 'church';
    $this->dice = $d;
    $this->direction = $ud;
  }
  function display() {
    return "{$this->dice} dice {$this->direction}";
  }
}
class bibliosDeck extends deck {
  function __construct($root, $playerCount) {
    $this->r = $root;
    $this->deck = array();
    $this->discard = array();
    $letters = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I');
    $tLetter = 0;
    for($i=0;$i<7;$i++) {
      $this->cards[] = new bibliosResourceCard($root, 'Red', 1, $letters[$tLetter]);
      $this->cards[] = new bibliosResourceCard($root, 'Orange', 1, $letters[$tLetter]);
      $this->cards[] = new bibliosResourceCard($root, 'Green', 1, $letters[$tLetter++]);
    }
    for($i=8;$i<10;$i++) {
      $this->cards[] = new bibliosResourceCard($root, 'Red', 2, $letters[$tLetter]);
      $this->cards[] = new bibliosResourceCard($root, 'Orange', 2, $letters[$tLetter]);
      $this->cards[] = new bibliosResourceCard($root, 'Green', 2, $letters[$tLetter++]);
    }
    $tLetter = 0;
    for($i=0;$i<4;$i++) {
      $this->cards[] = new bibliosResourceCard($root, 'Blue', 2, $letters[$tLetter]);
      $this->cards[] = new bibliosResourceCard($root, 'Purple', 2, $letters[$tLetter++]);
    }
    for($i=4;$i<7;$i++) {
      $this->cards[] = new bibliosResourceCard($root, 'Blue', 3, $letters[$tLetter]);
      $this->cards[] = new bibliosResourceCard($root, 'Purple', 3, $letters[$tLetter++]);
    }
    for($i=8;$i<10;$i++) {
      $this->cards[] = new bibliosResourceCard($root, 'Blue', 4, $letters[$tLetter]);
      $this->cards[] = new bibliosResourceCard($root, 'Purple', 4, $letters[$tLetter++]);
    }
    $gold = 11;
    $discard = 7;
    if($playerCount == 2) {$gold = 9;$discard = 21;}
    else if($playerCount == 3) {$gold = 10;$discard = 12;}
    for($i=0;$i<$gold;$i++) {
      $this->cards[] = new bibliosGoldCard($root, 1);
      $this->cards[] = new bibliosGoldCard($root, 2);
      $this->cards[] = new bibliosGoldCard($root, 3);
    }
    for($i=0;$i<2;$i++) {
      $this->cards[] = new bibliosChurchCard($root, 1, 'up');
      $this->cards[] = new bibliosChurchCard($root, 1, 'down');
      $this->cards[] = new bibliosChurchCard($root, 2, 'up');
      $this->cards[] = new bibliosChurchCard($root, 2, 'down');
    }
    $this->cards[] = new bibliosChurchCard($root, 1, 'up or down');
    $this->deck = $this->cards;
    for($i=0;$i<$discard;$i++) {
      $card = $this->draw();
      $this->discard($card);
    }
  }
}
class bibliosAuctionDeck extends deck {
  function __construct($root) {
    $this->r = $root;
  }
  function addCard($card) {
    $this->cards[] = $card;
    $this->deck = $this->cards;
  }
}
?>
