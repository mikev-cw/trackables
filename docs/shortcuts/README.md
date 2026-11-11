# Trackables: Apple Shortcuts

Open any trackable’s **Apple Shortcuts** page, from its records page or dashboard menu. Download the signed `.shortcut` file and open it on an iPhone, iPad, or Mac.

During import, the shortcut asks for three setup values:

| Setup value | Where to find it |
| --- | --- |
| API token | Reuse a Sanctum API token, without `Bearer `, or use **Create API token** on the setup page. The new token is shown once. |
| API URL | Copy the API URL displayed on the setup page, ending in `/api/trackable`. |
| Trackable UID | Copy the UID displayed on the setup page. UIDs keep working after alias changes. |

Rename each installed copy for its trackable. These values can also be edited in the first three Text actions. The same signed template supports every trackable. The setup values select the account, server, and trackable after download.

The signed file ships with the app in `resources/shortcuts`. Trackables serves those bytes as-is on any supported host. Installing Trackables and downloading the file require no macOS tooling, Python, Apple credentials, or calls to Apple from the web server.

Each run:

1. Fetches `GET /api/trackable/{uid}` with bearer authentication and reads `data.schema`.
2. Prompts once per field. Optional enums and booleans include **Don’t fill** in the choice list. Other optional fields accept an empty answer to skip, without a preliminary confirmation prompt. Skipped fields are omitted from the request; zero and No are preserved.
3. Offers only active enum choices, displayed as `label [key]`, and submits the selected UID. Lists refresh on every run. An optional enum with no active choices still offers **Don’t fill**; a required enum with no choices stops before submission.
4. Uses numeric input and date pickers for required numbers and dates. Optional numbers/dates use text input so they can be left empty: dates use `YYYY-MM-DD`, datetimes use `YYYY-MM-DDTHH:mm:ss±HH:mm`. JSON fields accept JSON text; image fields accept a stored reference. The API currently treats `calc` as text input.
5. Posts the assembled dictionary to `/api/trackable/{uid}/record`. The server assigns the current record date. The final Show Result action displays the response’s human-readable `message`, with a fallback if no message exists.

Cancel a prompt to stop without submitting. The server enforces required fields, integer values, ranges, and formats. HTTP/network errors may be presented directly by Apple’s Get Contents of URL action.

A configured shortcut contains your token. Keep it private. Tokens created on the setup page grant account API access, like existing Sanctum tokens. A local Herd `.test` address must be reachable from your device; otherwise use a reachable hosted address.

## Rebuild on macOS when changing the shortcut

From the project root:

```sh
python3 docs/shortcuts/build_shortcut.py /tmp/trackables-new-record.unsigned.shortcut
shortcuts sign --mode anyone \
  --input /tmp/trackables-new-record.unsigned.shortcut \
  --output 'resources/shortcuts/Trackables - New Record.shortcut'
```

Apple validates the exported template when it is signed. The `anyone` mode allows anyone with Apple Shortcuts to import it. Commit the signed file with generator changes so subsequent installations include it. [Apple’s signing documentation](https://support.apple.com/guide/shortcuts-mac/apd455c82f02/mac)

```sh
PYTHONDONTWRITEBYTECODE=1 python3 docs/shortcuts/test_shortcut.py
php artisan test --compact
```

Checks cover native parameter shapes, action references, token offsets, control flow, import-question targets, active enum filtering, refreshed labels, UID submission, skipped fields, zero values, JSON escaping, and readable API messages. Feature tests cover download access, ownership, disabled trackables, and one-time token display. Apple’s signer checks the import package; a physical-device run is still required to verify the complete native prompt experience.

Serialization references: [Shortcuts action metadata](https://github.com/EachSheep/ShortcutsBench/blob/master/data/all_api2parasummary.json), [control-flow examples](https://github.com/viticci/shortcuts-playground-plugin/blob/main/codex/skills/shortcuts-playground/CONTROL_FLOW.md), and [import-question format](https://github.com/sebj/iOS-Shortcuts-Reference).
