# Leuchtfeuer Segment CRUD

Console tooling to create or update lead segments by **alias** or **id**, and optionally **clear segment membership** in two ways: **`--clear`** deletes rows from `lead_lists_leads` (soft), or **`--permanent-clear`** sets `manually_removed = 1` on active rows while keeping the records (useful for Databridge-style / prep workflows where you need one or the other).

## Overview

- Symfony command: `leuchtfeuer:segment:prepare`
- Behaviour is gated by a plugin integration: the command runs only when the integration is **installed and published** (see below).

## Requirements

> [!TIP]
> Other releases of this plugin may target different Mautic versions.

- Mautic **5.x**
- PHP **8.1** or higher

## Installation

### Composer

Install the package and ensure it is deployed under `plugins/LeuchtfeuerSegmentCrudBundle` (see Composer `extra.install-directory-name`).

### Manual install

1. Download / copy the plugin into the Mautic `plugins` directory.
2. The folder name must be **`LeuchtfeuerSegmentCrudBundle`**.
3. In the Mautic UI as an administrator: **Plugins** → **Install/Upgrade Plugins**.

   **Or** with shell access from the Mautic project root:

   ```bash
   php bin/console cache:clear
   php bin/console mautic:plugins:reload
   ```

## Plugin activation (required)

The console command checks that the integration is **published**. If it is disabled, the command exits with an error and asks you to enable it.

1. Log in as an administrator.
2. Open **Settings** (cog) → **Plugins** (or **Integrations**, depending on your Mautic layout).
3. Open **Leuchtfeuer Segment CRUD** (integration key: `SegmentCrud`).
4. **Publish** / enable the integration and save.

Until this is done, `leuchtfeuer:segment:prepare` will refuse to run.

## Console command and help

From the **Mautic project root** (where `bin/console` lives):

```bash
php bin/console leuchtfeuer:segment:prepare --help
```

That lists all options (`--alias`, `--id`, `--name`, `--desc`, `--noupdate`, `--nocreate`, `--clear`, `--permanent-clear`, `--batch-size`, etc.).

Typical examples (after the plugin is published):

```bash
# Soft clear: remove all membership rows for this segment (batched DELETE on lead_lists_leads)
php bin/console leuchtfeuer:segment:prepare --alias=my-segment-alias --clear

# Permanent clear: set manually_removed = 1 on every active membership; rows remain (batched UPDATE)
php bin/console leuchtfeuer:segment:prepare --alias=my-segment-alias --permanent-clear

# Same by numeric segment id (segment must already exist)
php bin/console leuchtfeuer:segment:prepare --id=123 --clear

# Update display name only
php bin/console leuchtfeuer:segment:prepare --alias=my-segment-alias --name="New name"
```

Use `--help` for the authoritative option list and defaults (e.g. `--batch-size` applies to both `--clear` and `--permanent-clear`).

Do **not** pass **`--clear` and `--permanent-clear` together** — the command exits with an error.

- **`--clear`:** batched `DELETE` — no rows left for that segment in `lead_lists_leads`.
- **`--permanent-clear`:** batched `UPDATE … SET manually_removed = 1 WHERE manually_removed = 0` — active membership (what Mautic treats as “in segment”) becomes zero, but history rows stay in the table.

### Mautic events

This command is a **direct console path** and does **not** participate in Mautic’s **event system** the way UI- or API-driven segment flows do. In particular, **do not expect** the same **subscribers / follow-up events** (e.g. segment membership or list change events) to be dispatched or to run in the same order as in core segment processing. If you rely on custom plugins that listen for segment-related events, validate behaviour separately or trigger those side effects by another supported mechanism.

## Tests

### Unit tests (plugin repository / CI)

In this plugin directory, after `composer install`, **CI and the default Composer script run only the unit suite**:

```bash
composer test
# same as:
composer phpunit
```

That executes PHPUnit with `--testsuite unit` (`Tests/Unit`). Functional tests are **not** included.

To run **all** PHPUnit suites defined in `phpunit.xml.dist` (unit + functional) from a standalone plugin checkout:

```bash
composer phpunit:all
```

> [!NOTE]
> Standalone functional tests still expect a full Mautic test bootstrap and database; in practice they are meant to be run from a full Mautic instance (next section).

### Functional tests (full Mautic instance only)

Functional tests extend `Mautic\CoreBundle\Test\MauticMysqlTestCase`. They need:

- A **complete Mautic codebase** (not only this plugin folder).
- **MySQL** and the normal Mautic **test** environment (`APP_ENV=test`, `AppTestKernel`, etc.).
- The plugin installed and loadable like in production.

They are **not** executed in the plugin’s default CI pipeline; run them **manually** when you have a dev/staging Mautic tree (e.g. local or DDEV).

Example from the **Mautic project root** (adjust paths if your tree uses `app/` as CWD for PHPUnit):

```bash
env APP_ENV=test APP_DEBUG=0 KERNEL_CLASS=AppTestKernel \
  bin/phpunit -d memory_limit=2G \
  plugins/LeuchtfeuerSegmentCrudBundle/Tests/Functional
```

If you use **DDEV**, from the host:

```bash
ddev exec env APP_ENV=test APP_DEBUG=0 KERNEL_CLASS=AppTestKernel \
  bin/phpunit -d memory_limit=2G \
  plugins/LeuchtfeuerSegmentCrudBundle/Tests/Functional
```

Some environments require database credentials and tools (`mysqldump` / `mysql` client) to match what Mautic’s functional test base expects; see your Mautic and DDEV documentation if setup fails.

## Troubleshooting

- **Command says the plugin is disabled:** enable **Leuchtfeuer Segment CRUD** under Plugins / Integrations and save.
- After deploying files manually: `php bin/console cache:clear` and `php bin/console mautic:plugins:reload`.
- **Alias vs CLI:** segment aliases are normalized when saved in Mautic; use the **stored** alias (as in the UI or database) when calling `--alias`.

## Author and contact

**Leuchtfeuer Digital Marketing GmbH**

- Issues: GitHub  
- Other: [mautic-plugins@Leuchtfeuer.com](mailto:mautic-plugins@Leuchtfeuer.com)
