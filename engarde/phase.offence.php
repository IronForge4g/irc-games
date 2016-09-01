<?php
class phaseEnGardeOffence {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Offence';
  }
  function init() {
    $player = $this->r->currentPlayer;
    $opponent = $player->left;
    $distance = abs($player->position - $opponent->position);
    if($player->side == 1) $retreat = $player->position - 1;
    else $retreat = 23 - $player->position;
    $moves = false;
    foreach($player->hand as $key => $val) {
      if($val <= $distance || $val <= $retreat) {
        $moves = true;
        break;
      }
    }
    if($moves) {
      for($i=1;$i<24;$i++) {
        if($player->position == $i) $board[] = $player->nick;
        else if($opponent->position == $i) $board[] = $opponent->nick;
        else $board[] = $i;
      }
      $this->r->mChan(implode(' ', $board));
      if(count($this->r->discarded) > 0) $this->r->mChan("Discard Pile: ".implode(', ', $this->r->discarded).'.');
      $this->r->mChan("{$player->nick}, you're up. The deck has ".$this->r->plural($this->r->deck->count(), "card").". You are {$distance} from your opponent. Please !(a)dvance, !(r)etreat, !da [direct attack], or !aa [advance and attack].");
      $this->r->nUser($player->nick, "Your hand: ". implode(', ', $player->hand));
    } else {
      $this->r->mChan("{$player->nick} has no valid moves. {$opponent->nick} scores the point.");
      $opponent->score++;
      $this->r->setPhase('newRound');
      return;
    }
  }
  function cmda($from, $args) {
    $this->cmdadvance($from, $args);
  }
  function cmdr($from, $args) {
    $this->cmdretreat($from, $args);
  }
  function cmdda($from, $args) {
    $this->cmddirect($from, $args);
  }
  function cmdaa($from, $args) {
    $this->cmdattack($from, $args);
  }
  function cmdadvance($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'play a card'))) return;
    if(!($this->r->checkArgs($from, $args, 1, 1))) return;
    $player = $this->r->currentPlayer;
    $opponent = $player->left;
    $distance = abs($player->position - $opponent->position);
    $card = $player->has($args[0]);
    if($card === false) {
      $this->r->mChan("$from, please play a valid card.");
      return;
    }
    $card = $args[0];
    if($card >= $distance) {
      $this->r->mChan("$from, You may not advance that far. Please play a valid card.");
      return;
    }
    $this->r->mChan("{$from} advances ".$this->r->plural($card, 'space').".");
    $player->discard($card);
    $player->position += $card * $player->side;
    $end = $player->draw();
    if($end) {
      $this->r->phases['endRound']->attack = true;
      $this->r->setPhase('endRound');
      return;
    }
    $this->r->currentPlayer = $opponent;
    $this->r->setPhase('offence');
  }
  function cmdretreat($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'play a card'))) return;
    if(!($this->r->checkArgs($from, $args, 1, 1))) return;
    $player = $this->r->currentPlayer;
    if($player->side == 1) $retreat = $player->position - 1;
    else $retreat = 23 - $player->position;
    $card = $player->has($args[0]);
    if($card === false) {
      $this->r->mChan("$from, please play a valid card.");
      return;
    }
    $card = $args[0];
    if($card > $retreat) {
      $this->r->mChan("$from, You may not retreat that far. Please play a valid card.");
      return;
    }
    $this->r->mChan("{$from} retreats ".$this->r->plural($card, 'space').".");
    $player->discard($card);
    $player->position -= $card * $player->side;
    $end = $player->draw();
    if($end) {
      $this->r->phases['endRound']->attack = true;
      $this->r->setPhase('endRound');
      return;
    }
    $this->r->currentPlayer = $player->left;
    $this->r->setPhase('offence');
  }
  function cmddirect($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'play a card'))) return;
    if(!($this->r->checkArgs($from, $args, 1, 5))) return;
    $card = $args[0];
    foreach($args as $arg) {
      if($arg != $card) {
        $this->r->mChan("$from, all attack cards must match. Please play a valid card.");
        return;
      }
    }
    $player = $this->r->currentPlayer;
    $opponent = $player->left;
    $distance = abs($player->position - $opponent->position);
    $card = $player->has($args[0], count($args));
    if($card === false) {
      $this->r->mChan("$from, please play a valid card.");
      return;
    }
    $card = $args[0];
    if($card != $distance) {
      $this->r->mChan("$from, you are not at the correct distance ({$distance}) to attack with {$card}. Please play a valid card.");
      return;
    }
    $parry = $opponent->has($args[0], count($args));
    if($parry) {
      $this->r->mChan("{$from} attacks, {$opponent->nick} parries.");
      foreach($args as $arg) $opponent->discard($arg);
    }
    else {
      $this->r->mChan("{$from} attacks, hitting {$opponent->nick} for the point.");
      $player->score++;
      $this->r->setPhase('newRound');
      return;
    }
    foreach($args as $arg) $player->discard($arg);
    $end = $player->draw();
    if($end) {
      $this->r->phases['endRound']->attack = true;
      $this->r->setPhase('endRound');
      return;
    }
    $this->r->currentPlayer = $player->left;
    $this->r->setPhase('offence');
  }
  function cmdattack($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'play a card'))) return;
    if(!($this->r->checkArgs($from, $args, 2, 6))) return;
    $advance = array_shift($args);
    $card = $args[0];
    foreach($args as $arg) {
      if($arg != $card) {
        $this->r->mChan("$from, all attack cards must match. Please play a valid card.");
        return;
      }
    }
    $player = $this->r->currentPlayer;
    $opponent = $player->left;
    $distance = abs($player->position - $opponent->position);
    $card = $player->has($args[0], count($args));
    if($card === false) {
      $this->r->mChan("$from, please play a valid card.");
      return;
    }
    $card = $args[0];
    if($card != ($distance - $advance)) {
      $this->r->mChan("$from, you are not at the correct distance ({$distance}) to advance {$advance} and attack with {$card}. Please play a valid card.");
      return;
    }
    $parry = $opponent->has($args[0], count($args));
    $this->r->mChan("{$from} advances ({$advance}) and attacks (".implode(', ', $args).").");
    $player->position += $advance * $player->side;
    $player->discard($advance);
    foreach($args as $arg) $player->discard($arg);
    $end = $player->draw();
    if($end) $this->r->phases['endRound']->attack = false;
    $this->r->currentPlayer = $player->left;
    $this->r->phases['defence']->attack = $card;
    $this->r->phases['defence']->count = count($args);
    $this->r->phases['defence']->endRound = $end;
    $this->r->setPhase('defence');
  }
}
?>
