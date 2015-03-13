<?php
namespace kcmerrill\yoda;

class shell {
    var $cli;
    var $lifted = array();
    var $commands = array();
    var $dry_run = false;

    function __construct($cli) {
        $this->cli = $cli;
    }

    function dryRun($dry_run = false) {
        $this->dry_run = $dry_run;
    }

    function commands(){
        return is_array($this->commands) ? array_filter($this->commands) : array();
    }

    function addCommand($cmd) {
        $this->commands[] = $cmd;
    }

    function cd($dir) {
        chdir($dir);
        $this->addCommand('cd ' . getcwd());
    }

    function executeCommandForeground($command) {
        $this->commands[] = $command;
        if($this->dry_run) {
            return true;
        }
        passthru($command, $results);
    }

    function execute($command, $interactive = false, $ignore_yoda_response = false, $do_not_fail = false ) {
        $output = $results = false;
        $this->commands[] = $command;

        if($this->dry_run) {
            /* do not execute the command! */
            return true;
        }

        if($interactive){
            passthru($command, $results);
        } else {
            exec($command . ($interactive ?  '' : ' &> /dev/null'), $output, $results);
        }

        //Useful for prompts, etc
        if($ignore_yoda_response) {
            return $results;
        }

        //Don't show the user the command, just in case
        $command = str_replace('&> /dev/null', '', $command);
        if($results >= 1 && !$do_not_fail) {
            $this->cli->out('<red>[Do Not]</red> <white>' . $command . '</white>');
            exit(1);
        } else if($results >= 1) {
            $this->cli->out('<yellow>[Worry you should not]</yellow> <white>' . $command . '</white>');
        }else {
            $this->cli->out('<green>[Do]</green> <white>' . $command . '</white>');
        }
        return $results;
    }

    function executeInstructions($instructions, $interactive) {
        foreach($instructions as $command) {
            $this->execute($command, $interactive);
        }
    }
    function executeLiftInstructions($instructions, $config, $interactive = false) {
        foreach($instructions as $type=>$commands) {
            foreach($commands as $command) {
                $interactive_type = in_array($type, array('prompt','prompt_password','setup','success'));
                $do_not_fail = in_array($type, array('kill','remove'));
                $results = $this->execute($command, $interactive || $interactive_type, $interactive_type, $do_not_fail);
            }
        }
        foreach($config as $container_name=>$configuration) {
            if(isset($configuration['success'])) {
                $configuration['success'] = is_string($configuration['success']) ? array($configuration['success']) : $configuration['success'];
                foreach($configuration['success'] as $command) {
                    $this->execute($command, $interactive, true);
                }
            }
            if(isset($configuration['notes'])){
                $configuration['notes'] = is_array($configuration['notes']) ? $configuration['notes'] : array($configuration['notes']);
                foreach($configuration['notes'] as $note) {
                    $this->execute('echo "' . $note . '"', false, true, true);
                }
            }
        }
    }
}
