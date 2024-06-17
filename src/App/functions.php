<?php 

declare(strict_types=1);

use Framework\Http;

function dd(mixed $value)
{
    echo "<pre>";
    var_dump($value);
    echo "</pre>";
    die();
}

function e (mixed $value) : string { //e is short for escape
    return htmlspecialchars((string) $value);
}

function redirectTo($path)
{
    header("Location: {$path}");
    http_response_code(HTTP::REDIRECT_STATUS_CODE);
    exit; //prevents error from occuring after redirection
}