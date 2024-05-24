# Forum Moderation Plugin for Moodle

This plugin adds a forum post reporting feature to Moodle, allowing users to report inappropriate or rule-violating posts to moderators. The plugin also includes email and Moodle web notification systems to alert moderators of new reports.

## Features

* "Report to Moderator" button on each forum post.
* Email and Moodle web notifications for moderators.
* Settings to enable/disable the reporting feature and select roles to receive notifications.

## Installation
### 1.  Installing from Web Interface
1. Download the plugin file (ZIP).
2. Go to your Moodle administration page.
3. Navigate to "Site administration" > "Plugins" > "Install plugins".
4. Upload the downloaded plugin ZIP file.
5. Follow the installation instructions that appear.

### 2.  Installing from Command-Line Interface(CLI)
1. Download the plugin file (.zip) and extract it to your Moodle's local/ directory.
2. Navigate to your Moodle's root directory in your terminal.
3. Run the following command:

```bash
php admin/cli/upgrade.php
```
## Configuration

1. After installation, go to "Site administration" > "Plugins" > "Local plugins" > "Forum Moderation".
2. Set the "Reporting Enabled" option to "Yes" to enable the reporting feature.
3. Select the roles you want to grant access to receive report notifications in the "Selectable Roles" option.
4. Save changes.

## Usage

1. Users will see a "Report to Moderator" button below each forum post.
2. When the button is clicked, users will be asked to provide a reason for the report.
3. The report will be sent to the selected moderators via email and Moodle web notifications.

## Notes

* Ensure your Moodle email settings are configured correctly for email notifications to be sent.


