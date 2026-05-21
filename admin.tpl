<div class="titrePage">
  <h2>Stop Spammers - Settings</h2>
</div>

<form method="post" action="{$F_ACTION}" class="properties">

  <fieldset>
    <legend>StopForumSpam</legend>

    <p>
      <label>
        Confidence threshold<br>
        <input type="number" name="stop_spammers_sfs_threshold" value="{$STOP_SPAMMERS_SFS_THRESHOLD}" min="1" max="100">
      </label>
    </p>

    <p>
      <label>
        Cache duration in days<br>
        <input type="number" name="stop_spammers_cache_days" value="{$STOP_SPAMMERS_CACHE_DAYS}" min="1">
      </label>
    </p>
  </fieldset>

  <fieldset>
    <legend>Content filtering</legend>

    <p>
      <label>
        Maximum number of links<br>
        <input type="number" name="stop_spammers_max_links" value="{$STOP_SPAMMERS_MAX_LINKS}" min="0">
      </label>
    </p>

    <p>
      <label>
        Spam keywords, one per line<br>
        <textarea name="stop_spammers_keywords" rows="10" cols="80">{$STOP_SPAMMERS_KEYWORDS}</textarea>
      </label>
    </p>
  </fieldset>

  <fieldset>
    <legend>Whitelist</legend>

    <p>
      <label>
        Whitelisted IP addresses, one per line<br>
        <textarea name="stop_spammers_whitelist" rows="6" cols="80">{$STOP_SPAMMERS_WHITELIST}</textarea>
      </label>
    </p>
  </fieldset>

  <p>
    <input type="hidden" name="pwg_token" value="{$PWG_TOKEN}">
    <input type="submit" name="submit" value="Save settings">
  </p>

</form>