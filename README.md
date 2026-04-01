# Reverse Title

Reverse Title is a lightweight WordPress plugin that swaps the order of the page title and site name in the browser `<title>` tag on posts, pages, and custom post types. The default WordPress title format is `Page Title – Site Name`, and with Reverse Title it becomes `Site Name – Page Title`. The front page title is unchanged.

The most common alternative to Reverse Title is using a small custom filter in functions.php, and there are title reversal features found in plugins like Yoast SEO. Reverse Title is a bit different, though, because it tries to address just one problem, and that's it.

Well, Reverse Title does offer a little bit more. You can also:

- Change the separator between your site name and page title
- Enable or disable title reversal on a per-post basis
- Add a custom tagline, slogan, or even a Unicode character between your site name and page title

If like me you like your bookmarks and tabs sorted by [site name] and having a custom separator between, this plugin is for you. Using Reverse Title has no negative impact on SEO. If your site has an up to date sitemap.xml file, search engines will simply pick up your site’s new titles in a couple days. (An important exception is emojis. Unicode characters are fine, but *don’t use emojis in your `<title>` tags*.)

Reverse Title was developed for the [McMinnville Chess Club](https://macchess.org) website. If you find this plugin useful, consider [making a donation](https://macchess.org/donate) to the McMinnville Chess Club!


## Features

**Title reversal**

Applies to posts, pages, and any registered custom post type. The front page is always excluded. The reversal is applied via the `document_title_parts` filter, which means it affects the `<title>` tag used by browsers and search engines.

**Custom separator**

By default Reverse Title uses whatever separator WordPress is configured to use (usually –). A custom separator can be set under Settings -> Reverse Title, for example `·`, `|`, or even `♝`. Leave the field blank to fall back to the WordPress default.

**Per-post opt-out**

Individual posts and pages can be excluded from title reversal. A Reverse Title meta box appears in the sidebar of the post and page editors with a single checkbox: *Don't reverse page title and site name for this post*. The meta box appears on all public post types, including custom post types. The checkbox state is stored as post meta and deleted (not set to 0) when unchecked, keeping the database clean.

**Developer filters**

The reversal condition is passed through the `wp_reverse_title_enabled` filter so other plugins or themes can override the logic without modifying this file:

```php
// Disable reversal on a specific page by ID
add_filter( 'wp_reverse_title_enabled', function( $enabled ) {
    return ( is_page( 42 ) ) ? false : $enabled;
} );
```

The separator used in reversed titles is passed through the `wp_reverse_title_separator` filter, allowing a different separator per post type or per post without touching Settings:

```php
// Use a different separator on a specific post type
add_filter( 'wp_reverse_title_separator', function( $sep ) {
    return is_singular( 'product' ) ? '·' : $sep;
} );
```


## Installation

1. Upload the `wp-reverse-title` folder to `wp-content/plugins/`.
2. Activate the plugin from the Plugins admin screen.
3. Title reversal is active immediately on all singular posts and pages.


## Configuration

All options are under Settings -> Reverse Title.

**Custom separator**

Replaces the default WordPress title separator in reversed titles. Accepts any length of text - use this to add a custom tagline or slogan between your site name and page title. Leave blank to use the WordPress default. A live preview updates as you type, and a Reset link clears the field back to blank in one click.

**Per-post opt-out**

When enabled, a Reverse Title meta box appears in the editor sidebar on all public post types, allowing individual posts to opt out of title reversal. Enabled by default. Disable this if you don't need per-post control and prefer a more minimal editor sidebar.


## Limitations

* The custom separator applies globally to all titles, not per post type or per post.
* Titles for archives, 404s, and search results are unaffected.
* While Unicode separators are supported by all modern browsers and common characters like `·` or `|` are safe , some screen readers may announce characters by name. For example, instead of “pi” the 𝜋 symbol may be announced as “MATHEMATICAL BOLD ITALIC SMALL PI” instead.


## Troubleshooting

**The title order isn't changing.**

Confirm the plugin is active. If the front page is a static page, its title is intentionally left unchanged. Check whether the post has the per-post opt-out checkbox enabled.

**The custom separator isn't appearing.**

The separator is applied via a late-priority `document_title_separator` filter that only fires when a reversal is happening. If another plugin or theme hooks into `document_title_separator` with a priority higher than 99, it may override the custom separator. Try deactivating other plugins one at a time, and if you find the conflicting plugin let me know by opening a GitHub issue.

**The custom separator appears garbled.**

Check that your database is UTF-8 encoded. WordPress uses UTF-8 by default, but older WordPress sites may still be using latin1. RSS readers, text-based browsers, and older systems and their system fonts may also not support your favorite Unicode characters, but that’s not a problem with WordPress. Still, choose wisely (𐃯), and *don’t put emojis in your `<title>` tags*. It will technically work, but [a 2022 case study](https://www.searchpilot.com/resources/case-studies/seo-testing-lessons-emoji-title-tags) showed a measurable drop in traffic on sites using emojis in their titles.


## Data

* The custom separator is stored in `wp_options` under `wp_reverse_title_separator`.
* The per-post opt-out setting is stored in `wp_options` under `wp_reverse_title_show_meta_box`.
* Per-post opt-outs are stored as post meta keyed `_wp_reverse_title_opt_out` on the post.

Reverse Title tries to play nice, and uninstalling the plugin deletes both options and all per-post opt-out meta entries via `delete_post_meta_by_key()`.


## License

GPL-2.0-or-later
