<?php

require './vendor/autoload.php';
require './vendor/funkatron/funit/FUnit.php';

use \FUnit as fu;
use goliatone\support\Verify;

$htmlTemplate = <<<HTML
<div class="report-entry {{status}}">
    <h4>
        <span class="glyphicon glyphicon-{{iconLabel}}-sign"></span>{{label}}
    </h4>
        <p class="report-{{status}}">{{message}}</p>
</div>
HTML;

$cliTemplate = <<<TMP
Verify {{label}}: {{status}}.
{{iconLabel}} {{message}}
--
TMP;


fu::setup(function( ) use ($htmlTemplate, $cliTemplate)
{
    fu::fixture('cliTemplate', $cliTemplate);
    fu::fixture('htmlTemplate', $htmlTemplate);
    fu::fixture('path', '/Users/emilianoburgos/Development/CG/CG-RCCL/Platform/product/admincms/app/api/get/');
    Verify::setTemplate($cliTemplate);
});

fu::teardown(function() {
    // this resets the fu::$fixtures array. May not provide clean shutdown
    fu::reset_fixtures();
});

/////////////////////////////////////////////
// FLATG HELPER METHODS
/////////////////////////////////////////////
fu::test("scriptURL returns the current url", function() {
    fu::ok(true, "yeah");
    $path = fu::fixture('path');
    Verify::that("GET path")
        ->is('is_dir', $path."no")
//        ->and('is_writable', $path)
//        ->and('is_executable', $path)
//        ->not('is_file', $path)
        ->then('The get path is ok {{path}}')
        ->else('The path is fucked up.')
        ->providing('path', $path)
        ->providing('iconLabel', function ($v) {
            return $v->status === 'success' ? 'ok' : 'exclamation';
        })
        ->next("PHP Version")
        ->is('version_compare', PHP_VERSION, '5.3.3', '>=')
        ->then('The PHP version {{PHP_VERSION}} is OK.')
        ->else('The CMS requires PHP 5.3.3 or newer, this version of PHP is {{PHP_VERSION}}')
        ->providing('PHP_VERSION') //we should get from our is
        ->providing('iconLabel', function ($v) {
            return $v->status === 'success' ? 'ok' : 'exclamation';
        });
});



$exit = fu::run();
exit($exit);