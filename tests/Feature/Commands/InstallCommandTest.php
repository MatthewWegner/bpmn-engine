<?php

it('runs the install command and offers to migrate', function () {
    $this->artisan('bpmn:install')
        ->expectsConfirmation('Would you like to run the database migrations now?', 'no')
        ->expectsOutputToContain('BPMN Engine installed successfully!')
        ->assertSuccessful();
});