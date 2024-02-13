# [Stanford Syndication](https://github.com/SU-SWS/stanford_syndication)


Changelog: [Changelog.md](CHANGELOG.md)

Description
---

This is essentially a webhook trigger to inform the Syndication CMS about new content.

Installation
---

Install this module like any other. [See Drupal Documentation](https://www.drupal.org/docs/extending-drupal/installing-modules)

Configuration
---

Configure the desired content types and the access token at `/admin/config/services/syndication`.

To protect non-prod environments add `$config['stanford_syndication.settings']['enabled'] = FALSE;` to a settings.php file


Contribution / Collaboration
---

You are welcome to contribute functionality, bug fixes, or documentation to this module. If you would like to suggest a fix or new functionality you may add a new issue to the GitHub issue queue or you may fork this repository and submit a pull request. For more help please see [GitHub's article on fork, branch, and pull requests](https://help.github.com/articles/using-pull-requests)
