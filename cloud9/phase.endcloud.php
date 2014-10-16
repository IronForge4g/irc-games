<?php
class phaseCloud9EndCloud {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Climbing';
  }
  function init() {
    $this->r->nUser($this->r->currentPlayer->nick, "Your hand: ".implode(', ', $this->r->currentPlayer->hand).".");
    $this->r->mChan($this->r->currentPlayer->nick.", everyone has made their choice. You require: ".implode(', ', $this->r->requiredSkills).". Please !pilot or !rainbow.");
  }
  function cmdpilot($from, $args) {
    $this->pilotBalloon($from, false);
  }
  function cmdrainbow($from, $args) {
    $this->pilotBalloon($from, true);
  }
  function pilotBalloon($from, $rainbow = false) {
    if(!($this->r->checkCurrentPlayer($from, "pilot the ballooon"))) return;
    $required = array_count_values($this->r->requiredSkills);
    unset($required['Blank']);
    $hand = array_count_values($this->r->currentPlayer->hand);

    if($rainbow) {
      if(!(isset($hand['Rainbow']))) {
        $this->r->mChan("$from: You look everywhere for a rainbow amongst the clouds, but alas find nothing.");
        return;
      }
      $required = array('Rainbow' => 1);
    }
    $valid = true;
    foreach($required as $key => $val) {
      if(!(isset($hand[$key]))) {
        $valid = false;
        break;
      }
      if($hand[$key] < $val) {
        $valid = false;
        break;
      }
    }
    if($valid) {
      $discard = array();
      foreach($this->r->currentPlayer->hand as $idx => $card) {
        if(!(isset($required[$card]))) continue;
        if($required[$card] == 0) continue;
        $discard[] = $idx;
        $this->r->deck->discard($card);
        $required[$card]--;
      }
      foreach($discard as $idx) unset($this->r->currentPlayer->hand[$idx]);
      $this->r->currentCloud++;
      $this->r->mChan("$from successfully pilots the balloon.");
      $next = $this->r->currentPlayer->left;
      while($next != $this->r->currentPlayer) {
        if($next->jumped) $next = $next->left;
        else break;
      }
      $this->r->currentPlayer = $next;
      if($this->r->currentCloud == 9) {
        $stillIn = array();
        foreach($this->r->players as $nick => $player) {
          if($player->jumped) continue;
          $player->points += 25;
          $stillIn[] = $player->nick;
        }
        $this->r->mChan("By making it to cloud 9, everyone still on board (".implode(", ", $stillIn).") earns 25 points, has a wonderful ride, and then lands safely back to the ground.");
        $this->r->setPhase('endclimb');
        return;
      } 
      $this->r->setPhase('startcloud');
      return;
    } else {
      $this->r->mChan("$from pretends like they know what they're doing, crashing the balloon into the ground. Way to go $from.");
      $this->r->currentPlayer = $this->r->currentPlayer->left;
      $this->r->setPhase('endclimb');
      return;
    }
  }
}
?>
