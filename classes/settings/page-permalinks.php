<?php
if (!defined('WPINC')) {
    die();
}

printf('<p>%s</p>', esc_html__('This tool enables you to convert all existing Cyrillic permalinks in your database to Latin characters.', 'serbian-transliteration'));
printf('<p><strong>%s</strong> %s</p>', esc_html__(
    'Warning:',
    'serbian-transliteration'
), esc_html__(
    'Applying slug changes alters existing URLs and may cause 404 errors without redirects. Dry Run does not apply changes.',
    'serbian-transliteration'
));
printf('<p>%s</p>', esc_html__('Before proceeding, consult with your SEO specialist, as you will likely need to resubmit your sitemap and adjust other settings to update the permalinks in search engines.', 'serbian-transliteration'));
printf('<p><strong>%s</strong></p>', esc_html__('WordPress does not automatically redirect old taxonomy term URLs. Download the redirect CSV after conversion and import it into your redirect system. This tool does not install redirects.', 'serbian-transliteration'));
printf('<p>%s</p>', esc_html__('Changing a parent slug can also change descendant and related post URLs. The redirect CSV includes selected objects whose own slug and URL changed; URLs affected only by a parent or category change require a separate redirect review.', 'serbian-transliteration'));
printf('<p>%s</p>', esc_html__('Dry run does not change the database. Proposed URLs are estimates using the current database; parent changes, competing slugs and third-party save or permalink filters can affect the final result. Uncertain collisions are marked explicitly. Reports are private and available for 24 hours on this site only.', 'serbian-transliteration'));

printf('<p>%s: <code style="white-space: nowrap;word-break: keep-all;word-wrap: normal;">wp transliterate permalinks</code></p>', esc_html__('For advanced users, post permalink conversion is also available via WP-CLI using the command', 'serbian-transliteration'));

printf(
    '<p><strong class="text-danger">%s</strong></p>',
    wp_kses_post(sprintf(
        /* translators: %s: Link to the WordPress database backup documentation. */
        __('Important: Make sure to %s before applying changes.', 'serbian-transliteration'),
        '<a href="https://wordpress.org/support/article/wordpress-backups/" target="_blank">' . esc_html__('back up your database', 'serbian-transliteration') . '</a>'
    ))
);

$get_post_types = get_post_types([
    'public' => true,
], 'names', 'and');
printf('<h3>%s</h3>', esc_html__('Post Types', 'serbian-transliteration'));
/*
$post_types = array_map(function($match){
    return '<code><b>' . $match . '</b></code>';
}, $get_post_types);
*/
$post_types_selector = array_map(fn ($match): string => sprintf('<label for="tools-transliterate-post-type-%1$s"><input type="checkbox" id="tools-transliterate-post-type-%1$s" value="%1$s" class="tools-transliterate-permalinks-post-types" name="tools-transliterate-permalinks-post-types[]" checked><span>%2$s</span></label>', esc_attr($match), esc_html(strtr($match, ['_' => ' ',  '-' => ' ']))), $get_post_types);

printf(
    '<p>%s</p>',
    wp_kses(
        sprintf(
            /* translators: %s: List of post type checkboxes affected by the operation. */
            __('This tool will affect on the following post types: %s', 'serbian-transliteration'),
            '<br><br>' . implode('&nbsp;&nbsp;&nbsp;&nbsp; ', $post_types_selector)
        ),
        [
            'br'    => [],
            'input' => [
                'checked' => true,
                'class'   => true,
                'id'      => true,
                'name'    => true,
                'type'    => true,
                'value'   => true,
            ],
            'label' => ['for' => true],
            'span'  => [],
        ]
    )
);
printf('<h3>%s</h3>', esc_html__('Taxonomies', 'serbian-transliteration'));
foreach (Transliteration_Permalink_Job::taxonomies() as $permalink_taxonomy) {
    printf('<p><label><input type="checkbox" class="tools-transliterate-permalinks-taxonomies" value="%1$s"> %2$s <code>%1$s</code></label></p>', esc_attr($permalink_taxonomy->name), esc_html($permalink_taxonomy->labels->name));
}
?>
<style>
    #rstr-permalink-resume[hidden], #rstr-permalink-reset[hidden], #rstr-permalink-result[hidden], #rstr-permalink-csv[hidden], #rstr-permalink-report[hidden], #rstr-permalink-preview[hidden] { display: none !important; }
</style>
<br>
<div id="rstr-progress-bar" style="display:none;">
	<p class="progress-value" style="width:33%" data-value="33"></p>
	<progress max="100" value="33" class="php">
		<!-- Browsers that support HTML5 progress element will ignore the html inside `progress` element. Whereas older browsers will ignore the `progress` element and instead render the html inside it. -->
		<div class="progress-bar">
			<span style="width: 33%">33%</span>
		</div>
	</progress>
	<p class="progress-message"><?php esc_html_e('Please wait! Do not close the window or leave the page until this operation is completed!', 'serbian-transliteration'); ?></p>
</div>
<p>
	<input type="button" id="rstr-permalink-dry-run" class="button" value="<?php esc_attr_e('Dry Run', 'serbian-transliteration'); ?>">
	<input type="button" id="<?php echo 'serbian-transliteration' ?>-tools-transliterate-permalinks" class="button button-primary" data-nonce="<?php echo esc_attr(wp_create_nonce('rstr-run-permalink-transliteration')); ?>" value="<?php esc_attr_e('Convert / Apply permalinks', 'serbian-transliteration'); ?>" disabled>
	&nbsp;&nbsp;&nbsp;
	<label for="<?php echo 'serbian-transliteration' ?>-tools-check">
		<input type="checkbox" id="<?php echo 'serbian-transliteration' ?>-tools-check" value="1"> <?php esc_html_e('I have a backup and understand that applying changes can change existing URLs.', 'serbian-transliteration'); ?>
	</label>
</p>
<blockquote id="rstr-disclaimer" style="display:none;">
	<h3><?php esc_html_e('Disclaimer', 'serbian-transliteration'); ?></h3>
	<?php printf('<p>%s</p>', esc_html__('While this tool is designed to operate safely, there is always a small risk of unpredictable issues.', 'serbian-transliteration')); ?>
	<?php printf('<p><b style="text-transform: uppercase;">%s</b></p>', esc_html__('Note: We do not guarantee that this tool will function correctly on your server. By using it, you assume all risks and responsibilities for any potential issues.', 'serbian-transliteration')); ?>
	<?php printf('<p><b style="text-transform: uppercase;">%s</b></p>', esc_html__('Backup your database before using this tool.', 'serbian-transliteration')); ?>
</blockquote>
<p>
    <button type="button" id="rstr-permalink-resume" class="button" hidden><?php esc_html_e('Resume / Retry last operation', 'serbian-transliteration'); ?></button>
    <button type="button" id="rstr-permalink-reset" class="button" hidden><?php esc_html_e('Clear saved operation progress', 'serbian-transliteration'); ?></button>
</p>
<p class="description"><?php esc_html_e('This clears only the operation progress saved in this browser. It does not undo any slug changes that were already applied.', 'serbian-transliteration'); ?></p>
<div id="rstr-permalink-result" class="notice inline" role="status" aria-live="polite" hidden><p></p></div>
<p>
    <a id="rstr-permalink-csv" class="button button-primary" hidden><?php esc_html_e('Download redirect CSV', 'serbian-transliteration'); ?></a>
    <a id="rstr-permalink-report" class="button" hidden><?php esc_html_e('Download full operation report', 'serbian-transliteration'); ?></a>
</p>
<div id="rstr-permalink-preview" hidden>
    <p><?php esc_html_e('Most recent batch (up to 50 objects). The full operation report contains every candidate. Status codes: unchanged, will_change, collision_adjusted, potential_collision, changed, skipped, invalid, failed, conflict, url_only_change. Estimated URLs are not guaranteed redirects.', 'serbian-transliteration'); ?></p>
    <div style="overflow-x:auto">
        <table class="widefat striped">
            <thead><tr>
                <?php foreach ([__('Object type', 'serbian-transliteration'), __('ID', 'serbian-transliteration'), __('Post type / Taxonomy', 'serbian-transliteration'), __('Title / Name', 'serbian-transliteration'), __('Old slug', 'serbian-transliteration'), __('New slug', 'serbian-transliteration'), __('Old URL', 'serbian-transliteration'), __('New URL (estimated in dry run)', 'serbian-transliteration'), __('Status', 'serbian-transliteration'), __('URL certainty', 'serbian-transliteration')] as $permalink_heading) : ?>
                    <th scope="col"><?php echo esc_html($permalink_heading); ?></th>
                <?php endforeach; ?>
            </tr></thead><tbody></tbody>
        </table>
    </div>
</div>
