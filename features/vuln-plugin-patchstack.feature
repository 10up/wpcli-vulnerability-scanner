Feature: Show vulnerability status for Gutenberg plugin while using Patchstack

  Scenario: Show vulnerable plugin
    Given a WP install

    When I run `wp plugin install gutenberg --version=14.1.0 --force`
    Then STDOUT should not be empty

    When I run `wp vuln plugin-check --no-color`
    Then STDOUT should be a table containing rows:
      | name                        | installed version | status                                                                                               | introduced in | fix       |
      | gutenberg                   | 14.1.0            | WordPress Gutenberg plugin <= 13.7.3 - Authenticated Stored Cross-Site Scripting (XSS) vulnerability | n/a           | Not fixed |

