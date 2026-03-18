//Add any of your own step definitions here
const { Given, defineParameterType } = require('@badeball/cypress-cucumber-preprocessor')


/**
 * @module EnhanceFormStatus
 * @author Mintoo Xavier <min2xavier@gmail.com>
 * @example I should see {formStatusIcon} bubble with the form status {string}
 * @param {string} formStatusIcon - form status icon - available options: 'red', 'yellow', 'green'
 * @param {string} status - form status
 * @description verifies the form status and color of bubble
 */
Given('I should see {formStatusIcon} bubble with the form status {string}', (icon, status) => {
    cy.get('#questiontable').find(formStatusIcon[icon]).next().contains(status)
})


/**
 * @module EnhanceFormStatus
 * @author Mintoo Xavier <min2xavier@gmail.com>
 * @example I should NOT see {formStatusIcon} form status bubble
 * @param {string} formStatusIcon - form status icon - available options: 'red', 'yellow', 'green'
 * @description verifies form status bubble is not visible
 */
Given('I should NOT see {formStatusIcon} form status bubble', (icon) => {
    cy.get('#questiontable').should('not.contain', formStatusIcon[icon])
})

