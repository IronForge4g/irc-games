<?php
class area {
  var $r;
  var $name;
  var $phase;
  var $numbers;
  var $block;
  var $side;
  var $neighbour;
  var $players;
  function display() {
    $playerCount = count($this->players);
    if($playerCount == 0) return implode('/', $this->numbers).'. '.$this->name.' (Nobody)';
    $players = array_keys($this->players);
    return implode('/', $this->numbers).'. '.$this->name.' ('.implode(', ', $players).')';
  }
}
class area0 extends area {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Hermits Cabin';
    $this->phase = 'hermit';
    $this->numbers = array(2, 3);
    $this->block = null;
    $this->side = null;
    $this->neighbour = null;
    $this->players = array();
  }
}
class area1 extends area {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Underworld Gate';
    $this->phase = 'underworld';
    $this->numbers = array(4, 5);
    $this->block = null;
    $this->side = null;
    $this->neighbour = null;
    $this->players = array();
  }
}
class area2 extends area {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Church';
    $this->phase = 'church';
    $this->numbers = array(6);
    $this->block = null;
    $this->side = null;
    $this->neighbour = null;
    $this->players = array();
  }
}
class area3 extends area {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Cemetary';
    $this->phase = 'cemetary';
    $this->numbers = array(8);
    $this->block = null;
    $this->side = null;
    $this->neighbour = null;
    $this->players = array();
  }
}
class area4 extends area {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Weird Woods';
    $this->phase = 'woods';
    $this->numbers = array(9);
    $this->block = null;
    $this->side = null;
    $this->neighbour = null;
    $this->players = array();
  }
}
class area5 extends area {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Erstwhile Altar';
    $this->phase = 'altar';
    $this->numbers = array(10);
    $this->block = null;
    $this->side = null;
    $this->neighbour = null;
    $this->players = array();
  }
}
?>
