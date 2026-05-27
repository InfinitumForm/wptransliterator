<?php if (!defined('WPINC')) {
    die();
} ?>
<p class="description"><?php esc_html_e('These tags have a special purpose and work separately from short codes and can be used in fields where short codes cannot be used.', 'serbian-transliteration'); ?><br><?php esc_html_e('These tags have no additional settings and can be applied in plugins, themes, widgets and within other short codes.', 'serbian-transliteration'); ?></p>
<h2 style="margin:0;"><?php esc_html_e('Keep exact content', 'serbian-transliteration'); ?>:</h2>
<p><code class="lang-txt">{<span class="hljs-title">rstr_keep</span>}<?php esc_html_e('Keep this text exactly as written', 'serbian-transliteration'); ?>{/<span class="hljs-title">rstr_keep</span>}</code></p>
<br>
<h2 style="margin:0;"><?php esc_html_e('Skip active transliteration', 'serbian-transliteration'); ?>:</h2>
<p><code class="lang-txt">{<span class="hljs-title">rstr_skip</span>}<?php esc_html_e('Keep this text exactly as written', 'serbian-transliteration'); ?>{/<span class="hljs-title">rstr_skip</span>}</code></p>
<br>
<h2 style="margin:0;"><?php esc_html_e('Cyrillic to Latin', 'serbian-transliteration'); ?>:</h2>
<p><code class="lang-txt">{<span class="hljs-title">cyr_to_lat</span>}Ћирилица у латиницу{/<span class="hljs-title">cyr_to_lat</span>}</code></p>
<br>
<h2 style="margin:0;"><?php esc_html_e('Latin to Cyrillic', 'serbian-transliteration'); ?>:</h2>
<p><code class="lang-txt">{<span class="hljs-title">lat_to_cyr</span>}Latinica u ćirilicu{/<span class="hljs-title">lat_to_cyr</span>}</code></p>