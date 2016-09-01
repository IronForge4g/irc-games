<?php
class phaseOneNightRevolutionClaim {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Claim a Specialist';
  }
  function init() {
    $tPlayer = $this->r->currentPlayer;
    if($tPlayer->claimed != null) {
      $this->r->setPhase('day');
      return;
    }
    $this->r->mChan("The specialists in this game are: ".implode(', ', $this->r->table).". {$tPlayer->nick}, please !claim a specialist.");
  }
  function cmdclaim($from, $args, $source) {
    if(!($this->r->checkCurrentPlayer($from, 'claim a specialist', $source))) return;
    if(!($this->r->checkArgs($from, $args, 1, 1, $source))) return;
    $player = $this->r->currentPlayer;
    $spec = $this->r->findSpec($args[0]);
    if($spec == null) {
      $this->r->mChan("$from: Please !claim a valid specialist from the table.");
      return;
    }
    if(!(in_array($spec, $this->r->table))) {
      $this->r->mChan("$from: Please !claim a valid specialist from the table.");
      return;
    }
    $player->claimed = $spec;
    $this->r->currentPlayer = $player->left;
    $this->r->mChan("$from has claimed to have the $spec specialist.");
    $this->r->setPhase('claim');
  }
}
?>
