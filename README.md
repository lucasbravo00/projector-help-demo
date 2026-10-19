# Projector Help Center redesign demo

A working preview of a visual redesign of [projector.help](https://projector.help). The content is the same; the site is rebuilt with Elementor in the Projector app's visual language: Montserrat, dark "aurora" heroes, a glass navigation bar, rounded cards and accordions.

**Open the live demo:** https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/lucasbravo00/projector-help-demo/main/blueprint.json

The link starts a complete WordPress site inside your browser with [WordPress Playground](https://wordpress.org/playground/).

- It takes about half a minute to load.
- You are signed in as an administrator.
- Any changes you make stay in your own browser only; nobody else sees them.

To try editing a page, click **Edit with Elementor** in the top bar.

## Files

- `blueprint.json`: tells Playground what to install (Elementor, Contact Form 7, Hello Elementor and the child theme), then runs `setup.php`.
- `projector-academy-theme.zip`: the Projector child theme shared with the Academy redesign, with the header, footer and styles.
- `pages.json`: the 5 pages in Elementor format. Images and videos point to the existing files on projector.help and YouTube.
- `setup.php` and `lib.php`: create the pages, menus, contact form and Elementor's global colors and fonts.
