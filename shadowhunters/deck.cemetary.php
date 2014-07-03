<?php
class cecard {
  var $r;
  var $name;
  var $cardText;
  var $type;
  var $cmdText;
}
class cecard0 extends cecard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Banana Peel';
    $this->cardText = 'Give one of your equipment cards to another player. If you have none, take 1 point of damage.';
    $this->cmdText = '"!card player equipment" to give, otherwise !card to take the damage.';
    $this->type = 'Cemetary';
  }
  function auto($target, $args) {
    if(count($target->equipment) == 0) {
      $target->damage();
      return true;
    }
    return false;
  }
  function action($target, $args) {
    if(count($args) == 2) {
      if($this->r->validTarget($args[0]) && isset($target->equipment[$args[1]])) {
        $player = $this->r->players[$args[0]];
        $equipment = $args[1];
      }
      else if($this->r->validTarget($args[1]) && isset($target->equipment[$args[0]])) {
        $player = $this->r->players[$args[1]];
        $equipment = $args[0];
      }
      else {
        $this->r->mChan($target->nick.": Please select a valid target and piece of equipment.");
        return false;
      }
      $this->r->mChan($target->nick." gives their ".$target->equipment[$e]->name." to {$player->nick}.");
      $player->equipment[] = $target->equipment[$equipment];
      unset($target->equipment[$equipment]);
      $player->equipment();
      $target->equipment();
      return true;
    }
  }
}
class cecard1 extends cecard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Bloodthirsty Spider';
    $this->cardText = 'Give 2 points of damage to any players character and take 2 points of damage yourself.';
    $this->cmdText = '"!card player" to choose.';
    $this->type = 'Cemetary';
  }
  function action($target, $args) {
    if(count($args) != 1) {
      $this->r->mChan($target->nick.": Please specify a valid player.");
      return false;
    }
    $player = $args[0];
    if(!($this->r->validTarget($player))) {
      $this->r->mChan($target->nick.": Please specify a valid player.");
      return false;
    }
    $this->r->players[$player]->damage(2, 'Bloodthirsty Spider');
    $target->damage(2);
    return true;
  }
}
class cecard2 extends cecard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Butcher Knife';
    $this->cardText = 'If your attack is successful, you give 1 extra point of damage.';
    $this->type = 'Cemetary';
  }
}
class cecard3 extends cecard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Chainsaw';
    $this->cardText = 'If your attack is successful, you give 1 extra point of damage.';
    $this->type = 'Cemetary';
  }
}
class cecard4 extends cecard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Diabolic Ritual';
    $this->cardText = 'If you are a Shadow you may reveal your identity and fully heal your damage.';
    $this->type = 'Cemetary';
  }
  function action($target, $args) {
    if(count($args) > 0) {
      if($args[0] == 'reveal') {
        if($target->character->type == 'Shadow') {
          $target->reveal();
          $this->r->mChan($target->nick." has revealed, lowering their damage to 0.");
          $target->damage = 0;
        }
      }
    }
    return true;
  }
}
class cecard5 extends cecard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Dynamite';
    $this->cardText = 'Roll both dice and give 3 points of damage to all characters in that area. On a 7 nothing happens.';
    $this->type = 'Cemetary';
  }
  function auto($target, $args) {
    $d10 = mt_rand(1,4) + mt_rand(1,6);
    if($d10 == 7) {
      $this->r->mChan("A 7 was rolled, no damage is done.");
      return true;
    }
    $this->r->mChan("$d10 was rolled. Dealing 3 damage to all players in Block {$player->location->block}.");
    foreach($target->location->players as $nick => $player) $player->damage(3, 'Dynamite');
    foreach($target->location->neighbour->players as $nick => $player) $player->damage(3, 'Dynamite');
    return true;
  }
}
class cecard6 extends cecard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Handgun';
    $this->cardText = 'All ranges but yours become your attack range (you cannot attack in your normal range.)';
    $this->type = 'Cemetary';
  }
}
class cecard7 extends cecard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Machine Gun';
    $this->cardText = 'Your attack will affect all characters in your attack range. Apply the same damage to all.';
    $this->type = 'Cemetary';
  }
}
class cecard8 extends cecard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Masamune';
    $this->cardText = 'You MUSt attack and you deal D4 damage. You can\'t fail.';
    $this->type = 'Cemetary';
  }
}
class cecard9 extends cecard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Moody Goblin';
    $this->cardText = 'Steal an equipment card from any players character.';
    $this->cmdText = '!card player equipment';
    $this->type = 'Cemetary';
  }
  function auto($target, $args) {
    $count = 0;
    foreach($this->r->players as $nick => $player) {
      $count += count($player->equipment);
      if($count > 0) break;
    }
    if($count == 0) {
      $this->r->mChan("Nobody has any equipment. Please disregard.");
      return true;
    }
    return false;
  }
  function action($target, $args) {
    if(count($args) != 2) {
      $this->r->mChan($target->nick.": Please select a valid target and piece of equipment.");
      return false;
    }
    $player = null;
    $equipment = null;
    if($this->r->validTarget($args[0])) {
      if(isset($this->r->players[$args[0]]->equipment[$args[1]])) {
        $player = $this->r->players[$args[0]];
        $equipment = $args[1];
      }
    }
    else if($this->r->validTarget($args[1])) {
      if(isset($this->r->players[$args[1]]->equipment[$args[0]])) {
        $player = $this->r->players[$args[1]];
        $equipment = $args[0];
      }
    }
    if($player == null) {
      $this->r->mChan($target->nick.": Please select a valid target and piece of equipment.");
      return false;
    }
    $this->r->mChan($target->nick." takes ".$player->equipment[$e]->name." from {$player->nick}.");
    $target->equipment[] = $player->equipment[$equipment];
    unset($player->equipment[$equipment]);
    $target->equipment();
    $player->equipment();
    return true;
  }
}
class cecard10 extends cecard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Moody Goblin';
    $this->cardText = 'Steal an equipment card from any players character.';
    $this->cmdText = '!card player equipment';
    $this->type = 'Cemetary';
  }
  function auto($target, $args) {
    $count = 0;
    foreach($this->r->players as $nick => $player) {
      $count += count($player->equipment);
      if($count > 0) break;
    }
    if($count == 0) {
      $this->r->mChan("Nobody has any equipment. Please disregard.");
      return true;
    }
    return false;
  }
  function action($target, $args) {
    if(count($args) != 2) {
      $this->r->mChan($target->nick.": Please select a valid target and piece of equipment.");
      return false;
    }
    $player = null;
    $equipment = null;
    if($this->r->validTarget($args[0])) {
      if(isset($this->r->players[$args[0]]->equipment[$args[1]])) {
        $player = $this->r->players[$args[0]];
        $equipment = $args[1];
      }
    }
    else if($this->r->validTarget($args[1])) {
      if(isset($this->r->players[$args[1]]->equipment[$args[0]])) {
        $player = $this->r->players[$args[1]];
        $equipment = $args[0];
      }
    }
    if($player == null) {
      $this->r->mChan($target->nick.": Please select a valid target and piece of equipment.");
      return false;
    }
    $this->r->mChan($target->nick." takes ".$player->equipment[$e]->name." from {$player->nick}.");
    $target->equipment[] = $player->equipment[$equipment];
    unset($player->equipment[$equipment]);
    $target->equipment();
    $player->equipment();
    return true;
  }
}
class cecard11 extends cecard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Rusted Broad Axe';
    $this->cardText = 'If your attack is successful, you give 1 extra point of damage.';
    $this->type = 'Cemetary';
  }
}
class cecard12 extends cecard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Spiratual Doll';
    $this->cardText = 'Pick a player and roll D6 1-4 give their character 3 points of Damage. 6-7 take 3 points of damage.';
    $this->cmdText = '"!card player" to choose.';
    $this->type = 'Cemetary';
  }
  function action($target, $args) {
    if(count($args) != 1) {
      $this->r->mChan($target->nick.": Please specify a valid player.");
      return false;
    }
    $player = $args[0];
    if(!($this->r->validTarget($player))) {
      $this->r->mChan($target->nick.": Please specify a valid player.");
      return false;
    }
    $d6 = mt_rand(1,6);
    $this->r->mChan($target->nick." rolled a $d6.");
    if($d6 < 5) 
      $this->r->players[$player]->damage(3);
    else 
      $target->damage(3);
    return true;
  }
}
class cecard13 extends cecard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Vampire Bat';
    $this->cardText = 'Give 2 points of damage to any players character and heal 1 point of your own.';
    $this->cmdText = '"!card player" to choose.';
    $this->type = 'Cemetary';
  }
  function action($target, $args) {
    if(count($args) != 1) {
      $this->r->mChan($target->nick.": Please specify a valid player.");
      return false;
    }
    $player = $args[0];
    if(!($this->r->validTarget($player))) {
      $this->r->mChan($target->nick.": Please specify a valid player.");
      return false;
    }
    $this->r->players[$player]->damage(2, 'Vampire Bat');
    $target->heal(1);
    return true;
  }
}
class cecard14 extends cecard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Vampire Bat';
    $this->cardText = 'Give 2 points of damage to any players character and heal 1 point of your own.';
    $this->cmdText = '"!card player" to choose.';
    $this->type = 'Cemetary';
  }
  function action($target, $args) {
    if(count($args) != 1) {
      $this->r->mChan($target->nick.": Please specify a valid player.");
      return false;
    }
    $player = $args[0];
    if(!($this->r->validTarget($player))) {
      $this->r->mChan($target->nick.": Please specify a valid player.");
      return false;
    }
    $this->r->players[$player]->damage(2, 'Vampire Bat');
    $target->heal(1);
    return true;
  }
}
class cecard15 extends cecard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Vampire Bat';
    $this->cardText = 'Give 2 points of damage to any players character and heal 1 point of your own.';
    $this->cmdText = '"!card player" to choose.';
    $this->type = 'Cemetary';
  }
  function action($target, $args) {
    if(count($args) != 1) {
      $this->r->mChan($target->nick.": Please specify a valid player.");
      return false;
    }
    $player = $args[0];
    if(!($this->r->validTarget($player))) {
      $this->r->mChan($target->nick.": Please specify a valid player.");
      return false;
    }
    $this->r->players[$player]->damage(2, 'Vampire Bat');
    $target->heal(1);
    return true;
  }
}
class cemetaryDeck extends deck {
  function __construct($root) {
    $this->r = $root;
    $this->cards = array();
    $this->deck = array();
    $this->discard = array();
    $cardId = 0;
    for($i=0;$i<16;$i++) {
      $card = 'cecard'.$i;
      $this->cards[$cardId] = new $card($root);
      $cardId++;
    }
    $this->deck = $this->cards;
  }
}
?>
