# PHPUnit Instructions

These are brief instructions for using PHPUnit to run the tests in the /tests folder. It assumes you have PHP
unit set-up on your system already.

1. Download the WordPress test suite from [http://codex.wordpress.org/Automated_Testing](http://codex.wordpress.org/Automated_Testing).
2. Create an empty database on your system.
3. CP wp-tests-config-sample.php to wp-tests-config.php
4. Edit the contents of wp-tests-config.php to have your empty database config and path to the directory for this site.
5. Create an environment variable named WORDPRESS_TEST_ENVIRONMENT on your system that points to the location of includes/bootstrap.php in the WordPress test suite you downloaded.
6. CD to woocommerce-attribute-pricing
7. Execute phpunit