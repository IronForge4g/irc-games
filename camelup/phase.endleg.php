<?php
class phaseCamelUpEndLeg {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'End of Leg';
  }
  function init() {
    $this->r->mChan('The leg is over!');
    $order = $this->r->camelOrder();
    $position = array();
    $p = 5;
    foreach($order as $camel) $position[$camel->color] = $p--;
    foreach($this->r->players as $nick => $player) {
      foreach($player->bets as $bet) {
        list($color, $points) = $bet;
        if($position[$color] == 1) {
          $player->money += $points;
          $this->r->mChan($player->nick." earns $".$points." for betting on the ".$this->r->colorText("$color camel", $color)." in 1st.");
        }
        else if($position[$color] == 2) {
          $player->money += 1;
          $this->r->mChan($player->nick." earns $"."1 for betting on the ".$this->r->colorText("$color camel", $color)." in 2nd.");
        }
        else {
          $this->r->mChan($player->nick." loses $"."1 for betting on the ".$this->r->colorText("$color camel", $color).".");
          $player->money -= 1;
        }
      }
      if($player->money < 0) $player->money = 0;
    }
    if($this->r->gameEnd) $this->r->setPhase('end');
    else $this->r->setPhase('startleg');
  }
}
?>
