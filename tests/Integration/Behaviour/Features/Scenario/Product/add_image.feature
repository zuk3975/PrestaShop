# ./vendor/bin/behat -c tests/Integration/Behaviour/behat.yml -s product --tags add-image
@reset-database-before-feature
@clear-cache-after-feature
@reset-img-after-feature
@add-image
Feature: Add product image from Back Office (BO)
  As an employee I need to be able to add new product image

  Scenario: Add new product image
    Given I add product "product1" with following information:
      | name       | en-US:bottle of beer |
      | is_virtual | false                |
    And product "product1" type should be standard
#todo    And product "product1" should have no images
    When I add new product "product1" image "app_icon.png"