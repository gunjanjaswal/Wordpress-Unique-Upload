<div align="center">

# Unique Upload Filenames

### A tiny companion plugin for [g33ki Cloud Storage for Media Library](https://wordpress.org/plugins/g33ki-cloud-storage-for-media-library/)

**Stops same-named uploads from silently overwriting each other on your cloud bucket.**

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-21759B?logo=wordpress&logoColor=white)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.0%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-GPLv2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-1.0.0-blue.svg)](https://github.com/gunjanjaswal/Wordpress-Unique-Upload/releases)
[![Add-on for g33ki](https://img.shields.io/badge/Add--on%20for-g33ki%20Cloud%20Storage-FF6B6B.svg)](https://wordpress.org/plugins/g33ki-cloud-storage-for-media-library/)
[![Zero config](https://img.shields.io/badge/Setup-Zero%20config-success.svg)](#-installation)

</div>

---

## Why this exists

If you offload your media to the cloud with **g33ki Cloud Storage for Media Library** and you have the *remove local files* option switched on, WordPress loses the one thing it relies on to keep filenames unique.

Here is the chain of events, step by step:

1. You upload `photo.jpg`. g33ki pushes it to your bucket and then deletes the local copy to save disk space.
2. A week later, a different `photo.jpg` is uploaded (and yes, a lot of AI image tools happily export everything as `unknown.png` or `image.png`).
3. WordPress checks for a clash with `wp_unique_filename()`, but that function only looks at the **local** uploads folder, which is now empty.
4. Seeing no clash, WordPress keeps the name `photo.jpg` and the second file **overwrites the first one on the bucket**.

The result is quietly nasty: one post suddenly shows another post's featured image, social share previews point at the wrong picture, and nothing in the dashboard tells you what happened. The files look fine in the Media Library because the thumbnails were generated separately. Only the full-size original gets clobbered.

**Unique Upload Filenames** closes that gap. It gives every new upload a short unique suffix before the file is ever written, so two uploads can never land on the same path.

---

## How it works

The plugin hooks into WordPress right when an upload arrives and rewrites the filename, adding a compact date plus random token:

```text
unknown.png            ->   unknown-261019-a3f90c.png
festival-poster.png    ->   festival-poster-261019-7b2e14.png
IMG_4471.jpg           ->   IMG_4471-261019-c0d9fa.jpg
```

- The descriptive part of the name is kept, so your URLs stay readable.
- `261019` is the date (`YYMMDD`) and the six characters after it are random hex, so even two uploads of the same file on the same day get different names.
- It runs for both browser uploads (Media Library, block editor, classic editor) and programmatic imports (`wp media import`, `media_sideload_image`).
- Generated thumbnail sizes inherit the unique base, so every size is unique too.

No database tables, no admin screen, no settings to get wrong. Drop it in and forget it.

---

## Table of contents

- [Installation](#-installation)
- [Compatibility](#-compatibility)
- [Developer filters](#-developer-filters)
- [FAQ](#-faq)
- [Before and after](#-before-and-after)
- [Changelog](#-changelog)
- [Contributing](#-contributing)
- [Support](#-support)
- [Author](#-author)
- [License](#-license)

---

## Installation

You can run this as a normal plugin or as a must-use plugin. Pick whichever fits how you manage the site.

### Option A: as a regular plugin

1. Download this repository as a ZIP, or clone it.
2. Copy the folder into `wp-content/plugins/`.
3. Go to **Plugins** in wp-admin and click **Activate** on *Unique Upload Filenames for g33ki Cloud Storage*.

### Option B: as a must-use plugin (always on, cannot be deactivated by accident)

1. Copy `unique-upload-filenames.php` into `wp-content/mu-plugins/`.
2. Create that folder first if it does not exist.

That is the whole setup. There is nothing to configure.

> **Note on existing files:** the plugin only renames *new* uploads. Files already in your Media Library are left exactly as they are.

---

## Compatibility

| Works with | Notes |
| --- | --- |
| **g33ki Cloud Storage for Media Library** | This is the add-on's main target. Recommended whenever *remove local files* is enabled. |
| Any other offload or CDN plugin | The same overwrite problem exists with any plugin that removes local copies. This fixes all of them. |
| Vanilla WordPress with no offload | Harmless. You get guaranteed-unique names and never see WordPress's `-1`, `-2`, `-3` suffixes again. |
| Multisite | Works per site. Each upload gets its own unique name. |

**Requirements:** WordPress 5.0 or newer, PHP 7.0 or newer.

---

## Developer filters

Everything is optional. The defaults are sensible, but if you want to shape the behaviour, three filters are available.

| Filter | What it does | Default |
| --- | --- | --- |
| `wuuf_enabled` | Return `false` to skip renaming for a given upload. | `true` |
| `wuuf_token` | Change the token that gets appended to the base name. | `YYMMDD-xxxxxx` |
| `wuuf_filename` | Override the final filename after it has been built. | the rewritten name |

### Examples

Turn the renaming off for a specific file type:

```php
add_filter( 'wuuf_enabled', function ( $enabled, $file ) {
	if ( isset( $file['name'] ) && str_ends_with( $file['name'], '.svg' ) ) {
		return false; // leave SVG icons with their original names
	}
	return $enabled;
}, 10, 2 );
```

Use a plain timestamp instead of the date-plus-random token:

```php
add_filter( 'wuuf_token', function () {
	return (string) time();
} );
```

Lowercase every uploaded filename while you are at it:

```php
add_filter( 'wuuf_filename', function ( $new_name ) {
	return strtolower( $new_name );
} );
```

---

## FAQ

**Will this rename the files I already uploaded?**
No. It only touches new uploads. Your existing library is untouched.

**Do my URLs become ugly random strings?**
Not really. The original name stays in front, so `holiday-banner.png` becomes `holiday-banner-261019-a3f90c.png`. Still readable, still searchable, just safe.

**I do not use g33ki. Is this still useful?**
Yes. Any time local files are removed after an offload, the same overwrite risk exists. Even without any offload plugin, you simply stop seeing WordPress's automatic `-1`, `-2` renames because every name is unique from the start.

**Does it slow uploads down?**
No. It generates a few random bytes and rewrites a string. There are no network calls and no database lookups.

**Can I use it as a must-use plugin?**
Yes, and that is often the better choice on a production site because it cannot be switched off from the dashboard by mistake. Just drop the PHP file into `wp-content/mu-plugins/`.

**What about thumbnail sizes?**
They inherit the unique base name, so every generated size is unique too.

---

## Before and after

Without the plugin, with local files removed after offload:

```text
Upload 1:  photo.jpg   ->  bucket: /uploads/2026/10/photo.jpg
Upload 2:  photo.jpg   ->  bucket: /uploads/2026/10/photo.jpg   (overwrites upload 1)
```

With the plugin:

```text
Upload 1:  photo.jpg   ->  bucket: /uploads/2026/10/photo-261019-a3f90c.jpg
Upload 2:  photo.jpg   ->  bucket: /uploads/2026/10/photo-261019-7b2e14.jpg   (safe, separate file)
```

---

## Changelog

### 1.0.0
- First release.
- Appends a unique `YYMMDD-xxxxxx` token to every upload.
- Covers browser uploads and programmatic sideloads.
- Adds the `wuuf_enabled`, `wuuf_token`, and `wuuf_filename` filters.

---

## Contributing

Issues and pull requests are welcome. If you hit an edge case with a particular offload setup, open an issue with the steps to reproduce and I will take a look.

---

## Support

If this saved you from a confusing afternoon of swapped images, you can say thanks here:

[![Ko-fi](https://img.shields.io/badge/Ko--fi-Buy%20me%20a%20coffee-FF5E5B?logo=ko-fi&logoColor=white)](https://ko-fi.com/gunjanjaswal)

Questions or ideas: [hello@gunjanjaswal.me](mailto:hello@gunjanjaswal.me)

---

## Author

**Gunjan Jaswal**

- Website: [www.gunjanjaswal.me](https://www.gunjanjaswal.me)
- GitHub: [@gunjanjaswal](https://github.com/gunjanjaswal)
- Email: [hello@gunjanjaswal.me](mailto:hello@gunjanjaswal.me)

---

## License

Released under the [GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html) license, the same license WordPress itself uses. You are free to use, change, and share it.
