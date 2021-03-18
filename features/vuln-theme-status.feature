Feature: Show vulnerability status for themes

  Scenario: Get status
    Given a WP install

    When I run `wp vuln theme-status`
    Then STDOUT should contain:
      """
      Nothing to update
      """

  Scenario: Show vulnerable theme
    Given a WP install
    And I run `wp theme path`
    And save STDOUT as {THEME_DIR}

    When I run `wp theme install https://github.com/easydigitaldownloads/Digital-Store/archive/1.3.zip`
    Then STDOUT should not be empty

    When I run `wp vuln theme-status --no-color`
    Then STDOUT should be a table containing rows:
      | name          | installed version | status                          | fix            |
      | Digital-Store | 1.3               | Digital Store - Unspecified XSS | Fixed in 1.3.3 |

    When I run `wp vuln theme-status --porcelain`
    Then STDOUT should be:
      """
      Digital-Store
      """
