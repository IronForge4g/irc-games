<?php
class phaseEnGardeDefence {
  var $r;
  var $desc;

  var $attack;
  var $count;
  
  var $endRound;
  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Defence';
  }
  function init() {
    $player = $this->r->currentPlayer;
    $opponent = $player->left;
    if($player->side == 1) $retreat = $player->position - 1;
    else $retreat = 23 - $player->position;
    $moves = false;
    $parry = 0;
    foreach($player->hand as $key => $val) {
      if($val <= $retreat) {
        $moves = true;
        break;
      }
      else if($val == $this->attack) {
        $parry++;
        if($parry >= $this->count) {
          $moves = true;
          break;
        }
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
      $this->r->mChan("{$player->nick}, please !(p)arry or !(r)etreat.");
      $this->r->nUser($player->nick, "Your hand: ". implode(', ', $player->hand));
    } else {
      $this->r->mChan("{$player->nick} has no valid moves. {$opponent->nick} scores the point.");
      $opponent->score++;
      $this->r->setPhase('newRound');
      return;
    }
  }
  function cmdp($from, $args) {
    $this->cmdparry($from, $args);
  }
  function cmdr($from, $args) {
    $this->cmdretreat($from, $args);
  }
  function cmdparry($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'play a card'))) return;
    $player = $this->r->currentPlayer;
    $card = $player->has($this->attack, $this->count);
    if($card === false) {
      $this->r->mChan("$from, you do not have the valid cards to parry. Please !(r)etreat.");
      return;
    }
    $this->r->mChan("{$from} parries the attack.");
    for($i=0;$i<$this->count;$i++) 
      $player->discard($this->attack);
    if($this->endRound) {
      $this->r->phases['endRound']->attack = true;
      $this->r->setPhase('endRound');
      return;
    }
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
    $end = $player->draw();
    $player->position -= $card * $player->side;
    if($end) {
      $this->r->phases['endRound']->attack = false;
      $this->r->setPhase('endRound');
      return;
    }
    $this->r->currentPlayer = $player->left;
    $this->r->setPhase('offence');
  }
}
?>
