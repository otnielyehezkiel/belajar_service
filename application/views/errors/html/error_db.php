<?php

defined('BASEPATH') OR exit('No direct script access allowed');

$system = new System(ResponseStatus::ERROR, strip_tags($message));

$response = new Response();
$response->setData($system);

$response->render(FALSE);
?>