<?php
class phaseCodenamesSpymaster {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Spymaster';
  }
  function init() {
    $this->r->turn = ($this->r->turn == 'green' ? 'pink' : 'green');
    $player = $this->r->spymaster[$this->r->turn];
    $this->r->currentPlayer = $player;
    $words = array('green' => array(), 'pink' => array(), 'none' => array(), 'hidden' => array(), 'spygreen' => array(), 'spypink' => array(), 'spynone' => array(), 'spyorange' => array());
    foreach($this->r->words as $word) {
      if($word->revealed) $words[$word->color][] = $word->code;
      else {
        $words['hidden'][] = $word->code;
        $words['spy'.$word->color][] = $word->cWord();
      }
    }
    if(count($words['green']) > 0) $this->r->mChan("Green Spies: ".$this->r->colorText(implode(', ', $words['green']), 'green'));
    if(count($words['pink']) > 0) $this->r->mChan("Pink Spies: ".$this->r->colorText(implode(', ', $words['pink']), 'pink'));
    if(count($words['none']) > 0) $this->r->mChan("Civilians: ".implode(', ', $words['none']));
    $this->r->mChan("Codenames remaining: ".implode(', ', $words['hidden']));
    $this->r->mChan($player->cNick().", you're up, please give your !(c)lue.");
    $this->r->nUser($this->r->spymaster['green']->nick, "Assassin: ".implode(', ', $words['spyorange']).". Green: ".implode(', ', $words['spygreen']).". Pink: ".implode(', ', $words['spypink']).". Civilians: ".implode(', ', $words['spynone']).".");
    $this->r->nUser($this->r->spymaster['pink']->nick, "Assassin: ".implode(', ', $words['spyorange']).". Green: ".implode(', ', $words['spygreen']).". Pink: ".implode(', ', $words['spypink']).". Civilians: ".implode(', ', $words['spynone']).".");
  }
  function cmdc($from, $args) {
    $this->cmdclue($from, $args);
  }
  function cmdclue($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'give a clue'))) return;
    if(!($this->r->checkArgs($from, $args, 2, 2))) return;
    $player = $this->r->currentPlayer;
    $clue = $args[0];
    $number = $args[1];
    $checkNumber = preg_replace("#[^0-9]+#", "", $number);
    if($checkNumber != $number) {
      $this->r->mChan($player->cNick().", {$number} is not a valid number. Please give a !(c)lue <word> <number>.");
      return;
    }
    $this->r->phases['guess']->count = $number;
    $this->r->setPhase('guess');
  }
}
?>
