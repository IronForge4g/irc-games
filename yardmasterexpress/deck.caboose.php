<?php
class caboose {
  var $r;
  var $points;
  var $title;
  var $text;
}
class caboose0 extends caboose {
  function __construct($root) {
    $this->r = $root;
    $this->points = 5;
    $this->title = 'Executive';
    $this->text = 'Finish with the fewest types of cargo.';
  }
  function win() {
    $fewest = 6;
    $winners = array();
    foreach($this->r->players as $nick => $player) {
      $cargo = array();
      foreach($player->train as $car) {
        if(!(isset($cargo[$car->leftColor]))) $cargo[$car->leftColor] = 0;
        if(!(isset($cargo[$car->rightColor]))) $cargo[$car->rightColor] = 0;
        $cargo[$car->leftColor]++;
        $cargo[$car->rightColor]++;
      }
      $cargoTypes = count($cargo);
      if(isset($cargo['Wild'])) $cargoTypes--;
      if($cargoTypes < $fewest) {
        $fewest = $cargoTypes;
        $winners = array($nick);
      }
      else if($cargoTypes == $fewest) {
        $winners[] = $nick;
      }
    }
    return $winners;
  }
}
class caboose1 extends caboose {
  function __construct($root) {
    $this->r = $root;
    $this->points = 5;
    $this->title = 'Two-Timer';
    $this->text = 'Finish your train with two types of Cargo, each consisting of three railcars. Railcars do not have to be connected.';
  }
  function win() {
    $winners = array();
    foreach($this->r->players as $nick => $player) {
      $cargo = array();
      foreach($player->train as $car) {
        if(!(isset($cargo[$car->leftColor]))) $cargo[$car->leftColor] = 0;
        if(!(isset($cargo[$car->rightColor]))) $cargo[$car->rightColor] = 0;
        $cargo[$car->leftColor]++;
        $cargo[$car->rightColor]++;
      }
      $threeCargo = 0;
      foreach($cargo as $color => $cargoCount) {
        if($color == 'Wild') continue;
        if($cargoCount == 3) $threeCargo++;
      }
      if($threeCargo >= 2) $winners[] = $nick;
    }
    return $winners;
  }
}
class caboose2 extends caboose {
  function __construct($root) {
    $this->r = $root;
    $this->points = 5;
    $this->title = 'Diversified';
    $this->text = 'Finish with the most Cargo runs each with at least two Railcars.';
  }
  function win() {
    $most = 1;
    $winners = array();
    foreach($this->r->players as $nick => $player) {
      $last = null;
      $run = 0;
      $runs = 0;
      foreach($player->train as $car) {
        if($car->leftColor == $last) $run++;
        else {
          if($run >= 2 && $last != 'Wild') $runs++;
          $last = $car->leftColor;
          $run = 1;
        }
        if($car->rightColor == $last) $run++;
        else {
          if($run >= 2 && $last != 'Wild') $runs++;
          $last = $car->rightColor;
          $run = 1;
        }
      }
      if($run >= 2 && $last != 'Wild') $runs++;
      if($runs > $most) {
        $most = $runs;
        $winners = array($nick);
      }
      else if($runs == $most) {
        $winners[] = $nick;
      }
    }
    return $winners;
  }
}
class caboose3 extends caboose {
  function __construct($root) {
    $this->r = $root;
    $this->points = 5;
    $this->title = 'Short Line';
    $this->text = 'Finish with the most Railcars of one type of Cargo.';
  }
  function win() {
    $most = 1;
    $winners = array();
    foreach($this->r->players as $nick => $player) {
      $cargo = array();
      foreach($player->train as $car) {
        if(!(isset($cargo[$car->leftColor]))) $cargo[$car->leftColor] = 0;
        if(!(isset($cargo[$car->rightColor]))) $cargo[$car->rightColor] = 0;
        $cargo[$car->leftColor]++;
        $cargo[$car->rightColor]++;
      }
      foreach($cargo as $color => $count) {
        if($color == 'Wild') continue;
        if($count > $most) {
          $most = $count;
          $winners = array($nick);
        }
        else if($count == $most) {
          $winners[] = $nick;
        }
      }
    }
    return $winners;
  }
}
class caboose4 extends caboose {
  function __construct($root) {
    $this->r = $root;
    $this->points = 6;
    $this->title = 'Lightweight';
    $this->text = 'Finish with no 4/4 Railcar Cards on your Train.';
  }
  function win() {
    $winners = array();
    foreach($this->r->players as $nick => $player) {
      $found = false;
      foreach($player->train as $car) {
        if($car->leftNumber == 4) $found = true;
        else if($car->rightNumber == 4) $found = true;
      }
      if(!($found)) $winners[] = $nick;
    }
    return $winners;
  }
}
class caboose5 extends caboose {
  function __construct($root) {
    $this->r = $root;
    $this->points = 3;
    $this->title = 'Specialized';
    $this->text = 'Finish your Train with one type of Cargo with exactly five Railcars.';
  }
  function win() {
    $winners = array();
    foreach($this->r->players as $nick => $player) {
      $cargo = array();
      foreach($player->train as $car) {
        if(!(isset($cargo[$car->leftColor]))) $cargo[$car->leftColor] = 0;
        if(!(isset($cargo[$car->rightColor]))) $cargo[$car->rightColor] = 0;
        $cargo[$car->leftColor]++;
        $cargo[$car->rightColor]++;
      }
      $fiveCargo = 0;
      foreach($cargo as $color => $cargoCount) {
        if($color == 'Wild') continue;
        if($cargoCount == 5) $fiveCargo++;
      }
      if($fiveCargo >= 1) $winners[] = $nick;
    }
    return $winners;
  }
}
class caboose6 extends caboose {
  function __construct($root) {
    $this->r = $root;
    $this->points = 7;
    $this->title = 'The Little Engine';
    $this->text = 'Finish with the lowest valued Train.';
  }
  function win() {
    $winners = array();
    $scores = array();
    $runs = array();
    foreach($this->r->players as $nick => $player) {
      $last = null;
      $run = 0;
      $bestRun = 0;
      $score = 0;
      foreach($player->train as $car) {
        $score += $car->leftNumber;
        $score += $car->rightNumber;
        if($car->leftColor == $last) $run++;
        else {
          if($run > $bestRun && $last != 'Wild') $bestRun = $run;
          $last = $car->leftColor;
          $run = 1;
        }
        if($car->rightColor == $last) $run++;
        else {
          if($run > $bestRun && $last != 'Wild') $bestRun = $run;
          $last = $car->rightColor;
          $run = 1;
        }
      }
      if($run > $bestRun && $last != 'Wild') $bestRun = $run;
      $scores[$nick] = $score;
      $runs[$nick] = $bestRun;
    }
    $longestRun = -1;
    $longestRunners = array();
    foreach($runs as $nick => $bestRun) {
      if($bestRun > $longestRun) {
        $longestRun = $bestRun;
        $longestRunners = array($nick);
      }
      else if($bestRun == $longestRun) {
        $longestRunners[] = $nick;
      }
    }
    foreach($longestRunners as $nick) $scores[$nick] += $longestRun;
    $lowestScore = 200;
    foreach($scores as $nick => $score) {
      if($score < $lowestScore) {
        $lowestScore = $score;
        $winners = array($nick);
      }
      else if ($score == $lowestScore) {
        $winners[] = $nick;
      }
    }
    return $winners;
  }
}
class caboose7 extends caboose {
  function __construct($root) {
    $this->r = $root;
    $this->points = 8;
    $this->title = 'Efficient';
    $this->text = 'Finish with no mixed Cargo Cards on your Train.';
  }
  function win() {
    $winners = array();
    foreach($this->r->players as $nick => $player) {
      $mixed = false;
      foreach($player->train as $car) {
        if($car->leftColor != $car->rightColor) $mixed = true;
      }
      if(!($mixed)) $winners[] = $nick;
    }
    return $winners;
  }
}
class caboose8 extends caboose {
  function __construct($root) {
    $this->r = $root;
    $this->points = 6;
    $this->title = 'Sequence #1';
    $this->text = 'Finish your Train with this sequence of values: 2, 2, 2, 2, 2.';
  }
  function win() {
    $winners = array();
    $mixed = false;
    foreach($this->r->players as $nick => $player) {
      $sequence = 'seq';
      foreach($player->train as $car) {
        $sequence .= $car->leftNumber.$car->rightNumber;
      }
      if(strpos($sequence, '22222') !== false) $winners[] = $nick;
    }
    return $winners;
  }
}
class caboose9 extends caboose {
  function __construct($root) {
    $this->r = $root;
    $this->points = 6;
    $this->title = 'Sequence #2';
    $this->text = 'Finish your Train with this sequence of values: 3, 3, 3, 3, 3.';
  }
  function win() {
    $winners = array();
    $mixed = false;
    foreach($this->r->players as $nick => $player) {
      $sequence = 'seq';
      foreach($player->train as $car) {
        $sequence .= $car->leftNumber.$car->rightNumber;
      }
      if(strpos($sequence, '33333') !== false) $winners[] = $nick;
    }
    return $winners;
  }
}
class caboose10 extends caboose {
  function __construct($root) {
    $this->r = $root;
    $this->points = 6;
    $this->title = 'Sequence #3';
    $this->text = 'Finish your Train with this sequence of values: 2, 2, 2, 3, 3.';
  }
  function win() {
    $winners = array();
    $mixed = false;
    foreach($this->r->players as $nick => $player) {
      $sequence = 'seq';
      foreach($player->train as $car) {
        $sequence .= $car->leftNumber.$car->rightNumber;
      }
      if(strpos($sequence, '22233') !== false) $winners[] = $nick;
    }
    return $winners;
  }
}
class caboose11 extends caboose {
  function __construct($root) {
    $this->r = $root;
    $this->points = 6;
    $this->title = 'Sequence #4';
    $this->text = 'Finish your Train with this sequence of values: 2, 2, 2, 2, 3.';
  }
  function win() {
    $winners = array();
    $mixed = false;
    foreach($this->r->players as $nick => $player) {
      $sequence = 'seq';
      foreach($player->train as $car) {
        $sequence .= $car->leftNumber.$car->rightNumber;
      }
      if(strpos($sequence, '22223') !== false) $winners[] = $nick;
    }
    return $winners;
  }
}
class caboose12 extends caboose {
  function __construct($root) {
    $this->r = $root;
    $this->points = 6;
    $this->title = 'Sequence #5';
    $this->text = 'Finish your Train with this sequence of values: 3, 2, 2, 3, 3.';
  }
  function win() {
    $winners = array();
    $mixed = false;
    foreach($this->r->players as $nick => $player) {
      $sequence = 'seq';
      foreach($player->train as $car) {
        $sequence .= $car->leftNumber.$car->rightNumber;
      }
      if(strpos($sequence, '32233') !== false) $winners[] = $nick;
    }
    return $winners;
  }
}
class caboose13 extends caboose {
  function __construct($root) {
    $this->r = $root;
    $this->points = 4;
    $this->title = 'End of Line';
    $this->text = 'Finish your Train with the very last Railcar having a value of 3.';
  }
  function win() {
    $winners = array();
    foreach($this->r->players as $nick => $player) {
      $last = 0;
      foreach($player->train as $car) {
        $last = $car->rightNumber;
      }
      if($last == 3) $winners[] = $nick;
    }
    return $winners;
  }
}
class caboose14 extends caboose {
  function __construct($root) {
    $this->r = $root;
    $this->points = 7;
    $this->title = 'Buried Alive';
    $this->text = 'Finish your Train with the most Wild Cards.';
  }
  function win() {
    $most = 1;
    $winners = array();
    foreach($this->r->players as $nick => $player) {
      $cargo = array();
      foreach($player->train as $car) {
        if(!(isset($cargo[$car->leftColor]))) $cargo[$car->leftColor] = 0;
        if(!(isset($cargo[$car->rightColor]))) $cargo[$car->rightColor] = 0;
        $cargo[$car->leftColor]++;
        $cargo[$car->rightColor]++;
      }
      foreach($cargo as $color => $count) {
        if($color != 'Wild') continue;
        if($count > $most) {
          $most = $count;
          $winners = array($nick);
        }
        else if($count == $most) {
          $winners[] = $nick;
        }
      }
    }
    return $winners;
  }
}
class caboose15 extends caboose {
  function __construct($root) {
    $this->r = $root;
    $this->points = 5;
    $this->title = 'Inefficient';
    $this->text = 'Finish your Train with no Cargo Runs.';
  }
  function win() {
    $winners = array();
    foreach($this->r->players as $nick => $player) {
      $last = null;
      $run = 0;
      $runs = 0;
      foreach($player->train as $car) {
        if($car->leftColor == $last) $run++;
        else {
          if($run > 1 && $last != 'Wild') $runs++;
          $last = $car->leftColor;
          $run = 1;
        }
        if($car->rightColor == $last) $run++;
        else {
          if($run > 1 && $last != 'Wild') $runs++;
          $last = $car->rightColor;
          $run = 1;
        }
      }
      if($run > 1 && $last != 'Wild') $runs++;
      if($runs == 0) {
        $winners[] = $nick;
      }
    }
    return $winners;
  }
}class cabooseDeck extends deck {
  function __construct($root, $purple = true) {
    $this->r = $root;
    $this->deck = array();
    $this->discard = array();
    for($i=0;$i<16;$i++) {
      $cardName = 'caboose'.$i;
      $this->cards[] = new $cardName($this->r);
    }
    $this->deck = $this->cards;
  }
}
?>
