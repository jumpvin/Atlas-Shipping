# Shipping Data Foundation

ATLAS shipping-domain code follows `consumer -> Service -> Repository -> WordPress database`. The public service boundary returns model objects and `WP_Error`; callers do not consume `$wpdb` rows.

`Service::create_draft()` wraps request, stop, and item creation in a database transaction. The supported ATLAS storage engine is transactional InnoDB as created through WordPress `dbDelta`. If a host replaces these tables with a non-transactional engine, rollback cannot be guaranteed; the service fails conservatively and protected diagnostics should be checked before retrying.

Public identifiers are derived only after the database allocates a unique primary key and are protected by a unique index. Relationship keys continue to use internal IDs, so the public format can evolve without rewriting relationships.

Snapshots serialize the request plus ordered stops and items, store the actual immutable JSON and its SHA-256 fingerprint, and expose no update/delete repository operation. Workflow transitions and permissions are deliberately deferred.

All mutation entry points validate the acting ATLAS identity and active parent aggregate. Stop windows accept canonical UTC `YYYY-MM-DD HH:MM:SS` values and reject reversed ranges. Snapshot sequence allocation runs under a row/range lock with a bounded retry and retains the unique index as its final collision guard.
