<?php

return [
    'labels'      => [
        'pluginName' => 'Business logic scenario processor',
        'jobs'       => 'Jobs',
        'apparatus'  => 'Apparatus',
    ],
    'errors'      => [
        'pageWithComponentNotFound' => 'Component %s is not bound to any page in CMS.',
        'parameterNotFound'         => 'Parameter %s was not found in component %s configuration.',
    ],
    'settings'    => [
        'messaging-label'          => 'Notifications',
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
        'messaging'                => 'Notifications',
    ],
    'permissions' => [
        'tab'             => 'Apparatus',
        'access_settings' => 'Settings access',
        'access_jobs'     => 'Access Jobs',
    ],
    'strings'     => [
        'inject_main'         => 'Inject main script',
        'inject_main_desc'    => 'Main script for displaying messages on framework events',
        'inject_noty'         => 'Inject Noty.js',
        'inject_noty_desc'    => 'Noty.js library for showing notifications.',
        'inject_animate'      => 'Inject animate.css',
        'inject_animate_desc' => 'Inject animation css styles',
    ],
    'job'         => [
        'new'           => 'New Job',
        'label'         => 'Job',
        'create_title'  => 'Create Job',
        'update_title'  => 'Edit Job',
        'preview_title' => 'Preview Job',
        'list_title'    => 'Manage Jobs',
    ],
    'jobs'        => [
        'delete_selected_confirm' => 'Delete the selected Jobs?',
        'menu_label'              => 'Jobs',
        'return_to_list'          => 'Return to Jobs',
        'delete_confirm'          => 'Do you really want to delete this Job?',
        'delete_selected_success' => 'Successfully deleted the selected Jobs.',
        'delete_selected_empty'   => 'There are no selected Jobs to delete.',
        'in_queue'                => 'Queued',
        'in_progress'             => 'Processing',
        'complete'                => 'Complete',
        'error'                   => 'Crashed',
        'stopped'                 => 'Stopped',
        'unknown'                 => 'Unknown',
    ],
    'listtoggle'  => [
        'title_true'  => 'Yes',
        'title_false' => 'No',
        'text_true'   => 'Enabled',
        'text_false'  => 'Disabled',
    ],
];