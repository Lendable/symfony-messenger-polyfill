Feature: Query
  Dispatching a query should find its handler

  Scenario: Dispatch Query
    When I dispatch a query
    Then I should get a response
