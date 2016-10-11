<?php

return array(
    //Since the cli module is instantiated with a FQ class name parameter and the entry point is not index.php it's not necessary to register it as a module

    /*'cli'      => array(
        'className' => 'Cli\Module',
        'path'      => '../brandme/cli/Module.php'
    ),*/
    'frontend' => array(
        'className' => 'Frontend\Module',
        'path'      => '../brandme/frontend/Module.php'
    ),
    'backend'  => array(
        'className' => 'Backend\Module',
        'path'      => '../brandme/backend/Module.php'
    )
);