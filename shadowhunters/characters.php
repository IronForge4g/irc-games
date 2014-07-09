<?php
class char {
  var $r;
  var $name;
  var $team;
  var $action;
  var $winCondition;
  var $life;
  var $player;
}
class nchar0 extends char {
  var $actionUsed;
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Allie';
    $this->team = 'Neutral';
    $this->action = 'Fully heal your damage. (Only once per game.)';
    $this->winCondition = 'You\'re not dead when the game is over.';
    $this->life = 8;
    $this->player = null;
    $this->actionUsed = false;
  }
  function win() {
    return false;
  }
  function action($from, $args) {
    if($this->actionUsed) {
      $this->r->mChan("$from: You have already used your action this game.");
      return;
    }
    if(!($this->player->revealed)) {
      $this->player->reveal();
    }
    $this->actionUsed = true;
    $this->player->damage = 0;
    $this->r->mChan("$from fully heals themselves.");
  }
}
class nchar1 extends char {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Bob';
    $this->team = 'Neutral';
    $this->action = 'If your attack kills a character, you take all the Equipment cards that character had.';
    $this->winCondition = 'You have 4 or more Equipment cards.';
    $this->life = 10;
    $this->player = null;
  }
  function win() {
    if(count($this->player->equipment) > 3) return true;
    return false;
  }
  function action($from, $args) {
    if(!($this->player->revealed)) {
      $this->r->mChan("$from: You must !reveal first.");
      return;
    }
    $this->r->mChan("$from: Your action automatically takes effect once revealed.");
  }
}
class nchar2 extends char {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Charles';
    $this->team = 'Neutral';
    $this->action = 'After you attack, you may give yourself 2 points of damage to attack the same character again.';
    $this->winCondition = 'At the time you kill another character, the total number of dead characters is 3 or more.';
    $this->life = 11;
    $this->player = null;
  }
  function win() {
    return false;
  }
  function action($from, $args) {
    if(!($this->player->revealed)) {
      $this->r->mChan("$from: You must !reveal first.");
      return;
    }
    $this->r->mChan("$from: Your action is handled after an attack.");
  }
}
class nchar3 extends char {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Daniel';
    $this->team = 'Neutral';
    $this->action = 'As soon as another player dies, you must reveal your identity.';
    $this->winCondition = 'You are the first character to die OR all the Shadow characters are dead and you are not.';
    $this->life = 13;
    $this->player = null;
  }
  function win() {
    $salive = 0;
    $pdead = 0;
    foreach($this->r->players as $nick => $player) {
      if($player == $this->player) continue;
      if($player->character->team == 'Shadow' && $player->alive) $salive++;
      if(!($player->alive)) $pdead++;
    }
    if(!($this->player->alive) && $pdead == 0) return true;
    if($salive == 0 && $this->player->alive) return true;
    return false;
  }
  function action($from, $args) {
    if(!($this->player->revealed)) {
      $this->r->mChan("$from: You must !reveal first.");
      return;
    }
    $this->r->mChan("$from: Your action is handled automatically.");
  }
}
class hchar0 extends char {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Emi';
    $this->team = 'Hunter';
    $this->action = 'When you move, you can roll dice as normal or move to an adjacent Area Card.';
    $this->winCondition = 'All the Shadow characters are dead.';
    $this->life = 10;
    $this->player = null;
  }
  function win() {
    foreach($this->r->players as $nick => $player) {
      if($player->character->team == 'Shadow' && $player->alive) return false;
    }
    return true;
  }
  function action($from, $args) {
    if(!($this->player->revealed)) {
      $this->r->mChan("$from: You must !reveal first.");
      return;
    }
    if(!($this->r->checkCurrentPlayer($from, 'Emi Action'))) return;
    if(!($this->r->checkArgs($from, $args, 1))) return;
    if(!($this->r->currentPhase != $this->r->phases['move'])) {
      $this->r->mChan("$from: Your action is only available before you roll for the move phase.");
      return;
    } 
    if(!($this->r->currentPhase->locations != null)) {
      $this->r->mChan("$from: Your action is only available before you roll for the move phase.");
      return;
    }
    $direction = strtolower($args[0]{0});
    $block = $this->r->currentPlayer->location->block;
    $side = $this->r->currentPlayer->location->side;
    $newLocation = null;
    if($direction == 'l') {
      if($side == 0) {
        $block--;
        if($block < 0) $block = 2;
        $side = 1;
      } else {
        $side--;
      }
      $newLocation = $this->r->blocks[$block][$side];
    } else if ($direction == 'r') {
      if($side == 1) {
        $block++;
        if($block > 2) $block = 0;
        $side = 0;
      } else {
        $side++;
      }
      $newLocation = $this->r->blocks[$block][$side];
    }
    if($newLocation == null) {
      $this->r->mChan("$from: Please specify to move left or right.");
      return;
    }
    if($this->r->currentPlayer->location != null) {
      unset($this->r->currentPlayer->location->players[$from]);
    }
    $this->r->currentPlayer->location = $newLocation;
    $newLocation->players[$from] = $this->r->currentPlayer;
    $this->r->setPhase($newLocation->phase);
  }
}
class hchar1 extends char {
  var $actionUsed;
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Franklin';
    $this->team = 'Hunter';
    $this->action = 'At the start of your turn, you can pick any player and give him/her damage equal to the roll of a 6-sided die.';
    $this->winCondition = 'All the Shadow characters are dead.';
    $this->life = 12;
    $this->player = null;
    $this->actionUsed = false;
  }
  function win() {
    foreach($this->r->players as $nick => $player) {
      if($player->character->team == 'Shadow' && $player->alive) return false;
    }
    return true;
  }
  function action($from, $args) {
    if(!($this->player->revealed)) {
      $this->r->mChan("$from: You must !reveal first.");
      return;
    }
    if(!($this->r->checkCurrentPlayer($from, 'Franklin Action'))) return;
    if($this->actionUsed) {
      $this->r->mChan("$from: You have already used your action this game.");
      return;
    }
    if(!($this->r->checkArgs($from, $args, 1))) return;
    if(!($this->r->currentPhase != $this->r->phases['move'])) {
      $this->r->mChan("$from: Your action is only available before you roll for the move phase.");
      return;
    } 
    if(!($this->r->currentPhase->locations != null)) {
      $this->r->mChan("$from: Your action is only available before you roll for the move phase.");
      return;
    }
    if(!($this->r->validTarget($args[0]))) {
      $this->r->mChan("$from: Please specify a valid target.");
      return;
    }
    $d6 = mt_rand(1,6);
    $this->r->mChan("$from casts lightning on {$args[0]} doing $d6 damage.");
    $this->r->players[$args[0]]->damage($d6);
    $this->actionUsed = true;
  }
}
class hchar2 extends char {
  var $actionUsed;
  function __construct($root) {
    $this->r = $root;
    $this->name = 'George';
    $this->team = 'Hunter';
    $this->action = 'At the start of your turn, you can pick any player and give him/her damage equal to the roll of a 4-sided die.';
    $this->winCondition = 'All the Shadow characters are dead.';
    $this->life = 14;
    $this->player = null;
    $this->actionUsed = false;
  }
  function win() {
    foreach($this->r->players as $nick => $player) {
      if($player->character->team == 'Shadow' && $player->alive) return false;
    }
    return true;
  }
  function action($from, $args) {
    if(!($this->player->revealed)) {
      $this->r->mChan("$from: You must !reveal first.");
      return;
    }
    if(!($this->r->checkCurrentPlayer($from, 'George Action'))) return;
    if($this->actionUsed) {
      $this->r->mChan("$from: You have already used your action this game.");
      return;
    }
    if(!($this->r->checkArgs($from, $args, 1))) return;
    if(!($this->r->phase != $this->r->phases['move'])) {
      $this->r->mChan("$from: Your action is only available before you roll for the move phase.");
      return;
    } 
    if(!($this->r->phase->locations != null)) {
      $this->r->mChan("$from: Your action is only available before you roll for the move phase.");
      return;
    }
    if(!($this->r->validTarget($args[0]))) {
      $this->r->mChan("$from: Please specify a valid target.");
      return;
    }
    $d4 = mt_rand(1,4);
    $this->r->mChan("$from casts demolish on {$args[0]} doing $d4 damage.");
    $this->r->players[$args[0]]->damage($d4);
    $this->actionUsed = true;
  }
}
class schar0 extends char {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Unknown';
    $this->team = 'Shadow';
    $this->action = 'You may lie when given a Hermit card. You don\'t have to !reveal your identity to do this.';
    $this->winCondition = 'All the Hunter characters are dead, OR 3 Neutral characters are dead.';
    $this->life = 11;
    $this->player = null;
  }
  function win() {
    $halive = 0;
    $ndead = 0;
    foreach($this->r->players as $nick => $player) {
      if($player->character->team == 'Hunter' && $player->alive) $halive++;
      if($player->character->team == 'Neutral' && !($player->alive)) $ndead++;
    }
    if($ndead >= 3) return true;
    if($halive == 0) return true;
    return false;
  }
  function action($from, $args) {
    if(!($this->player->revealed)) {
      $this->r->mChan("$from: You must !reveal first.");
      return;
    }
    $this->r->mChan("$from: Your action is handled elsewhere.");
  }
}
class schar1 extends char {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Vampire';
    $this->team = 'Shadow';
    $this->action = 'If you attack a player and inflict damage, you heal 2 points of your own damage.';
    $this->winCondition = 'All the Hunter characters are dead, OR 3 Neutral characters are dead.';
    $this->life = 13;
    $this->player = null;
  }
  function win() {
    $halive = 0;
    $ndead = 0;
    foreach($this->r->players as $nick => $player) {
      if($player->character->team == 'Hunter' && $player->alive) $halive++;
      if($player->character->team == 'Neutral' && !($player->alive)) $ndead++;
    }
    if($ndead >= 3) return true;
    if($halive == 0) return true;
    return false;
  }
  function action($from, $args) {
    if(!($this->player->revealed)) {
      $this->r->mChan("$from: You must !reveal first.");
      return;
    }
    $this->r->mChan("$from: Your action is handled elsewhere.");
  }
}
class schar2 extends char {
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Werewolf';
    $this->team = 'Shadow';
    $this->action = 'After you are attacked, you can attack that character immediately.';
    $this->winCondition = 'All the Hunter characters are dead, OR 3 Neutral characters are dead.';
    $this->life = 14;
    $this->player = null;
  }
  function win() {
    $halive = 0;
    $ndead = 0;
    foreach($this->r->players as $nick => $player) {
      if($player->character->team == 'Hunter' && $player->alive) $halive++;
      if($player->character->team == 'Neutral' && !($player->alive)) $ndead++;
    }
    if($ndead >= 3) return true;
    if($halive == 0) return true;
    return false;
  }
  function action($from, $args) {
    if(!($this->player->revealed)) {
      $this->r->mChan("$from: You must !reveal first.");
      return;
    }
  }
}
?>
