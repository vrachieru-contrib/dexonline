<a href="bulkLabelSelectSuffix.php">Alege alt sufix</a><br/>

<ul>
  <li>Sunt prezentate maximum 20 de lexeme pe pagină.</li>
  <li>
    Restricțiile nu sunt luate în considerare în timp real (toate
    formele vor fi afișate chiar dacă indicați unele restricții),
    dar vor fi procesate corect când trimiteți formularul.
  </li>
  <li>
    Dacă ignorați un lexem, el nu va fi modificat și va continua să apară în listă.
    Îi puteți adăuga un comentariu, dacă doriți.
  </li>
</ul>

<form action="bulkLabel.php" method="post">
  <input type="hidden" name="suffix" value="{$suffix|escape}"/>
  {foreach from=$lexems item=l key=lIter}
    <div class="blLexem">
      <div class="blLexemTitle">
        <span class="name">{$lIter+1}. {$l->formNoAccent|escape}</span>
        <a class="noBorder" href="../admin/lexemEdit.php?lexemId={$l->id}">
          <img src={$imgRoot}/icons/pencil.png alt="editează" title="editează lexemul"/>
        </a>
      </div>
      <div class="blLexemBody">

        <!-- Radio buttons to choose the model. -->
        Model de flexiune:
        {foreach from=$models item=m key=mIter}
          {assign var="mId" value="`$m->modelType`_`$m->number`"}
          {assign var="label" value="radio_`$l->id`_`$mId`"}
          <input type="radio" name="lexem_{$l->id}" value="{$mId}"
                 id="{$label|escape}"
                 onclick="hideDiv('comment_{$l->id}'); return blUpdateParadigmVisibility(this)"
          /><label for="{$label|escape}"
          >{$m->modelType}{$m->number} ({$m->exponent})</label>
          &nbsp;&nbsp;
        {/foreach}
        &nbsp;&nbsp;&nbsp;&nbsp;
        <input type="radio" name="lexem_{$l->id}" value="0"
               id="radio_{$l->id}_0"
               checked="checked"
               onclick="showDiv('comment_{$l->id}'); return blUpdateParadigmVisibility(this)"
        /><label for="radio_{$l->id}_0">Ignoră</label>
        <br/>

        <div id="comment_{$l->id}" class="bulkLabelComment">
          <span style="vertical-align: top">Comentariu:</span>
          <textarea name="comment_{$l->id}" rows="2" cols="60"
                    class="commentTextArea"
          >{$l->comment|escape}</textarea>
        </div>

        <!-- Restriction checkboxes, if applicable -->
        Restricții:
        <input type="text" name="restr_{$l->id}" size="5">
        <br/>

        <!-- Hide/show definitions -->
        <a href="#" id="defLink_{$l->id}"
           onclick="return blUpdateDefVisibility(this)"
        >ascunde definițiile</a>

        <!-- Definitions -->
        <div class="blDefinitions" id="definitions_{$l->id}">
          {assign var="srArray" value=$searchResults[$lIter]}
          {foreach from=$srArray item=row}
            {$row->definition->htmlRep}<br/>
            <span class="defDetails">
              Sursa: {$row->source->shortName|escape} |
              {assign var=status value=$row->definition->status}
              {assign var=statusName value=$allStatuses[$status]}
              Starea: {$statusName}
            </span>
            <br/>
          {/foreach}
        </div>

        <!-- Div containing all the paradigms. Only one of them will -->
        <!-- be visible at any time. -->
        <div id="paradigms_{$l->id}">
          {assign var="lmArray" value=$lmMatrix[$lIter]}
          {foreach from=$lmArray item=lm key=pIter}
            {assign var="m" value=$models[$pIter]}
            {assign var="mt" value=$modelTypes[$pIter]}
            <div class="blParadigm" style="display: none" id="paradigm_{$l->id}_{$m->modelType}_{$m->number}">
              {include file="paradigm/paradigm.tpl" lexemModel=$lm}
            </div>
          {/foreach}
        </div>
      </div>
    </div>
  {/foreach}

  <br/>
  <input type="submit" name="submitButton" value="Trimite"/>
</form>
