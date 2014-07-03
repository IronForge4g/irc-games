<?php
class hcard {
  var $r;
  var $name;
  var $cardText;
  var $targets;
  var $type;
}
class hcard0 extends hcard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Aid';
    $this->cardText = 'Hunter heal 1 damage, if you have none take 1 damage.';
    $this->targets = array('Hunter');
    $this->type = 'Hermit';
  }
  function action($target, $args) {
    if($target->damage > 0) {
      $target->heal();
    } else {
      $target->damage();
    }
  }
}
class hcard1 extends hcard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Anger';
    $this->cardText = 'Hunter or Shadow - give 1 equipment to current player or take 1 damage.';
    $this->targets = array('Hunter', 'Shadow');
    $this->type = 'Hermit';
  }
  function action($target, $args) {
    if(count($args) == 1) {
      $e = strtoupper($args[0]);
      if(isset($target->equipment[$e])) {
        $this->r->mChan($target->nick." gives their ".$target->equipment[$e]->name." to {$this->r->currentPlayer->nick}.");
        $this->r->currentPlayer->equipment[] = $target->equipment[$e];
        unset($target->equipment[$e]);
        $target->equipment();
        $this->r->currentPlayer->equipment();
        return;
      }
    }
    $target->damage();
  }
}
class hcard2 extends hcard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Anger';
    $this->cardText = 'Hunter or Shadow - give 1 equipment to current player or take 1 damage.';
    $this->targets = array('Hunter', 'Shadow');
    $this->type = 'Hermit';
  }
  function action($target, $args) {
    if(count($args) == 1) {
      $e = strtoupper($args[0]);
      if(isset($target->equipment[$e])) {
        $this->r->mChan($target->nick." gives their ".$target->equipment[$e]->name." to {$this->r->currentPlayer->nick}.");
        $this->r->currentPlayer->equipment[] = $target->equipment[$e];
        unset($target->equipment[$e]);
        $target->equipment();
        $this->r->currentPlayer->equipment();
        return;
      }
    }
    $target->damage();
  }
}
class hcard3 extends hcard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Blackmail';
    $this->cardText = 'Hunter or Neutral - give 1 equipment to current player or take 1 damage.';
    $this->targets = array('Hunter', 'Neutral');
    $this->type = 'Hermit';
  }
  function action($target, $args) {
    if(count($args) == 1) {
      $e = strtoupper($args[0]);
      if(isset($target->equipment[$e])) {
        $this->r->mChan($target->nick." gives their ".$target->equipment[$e]->name." to {$this->r->currentPlayer->nick}.");
        $this->r->currentPlayer->equipment[] = $target->equipment[$e];
        unset($target->equipment[$e]);
        $target->equipment();
        $this->r->currentPlayer->equipment();
        return;
      }
    }
    $target->damage();
  }
}
class hcard4 extends hcard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Blackmail';
    $this->cardText = 'Hunter or Neutral - give 1 equipment to current player or take 1 damage.';
    $this->targets = array('Hunter', 'Neutral');
    $this->type = 'Hermit';
  }
  function action($target, $args) {
    if(count($args) == 1) {
      $e = strtoupper($args[0]);
      if(isset($target->equipment[$e])) {
        $this->r->mChan($target->nick." gives their ".$target->equipment[$e]->name." to {$this->r->currentPlayer->nick}.");
        $this->r->currentPlayer->equipment[] = $target->equipment[$e];
        unset($target->equipment[$e]);
        $target->equipment();
        $this->r->currentPlayer->equipment();
        return;
      }
    }
    $target->damage();
  }
}
class hcard5 extends hcard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Bully';
    $this->cardText = 'HP less or equal 11 (A,B,C,E,U) take 1 damage.';
    $this->targets = array('Allie', 'Bob', 'Charles', 'Emi', 'Unknown');
    $this->type = 'Hermit';
  }
  function action($target, $args) {
    $target->damage();
  }
}
class hcard6 extends hcard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Exorcism';
    $this->cardText = 'Shadow - take 2 damage.';
    $this->targets = array('Shadow');
    $this->type = 'Hermit';
  }
  function action($target, $args) {
    $target->damage(2);
  }
}
class hcard7 extends hcard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Greed';
    $this->cardText = 'Neutral or Shadow - give 1 equipment to current player or take 1 damage.';
    $this->targets = array('Neutral', 'Shadow');
    $this->type = 'Hermit';
  }
  function action($target, $args) {
    if(count($args) == 1) {
      $e = strtoupper($args[0]);
      if(isset($target->equipment[$e])) {
        $this->r->mChan($target->nick." gives their ".$target->equipment[$e]->name." to {$this->r->currentPlayer->nick}.");
        $this->r->currentPlayer->equipment[] = $target->equipment[$e];
        unset($target->equipment[$e]);
        $target->equipment();
        $this->r->currentPlayer->equipment();
        return;
      }
    }
    $target->damage();
  }
}
class hcard8 extends hcard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Greed';
    $this->cardText = 'Neutral or Shadow - give 1 equipment to current player or take 1 damage.';
    $this->targets = array('Neutral', 'Shadow');
    $this->type = 'Hermit';
  }
  function action($target, $args) {
    if(count($args) == 1) {
      $e = strtoupper($args[0]);
      if(isset($target->equipment[$e])) {
        $this->r->mChan($target->nick." gives their ".$target->equipment[$e]->name." to {$this->r->currentPlayer->nick}.");
        $this->r->currentPlayer->equipment[] = $target->equipment[$e];
        unset($target->equipment[$e]);
        $target->equipment();
        $this->r->currentPlayer->equipment();
        return;
      }
    }
    $target->damage();
  }
}
class hcard9 extends hcard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Huddle';
    $this->cardText = 'Shadow heal 1 damage, if you have none take 1 damage.';
    $this->targets = array('Shadow');
    $this->type = 'Hermit';
  }
  function action($target, $args) {
    if($target->damage > 0) {
      $target->heal();
    } else {
      $target->damage();
    }
  }
}
class hcard10 extends hcard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Nuturance';
    $this->cardText = 'Neutral heal 1 damage, if you have none take 1 damage.';
    $this->targets = array('Neutral');
    $this->type = 'Hermit';
  }
  function action($target, $args) {
    if($target->damage > 0) {
      $target->heal();
    } else {
      $target->damage();
    }
  }
}
class hcard11 extends hcard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Prediction';
    $this->cardText = 'Show your character card to current player.';
    $this->targets = array('Hunter', 'Neutral', 'Shadow');
    $this->type = 'Hermit';
  }
  function action($target, $args) {
    $this->r->mChan($target->nick." shows their character card to {$this->r->currentPlayer->nick}.");
    $this->r->nUser($this->r->currentPlayer->nick, $target->nick." is {$target->character->name}.");
  }
}
class hcard12 extends hcard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Slap';
    $this->cardText = 'Hunter - take 1 damage.';
    $this->targets = array('Hunter');
    $this->type = 'Hermit';
  }
  function action($target, $args) {
    $target->damage();
  }
}
class hcard13 extends hcard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Slap';
    $this->cardText = 'Hunter - take 1 damage.';
    $this->targets = array('Hunter');
    $this->type = 'Hermit';
  }
  function action($target, $args) {
    $target->damage();
  }
}
class hcard14 extends hcard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Spell';
    $this->cardText = 'Shadow - take 1 damage.';
    $this->targets = array('Shadow');
    $this->type = 'Hermit';
  }
  function action($target, $args) {
    $target->damage();
  }
}
class hcard15 extends hcard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Tough Lesson';
    $this->cardText = 'HP greater or equal to 12 (D,F,G,V,W) take 2 damage.';
    $this->targets = array('Daniel', 'Franklin', 'George', 'Vampire', 'Werewolf');
    $this->type = 'Hermit';
  }
  function action($target, $args) {
    $target->damage(2);
  }
}
class hermitDeck extends deck {
  function __construct($root) {
    $this->r = $root;
    $this->cards = array();
    $this->deck = array();
    $this->discard = array();
    $cardId = 0;
    for($i=0;$i<16;$i++) {
      $card = 'hcard'.$i;
      $this->cards[$cardId] = new $card($root);
      $cardId++;
    }
    $this->deck = $this->cards;
  }
}
?>
