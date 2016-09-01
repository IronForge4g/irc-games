<?php
class phaseCodenamesSetup {
  var $r;
  var $desc;

  var $informants;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Setting up Game';
  }
  function init() {
    $this->setupBase();
    $this->setupPlayers();
    $this->r->mChan("The game is now starting.");
    $this->r->mChan("The ".$this->r->colorText('Green', 'green')." team: ".$this->r->team('green'));
    $this->r->mChan("The ".$this->r->colorText('Pink', 'pink')." team: ".$this->r->team('pink'));
    $this->r->setPhase('spymaster');
  }
  function setupBase() {
    $this->r->started = true;
    $this->r->codenamesDeck = new codenamesDeck($this->r);
    $this->r->words = array();
    for($i=0;$i<9;$i++) $this->r->words[] = new codenameCard($this->r, $this->r->codenamesDeck->draw(), 'green');
    for($i=0;$i<8;$i++) $this->r->words[] = new codenameCard($this->r, $this->r->codenamesDeck->draw(), 'pink');
    for($i=0;$i<7;$i++) $this->r->words[] = new codenameCard($this->r, $this->r->codenamesDeck->draw(), 'none');
    $this->r->words[] = new codenameCard($this->r, $this->r->codenamesDeck->draw(), 'orange');
    shuffle($this->r->words);
    $this->r->spymaster = array();
    $this->r->turn = 'pink';
  }
  function setupPlayers() {
    $first = null;
    $last = null;
    $nicks = array_keys($this->r->players);
    shuffle($nicks);
    $new = array();
    foreach($nicks as $nick) $new[$nick] = $this->r->players[$nick];
    $this->r->players = $new;
    $masters = 0;
    $color = 'green';
    foreach($this->r->players as $nick => $player) {
      $player->color = $color;
      $player->spymaster = false;
      $player->ending = false;
      if($masters < 2) {
        $player->spymaster = true;
        $this->r->spymaster[$color] = $player;
        $masters++;
      }
      $color = $color == 'green' ? 'pink' : 'green';
      if($last == null) {
        $first = $player;
        $last = $player;
        continue;
      }
      $player->right = $last;
      $last->left = $player;
      $last = $player;
    }
    $first->right = $last;
    $last->left = $first;
    $this->r->currentPlayer = $this->r->spymaster['green'];
  }
}
?>
