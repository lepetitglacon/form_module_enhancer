<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Form Backend Module Enhancer',
    'description' => 'Enhance ext:form backend module with a search bar and additional fields in the FormManager',
    'category' => 'module',
    'author' => 'petitglacon',
    'author_email' => 'estebangagneur03@gmail.com',
    'state' => 'beta',
    'clearCacheOnLoad' => 1,
    'version' => '0.0.1',
    'constraints' => [
        'depends' => [
            'typo3' => '12.0.0-12.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];