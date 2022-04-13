Feature: Show vulnerability status for plugins

  Scenario: Get status
    Given a WP install

    When I run `wp vuln plugin-status`
    Then STDOUT should contain:
      """
      Nothing to update
      """

  Scenario: Should not show vulnerable plugin on older version.
    Given a WP install

    When I run `wp plugin install sassy-social-share --version=3.3.22 --force`
    Then STDOUT should not be empty

    When I run `wp vuln plugin-status --no-color`
    Then STDOUT should be a table containing rows:
      | name     | installed version | status                                                       | introduced in | fix            |
      | sassy-social-share | 3.3.22             | No vulnerabilities reported for this version of sassy-social-share | n/a | n/a |

    When I run `wp vuln plugin-status --porcelain`
    Then STDOUT should be empty

  Scenario: Show vulnerable plugin
    Given a WP install

    When I run `wp plugin install sassy-social-share --version=3.3.23 --force`
    Then STDOUT should not be empty

    When I run `wp vuln plugin-status --no-color`
    Then STDOUT should be a table containing rows:
      | name     | installed version | status                                                       | introduced in | fix            |
      | sassy-social-share | 3.3.23             | Sassy Social Share 3.3.23 - Missing Access Controls to PHP Object Injection | 3.3.23 | Fixed in 3.3.24 |

    When I run `wp vuln plugin-status --porcelain`
    Then STDOUT should be:
      """
      sassy-social-share
      """

  Scenario: Should not show vulnerable plugin on fixed version.
    Given a WP install

    When I run `wp plugin install sassy-social-share --version=3.3.24 --force`
    Then STDOUT should not be empty

    When I run `wp vuln plugin-status --no-color`
    Then STDOUT should be a table containing rows:
      | name     | installed version | status                                                       | introduced in | fix            |
      | sassy-social-share | 3.3.24             | No vulnerabilities reported for this version of sassy-social-share | n/a | n/a |

    When I run `wp vuln plugin-status --porcelain`
    Then STDOUT should be empty
