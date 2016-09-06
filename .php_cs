<?php

return Symfony\CS\Config\Config::create()
    ->setUsingCache(true)
    ->level(Symfony\CS\FixerInterface::SYMFONY_LEVEL)
    ->fixers([
        'concat_with_spaces',
        'newline_after_open_tag',
        'ordered_use',
        'phpdoc_order',
        'short_array_syntax',
        '-empty_return',
        '-concat_without_spaces',
        '-phpdoc_inline_tag',
    ])
    ->finder(
        Symfony\CS\Finder\DefaultFinder::create()->in(['src', 'tests'])
    )
;
