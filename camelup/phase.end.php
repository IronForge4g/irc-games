<?php
class phaseCamelUpEnd {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'End of Leg';
  }
  function init() {
    $this->r->mChan('The game is over! Final Scoring...');
    $order = $this->r->camelOrder();
    $loser = $order[0];
    $winner = $order[4];
    $winnerPoints = array(8, 5, 3, 2);
    for($i=0;$i<40;$i++) $winnerPoints[] = 1;
    $loserPoints = array(8, 5, 3, 2);
    for($i=0;$i<40;$i++) $loserPoints[] = 1;
    while(count($this->r->winDeck) > 0) {
      list($player, $color) = array_shift($this->r->winDeck);
      if($color == $winner->color) {
        $points = array_shift($winnerPoints);
        $this->r->mChan($player->nick." earns $".$points." for betting on the ".$this->r->colorText("{$winner->color} camel", $winner->color)." to win.");
        $player->money += $points;
      } else {
        $this->r->mChan($player->nick." loses $"."1 for betting on the ".$this->r->colorText("{$color} camel", $color)." to win.");
        $player->money -= 1;
      }
    }
    while(count($this->r->loseDeck) > 0) {
      list($player, $color) = array_shift($this->r->loseDeck);
      if($color == $loser->color) {
        $points = array_shift($loserPoints);
        $this->r->mChan($player->nick." earns $"."{$points} for betting on the ".$this->r->colorText("{$loser->color} camel", $loser->color)." to lose.");
        $player->money += $points;
      } else {
        $this->r->mChan($player->nick." loses $"."1 for betting on the ".$this->r->colorText("{$color} camel", $color)." to lose.");
        $player->money -= 1;
      }
    }
    $scores = array();
    foreach($this->r->players as $nick => $player) {
      if($player->money < 0) $player->money = 0;
      $scores[$nick] = $player->money;
    }
    arsort($scores);
    $places = array('1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th');
    $place = 0;
    foreach($scores as $nick => $score) {
      $this->r->mChan($places[$place++]." place was $nick with $"."{$score}.");
    }
    $this->r->setPhase('nogame');
  }
}
?>
