<?php

defined('BASEPATH') OR exit('No direct script access allowed');

$messages = array(
    'type' => strip_tags(get_class($exception)),
    'message' => strip_tags($message),
    'filename' => strip_tags($exception->getFile()),
    'lineNumber' => strip_tags($exception->getLine())
);
if (defined('SHOW_DEBUG_BACKTRACE') && SHOW_DEBUG_BACKTRACE === TRUE) {
    foreach (debug_backtrace() as $error) {
        if (isset($error['file']) && strpos($error['file'], realpath(BASEPATH)) !== 0) {
            $messages['backtrace'] = array(
                'file' => $error['file'],
                'line' => $error['line'],
                'function' => $error['function']
            );
        }
    }
}

$system = new System(ResponseStatus::ERROR, json_decode($messages));

$response = new Response();
$response->setData($system);

$response->render(FALSE);
?>