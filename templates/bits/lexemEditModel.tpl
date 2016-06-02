{assign var="readonly" value=!$canEdit.loc && $l->isLoc}
<table class="paradigmFields">
  <tr>
    <td>model:</td>
    <td>
      <span data-model-dropdown>
        <input type="hidden" name="locVersion" value="6.0" data-loc-version>
        <select name="modelType" {if $readonly}disabled{/if} data-model-type data-selected="{$l->modelType}">
        </select>
        <select name="modelNumber" {if $readonly}disabled{/if} data-model-number data-selected="{$l->modelNumber}">
        </select>
        
        {if !$readonly}
          <select class="similarLexem"></select>
        {/if}
      </span>
    </td>
  </tr>

  <tr>
    <td>restricții:</td>
    <td>
      <input type="text" name="restriction" value="{$l->restriction}" size="5" {if $readonly}readonly{/if}>
      <span class="tooltip2" title="litere din setul <b>S</b>ingular, <b>P</b>lural, <b>U</b>nipersonal, <b>I</b>mpersonal, <b>T</b>recut, <b>V</b> (vocativ masculin/neutru forma 1), <b>v</b> (vocativ masculin/neutru forma 2), <b>W</b> (vocativ feminin forma 1), <b>w</b> (vocativ feminin forma 2)">&nbsp;</span>
    </td>
  </tr>

  <tr>
    <td>surse:</td>
    <td>
      <select class="sourceIds" name="sourceIds[]" multiple {if !$canEdit.sources}disabled{/if}>
        {foreach $l->getSourceIds() as $lsId}
          <option value="{$lsId}" selected></option>
        {/foreach}
      </select>
    </td>
  </tr>

  <tr>
    <td>etichete:</td>
    <td>
      <input type="text" name="notes" value="{$l->notes|escape}" size="26"
             placeholder="explicații despre sursa flexiunii" {if !$canEdit.tags}readonly{/if}>
      <span class="tooltip2" title="O scurtă clasificare, vizibilă public, care marchează sursa flexiunii. Pentru cuvintele cu flexiuni în DOOM-ul
                                    curent (DOOM2 în acest moment), ea poate fi vidă. Sursele pot reprezenta dicționare, autori cunoscuți, inclusiv părerea moderatorului, dar
                                    trebuie documentate clar aceste situații.">&nbsp;</span>
    </td>
  </tr>
  
  <tr>
    <td>inclus în LOC:</td>
    <td>
      <input type="hidden" name="isLoc" value="{if $l->isLoc}1{/if}">
      {if $canEdit.loc}
        <input type="checkbox" class="fakeCheckbox" value="1" {if $l->isLoc}checked{/if}>
      {else}
        {if $l->isLoc}da{else}nu{/if}
      {/if}
      <span class="tooltip2" title="dexonline menține Lista Oficială de Cuvinte a Federației Române de Scrabble. Acest câmp poate fi modificat
                                    numai de către un set restrâns de administratori ai LOC.">&nbsp;</span>
    </td>
  </tr>
</table>

{include "paradigm/paradigm.tpl" lexem=$l}
