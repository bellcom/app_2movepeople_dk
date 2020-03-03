<?php

/**
 * @file
 * Lists available colors and color schemes for the 2movepeople theme theme.
 */

$info = [
  // Available colors and color labels used in theme.
  'fields' => [
    'default' => t('Default color'),
    'default_lighter_5' => t('Default lighter 5%'),
    'default_lighter_10' => t('Default lighter 10%'),
    'default_lighter_15' => t('Default lighter 15%'),
    'default_darken_10' => t('Default darken 10%'),
    'text' => t('Text color'),
  ],
  // Pre-defined color schemes.
  'schemes' => [
    'default' => [
      'title' => t('Virksomhed (default)'),
      'colors' => [
        'default' => '#c13c8d',
        'default_lighter_5' => '#c84e99',
        'default_lighter_10' => '#ce62a4',
        'default_lighter_15' => '#d475af',
        'default_darken_10' => '#8e184e',
      ],
    ],
    'kommune' => [
      'title' => t('Kommune'),
      'colors' => [
        'default' => '#50afe8',
        'default_lighter_5' => '#67b9eb',
        'default_lighter_10' => '#7dc4ee',
        'default_lighter_15' => '#94cef1',
        'default_darken_10' => '#239ae2',
      ],
    ],
  ],

  // CSS files (excluding @import) to rewrite with new color scheme.
  'css' => [
    'dist/css/stylesheet.css',
  ],
];
