//Add any of your own step definitions here
const { Given, defineParameterType } = require('@badeball/cypress-cucumber-preprocessor')

defineParameterType({
    name: 'formStatusIcon',
    regexp: /red|yellow|green/
})

const formStatusIcon = {
    'red' : 'img[src*=circle_red]',
    'yellow' : 'img[src*=circle_yellow]',
    'green' : 'img[src*=circle_green]'
}

defineParameterType({
    name: 'emTableName',
    regexp: /external module logs/
})

const emTableName = {
    'external module logs' : '#external-module-logs-wrapper table[id^="DataTables_Table_"]'
}


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


/**
 * @module ExternalModule
 * @author Mintoo Xavier <min2xavier@gmail.com>
 * @example I close the dialog box for the external module {string}
 * @param {string} name - Name of external module
 * @description Close the dialog box for the external module
 */
Given("I close the dialog box for the external module {string}", (name) => {
    cy.get('.modal-dialog').contains(name).parents('div[class="modal-header"]').within(() => {
        cy.get('button[class=close]').click()
    })
})


/**
 * @module EnhanceFormStatus
 * @author Mintoo Xavier <min2xavier@gmail.com>
 * @example I should see {int} row(s) in the {emTableName} table
 * @param {int} num - number of row(s)
 * @param {string} emTableName - available options: 'external module logs'
 * @description verifies the table contains the specified number of row(s)
 */
Given('I should see {int} row(s) in the {emTableName} table', (num, tableName) => {
    const element = emTableName[tableName] + ' tbody tr'
    cy.get(element).its('length').then ((rowCount) => {
        expect(rowCount).to.be.equal(num)
    })
})
