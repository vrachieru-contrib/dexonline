Lexemul {include file="bits/lexemName.tpl" lexem=$lexem} a fost șters. Puteți vizita unul dintre omonimele listate mai jos sau merge înapoi
la <a href="../admin">pagina moderatorului</a>.
<br/><br/>

{foreach from=$homonyms item=h key=i}
  {if $i}|{/if}
  <a href="lexemEdit.php?lexemId={$h->id}"
    >{include file="bits/lexemName.tpl" lexem=$h}</a>
{/foreach}
