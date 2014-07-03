<?php
class chcard {
  var $r;
  var $name;
  var $cardText;
  var $type;
  var $cmdText;
}
class chcard0 extends chcard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Advent';
    $this->cardText = 'If you are a Hunter, you may reveal your identity and fully heal your damage.';
    $this->cmdText = '"!card reveal" to reveal, otherwise !card.';
    $this->type = 'Church';
  }
  function action($target, $args) {
    if(count($args) > 0) {
      if($args[0] == 'reveal') {
        if($target->character->type == 'Hunter') {
          $target->reveal();
          $this->r->mChan($target->nick." has revealed, lowering their damage to 0.");
          $target->damage = 0;
        }
      }
    }
    return true;
  }
}
class chcard1 extends chcard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Blessing';
    $this->cardText = 'Pick a player character other than yourself and they heal D6 damage.';
    $this->cmdText = '"!card player" to choose.';
    $this->type = 'Church';
  }
  function action($target, $args) {
    if(count($args) != 1) {
      $this->mChan($target->nick.": Please specify a valid player.");
      return false;
    }
    $player = $args[0];
    if(($player == $target->nick) || (!($this->r->validTarget($player)))) {
      $this->mChan($target->nick.": Please specify a valid player.");
      return false;
    }
    $this->r->players[$player]->heal(mt_rand(1, 6));
    return true;
  }
}
class chcard2 extends chcard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Chocolate';
    $this->cardText = 'If you\'re Allie, Emi, or Unknown you may reveal your identity and fully heal your damage.';
    $this->cmdText = '"!card reveal" to reveal, otherwise !card.';
    $this->type = 'Church';
  }
  function action($target, $args) {
    if(count($args) > 0) {
      if($args[0] == 'reveal') {
        if($target->character->name == 'Allie' || $target->character->name == 'Emi' || $target->character->name == 'Unknown') {
          $target->reveal();
          $this->r->mChan($target->nick." has revealed, lowering their damage to 0.");
          $target->damage = 0;
        }
      }
    }
    return true;
  }
}
class chcard3 extends chcard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Concealed Knowledge';
    $this->cardText = 'When your turn is over, it will be your turn again.';
    $this->type = 'Church';
  }
  function auto($target, $args) {
    $target->freeTurn = true;
    return true;
  }
}
class chcard4 extends chcard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Disenchant Mirror';
    $this->cardText = 'If you are either Vampire or Werewolf you must reveal your identity.';
    $this->type = 'Church';
  }
  function auto($target, $args) {
    if($target->name == 'Vampire' || $target->name == 'Werewolf') $target->reveal();
    return true;
  }
}
class chcard5 extends chcard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'First Aid';
    $this->cardText = 'Set the damage marker of any players character to 7.';
    $this->cmdText = '"!card player" to choose.';
    $this->type = 'Church';
  }
  function action($target, $args) {
    if(count($args) != 1) {
      $this->mChan($target->nick.": Please specify a valid player.");
      return false;
    }
    $player = $args[0];
    if(!($this->r->validTarget($player))) {
      $this->mChan($target->nick.": Please specify a valid player.");
      return false;
    }
    $this->mChan($player." now has 7 damage.");
    $this->r->players[$player]->damage = 7;
    return true;
  }
}
class chcard6 extends chcard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Flare of Judgement';
    $this->cardText = 'All OTHER characters take 2 points of damage.';
    $this->type = 'Church';
  }
  function auto($target, $args) {
    foreach($this->r->players as $nick => $player) {
      if($nick == $target->nick) continue;
      $player->damage(2);
    }
    return true;
  }
}
class chcard7 extends chcard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Fortune Brooch';
    $this->cardText = 'You take no damage from the weird woods location. You may still heal your own damage.';
    $this->type = 'Church';
  }
}
class chcard8 extends chcard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Guardian Angel';
    $this->cardText = 'You take no damage from another characters attack until your next turn.';
    $this->type = 'Church';
  }
  function auto($target, $args) {
    $target->guardianAngel = true;
    return true;
  }
}
class chcard9 extends chcard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Holy Robe';
    $this->cardText = 'Your attacks do 1 less damage and damage you take from attacks is reduced by 1 point.';
    $this->type = 'Church';
  }
}
class chcard10 extends chcard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Holy Water of Healing';
    $this->cardText = 'Heal 2 points of your damage.';
    $this->type = 'Church';
  }
  function auto($target, $args) {
    $target->heal(2);
    return true;
  }
}
class chcard11 extends chcard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Holy Water of Healing';
    $this->cardText = 'Heal 2 points of your damage.';
    $this->type = 'Church';
  }
  function auto($target, $args) {
    $target->heal(2);
    return true;
  }
}
class chcard12 extends chcard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Mystic Compass';
    $this->cardText = 'You may roll twice for movement and choose which result to use.';
    $this->type = 'Church';
  }
}
class chcard13 extends chcard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Silver Rosary';
    $this->cardText = 'If your attack kills another character you get all of their equipment.';
    $this->type = 'Church';
  }
}
class chcard14 extends chcard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Spear of Longinus';
    $this->cardText = 'If you\'re a hunter and attack successful you may reveal. If you do or are already revealed you give 2 extra damage.';
    $this->type = 'Church';
  }
}
class chcard15 extends chcard {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Talisman';
    $this->cardText = 'You take no damage from the black cards Bloodthirsty Spider, Vampire Bat, or Dynamite.';
    $this->type = 'Church';
  }
}
class churchDeck extends deck {
  function __construct($root) {
    $this->r = $root;
    $this->cards = array();
    $this->deck = array();
    $this->discard = array();
    $cardId = 0;
    for($i=0;$i<16;$i++) {
      $card = 'chcard'.$i;
      $this->cards[$cardId] = new $card($root);
      $cardId++;
    }
    $this->deck = $this->cards;
  }
}
?>
