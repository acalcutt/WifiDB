{* Hints for the spam check (CleanTalk, when it's on): when the form was shown, and whether its script ran. *}
<input type="hidden" name="ct_t" value="{$ct_form_time|default:0}"><input type="hidden" name="ct_js" value="0" class="wifidb-ct-js">
<script>{literal}document.querySelectorAll('.wifidb-ct-js').forEach(function (e) { e.value = '1'; });{/literal}</script>
