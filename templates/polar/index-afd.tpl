<section class="siteIdentity">
  <div class="siteLogo">
    <a href="{$wwwRoot}" class="noBorder">
      <img src="{$imgRoot}/polar/logo-dexonline.png" alt="logo dexonline"/>
    </a>
  </div>
  <div class="tagline">
    <span class="pageTitle">dexonline</span><br/>
    Dicționare ale limbii române<br/>
    <span class="formPlug">Peste {$words_rough} de definiții</span>
  </div>
</section>

<section id="searchHomePage">
  {include file="bits/searchForm.tpl" advancedSearch=0}
</section>

{if !$suggestNoBanner}
  {include file="bits/banner.tpl" id="mainPage" width="728" height="90"}
{/if}

<section id="missionStatement">
  <i>dexonline</i> transpune pe Internet dicționare de prestigiu ale limbii române. Proiectul este întreținut de un colectiv de voluntari.
  O parte din definiții pot fi descărcate liber și gratuit sub Licența Publică Generală GNU.
  Starea curentă: {$words_total} de definiții, din care {$words_last_month} învățate în ultima lună.
</section>

<section class="widget wotd">
  {include file="widgets/wotd.tpl"}
</section>
