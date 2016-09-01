<?php
class specObserver {
  var $r;
  var $name;
  var $player;
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Observer';
    $this->player = null;
  }
  function init() {
    $this->r->nUser($this->player->nick, "As the Observer, you have no actions to perform at night. Please announce you are !complete.");
    $this->player->actionTaken = true;
  }
}
class specInvestigator {
  var $r;
  var $name;
  var $player;
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Investigator';
    $this->player = null;
  }
  function init() {
    $this->r->nUser($this->player->nick, "As the Investigator, you may choose one player to !view their ID.");
  }
  function view($from, $args, $source) {
    if(!($this->r->checkArgs($from, $args, 1, 1, $source))) return;
    $tPlayer = $this->r->findPlayer($args[0]);
    if($tPlayer == null) {
      $this->r->nUser($from, "{$args[0]} is not a valid target. Please choose a valid target to !view.");
      return;
    }
    $this->r->nUser($from, "{$tPlayer->nick} is on the {$tPlayer->team} side. Please announce you are !complete.");
    $this->player->actionTaken = true;
  }
}
class specSignaler {
  var $r;
  var $name;
  var $player;
  var $valid;
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Signaler';
    $this->player = null;
    $this->valid = array();
  }
  function init() {
    if($this->player->initialTeam == 'Rebel' || $this->player->revealed) {
      $this->r->nUser($this->player->nick, "As a Rebel Signaler, please !tap {$this->player->left->nick} or !tap {$this->player->right->nick}.");
      $this->valid[] = $this->player->left;
      $this->valid[] = $this->player->right;
    }
    else {
      if(($this->player->left->initialTeam == 'Informant' && (!$this->player->left->revealed)) && ($this->player->right->initialTeam == 'Informant' && (!$this->player->right->revealed))) {
        $this->r->nUser($this->player->nick, "As an Informant Signaler, please !tap {$this->player->left->nick} or !tap {$this->player->right->nick}.");
        $this->valid[] = $this->player->left;
        $this->valid[] = $this->player->right;
      } else if($this->player->left->initialTeam == 'Informant' && !$this->player->left->revealed) {
        $this->r->nUser($this->player->nick, "As an Informant Signaler, you tap {$this->player->left->nick} on the shoulder. Please announce you are !complete.");
        $this->r->nUser($this->player->left->nick, "You feel a tap on your shoulder from {$this->player->nick}.");
        $this->player->actionTaken = true;
      } else if($this->player->right->initialTeam == 'Informant' && !$this->player->right->revealed) {
        $this->r->nUser($this->player->nick, "As an Informant Signaler, you tap {$this->player->right->nick} on the shoulder. Please announce you are !complete.");
        $this->r->nUser($this->player->right->nick, "You feel a tap on your shoulder from {$this->player->nick}.");
        $this->player->actionTaken = true;
      } else {
        $this->r->nUser($this->player->nick, "As an Informant Signaler, you have no one to tap. Please announce you are !complete.");
        $this->player->actionTaken = true;
      }
    }
  }
  function tap($from, $args, $source) {
    if(!($this->r->checkArgs($from, $args, 1, 1, $source))) return;
    $tPlayer = $this->r->findPlayer($args[0]);
    if($tPlayer == null) {
      $this->r->nUser($from, "{$args[0]} is not a valid target. Please choose a valid target to !tap.");
      return;
    }
    if(!(in_array($tPlayer, $this->valid))) {
      $this->r->nUser($from, "{$args[0]} is not a valid target. Please choose a valid target to !tap.");
      return;
    }
    $this->r->nUser($this->player->nick, "As a Signaler, you tap {$tPlayer->nick} on the shoulder. Please announce you are !complete.");
    $this->r->nUser($tPlayer->nick, "You feel a tap on your shoulder from {$this->player->nick}.");
    $this->r->nUser($from, "{$tPlayer->nick} is on the {$tPlayer->team} side. You may now !complete your night.");
    $this->player->actionTaken = true;
  }
}
class specThief {
  var $r;
  var $name;
  var $player;
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Thief';
    $this->player = null;
  }
  function init() {
    if($this->player->initialTeam == 'Rebel' || $this->player->revealed) {
      $this->r->nUser($this->player->nick, "As a Rebel Thief, you can now !swap IDs with another player.");
    }
    else {
      if($this->player->team == 'Rebel') {
        $this->r->nUser($this->player->nick, "As an Informant Thief, you view your own allegiance, seeing you are now a Rebel. Please announce you are !complete.");
      } else {
        $this->r->nUser($this->player->nick, "As an Informant Thief, you view your own allegiance, seeing you are still an Informant. Please announce you are !complete.");
      }
      $this->player->actionTaken = true;
    }
  }
  function swap($from, $args, $source) {
    if(!($this->r->checkArgs($from, $args, 1, 1, $source))) return;
    $tPlayer = $this->r->findPlayer($args[0]);
    if($tPlayer == null) {
      $this->r->nUser($from, "{$args[0]} is not a valid target. Please choose a valid target to !swap with.");
      return;
    }
    if($tPlayer == $this->player) {
      $this->r->nUser($from, "You can't !swap with yourself. Please choose a valid target to !swap with.");
      return;
    }
    $this->r->nUser($from, "You swap IDs with {$tPlayer->nick}, you are now on the {$tPlayer->team} side. Please announce you are !complete.");
    $tmpTeam = $tPlayer->team;
    $tmpRevealed = $tPlayer->revealed;
    $tPlayer->team = $this->player->team;
    $tPlayer->revealed = $this->player->revealed;
    $this->player->team = $tmpTeam;
    $this->player->revealed = $tmpRevealed;
    $this->player->actionTaken = true;
  }
}
class specReassignor {
  var $r;
  var $name;
  var $player;
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Reassignor';
    $this->player = null;
  }
  function init() {
    if($this->player->initialTeam == 'Rebel' || $this->player->revealed) {
      $this->r->nUser($this->player->nick, "As a Rebel Reassignor, you can now !swap two other players IDs.");
    }
    else {
      $tc = array();
      for($i=0;$i<3;$i++) {
        $tc[] = $this->r->tableCards[$i];
        if($this->r->tableCards[$i] == 'Informant') break;
      }
      $tableCards = implode(', ', $tc);
      if (in_array('Informant', $tc)) {
        $this->r->nUser($this->player->nick, "As an Informant Reassignor, you look at the IDs on the table, seeing they are: {$tableCards}. You can now !swap a Rebel players ID with an Informant ID.");
      } else {
        $this->r->nUser($this->player->nick, "As an Informant Reassignor, you look at the IDs on the table, seeing they are: {$tableCards}. No Informant cards are available for swapping. Please announce you are !complete.");
        $this->player->actionTaken = true;
      }
    }
  }
  function swap($from, $args, $source) {
    if($this->player->initialTeam == 'Rebel' || $this->player->revealed) {
      if(!($this->r->checkArgs($from, $args, 2, 2, $source))) return;
      $tPlayer1 = $this->r->findPlayer($args[0]);
      if($tPlayer1 == null) {
        $this->r->nUser($from, "{$args[0]} is not a valid target. Please choose a valid targets for the !swap.");
        return;
      }
      $tPlayer2 = $this->r->findPlayer($args[1]);
      if($tPlayer2 == null) {
        $this->r->nUser($from, "{$args[1]} is not a valid target. Please choose a valid targets for the !swap.");
        return;
      }
      if($tPlayer1 == $tPlayer2) {
        $this->r->nUser($from, "Sorry, you can't !swap someone with themselves. Please choose valid targets for the !swap.");
        return;
      }
      if($tPlayer1 == $this->player || $tPlayer2 == $this->player) {
        $this->r->nUser($from, "Sorry, you can't !swap with yourself. Please choose valid targets for the !swap.");
        return;
      }
      $tmpTeam = $tPlayer1->team;
      $tmpRevealed = $tPlayer->revealed;
      $tPlayer1->team = $tPlayer2->team;
      $tPlayer1->revealed = $tPlayer2->revealed;
      $tPlayer2->team = $tmpTeam;
      $tPlayer2->revealed = $tmpRevealed;
      $this->r->nUser($from, "You have swapped {$tPlayer1->nick}'s ID with {$tPlayer2->nick}. Please announce you are !complete.");
      $this->player->actionTaken = true;
    } else {
      if(!($this->r->checkArgs($from, $args, 1, 1, $source))) return;
      $tPlayer = $this->r->findPlayer($args[0]);
      if($tPlayer == null) {
        $this->r->nUser($from, "{$args[0]} is not a valid target. Please choose a valid target to !swap with.");
        return;
      }
      if($tPlayer->initialTeam == 'Informant' && !$tPlayer->revealed) {
        $this->r->nUser($from, "You can't !swap with {$tPlayer->nick}, they are not a Rebel.");
        return;
      }
      for($i=0;$i<3;$i++) {
        if($this->r->tableCards[$i] == 'Informant') {
          $this->r->tableCards[$i] = 'Rebel';
          $this->r->tableCardsRevealed[$i] = $tPlayer->revealed;
          $tPlayer->revealed = false;
          $tPlayer->team = 'Informant';
          break;
        }
      }
      $this->r->nUser($from, "You swap {$tPlayer->nick}'s ID with an Informant with the table. Please announce you are !complete.");
      $this->player->actionTaken = true;
    }
  }
}
class specAnalyst {
  var $r;
  var $name;
  var $player;
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Analyst';
    $this->player = null;
  }
  function init() {
    $this->r->nUser($this->player->nick, "As the Analyst, you may choose one player to !view their Specialist card.");
  }
  function view($from, $args, $source) {
    if(!($this->r->checkArgs($from, $args, 1, 1, $source))) return;
    $tPlayer = $this->r->findPlayer($args[0]);
    if($tPlayer == null) {
      $this->r->nUser($from, "{$args[0]} is not a valid target. Please choose a valid target to !view.");
      return;
    }
    if($tPlayer == $this->player) {
      $this->r->nUser($from, "You are the Specialist, you know you are the Specialist. Please !view another players card.");
      return;
    }
    $this->r->nUser($from, "{$tPlayer->nick} has the Specialist {$tPlayer->specialist->name}. Please announce you are !complete.");
    $this->player->actionTaken = true;
  }
}
class specConfirmer {
  var $r;
  var $name;
  var $player;
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Confirmer';
    $this->player = null;
  }
  function init() {
    $this->r->nUser($this->player->nick, "As the Confirmer, you see you are currently on the {$this->player->team} side. Please announce you are !complete.");
    $this->player->actionTaken = true;
  }
}
class specRevealer {
  var $r;
  var $name;
  var $player;
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Revealer';
    $this->player = null;
  }
  function init() {
    $this->r->nUser($this->player->nick, "As the Revealer, you may !flip one players ID card. If they are a Rebel, leave it revealed.");
  }
  function flip($from, $args, $source) {
    if(!($this->r->checkArgs($from, $args, 1, 1, $source))) return;
    $tPlayer = $this->r->findPlayer($args[0]);
    if($tPlayer == null) {
      $this->r->nUser($from, "{$args[0]} is not a valid target. Please choose a valid target to !flip.");
      return;
    }
    if($tPlayer->team == 'Rebel') {
      $this->r->nUser($from, "{$tPlayer->nick} is on the {$tPlayer->team} side, and have been revealed. Please announce you are !complete.");
      $tPlayer->revealed = true;
    } else {
      $this->r->nUser($from, "{$tPlayer->nick} is on the {$tPlayer->team} side, and remains hidden. Please announce you are !complete.");
    }
    $this->player->actionTaken = true;
  }
}
class specBlindInformant {
  var $r;
  var $name;
  var $player;
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Blind Informant';
    $this->player = null;
  }
  function init() {
    $this->r->nUser($this->player->nick, "As the Blind Informant, you have no actions to perform at night. Please announce you are !complete.");
    $this->player->actionTaken = true;
  }
}
class specDefector {
  var $r;
  var $name;
  var $player;
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Defector';
    $this->player = null;
  }
  function init() {
    if($this->player->initialTeam == 'Informant' && !$this->player->revealed) {
      if($this->player->team == 'Informant') 
        $this->r->nUser($this->player->nick, "As an Informant Defector, you view your own ID, finding you are still on the Informant side. Please announce you are !complete.");
      else
        $this->r->nUser($this->player->nick, "As an Informant Defector, you view your own ID, finding you are now on the Rebel side. Please announce you are !complete.");
    }
    else {
      $tc = array();
      for($i=0;$i<3;$i++) {
        $tc[] = $this->r->tableCards[$i];
        if($this->r->tableCards[$i] == 'Informant') break;
      }
      $tableCards = implode(', ', $tc);
      if (in_array('Informant', $tc)) {
        $this->r->nUser($this->player->nick, "As a Rebel Defector, you look at the IDs on the table, seeing they are: {$tableCards}. You swap with an Informant card. Please announce you are complete.");
        for($i=0;$i<3;$i++) {
          if($this->r->tableCards[$i] == 'Informant') {
            $this->r->tableCards[$i] = $this->player->team;
            $this->r->tableCardsRevealed[$i] = $this->player->revealed;
            $this->player->team = 'Informant';
            $this->player->revealed = false;
            break;
          }
        }
      } else {
        $this->r->nUser($this->player->nick, "As a Rebel Defector, you look at the IDs on the table, seeing they are: {$tableCards}. No Informant cards are available for swapping. Please announce you are !complete.");
      }
    }
    $this->player->actionTaken = true;
  }
}
class specRogue {
  var $r;
  var $name;
  var $player;
  var $informants;
  var $rebels;
  function __construct($root) {
    $this->r = $root;
    $this->name = 'Rogue';
    $this->player = null;
  }
  function init() {
    if($this->player->initialTeam == 'Rebel' || $this->player->revealed) {
      if($this->player->team == 'Rebel')
        $this->r->nUser($this->player->nick, "As a Rebel Rogue, you view your own ID, finding you are still on the Rebel side. Please announce you are !complete.");
      else
        $this->r->nUser($this->player->nick, "As a Rebel Rogue, you view your own ID, finding you are now on the Informant side. Please announce you are !complete.");
      $this->actionTaken = true;
    }
    else {
      foreach($this->r->players as $nick => $player) {
        if($player == $this->player) continue;
        if($player->initialTeam == 'Informant' && !$player->revealed)
          $this->informants[] = $player;
        else
          $this->rebels[] = $player;
      }
      if(count($this->informants) > 0 && count($this->rebels) > 0)
        $this->r->nUser($this->player->nick, "As an Informant Rogue, please !swap a Rebel player with an Informant.");
      else {
        $this->r->nUser($this->player->nick, "As an Informant Rogue, you would swap a Rebel player with an Informant, however there are not enough Informants/Rebels on the table for this swap. Please announce you are !complete.");
        $this->player->actionTaken;
      }
    }
  }
  function swap($from, $args, $source) {
    if(!($this->r->checkArgs($from, $args, 2, 2, $source))) return;
    $tPlayer1 = $this->r->findPlayer($args[0]);
    if($tPlayer1 == null) {
      $this->r->nUser($from, "{$args[0]} is not a valid target. Please choose a valid targets for the !swap.");
      return;
    }
    $tPlayer2 = $this->r->findPlayer($args[1]);
    if($tPlayer2 == null) {
      $this->r->nUser($from, "{$args[1]} is not a valid target. Please choose a valid targets for the !swap.");
      return;
    }
    if($tPlayer1 == $tPlayer2) {
      $this->r->nUser($from, "Sorry, you can't !swap someone with themselves. Please choose valid targets for the !swap.");
      return;
    }
    if($tPlayer1 == $this->player || $tPlayer2 == $this->player) {
      $this->r->nUser($from, "Sorry, you can't !swap with yourself. Please choose valid targets for the !swap.");
      return;
    }
    if(in_array($tPlayer1, $this->informants)) {
      if(!in_array($tPlayer2, $this->rebels)) {
        $this->r->nUser($from, "Sorry, this is not a valid !swap. Please try again.");
        return;
      }
    } else if(in_array($tPlayer2, $this->informants)) {
      if(!in_array($tPlayer1, $this->rebels)) {
        $this->r->nUser($from, "Sorry, this is not a valid !swap. Please try again.");
        return;
      }
    } else {
      $this->r->nUser($from, "Sorry, this is not a valid !swap. Please try again.");
      return;
    }
    $tmpTeam = $tPlayer1->team;
    $tmpRevealed = $tPlayer->revealed;
    $tPlayer1->team = $tPlayer2->team;
    $tPlayer1->revealed = $tPlayer2->revealed;
    $tPlayer2->team = $tmpTeam;
    $tPlayer2->revealed = $tmpRevealed;
    $this->r->nUser($from, "You have swapped {$tPlayer1->nick}'s ID with {$tPlayer2->nick}. Please announce you are !complete.");
    $this->player->actionTaken = true;
  }
}
class specDeck extends deck {
  function __construct($root, $specs) {
    $this->r = $root;
    $this->deck = array();
    $this->discard = array();
    foreach($specs as $spec) {
      $c = "spec$spec";
      $tSpec = new $c($this->r);
      $this->cards[] = $tSpec;
    }
    $this->deck = $this->cards;
  }
}
?>
