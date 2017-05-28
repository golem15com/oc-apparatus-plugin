<?php

return [
    'labels'      => [
        'pluginName' => 'Business logic scenario processor',
    ],
    'errors'      => [
        'pageWithComponentNotFound' => 'Component %s is not bound to any page in CMS.',
        'parameterNotFound'         => 'Parameter %s was not found in component %s configuration.',
    ],
    'settings'    => [
        'messaging-label'          => 'Messaging',
        'messaging-description'    => 'Provides notifications messages engine',
        'messaging-tab'            => 'Messaging',
        'messaging-layout'         => 'Layout',
        'messaging-openAnimation'  => 'Opening Animation',
        'messaging-closeAnimation' => 'Closing Animation',
        'messaging-theme'          => 'Theme',
        'messaging-template'       => 'Template',
        'messaging-timeout'        => 'Timeout',
        'messaging-dismissQueue'   => 'Dismiss queue',
        'messaging-force'          => 'Force',
        'messaging-modal'          => 'Show as modal',
        'messaging-maxVisible'     => 'Max visible time',
    ],
    'permissions' => [
        'tab'             => 'Apparatus',
        'access_settings' => 'Settings access',
    ],
    'strings'     => [
        'inject_main'         => 'Inject main script',
        'inject_main_desc'    => 'Main script for displaying messages on framework events',
        'inject_noty'         => 'Inject Noty.js',
        'inject_noty_desc'    => 'Noty.js library for showing notifications.',
        'inject_animate'      => 'Inject animate.css',
        'inject_animate_desc' => 'Inject animation css styles',
    ],
];