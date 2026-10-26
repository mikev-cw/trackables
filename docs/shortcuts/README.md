# Trackables: New Record for iPhone / iPad

Open **Trackables - New Record.shortcut** on your iPhone or iPad and add it to Shortcuts. You can AirDrop the file from your Mac or save it in iCloud Drive and open it from Files.

If you imported the earlier version with blank conditions or inputs, replace that copy with this rebuilt file. The repair updates the native If variable wrappers, HTTP URL inputs, dictionary targets, count inputs, date inputs, and custom date formats. Copy your three configuration values to the replacement.

The current version also fixes active-enum filtering: JSON boolean flags are coerced to numbers and compared numerically. Earlier versions compared their text representation to `1`, which could reject active choices. The required-field flag uses the same numeric handling. Regression fixtures now use actual JSON booleans, as returned by the API, as well as numeric flags.

Edit the shortcut's first three **Text** actions:

| Text action | Replace with |
| --- | --- |
| `PASTE_API_TOKEN_HERE` | Your Sanctum API token, without `Bearer ` |
| `https://YOUR_DOMAIN/api/trackable` | Your externally reachable Trackables URL, ending in `/api/trackable`, without a trailing slash |
| `YOUR_TRACKABLE_ALIAS` | The trackable alias (a UID also works) |

Run the shortcut and allow access to your Trackables domain when Shortcuts asks.

Each run:

1. Fetches `GET /api/trackable/{alias}` with `Authorization: Bearer {token}` and `Accept: application/json`.
2. Reads `data.schema` and prompts for each field. Optional fields offer **Fill in / Skip**.
3. For enums, offers only values with `is_active = true`. Choices display `label [key]`; the record contains the selected value's **UID**. Lists are downloaded again on every run.
4. Prompts for numbers with numeric input, booleans with Yes/No, and dates or datetimes with date pickers. Other types use text input. Enter valid JSON text for JSON fields; image fields take a stored reference as text, without uploading a photo. The current API treats `calc` as text input rather than evaluating its formula on submission.
5. Posts the assembled dictionary to `/api/trackable/{alias}/record` as JSON and displays the API response. The server assigns the current record date.

Cancel any prompt to stop before submitting. A required enum without active choices stops the shortcut. API validation errors are shown in the final response; the server enforces required fields, integer values, ranges, and formats.

The shortcut contains your token after configuration. Remove it before sharing a configured copy. A local Herd `.test` address must be reachable from the iPhone/iPad; otherwise use your hosted HTTPS address.

## Rebuild on macOS

From the project root:

```sh
python3 docs/shortcuts/build_shortcut.py /tmp/trackables-new-record.unsigned.shortcut
shortcuts sign --mode anyone \
  --input /tmp/trackables-new-record.unsigned.shortcut \
  --output 'docs/shortcuts/Trackables - New Record.shortcut'
```

Run the regression checks:

```sh
PYTHONDONTWRITEBYTECODE=1 python3 docs/shortcuts/test_shortcut.py
```

Validation performed: native parameter shapes, action references, text-variable offsets, and control-flow brackets were checked. A local simulation checked active-enum filtering, UID submission, refreshed labels, optional fields, typed branches, JSON escaping, and stopping on empty enum lists. These tests now check the exact If wrappers and action-specific inputs that were missing in the first version. Apple's signer checks the import package; it does not verify the editor's fields. An end-to-end run on a physical iPhone/iPad with a configured API token is still required. Direct editor inspection was unavailable because Computer Use permission was not granted.

Serialization was cross-checked against the published [Shortcuts action metadata](https://github.com/EachSheep/ShortcutsBench/blob/master/data/all_api2parasummary.json) and [Shortcuts Playground control-flow examples](https://github.com/viticci/shortcuts-playground-plugin/blob/main/codex/skills/shortcuts-playground/CONTROL_FLOW.md).

Apple documents the underlying [Get Contents of URL action](https://support.apple.com/guide/shortcuts/request-your-first-api-apd58d46713f/ios) and [Ask for Input action](https://support.apple.com/guide/shortcuts/use-the-ask-for-input-action-apd68b5c9161/ios).
