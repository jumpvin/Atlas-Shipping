=== ATLAS Shipping Management ===
Contributors: atlas
Tags: shipping, operations, passwordless
Requires at least: 6.4
Requires PHP: 8.0
Stable tag: 0.1.6
License: GPLv2 or later

Private frontend foundation for ATLAS Shipping.

== Description ==
Version 0.1.6 adds the backend Shipping Data Foundation: durable request, ordered stop/item, snapshot, repository, and service architecture. Shipping request UI and workflow actions are intentionally not included.

== Installation ==
Upload atlas-shipping-0.1.6.zip through Plugins > Add Plugin > Upload Plugin and activate it. Existing identities, sessions, migrations, and application-page configuration remain compatible.

== Shortcode ==
[atlas_shipping_app]

== Data preservation ==
Deactivation and deletion preserve ATLAS Shipping tables, options, application page, identities, and sessions by default.

== Changelog ==
= 0.1.6 =
* Adds the backend-only Shipping Data Foundation and schema 0.1.3.
* Adds repositories, domain models, services, ordered stops/items, and immutable snapshots.

= 0.1.5 =
* Added responsive authenticated application layout with header, sidebar, and main content area.
* Added dependency-free hash routing with refresh, Back, and Forward support.
* Added Home, Profile, Settings, and future-module placeholder pages.
* Added mobile navigation drawer and frontend diagnostics.

= 0.1.3 =
* Added atomic single-use magic-link claiming.
* Added immediate identity revocation and authentication hardening.
